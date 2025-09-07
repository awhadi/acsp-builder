<?php
/**
 * Custom policy builder tab.
 *
 * @package aCSP-Builder
 */

if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( 'Unauthorized user' );
}

wp_enqueue_style( 'acsp-admin' );
wp_enqueue_script( 'acsp-admin' );

$policy         = get_option( 'acsp_policy', array() );
$current_preset = get_option( 'acsp_current_preset', '' );

$directive_data = array(
	'labels'       => array(
		'default-src'     => 'default-src',
		'script-src'      => 'script-src',
		'style-src'       => 'style-src',
		'img-src'         => 'img-src',
		'font-src'        => 'font-src',
		'connect-src'     => 'connect-src',
		'frame-src'       => 'frame-src',
		'worker-src'      => 'worker-src',
		'object-src'      => 'object-src',
		'base-uri'        => 'base-uri',
		'form-action'     => 'form-action',
		'frame-ancestors' => 'frame-ancestors',
		'style-src-attr'  => 'style-src-attr',
	),
	'descriptions' => array(
		'default-src'     => 'Fallback for any fetch directive not explicitly listed.',
		'script-src'      => 'Valid sources for JavaScript.',
		'style-src'       => 'Valid sources for stylesheets.',
		'img-src'         => 'Valid sources for images.',
		'font-src'        => 'Valid sources for web fonts.',
		'connect-src'     => 'Valid endpoints for XHR, fetch, WebSocket, EventSource.',
		'frame-src'       => 'Valid sources for <iframe>.',
		'worker-src'      => 'Valid sources for Web Workers and Service Workers.',
		'object-src'      => 'Valid sources for <object>, <embed>, <applet>.',
		'base-uri'        => 'Restricts the URLs that can be used in a <base> tag.',
		'form-action'     => 'Restricts the URLs that can be used as the target of form submissions.',
		'frame-ancestors' => 'Restricts which pages may embed this page (click-jacking protection).',
		'style-src-attr'  => 'Inline style attributes. Set to \'none\' to block all inline style="...".',
	),
	'keywords'     => array(
		'default-src'     => array( "'none'", "'self'" ),
		'script-src'      => array( "'self'", "'unsafe-inline'", "'unsafe-eval'", "'strict-dynamic'", "'unsafe-hashes'" ),
		'style-src'       => array( "'self'", "'unsafe-inline'", "'unsafe-hashes'" ),
		'img-src'         => array( "'self'", 'data:', 'blob:', 'https:' ),
		'font-src'        => array( "'self'", 'https:' ),
		'connect-src'     => array( "'self'", 'https:' ),
		'frame-src'       => array( "'self'", 'https:' ),
		'worker-src'      => array( "'self'", 'blob:' ),
		'object-src'      => array( "'none'" ),
		'base-uri'        => array( "'self'" ),
		'form-action'     => array( "'self'" ),
		'frame-ancestors' => array( "'self'", "'none'" ),
		'style-src-attr'  => array( "'none'", "'self'", "'unsafe-inline'" ),
	),
);

$keyword_explanations = array(
	"'none'"           => 'Allows no resources. Most restrictive option.',
	"'self'"           => 'Allows resources from the same origin (same scheme, host and port).',
	"'unsafe-inline'"  => 'Allows inline JavaScript or CSS. Use with caution as it reduces security.',
	"'unsafe-eval'"    => 'Allows unsafe dynamic code evaluation like eval(). Use with extreme caution.',
	"'strict-dynamic'" => 'Allows scripts that are trusted by already-executing scripts.',
	"'unsafe-hashes'"  => 'Allows inline scripts/styles by matching their hash.',
	'data:'            => 'Allows data: URIs (e.g., inline images).',
	'blob:'            => 'Allows blob: URIs (e.g., files created in browser).',
	'https:'           => 'Allows resources from any HTTPS source.',
	"'nonce-*'"        => 'Allows scripts/styles with a specific cryptographic nonce (automatically added).',
);
?>

<div class="wrap">
	<h1><?php esc_html_e( 'a Content-Security-Policy (CSP) Builder', 'aCSP' ); ?></h1>

	<?php
	// phpcs:disable WordPress.WP.GlobalVariablesOverride.Prohibited -- local scope only.
	$tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'presets'; // Input var okay.
	// phpcs:enable WordPress.WP.GlobalVariablesOverride.Prohibited
	?>

	<h2 class="nav-tab-wrapper">
		<?php
		foreach ( array(
			'presets'  => 'Quick Start',
			'builder'  => 'Custom Policy Builder',
			'settings' => 'Settings',
			'about'    => 'About',
		) as $slug => $label ) :
			?>
			<a href="<?php echo esc_url( add_query_arg( 'tab', $slug, admin_url( 'admin.php?page=acsp-builder' ) ) ); ?>" class="nav-tab <?php echo ( 'builder' === $slug ) ? 'nav-tab-active' : ''; ?>"><?php echo esc_html( $label ); ?></a>
		<?php endforeach; ?>
		<?php
		$badge_preset = ( $current_preset && isset( acsp_get_presets()[ $current_preset ] ) ) ? acsp_get_presets()[ $current_preset ]['name'] : 'Custom';
		$badge_class  = $current_preset ? $current_preset : 'custom';
		?>
		<span class="acsp-preset-badge <?php echo esc_attr( $badge_class ); ?>">
			<?php echo esc_html( $badge_preset ); ?>
		</span>
	</h2>

	<div class="acsp-card" style="display: flex; gap: 20px;">
		<div style="flex:2;">
			<form method="post" action="options.php">
				<?php settings_fields( 'acsp' ); ?>

				<div class="acsp-actions-top">
					<?php submit_button( 'Save Changes', 'primary', 'submit', false ); ?>
					<?php wp_nonce_field( 'acsp_reset_action', 'acsp_reset_nonce' ); ?>
					<button type="submit" name="acsp_reset_all" class="button button-link-delete" onclick="return confirm('Are you sure you want to reset all CSP configuration? This will remove ALL settings and deactivate CSP.');">Reset All Policies</button>
				</div>

				<?php if ( $current_preset && 'custom' !== $current_preset ) : ?>
					<div class="notice notice-success">
						<p><strong><?php esc_html_e( '✅ Preset active.', 'aCSP' ); ?></strong> <?php esc_html_e( 'Customising will switch to a custom policy.', 'aCSP' ); ?></p>
					</div>
				<?php elseif ( ! $current_preset && empty( get_option( 'acsp_policy' ) ) ) : ?>
					<div class="notice notice-info">
						<p><strong><?php esc_html_e( 'ℹ️ No CSP active', 'aCSP' ); ?></strong> <?php esc_html_e( 'Configure directives below to create a custom policy.', 'aCSP' ); ?></p>
					</div>
				<?php endif; ?>

				<p><?php esc_html_e( 'Check the keywords you want, and/or add extra hostnames. Leave everything unchecked to fall back to the default.', 'aCSP' ); ?></p>

				<table class="form-table">
					<?php foreach ( $directive_data['labels'] as $dir => $label ) : ?>
						<?php $current_parts = isset( $policy[ $dir ] ) ? preg_split( '/\s+/', trim( $policy[ $dir ] ) ) : array(); ?>
						<tr>
							<th><?php echo esc_html( $label ); ?>
								<span class="dashicons dashicons-info" title="<?php echo esc_attr( $directive_data['descriptions'][ $dir ] ); ?>"></span>
							</th>
							<td>
								<div class="acsp-keywords">
									<?php
									$is_hash_enabled = (bool) get_option( 'acsp_enable_hashes', 0 );
									foreach ( $directive_data['keywords'][ $dir ] as $kw ) :
										if ( "'unsafe-hashes'" === $kw && ! $is_hash_enabled ) {
											continue;
										}
										$checked = in_array( $kw, $current_parts, true );
										?>
										<label>
											<input type="checkbox" name="acsp_policy[<?php echo esc_attr( $dir ); ?>][]" value="<?php echo esc_attr( $kw ); ?>" <?php checked( $checked ); ?>>
											<?php echo esc_html( $kw ); ?>
										</label>
									<?php endforeach; ?>
								</div>

								<div class="acsp-custom-urls" data-dir="<?php echo esc_attr( $dir ); ?>">
									<?php
									$custom = array_diff( $current_parts, $directive_data['keywords'][ $dir ] );
									foreach ( $custom as $url ) :
										?>
										<div style="margin-top:4px;">
											<input type="text" name="acsp_policy[<?php echo esc_attr( $dir ); ?>][]" value="<?php echo esc_attr( $url ); ?>" placeholder="https://example.com" class="regular-text code" />
											<button type="button" class="button acsp-remove-url">Remove</button>
										</div>
									<?php endforeach; ?>
								</div>
								<button type="button" class="button acsp-add-url" data-dir="<?php echo esc_attr( $dir ); ?>">Add custom URL</button>
							</td>
						</tr>
					<?php endforeach; ?>
				</table>

				<div style="display:flex; justify-content:flex-end; margin-top:20px;">
					<?php submit_button( 'Save Changes', 'primary', 'submit', false ); ?>
				</div>
			</form>
		</div>

		<div style="flex:1;">
			<div class="acsp-card">
				<h3><?php esc_html_e( 'CSP Directive Values Explained', 'aCSP' ); ?></h3>
				<p><?php esc_html_e( 'Understanding what each value means will help you build a more effective security policy:', 'aCSP' ); ?></p>

				<div class="acsp-info-sidebar">
					<h4><?php esc_html_e( 'Keyword Values', 'aCSP' ); ?></h4>
					<ul>
						<?php foreach ( $keyword_explanations as $keyword => $explanation ) : ?>
							<li><strong><?php echo esc_html( $keyword ); ?></strong>: <?php echo esc_html( $explanation ); ?></li>
						<?php endforeach; ?>
					</ul>
				</div>

				<div class="acsp-info-sidebar">
					<h4><?php esc_html_e( 'Directive-Specific Guidance', 'aCSP' ); ?></h4>
					<ul>
						<li><strong>script-src</strong>: <?php esc_html_e( 'Controls which scripts can execute. Use self for same-origin scripts, and add specific domains for external scripts like Google Analytics.', 'aCSP' ); ?></li>
						<li><strong>style-src</strong>: <?php esc_html_e( 'Controls which stylesheets can load. Use self for your theme/styles, and add domains for external fonts or CSS libraries.', 'aCSP' ); ?></li>
						<li><strong>img-src</strong>: <?php esc_html_e( 'Controls image sources. Include self and domains for external images. Add data: if you use inline images.', 'aCSP' ); ?></li>
						<li><strong>connect-src</strong>: <?php esc_html_e( 'Controls AJAX/API endpoints. Include your domain and any external APIs you use.', 'aCSP' ); ?></li>
						<li><strong>frame-src</strong>: <?php esc_html_e( 'Controls iframe embeds. Include domains for embedded content like YouTube videos.', 'aCSP' ); ?></li>
						<li><strong>font-src</strong>: <?php esc_html_e( 'Controls web font loading. Include your domain and external font providers like Google Fonts.', 'aCSP' ); ?></li>
					</ul>
				</div>

				<div class="acsp-info-sidebar">
					<h4><?php esc_html_e( 'Best Practices', 'aCSP' ); ?></h4>
					<ul>
						<li><?php esc_html_e( 'Start with restrictive policies and gradually add needed sources.', 'aCSP' ); ?></li>
						<li><?php esc_html_e( 'Avoid unsafe-inline and unsafe-eval when possible.', 'aCSP' ); ?></li>
						<li><?php esc_html_e( 'Use nonces (automatically handled by this plugin) for inline scripts/styles.', 'aCSP' ); ?></li>
						<li><?php esc_html_e( 'Test your policy thoroughly before enforcing it.', 'aCSP' ); ?></li>
						<li><?php esc_html_e( 'Use the Report URI feature to monitor violations.', 'aCSP' ); ?></li>
					</ul>
				</div>
			</div>
		</div>
	</div>
</div>