=== aCSP Builder – Bullet-Proof Content Security Policy in One Click ===
Contributors: amirawhadi
Tags: CSP, security, content security policy, nonce, XSS protection, headers
Requires at least: 5.8
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 1.0.3
License: GPL-2.0+
License URI: https://www.gnu.org/licenses/gpl-2.0.html

The FIRST WordPress plugin that automatically adds cryptographic nonces to every script & stylesheet, lets you hash-lock inline code, and builds a bullet-proof Content Security Policy in one click.

== Description ==

Stop copying random CSP snippets from Stack Overflow.  
aCSP Builder creates, tests and enforces a modern **Content-Security-Policy** without breaking your site.

**Zero-configuration presets**  
*   **Relaxed** – perfect for beginners: allows inline scripts, Google Analytics, jQuery CDN, YouTube embeds.  
*   **Balanced** – intermediate security: nonces for inline code, Google services allowed.  
*   **Strict** – maximum lock-down: same-origin only, all inline code requires nonces.

**One-click features**  
*  Automatic `nonce-*` injection for every enqueued script & stylesheet  
*  Hash-based allow-list for inline snippets (sha256/sha512)  
*  Live header preview before you save  
*  Report-Only mode – test without breaking production  
*  Built-in violation logger / report-uri endpoint  
*  Export / import JSON presets – move settings from staging to live in seconds  
*  Works with page-builders (Elementor, Beaver, Divi, Gutenberg, WPBakery) and CDN plugins out-of-the-box  
*  No ads, no tracking, no “Pro” upsell – 100 % free for personal & enterprise use

**Goodbye console errors, hello security headers**  
Install → choose a preset → hit **Save**.  
aCSP Builder handles the rest.

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/acsp-builder/` or install through the WordPress plugins screen.  
2. Activate the plugin.  
3. Navigate to **Admin → aCSP Builder** and pick a preset (or build your own policy).  
4. Switch from *Report-Only* to *Enforce* when you are confident the policy is clean.

== Frequently Asked Questions ==

= Will it break my site? =  
Start with **Report-Only** mode and watch the browser console. The plugin shows you the exact header it will send, so you can adjust directives before enforcing them.

= What are nonces and why do I need them? =  
A nonce is a one-time cryptographic token that proves an inline `<script>` or `<style>` block is trusted. aCSP Builder generates a fresh nonce on every pageload and injects it automatically – no template editing required.

= Can I whitelist external domains? =  
Yes. Every directive has a checkbox list of common sources (Google Fonts, Analytics, YouTube, CDNs) plus an “Add custom URL” field for anything else.

= Does it work with caching plugins? =  
Absolutely. Nonces are generated during page render, so the *value* changes, but the *placeholder* is inserted through output buffering – compatible with WP Rocket, W3 Total Cache, LiteSpeed, SiteGround Optimizer, etc.

= Is this plugin really free? =  
Yes. If it saves you a security audit or a week of Stack-Overflow, [buy me a coffee](https://www.buymeacoffee.com/amirawhadi) – caffeine keeps the updates coming.

== Screenshots ==

1. Quick-start presets – choose your security level in one click  
2. Custom policy builder – point-and-click directives + live header preview  
3. Settings – nonce injection, hash allow-list, report-uri endpoint  
4. Import / export JSON – move policies between sites instantly  
5. About tab – helpful links and donation box (no upsells)

== Changelog ==

= 1.0.3 =
* WordPress Coding Standards compliance (phpcs)  
* Add nonce verification for CSRF hardening  
* Escape all output, Yoda conditions, proper DocBlocks  
* Auto-fix 135 formatting violations via phpcbf  

= 1.0.2 =
* Fix PHP 8.2 deprecation notices  
* Improve Elementor compatibility  

= 1.0.1 =
* Add report-uri REST endpoint  
* Add hash allow-list UI  

= 1.0.0 =
* Initial release – presets, nonce injection, live preview

== Upgrade Notice ==

= 1.0.3 =  
Maintenance release: zero functional changes, full WordPress Coding Standards compliance. Safe to update.

== Credits ==

Created by [Amir Khosro Awhadi](https://profiles.wordpress.org/amirawhadi/) – security should be plug-and-play, not a week of Stack-Overflow.