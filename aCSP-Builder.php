<?php
/**
 * Plugin Name: aCSP Builder
 * Plugin URI:  https://plugins.awhadi.online
 * Description: The FIRST WordPress plugin that automatically adds cryptographic nonces to every script & stylesheet, lets you hash-lock inline code, and builds a bullet-proof Content Security Policy in one click.
 * Version:     1.0.3
 * Requires WP: 5.8
 * Requires PHP:7.4
 * Text Domain: aCSP
 * Author:      aStudio, Amir Khosro Awhadi
 * License:     GPL-2.0+
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ACSP_FILE', __FILE__ );
define( 'ACSP_DIR', plugin_dir_path( __FILE__ ) );
define( 'ACSP_URL', plugin_dir_url( __FILE__ ) );
define( 'ACSP_VER', '1.0.3' );

/*
------------------------------------------------------------------
 *  Bootstrap
 * ----------------------------------------------------------------- */
require_once ACSP_DIR . 'includes/acsp-helpers.php';
require_once ACSP_DIR . 'includes/acsp-preset-data.php';
require_once ACSP_DIR . 'includes/class-acsp-core.php';
require_once ACSP_DIR . 'includes/acsp-register.php';
require_once ACSP_DIR . 'includes/acsp-ajax-rest.php';
require_once ACSP_DIR . 'includes/acsp-force-custom.php';

add_action( 'plugins_loaded', 'acsp_boot' );
function acsp_boot() {
	\aCSP\Core::init();
}

/*
------------------------------------------------------------------
 *  Admin menu & tabs
 * ----------------------------------------------------------------- */
add_action( 'admin_menu', 'acsp_admin_menu' );
function acsp_admin_menu() {
	add_menu_page(
		'aCSP Builder',
		'aCSP Builder',
		'manage_options',
		'acsp-builder',
		'acsp_router',
		'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAiIGhlaWdodD0iMjAiIHZpZXdCb3g9IjAgMCAyMCAyMCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHBhdGggZD0iTTEwIDE4QzE0LjQxODMgMTggMTggMTQuNDE4MyAxOCAxMEMxOCA1LjU4MTcyIDE0LjQxODMgMiAxMCAyQzUuNTgxNzIgMiAyIDUuNTgxNzIgMiAxMEMyIDE0LjQxODMgNS41ODE3MiAxOCAxMCAxOFoiIHN0cm9rZT0iIzAwN2NiYSIgc3Ryb2tlLXdpZHRoPSIyIiBzdHJva2UtbGluZWNhcD0icm91bmQiIHN0cm9rZS1saW5lam9pbj0icm91bmQiLz4KPHBhdGggZD0iTTggMTJMMTEgMTVMMTYgNyIgc3Ryb2tlPSIjMDA3Y2JhIiBzdHJva2Utd2lkdGg9IjIiIHN0cm9rZS1saW5lY2FwPSJyb3VuZCIgc3Ryb2tlLWxpbmVqb2luPSJyb3VuZCIvPgo8L3N2Zz4K',
		80
	);
}

/**
 * Route to the correct tab view.
 */
function acsp_router() {
	$tabs = array( 'presets', 'builder', 'settings', 'about' );
	$tab  = isset( $_GET['tab'] ) && in_array( $_GET['tab'], $tabs, true ) ? $_GET['tab'] : 'presets';

	$map = array(
		'presets'  => ACSP_DIR . 'admin/acsp-presets.php',
		'builder'  => ACSP_DIR . 'admin/acsp-builder.php',
		'settings' => ACSP_DIR . 'admin/acsp-settings.php',
		'about'    => ACSP_DIR . 'admin/acsp-about.php',
	);

	if ( is_readable( $map[ $tab ] ) ) {
		require_once $map[ $tab ];
	}
}

/*
------------------------------------------------------------------
 *  CSS + JS  (only on our pages)
 * ----------------------------------------------------------------- */
add_action( 'admin_enqueue_scripts', 'acsp_assets' );
function acsp_assets( $hook ) {
	if ( 'toplevel_page_acsp-builder' !== $hook ) {
		return;
	}
	wp_enqueue_style( 'acsp-admin', ACSP_URL . 'assets/acsp.css', array(), ACSP_VER );
	wp_enqueue_script( 'acsp-admin', ACSP_URL . 'assets/acsp.js', array( 'jquery' ), ACSP_VER, true );
}

/*
------------------------------------------------------------------
 *  Activation / uninstall
 * ----------------------------------------------------------------- */
register_activation_hook( ACSP_FILE, 'acsp_activate' );
function acsp_activate() {
	add_option( 'acsp_current_preset', 'custom' );
	add_option( 'acsp_mode', 'reject' );
	add_option( 'acsp_enable_hashes', 0 );
}

register_uninstall_hook( ACSP_FILE, 'acsp_uninstall' );
function acsp_uninstall() {
	$opts = array(
		'acsp_policy',
		'acsp_current_preset',
		'acsp_add_dynamic_nonce',
		'acsp_mode',
		'acsp_report_endpoint',
		'acsp_enable_hashes',
		'acsp_hash_values',
		'acsp_enable_meta_tag',
	);
	foreach ( $opts as $o ) {
		delete_option( $o );
	}
	delete_transient( 'acsp_live_policy_preview' );
}
