<?php
add_action( 'admin_init', 'acsp_register_settings' );
function acsp_register_settings() {
	$settings = array(
		array(
			'group' => 'acsp',
			'name'  => 'acsp_policy',
			'args'  => array(
				'type'              => 'array',
				'sanitize_callback' => 'acsp_sanitize_policy',
			),
		),
		array(
			'group' => 'acsp_settings',
			'name'  => 'acsp_mode',
			'args'  => array(
				'type'              => 'string',
				'default'           => 'reject',
				'sanitize_callback' => 'acsp_sanitize_mode',
			),
		),
		array(
			'group' => 'acsp_settings',
			'name'  => 'acsp_add_dynamic_nonce',
			'args'  => array(
				'type'              => 'boolean',
				'default'           => 1,
				'sanitize_callback' => 'absint',
			),
		),
		array(
			'group' => 'acsp_settings',
			'name'  => 'acsp_enable_meta_tag',
			'args'  => array(
				'type'              => 'boolean',
				'default'           => 0,
				'sanitize_callback' => 'absint',
			),
		),
		array(
			'group' => 'acsp_settings',
			'name'  => 'acsp_enable_hashes',
			'args'  => array(
				'type'              => 'boolean',
				'sanitize_callback' => 'absint',
			),
		),
		array(
			'group' => 'acsp_settings',
			'name'  => 'acsp_hash_values',
			'args'  => array(
				'type'              => 'array',
				'sanitize_callback' => 'acsp_sanitize_hash_values',
			),
		),
		array(
			'group' => 'acsp_settings',
			'name'  => 'acsp_report_endpoint',
			'args'  => array(
				'type'              => 'string',
				'sanitize_callback' => 'esc_url_raw',
			),
		),
	);

	foreach ( $settings as $s ) {
		register_setting( $s['group'], $s['name'], $s['args'] );
	}
}

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

add_action( 'admin_menu', 'acsp_submenus', 20 );
function acsp_submenus() {
	$tabs = array(
		'presets'  => 'Quick Start',
		'builder'  => 'Custom Policy Builder',
		'settings' => 'Settings',
		'about'    => 'About',
	);

	foreach ( $tabs as $slug => $title ) {
		$nonce = wp_create_nonce( 'acsp_tab_' . $slug );
		add_submenu_page(
			'acsp-builder',
			$title,
			$title,
			'manage_options',
			'acsp-builder&tab=' . $slug . '&_wpnonce=' . $nonce,
			'acsp_router'
		);
	}

	remove_submenu_page( 'acsp-builder', 'acsp-builder' );
}

add_filter( 'plugin_action_links_' . plugin_basename( ACSP_FILE ), 'acsp_action_links' );
function acsp_action_links( $links ) {
	$presets_url = esc_url( add_query_arg( array( 'page' => 'acsp-builder', 'tab' => 'presets', '_wpnonce' => wp_create_nonce( 'acsp_tab_presets' ) ), admin_url( 'admin.php' ) ) );
	$settings_url = esc_url( add_query_arg( array( 'page' => 'acsp-builder', 'tab' => 'settings', '_wpnonce' => wp_create_nonce( 'acsp_tab_settings' ) ), admin_url( 'admin.php' ) ) );

	array_unshift( $links, sprintf( '<a href="%s">Config</a>', $presets_url ), sprintf( '<a href="%s">Settings</a>', $settings_url ) );

	return $links;
}

function acsp_sanitize_mode( $v ) {
	return in_array( $v, array( 'reject', 'report' ), true ) ? $v : 'reject';
}

function acsp_sanitize_hash_values( $v ) {
	return array_filter( array_map( 'sanitize_text_field', (array) $v ) );
}
