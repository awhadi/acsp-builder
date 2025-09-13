<?php
/**
 * CSP engine.
 *
 * @package acsp-builder
 */

namespace aCSP;

/**
 * CSP Engine handler – sends headers, injects nonces, buffers output.
 */
final class CSP_Engine {

	/**
	 * Single instance.
	 *
	 * @var self|null
	 */
	private static $instance = null;

	/**
	 * Whether nonces are enabled.
	 *
	 * @var bool
	 */
	private $nonce_enabled;

	/**
	 * Parsed policy array.
	 *
	 * @var array
	 */
	private $policy_options;

	/**
	 * Mode: reject | report.
	 *
	 * @var string
	 */
	private $mode;

	/**
	 * Initialise (or fetch) the singleton.
	 *
	 * @return self
	 */
	public static function init() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Set up properties and hooks.
	 */
	private function __construct() {
		$this->nonce_enabled  = (bool) get_option( 'acsp_add_dynamic_nonce', 1 );
		$this->policy_options = get_option( 'acsp_policy', array() );
		$this->mode           = get_option( 'acsp_mode', 'reject' );

		$this->hooks();
	}

	/**
	 * Register all WordPress hooks.
	 */
	private function hooks() {
		add_action( 'init', array( $this, 'initialize_nonce' ) );
		add_action( 'wp_head', array( $this, 'output_csp_meta_tag' ), 1 );
		add_action( 'template_redirect', array( $this, 'send_csp_header' ) );

		if ( $this->nonce_enabled ) {
			add_filter( 'script_loader_tag', array( $this, 'inject_nonce_to_scripts' ), 10, 3 );
			add_filter( 'style_loader_tag', array( $this, 'inject_nonce_to_styles' ), 10, 3 );
			add_action( 'template_redirect', array( $this, 'start_output_buffering' ), -1 );
			add_action( 'shutdown', array( $this, 'end_output_buffering' ), 0 );
		}

		add_action( 'init', array( $this, 'refresh_nonce_in_policy' ) );
	}

	/**
	 * Create the cryptographic nonce if it does not exist.
	 */
	public function initialize_nonce() {
		if ( ! defined( 'ACSP_NONCE' ) ) {
			// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
			define( 'ACSP_NONCE', base64_encode( random_bytes( 16 ) ) );
		}
	}

	/**
	 * Output CSP as meta tag if the option is enabled.
	 */
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

	/**
	 * Send CSP HTTP header.
	 */
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

	/**
	 * Decide whether a CSP header/tag should be produced.
	 *
	 * @return bool
	 */
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

	/**
	 * Replace [NONCE] placeholder with the real nonce if necessary.
	 */
	public function refresh_nonce_in_policy() {
		if ( ! defined( 'ACSP_NONCE' ) || ! $this->should_output_csp() ) {
			return;
		}
		$dirty = false;
		foreach ( $this->policy_options as $dir => &$value ) {
			$new_value = str_replace( '[NONCE]', ACSP_NONCE, $value );
			if ( $new_value !== $value ) {
				$value = $new_value;
				$dirty = true;
			}
		}
		if ( $dirty ) {
			update_option( 'acsp_policy', $this->policy_options );
			$this->policy_options = get_option( 'acsp_policy', array() );
		}
	}

	/**
	 * Build the complete CSP string.
	 *
	 * @param bool $for_header Generate for HTTP header (adds Report-To).
	 * @return string
	 */
	public function generate_csp_policy( $for_header = true ) {
		$directives = array();
		if ( empty( array_filter( $this->policy_options ) ) ) {
			return '';
		}

		foreach ( $this->policy_options as $key => $value ) {
			if ( '' === trim( $value ) ) {
				continue;
			}

			// Split the directive value into parts for proper ordering.
			$parts = preg_split( '/\s+/', trim( $value ) );

			// Insert hashes right after 'unsafe-hashes' if present.
			$hash_enabled = (bool) get_option( 'acsp_enable_hashes', 0 );
			$hash_values  = array_filter( (array) get_option( 'acsp_hash_values', array() ) );

			if ( $hash_enabled && ! empty( $hash_values ) &&
				in_array( $key, array( 'script-src', 'style-src' ), true ) ) {

				$unsafe_hashes_index = array_search( "'unsafe-hashes'", $parts, true );
				if ( false !== $unsafe_hashes_index ) {
					// Ensure each hash is properly quoted.
					$quoted_hashes = array();
					foreach ( $hash_values as $hash ) {
						$quoted_hashes[] = "'" . esc_attr( trim( $hash ) ) . "'";
					}
					// Insert quoted hashes immediately after 'unsafe-hashes'.
					array_splice( $parts, $unsafe_hashes_index + 1, 0, $quoted_hashes );
				}
			}

			// Add nonce at the end for script/style directives.
			if ( $this->nonce_enabled && in_array( $key, array( 'script-src', 'style-src' ), true ) ) {
				$parts[] = "'nonce-" . ACSP_NONCE . "'";
			}

			// Reconstruct the directive with proper order.
			$directives[ $key ] = implode( ' ', $parts );
		}

		$endpoint = get_option( 'acsp_report_endpoint', '' );
		if ( $endpoint ) {
			$report_to = array(
				'group'              => 'csp-endpoint',
				'max_age'            => 86400,
				'endpoints'          => array( array( 'url' => $endpoint ) ),
				'include_subdomains' => false,
			);
			if ( $for_header && ! headers_sent() ) {
				header( 'Report-To: ' . wp_json_encode( $report_to, JSON_UNESCAPED_SLASHES ) );
			}
			$directives['report-to']  = 'csp-endpoint';
			$directives['report-uri'] = $endpoint;
		}

		$policy = array();
		foreach ( $directives as $dir => $val ) {
			if ( '' !== $val ) {
				$policy[] = $dir . ' ' . $val;
			}
		}
		$policy = apply_filters( 'acsp_policy_array', $policy, $for_header );
		return implode( '; ', $policy );
	}

	/**
	 * Add nonce attribute to <script> tags.
	 *
	 * @param string $tag    Original tag markup.
	 * @param string $handle Script handle.
	 * @param string $src    Script source.
	 * @return string
	 */
	public function inject_nonce_to_scripts( $tag, $handle, $src ) {
		if ( defined( 'ACSP_NONCE' ) && $src && false === strpos( $tag, ' nonce=' ) ) {
			$tag = str_replace( '<script', '<script nonce="' . ACSP_NONCE . '"', $tag );
		}
		return $tag;
	}

	/**
	 * Add nonce attribute to <link> tags for stylesheets.
	 *
	 * @param string $tag    Original tag markup.
	 * @param string $handle Style handle.
	 * @param string $src    Style source.
	 * @return string
	 */
	public function inject_nonce_to_styles( $tag, $handle, $src ) {
		if ( defined( 'ACSP_NONCE' ) && $src && false === strpos( $tag, ' nonce=' ) ) {
			$tag = str_replace( '<link', '<link nonce="' . ACSP_NONCE . '"', $tag );
		}
		return $tag;
	}

	/**
	 * Start output buffering to catch inline scripts/styles.
	 */
	public function start_output_buffering() {
		if ( defined( 'ACSP_NONCE' ) ) {
			ob_start( array( $this, 'inject_nonce_to_inline_code' ) );
		}
	}

	/**
	 * Flush the output buffer.
	 */
	public function end_output_buffering() {
		if ( defined( 'ACSP_NONCE' ) && ob_get_length() ) {
			ob_end_flush();
		}
	}

	/**
	 * Inject nonce into inline <script> and <style> elements.
	 *
	 * @param string $buffer Full HTML page.
	 * @return string
	 */
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

	/**
	 * Return the current nonce.
	 *
	 * @return string
	 */
	public static function get_nonce() {
		return defined( 'ACSP_NONCE' ) ? ACSP_NONCE : '';
	}
}
