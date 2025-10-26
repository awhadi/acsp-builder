=== aCSP Builder ===
Contributors: Awhadi
Tags: security, content security policy, csp, xss protection, headers
Requires at least: 5.8
Tested up to: 6.8
Stable tag: 1.0.12
License: GPL-2.0+
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Build and manage Content Security Policy headers with ease.

== Description ==
aCSP Builder helps you create and implement Content Security Policy (CSP) headers for your WordPress site.

**Quick Start Presets**
Choose from three security levels:
* **Relaxed** - Good for beginners, allows common external services
* **Balanced** - Moderate security with nonce protection
* **Strict** - Maximum security, same-origin only

**Main Features**
* Automatic nonce injection for scripts and styles
* Hash-based allow-list for inline code
* Live header preview before activation
* Meta tag injection option for additional compatibility
* Custom policy builder for advanced configuration
* Report-Only mode for testing
* Export/import settings as JSON
* Works with popular page builders

**How It Works**
1. Install and activate the plugin
2. Choose a preset or build custom policy
3. Test in Report-Only mode
4. Switch to Enforce mode when ready

== Installation ==
1. Upload plugin files to `/wp-content/plugins/acsp-builder/` or install via WordPress plugins screen
2. Activate the plugin
3. Go to **Admin → aCSP Builder**
4. Select a preset and configure as needed

== Frequently Asked Questions ==

= Will this break my site? =
Start with Report-Only mode to test without affecting your site. The plugin shows exactly what headers will be sent.

= What are nonces? =
Nonces are security tokens that verify trusted scripts and styles. The plugin automatically adds them without editing templates.

= Can I allow external domains? =
Yes. Each directive includes common domains (Google Fonts, Analytics, etc.) and custom URL fields.

= Does it work with caching? =
Works with object caching (Redis, etc.). Page caching may require additional configuration.

= Is this plugin free? =
Yes. Donations via [Buy Me a Coffee](https://buymeacoffee.com/awhadikf) are appreciated but not required.

== Screenshots ==
1. Preset selection screen
2. Custom policy builder
3. Settings and configuration
4. Import/export tools
5. About and support information

== Changelog ==

= 1.0.12 =
* Code optimization and performance improvements

= 1.0.11 =
* Fixed PHP coding standards violations
* Improved code quality and documentation

= 1.0.10 =
* Added endpoint testing feature
* Improved security and error handling

= 1.0.9 =
* Fixed CSP header formatting
* Improved hash and nonce handling

= 1.0.7 =
* Added security improvements
* Fixed code standards issues

= 1.0.3 =
* WordPress coding standards compliance
* Security enhancements

= 1.0.2 =
* PHP 8.2 compatibility
* Elementor improvements

= 1.0.1 =
* Added report endpoint
* Hash allow-list interface

= 1.0.0 =
* Initial release

== Upgrade Notice ==
= 1.0.3 =
Maintenance release with code improvements. Safe to update.

== Credits ==
Created by [Amir Khosro Awhadi](https://profiles.wordpress.org/awhadi/)