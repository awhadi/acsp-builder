<?php
/**
 * Helper utilities for CSP directive handling.
 *
 * @package aCSP-Builder
 */

/**
 * Return the list of CSP directives this plugin supports.
 *
 * @return string[]
 */
function acsp_allowed_directives() {
	return array(
		'default-src',
		'script-src',
		'style-src',
		'img-src',
		'font-src',
		'connect-src',
		'frame-src',
		'worker-src',
		'object-src',
		'base-uri',
		'form-action',
		'frame-ancestors',
		'style-src-attr',
		'upgrade-insecure-requests',
	);
}

/**
 * Sanitise a policy array coming from the form.
 *
 * @param mixed $input Raw value from the option/form.
 * @return string[]     Sanitised directive => value pairs.
 */
function acsp_sanitize_policy( $input ) {
	$sanitized = array();
	foreach ( acsp_allowed_directives() as $dir ) {
		if ( isset( $input[ $dir ] ) ) {
			if ( is_array( $input[ $dir ] ) ) {
				$parts             = array_map( 'sanitize_text_field', $input[ $dir ] );
				$sanitized[ $dir ] = implode( ' ', array_filter( $parts ) );
			} else {
				$sanitized[ $dir ] = sanitize_text_field( $input[ $dir ] );
			}
		}
	}
	return $sanitized;
}
