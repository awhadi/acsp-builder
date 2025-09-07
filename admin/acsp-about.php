<?php
/**
 * About tab – markup only
 *
 * @package aCSP
 */

if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( 'Unauthorized user' );
}

$active_tab     = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'presets'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$current_preset = get_option( 'acsp_current_preset', '' );

$plugin_list = get_transient( 'acsp_my_plugins' );
if ( false === $plugin_list ) {
	$resp = wp_remote_get( 'https://api.wordpress.org/plugins/info/1.2/?action=query_plugins&request[author]=amirawhadi&request[per_page]=20' );
	if ( ! is_wp_error( $resp ) && 200 === wp_remote_retrieve_response_code( $resp ) ) {
		$body        = json_decode( wp_remote_retrieve_body( $resp ), true );
		$plugin_list = $body['plugins'] ?? array();
		set_transient( 'acsp_my_plugins', $plugin_list, 12 * HOUR_IN_SECONDS );
	} else {
		$plugin_list = array();
	}
}
?>

<div class="wrap">
	<h1>a Content-Security-Policy (CSP) Builder</h1>
	<?php
	$nav_tabs = array(
		'presets'  => 'Quick Start',
		'builder'  => 'Custom Policy Builder',
		'settings' => 'Settings',
		'about'    => 'About',
	);
	?>
	<h2 class="nav-tab-wrapper">
		<?php foreach ( $nav_tabs as $nav_tab => $label ) : ?>
			<a href="
			<?php
			echo esc_url(
				add_query_arg(
					array(
						'page' => 'acsp-builder',
						'tab'  => $nav_tab,
					),
					admin_url( 'admin.php' )
				)
			);
			?>
						" class="nav-tab <?php echo ( $nav_tab === $active_tab ? 'nav-tab-active' : '' ); ?>"><?php echo esc_html( $label ); ?></a>
		<?php endforeach; ?>
		<span class="acsp-preset-badge <?php echo esc_attr( $current_preset ? $current_preset : 'custom' ); ?>">
			<?php echo esc_html( $current_preset && isset( acsp_get_presets()[ $current_preset ] ) ? acsp_get_presets()[ $current_preset ]['name'] : 'Custom' ); ?>
		</span>
	</h2>

	<div class="acsp-about-flex">
		<div class="acsp-about-main">
			<div class="acsp-card">
				<h2>🔐 The First WordPress CSP Plugin That Just Works</h2>
				<p>aCSP Builder automatically adds cryptographic nonces to every script & stylesheet, lets you hash-lock inline code, and builds a bullet-proof Content-Security-Policy in one click. No ads, no tracking, 100 % free for personal and enterprise use.</p>
				<p>Created by Amir Khosro Awhadi because security should be plug-and-play, not a week of Stack-Overflow.</p>
			</div>

			<div class="acsp-card">
				<h3>🚀 Why aCSP Builder?</h3>
				<ul class="acsp-feature-list">
					<li>⚡️ Zero-configuration presets: Relaxed, Balanced, Strict</li>
					<li>🛡️ Automatic nonce injection for themes & page-builders</li>
					<li>📊 Real-time header preview before you push “Save”</li>
					<li>🌍 Works with CDNs, GA4, YouTube, Google Fonts out-of-the-box</li>
					<li>🎯 Report-Only mode → test without breaking production</li>
				</ul>
			</div>

			<div class="acsp-card">
				<h3>📖 How We Stay Free</h3>
				<p>No feature gates, no “Pro” upsells, no banners in wp-admin. If the plugin saves you time or a security audit, <strong>buy me a coffee</strong> – it keeps the updates coming and the motivation high.</p>
			</div>
		</div>

		<aside class="acsp-about-side">
			<div class="acsp-donation-box">
				<h3>☕ Like what you see?</h3>
				<p>Your caffeine donation = faster updates, new features, bug-fixes.</p>
				<a class="button button-primary" href="https://www.buymeacoffee.com/amirawhadi" target="_blank" rel="noopener">Buy me a coffee</a>
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