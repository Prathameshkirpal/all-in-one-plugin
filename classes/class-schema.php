<?php
/**
 * Schema Sub-plugin Class
 *
 * Handles output of structured data schemas (Article, Website, Organization, Breadcrumb)
 * Breadcrumb schema depends on the Breadcrumb plugin being enabled
 *
 * @package All-in-one-plugin
 * @subpackage Schema
 * @author Prathamesh Kirpal
 * @license GPL-2.0+
 * @since 1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Schema generator class.
 */
class Schema {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'wp_head', array( $this, 'render_schema_markup' ), 5 );
	}

	/**
	 * Conditionally outputs schema JSON-LD to the head.
	 *
	 * @return void
	 */
	public function render_schema_markup() {
		global $post;
		global $enabled_plugins;
		$site_name       = get_bloginfo( 'name' );
		$site_url        = home_url( '/' );
		$logo_url        = get_option( 'home_page_logo_url',true );

		// ✅ Website schema (common for both homepage and posts).
		echo '<script type="application/ld+json">' . wp_json_encode(
			array(
				'@context'    => 'https://schema.org',
				'@type'       => 'WebSite',
				'name'        => $site_name,
				'url'         => $site_url,
				'description' => get_bloginfo( 'description' ),
			),
			JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE 
		) . '</script>';

		// Organization schema
		echo '<script type="application/ld+json">' . wp_json_encode( [
			'@context' => 'https://schema.org',
			'@type'    => 'Organization',
			'name'     => $site_name,
			'url'      => $site_url,
			'logo'     => [
				'@type' => 'ImageObject',
				'url'   => $logo_url,
			],
		], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>';

		// ✅ Homepage breadcrumb (if enabled)
		if ( is_front_page() || is_home() ) {
			if ( in_array( 'breadcrumb', $enabled_plugins, true ) ) {
				echo '<script type="application/ld+json">' . wp_json_encode(
					array(
						'@context'        => 'https://schema.org',
						'@type'           => 'BreadcrumbList',
						'itemListElement' => array(
							array(
								'@type'    => 'ListItem',
								'position' => 1,
								'name'     => 'Home',
								'item'     => $site_url,
							),
						),
					),
					JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE 
				) . '</script>';
			}
			return; // 🚫 No further schema needed on homepage
		}

		// ✅ Skip non-post pages (e.g., archive, search, etc.)
		if ( ! is_singular( 'post' ) ) {
			return;
		}

		// ✅ For single posts: render article and breadcrumb
		$post_url     = get_permalink( $post );
		$post_title   = get_the_title( $post );
		$post_desc    = get_the_excerpt( $post );
		$post_date    = get_the_date( 'c', $post );
		$author_name  = get_the_author_meta( 'display_name', $post->post_author );
		$featured_img = get_the_post_thumbnail_url( $post, 'full' );
		$seo_title    = get_post_meta( $post->ID, '_seo_title', true );
		$post_content = wp_strip_all_tags( apply_filters( 'the_content', $post->post_content ) );

		// ✅ Article schema
		echo '<script type="application/ld+json">' . wp_json_encode(
			array(
				'@context'         => 'https://schema.org',
				'@type'            => 'Article',
				'headline'         => $post_title,
				'description'      => $post_desc,
				'author'           => array(
					'@type' => 'Person',
					'name'  => $author_name,
				),
				'datePublished'    => $post_date,
				'image'            => $featured_img,
				'mainEntityOfPage' => array(
					'@type' => 'WebPage',
					'@id'   => $post_url,
				),
				'publisher'        => array(
					'@type' => 'Organization',
					'name'  => $site_name,
					'logo'  => array(
						'@type' => 'ImageObject',
						'url'   => $logo_url,
					),
				),
				'articleBody' => $post_content,
			),
			JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE 
		) . '</script>';

		// ✅ Breadcrumb schema for post (if enabled)
		if ( in_array( 'breadcrumb', $enabled_plugins, true ) ) {
			echo '<script type="application/ld+json">' . wp_json_encode(
				array(
					'@context'        => 'https://schema.org',
					'@type'           => 'BreadcrumbList',
					'itemListElement' => array(
						array(
							'@type'    => 'ListItem',
							'position' => 1,
							'name'     => 'Home',
							'item'     => $site_url,
						),
						array(
							'@type'    => 'ListItem',
							'position' => 2,
							'name'     => $seo_title ? $seo_title : $post_title,
							'item'     => $post_url,
						),
					),
				),
				JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE 
			) . '</script>';
		} else {
			echo '<!-- Breadcrumb schema not rendered: Breadcrumb subplugin not active -->';
		}
	}
}
