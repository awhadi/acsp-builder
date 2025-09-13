<?php
/**
 * Settings registration, REST route, plugin-action links, activation defaults.
 *
 * @package acsp-builder
 */

// ------------------------------------------------------------------
// Register settings.
// ------------------------------------------------------------------
add_action( 'admin_init', 'acsp_register_settings' );

/**
 * Register every option the plugin stores.
 */
function acsp_register_settings() {
	// Main policy array.
	register_setting(
		'acsp',
		'acsp_policy',
		array(
			'type'              => 'array',
			'sanitize_callback' => 'acsp_sanitize_policy',
		)
	);

	// Settings tab group.
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

// ------------------------------------------------------------------
// Activation defaults.
// ------------------------------------------------------------------
add_action( 'admin_init', 'acsp_maybe_set_defaults' );

/**
 * Seed default values on first run.
 */
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

// ------------------------------------------------------------------
// Fly-out sub-menus (hover shows Quick-Start / Builder / etc.).
// ------------------------------------------------------------------
add_action( 'admin_menu', 'acsp_submenus', 20 ); // After top-level is built.

/**
 * Add individual submenu items for each tab.
 *
 * The links must keep the query-string so the nonce survives.
 * add_submenu_page() accepts a FILE-NAME as the 5th parameter,
 * but we can also pass a CALLABLE that already contains the
 * query args we need – WP will still fire it.
 */
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
			'acsp-builder&tab=' . $slug . '&_wpnonce=' . wp_create_nonce( 'acsp_tab_' . $slug ),
			'acsp_router'
		);
	}

	// Remove the duplicate auto-generated top entry.
	remove_submenu_page( 'acsp-builder', 'acsp-builder' );
}

// ------------------------------------------------------------------
// Plugins list: Config | Settings | Deactivate | Edit.
// ------------------------------------------------------------------
add_filter( 'plugin_action_links_' . plugin_basename( ACSP_FILE ), 'acsp_action_links' );

/**
 * Add quick links on Plugins screen.
 *
 * @param string[] $links Original action links.
 * @return string[]
 */
function acsp_action_links( $links ) {
	// 1. Config  -> Presets tab.
	$config = sprintf(
		'<a href="%s">Config</a>',
		esc_url(
			add_query_arg(
				array(
					'page'     => 'acsp-builder',
					'tab'      => 'presets',
					'_wpnonce' => wp_create_nonce( 'acsp_tab_presets' ),
				),
				admin_url( 'admin.php' )
			)
		)
	);

	// 2. Settings -> Settings tab.
	$settings = sprintf(
		'<a href="%s">Settings</a>',
		esc_url(
			add_query_arg(
				array(
					'page'     => 'acsp-builder',
					'tab'      => 'settings',
					'_wpnonce' => wp_create_nonce( 'acsp_tab_settings' ),
				),
				admin_url( 'admin.php' )
			)
		)
	);

	// Push them to the front of the links array.
	array_unshift( $links, $config, $settings );

	return $links;
}
