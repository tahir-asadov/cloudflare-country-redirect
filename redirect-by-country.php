<?php
/*
Plugin Name: Simple Country Redirect with IP geolocation
Description: Redirects visitors based on their country using Cloudflare's IP geolocation.
Version: 1.0.1
Author: Tahir Asadli
Author URI: https://github.com/tahir-asadov
License: GPLv2 or later
*/

/**  Redirect visitors based on their country */
function ccr_geo_redirect()
{
	// Skip if admin, AJAX, already redirected, redirection is off, or not front page.
	if (
		is_admin() ||
		(defined('DOING_AJAX') && DOING_AJAX) ||
		isset($_COOKIE['ccr_redirected']) ||
		!is_front_page() ||
		!get_option('ccr_redirect_enabled', 1)
	) {
		return;
	}

	// Skip if bot/crawler.
	if (ccr_is_bot()) {
		return;
	}

	$country = empty($_SERVER['HTTP_CF_IPCOUNTRY']) ? wp_unslash($_SERVER['HTTP_CF_IPCOUNTRY']) : null;
	$rules = get_option('ccr_redirect_rules', array());

	if (!$country || empty($rules)) {
		return;
	}

	$current_path = trim($_SERVER['REQUEST_URI'], '/');

	foreach ($rules as $rule) {
		$rule_country = strtoupper($rule['country']);
		$rule_target_path = trim(parse_url($rule['url'], PHP_URL_PATH), '/');

		if ($rule_country === strtoupper($country)) {
			if ($current_path === $rule_target_path) {
				return;
			}

			$cookie_days = intval(get_option('ccr_cookie_days', 7));
			$cookie_lifetime = time() + ($cookie_days * 24 * 60 * 60);
			setcookie('ccr_redirected', '1', $cookie_lifetime, '/');

			$redirect_url = home_url($rule_target_path);
			wp_redirect($redirect_url, 302);
			exit;
		}
	}
}

function ccr_is_bot()
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

	$agent = strtolower($_SERVER['HTTP_USER_AGENT']);

	foreach ($bots as $bot) {
		if (strpos($agent, $bot) !== false) {
			return true;
		}
	}

	return false;
}

// Register plugin settings page in the admin menu
function ccr_add_admin_menu()
{
	add_options_page(
		'Country Redirect Settings',
		'Country Redirect',
		'manage_options',
		'ccr_settings',
		'ccr_settings_page'
	);
}

// Initialize plugin settings
function ccr_settings_init()
{
	register_setting('ccr_plugin_settings', 'ccr_redirect_rules');
	register_setting('ccr_plugin_settings', 'ccr_cookie_days');
	register_setting('ccr_plugin_settings', 'ccr_redirect_enabled');

	add_settings_section(
		'ccr_section_main',
		'Redirection Rules',
		null,
		'ccr_settings'
	);

	add_settings_field(
		'ccr_redirect_rules_field',
		'Country-to-URL Map (JSON)',
		'ccr_rules_field_render',
		'ccr_settings',
		'ccr_section_main'
	);

	add_settings_field(
		'ccr_cookie_days_field',
		'Redirect Cookie Lifetime (days)',
		'ccr_cookie_days_field_render',
		'ccr_settings',
		'ccr_section_main'
	);

	add_settings_field(
		'ccr_redirect_enabled_field',
		'Enable Redirects',
		'ccr_redirect_enabled_field_render',
		'ccr_settings',
		'ccr_section_main'
	);
}

// Render the Country-to-URL Map (JSON) field
function ccr_rules_field_render()
{
	$value = get_option('ccr_redirect_rules', '{}');
	echo '<textarea name="ccr_redirect_rules" rows="10" cols="60">' . esc_textarea($value) . '</textarea>';
}

// Render the Redirect Cookie Lifetime (days) field
function ccr_cookie_days_field_render()
{
	$value = get_option('ccr_cookie_days', 7);
	echo '<input type="number" name="ccr_cookie_days" value="' . esc_attr($value) . '" min="1" />';
}

// Render the Enable Redirects field
function ccr_redirect_enabled_field_render()
{
	$value = get_option('ccr_redirect_enabled', 1);
	echo '<input type="checkbox" name="ccr_redirect_enabled" value="1" ' . checked(1, $value, false) . ' />';
}

// Display the settings page
function ccr_settings_page()
{
	// Handle form submission securely
	if (isset($_POST['ccr_rules_submit']) && check_admin_referer('ccr_rules_form')) {
		// Sanitize inputs
		$countries = isset($_POST['country']) ? array_map('sanitize_text_field', $_POST['country']) : array();
		$urls = isset($_POST['url']) ? array_map('esc_url_raw', $_POST['url']) : array();

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
		update_option('ccr_redirect_rules', $rules);

		// Sanitize and save cookie days and redirect enabled
		$cookie_days = intval($_POST['ccr_cookie_days'] ?? 7);
		update_option('ccr_cookie_days', $cookie_days);

		$redirect_enabled = isset($_POST['ccr_redirect_enabled']) ? 1 : 0;
		update_option('ccr_redirect_enabled', $redirect_enabled);

		echo '<div class="updated"><p>Settings saved.</p></div>';
	}

	// Get the settings for rendering
	$cookie_days = get_option('ccr_cookie_days', 7);
	$redirect_enabled = get_option('ccr_redirect_enabled', 1);
	$rules = get_option('ccr_redirect_rules', array());
	?>
	<div class="wrap">
		<h1>Country Redirect Settings</h1>
		<form method="post">
			<?php wp_nonce_field('ccr_rules_form'); ?>
			<table class="widefat" id="ccr-rules-table">
				<thead>
					<tr>
						<th>Country Code</th>
						<th>Redirect URL</th>
						<th>Action</th>
					</tr>
				</thead>
				<tbody>
					<?php if (!empty($rules)): ?>
						<?php foreach ($rules as $rule): ?>
							<tr>
								<td><input type="text" placeholder="Country code: es" name="country[]"
										value="<?php echo esc_attr($rule['country']); ?>" /></td>
								<td><input type="url" placeholder="https://example.com/es" name="url[]"
										value="<?php echo esc_url($rule['url']); ?>" style="width: 100%;" /></td>
								<td><button type="button" class="button ccr-remove-row">Remove</button></td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
			<p>
				<button type="button" class="button" id="ccr-add-row">Add Rule</button>
			</p>
			<h2>Settings</h2>
			<table class="form-table">
				<tr>
					<th scope="row"><label for="ccr_redirect_enabled">Enable Redirects</label></th>
					<td>
						<input type="checkbox" id="ccr_redirect_enabled" name="ccr_redirect_enabled" value="1" <?php checked(1, $redirect_enabled, true); ?> />
						<p class="description">Check to enable country-based redirection.</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="ccr_cookie_days">Redirect Cookie Lifetime (days)</label></th>
					<td>
						<input type="number" name="ccr_cookie_days" id="ccr_cookie_days"
							value="<?php echo esc_attr($cookie_days); ?>" min="1" />
						<p class="description">How many days to prevent repeated redirection.</p>
					</td>
				</tr>
			</table>
			<p><input type="submit" name="ccr_rules_submit" class="button-primary" value="Save Rules"></p>
		</form>
	</div>
	<?php
}

// Enqueue JavaScript file for the plugin settings page
function ccr_enqueue_admin_scripts($hook)
{
	// Check if we're on the plugin settings page
	if ('settings_page_ccr_settings' !== $hook) {
		return;
	}

	// Enqueue the JavaScript file
	wp_enqueue_script(
		'ccr-admin-scripts', // Handle for the script
		plugin_dir_url(__FILE__) . 'assets/js/ccr-scripts.js', // Path to the JS file
		array('jquery'), // Dependencies (if any)
		null, // Version (optional)
		true // Load in footer
	);
}

function ccr_add_settings_link($links)
{
	$settings_link = '<a href="' . admin_url('options-general.php?page=ccr_settings') . '">Settings</a>';
	array_unshift($links, $settings_link);
	return $links;
}

add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'ccr_add_settings_link');
add_action('admin_enqueue_scripts', 'ccr_enqueue_admin_scripts');
add_action('template_redirect', 'ccr_geo_redirect');
add_action('admin_menu', 'ccr_add_admin_menu');
add_action('admin_init', 'ccr_settings_init');
