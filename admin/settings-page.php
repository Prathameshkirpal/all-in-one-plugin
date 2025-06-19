	<?php
	add_action('admin_enqueue_scripts', function() {
	wp_enqueue_script('maiop-admin-js', plugin_dir_url(__FILE__) . 'settings-toggle.js', ['jquery'], null, true);
	wp_localize_script('maiop-admin-js', 'maiopAjax', [
		'ajax_url' => admin_url('admin-ajax.php'),
		'nonce'    => wp_create_nonce('maiop_toggle_nonce'),
	]);
	});

	add_action('wp_ajax_maiop_toggle_plugin', function() {
	check_ajax_referer('maiop_toggle_nonce', 'nonce');

	$plugin_slug = sanitize_text_field($_POST['plugin']);
	$is_enabled  = $_POST['enabled'] === 'true';

	$enabled_plugins = get_option('maiop_enabled_plugins', []);
	if ($is_enabled) {
		if (!in_array($plugin_slug, $enabled_plugins)) {
			$enabled_plugins[] = $plugin_slug;
		}
	} else {
		$enabled_plugins = array_diff($enabled_plugins, [$plugin_slug]);
	}

	update_option('maiop_enabled_plugins', array_values($enabled_plugins));
	wp_send_json_success(['status' => 'updated']);
	});

	function maiop_render_settings_page() {
	$enabled = get_option('maiop_enabled_plugins', []);
	$plugin_list = [
		'seo-metaboxes' => 'SEO Metaboxes',
		'alsoread'      => 'Also Read',
		'schema'        => 'Schema Markup',
		'contact-us'    => 'Contact Us Form',
		'sitemap'       => 'Sitemap Generator',
	];
	?>
	<style>
		.maiop-container {
			display: grid;
			grid-template-columns: 1fr 1fr;
			gap: 20px;
			max-width: 1000px;
		}
		.maiop-box {
			background: #fff;
			padding: 20px;
			border-left: 5px solid #007cba;
			border-radius: 10px;
			box-shadow: 0 2px 8px rgba(0,0,0,0.06);
		}
		.maiop-box h2 {
			margin-top: 0;
			font-size: 18px;
			color: #222;
			margin-bottom: 15px;
		}
		.maiop-toggle {
			display: flex;
			align-items: center;
			justify-content: space-between;
		}
		.maiop-toggle-switch {
			position: relative;
			width: 50px;
			height: 26px;
		}
		.maiop-toggle-switch input {
			opacity: 0;
			width: 0;
			height: 0;
		}
		.maiop-slider {
			position: absolute;
			cursor: pointer;
			top: 0;
			left: 0;
			right: 0;
			bottom: 0;
			background-color: #ccc;
			transition: 0.4s;
			border-radius: 34px;
		}
		.maiop-slider:before {
			position: absolute;
			content: "";
			height: 18px;
			width: 18px;
			left: 4px;
			bottom: 4px;
			background-color: white;
			transition: 0.4s;
			border-radius: 50%;
		}
		.maiop-toggle-switch input:checked + .maiop-slider {
			background-color: #00a0d2;
		}
		.maiop-toggle-switch input:checked + .maiop-slider:before {
			transform: translateX(24px);
		}
	</style>

	<div class="wrap">
		<h1>All-in-One Plugin Modules</h1>
		<div class="maiop-container">
			<?php foreach ($plugin_list as $slug => $label): ?>
				<div class="maiop-box">
					<h2><?= esc_html($label) ?></h2>
					<div class="maiop-toggle">
						<span>Status:</span>
						<label class="maiop-toggle-switch">
							<input type="checkbox" value="<?= esc_attr($slug) ?>" <?= in_array($slug, $enabled) ? 'checked' : '' ?>>
							<span class="maiop-slider"></span>
						</label>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
	<?php
}

