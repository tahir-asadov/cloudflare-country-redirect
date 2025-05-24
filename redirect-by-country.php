<?php
/*
Plugin Name: Simple Country Redirect with IP geolocation
Description: Redirects visitors based on their country using Cloudflare's IP geolocation.
Version: 1.0.3
Author: Tahir Asadli
Author URI: https://tahir-asadov.github.io/
Text Domain: redirect-by-country
License: GPLv2 or later
*/

// Do not load directly.
if (!defined('ABSPATH')) {
	die();
}


/**  Redirect visitors based on their country */
function redirect_by_country_geo_redirect()
{
	// Skip if admin, AJAX, already redirected, redirection is off, or not front page.
	if (
		is_admin() ||
		(defined('DOING_AJAX') && DOING_AJAX) ||
		isset($_COOKIE['redirect_by_country_redirected']) ||
		!is_front_page() ||
		!get_option('redirect_by_country_redirect_enabled', 1)
	) {
		return;
	}

	// Skip if bot/crawler.
	if (redirect_by_country_is_bot()) {
		return;
	}

	$country = isset($_SERVER['HTTP_CF_IPCOUNTRY']) ? strtoupper(sanitize_text_field(wp_unslash($_SERVER['HTTP_CF_IPCOUNTRY']))) : '';

	if (!preg_match('/^[A-Z]{2}$/', $country)) {
		$country = null;
	}
	$rules = get_option('redirect_by_country_redirect_rules', array());

	if (!$country || empty($rules)) {
		return;
	}

	$current_path = isset($_SERVER['REQUEST_URI']) ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI'])) : '';

	foreach ($rules as $rule) {
		$rule_country = strtoupper($rule['country']);
		$rule_target_path = trim(wp_parse_url($rule['url'], PHP_URL_PATH), '/');

		if ($rule_country === strtoupper($country)) {
			if ($current_path === $rule_target_path) {
				return;
			}

			$cookie_days = intval(get_option('redirect_by_country_cookie_days', 7));
			$cookie_lifetime = time() + ($cookie_days * 24 * 60 * 60);
			setcookie('redirect_by_country_redirected', '1', $cookie_lifetime, '/');

			$redirect_url = home_url($rule_target_path);
			wp_redirect($redirect_url, 302);
			exit;
		}
	}
}

function redirect_by_country_is_bot()
{
	if (empty($_SERVER['HTTP_USER_AGENT'])) {
		return false;
	}

	$bots = array(
		'bot',
		'crawl',
		'slurp',
		'spider',
		'mediapartners',
		'google',
		'bingpreview',
		'facebookexternalhit',
		'linkedinbot',
		'embedly',
		'quora link preview',
		'outbrain',
		'pinterest',
		'developers.google.com/+/web/snippet',
	);

	$agent = strtolower(sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT'])));

	foreach ($bots as $bot) {
		if (strpos($agent, $bot) !== false) {
			return true;
		}
	}

	return false;
}

// Register plugin settings page in the admin menu
function redirect_by_country_add_admin_menu()
{
	add_options_page(
		'Country Redirect Settings',
		'Country Redirect',
		'manage_options',
		'redirect_by_country_settings',
		'redirect_by_country_settings_page'
	);
}

// Initialize plugin settings
function redirect_by_country_settings_init()
{

	register_setting('redirect_by_country_plugin_settings', 'redirect_by_country_redirect_rules', [
		'sanitize_callback' => 'redirect_by_country_sanitize_rules'
	]);

	register_setting('redirect_by_country_plugin_settings', 'redirect_by_country_cookie_days', [
		'sanitize_callback' => 'absint'
	]);

	register_setting('redirect_by_country_plugin_settings', 'redirect_by_country_redirect_enabled', [
		'sanitize_callback' => 'redirect_by_country_sanitize_checkbox'
	]);

	add_settings_section(
		'redirect_by_country_section_main',
		'Redirection Rules',
		null,
		'redirect_by_country_settings'
	);

}
function redirect_by_country_sanitize_rules($input)
{
	$sanitized = [];

	if (is_array($input)) {
		foreach ($input as $rule) {
			if (!is_array($rule) || empty($rule['country']) || empty($rule['url'])) {
				continue;
			}
			$country = strtoupper(sanitize_text_field($rule['country']));
			$url = esc_url_raw($rule['url']);

			if ($country && $url) {
				$sanitized[] = ['country' => $country, 'url' => $url];
			}
		}
	}

	return $sanitized;
}

function redirect_by_country_sanitize_checkbox($value)
{
	return $value === '1' ? 1 : 0;
}

// Display the settings page
function redirect_by_country_settings_page()
{
	// Handle form submission securely
	if (isset($_POST['redirect_by_country_rules_submit']) && check_admin_referer('redirect_by_country_rules_form')) {
		// Sanitize inputs
		$countries = isset($_POST['country']) ? array_map('sanitize_text_field', wp_unslash($_POST['country'])) : array();
		$urls = isset($_POST['url']) ? array_map('esc_url_raw', wp_unslash($_POST['url'])) : array();

		$rules = array();
		for ($i = 0; $i < count($countries); $i++) {
			$c = strtoupper(trim($countries[$i]));
			$u = trim($urls[$i]);
			if ($c && $u) {
				$rules[] = array(
					'country' => $c,
					'url' => esc_url_raw($u),
				);
			}
		}

		// Update settings securely
		update_option('redirect_by_country_redirect_rules', $rules);

		// Sanitize and save cookie days and redirect enabled
		$cookie_days = intval($_POST['redirect_by_country_cookie_days'] ?? 7);
		update_option('redirect_by_country_cookie_days', $cookie_days);

		$redirect_enabled = isset($_POST['redirect_by_country_redirect_enabled']) ? 1 : 0;
		update_option('redirect_by_country_redirect_enabled', $redirect_enabled);

		echo '<div class="updated"><p>' . esc_html__('Settings saved', 'simple-country-redirect-with-ip-geolocation') . '</p></div>';
	}

	// Get the settings for rendering
	$cookie_days = get_option('redirect_by_country_cookie_days', 7);
	$redirect_enabled = get_option('redirect_by_country_redirect_enabled', 1);
	$rules = get_option('redirect_by_country_redirect_rules', array());
	?>
	<div class="wrap">
		<h1><?php esc_html_e('Country Redirect Settings', 'simple-country-redirect-with-ip-geolocation'); ?></h1>
		<form method="post">
			<?php wp_nonce_field('redirect_by_country_rules_form'); ?>
			<table class="widefat" id="ccr-rules-table">
				<thead>
					<tr>
						<th><?php esc_html_e('Country Code', 'simple-country-redirect-with-ip-geolocation'); ?></th>
						<th><?php esc_html_e('Redirect URL', 'simple-country-redirect-with-ip-geolocation'); ?></th>
						<th><?php esc_html_e('Action', 'simple-country-redirect-with-ip-geolocation'); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if (!empty($rules)): ?>
						<?php foreach ($rules as $rule): ?>
							<tr>
								<td><input type="text"
										placeholder="<?php esc_html_e('Country Code', 'simple-country-redirect-with-ip-geolocation'); ?>: es"
										name="country[]" value="<?php echo esc_attr($rule['country']); ?>" /></td>
								<td><input type="url" placeholder="https://example.com/es" name="url[]"
										value="<?php echo esc_url($rule['url']); ?>" style="width: 100%;" /></td>
								<td><button type="button"
										class="button ccr-remove-row"><?php esc_html_e('Remove', 'simple-country-redirect-with-ip-geolocation'); ?></button>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
			<p>
				<button type="button" class="button"
					id="ccr-add-row"><?php esc_html_e('Add Rule', 'simple-country-redirect-with-ip-geolocation'); ?></button>
			</p>
			<h2><?php esc_html_e('Settings', 'simple-country-redirect-with-ip-geolocation'); ?></h2>
			<table class="form-table">
				<tr>
					<th scope="row"><label
							for="redirect_by_country_redirect_enabled"><?php esc_html_e('Enable Redirects', 'simple-country-redirect-with-ip-geolocation'); ?></label>
					</th>
					<td>
						<input type="checkbox" id="redirect_by_country_redirect_enabled" name="redirect_by_country_redirect_enabled"
							value="1" <?php checked(1, $redirect_enabled, true); ?> />
						<p class="description">
							<?php esc_html_e('Check to enable country-based redirection', 'simple-country-redirect-with-ip-geolocation'); ?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label
							for="redirect_by_country_cookie_days"><?php esc_html_e('Redirect Cookie Lifetime (days)', 'simple-country-redirect-with-ip-geolocation'); ?></label>
					</th>
					<td>
						<input type="number" name="redirect_by_country_cookie_days" id="redirect_by_country_cookie_days"
							value="<?php echo esc_attr($cookie_days); ?>" min="1" />
						<p class="description">
							<?php esc_html_e('How many days to prevent repeated redirection', 'simple-country-redirect-with-ip-geolocation'); ?>
						</p>
					</td>
				</tr>
			</table>
			<p><input type="submit" name="redirect_by_country_rules_submit" class="button-primary"
					value="<?php esc_html_e('Save Rules', 'simple-country-redirect-with-ip-geolocation'); ?>"></p>
		</form>
	</div>
	<?php
}

// Enqueue JavaScript file for the plugin settings page
function redirect_by_country_enqueue_admin_scripts($hook)
{
	// Check if we're on the plugin settings page
	if ('settings_page_redirect_by_country_settings' !== $hook) {
		return;
	}

	// Enqueue the JavaScript file
	wp_enqueue_script(
		'ccr-admin-scripts',
		plugin_dir_url(__FILE__) . 'assets/js/ccr-scripts.js?v=2',
		array('wp-i18n'),
		'1.0.3',
		true
	);
}

function redirect_by_country_add_settings_link($links)
{
	$settings_link = '<a href="' . admin_url('options-general.php?page=redirect_by_country_settings') . '">Settings</a>';
	array_unshift($links, $settings_link);
	return $links;
}

add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'redirect_by_country_add_settings_link');
add_action('admin_enqueue_scripts', 'redirect_by_country_enqueue_admin_scripts');
add_action('template_redirect', 'redirect_by_country_geo_redirect');
add_action('admin_menu', 'redirect_by_country_add_admin_menu');
add_action('admin_init', 'redirect_by_country_settings_init');