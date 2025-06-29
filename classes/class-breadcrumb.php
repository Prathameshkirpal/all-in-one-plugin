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
		$this->init_output_hook();
	}

	/**
	 * Hook to buffer and inject breadcrumb HTML after header.
	 */
	public function init_output_hook() {
		add_action( 'template_redirect', array( $this, 'start_output_buffer' ) );
	}

	/**
	 * Start buffering template output.
	 */
	public function start_output_buffer() {
		ob_start( array( $this, 'inject_breadcrumb_html' ) );
	}

	/**
	 * Injects breadcrumb HTML after the opening header tag.
	 *
	 * @param string $content Full HTML output.
	 * @return string Modified output.
	 */
	public function inject_breadcrumb_html( $content ) {
		if ( is_front_page() || is_home() ) {
			return $content;
		}

		$breadcrumb  = '<div class="breadcrumb-wrapper" style="padding:10px 20px;font-size:14px;">';
		$breadcrumb .= '<a href="' . esc_url( home_url() ) . '" rel="nofollow">Home</a>';

		if ( is_category() || is_single() ) {
			$breadcrumb .= '&nbsp;&nbsp;&#187;&nbsp;&nbsp;';
			$breadcrumb .= get_the_category_list( ' &bull; ' );

			if ( is_single() ) {
				global $post;
				$meta     = get_post_meta( $post->ID );
				$seo_slug = isset( $meta['_seo_slug_url'][0] ) ? $meta['_seo_slug_url'][0] : '';
				$seo_slug = str_replace( '-', ' ', $seo_slug );

				if ( ! empty( $seo_slug ) ) {
					$breadcrumb .= ' &nbsp;&nbsp;&#187;&nbsp;&nbsp; ';
					$breadcrumb .= esc_html( $seo_slug );
				}
			}
		} elseif ( is_page() ) {
			$breadcrumb .= '&nbsp;&nbsp;&#187;&nbsp;&nbsp;';
			$breadcrumb .= get_the_title();
		} elseif ( is_search() ) {
			$breadcrumb .= '&nbsp;&nbsp;&#187;&nbsp;&nbsp;Search Results for "<em>' . esc_html( get_search_query() ) . '</em>"';
		} elseif ( is_tag() ) {
			$breadcrumb .= '&nbsp;&nbsp;&#187;&nbsp;&nbsp;<a href="' . esc_url( home_url( '/topics' ) ) . '">Topics</a> &#187;&nbsp;';
			$breadcrumb .= esc_html( single_tag_title( '', false ) );
		}

		$breadcrumb .= '</div>';

		// Inject breadcrumb after <header> or </header> or before <main>.
		$content = preg_replace( '/(<\/header>)/i', $breadcrumb . '$1', $content, 1 );

		return $content;
	}

}
