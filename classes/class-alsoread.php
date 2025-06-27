<?php
/**
 * Class AlsoRead
 * @package All-in-one-plugin
 * @subpackage SEO_Metaboxes
 * @author Prathamesh Kirpal
 * @license GPL-2.0+
 * @since 1.0.0
 */

class AlsoRead {

	public function __construct() {
		add_action( 'save_post', [ $this, 'inject_also_read_on_publish' ], 10, 2 );

	}

	public function inject_also_read_on_publish( $post_id, $post ) {
	if (
		defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ||
		wp_is_post_revision( $post_id ) ||
		// $post->post_status !== 'publish' ||
		$post->post_type !== 'post'
	) {
		return;
	}
	// Check if this is the first time it's being published
	$published_flag = get_post_meta( $post_id, '_also_read_injected', true );
	if ( $published_flag ) {
		return;
	}
	error_log('this is it');
	$categories = get_the_category( $post_id );
	if ( empty( $categories ) ) {
		return;
	}

	$cat_ids = wp_list_pluck( $categories, 'term_id' );
	$args    = array(
		'post_type'           => 'post',
		'posts_per_page'      => 5,
		'post__not_in'        => array( $post_id ),
		'orderby'             => 'date',
		'order'               => 'DESC',
		'date_query'          => array(
			array(
				'before'    => get_the_date( 'Y-m-d H:i:s', $post_id ),
				'inclusive' => false,
			),
		),
		'category__in'        => $cat_ids,
		'post_status'         => 'publish',
		'ignore_sticky_posts' => true,
	);

	$query = new WP_Query( $args );

	if ( $query->have_posts() ) {
		$also_read_html  = "<hr style='border:1px solid #ddd;margin-top:30px;'>";
		$also_read_html .= "<div style='padding:15px;border:1px solid #ccc;border-radius:5px;margin-top:20px;'>";
		$also_read_html .= "<h4 style='margin-bottom:10px;font-size:18px;'>और पढ़ें</h4>";
		$also_read_html .= "<ul style='list-style-type:disc;padding-left:20px;'>";

		while ( $query->have_posts() ) {
			$query->the_post();
			$also_read_html .= '<li style="margin-bottom:5px;"><a href="' . esc_url( get_permalink() ) . '" style="text-decoration:none;color:#0073aa;">' . esc_html( get_the_title() ) . '</a></li>';
		}

		$also_read_html .= "</ul></div>";

		wp_reset_postdata();

		// Append to post content and save
		$updated_content = $post->post_content . "\n\n" . $also_read_html;
		remove_action( 'save_post', [ $this, 'inject_also_read_on_publish' ], 10 );
		wp_update_post( [
			'ID'           => $post_id,
			'post_content' => $updated_content,
		] );
		add_action( 'save_post', [ $this, 'inject_also_read_on_publish' ], 10, 2 );

		// Mark as injected so it doesn't run again
		update_post_meta( $post_id, '_also_read_injected', 1 );
	}
}

}
