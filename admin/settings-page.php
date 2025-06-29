<?php
/**
 * Class AlsoRead
 *
 * @package    All-in-one-plugin
 * @author     Prathamesh Kirpal
 * @license    GPL-2.0+
 * @since      1.0.0
 */

/** Enqueue script and css of admin setting page */
add_action(
	'admin_enqueue_scripts',
	function() {
		wp_enqueue_script( 'maiop-admin-js', plugin_dir_url( __FILE__ ) . 'settings-toggle.js', array( 'jquery' ), filemtime( plugin_dir_path( __FILE__ ) . 'settings-toggle.js' ), true );
		wp_enqueue_style( 'maiop-admin-css', plugin_dir_url( __FILE__ ) . 'settings-page.css', null, true );
		wp_localize_script(
			'maiop-admin-js',
			'maiopAjax',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'maiop_toggle_nonce' ),
			)
		);
	}
);

add_action(
	'wp_ajax_maiop_toggle_plugin',
	function() {
		check_ajax_referer( 'maiop_toggle_nonce', 'nonce' );
		if ( ! empty( $_POST['plugin'] && ! empty( $_POST['enabled'] ) ) ) {
			$plugin_slug     = sanitize_text_field( $_POST['plugin'] );
			$is_enabled      = 'true' === $_POST['enabled'];
			$enabled_plugins = get_option( 'maiop_enabled_plugins', array() );

			if ( $is_enabled ) {
				if ( ! in_array( $plugin_slug, $enabled_plugins ) ) {
					$enabled_plugins[] = $plugin_slug;
				}
			} else {
				$enabled_plugins = array_diff( $enabled_plugins, array( $plugin_slug ) );
			}
			update_option( 'maiop_enabled_plugins', array_values( $enabled_plugins ) );
			wp_send_json_success( array( 'status' => 'updated' ) );
		}
	}
);

/**
 *  Callback function to render sub-plugin list.
 *
 * @return void
 */
function maiop_render_settings_page() {
	$enabled     = get_option( 'maiop_enabled_plugins', array() );
	$plugin_list = array(
		'seo-metaboxes' => 'SEO Metaboxes',
		'alsoread'      => 'Also Read',
		'schema'        => 'Schema Markup',
		'contact-us'    => 'Contact Us Form',
		'sitemap'       => 'Sitemap Generator',
		'breadcrumb'    => 'Breadcrumb Trail',
	);
	?>

<div class="wrap">
	<h1>All-in-One Plugin Modules</h1>
	<div class="maiop-container">
	<?php foreach ( $plugin_list as $slug => $label ) : ?>
			<div class="maiop-box">
				<h2><?= esc_html( $label ) ?></h2>
				<div class="maiop-toggle">
					<div class="maiop-toggle">
					<span>Status :</span>
					<?php $is_on = in_array( $slug, $enabled ); ?>
					<span class="maiop-status-text" style="margin-left: 8px; font-weight: bold; color: <?= $is_on ? 'green' : 'red' ?>;">
						<?= $is_on ? 'Enabled' : 'Disabled' ?>
					</span>
				</div>
					<label class="maiop-toggle-switch">
						<input type="checkbox" value="<?= esc_attr( $slug ) ?>" <?= in_array( $slug, $enabled ) ? 'checked' : '' ?>>
						<span class="maiop-slider"></span>
					</label>
				</div>
				<?php
				if ( 'sitemap' === $slug && in_array( 'sitemap', $enabled ) && class_exists( 'Sitemap' ) ) {
					$sitemap = new Sitemap();
					$sitemap->render_plugin_settings_ui();
				}
				?>

			</div>
		<?php endforeach; ?>
	</div>
</div>
	<?php
}
add_action(
	'wp_ajax_maiop_save_sitemap_settings',
	function () {
		check_ajax_referer( 'maiop_toggle_nonce', 'nonce' );

		$fields = array(
			'maiop_sitemap_enable_post',
			'maiop_sitemap_enable_category',
			'maiop_sitemap_enable_tag',
			'maiop_sitemap_name_post',
			'maiop_sitemap_name_category',
			'maiop_sitemap_name_tag',
		);

		$new_options = array();
		foreach ( $fields as $field ) {
			if ( isset( $_POST[ $field ] ) ) {
				$new_options[ $field ] = sanitize_text_field( $_POST[ $field ] );
			}
		}

		update_option( 'maiop_sitemap_settings', $new_options );
		wp_send_json_success(
			array(
				'status' => 'saved',
				'data'   => $new_options,
			) 
		);
	}
);


