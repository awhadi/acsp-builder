<?php
/**
 * Quick-start presets tab.
 *
 * @package acsp-builder
 */

if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( 'Unauthorized user' );
}

// ------------------------------------------------------------------
// Nonce check.
// ------------------------------------------------------------------
$nonce_action = 'acsp_tab_presets';
if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), $nonce_action ) ) {
	wp_die( 'Security check failed.' );
}
$acsp_tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'presets';

wp_enqueue_style( 'acsp-admin' );
wp_enqueue_script( 'acsp-admin' );

$current_preset = get_option( 'acsp_current_preset', '' );
?>
<div class="wrap">
	<h1><?php esc_html_e( 'a Content-Security-Policy (CSP) Builder', 'acsp-builder' ); ?></h1>

	<?php
	$preset_names = array(
		'relaxed'  => 'Relaxed',
		'balanced' => 'Balanced',
		'strict'   => 'Strict',
	);
	if ( $current_preset && isset( $preset_names[ $current_preset ] ) ) {
		$preset_name  = $preset_names[ $current_preset ];
		$preset_class = $current_preset;
	} else {
		$preset_name  = 'Custom';
		$preset_class = 'custom';
	}
	?>

	<h2 class="nav-tab-wrapper">
		<?php
		$tab_items = array(
			'presets'  => 'Quick Start',
			'builder'  => 'Custom Policy Builder',
			'settings' => 'Settings',
			'about'    => 'About',
		);
		foreach ( $tab_items as $tab_key => $label ) :
			printf(
				'<a href="%s" class="nav-tab %s">%s</a>',
				esc_url(
					add_query_arg(
						array(
							'page'     => 'acsp-builder',
							'tab'      => $tab_key,
							'_wpnonce' => wp_create_nonce( 'acsp_tab_' . $tab_key ),
						),
						admin_url( 'admin.php' )
					)
				),
				( 'presets' === $tab_key ) ? 'nav-tab-active' : '',
				esc_html( $label )
			);
		endforeach;
		?>
		<span class="acsp-preset-badge <?php echo esc_attr( $preset_class ); ?>">
			<?php echo esc_html( $current_preset && isset( acsp_get_presets()[ $current_preset ] ) ? acsp_get_presets()[ $current_preset ]['name'] : 'Custom' ); ?>
		</span>
	</h2>

	<div style="margin-top:20px;">
		<!-- Live header preview. -->
		<div class="acsp-card">
			<h2><?php esc_html_e( '🌐 Raw HTTP Response Header (as sent to the browser)', 'acsp-builder' ); ?></h2>
			<?php
			$mode_value      = get_option( 'acsp_mode', 'reject' );
			$nonce           = defined( 'ACSP_NONCE' ) ? ACSP_NONCE : '';
			$policy_arr      = get_option( 'acsp_policy', array() );
			$report_endpoint = get_option( 'acsp_report_endpoint', '' );
			// If it's an array (from aCSP Report plugin), use the REST endpoint.
			if ( is_array( $report_endpoint ) && isset( $report_endpoint['rest'] ) ) {
				$report_endpoint = $report_endpoint['rest'];
			}

			$directives = array();
			$source     = ( $current_preset && isset( acsp_get_presets()[ $current_preset ] ) )
				? acsp_get_presets()[ $current_preset ]['policy']
				: $policy_arr;

			// In the Live header preview section of acsp-presets.php.
			foreach ( $source as $key => $val ) {
				if ( '' === trim( $val ) ) {
					continue;
				}

				// Split into parts for proper ordering.
				$parts = preg_split( '/\s+/', trim( $val ) );

				// Insert hashes right after 'unsafe-hashes' if present.
				$hash_enabled = (bool) get_option( 'acsp_enable_hashes', 0 );
				$hash_values  = array_filter( (array) get_option( 'acsp_hash_values', array() ) );

				// In the hash insertion section for the preview.
				if ( $hash_enabled && ! empty( $hash_values ) &&
					in_array( $key, array( 'script-src', 'style-src' ), true ) ) {

					$unsafe_hashes_index = array_search( "'unsafe-hashes'", $parts, true );
					if ( false !== $unsafe_hashes_index ) {
						// Ensure each hash is properly quoted.
						$quoted_hashes = array();
						foreach ( $hash_values as $hash ) {
							$quoted_hashes[] = "'" . esc_attr( trim( $hash ) ) . "'";
						}
						// Insert quoted hashes immediately after 'unsafe-hashes'.
						array_splice( $parts, $unsafe_hashes_index + 1, 0, $quoted_hashes );
					}
				}

				// Add nonce at the end for script/style directives.
				if ( get_option( 'acsp_add_dynamic_nonce', 1 ) && in_array( $key, array( 'script-src', 'style-src' ), true ) ) {
					$parts[] = "'nonce-" . $nonce . "'";
				}

				// Reconstruct with proper order.
				$directives[] = $key . ' ' . implode( ' ', $parts );
			}

			if ( ! empty( $directives ) && $report_endpoint ) {
				$directives[] = 'report-uri ' . trim( $report_endpoint );
			}

			if ( ! empty( $directives ) ) {
				$header_name  = ( 'report' === $mode_value ) ? 'Content-Security-Policy-Report-Only' : 'Content-Security-Policy';
				$header_value = implode( '; ', $directives );
				echo '<pre style="background:#2d3748;color:#e2e8f0;padding:16px;border-radius:6px;font-family:Menlo,Consolas,monospace;font-size:13px;line-height:1.4;overflow-x:auto;">';
				echo esc_html( $header_name . ': ' . $header_value );
				echo '</pre>';
			} else {
				echo '<p style="color:#666;">' . esc_html__( 'No CSP header is currently being sent.', 'acsp-builder' ) . '</p>';
			}
			?>
		</div>

		<!-- Preset cards. -->
		<div class="acsp-card">
			<h2><?php esc_html_e( 'Quick Start Presets', 'acsp-builder' ); ?></h2>
			<p style="font-size:16px;color:#555;margin-bottom:30px;"><?php esc_html_e( 'Choose a preset below to quickly configure your CSP. You can then customize it further in the Custom Policy Builder tab.', 'acsp-builder' ); ?></p>

			<div class="acsp-presets">
				<?php foreach ( acsp_get_presets() as $key => $preset ) : ?>
					<div class="acsp-preset-card <?php echo esc_attr( $key === $current_preset ? 'active' : '' ); ?>">
						<h3>
							<?php
							echo esc_html(
								array(
									'relaxed'  => '🟢',
									'balanced' => '🟡',
									'strict'   => '🔴',
								)[ $key ] ?? '🔘'
							);
							?>
							<?php echo esc_html( $preset['name'] ); ?>
						</h3>
						<span class="level <?php echo esc_attr( $key ); ?>">
							<?php
							echo esc_html(
								array(
									'relaxed'  => 'Beginner',
									'balanced' => 'Intermediate',
									'strict'   => 'Advanced',
								)[ $key ] ?? ''
							);
							?>
						</span>
						<p class="description"><?php echo esc_html( $preset['description'] ?? '' ); ?></p>
						<ul class="features">
							<?php
							$features = array(
								'relaxed'  => array( 'Allows inline scripts & styles', 'Permits external CDNs like Google & jQuery', 'Compatible with most plugins', 'Perfect starting point' ),
								'balanced' => array( 'Uses nonces for inline scripts/styles', 'Allows Google services (Analytics, Fonts)', 'Permits common CDN sources', 'Good security without breaking functionality' ),
								'strict'   => array( 'Maximum security protection', 'Only same-origin resources allowed', 'Nonces required for all inline code', 'Blocks external resources entirely' ),
							);
							foreach ( ( $features[ $key ] ?? array() ) as $f ) {
								echo '<li>' . esc_html( $f ) . '</li>';
							}
							?>
						</ul>

						<a href="
						<?php
						echo esc_url(
							wp_nonce_url(
								add_query_arg(
									array(
										'tab' => 'presets',
										'acsp_apply_preset' => $key,
									)
								),
								'acsp_preset_action',
								'acsp_preset_nonce'
							)
						);
						?>
									" class="button <?php echo esc_attr( $key === $current_preset ? 'disabled' : ( 'balanced' === $key ? 'button-primary' : 'button-secondary' ) ); ?>" <?php echo disabled( $key === $current_preset, true, false ); ?>>
							<?php echo esc_html( $key === $current_preset ? '✓ Currently Active' : 'Apply ' . $preset['name'] ); ?>
						</a>

						<div class="acsp-current-policy">
							<h4><?php esc_html_e( 'Policy Preview:', 'acsp-builder' ); ?></h4>
							<code>script-src: <?php echo esc_html( implode( ' ', array_slice( explode( ' ', $preset['policy']['script-src'] ?? '' ), 0, 6 ) ) ); ?>...</code>
						</div>
					</div>
				<?php endforeach; ?>
			</div>

			<?php if ( ! $current_preset ) : ?>
				<div class="notice notice-info" style="margin-top:20px;">
					<p><strong><?php esc_html_e( 'ℹ️ No preset active', 'acsp-builder' ); ?></strong> <?php esc_html_e( 'custom policy is currently in effect.', 'acsp-builder' ); ?></p>
				</div>
			<?php endif; ?>
		</div>
	</div>
</div>