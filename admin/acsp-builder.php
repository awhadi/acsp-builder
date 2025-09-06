<?php
/**
 * Custom policy builder tab.
 */
if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( 'Unauthorized user' );
}

wp_enqueue_style( 'acsp-admin' );
wp_enqueue_script( 'acsp-admin' );

$policy         = get_option( 'acsp_policy', [] );
$current_preset = get_option( 'acsp_current_preset', '' );

$directive_data = [
    'labels' => [
        'default-src' => 'default-src',
        'script-src'  => 'script-src',
        'style-src'   => 'style-src',
        'img-src'     => 'img-src',
        'font-src'    => 'font-src',
        'connect-src' => 'connect-src',
        'frame-src'   => 'frame-src',
        'worker-src'  => 'worker-src',
        'object-src'  => 'object-src',
        'base-uri'    => 'base-uri',
        'form-action' => 'form-action',
        'frame-ancestors' => 'frame-ancestors',
        'style-src-attr'  => 'style-src-attr',
    ],
    'descriptions' => [
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
    ],
    'keywords' => [
        'default-src' => [ "'none'", "'self'" ],
        'script-src'  => [ "'self'", "'unsafe-inline'", "'unsafe-eval'", "'strict-dynamic'", "'unsafe-hashes'" ],
        'style-src'   => [ "'self'", "'unsafe-inline'", "'unsafe-hashes'" ],
        'img-src'     => [ "'self'", 'data:', 'blob:', 'https:' ],
        'font-src'    => [ "'self'", 'https:' ],
        'connect-src' => [ "'self'", 'https:' ],
        'frame-src'   => [ "'self'", 'https:' ],
        'worker-src'  => [ "'self'", 'blob:' ],
        'object-src'  => [ "'none'" ],
        'base-uri'    => [ "'self'" ],
        'form-action' => [ "'self'" ],
        'frame-ancestors' => [ "'self'", "'none'" ],
        'style-src-attr'  => [ "'none'", "'self'", "'unsafe-inline'" ],
    ],
];

$keyword_explanations = [
    "'none'"          => 'Allows no resources. Most restrictive option.',
    "'self'"          => 'Allows resources from the same origin (same scheme, host and port).',
    "'unsafe-inline'" => 'Allows inline JavaScript or CSS. Use with caution as it reduces security.',
    "'unsafe-eval'"   => 'Allows unsafe dynamic code evaluation like eval(). Use with extreme caution.',
    "'strict-dynamic'"=> 'Allows scripts that are trusted by already-executing scripts.',
    "'unsafe-hashes'" => 'Allows inline scripts/styles by matching their hash.',
    'data:'           => 'Allows data: URIs (e.g., inline images).',
    'blob:'           => 'Allows blob: URIs (e.g., files created in browser).',
    'https:'          => 'Allows resources from any HTTPS source.',
    "'nonce-*'"       => 'Allows scripts/styles with a specific cryptographic nonce (automatically added).',
];
?>

<div class="wrap">
    <h1>a Content-Security-Policy (CSP) Builder</h1>

    <h2 class="nav-tab-wrapper">
        <?php foreach ( [ 'presets' => 'Quick Start', 'builder' => 'Custom Policy Builder', 'settings' => 'Settings', 'about' => 'About' ] as $tab => $label ) : ?>
            <a href="<?php echo esc_url( add_query_arg( 'tab', $tab, admin_url( 'admin.php?page=acsp-builder' ) ) ); ?>" class="nav-tab <?php echo( $tab === 'builder' ? 'nav-tab-active' : '' ); ?>"><?php echo esc_html( $label ); ?></a>
        <?php endforeach; ?>
        <span class="acsp-preset-badge <?php echo esc_attr( $current_preset ?: 'custom' ); ?>"><?php echo esc_html( $current_preset ? acsp_get_presets()[ $current_preset ]['name'] : 'Custom' ); ?></span>
    </h2>

    <div class="acsp-card" style="display: flex; gap: 20px;">
        <div style="flex:2;">
            <form method="post" action="options.php">
                <?php settings_fields( 'acsp' ); ?>

                <div class="acsp-actions-top">
                    <?php submit_button( 'Save Changes', 'primary', 'submit', false ); ?>
                    <button type="submit" name="acsp_reset_all" class="button button-link-delete"
                            onclick="return confirm('Are you sure you want to reset all CSP configuration? This will remove ALL settings and deactivate CSP.');">
                        Reset All Settings
                    </button>
                    <?php wp_nonce_field( 'acsp_reset_action', 'acsp_reset_nonce' ); ?>
                </div>

                <?php if ( $current_preset && 'custom' !== $current_preset ) : ?>
                    <div class="notice notice-success">
                        <p><strong>✅ <?php echo esc_html( acsp_get_presets()[ $current_preset ]['name'] ); ?> preset is active.</strong> Customising will switch to a custom policy.</p>
                    </div>
                <?php elseif ( ! $current_preset && empty( get_option( 'acsp_policy' ) ) ) : ?>
                    <div class="notice notice-info">
                        <p><strong>ℹ️ No CSP active</strong> Configure directives below to create a custom policy.</p>
                    </div>
                <?php endif; ?>

                <p>Check the keywords you want, and/or add extra hostnames. Leave everything unchecked to fall back to the default.</p>

                <table class="form-table">
                    <?php foreach ( $directive_data['labels'] as $dir => $label ) : ?>
                        <?php $current_parts = isset( $policy[ $dir ] ) ? preg_split( '/\s+/', trim( $policy[ $dir ] ) ) : []; ?>
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
                                            <input type="checkbox"
                                                   name="acsp_policy[<?php echo esc_attr( $dir ); ?>][]"
                                                   value="<?php echo esc_attr( $kw ); ?>"
                                                <?php checked( $checked ); ?>>
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
                                            <input type="text"
                                                   name="acsp_policy[<?php echo esc_attr( $dir ); ?>][]"
                                                   value="<?php echo esc_attr( $url ); ?>"
                                                   placeholder="https://example.com   "
                                                   class="regular-text code" />
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
                <h3>CSP Directive Values Explained</h3>
                <p>Understanding what each value means will help you build a more effective security policy:</p>

                <div class="acsp-info-sidebar">
                    <h4>Keyword Values</h4>
                    <ul>
                        <?php foreach ( $keyword_explanations as $keyword => $explanation ) : ?>
                            <li><strong><?php echo esc_html( $keyword ); ?></strong>: <?php echo esc_html( $explanation ); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <div class="acsp-info-sidebar">
                    <h4>Directive-Specific Guidance</h4>
                    <ul>
                        <li><strong>script-src</strong>: Controls which scripts can execute. Use 'self' for same-origin scripts, and add specific domains for external scripts like Google Analytics.</li>
                        <li><strong>style-src</strong>: Controls which stylesheets can load. Use 'self' for your theme/styles, and add domains for external fonts or CSS libraries.</li>
                        <li><strong>img-src</strong>: Controls image sources. Include 'self' and domains for external images. Add 'data:' if you use inline images.</li>
                        <li><strong>connect-src</strong>: Controls AJAX/API endpoints. Include your domain and any external APIs you use.</li>
                        <li><strong>frame-src</strong>: Controls iframe embeds. Include domains for embedded content like YouTube videos.</li>
                        <li><strong>font-src</strong>: Controls web font loading. Include your domain and external font providers like Google Fonts.</li>
                    </ul>
                </div>

                <div class="acsp-info-sidebar">
                    <h4>Best Practices</h4>
                    <ul>
                        <li>Start with restrictive policies and gradually add needed sources</li>
                        <li>Avoid 'unsafe-inline' and 'unsafe-eval' when possible</li>
                        <li>Use nonces (automatically handled by this plugin) for inline scripts/styles</li>
                        <li>Test your policy thoroughly before enforcing it</li>
                        <li>Use the Report URI feature to monitor violations</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>