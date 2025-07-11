<?php
/**
 * Class Auto_Scheduler
 *
 * @package    All-in-one-plugin
 * @subpackage Auto_Scheduler
 * @author     Prathamesh Kirpal
 * @license    GPL-2.0+
 * @since      1.0.0
*/

defined( 'ABSPATH' ) || exit;

/**
 * Class Auto_Scheduler.
 */
class Auto_Scheduler {

	/**
	 * Options.
	 */
	const OPTION_PHASE_1       = 'auto_scheduler_phase_1';
	const OPTION_PHASE_2       = 'auto_scheduler_phase_2';
	const OPTION_PHASE_3       = 'auto_scheduler_phase_3';
	const OPTION_CURRENT_PHASE = 'auto_scheduler_current_phase';
	const OPTION_POST_STATUS   = 'auto_scheduler_post_status';

	/**
	 * Boot the module.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_settings_page' ) );
		add_action( 'auto_scheduler_cron_hook', array( __CLASS__, 'schedule_drafts' ) );
	}

	/**
	 * Run on plugin/module activation.
	 */
	public static function activate() {
		add_option( self::OPTION_PHASE_1, 1 );
		add_option( self::OPTION_PHASE_2, 3 );
		add_option( self::OPTION_PHASE_3, 5 );
		add_option( self::OPTION_CURRENT_PHASE, 1 );
		add_option( self::OPTION_POST_STATUS, 'draft' );
		// Activation should be called by main plugin loader once.
		if ( ! wp_next_scheduled( 'auto_scheduler_cron_hook' ) ) {
			wp_schedule_event( time(), 'daily', 'auto_scheduler_cron_hook' );
		}
	}

	/**
	 * Run on plugin/module deactivation.
	 */
	public static function deactivate() {
		wp_clear_scheduled_hook( 'auto_scheduler_cron_hook' );
	}

	/**
	 * Add settings page under Settings.
	 */
	public static function add_settings_page() {
		add_options_page(
			'Auto Scheduler Settings',
			'Auto Scheduler',
			'manage_options',
			'auto-scheduler',
			array( __CLASS__, 'render_settings_page' )
		);
	}

	/**
	 * Render settings page.
	 */
	public static function render_settings_page() {
		if ( isset( $_POST['auto_scheduler_save'] ) && check_admin_referer( 'auto_scheduler_save_action' ) ) {
			update_option( self::OPTION_PHASE_1, absint( $_POST['phase_1'] ) );
			update_option( self::OPTION_PHASE_2, absint( $_POST['phase_2'] ) );
			update_option( self::OPTION_PHASE_3, absint( $_POST['phase_3'] ) );
			update_option( self::OPTION_POST_STATUS, sanitize_text_field( $_POST['post_status'] ) );
			echo '<div class="updated"><p>Settings saved.</p></div>';
		}

		$phase1  = get_option( self::OPTION_PHASE_1, 1 );
		$phase2  = get_option( self::OPTION_PHASE_2, 3 );
		$phase3  = get_option( self::OPTION_PHASE_3, 5 );
		$status  = get_option( self::OPTION_POST_STATUS, 'draft' );

		?>
		<div class="wrap">
			<h1>Auto Scheduler Settings</h1>
			<form method="post">
				<?php wp_nonce_field( 'auto_scheduler_save_action' ); ?>
				<table class="form-table">
					<tr>
						<th><label for="phase_1">Phase 1 Posts/Day</label></th>
						<td><input type="number" name="phase_1" value="<?php echo esc_attr( $phase1 ); ?>" min="1"></td>
					</tr>
					<tr>
						<th><label for="phase_2">Phase 2 Posts/Day</label></th>
						<td><input type="number" name="phase_2" value="<?php echo esc_attr( $phase2 ); ?>" min="1"></td>
					</tr>
					<tr>
						<th><label for="phase_3">Phase 3 Posts/Day</label></th>
						<td><input type="number" name="phase_3" value="<?php echo esc_attr( $phase3 ); ?>" min="1"></td>
					</tr>
					<tr>
						<th><label for="post_status">Post Status</label></th>
						<td>
							<select name="post_status">
								<option value="draft" <?php selected( $status, 'draft' ); ?>>Draft</option>
								<option value="private" <?php selected( $status, 'private' ); ?>>Private</option>
							</select>
						</td>
					</tr>
				</table>
				<?php submit_button( 'Save Settings', 'primary', 'auto_scheduler_save' ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Main logic to schedule drafts.
	 */
	public static function schedule_drafts() {
		$current_phase = absint( get_option( self::OPTION_CURRENT_PHASE, 1 ) );
		$post_status   = get_option( self::OPTION_POST_STATUS, 'draft' );

		$phase_counts = array(
			1 => absint( get_option( self::OPTION_PHASE_1, 1 ) ),
			2 => absint( get_option( self::OPTION_PHASE_2, 3 ) ),
			3 => absint( get_option( self::OPTION_PHASE_3, 5 ) ),
		);

		$posts_per_day = isset( $phase_counts[ $current_phase ] ) ? $phase_counts[ $current_phase ] : $phase_counts[3];

		$drafts = get_posts(
			array(
				'post_status' => 'draft',
				'post_type'   => 'post',
				'numberposts' => 999,
				'orderby'     => 'date',
				'order'       => 'ASC',
			)
		);

		$today = strtotime( 'today' );

		for ( $i = 0; $i < 7; $i++ ) {
			if ( empty( $drafts ) ) {
				break;
			}

			// Always start from *tomorrow*
			$timestamp = strtotime( '+' . ( $i + 1 ) . ' days', $today );

			for ( $j = 0; $j < $posts_per_day; $j++ ) {
				if ( empty( $drafts ) ) {
					break;
				}

				$post = array_shift( $drafts );

				if ( get_post_meta( $post->ID, '_auto_scheduler_done', true ) ) {
					continue;
				}

				$random_time    = rand( 9 * 3600, 20 * 3600 );
				$post_date      = date( 'Y-m-d H:i:s', $timestamp + $random_time );
				$post_date_gmt  = get_gmt_from_date( $post_date );
				$post_status    = 'future';

				$result = wp_update_post(
					array(
						'ID'            => $post->ID,
						'post_status'   => $post_status,
						'post_date'     => $post_date,
						'post_date_gmt' => $post_date_gmt,
					)
				);

				if ( ! is_wp_error( $result ) ) {
					update_post_meta( $post->ID, '_auto_scheduler_done', 1 );
				}
			}
		}

		if ( $current_phase < 3 ) {
			update_option( self::OPTION_CURRENT_PHASE, $current_phase + 1 );
		}
	}
}
