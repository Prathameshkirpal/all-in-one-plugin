<?php
/**
 * Sitemap generator class integrated into All-in-One Plugin.
 *
 * @package My_All_In_One_Plugin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Sitemap {

	private $option_key = 'maiop_sitemap_settings';

	public function __construct() {
		add_action( 'init', array( $this, 'maybe_register_sitemaps' ) );
		add_action( 'wp', array( $this, 'maybe_schedule_cron' ) );
		add_action( 'template_redirect', array( $this, 'output_sitemap' ) );
		add_action('admin_init', [$this, 'maybe_flush_rewrite']);
		add_action('init', [$this, 'register_sitemap_rewrite_rules']);
		add_filter('query_vars', [$this, 'register_query_var']);
		add_action('template_redirect', [$this, 'render_dynamic_sitemap']);
	}

	public function render_plugin_settings_ui() {
		$options = get_option( $this->option_key, array() );
		$types   = array( 'post', 'category', 'tag' );

		foreach ( $types as $type ) {
			$enabled = ! empty( $options[ "maiop_sitemap_enable_{$type}" ] );
			$name    = ! empty( $options[ "maiop_sitemap_name_{$type}" ] ) ? esc_attr( $options[ "maiop_sitemap_name_{$type}" ] ) : '';
			?>
			<div style="margin-bottom: 15px;">
				<strong><?= ucfirst( $type ); ?> Sitemap:</strong><br>

				<label class="maiop-toggle-switch" style="margin-right: 10px;">
					<input type="checkbox"
						class="maiop-sitemap-toggle"
						data-type="<?= esc_attr( $type ); ?>"
						<?= $enabled ? 'checked' : ''; ?>>
					<span class="maiop-slider"></span>
				</label>

				<input type="text"
					class="maiop-sitemap-filename"
					data-type="<?= esc_attr( $type ); ?>"
					placeholder="<?= get_bloginfo( 'name' ) . "-{$type}-sitemap" ?>"
					value="<?= $name; ?>"
					style="min-width: 250px;">
			</div>

			<?php
		}
	}


	public function maybe_register_sitemaps() {
		$options = get_option( $this->option_key, array() );
		$types   = array( 'post', 'category', 'tag' );

		foreach ( $types as $type ) {
			if ( ! empty( $options[ "maiop_sitemap_enable_{$type}" ] ) ) {
				$slug = ! empty( $options[ "maiop_sitemap_name_{$type}" ] ) ? $options[ "maiop_sitemap_name_{$type}" ] : get_bloginfo( 'name' ) . '-' . $type . '-sitemap';
				$slug = sanitize_title_with_dashes( $slug );
				add_rewrite_rule( '^' . $slug . '\.xml$', 'index.php?maiop_sitemap=' . $type, 'top' );
			}
		}
		add_rewrite_tag( '%maiop_sitemap%', '([^&]+)' );
	}

	public function output_sitemap() {
		$type = get_query_var( 'maiop_sitemap' );
		if ( ! $type ) {
			return;
		}

		header( 'Content-Type: application/xml; charset=utf-8' );
		echo '<?xml version="1.0" encoding="UTF-8"?>';
		echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

		if ( 'post' === $type ) {
			$posts = get_posts( array( 'numberposts' => -1 ) );
			foreach ( $posts as $post ) {
				echo '<url><loc>' . esc_url( get_permalink( $post ) ) . '</loc></url>';
			}
		} elseif ( 'category' === $type ) {
			$terms = get_terms( array( 'taxonomy' => 'category', 'hide_empty' => false ) );
			foreach ( $terms as $term ) {
				echo '<url><loc>' . esc_url( get_term_link( $term ) ) . '</loc></url>';
			}
		} elseif ( 'tag' === $type ) {
			$terms = get_terms( array( 'taxonomy' => 'post_tag', 'hide_empty' => false ) );
			foreach ( $terms as $term ) {
				echo '<url><loc>' . esc_url( get_term_link( $term ) ) . '</loc></url>';
			}
		}

		echo '</urlset>';
		exit;
	}

	public function maybe_schedule_cron() {
		$options = get_option( $this->option_key, array() );
		$enabled = ! empty( $options['maiop_sitemap_enable_post'] ) || ! empty( $options['maiop_sitemap_enable_category'] ) || ! empty( $options['maiop_sitemap_enable_tag'] );

		if ( $enabled && ! wp_next_scheduled( 'maiop_generate_sitemaps' ) ) {
			wp_schedule_event( time(), 'hourly', 'maiop_generate_sitemaps' );
		} elseif ( ! $enabled && wp_next_scheduled( 'maiop_generate_sitemaps' ) ) {
			wp_clear_scheduled_hook( 'maiop_generate_sitemaps' );
		}
	}

	public function register_query_var($vars) {
		$vars[] = 'dynamic_sitemap';
		return $vars;
	}

	public function register_sitemap_rewrite_rules() {
		$options = get_option($this->option_key, []);
		$name = !empty($options['maiop_sitemap_name_post']) ? sanitize_title_with_dashes($options['maiop_sitemap_name_post']) : 'post-sitemap';

		add_rewrite_rule(
			"^{$name}\.xml$",
			'index.php?dynamic_sitemap=post',
			'top'
		);
	}

	public function render_dynamic_sitemap() {
		$type = get_query_var('dynamic_sitemap');
		if ($type === 'post') {
			header('Content-Type: application/xml; charset=UTF-8');
			echo '<?xml version="1.0" encoding="UTF-8"?>';
			echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

			$posts = get_posts([
				'post_type'      => 'post',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
			]);

			foreach ($posts as $post) {
				$url = get_permalink($post);
				$mod = get_post_modified_time('c', true, $post);
				echo "<url><loc>{$url}</loc><lastmod>{$mod}</lastmod></url>";
			}

			echo '</urlset>';
			exit;
		}
	}

	public function maybe_flush_rewrite() {
	if (!get_option('maiop_sitemap_flushed')) {
		flush_rewrite_rules();
		update_option('maiop_sitemap_flushed', true);
	}
}

}
