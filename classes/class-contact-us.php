<?php
/**
 * Class ContactUs
 *
 * @package    All-in-one-plugin
 * @subpackage ContactUs
 * @author     Prathamesh Kirpal
 * @license    GPL-2.0+
 * @since      1.0.0
 */

/**
 * Class responsible for contact us page building.
 */
class Contact_Us {
	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'maybe_create_contact_page' ) );
	}

	/**
	 * Create Contact Us page if not already exists.
	 *
	 * @return void
	 */
	public function maybe_create_contact_page() {
		$page_slug  = 'contact-us';
		$page_title = 'Contact Us';

		$existing_page = get_page_by_path( $page_slug ); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.get_page_by_path_get_page_by_path

		if ( ! $existing_page ) {
			$page_content = $this->get_contact_form_html();

			$post_data = array(
				'post_title'   => $page_title,
				'post_name'    => $page_slug,
				'post_status'  => 'publish',
				'post_type'    => 'page',
				'post_content' => $page_content,
			);

			wp_insert_post( $post_data );
		}
	}

	/**
	 * Returns the contact form HTML with inline CSS and dummy submission message.
	 *
	 * @return string
	 */
	private function get_contact_form_html() {
		ob_start();
		?>
		<div style="max-width:600px;margin:auto;padding:20px;border:1px solid #ccc;border-radius:6px;background:#f9f9f9;">
			<h2 style="text-align:center;">Contact Us</h2>
			<form onsubmit="event.preventDefault(); document.getElementById('contact-success-msg').style.display='block';">
				<label style="display:block;margin-bottom:10px;">
					Name:<br>
					<input type="text" name="name" style="width:100%;padding:8px;border:1px solid #ccc;border-radius:4px;">
				</label>
				<label style="display:block;margin-bottom:10px;">
					Email:<br>
					<input type="email" name="email" style="width:100%;padding:8px;border:1px solid #ccc;border-radius:4px;">
				</label>
				<label style="display:block;margin-bottom:10px;">
					Message:<br>
					<textarea name="message" rows="5" style="width:100%;padding:8px;border:1px solid #ccc;border-radius:4px;"></textarea>
				</label>
				<button type="submit" style="background:#0073aa;color:white;padding:10px 20px;border:none;border-radius:4px;cursor:pointer;">Submit</button>
				<p id="contact-success-msg" style="display:none;margin-top:15px;color:green;">✅ Submitted successfully!</p>
			</form>
		</div>
		<?php
		return ob_get_clean();
	}
}
