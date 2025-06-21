<?php
/**
 * Initializes enabled sub-plugin classes based on saved options.
 *
 * @package My_All_In_One_Plugin
 */

add_action( 'plugins_loaded', 'maiop_initialize_enabled_plugins' );

/**
 * Loads and instantiates enabled sub-plugin classes.
 */
function maiop_initialize_enabled_plugins() {
	$enabled_plugins = get_option( 'maiop_enabled_plugins', array() );

	if ( ! is_array( $enabled_plugins ) ) {
		return;
	}

	foreach ( $enabled_plugins as $plugin_slug ) {
		$file_name  = 'class-' . sanitize_file_name( $plugin_slug ) . '.php';
		$class_path = trailingslashit( MAIOP_DIR . 'classes' ) . $file_name;

		if ( file_exists( $class_path ) ) {
			require_once $class_path;

			$class_name = str_replace( ' ', '_', ucwords( str_replace( '-', ' ', $plugin_slug ) ) );

			if ( class_exists( $class_name ) ) {
				new $class_name();
			}
		}
	}
}
