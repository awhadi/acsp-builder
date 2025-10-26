<?php
if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( 'Unauthorized user' );
}

$nonce_action = 'acsp_tab_settings';
if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), $nonce_action ) ) {
	wp_die( 'Security check failed.' );
}

$acsp_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'presets';

wp_enqueue_style( 'acsp-admin' );
wp_enqueue_script( 'acsp-admin' );

settings_errors( 'acsp_settings' );
?>

<div class="wrap">
	<h1>a Content-Security-Policy (CSP) Builder</h1>

	<h2 class="nav-tab-wrapper">
		<?php
		$nav_tabs = array(
			'presets'  => 'Quick Start',
			'builder'  => 'Custom Policy Builder',
			'settings' => 'Settings',
			'about'    => 'About',
		);
		foreach ( $nav_tabs as $nav_tab => $label ) :
			printf(
				'<a href="%s" class="nav-tab %s">%s</a>',
				esc_url(
					add_query_arg(
						array(
							'page'     => 'acsp-builder',
							'tab'      => $nav_tab,
							'_wpnonce' => wp_create_nonce( 'acsp_tab_' . $nav_tab ),
						),
						admin_url( 'admin.php' )
					)
				),
				( 'settings' === $nav_tab ) ? 'nav-tab-active' : '',
				esc_html( $label )
			);
		endforeach;
		
		$current_preset = get_option( 'acsp_current_preset' );
		$preset_class = $current_preset ?: 'custom';
		$preset_name = $current_preset && isset( acsp_get_presets()[ $current_preset ] ) ? acsp_get_presets()[ $current_preset ]['name'] : 'Custom';
		?>
		<span class="acsp-preset-badge <?php echo esc_attr( $preset_class ); ?>"><?php echo esc_html( $preset_name ); ?></span>
	</h2>

	<div style="display:flex;gap:24px;align-items:flex-start;">
		<div style="flex:1;">
			<form method="post" action="options.php">
				<?php settings_fields( 'acsp_settings' ); ?>

				<div class="acsp-card">
					<h3>Mode</h3>
					<p>Choose how the policy is delivered.</p>
					<table class="form-table">
						<tr>
							<th scope="row">CSP Mode</th>
							<td>
								<label style="display:block;margin-bottom:6px;">
									<input type="radio" name="acsp_mode" value="reject" <?php checked( get_option( 'acsp_mode', 'reject' ), 'reject' ); ?> />
									Reject & Report (enforce)
								</label>
								<label>
									<input type="radio" name="acsp_mode" value="report" <?php checked( get_option( 'acsp_mode', 'reject' ), 'report' ); ?> />
									Report-Only
								</label>
							</td>
						</tr>
					</table>
				</div>

				<div class="acsp-card">
					<h3>Meta-tag injection</h3>
					<p>When enabled the plugin will also insert<br>
						<code>&lt;meta http-equiv="Content-Security-Policy" ...&gt;</code><br>
						into the front-end <code>&lt;head&gt;</code>. (Default: off) remember meta data may override the header. In most case let this option is not required at all and let it be disabled.</p>
					<table class="form-table">
						<tr>
							<th scope="row">Insert CSP meta tag</th>
							<td>
								<label>
									<input type="checkbox" name="acsp_enable_meta_tag" value="1" <?php checked( get_option( 'acsp_enable_meta_tag', 0 ) ); ?> />
									Enabled
								</label>
							</td>
						</tr>
					</table>
				</div>

				<div class="acsp-card">
					<h3>Nonce Injection</h3>
					<p>When enabled, a fresh <code>nonce-*</code> value is automatically appended to <code>script-src</code> and <code>style-src</code> directives, and injected into matching tags.</p>
					<table class="form-table">
						<tr>
							<th scope="row">Add dynamic nonce</th>
							<td>
								<label>
									<input type="checkbox" name="acsp_add_dynamic_nonce" value="1" <?php checked( get_option( 'acsp_add_dynamic_nonce', 1 ) ); ?> />
									Enabled
								</label>
							</td>
						</tr>
					</table>
				</div>

				<div class="acsp-card">
					<h3>Hash Allow-List</h3>
					<table class="form-table">
						<tr>
							<th scope="row">Enable&nbsp;hashes</th>
							<td>
								<label>
									<input type="checkbox" name="acsp_enable_hashes" id="acsp_enable_hashes" value="1" <?php checked( get_option( 'acsp_enable_hashes', 0 ) ); ?> />
									Enabled
								</label>
							</td>
						</tr>

						<tr class="acsp-hash-row" style="<?php echo get_option( 'acsp_enable_hashes', 0 ) ? '' : 'display:none;'; ?>">
							<th scope="row">Inline hashes</th>
							<td>
								<div id="acsp-hash-list" class="acsp-hash-list">
									<?php
									$hashes = get_option( 'acsp_hash_values', array( '' ) );
									foreach ( $hashes as $hash ) :
										?>
										<div class="acsp-hash-item">
											<input type="text" name="acsp_hash_values[]" value="<?php echo esc_attr( $hash ); ?>" placeholder="sha256-…" class="regular-text code"/>
											<button type="button" class="button button-small acsp-remove-hash">Remove</button>
										</div>
									<?php endforeach; ?>
								</div>
								<button type="button" class="button button-secondary" id="acsp-add-hash">Add hash</button>
								<p class="description">
									Enter one hash per box, e.g. <code>sha256-AbCdEf123…</code><br>
									These will be appended to <strong>script-src</strong> and <strong>style-src</strong> only when
									<code>'unsafe-hashes'</code> is also present.
								</p>
							</td>
						</tr>
					</table>
				</div>

				<div class="acsp-card">
					<h3>Report</h3>
					<p>Enter the endpoint where violation reports should be sent (optional).</p>
					<table class="form-table">
						<tr>
							<th scope="row">Report endpoint</th>
							<td>
								<?php
								$report_endpoint = get_option( 'acsp_report_endpoint', '' );
								if ( is_array( $report_endpoint ) && isset( $report_endpoint['rest'] ) ) {
									$report_endpoint = $report_endpoint['rest'];
								}
								?>
								<input type="url" name="acsp_report_endpoint" id="acsp_report_endpoint" value="<?php echo esc_attr( $report_endpoint ); ?>" class="regular-text code" placeholder="https://example.com/wp-json/acsp/v1/report">
								<button type="button" id="acsp_test_endpoint" class="button button-secondary" style="margin-left: 10px;">Test Endpoint</button>
								<span id="acsp_test_result" style="margin-left: 10px; display: none;"></span>
								<p class="description">URL that receives both <code>report-to</code> and legacy <code>report-uri</code> reports.</p>
							</td>
						</tr>
					</table>
				</div>

				<div style="display:flex;justify-content:flex-end;margin-top:20px;">
					<?php submit_button( 'Save Changes', 'primary', 'submit', false ); ?>
				</div>
			</form>
		</div>

		<div style="width:320px;">
			<div class="acsp-card">
				<h3>Export / Import Preset</h3>

				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="acsp_export_json">
					<?php wp_nonce_field( 'acsp_export_json_action', 'acsp_export_json_nonce' ); ?>
					<p><strong>Export</strong></p>
					<p style="font-size:13px;margin-bottom:12px;">Download a JSON file with the current policy & all settings.</p>
					<?php submit_button( 'Download JSON', 'primary', 'acsp_export_json', false ); ?>
				</form>

				<hr style="margin:20px 0;">

				<form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="acsp_import_json">
					<?php wp_nonce_field( 'acsp_import_json_action', 'acsp_import_json_nonce' ); ?>
					<p><strong>Import</strong></p>
					<p style="font-size:13px;margin-bottom:12px;">Upload a previously exported JSON file.</p>
					<input type="file" name="acsp_import_file" accept=".json" required style="width:100%;margin-bottom:10px;">
					<?php submit_button( 'Upload & Import', 'secondary', 'acsp_import_json', false ); ?>
				</form>
			</div>
		</div>
	</div>
</div>