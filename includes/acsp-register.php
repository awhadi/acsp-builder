<?php
/**
 * Settings registration, REST route, plugin-action links, activation defaults.
 */

/*
------------------------------------------------------------------
 *  Register settings
 * ----------------------------------------------------------------- */
add_action( 'admin_init', 'acsp_register_settings' );
function acsp_register_settings() {
	// Main policy array
	register_setting(
		'acsp',
		'acsp_policy',
		array(
			'type'              => 'array',
			'sanitize_callback' => 'acsp_sanitize_policy',
		)
	);

	// Settings tab group
	register_setting(
		'acsp_settings',
		'acsp_mode',
		array(
			'type'              => 'string',
			'default'           => 'reject',
			'sanitize_callback' => function ( $v ) {
				return in_array( $v, array( 'reject', 'report' ), true ) ? $v : 'reject';
			},
		)
	);
	register_setting(
		'acsp_settings',
		'acsp_add_dynamic_nonce',
		array(
			'type'              => 'boolean',
			'default'           => 1,
			'sanitize_callback' => 'absint',
		)
	);
	register_setting(
		'acsp_settings',
		'acsp_enable_meta_tag',
		array(
			'type'              => 'boolean',
			'default'           => 0,
			'sanitize_callback' => 'absint',
		)
	);
	register_setting(
		'acsp_settings',
		'acsp_enable_hashes',
		array(
			'type'              => 'boolean',
			'sanitize_callback' => 'absint',
		)
	);
	register_setting(
		'acsp_settings',
		'acsp_hash_values',
		array(
			'type'              => 'array',
			'sanitize_callback' => function ( $v ) {
				return array_filter( array_map( 'sanitize_text_field', (array) $v ) );
			},
		)
	);
	register_setting(
		'acsp_settings',
		'acsp_report_endpoint',
		array(
			'type'              => 'string',
			'sanitize_callback' => 'esc_url_raw',
		)
	);
}

/*
------------------------------------------------------------------
 *  REST endpoint for CSP reports
 * ----------------------------------------------------------------- */
add_action( 'rest_api_init', 'acsp_rest_report_endpoint' );
function acsp_rest_report_endpoint() {
	register_rest_route(
		'acsp/v1',
		'/report',
		array(
			'methods'             => 'POST',
			'callback'            => function () {
				$body = file_get_contents( 'php://input' );
				error_log( 'CSP Violation: ' . $body );   // simple – send to your own service if desired
				return new \WP_REST_Response( null, 204 );
			},
			'permission_callback' => '__return_true',
		)
	);
}

/*
------------------------------------------------------------------
 *  Activation defaults
 * ----------------------------------------------------------------- */
add_action( 'admin_init', 'acsp_maybe_set_defaults' );
function acsp_maybe_set_defaults() {
	if ( false === get_option( 'acsp_current_preset' ) ) {
		update_option( 'acsp_current_preset', 'custom' );
	}
	if ( false === get_option( 'acsp_mode' ) ) {
		update_option( 'acsp_mode', 'reject' );
	}
	if ( false === get_option( 'acsp_enable_hashes' ) ) {
		update_option( 'acsp_enable_hashes', 0 );
	}
}

/*
------------------------------------------------------------------
 *  1. Fly-out sub-menus (hover shows Quick-Start / Builder / etc.)
 * ----------------------------------------------------------------- */
add_action( 'admin_menu', 'acsp_submenus', 20 );   // after top-level is built
function acsp_submenus() {
	$tabs = array(
		'presets'  => 'Quick Start',
		'builder'  => 'Custom Policy Builder',
		'settings' => 'Settings',
		'about'    => 'About',
	);
	foreach ( $tabs as $slug => $title ) {
		add_submenu_page(
			'acsp-builder',
			$title,
			$title,
			'manage_options',
			'acsp-builder&tab=' . $slug,
			'acsp_router'
		);
	}
	// Remove the auto-generated duplicate top entry
	remove_submenu_page( 'acsp-builder', 'acsp-builder' );
}

/*
------------------------------------------------------------------
 *  2. Plugins list:  Config | Settings | Deactivate | Edit
 * ----------------------------------------------------------------- */
add_filter( 'plugin_action_links_' . plugin_basename( ACSP_FILE ), 'acsp_action_links' );
function acsp_action_links( $links ) {
	// WP 6.x:  $links = [ 'Deactivate', 'Edit' ]  (index 0 = Deactivate)
	$config   = '<a href="' . esc_url( admin_url( 'admin.php?page=acsp-builder' ) ) . '">Config</a>';
	$settings = '<a href="' . esc_url( admin_url( 'admin.php?page=acsp-builder&tab=settings' ) ) . '">Settings</a>';

	array_unshift( $links, $config, $settings ); // push both to the front
	return $links;
}
