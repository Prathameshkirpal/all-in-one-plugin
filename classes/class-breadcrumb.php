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
        add_action( 'template_redirect', array( $this, 'inject_breadcrumb_html' ) );
    }

    /**
     * Injects breadcrumb HTML after the header and before the page title.
     */
    public function inject_breadcrumb_html() {
        // Don't show breadcrumb on front page or home page
        if ( is_front_page() || is_home() ) {
            return;
        }

        // Start the breadcrumb HTML
        $breadcrumb  = '<div class="breadcrumb-wrapper" style="padding:10px 20px;font-size:14px;">';
        $breadcrumb .= '<a href="' . esc_url( home_url() ) . '" rel="nofollow">Home</a>';

        // If we're on a category page or single post, generate the breadcrumb
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
        }
        // If we're on a page
        elseif ( is_page() ) {
            $breadcrumb .= '&nbsp;&nbsp;&#187;&nbsp;&nbsp;';
            $breadcrumb .= get_the_title();
        }
        // If we're on a search results page
        elseif ( is_search() ) {
            $breadcrumb .= '&nbsp;&nbsp;&#187;&nbsp;&nbsp;Search Results for "<em>' . esc_html( get_search_query() ) . '</em>"';
        }
        // If we're on a tag archive
        elseif ( is_tag() ) {
            $breadcrumb .= '&nbsp;&nbsp;&#187;&nbsp;&nbsp;<a href="' . esc_url( home_url( '/topics' ) ) . '">Topics</a> &#187;&nbsp;';
            $breadcrumb .= esc_html( single_tag_title( '', false ) );
        }

        // Close the breadcrumb wrapper
        $breadcrumb .= '</div>';

        // Output the breadcrumb HTML after the header and before the content
        add_filter( 'the_content', function( $content ) use ( $breadcrumb ) {
            // Only add breadcrumb before content for single post or page
            if ( is_single() || is_page() ) {
                return $breadcrumb . $content;
            }
            return $content;
        });
    }
}
