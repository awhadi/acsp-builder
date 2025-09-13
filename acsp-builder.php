<?php
/**
 * Plugin Name: aCSP Builder
 * Plugin URI:  https://plugins.awhadi.online
 * Description: The FIRST WordPress plugin that automatically adds cryptographic nonces to every script & stylesheet, lets you hash-lock inline code, and builds a bullet-proof Content Security Policy in one click.
 * Version:     1.0.11
 * Requires WP: 5.8
 * Requires PHP:7.4
 * Text Domain: acsp-builder
 * Domain Path: /languages
 * Author:      Amir Khosro Awhadi
 * License:     GPL-2.0+
 *
 * @package acsp-builder
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ACSP_FILE', __FILE__ );
define( 'ACSP_DIR', plugin_dir_path( __FILE__ ) );
define( 'ACSP_URL', plugin_dir_url( __FILE__ ) );
define( 'ACSP_VER', '1.0.11' );

/*
------------------------------------------------------------------
 * Autoloader – loads files ONLY when needed, no re-declare errors.
 * ------------------------------------------------------------------
 */
add_action( 'plugins_loaded', 'acsp_autoload_register', 0 );

/**
 * Register autoloader for plugin classes/functions.
 *
 * @return void
 */
function acsp_autoload_register() {
	$map = array(
		// Class => file basename (without .php).
		'aCSP\CSP_Engine'             => 'class-csp-engine',
		// Functions => file that contains them.
		'acsp_sanitize_policy'        => 'acsp-helpers',
		'acsp_allowed_directives'     => 'acsp-helpers',
		'acsp_get_presets'            => 'acsp-preset-data',
		'acsp_handle_export'          => 'acsp-ajax-rest',
		'acsp_handle_import'          => 'acsp-ajax-rest',
		'acsp_ajax_test_report'       => 'acsp-ajax-rest',
		'acsp_preset_reset_handlers'  => 'acsp-ajax-rest',
		'acsp_maybe_switch_to_custom' => 'acsp-force-custom',
		'acsp_register_settings'      => 'acsp-register',
		'acsp_maybe_set_defaults'     => 'acsp-register',
		'acsp_submenus'               => 'acsp-register',
		'acsp_action_links'           => 'acsp-register',
	);

	spl_autoload_register(
		function ( $class_name ) use ( $map ) {
			if ( isset( $map[ $class_name ] ) ) {
				$file = ACSP_DIR . 'includes/' . $map[ $class_name ] . '.php';
				if ( is_readable( $file ) ) {
					require_once $file;
				}
				return;
			}

			foreach ( $map as $func => $basename ) {
				if ( function_exists( $func ) ) {
					continue;
				}
				$file = ACSP_DIR . 'includes/' . $basename . '.php';
				if ( is_readable( $file ) ) {
					require_once $file;
				}
			}
		}
	);
}

/*
------------------------------------------------------------------
 * Bootstrap the engine.
 * ------------------------------------------------------------------
 */
add_action( 'plugins_loaded', 'acsp_boot' );

/**
 * Initialise CSP engine.
 *
 * @return void
 */
function acsp_boot() {
	\aCSP\CSP_Engine::init();
}

/*
 * ------------------------------------------------------------------
 * Admin menu & tabs
 * ------------------------------------------------------------------
 */
add_action( 'admin_menu', 'acsp_admin_menu' );

/**
 * Register the top-level menu.
 *
 * @return void
 */
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
 *
 * @return void
 */
function acsp_router() {
	$tabs = array( 'presets', 'builder', 'settings', 'about' );

	// Verify the tab nonce first.
	if ( empty( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'acsp_tab_' . ( isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'presets' ) ) ) {
		wp_die( 'Security check failed.' );
	}

	$tab = isset( $_GET['tab'] ) && in_array( sanitize_key( wp_unslash( $_GET['tab'] ) ), $tabs, true ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'presets';

	$map = array(
		'presets'  => ACSP_DIR . 'admin/acsp-presets.php',
		'builder'  => ACSP_DIR . 'admin/acsp-custom-builder.php',
		'settings' => ACSP_DIR . 'admin/acsp-settings.php',
		'about'    => ACSP_DIR . 'admin/acsp-about.php',
	);

	if ( is_readable( $map[ $tab ] ) ) {
		require_once $map[ $tab ];
	}
}

/*
 * ------------------------------------------------------------------
 * CSS + JS  (only on our pages)
 * ------------------------------------------------------------------
 */
add_action( 'admin_enqueue_scripts', 'acsp_assets' );

/**
 * Enqueue admin assets.
 *
 * @param string $hook_suffix Current admin page.
 * @return void
 */
function acsp_assets( $hook_suffix ) {
	if ( 'toplevel_page_acsp-builder' !== $hook_suffix ) {
		return;
	}
	wp_enqueue_style( 'acsp-admin', ACSP_URL . 'assets/acsp.css', array(), ACSP_VER );
	wp_enqueue_script( 'acsp-admin', ACSP_URL . 'assets/acsp.js', array( 'jquery' ), ACSP_VER, true );

	// Localize AJAX URL and nonce.
	wp_localize_script(
		'acsp-admin',
		'acsp_ajax',
		array(
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'acsp_test_endpoint' ),
		)
	);
}

/*
 * ------------------------------------------------------------------
 * Activation / uninstall
 * ------------------------------------------------------------------
 */
register_activation_hook( ACSP_FILE, 'acsp_activate' );

/**
 * Seed default options on activation.
 *
 * @return void
 */
function acsp_activate() {
	add_option( 'acsp_current_preset', 'custom' );
	add_option( 'acsp_mode', 'reject' );
	add_option( 'acsp_enable_hashes', 0 );
}

register_uninstall_hook( ACSP_FILE, 'acsp_uninstall' );

/**
 * Remove all traces on uninstall.
 *
 * @return void
 */
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
