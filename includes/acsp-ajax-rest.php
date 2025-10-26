<?php
add_action( 'wp_ajax_acsp_test_report_endpoint', 'acsp_test_report_endpoint' );

function acsp_test_report_endpoint() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( 'Insufficient permissions.' );
	}

	if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'acsp_test_endpoint' ) ) {
		wp_send_json_error( 'Security check failed. Please refresh the page and try again.' );
	}

	$url = isset( $_POST['url'] ) ? esc_url_raw( wp_unslash( $_POST['url'] ) ) : '';
	if ( ! $url || ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
		wp_send_json_error( 'Invalid or empty URL provided.' );
	}

	$test_report = array(
		'csp-report' => array(
			'document-uri'        => home_url(),
			'referrer'            => '',
			'violated-directive'  => 'script-src',
			'effective-directive' => 'script-src',
			'original-policy'     => 'test-policy',
			'disposition'         => 'report',
			'blocked-uri'         => 'https://example.com/test.js',
			'line-number'         => 1,
			'column-number'       => 1,
			'source-file'         => home_url( '/test.js' ),
			'status-code'         => 200,
			'script-sample'       => '',
		),
	);

	$response = wp_remote_post(
		$url,
		array(
			'body'        => wp_json_encode( $test_report ),
			'headers'     => array(
				'Content-Type' => 'application/csp-report',
				'User-Agent'   => 'aCSP-Builder/Test',
			),
			'timeout'     => 15,
			'redirection' => 2,
			'httpversion' => '1.1',
			'sslverify'   => false,
		)
	);

	if ( is_wp_error( $response ) ) {
		$error = $response->get_error_message();
		if ( strpos( $error, 'Could not resolve host' ) !== false ) {
			wp_send_json_error( 'Could not resolve the hostname. Please check the URL.' );
		}
		if ( strpos( $error, 'Connection timed out' ) !== false ) {
			wp_send_json_error( 'Connection timed out. The endpoint may be unreachable.' );
		}
		if ( strpos( $error, 'SSL certificate' ) !== false ) {
			wp_send_json_error( 'SSL certificate error. The endpoint may have an invalid certificate.' );
		}
		wp_send_json_error( $error );
	}

	$code = wp_remote_retrieve_response_code( $response );

	if ( $code >= 200 && $code < 300 ) {
		wp_send_json_success( 'Endpoint is responding correctly (HTTP ' . $code . ').' );
	}
	if ( $code >= 400 && $code < 500 ) {
		wp_send_json_error( "Endpoint returned client error (HTTP $code). The endpoint may not be configured to accept CSP reports." );
	}
	if ( $code >= 500 ) {
		wp_send_json_error( "Endpoint returned server error (HTTP $code). The endpoint server may be experiencing issues." );
	}
	wp_send_json_error( "Unexpected response (HTTP $code)." );
}

add_action( 'admin_post_acsp_export_json', 'acsp_handle_export' );

function acsp_handle_export() {
	if ( ! isset( $_POST['acsp_export_json'] ) ) {
		return;
	}
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

add_action( 'admin_post_acsp_import_json', 'acsp_handle_import' );

function acsp_handle_import() {
	if ( ! current_user_can( 'manage_options' )
		|| empty( $_POST['acsp_import_json_nonce'] )
		|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['acsp_import_json_nonce'] ) ), 'acsp_import_json_action' )
	) {
		wp_die( 'Security check failed.' );
	}

	if ( empty( $_FILES['acsp_import_file']['tmp_name'] ) ) {
		acsp_admin_import_redirect( 'No file uploaded.' );
	}

	$file = wp_unslash( $_FILES['acsp_import_file']['tmp_name'] );
	if ( ! is_uploaded_file( $file ) ) {
		acsp_admin_import_redirect( 'Upload failed.' );
	}

	$json = file_get_contents( $file );
	$data = json_decode( $json, true );

	if ( empty( $data ) || ! is_array( $data ) ) {
		acsp_admin_import_redirect( 'Invalid JSON file.' );
	}

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
	wp_safe_redirect( add_query_arg( array( 'page' => 'acsp-builder', 'tab' => 'settings', '_wpnonce' => wp_create_nonce( 'acsp_tab_settings' ) ), admin_url( 'admin.php' ) ) );
	exit;
}

function acsp_admin_import_redirect( $message ) {
	add_settings_error( 'acsp_settings', 'acsp_import_fail', $message, 'error' );
	set_transient( 'settings_errors', get_settings_errors(), 30 );
	wp_safe_redirect( add_query_arg( array( 'page' => 'acsp-builder', 'tab' => 'settings', '_wpnonce' => wp_create_nonce( 'acsp_tab_settings' ) ), admin_url( 'admin.php' ) ) );
	exit;
}

add_action( 'admin_init', 'acsp_preset_reset_handlers' );

function acsp_preset_reset_handlers() {
	if ( isset( $_POST['acsp_reset_all'] ) ) {
		if ( empty( $_POST['acsp_reset_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['acsp_reset_nonce'] ) ), 'acsp_reset_action' ) ) {
			wp_die( 'The link you followed has expired. Please try again.' );
		}
		foreach ( array( 'acsp_policy', 'acsp_current_preset', 'acsp_add_dynamic_nonce' ) as $o ) {
			delete_option( $o );
		}
		set_transient( 'acsp_live_policy_preview', 'No CSP active (policy is empty).', 30 );
		wp_safe_redirect( add_query_arg( array( 'page' => 'acsp-builder', 'tab' => 'presets', '_wpnonce' => wp_create_nonce( 'acsp_tab_presets' ) ), admin_url( 'admin.php' ) ) );
		exit;
	}

	if ( isset( $_GET['acsp_apply_preset'], $_GET['acsp_preset_nonce'] ) ) {
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

		wp_safe_redirect( add_query_arg( array( 'page' => 'acsp-builder', 'tab' => 'presets', '_wpnonce' => wp_create_nonce( 'acsp_tab_presets' ) ), admin_url( 'admin.php' ) ) );
		exit;
	}
}
