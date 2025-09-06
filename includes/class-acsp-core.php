<?php
namespace aCSP;

/**
 * CSP engine – everything that used to live in the huge class.
 */
final class Core {

    private static $instance = null;

    /** @var bool */
    private $nonce_enabled;

    /** @var array */
    private $policy_options;

    /** @var string reject|report */
    private $mode;

    /* -------------------------------------------------------------- */
    public static function init() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->nonce_enabled  = (bool) get_option( 'acsp_add_dynamic_nonce', 1 );
        $this->policy_options = get_option( 'acsp_policy', [] );
        $this->mode           = get_option( 'acsp_mode', 'reject' );

        $this->hooks();
    }

    private function hooks() {
        add_action( 'init', [ $this, 'initialize_nonce' ] );
        add_action( 'wp_head', [ $this, 'output_csp_meta_tag' ], 1 );
        add_action( 'template_redirect', [ $this, 'send_csp_header' ] );

        if ( $this->nonce_enabled ) {
            add_filter( 'script_loader_tag', [ $this, 'inject_nonce_to_scripts' ], 10, 3 );
            add_filter( 'style_loader_tag', [ $this, 'inject_nonce_to_styles' ], 10, 3 );
            add_action( 'template_redirect', [ $this, 'start_output_buffering' ], -1 );
            add_action( 'shutdown', [ $this, 'end_output_buffering' ], 0 );
        }

        add_action( 'init', [ $this, 'refresh_nonce_in_policy' ] );
    }

    /* -------------------------------------------------------------- */
    public function initialize_nonce() {
        if ( ! defined( 'ACSP_NONCE' ) ) {
            define( 'ACSP_NONCE', base64_encode( random_bytes( 16 ) ) );
        }
    }

    public function output_csp_meta_tag() {
        if ( ! get_option( 'acsp_enable_meta_tag', 0 ) ) {
            return;
        }
        if ( ! $this->should_output_csp() ) {
            return;
        }
        if ( defined( 'ACSP_NONCE' ) ) {
            $csp = $this->generate_csp_policy( false );
            echo '<meta http-equiv="Content-Security-Policy" content="' . esc_attr( $csp ) . '">' . "\n";
        }
    }

    public function send_csp_header() {
		if ( empty( array_filter( $this->policy_options ) ) ) {
      		return;
    	}
        if ( ! $this->should_output_csp() ) {
            return;
        }
        if ( ! headers_sent() && defined( 'ACSP_NONCE' ) ) {
            $csp    = $this->generate_csp_policy( true );
            $header = ( 'report' === $this->mode )
                ? 'Content-Security-Policy-Report-Only: '
                : 'Content-Security-Policy: ';
            header( $header . $csp );
        }
    }

    private function should_output_csp() {
        $current_preset = get_option( 'acsp_current_preset', '' );
        if ( ! empty( $current_preset ) ) {
            return true;
        }
        if ( ! empty( $this->policy_options ) ) {
            foreach ( $this->policy_options as $directive => $value ) {
                if ( ! empty( trim( $value ) ) ) {
                    return true;
                }
            }
        }
        return false;
    }

    public function refresh_nonce_in_policy() {
        if ( ! defined( 'ACSP_NONCE' ) || ! $this->should_output_csp() ) {
            return;
        }
        $dirty = false;
        foreach ( $this->policy_options as $dir => &$value ) {
            $new_value = str_replace( '[NONCE]', ACSP_NONCE, $value );
            if ( $new_value !== $value ) {
                $value  = $new_value;
                $dirty  = true;
            }
        }
        if ( $dirty ) {
            update_option( 'acsp_policy', $this->policy_options );
            $this->policy_options = get_option( 'acsp_policy', [] );
        }
    }

    /* -------------------------------------------------------------- */
    public function generate_csp_policy( $for_header = true ) {
        $directives = [];
		if ( empty( array_filter( $this->policy_options ) ) ) {
        	return '';
    	}
        foreach ( $this->policy_options as $key => $value ) {
            if ( '' === trim( $value ) ) {
                continue;
            }
            $directive = trim( $value );

            if ( $this->nonce_enabled && in_array( $key, [ 'script-src', 'style-src' ], true ) ) {
                $directive .= " 'nonce-" . ACSP_NONCE . "'";
            }

            $hash_enabled = (bool) get_option( 'acsp_enable_hashes', 0 );
            $hash_values  = array_filter( (array) get_option( 'acsp_hash_values', [] ) );

            if (
                $hash_enabled
                && ! empty( $hash_values )
                && in_array( $key, [ 'script-src', 'style-src' ], true )
                && str_contains( $directive, "'unsafe-hashes'" )
            ) {
                foreach ( $hash_values as $h ) {
                    $directive .= " '" . esc_attr( trim( $h ) ) . "'";
                }
            }

            $directives[ $key ] = $directive;
        }

        $endpoint = get_option( 'acsp_report_endpoint', '' );
        if ( $endpoint ) {
            $report_to = [
                'group'              => 'csp-endpoint',
                'max_age'            => 86400,
                'endpoints'          => [ [ 'url' => $endpoint ] ],
                'include_subdomains' => false,
            ];
            if ( $for_header && ! headers_sent() ) {
                header( 'Report-To: ' . wp_json_encode( $report_to, JSON_UNESCAPED_SLASHES ) );
            }
            $directives['report-to']  = 'csp-endpoint';
            $directives['report-uri'] = $endpoint;
        }

        $policy = [];
        foreach ( $directives as $dir => $val ) {
            if ( '' !== $val ) {
                $policy[] = $dir . ' ' . $val;
            }
        }
        $policy = apply_filters( 'acsp_policy_array', $policy, $for_header );
        return implode( '; ', $policy );
    }

    /* -------------------------------------------------------------- */
    public function inject_nonce_to_scripts( $tag, $handle, $src ) {
        if ( defined( 'ACSP_NONCE' ) && $src && false === strpos( $tag, ' nonce=' ) ) {
            $tag = str_replace( '<script', '<script nonce="' . ACSP_NONCE . '"', $tag );
        }
        return $tag;
    }

    public function inject_nonce_to_styles( $tag, $handle, $src ) {
        if ( defined( 'ACSP_NONCE' ) && $src && false === strpos( $tag, ' nonce=' ) ) {
            $tag = str_replace( '<link', '<link nonce="' . ACSP_NONCE . '"', $tag );
        }
        return $tag;
    }

    /* -------------------------------------------------------------- */
    public function start_output_buffering() {
        if ( defined( 'ACSP_NONCE' ) ) {
            ob_start( [ $this, 'inject_nonce_to_inline_code' ] );
        }
    }

    public function end_output_buffering() {
        if ( defined( 'ACSP_NONCE' ) && ob_get_length() ) {
            ob_end_flush();
        }
    }

    public function inject_nonce_to_inline_code( $buffer ) {
        if ( ! defined( 'ACSP_NONCE' ) ) {
            return $buffer;
        }
        $nonce = ACSP_NONCE;

        $buffer = preg_replace_callback(
            '/<script\b(?![^>]*\bsrc\b)(?![^>]*\bnonce\b)([^>]*)>/i',
            function ( $m ) use ( $nonce ) {
                return '<script nonce="' . $nonce . '"' . $m[1] . '>';
            },
            $buffer
        );
        $buffer = preg_replace_callback(
            '/<style\b(?![^>]*\bnonce\b)([^>]*)>/i',
            function ( $m ) use ( $nonce ) {
                return '<style nonce="' . $nonce . '"' . $m[1] . '>';
            },
            $buffer
        );
        return $buffer;
    }

    /* -------------------------------------------------------------- */
    public static function get_nonce() {
        return defined( 'ACSP_NONCE' ) ? ACSP_NONCE : '';
    }
}