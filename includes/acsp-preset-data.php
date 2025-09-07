<?php
/**
 * Presets identical to original.
 *
 * @package aCSP-Builder
 */
function acsp_get_presets() {
	return array(
		'relaxed'  => array(
			'name'            => 'Relaxed (Beginner)',
			'nonce_enabled'   => 0,
			'enable_meta_tag' => 0,
			'policy'          => array(
				'default-src'               => "'self'",
				'script-src'                => "'self' 'unsafe-inline' 'unsafe-eval' https://www.googletagmanager.com https://www.google-analytics.com https://apis.google.com https://cdnjs.cloudflare.com https://ajax.cloudflare.com https://code.jquery.com",
				'style-src'                 => "'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com https://cdn.cloudflare.com",
				'img-src'                   => "'self' data: blob: https: *.google-analytics.com *.youtube.com *.google.com *.cloudflare.com *.gravatar.com",
				'font-src'                  => "'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com https://cdn.cloudflare.com",
				'connect-src'               => "'self' https://www.google-analytics.com https://region1.google-analytics.com",
				'frame-src'                 => "'self' https://www.youtube.com https://www.google.com",
				'worker-src'                => "'self' blob:",
				'object-src'                => "'none'",
				'base-uri'                  => "'self'",
				'form-action'               => "'self'",
				'style-src-attr'            => "'self' 'unsafe-inline'",
				'frame-ancestors'           => "'self'",
				'upgrade-insecure-requests' => '',
			),
		),
		'balanced' => array(
			'name'            => 'Balanced (Intermediate)',
			'nonce_enabled'   => 1,
			'enable_meta_tag' => 0,
			'policy'          => array(
				'default-src'               => "'self'",
				'script-src'                => "'self' 'unsafe-hashes' https://www.googletagmanager.com https://www.google-analytics.com https://apis.google.com https://cdnjs.cloudflare.com https://ajax.cloudflare.com",
				'style-src'                 => "'self' https://fonts.googleapis.com https://cdnjs.cloudflare.com https://cdn.cloudflare.com",
				'img-src'                   => "'self' data: blob: https: *.google-analytics.com *.youtube.com *.google.com *.cloudflare.com *.gravatar.com",
				'font-src'                  => "'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com https://cdn.cloudflare.com",
				'connect-src'               => "'self' https://www.google-analytics.com https://region1.google-analytics.com",
				'frame-src'                 => "'self' https://www.youtube.com https://www.google.com",
				'worker-src'                => "'self' blob:",
				'object-src'                => "'none'",
				'base-uri'                  => "'self'",
				'form-action'               => "'self'",
				'style-src-attr'            => "'none'",
				'frame-ancestors'           => "'self'",
				'upgrade-insecure-requests' => '',
			),
		),
		'strict'   => array(
			'name'            => 'Strict (Advanced)',
			'nonce_enabled'   => 1,
			'enable_meta_tag' => 0,
			'policy'          => array(
				'default-src'               => "'none'",
				'script-src'                => "'self' 'strict-dynamic'",
				'style-src'                 => "'self'",
				'img-src'                   => "'self' data:",
				'font-src'                  => "'self'",
				'connect-src'               => "'self'",
				'frame-src'                 => "'self'",
				'worker-src'                => "'self' blob:",
				'object-src'                => "'none'",
				'base-uri'                  => "'self'",
				'form-action'               => "'self'",
				'style-src-attr'            => "'none'",
				'frame-ancestors'           => "'none'",
				'upgrade-insecure-requests' => '',
			),
		),
	);
}
