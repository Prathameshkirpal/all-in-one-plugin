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

require_once MAIOP_DIR . 'admin/settings-page.php';
require_once MAIOP_DIR . 'includes/init-subplugins.php';

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
