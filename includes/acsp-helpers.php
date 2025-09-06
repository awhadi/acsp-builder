<?php
/**
 * Allowed CSP directives for sanitisation.
 */
function acsp_allowed_directives() {
    return [
        'default-src','script-src','style-src','img-src','font-src',
        'connect-src','frame-src','worker-src','object-src',
        'base-uri','form-action','frame-ancestors','style-src-attr',
        'upgrade-insecure-requests'
    ];
}

/**
 * Sanitise policy array .
 */
function acsp_sanitize_policy( $input ) {
    $sanitized = [];
    foreach ( acsp_allowed_directives() as $dir ) {
        if ( isset( $input[ $dir ] ) ) {
            if ( is_array( $input[ $dir ] ) ) {
                $parts = array_map( 'sanitize_text_field', $input[ $dir ] );
                $sanitized[ $dir ] = implode( ' ', array_filter( $parts ) );
            } else {
                $sanitized[ $dir ] = sanitize_text_field( $input[ $dir ] );
            }
        }
    }
    return $sanitized;
}