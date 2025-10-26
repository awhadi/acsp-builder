<?php
if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( 'Unauthorized user' );
}

$nonce_action = 'acsp_tab_about';
if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), $nonce_action ) ) {
	wp_die( 'Security check failed.' );
}

$acsp_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'presets';

$plugin_list = get_transient( 'acsp_my_plugins' );
if ( false === $plugin_list ) {
	$resp = wp_remote_get( 'https://api.wordpress.org/plugins/info/1.2/?action=query_plugins&request[author]=amirawhadi&request[per_page]=20' );
	if ( ! is_wp_error( $resp ) && 200 === wp_remote_retrieve_response_code( $resp ) ) {
		$body = json_decode( wp_remote_retrieve_body( $resp ), true );
		$plugin_list = $body['plugins'] ?? array();
		set_transient( 'acsp_my_plugins', $plugin_list, 12 * HOUR_IN_SECONDS );
	} else {
		$plugin_list = array();
	}
}
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
			$url = add_query_arg(
				array(
					'page'     => 'acsp-builder',
					'tab'      => $nav_tab,
					'_wpnonce' => wp_create_nonce( 'acsp_tab_' . $nav_tab ),
				),
				admin_url( 'admin.php' )
			);
		?>
			<a href="<?php echo esc_url( $url ); ?>" class="nav-tab <?php echo ( $nav_tab === $acsp_tab ? 'nav-tab-active' : '' ); ?>"><?php echo esc_html( $label ); ?></a>
		<?php endforeach; 
		
		$current_preset = get_option( 'acsp_current_preset', '' );
		$preset_class = $current_preset ?: 'custom';
		$preset_name = $current_preset && isset( acsp_get_presets()[ $current_preset ] ) ? acsp_get_presets()[ $current_preset ]['name'] : 'Custom';
		?>
		<span class="acsp-preset-badge <?php echo esc_attr( $preset_class ); ?>"><?php echo esc_html( $preset_name ); ?></span>
	</h2>

	<div class="acsp-about-flex">
		<div class="acsp-about-main">
			<div class="acsp-card">
				<p>aCSP Builder automatically adds cryptographic nonces to every script & stylesheet, lets you hash-lock inline code, and builds a bullet-proof Content-Security-Policy in one click.</p>
			</div>

			<div class="acsp-card">
				<h3>🚀 Why aCSP Builder?</h3>
				<ul class="acsp-feature-list">
					<li>⚡️ Zero-configuration presets: Relaxed, Balanced, Strict</li>
					<li>🛡️ Automatic nonce injection for themes & page-builders</li>
					<li>📊 Real-time header preview before you push "Save"</li>
					<li>🌍 Works with CDNs, GA4, YouTube, Google Fonts out-of-the-box</li>
					<li>🎯 Report-Only mode → test without breaking production</li>
				</ul>
			</div>
		</div>

		<aside class="acsp-about-side">
			<div class="acsp-donation-box">
				<h3>☕ Like what you see?</h3>
				<p>Your caffeine donation = faster updates, new features, bug-fixes.</p>
				<a class="button button-primary" href="https://buymeacoffee.com/awhadikf" target="_blank" rel="noopener">Buy me a coffee</a>
			</div>

			<div class="acsp-card">
				<h3>My Other Plugins</h3>
				<?php if ( $plugin_list ) : ?>
					<?php foreach ( $plugin_list as $p ) : ?>
						<div class="acsp-plugin-card">
							<h4><?php echo esc_html( $p['name'] ); ?></h4>
							<p><?php echo wp_kses_post( wp_trim_words( $p['short_description'], 16 ) ); ?></p>
							<a class="button button-small" href="<?php echo esc_url( 'https://wordpress.org/plugins/' . $p['slug'] . '/' ); ?>" target="_blank" rel="noopener">View on wp.org →</a>
						</div>
					<?php endforeach; ?>
				<?php else : ?>
					<p>Loading…</p>
				<?php endif; ?>
			</div>
		</aside>
	</div>
</div>