<?php
/**
 * Ajax handlers + JSON import/export + preset apply/reset.
 *
 * @package aCSP-Builder
 */

// ------------------------------------------------------------------
// Ajax end-point used by the settings page to test a report URI
// ------------------------------------------------------------------
add_action( 'wp_ajax_acsp_test_report_uri', 'acsp_ajax_test_report' );
/**
 * Send a dummy CSP report to the given URL and return HTTP status.
 *
 * @since 1.0.3
 */
function acsp_ajax_test_report() {
	// Basic capability check + nonce check.
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( 'insufficient_permissions' );
	}
	check_ajax_referer( 'acsp_test_report_uri', 'nonce' );

	$url = isset( $_POST['url'] ) ? esc_url_raw( wp_unslash( $_POST['url'] ) ) : '';
	if ( ! $url ) {
		wp_send_json_error( 'empty_url' );
	}

	$response = wp_remote_post(
		$url,
		array(
			'method'  => 'POST',
			'body'    => wp_json_encode( array( 'test' => 'ping' ) ),
			'headers' => array( 'Content-Type' => 'application/csp-report' ),
			'timeout' => 10,
		)
	);

	if ( is_wp_error( $response ) ) {
		wp_send_json_error( $response->get_error_message() );
	}

	$code = wp_remote_retrieve_response_code( $response );
	( $code >= 200 && $code < 300 ) ? wp_send_json_success( 'OK' ) : wp_send_json_error( "HTTP $code" );
}

// ------------------------------------------------------------------
// JSON EXPORT (triggered in settings tab)
// ------------------------------------------------------------------
add_action( 'admin_init', 'acsp_handle_export' );
/**
 * Stream a JSON file that contains current policy & settings.
 *
 * @since 1.0.3
 */
function acsp_handle_export() {
	if ( ! isset( $_POST['acsp_export_json'] ) ) {
		return;
	}

	// Capability + nonce.
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'insufficient_permissions' );
	}
	check_admin_referer( 'acsp_export_json_action', 'acsp_export_json_nonce' );

	$export = array(
		'mode'              => get_option( 'acsp_mode', 'reject' ),
		'add_dynamic_nonce' => (bool) get_option( 'acsp_add_dynamic_nonce', 1 ),
		'policy'            => get_option( 'acsp_policy', array() ),
		'enable_meta_tag'   => (bool) get_option( 'acsp_enable_meta_tag', 0 ),
		'report_endpoint'   => get_option( 'acsp_report_endpoint', '' ),
		'enable_hashes'     => (bool) get_option( 'acsp_enable_hashes', 0 ),
	);

	if ( $export['enable_hashes'] ) {
		$export['hash_values'] = get_option( 'acsp_hash_values', array() );
	}

	header( 'Content-Type: application/json' );
	header( 'Content-Disposition: attachment; filename=aCSP-Builder-preset-' . gmdate( 'Y-m-d' ) . '.json' );
	echo wp_json_encode( $export, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
	exit;
}

// ------------------------------------------------------------------
// JSON IMPORT (admin-post.php handler)
// ------------------------------------------------------------------
add_action( 'admin_post_acsp_import_json', 'acsp_handle_import' );
/**
 * Handle uploaded JSON file and import settings.
 *
 * @since 1.0.3
 */
function acsp_handle_import() {
	// Capability + nonce.
	if ( ! current_user_can( 'manage_options' )
		|| ! isset( $_POST['acsp_import_json_nonce'] )
		|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['acsp_import_json_nonce'] ) ), 'acsp_import_json_action' )
	) {
		wp_die( 'Security check failed.' );
	}

	// Make sure the upload field actually arrived.
	if ( empty( $_FILES['acsp_import_file'] ) || ! isset( $_FILES['acsp_import_file']['tmp_name'] ) ) {
		add_settings_error( 'acsp_settings', 'acsp_import_fail', 'No file uploaded.', 'error' );
		set_transient( 'settings_errors', get_settings_errors(), 30 );
		wp_safe_redirect( add_query_arg( 'tab', 'settings', admin_url( 'admin.php?page=acsp-builder' ) ) );
		exit;
	}

	$file = sanitize_text_field( wp_unslash( $_FILES['acsp_import_file']['tmp_name'] ) );
	if ( ! is_uploaded_file( $file ) ) {
		add_settings_error( 'acsp_settings', 'acsp_import_fail', 'Upload failed.', 'error' );
		set_transient( 'settings_errors', get_settings_errors(), 30 );
		wp_safe_redirect( add_query_arg( 'tab', 'settings', admin_url( 'admin.php?page=acsp-builder' ) ) );
		exit;
	}

    // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local file
	$json = file_get_contents( $file );
	$data = json_decode( $json, true );

	if ( empty( $data ) || ! is_array( $data ) ) {
		add_settings_error( 'acsp_settings', 'acsp_import_fail', 'Invalid JSON file.', 'error' );
		set_transient( 'settings_errors', get_settings_errors(), 30 );
		wp_safe_redirect( add_query_arg( 'tab', 'settings', admin_url( 'admin.php?page=acsp-builder' ) ) );
		exit;
	}

	// Import into DB.
	update_option( 'acsp_mode', $data['mode'] ?? 'reject' );
	update_option( 'acsp_add_dynamic_nonce', $data['add_dynamic_nonce'] ?? 1 );
	update_option( 'acsp_policy', $data['policy'] ?? array() );
	update_option( 'acsp_enable_meta_tag', $data['enable_meta_tag'] ?? 0 );
	update_option( 'acsp_enable_hashes', $data['enable_hashes'] ?? 0 );
	update_option( 'acsp_hash_values', $data['hash_values'] ?? array() );
	update_option( 'acsp_report_endpoint', $data['report_endpoint'] ?? '' );
	update_option( 'acsp_current_preset', 'custom' );

	add_settings_error( 'acsp_settings', 'acsp_import_ok', 'JSON preset imported successfully.', 'updated' );
	set_transient( 'settings_errors', get_settings_errors(), 30 );
	wp_safe_redirect( add_query_arg( 'tab', 'settings', admin_url( 'admin.php?page=acsp-builder' ) ) );
	exit;
}

// ------------------------------------------------------------------
// PRESET APPLY + RESET (safe POST handlers)
// ------------------------------------------------------------------
add_action( 'admin_init', 'acsp_preset_reset_handlers' );
/**
 * Handles two distinct actions:
 * 1. "Reset all policies" button
 * 2. "Apply preset" link (GET with nonce)
 *
 * @since 1.0.3
 */
function acsp_preset_reset_handlers() {
	// ---------- RESET ----------
	if ( isset( $_POST['acsp_reset_all'] ) ) {
		if ( ! isset( $_POST['acsp_reset_nonce'] )
			|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['acsp_reset_nonce'] ) ), 'acsp_reset_action' )
		) {
			wp_die( 'The link you followed has expired. Please try again.' );
		}

		$opts = array(
			'acsp_policy',
			'acsp_current_preset',
			'acsp_add_dynamic_nonce',
		);
		foreach ( $opts as $o ) {
			delete_option( $o );
		}
		set_transient( 'acsp_live_policy_preview', 'No CSP active (policy is empty).', 30 );
		wp_safe_redirect( add_query_arg( 'tab', 'presets', admin_url( 'admin.php?page=acsp-builder' ) ) );
		exit;
	}

	// ---------- APPLY PRESET ----------
	if ( isset( $_GET['acsp_apply_preset'] ) && isset( $_GET['acsp_preset_nonce'] ) ) {
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['acsp_preset_nonce'] ) ), 'acsp_preset_action' ) ) {
			wp_die( 'Security check failed.' );
		}

		$preset  = sanitize_text_field( wp_unslash( $_GET['acsp_apply_preset'] ) );
		$presets = acsp_get_presets();

		if ( isset( $presets[ $preset ] ) ) {
			update_option( 'acsp_policy', $presets[ $preset ]['policy'] );
			update_option( 'acsp_add_dynamic_nonce', $presets[ $preset ]['nonce_enabled'] );
			update_option( 'acsp_current_preset', $preset );
			update_option( 'acsp_enable_meta_tag', $presets[ $preset ]['enable_meta_tag'] );

			add_settings_error( 'acsp', 'preset_applied', sprintf( 'Preset "%s" applied.', $presets[ $preset ]['name'] ), 'updated' );
			set_transient( 'settings_errors', get_settings_errors(), 30 );
		}

		wp_safe_redirect( add_query_arg( 'tab', 'presets', admin_url( 'admin.php?page=acsp-builder' ) ) );
		exit;
	}
}
