<?php
/**
 * Plugin Name: myCred - LifterLMS Integration
 * Description: Extend the power of the LifterLMS plugin with the popular myCRED points management plugin.
 * Version: 1.5
 * Author: myCRED
 * Author URI: www.mycred.me
 * Text Domain: mycred-lifterlms-integration
 * Requires at least: WP 4.8
 * Tested up to: WP 6.6.1
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if (! class_exists( 'myCRED_LifterLMS')) :
	final class myCRED_LifterLMS {

		// Plugin Version
		public $version             = '1.5';

		// Instnace
		protected static $_instance = NULL;

		/**
		 * Setup Instance
		 * @since 1.1.2
		 * @version 1.0
		 */
		public static function instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}

		/**
		 * Not allowed
		 * @since 1.1.2
		 * @version 1.0
		 */
		public function __clone() { _doing_it_wrong( __FUNCTION__, 'Cheatin&#8217; huh?', '1.5'); }

		/**
		 * Not allowed
		 * @since 1.1.2
		 * @version 1.0
		 */
		public function __wakeup() { _doing_it_wrong( __FUNCTION__, 'Cheatin&#8217; huh?', '1.5'); }

		/**
		 * Define
		 * @since 1.1.2
		 * @version 1.0
		 */
		private function define($name, $value, $definable = true) {
			if (! defined($name))
				define($name, $value);
			elseif (! $definable && defined($name))
				_doing_it_wrong('myCRED_LifterLMS->define()', 'Could not define: ' . esc_html($name) . ' as it is already defined somewhere else!', '1.5');
		}

		/**
		 * Require File
		 * @since 1.1.2
		 * @version 1.0
		 */
		public function file($required_file) {
			if (file_exists($required_file))
				require_once $required_file;
			else
				_doing_it_wrong('myCRED_LifterLMS->file()', 'Requested file ' . esc_html($required_file) . ' not found.', '1.5');
		}

		/**
		 * Construct
		 * @since 1.0
		 * @version 1.0
		 */
		public function __construct() {

			$this->define_constants();

			if(! function_exists('is_plugin_active')) {
				include_once(ABSPATH . 'wp-admin/includes/plugin.php');
			}

			$is_lifterlms_active = is_plugin_active('lifterlms/lifterlms.php');
			$is_mycred_active    = is_plugin_active('mycred/mycred.php');

			add_action('admin_init', array($this, 'prevent_mycred_lifterlms_integration_activation'));

			if ($is_lifterlms_active && $is_mycred_active) {
				$this->includes();
				add_filter('mycred_setup_hooks',        array($this, 'register_hooks'));
				add_filter('mycred_all_references',    array($this, 'setup_references'));
			    add_filter('lifterlms_payment_gateways',array($this,'add_core_mycred_gateways'),1000);
				add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_lifterlms_front_scripts' ),1000 );
				add_action( 'admin_enqueue_scripts', array( $this, 'mycred_lifterlms_admin_styles_scripts' ),1000 );
			}
			
			if (! $is_mycred_active) {
				add_action('admin_notices', array($this, 'mycred_inactive_admin_notice'));
			}


		}

		public function enqueue_lifterlms_front_scripts() {

			wp_enqueue_style( 'llms-mycred-front-styles', plugins_url( '/assets/css/lifterlmsfront.css', __FILE__ ), array(), '1.0', 'all');

			wp_enqueue_script('front-mycredlifterlms-script', plugins_url( '/assets/js/front.js', __FILE__ ), array(),array('jquery'),'1.0.0',true);

		}

		public function prevent_mycred_lifterlms_integration_activation() {

			// Check if LifterLMS plugin and myCred plugin is active
		    if ( ! is_plugin_active( 'mycred/mycred.php' ) && ! is_plugin_active( 'lifterlms/lifterlms.php' ) ) {
		        // Deactivate "myCred - LifterLMS Integration" add-on
		        deactivate_plugins( 'mycred-lifterlms-integration/mycred-lifterlms.php' );
		        // Display an error message
		        wp_die( 'Please activate myCred plugin and LifterLMS plugin before activating myCred – LifterLMS Integration.' );
		    }
			// Check if myCred plugin is active
		    elseif ( ! is_plugin_active( 'mycred/mycred.php' ) ) {
		        // Deactivate "myCred - LifterLMS Integration" add-on
		        deactivate_plugins( 'mycred-lifterlms-integration/mycred-lifterlms.php' );
		        // Display an error message
		        wp_die( 'Please activate myCred plugin before activating myCred – LifterLMS Integration.' );
		    }
		    // Check if LifterLMS plugin is active
		    elseif ( ! is_plugin_active( 'lifterlms/lifterlms.php' ) ) {
		        // Deactivate "myCred - LifterLMS Integration" add-on
		        deactivate_plugins( 'mycred-lifterlms-integration/mycred-lifterlms.php' );
		        // Display an error message
		        wp_die( 'Please activate LifterLMS plugin before activating myCred – LifterLMS Integration.' );
		    }
		}

		public function get_gateway() {
			return llms()->payment_gateways()->get_gateway_by_id('mycred');
		}

		public function add_core_mycred_gateways($gateways) {
			$gateways[] = 'LLMS_Payment_Gateway_Mycred';
			return $gateways;
		}

		/**
		 * Define Constants
		 * @since 1.0
		 * @version 1.0
		 */
		private function define_constants() {
			$this->define('MYCRED_LIFTERLMS_VERSION',       $this->version);
			$this->define('MYCRED_LIFTERLMS_SLUG',          'mycred-lifterlms-integration');
			$this->define('MYCRED_LIFTERLMS',               __FILE__);
			$this->define('MYCRED_LIFTERLMS_ROOT',          plugin_dir_path( MYCRED_LIFTERLMS));
			$this->define('MYCRED_LIFTERLMS_INC_DIR',       MYCRED_LIFTERLMS_ROOT . 'includes/');
			$this->define('MYCRED_LIFTERLMS_TEMP_DIR',       MYCRED_LIFTERLMS_ROOT . 'templates/');
		}

		/**
		 * Include Plugin Files
		 * @since 1.0
		 * @version 1.0
		 */
		public function includes() {
			
			$this->file(MYCRED_LIFTERLMS_INC_DIR . 'mycred-lifterlms-course.php');
			$this->file(MYCRED_LIFTERLMS_INC_DIR . 'mycred-lifterlms-quiz.php');
			$this->file(MYCRED_LIFTERLMS_INC_DIR . 'mycred-lifterlms-lesson.php');
			$this->file(MYCRED_LIFTERLMS_INC_DIR . 'mycred-lifterlms-section.php');
			$this->file(MYCRED_LIFTERLMS_INC_DIR . 'mycred-lifterlms-membership.php');
			$this->file(MYCRED_LIFTERLMS_INC_DIR . 'mycred-lifterlms-plan.php');
			$this->file(MYCRED_LIFTERLMS_INC_DIR . 'mycred-lifterlms-achievement.php');
			$this->file(MYCRED_LIFTERLMS_INC_DIR . 'mycred-lifterlms-certificate.php');
			$this->file(MYCRED_LIFTERLMS_INC_DIR . 'mycred-lifterlms-endpoint.php');
			$this->file(MYCRED_LIFTERLMS_INC_DIR . 'mycred-lifterlms-notification.php');
			$this->file(MYCRED_LIFTERLMS_INC_DIR . 'mycred-lifterlms-payment-gateway.php');
		
		}

		public function register_hooks($installed)
		{
			$installed['mycred_lifterlms_course'] = array(
				'title'       => __('LifterLMS: Courses', 'mycred-lifterlms-integration'),
				'description' => __('Reward for completing course', 'mycred-lifterlms-integration'),
				'callback'    => array( 'myCRED_LifterLMS_Course' )
			);
			$installed['mycred_lifterlms_quiz'] = array(
				'title'       => __('LifterLMS: Quizes', 'mycred-lifterlms-integration'),
				'description' => __('Reward for completing quiz', 'mycred-lifterlms-integration'),
				'callback'    => array('myCRED_LifterLMS_Quiz')
			);
			$installed['mycred_lifterlms_lesson'] = array(
				'title'       => __('LifterLMS: Lessons', 'mycred-lifterlms-integration'),
				'description' => __('Reward for completing lesson', 'mycred-lifterlms-integration'),
				'callback'    => array('myCRED_LifterLMS_Lesson')
			);
			$installed['mycred_lifterlms_section'] = array(
				'title'       => __('LifterLMS: Sections', 'mycred-lifterlms-integration'),
				'description' => __('Reward for completing a section','mycred-lifterlms-integration'),
				'callback'    => array('myCRED_LifterLMS_Section')
			);
			$installed['mycred_lifterlms_membership'] = array(
				'title'       => __('LifterLMS: Memberships', 'mycred-lifterlms-integration'),
				'description' => __('Reward for buying a membership', 'mycred-lifterlms-integration'),
				'callback'    => array('myCRED_LifterLMS_Membership')
			);
			$installed['mycred_lifterlms_plan'] = array(
				'title'       => __('LifterLMS: Plans / Products', 'mycred-lifterlms-integration'),
				'description' => __('Reward for buying a plan', 'mycred-lifterlms-integration'),
				'callback'    => array('myCRED_LifterLMS_Plan')
			);
			$installed['mycred_lifterlms_achievement'] = array(
				'title'       => __('LifterLMS: Achievements', 'mycred-lifterlms-integration'),
				'description' => __('Reward when user earns an achievement','mycred-lifterlms-integration'),
				'callback'    => array('myCRED_LifterLMS_Achievement')
			);
			$installed['mycred_lifterlms_certificate'] = array(
				'title'       => __('LifterLMS: Certificates', 'mycred-lifterlms-integration'),
				'description' => __('Reward for getting a certificate','mycred-lifterlms-integration'),
				'callback'    => array('myCRED_LifterLMS_Certificate')
			);
			return $installed;
		}

		/**
		 * Setup references so they show on the Edit Points log screen
		 *
		 * @param $references
		 *
		 * @return mixed
		 */
		function setup_references($references) {

			$references['lifterlms_course_completed'] 			= 'LifterLMS: Student completes a course';
			$references['lifterlms_lesson_completed'] 			= 'LifterLMS: Student completes a lesson';
			$references['lifterlms_section_completed'] 			= 'LifterLMS: Student completes a section';
			$references['lifterlms_course_track_completed'] 	= 'LifterLMS: Student comepletes a course track';
			$references['lifterlms_quiz_passed'] 				= 'LifterLMS: Student passes a quiz';
			$references['lifterlms_quiz_failed'] 				= 'LifterLMS: Student fails a quiz';
			$references['lifterlms_quiz_completed'] 			= 'LifterLMS: Student completes a quiz';
			$references['llms_user_enrolled_in_course'] 		= 'LifterLMS: Student enrolls in a course';
			$references['llms_user_added_to_membership_level'] 	= 'LifterLMS: Student is added to a membership level';
			$references['lifterlms_access_plan_purchased'] 		= 'LifterLMS: Student Purchases an Access Plan or Product';
			$references['llms_user_earned_achievement'] 		= 'LifterLMS: Student earns a new achievement';
			$references['llms_user_earned_certificate'] 		= 'LifterLMS: Student earns a new certificate';

			return $references;

		}

		/**
		 * Add an admin notice when the myCRED plugin is not currently active
		 * @since 1.0
		 * @version 1.0
		 */
		public function mycred_inactive_admin_notice() {
			$class   = 'notice notice-error';
			$message = __('myCRED is currently not active. Please activate it so that you can continue to use this LifterLMS extension.', 'mycred-lifterlms-integration');
			printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), esc_html($message));
		}

		public function mycred_lifterlms_admin_styles_scripts() {
			wp_enqueue_style( 'llms-mycred-admin-styles', plugins_url( '/assets/css/admin.css', __FILE__ ), array(), '1.0', 'all');
			wp_enqueue_script('positive-number-script', plugins_url( '/assets/js/admin.js', __FILE__ ), array(),array('jquery'),'1.0.0',true);
			$mycred = new myCRED_Settings();

			if ( ! isset( $mycred->format['decimals'] ) ) {
   							$decimals = $mycred->core['format']['decimals'];
   			}
 			else {
   						   $decimals = $mycred->format['decimals'];
 			}

			 wp_localize_script('positive-number-script', 'lifterlms_mycred', 
			 	array(
			 	'profit_sharing' => $mycred->number(get_option('llms_gateway_mycred_lifterlms_profit_sharing')),
			 	'exchange_rate' => $mycred->number(get_option('llms_gateway_mycred_lifterlms_exchange_rate')),
			 	'decimals' => $decimals
			    )
			 );


		}
	}
endif;

function mycred_lifterlms_plugin() {
	return myCRED_LifterLMS::instance();
}
add_action('plugins_loaded', 'mycred_lifterlms_plugin');