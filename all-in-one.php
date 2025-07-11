<?php
/**
 * Plugin Name: All in One
 * Version: 1.0
 * Description: Make the basic seo optimization
 * Author: Prathamesh Kirpal
 * Author URI: http://scribu.net
 *
 * @package All-in-one
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'MAIOP_DIR', plugin_dir_path( __FILE__ ) );
global $enabled_plugins;
$enabled_plugins = get_option( 'maiop_enabled_plugins', array() );
require_once MAIOP_DIR . 'admin/settings-page.php';
require_once MAIOP_DIR . 'includes/init-subplugins.php';
require_once MAIOP_DIR . 'classes/class-auto-scheduler.php';

// Registering Activation hook for class auto scheduler sub plugin.
register_activation_hook( __FILE__, array( 'Auto_Scheduler', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Auto_Scheduler', 'deactivate' ) );

// Adding settings menu page.
add_action( 'admin_menu', 'maiop_add_settings_menu' );

/**
 * Callback function for admin settings page.
 *
 * @return void.
 */
function maiop_add_settings_menu() {
	add_menu_page(
		'All-in-One Settings',
		'All-in-One',
		'manage_options',
		'maiop-settings',
		'maiop_render_settings_page'
	);
}
// Condition to register and deregister the hook of auto schedular.
if ( is_array( $enabled_plugins ) && in_array( 'auto-scheduler', $enabled_plugins, true ) ) {
	Auto_Scheduler::init();
} else if (is_array( $enabled_plugins ) && ! array_key_exists('auto-scheduler', $enabled_plugins ) ) {
	wp_clear_scheduled_hook( 'auto_scheduler_cron_hook' );
}
