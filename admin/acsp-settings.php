<?php
/**
 * Settings tab (former huge settings section).
 */
if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( 'Unauthorized user' );
}

wp_enqueue_style( 'acsp-admin' );
wp_enqueue_script( 'acsp-admin' );

settings_errors( 'acsp_settings' );
?>

<div class="wrap">
	<h1>a Content-Security-Policy (CSP) Builder</h1>

	<h2 class="nav-tab-wrapper">
		<?php
		foreach ( array(
			'presets'  => 'Quick Start',
			'builder'  => 'Custom Policy Builder',
			'settings' => 'Settings',
			'about'    => 'About',
		) as $tab => $label ) :
			?>
			<a href="<?php echo esc_url( add_query_arg( 'tab', $tab, admin_url( 'admin.php?page=acsp-builder' ) ) ); ?>" class="nav-tab <?php echo( $tab === 'settings' ? 'nav-tab-active' : '' ); ?>"><?php echo esc_html( $label ); ?></a>
		<?php endforeach; ?>
		<span class="acsp-preset-badge <?php echo esc_attr( get_option( 'acsp_current_preset' ) ?: 'custom' ); ?>">
			<?php
			$cp = get_option( 'acsp_current_preset' );
			echo esc_html( $cp && isset( acsp_get_presets()[ $cp ] ) ? acsp_get_presets()[ $cp ]['name'] : 'Custom' );
			?>
		</span>
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

				<!-- ===== Hash Allow-List ===== -->
				<div class="acsp-card">
					<h3>Hash Allow-List</h3>
					<table class="form-table">
						<tr>
							<th scope="row">Enable&nbsp;hashes</th>
							<td>
								<label>
									<input type="checkbox" name="acsp_enable_hashes" id="acsp_enable_hashes" value="1"
										<?php checked( get_option( 'acsp_enable_hashes', 0 ) ); ?> />
									Enabled
								</label>
							</td>
						</tr>

						<tr class="acsp-hash-row" style="<?php echo get_option( 'acsp_enable_hashes', 0 ) ? '' : 'display:none;'; ?>">
							<th scope="row">Inline hashes</th>
							<td>
								<div id="acsp-hash-list" class="acsp-hash-list">
									<?php
									$hashes = get_option( 'acsp_hash_values', array() );
									if ( ! $hashes ) {
										$hashes = array( '' );
									}
									foreach ( $hashes as $hash ) :
										?>
										<div class="acsp-hash-item">
											<input type="text" name="acsp_hash_values[]" value="<?php echo esc_attr( $hash ); ?>"
													placeholder="sha256-…" class="regular-text code"/>
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
								<input type="url" name="acsp_report_endpoint" value="<?php echo esc_attr( get_option( 'acsp_report_endpoint', '' ) ); ?>" class="regular-text code" placeholder="https://example.com/wp-json/acsp/v1/report   ">
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

				<form method="post">
					<p><strong>Export</strong></p>
					<p style="font-size:13px;margin-bottom:12px;">Download a JSON file with the current policy & all settings.</p>
					<?php submit_button( 'Download JSON', 'primary', 'acsp_export_json', false ); ?>
				</form>

				<hr style="margin:20px 0;">

				<form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="acsp_import_json">
					<input type="file" name="acsp_import_file" accept=".json" required style="width:100%;margin-bottom:10px;">
					<?php wp_nonce_field( 'acsp_import_json_action', 'acsp_import_json_nonce' ); ?>
					<?php submit_button( 'Upload & Import', 'secondary', 'acsp_import_json', false ); ?>
				</form>
			</div>
		</div>
	</div>
</div>