<?php
/**
 * Class AlsoRead
 *
 * @package    All-in-one-plugin
 * @subpackage AlsoRead
 * @author     Prathamesh Kirpal
 * @license    GPL-2.0+
 * @since      1.0.0
 */

/**
 * Class also read, A sub-plugin class responsible for also read content.
 */
class AlsoRead {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'save_post', array( $this, 'inject_also_read_on_publish' ), 10, 2 );
	}

	/**
	 * Injects the "Also Read" HTML block at the end of the post content
	 * if it's the first time publishing the post.
	 *
	 * @param int     $post_id The post ID.
	 * @param WP_Post $post    The post object.
	 */
	public function inject_also_read_on_publish( $post_id, $post ) {
		if (
			( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ||
			wp_is_post_revision( $post_id ) ||
			'publish' !== $post->post_status ||
			'post' !== $post->post_type
		) {
			return;
		}

		// Avoid duplicate injections.
		$already_injected = get_post_meta( $post_id, '_also_read_injected', true );
		if ( $already_injected ) {
			return;
		}

		$categories = get_the_category( $post_id );
		if ( empty( $categories ) ) {
			return;
		}

		$cat_ids = wp_list_pluck( $categories, 'term_id' );

		$args = array(
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

		$related_posts = new WP_Query( $args );

		if ( $related_posts->have_posts() ) {
			$also_read_html  = "<hr style='border: 0; border-top: 1px solid #ccc; margin: 20px 0;'>";
			$also_read_html .= "<div style='padding: 15px; background-color: #f9f9f9; border: 1px solid #ddd; border-radius: 6px;'>";
			$also_read_html .= "<h4 style='margin-top: 0; font-size: 18px; color: #333;'>और पढ़ें</h4>";
			$also_read_html .= "<ul style='padding-left: 0; list-style: none; margin: 0;'>";

			while ( $related_posts->have_posts() ) {
				$related_posts->the_post();
				$also_read_html .= "<li style='margin-bottom: 10px; position: relative; padding-left: 20px;'>
					<span style='position: absolute; left: 0; color: #e63946;'>&#10148;</span>
					<a href='" . esc_url( get_permalink() ) . "'
					   style='color: #000; text-decoration: none; transition: color 0.3s;'
					   onmouseover=\"this.style.color='#e63946'\"
					   onmouseout=\"this.style.color='#000'\">
						" . esc_html( get_the_title() ) . '
					</a>
				</li>';
			}

			$also_read_html .= '</ul></div>';

			wp_reset_postdata();

			// Append to content and save post.
			$updated_content = $post->post_content . "\n\n" . $also_read_html;
			remove_action( 'save_post', array( $this, 'inject_also_read_on_publish' ), 10 );
			wp_update_post(
				array(
					'ID'           => $post_id,
					'post_content' => $updated_content,
				) 
			);
			add_action( 'save_post', array( $this, 'inject_also_read_on_publish' ), 10, 2 );

			update_post_meta( $post_id, '_also_read_injected', 1 );
		}
	}
}
