<?php
add_action('plugins_loaded', 'maiop_initialize_enabled_plugins');

	function maiop_initialize_enabled_plugins() {
	$enabled = get_option('maiop_enabled_plugins', []);

	foreach ($enabled as $plugin) {
		$class_file = MAIOP_DIR . 'classes/class-' . sanitize_file_name($plugin) . '.php';
		if (file_exists($class_file)) {
			require_once $class_file;
			$class_name = 'MAIOP_' . str_replace('-', '_', $plugin);

			if (class_exists($class_name)) {
				new $class_name();
			}
		}
	}
}
