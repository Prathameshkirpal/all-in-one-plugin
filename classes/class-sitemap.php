<?php
/**
 * Sitemap generator class integrated into All-in-One Plugin.
 *
 * @package My_All_In_One_Plugin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Sitemap for all the functionality related to sitemap .
 *
 * @param string $option_key key to check the settings for sub sitemap.
 */
class Sitemap {
	/**
	 * Option key used to store sitemap settings.
	 *
	 * @var string
	 */
	private $option_key = 'maiop_sitemap_settings';

	/**
	 * Construct function setup all hooks.
	 *
	 * @return void.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'maybe_register_sitemaps' ) );
		add_action( 'init', array( $this, 'create_cron_schedules' ) );
		add_action( 'init', array( $this, 'register_sitemap_rewrite_rules' ) );
		add_filter( 'query_vars', array( $this, 'register_query_var' ) );
		add_action( 'template_redirect', array( $this, 'handle_sitemap_template' ) );
		add_action( 'create-category-sitemap-cron', array( $this, 'create_category_sitemap' ) );
		add_action( 'create-tag-sitemap-cron', array( $this, 'create_tag_sitemap' ) );
		add_action( 'publish_post', array( $this, 'create_posts_sitemap' ), 10, 2 );
	}

	/**
	 * Callback function to render sitemap sub-plugins.
	 *
	 * @return void.
	 */
	public function render_plugin_settings_ui() {
		$options = get_option( $this->option_key, array() );
		$types   = array( 'post', 'category', 'tag' );

		foreach ( $types as $type ) {
			$enabled = ! empty( $options[ "maiop_sitemap_enable_{$type}" ] );
			$name    = ! empty( $options[ "maiop_sitemap_name_{$type}" ] ) ? esc_attr( $options[ "maiop_sitemap_name_{$type}" ] ) : '';
			?>
			<div style="margin-bottom: 15px;">
				<strong><?php echo esc_html( ucfirst( $type ) ); ?> Sitemap:</strong><br>

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
					placeholder="<?php echo esc_attr( get_bloginfo( 'name' ) . "-{$type}-sitemap" ); ?>"
					value="<?php echo esc_attr( $name ); ?>"
					style="min-width: 250px;">
			</div>

			<?php
		}
	}

	/**
	 * Function to register sitemaps.
	 *
	 * @return void.
	 */
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

	/**
	 * Function to rewrite sitemap rules.
	 *
	 * @return void.
	 */
	public function register_sitemap_rewrite_rules() {
		$options       = get_option( $this->option_key, array() );
		$post_name     = ! empty( $options['maiop_sitemap_name_post'] ) ? sanitize_title_with_dashes( $options['maiop_sitemap_name_post'] ) : sanitize_title_with_dashes( get_bloginfo( 'name' ) ) . '-post-sitemap';
		$category_name = ! empty( $options['maiop_sitemap_name_category'] ) ? sanitize_title_with_dashes( $options['maiop_sitemap_name_category'] ) : sanitize_title_with_dashes( get_bloginfo( 'name' ) ) . '-category-sitemap';
		$tag_name      = ! empty( $options['maiop_sitemap_name_tag'] ) ? sanitize_title_with_dashes( $options['maiop_sitemap_name_tag'] ) : sanitize_title_with_dashes( get_bloginfo( 'name' ) ) . '-tag-sitemap';

		add_rewrite_rule(
			"^{$post_name}\.xml$",
			'index.php?dynamic_sitemap=post',
			'top'
		);

		add_rewrite_rule(
			"^{$category_name}\.xml$",
			'index.php?dynamic_sitemap=category',
			'top'
		);

		add_rewrite_rule(
			"^{$tag_name}\.xml$",
			'index.php?dynamic_sitemap=tag',
			'top'
		);
	}


	/**
	 * Public function to create cron schedules.
	 */
	public function create_cron_schedules() {
		if ( ! wp_next_scheduled( 'create-category-sitemap-cron' ) ) {
			wp_schedule_event( time(), 'daily', 'create-category-sitemap-cron' );
		}
		if ( ! wp_next_scheduled( 'create-tag-sitemap-cron' ) ) {
			wp_schedule_event( time(), 'daily', 'create-tag-sitemap-cron' );
		}

	}

	/**
	 * Function to register query vars for dynamic sitemap.
	 *
	 * @param array $vars Query variables.
	 * @return array Modified query variables.
	 */
	public function register_query_var( $vars ) {
		$vars[] = 'dynamic_sitemap';
		return $vars;
	}

	/**
	 * Public function to handle sitemap template.
	 *
	 * @return void.
	 */
	public function handle_sitemap_template() {
		$sitemap_type = get_query_var( 'dynamic_sitemap' );

		if ( 'post' === $sitemap_type ) {
			$this->view_post_sitemap();
			exit;
		} elseif ( 'category' === $sitemap_type ) {
			$this->view_category_sitemap();
			exit;
		} elseif ( 'tag' === $sitemap_type ) {
			$this->view_tag_sitemap();
			exit;
		}
	}

	/**
	 * Function to create and save the category sitemap in the sitemap posttype
	 */
	public function create_category_sitemap() {
		$options = get_option( $this->option_key, array() );
		if ( empty( $options['maiop_sitemap_enable_category'] ) && ! 1 === $options['maiop_sitemap_enable_category'] ) {
			return;
		}

		$category_sitemap_url = 'category-sitemap';
		$category_post        = get_page_by_path( $category_sitemap_url, OBJECT, 'msm_sitemap' );
		if ( ! is_object( $category_post ) ) {
			$post_data = array(
				'post_title'  => 'Category Sitemap',
				'post_name'   => $category_sitemap_url,
				'post_status' => 'publish',
				'post_type'   => 'msm_sitemap',
			);
			$post_id   = wp_insert_post( $post_data );
			if ( ! is_wp_error( $post_id ) ) {
				$category_post = get_post( $post_id );
			}
		}
		if ( is_object( $category_post ) && property_exists( $category_post, 'ID' ) ) {
			$category_site_map_value = '<urlset xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
			$categories              = get_categories(
				array(
					'hide_empty' => true,
				)
			);
			if ( ! empty( $categories ) ) {
				foreach ( $categories as $category ) {
					if ( isset( $category->term_id ) ) {
						$category_url = get_category_link( $category->term_id );
						if ( $category_url ) {
							$category_site_map_value .= '<url>';
							$category_site_map_value .= '<loc>' . esc_url( $category_url ) . '</loc>';
							$category_site_map_value .= '<lastmod>' . date( 'Y-m-d' ) . '</lastmod>';
							$category_site_map_value .= '<changefreq>daily</changefreq>';
							$category_site_map_value .= '<priority>0.9</priority>';
							$category_site_map_value .= '</url>';
						}
					}
				}
			}
			$category_site_map_value .= '</urlset>';
			update_post_meta( $category_post->ID, 'category-site-map-key', $category_site_map_value );
			$existing_transient = get_transient( 'category-site-map-key-link' );
			if ( false === $existing_transient ) {
				set_transient( 'category-site-map-key-link', $category_site_map_value, DAY_IN_SECONDS );
			}
		}
	}

	/**
	 * Public function to  create tag sitemap in the sitemap post type.
	 *
	 * @return void
	 */
	public function create_tag_sitemap() {
		$tag_sitemap_url = 'tag-sitemap';
		$tag_post        = get_page_by_path( $tag_sitemap_url, OBJECT, 'msm_sitemap' );
		if ( ! is_object( $tag_post ) ) {
			$post_data = array(
				'post_title'  => 'Tag Sitemap',
				'post_name'   => $tag_sitemap_url,
				'post_status' => 'publish',
				'post_type'   => 'msm_sitemap',
			);
			$post_id   = wp_insert_post( $post_data );
			if ( ! is_wp_error( $post_id ) ) {
				$tag_post = get_post( $post_id );
			}
		}
		if ( is_object( $tag_post ) && property_exists( $tag_post, 'ID' ) ) {
			$tag_site_map_value = '<urlset xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
			$tags               = get_tags(
				array(
					'hide_empty' => true,
				)
			);
			if ( ! empty( $tags ) ) {
				foreach ( $tags as $tag ) {
					if ( isset( $tag->term_id ) ) {
						$tag_url = get_tag_link( $tag->term_id );
						if ( $tag_url ) {
							$tag_site_map_value .= '<url>';
							$tag_site_map_value .= '<loc>' . esc_url( $tag_url ) . '</loc>';
							$tag_site_map_value .= '<lastmod>' . date( 'Y-m-d' ) . '</lastmod>';
							$tag_site_map_value .= '<changefreq>daily</changefreq>';
							$tag_site_map_value .= '<priority>0.9</priority>';
							$tag_site_map_value .= '</url>';
						}
					}
				}
			}
			$tag_site_map_value .= '</urlset>';
			update_post_meta( $tag_post->ID, 'tag-site-map-key', $tag_site_map_value );
			$existing_transient = get_transient( 'tag-site-map-key-link' );
			if ( false === $existing_transient ) {
				set_transient( 'tag-site-map-key-link', $tag_site_map_value, DAY_IN_SECONDS );
			}
		}
	}

	/**
	 * Create normal post sitemap.
	 *
	 * @return void.
	 */
	function create_posts_sitemap() {
		$posts_sitemap_url = 'posts-sitemap';
		$posts_post        = get_page_by_path( $posts_sitemap_url, OBJECT, 'msm_sitemap' );
		if ( ! is_object( $posts_post ) ) {
			$post_data = array(
				'post_title'  => 'Post Sitemap',
				'post_name'   => $posts_sitemap_url,
				'post_status' => 'publish',
				'post_type'   => 'msm_sitemap',
			);
			$post_id   = wp_insert_post( $post_data );
			if ( ! is_wp_error( $post_id ) ) {
				$posts_post = get_post( $post_id );
			}
		}

		if ( is_object( $posts_post ) && property_exists( $posts_post, 'ID' ) ) {
			$posts_site_map_value = '<urlset xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">';

			$three_days_ago = date( 'Y-m-d', strtotime( '-3 days' ) );

			$query_args = array(
				'post_type'      => 'post',
				'post_status'    => 'publish',
				'orderby'        => 'modified',
				'order'          => 'DESC',
				'posts_per_page' => -1,
			);

			$query = new WP_Query( $query_args );

			if ( $query->have_posts() ) {
				while ( $query->have_posts() ) {
					$query->the_post();

					$post_url     = get_permalink();
					$post_title   = get_the_title();
					$lastmod_date = get_the_modified_date( DATE_W3C );

					$posts_site_map_value .= '<url>';
					$posts_site_map_value .= '<loc>' . esc_url( $post_url ) . '</loc>';

					// Add featured image if available.
					if ( has_post_thumbnail() ) {
						$image_url             = wp_get_attachment_url( get_post_thumbnail_id() );
						$posts_site_map_value .= '<image:image>';
						$posts_site_map_value .= '<image:loc>' . esc_url( $image_url ) . '</image:loc>';
						$posts_site_map_value .= '</image:image>';
					}

					// Add last modified date.
					$posts_site_map_value .= '<lastmod>' . esc_html( $lastmod_date ) . '</lastmod>';
					$posts_site_map_value .= '</url>';
				}
				wp_reset_postdata();
			}

			$posts_site_map_value .= '</urlset>';

			update_post_meta( $posts_post->ID, 'posts-site-map-key', $posts_site_map_value );

			if ( false === get_transient( 'posts-site-map-key-link' ) ) {
				set_transient( 'posts-site-map-key-link', $posts_site_map_value, HOUR_IN_SECONDS );
			}
		}
	}

	/**
	 * Function to view category sitemap.
	 *
	 * @return void.
	 */
	public function view_category_sitemap() {
		$category_sitemap_url = 'category-sitemap';
		$category_post        = get_page_by_path( $category_sitemap_url, '', 'msm_sitemap' );
		if ( is_object( $category_post ) && property_exists( $category_post, 'ID' ) ) {
			$category_sitemap_url = get_post_meta( $category_post->ID, 'category-site-map-key', true );
		}
		header( 'Content-type: application/xml; charset=UTF-8' );
		if ( isset( $category_sitemap_url ) && ! empty( $category_sitemap_url ) ) {
			echo $category_sitemap_url;
			exit;
		}
	}

	/**
	 * Function to view tag sitemap.
	 *
	 * @return void
	 */
	public function view_tag_sitemap() {
		$tag_sitemap_url = 'tag-sitemap';
		$tag_post        = get_page_by_path( $tag_sitemap_url, '', 'msm_sitemap' );
		if ( is_object( $tag_post ) && property_exists( $tag_post, 'ID' ) ) {
			$tag_sitemap_meta_xml = get_post_meta( $tag_post->ID, 'tag-site-map-key', true );
		}
		header( 'Content-type: application/xml; charset=UTF-8' );
		if ( isset( $tag_sitemap_meta_xml ) && ! empty( $tag_sitemap_meta_xml ) ) {
			echo $tag_sitemap_meta_xml;
			exit;
		}
	}

	/**
	 * Function to view posts sitemap.
	 *
	 * @return void.
	 */
	public function view_post_sitemap() {
		$posts_sitemap_url = 'posts-sitemap';
		$posts_post        = get_page_by_path( $posts_sitemap_url, '', 'msm_sitemap' );
		if ( is_object( $posts_post ) && property_exists( $posts_post, 'ID' ) ) {
			$posts_sitemap_meta_xml = get_post_meta( $posts_post->ID, 'posts-site-map-key', true );
		}
		header( 'Content-type: application/xml; charset=UTF-8' );
		if ( isset( $posts_sitemap_meta_xml ) && ! empty( $posts_sitemap_meta_xml ) ) {
			echo $posts_sitemap_meta_xml;
			exit;
		}
	}

}
