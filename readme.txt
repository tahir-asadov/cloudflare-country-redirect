=== Simple Country Redirect with IP geolocation ===
Contributors: tahirasad
Tags: cloudflare, geolocation, redirect, country, geolocation-redirect
Requires at least: 6.8
Tested up to: 6.8
Stable tag: 1.0.3
Requires PHP: 8.0
License: GPLv2 or later

Redirects users based on their country using Cloudflare IP geolocation.

== Description ==
The Country Redirect plugin allows you to redirect users based on their country using Cloudflare's IP Geolocation feature. You can configure custom redirection rules based on the user's country. 

It also allows you to enable or disable redirection, and configure the duration of a cookie that prevents repeated redirections. The plugin is fully configurable through a settings page in the WordPress admin dashboard.

== Features ==
* Redirect users based on their country using Cloudflare's IP Geolocation.
* Add, edit, and delete country-to-URL redirection rules from the WordPress admin.
* Enable/disable redirection functionality directly from the settings page.
* Set the cookie lifetime to prevent repeated redirection.
* Secure and follows WordPress plugin standards.

== Installation ==
1. Upload the plugin directory to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to the plugin settings page to add redirection rules and configure the plugin.

== Configuration ==
- **Enable Redirects**: Enable or disable the plugin's redirection feature.
- **Redirect Rules**: Define country-to-URL redirection rules in the settings.
- **Cookie Lifetime**: Configure how many days to prevent repeated redirection.

== Changelog ==

= 1.0.3 =
* Update text domain

= 1.0.2 =
* Update plugin informations

= 1.0.1 =
* Add placeholders to input fields

= 1.0.0 =
* Initial release.
* Added functionality to redirect users based on Cloudflare’s IP Geolocation.
* Ability to add, edit, and delete country-to-URL redirection rules.
* Configurable cookie lifetime to prevent repeated redirects.

== Frequently Asked Questions ==

= How do I add redirection rules? =
Go to the plugin settings page in the WordPress admin and enter the country-to-URL mapping in the "Redirection Rules" section.

= How does the plugin know the user's country? =
The plugin uses Cloudflare's IP Geolocation feature to detect the user's country.

= How does the cookie work? =
Once a user is redirected based on their country, a cookie is set to prevent the user from being redirected again during the specified lifetime.

= Can I turn off the redirection feature? =
Yes, you can enable or disable the redirection functionality from the plugin settings page.

= Where do I configure the redirect URL for each country? =
You can add or edit the country-to-URL mapping directly from the plugin settings page.

== Screenshots ==
1. Screenshot of the plugin settings page.

== Support ==
If you encounter any issues or need help, please visit the plugin's support forum on WordPress.org.

== License ==
This plugin is licensed under the GPLv2 or later license.
