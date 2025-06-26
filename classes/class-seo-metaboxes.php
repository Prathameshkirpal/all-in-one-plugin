<?php
/**
 * SEO Metaboxes Class
 *
 * Adds custom SEO fields to WordPress post edit screen, including
 * title, meta description, and custom slug, compatible with REST API.
 *
 * @package All-in-one-plugin
 * @subpackage SEO_Metaboxes
 * @author Prathamesh Kirpal
 * @license GPL-2.0+
 * @since 1.0.0
 */

defined( 'ABSPATH' ) || exit;
/**
 * Class SEO Metaboxes for all the functionality related to seo is in this class.
 */
class Seo_Metaboxes {
	/**
	 * FUnction to initialize all the functions as pr their hook .
	 *
	 * @return void.
	 */
	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'seo_meta_boxes' ) );
		add_action( 'save_post', array( $this, 'save_seo_meta_boxes' ) );
		add_action( 'admin_menu', array( $this, 'home_page_seo_meta_data_add_admin_page' ) );
		add_action( 'admin_init', array( $this, 'home_page_seo_meta_data_register_settings' ) );
		add_action( 'after_setup_theme', array( $this, 'my_plugin_remove_theme_support' ), 11 );
		add_action( 'wp_head', array( $this, 'seo_meta_changes' ), 1 );
		add_action( 'init', array( $this, 'register_custom_post_meta_fields' ) );
	}

	/**
	 * Function to add a meta box.
	 *
	 * @return void
	 */
	public function seo_meta_boxes() {
		add_meta_box(
			'seo_meta_box',
			'SEO Settings',
			array( $this, 'seo_meta_box_callback' ),
			array( 'post' ),
			'normal',
			'high'
		);
		add_meta_box(
			'seo_url_slug_metabox', // Unique ID for the meta box
			__('SEO URL Slug', 'seo_url_slug'), // Title
			array( $this, 'render_seo_url_slug_metabox' ), // Callback function to render the content
			array('post','webstories'), // Post type
			'normal', // Context: where to display (side, normal, etc.)
			'high' // Priority
		);
	}


	/**
	 * Callback function to add a meta box.
	 *
	 * @param WP_Post $post Post object.
	 *
	 * @return void
	 */
	public function seo_meta_box_callback( $post ) {
		// Getting vlaues if available.
		$seo_title                = get_post_meta( $post->ID, '_seo_title', true );
		$seo_meta_description     = get_post_meta( $post->ID, '_seo_meta_description', true );
		$seo_keywords             = get_post_meta( $post->ID, '_seo_keywords', true );
		$seo_google_news_keywords = get_post_meta( $post->ID, '_seo_google_news_keywords', true );
		?>

		<label for="seo_title">SEO Title (required):</label>
		<input type="text" id="seo_title" name="seo_title" value="<?php echo esc_attr( $seo_title ); ?>" required style="width:100%;"><br><br>

		<label for="seo_meta_description">SEO Meta Description (required):</label>
		<textarea id="seo_meta_description" name="seo_meta_description" required style="width:100%;"><?php echo esc_textarea( $seo_meta_description ); ?></textarea><br><br>

		<label for="seo_keywords">SEO Keywords (required):</label>
		<input type="text" id="seo_keywords" name="seo_keywords" value="<?php echo esc_attr( $seo_keywords ); ?>" required style="width:100%;"><br><br>

		<label for="seo_google_news_keywords">SEO Google News Keywords (optional):</label>
		<input type="text" id="seo_google_news_keywords" name="seo_google_news_keywords" value="<?php echo esc_attr( $seo_google_news_keywords ); ?>" style="width:100%;"><br><br>
		<?php
	}

	/** Callback function to render slug url metabox */
	function render_seo_url_slug_metabox( $post ) {
		$seo_slug_url = get_post_meta( $post->ID, '_seo_slug_url', true );
		?>
		<label for="seo_slug_url"><?php esc_html_e( 'SEO Slug:', 'seo_url_slug' ); ?></label>
		<input
			type="text"
			id="seo_slug_url"
			name="seo_slug_url"
			value="<?php echo esc_attr($seo_slug_url); ?>"required
			style="width: 100%;"
		/>
		<p id="slug-warning" style="color: red; display: none;">
			<?php esc_html_e( 'Please ensure the slug is in English and not empty.', 'seo_url_slug' ); ?>
		</p>
		<?php
	}

	/**
	 * Function to save meta values.
	 *
	 * @param int $post_id The ID of the post being saved.
	 * @return void
	 */
	public function save_seo_meta_boxes( $post_id ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		// Saving SEO Title.
		if ( isset( $_POST ['seo_title'] ) && ! empty( $_POST['seo_title'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			update_post_meta( $post_id, '_seo_title', sanitize_text_field( $_POST['seo_title'] ) );// phpcs:ignore WordPress.Security.NonceVerification.Missing
		}

		// Saving SEO Meta Description.
		if ( isset( $_POST['seo_meta_description'] ) && ! empty( $_POST['seo_meta_description'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			update_post_meta( $post_id, '_seo_meta_description', sanitize_textarea_field( $_POST['seo_meta_description'] ) );// phpcs:ignore WordPress.Security.NonceVerification.Missing
		}

		// Saving SEO Keywords.
		if ( isset( $_POST['seo_keywords'] ) && ! empty( $_POST['seo_keywords'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			update_post_meta( $post_id, '_seo_keywords', sanitize_text_field( $_POST['seo_keywords'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		}

		// Saveing SEO Google News Keywords (optional).
		if ( isset( $_POST['seo_google_news_keywords'] ) && ! empty( $_POST['seo_google_news_keywords'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			update_post_meta( $post_id, '_seo_google_news_keywords', sanitize_text_field( $_POST['seo_google_news_keywords'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		}

		//Seo slug.
		if ( array_key_exists( 'seo_slug_url', $_POST ) ) {
			update_post_meta( $post_id, '_seo_slug_url', sanitize_text_field( $_POST['seo_slug_url'] ) );
		}
	}


	/**
	 * Function to add a settings page for home page.
	 *
	 * @return void.
	 */
	public function home_page_seo_meta_data_add_admin_page() {
		add_menu_page(
			'Homepage Meta Data Settings',   // Page title.
			'Meta Data Settings',            // Menu title.
			'manage_options',                // Capability required to access the page.
			'home-page-seo-plugin-meta-settings',       // Menu slug.
			array( $this, 'home_page_seo_meta_data_settings_page' ),  // Callback function to display the page.
			'dashicons-admin-generic',       // Icon.
			80                               // Position in the menu.
		);
	}

	/**
	 * Callback function of the settins homepage.
	 *
	 * @return void.
	 */
	public function home_page_seo_meta_data_settings_page() {
		?>
		<div class="wrap">
			<h1>Homepage Meta Data Settings</h1>
			<form method="post" action="options.php">
				<?php
				// Output security fields for the registered setting.
				settings_fields( 'home_page_seo_meta_settings_group' );
				// Output setting sections and fields.
				do_settings_sections( 'home-page-seo-plugin-meta-settings' );
				// Output save settings button.
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Function to register homepage meta data.
	 *
	 * @return void .
	 */
	public function home_page_seo_meta_data_register_settings() {
		register_setting( 'home_page_seo_meta_settings_group', 'homepage_seo_title' );
		register_setting( 'home_page_seo_meta_settings_group', 'homepage_seo_keywords' );
		register_setting( 'home_page_seo_meta_settings_group', 'homepage_seo_description' );
		register_setting( 'home_page_seo_meta_settings_group', 'homepage_original_title' );
		register_setting( 'home_page_seo_meta_settings_group', 'homepage_original_description' );
		register_setting( 'home_page_seo_meta_settings_group', 'home_page_logo_url' );


		// Add section for SEO fields.
		add_settings_section(
			'homepage_plugin_seo_section',
			'Home Page SEO Settings',
			null,
			'home-page-seo-plugin-meta-settings'
		);

		// SEO Title Field.
		add_settings_field(
			'homepage_seo_title',
			'SEO Title',
			array( $this, 'seo_title_callback' ),
			'home-page-seo-plugin-meta-settings',
			'homepage_plugin_seo_section'
		);

		// SEO Keywords Field.
		add_settings_field(
			'homepage_seo_keywords',
			'SEO Keywords',
			array( $this, 'seo_keywords_callback' ),
			'home-page-seo-plugin-meta-settings',
			'homepage_plugin_seo_section'
		);

		// SEO Description Field.
		add_settings_field(
			'homepage_seo_description',
			'SEO Description',
			array( $this, 'seo_description_callback' ),
			'home-page-seo-plugin-meta-settings',
			'homepage_plugin_seo_section'
		);

		// Add section for Original Title/Description.
		add_settings_section(
			'original_section',
			'Original Home Page Titles',
			null,
			'home-page-seo-plugin-meta-settings'
		);

		// Original Title Field.
		add_settings_field(
			'homepage_original_title',
			'Original Title',
			array( $this, 'original_title_callback' ),
			'home-page-seo-plugin-meta-settings',
			'original_section'
		);

		// Original Description Field.
		add_settings_field(
			'homepage_original_description',
			'Original Description',
			array( $this, 'original_description_callback' ),
			'home-page-seo-plugin-meta-settings',
			'original_section'
		);
		// home page logo url.
		add_settings_field(
			'home_page_logo_url',
			'Home Page Logo',
			array( $this, 'home_page_logo_callback' ),
			'home-page-seo-plugin-meta-settings',
			'homepage_plugin_seo_section'
		);
	}

	/**
	 * Callback seo title functions to display the form fields .
	 *
	 * @return void
	 */
	public function seo_title_callback() {
		$value = get_option( 'homepage_seo_title', '' );
		echo '<input type="text" name="homepage_seo_title" value="' . esc_attr( $value ) . '" class="regular-text">';
	}

	/**
	 * Callback seo keywords functions to display the form fields .
	 *
	 * @return void
	 */
	public function seo_keywords_callback() {
		$value = get_option( 'homepage_seo_keywords', '' );
		echo '<input type="text" name="homepage_seo_keywords" value="' . esc_attr( $value ) . '" class="regular-text">';
	}

	/**
	 * Callback seo description functions to display the form fields .
	 *
	 * @return void
	 */
	public function seo_description_callback() {
		$value = get_option( 'homepage_seo_description', '' );
		echo '<textarea name="homepage_seo_description" rows="5" class="large-text">' . esc_textarea( $value ) . '</textarea>';
	}

	/**
	 * Callback OG Title title functions to display the form fields .
	 *
	 * @return void
	 */
	public function original_title_callback() {
		$value = get_option( 'homepage_original_title', '' );
		echo '<input type="text" name="homepage_original_title" value="' . esc_attr( $value ) . '" class="regular-text">';
	}

	/**
	 * Callback OG Description functions to display the form fields .
	 *
	 * @return void
	 */
	public function original_description_callback() {
		$value = get_option( 'homepage_original_description', '' );
		echo '<textarea name="homepage_original_description" rows="5" class="large-text">' . esc_textarea( $value ) . '</textarea>';
	}

	/**
	 * Callback Home page logo functions to display the form fields .
	 *
	 * @return void
	 */
	public function home_page_logo_callback() {
		$logo_url = get_option( 'home_page_logo_url', '' );
		?>
	<input type="text" id="home_page_logo_url" name="home_page_logo_url" value="<?php echo esc_attr( $logo_url ); ?>" class="regular-text" placeholder="Paste the logo image URL of your website">
		<?php if ( ! empty( $logo_url ) ) : ?>
			<br><img src="<?php echo esc_url( $logo_url ); ?>" style="max-width: 200px; margin-top: 10px;" />
		<?php endif; ?>
		<?php
	}

	// From the below their is rendering of seo tags .

	/** Function Removing title theme support
	 *
	 * @return void
	 */
	public function my_plugin_remove_theme_support() {
		remove_theme_support( 'title-tag' );
	}

	/**
	 * Gets the featured image details of a specific post.
	 *
	 * @param int $post_id Post ID to retrieve image details for.
	 * @return array|null Returns an associative array with 'url', 'alt', 'width', 'height', 'caption' or null if no image found.
	 */
	public function get_featured_image_details( $post_id ) {
		$thumbnail_id = get_post_thumbnail_id( $post_id );

		if ( ! $thumbnail_id ) {
			return null;
		}

		$image_info = wp_get_attachment_image_src( $thumbnail_id, 'full' );

		if ( ! $image_info ) {
			return null;
		}

		$image_url    = $image_info[0];
		$image_width  = $image_info[1];
		$image_height = $image_info[2];

		$alt_text = get_post_meta( $thumbnail_id, '_wp_attachment_image_alt', true );
		if ( empty( $alt_text ) ) {
			$alt_text = basename( get_attached_file( $thumbnail_id ) );
		}

		$caption = wp_get_attachment_caption( $thumbnail_id );
		if ( empty( $caption ) ) {
			$caption = null;
		}

		return array(
			'url'     => $image_url,
			'alt'     => $alt_text,
			'width'   => $image_width,
			'height'  => $image_height,
			'caption' => $caption,
		);
	}

	/**
	 * Function to add all the meta values coming from the home page meta setting and article page
	 * meta boxes if there values are not null and not empty it is ecoing in the wp_head for more 
	 * seo optimisation.
	 */
	public function seo_meta_changes() {
		$site_title = get_bloginfo( 'name' );
		$site_url   = get_site_url();

		if ( is_single() ) {
			global $post;
			if ( is_object( $post ) && ! empty( $post ) ) {
				$id                   = $post->ID;
				$post_meta            = get_post_meta( $id );
				$seo_title            = isset( $post_meta['_seo_title'][0] ) ? $post_meta['_seo_title'][0] : get_the_title();
				$seo_meta_description = isset( $post_meta['_seo_meta_description'][0] ) ? $post_meta['_seo_meta_description'][0] : '';
				$seo_keywords         = isset( $post_meta['_seo_keywords'][0] ) ? $post_meta['_seo_keywords'][0] : '';

				if ( ! empty( $seo_title ) ) {
					echo '<title>' . esc_html( $seo_title ) . '</title>' . "\n";
					echo '<meta name="title" content="' . esc_attr( $seo_title ) . '">' . "\n";
				}

				if ( ! empty( $seo_meta_description ) && isset( $seo_meta_description ) ) {
					echo '<meta name="description" content="' . esc_attr( $seo_meta_description ) . '">' . "\n";
				}

				if ( ! empty( $seo_keywords ) && isset( $seo_keywords ) ) {
					echo '<meta name="keywords" content="' . esc_attr( $seo_keywords ) . '">' . "\n";
				}

				$article_title                = get_the_title( $id );
				$article_link                 = get_permalink( $id );
				$article_excerpt              = get_the_excerpt( $id );
				$article_first_published_time = get_the_date( DATE_W3C );
				$article_updated_time         = get_the_modified_date( DATE_W3C );
				$thumbnail_data               = $this->get_featured_image_details( $id );

				if ( null !== $thumbnail_data ) {
					$thumbnail_url    = isset( $thumbnail_data['url'] ) ? $thumbnail_data['url'] : null;
					$thumbnail_alt    = isset( $thumbnail_data['alt'] ) ? $thumbnail_data['alt'] : null;
					$thumbnail_height = isset( $thumbnail_data['height'] ) ? $thumbnail_data['height'] : null;
					$thumbnail_width  = isset( $thumbnail_data['width'] ) ? $thumbnail_data['width'] : null;
				}

				if ( ! empty( $article_title ) && ! empty( $article_link ) && ! empty( $article_first_published_time ) && ! empty( $article_updated_time ) && ! empty( $article_excerpt ) && isset( $site_title ) && ! empty( $site_title ) ) {
					echo '<meta name="robots" content="max-image-preview:large">' . "\n";
					echo '<link rel="canonical" href="' . esc_url( $article_link ) . '" />' . "\n";
					echo '<meta property="og:locale" content="hi" />' . "\n";
					echo '<meta property="og:type" content="article" />' . "\n";
					echo '<meta property="og:title" content="' . esc_attr( $article_title ) . '" />' . "\n";
					echo '<meta property="og:description" content="' . esc_attr( $article_excerpt ) . '" />' . "\n";
					echo '<meta property="og:url" content="' . esc_url( $article_link ) . '" />' . "\n";

					if ( isset( $thumbnail_url, $thumbnail_alt, $thumbnail_height, $thumbnail_width ) &&
					! empty( $thumbnail_url ) &&
					! empty( $thumbnail_alt ) &&
					! empty( $thumbnail_height ) &&
					! empty( $thumbnail_width )
					) {
						echo '<meta property="og:image" content="' . esc_url( $thumbnail_url ) . '" />' . "\n";
						echo '<meta property="og:image:alt" content="' . esc_attr( $thumbnail_alt ) . '" />' . "\n";
						echo '<meta property="og:image:width" content="' . esc_attr( $thumbnail_width ) . '" />' . "\n";
						echo '<meta property="og:image:height" content="' . esc_attr( $thumbnail_height ) . '" />' . "\n";
					}
					if ( ! empty( $site_title ) ) {
						echo '<meta property="og:site_name" content="' . esc_attr( $site_title ) . '" />' . "\n";
					}
					echo '<meta name="twitter:card" content="summary_large_image" />' . "\n";
					echo '<meta name="twitter:site" content="" />' . "\n";
					echo '<meta name="twitter:creator" content="" />' . "\n";
					if ( ! empty( $site_title ) && ! empty( $site_excerpt ) ) {
						echo '<meta name="twitter:title" content="' . esc_attr( $article_title ) . '" />' . "\n";
						echo '<meta name="twitter:description" content="' . esc_attr( $article_excerpt ) . '" />' . "\n";
					}
					if ( ! empty( $thumbnail_url ) ) {
						echo '<meta name="twitter:image" content="' . esc_url( $thumbnail_url ) . '" />' . "\n";
					}
					if ( ! empty( $thumbnail_alt ) ) {
						echo '<meta name="twitter:image:alt" content="' . esc_attr( $thumbnail_alt ) . '" />' . "\n";
					}
					if ( ! empty( $article_first_published_time ) ) {
						echo '<meta property="article:published_time" content="' . esc_attr( $article_first_published_time ) . '" />' . "\n";
					}
					if ( ! empty( $article_updated_time ) && $article_updated_time > $article_first_published_time ) {
						echo '<meta property="article:modified_time" content="' . esc_attr( $article_updated_time ) . '" />' . "\n";
					}
				}
			}
		} elseif ( is_home() || is_front_page() ) {
			$home_page_seo_title            = get_option( 'homepage_seo_title' );
			$home_page_seo_keywords         = get_option( 'homepage_seo_keywords' );
			$home_page_seo_description      = get_option( 'homepage_seo_description' );
			$home_page_original_title       = get_option( 'homepage_original_title' );
			$home_page_original_description = get_option( 'homepage_original_description' );
			$homepage_logo_image_url        = get_option( 'home_page_logo_url' );
			$homepage_logo_image_url_alt    = null;
			$homepage_logo_image_url_id     = wpcom_vip_attachment_url_to_postid( $homepage_logo_image_url );

			if ( $homepage_logo_image_url_id ) {
				$home_page_logo_details = $this->get_featured_image_details( $homepage_logo_image_url_id );
				if ( null !== $home_page_logo_details && isset( $home_page_logo_details['alt'] ) ) {
					$homepage_logo_image_url_alt = $home_page_logo_details['alt'];
				}
			}
			echo '<title>' . esc_html( $home_page_seo_title ) . '</title>' . "\n";
			echo '<meta name="keywords" content="' . esc_attr( $home_page_seo_keywords ) . '">' . "\n";
			echo '<meta name="description" content="' . esc_attr( $home_page_seo_description ) . '">' . "\n";
			echo '<link rel="canonical" href="' . esc_url( $site_url ) . '" />' . "\n";
			echo '<meta property="og:locale" content="hi" />' . "\n";
			echo '<meta property="og:type" content="website" />' . "\n";
			echo '<meta property="og:title" content="' . esc_attr( $home_page_original_title ) . '" />' . "\n";
			echo '<meta property="og:description" content="' . esc_attr( $home_page_original_description ) . '" />' . "\n";
			echo '<meta property="og:url" content="' . esc_url( $site_url ) . '" />' . "\n";

			if ( ! empty( $homepage_logo_image_url ) && is_string( $homepage_logo_image_url ) ) {
				echo '<meta property="og:image" content="' . esc_url( $homepage_logo_image_url ) . '" />' . "\n";
				echo '<meta property="og:image:width" content="1200" />' . "\n";
				echo '<meta property="og:image:height" content="900" />' . "\n";
				if ( null !== $homepage_logo_image_url_alt ) {
					echo '<meta property="og:image:alt" content="' . esc_attr( $homepage_logo_image_url_alt ) . '" />' . "\n";
				}
			}

			echo '<meta property="og:site_name" content="' . esc_attr( $site_title ) . '" />' . "\n";
			echo '<meta name="twitter:card" content="summary_large_image" />' . "\n";
			echo '<meta name="twitter:site" content="" />' . "\n";
			echo '<meta name="twitter:creator" content="" />' . "\n";
			echo '<meta name="twitter:title" content="' . esc_attr( $home_page_original_title ) . '" />' . "\n";
			echo '<meta name="twitter:description" content="' . esc_attr( $home_page_original_description ) . '" />' . "\n";
			echo '<meta name="twitter:image" content="' . esc_url( $homepage_logo_image_url ) . '" />' . "\n";
			if ( null !== $homepage_logo_image_url_alt ) {
				echo '<meta name="twitter:image:alt" content="' . esc_attr( $homepage_logo_image_url_alt ) . '" />' . "\n";
			}
		}
	}

	/**
	 * Function to register seo metadata fields so that it can be input form the json.
	 *
	 * @return void.
	 */
	public function register_custom_post_meta_fields() {
		register_post_meta(
			'post',
			'_seo_title',
			array(
				'type'          => 'string',
				'single'        => true,
				'show_in_rest'  => true,
				'auth_callback' => function() {
					return current_user_can( 'edit_posts' );
				},
			)
		);

		register_post_meta(
			'post',
			'_seo_meta_description',
			array(
				'type'          => 'string',
				'single'        => true,
				'show_in_rest'  => true,
				'auth_callback' => function() {
					return current_user_can( 'edit_posts' );
				},
			)
		);

		register_post_meta(
			'post',
			'_seo_slug_url',
			array(
				'type'          => 'string',
				'single'        => true,
				'show_in_rest'  => true,
				'auth_callback' => function() {
					return current_user_can( 'edit_posts' );
				},
			)
		);
	}
}
