<?php
/**
 * Breadcrumb Subplugin
 *
 * @package All-in-one-plugin
 * @subpackage SEO_Metaboxes
 * @author Prathamesh Kirpal
 * @license GPL-2.0+
 * @since 1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Breadcrumb
 */
class Breadcrumb {
		/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_styles' ] );
		add_action( 'wp_head', [ $this, 'output_breadcrumb' ] );
	}

	/**
	 * Enqueue CSS for breadcrumbs on applicable pages.
	 *
	 * @return void
	 */
	public function enqueue_styles() {
		if ( ! is_front_page() && ! is_home() ) {
			wp_enqueue_style(
				'breadcrumb-css',
				plugins_url( 'assets/bread-crum.css', __FILE__ ),
				array(),
				null
			);
		}
	}

	/**
	 * Outputs breadcrumb HTML in head tag for supported pages.
	 *
	 * @return void
	 */
	public function output_breadcrumb() {
		if ( is_front_page() || is_home() ) {
			return;
		}

		echo '<div class="breadcrumb-trail">';
		echo '<a href="' . esc_url( home_url( '/' ) ) . '" rel="nofollow">Home</a>';

		if ( is_category() || is_single() ) {
			echo '&nbsp;&nbsp;&#187;&nbsp;&nbsp;';
			the_category( ' &bull; ' );

			if ( is_single() ) {
				global $post;

				$seo_slug_raw = get_post_meta( $post->ID, '_seo_slug_url', true );
				$seo_slug     = str_replace( '-', ' ', sanitize_text_field( $seo_slug_raw ) );

				if ( ! empty( $seo_slug ) ) {
					echo '&nbsp;&nbsp;&#187;&nbsp;&nbsp;' . esc_html( $seo_slug );
				}
			}
		} elseif ( is_page() ) {
			echo '&nbsp;&nbsp;&#187;&nbsp;&nbsp;' . esc_html( get_the_title() );
		} elseif ( is_search() ) {
			echo '&nbsp;&nbsp;&#187;&nbsp;&nbsp;Search Results for: ';
			echo '<em>' . esc_html( get_search_query() ) . '</em>';
		} elseif ( is_tag() ) {
			echo '&nbsp;&nbsp;&#187;&nbsp;&nbsp;<a href="' . esc_url( home_url( '/topics' ) ) . '">Topics</a> &#187;&nbsp;';
			echo esc_html( single_tag_title( '', false ) );
		}

		echo '</div>';
	}

}