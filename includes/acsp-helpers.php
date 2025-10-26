<?php

function acsp_allowed_directives() {
	static $dirs = array(
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
	return $dirs;
}

function acsp_sanitize_policy( $input ) {
	$sanitized = array();
	if ( ! is_array( $input ) ) {
		return $sanitized;
	}
	$allowed = array_flip( acsp_allowed_directives() );
	foreach ( $input as $dir => $val ) {
		if ( ! isset( $allowed[ $dir ] ) ) {
			continue;
		}
		if ( is_array( $val ) ) {
			$parts = array_filter( array_map( 'sanitize_text_field', $val ), 'strlen' );
			if ( $parts ) {
				$sanitized[ $dir ] = implode( ' ', $parts );
			}
		} else {
			$val = sanitize_text_field( $val );
			if ( $val !== '' ) {
				$sanitized[ $dir ] = $val;
			}
		}
	}
	return $sanitized;
}
