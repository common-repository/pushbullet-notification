<?php
/*
Plugin Name: Pushbullet Notifications for WordPress
Description: Pushbullet Notifications allows your WordPress blog to send push notifications for events happening on your blog straight to your iOS device with the Pushbullet app. Copy from pushover notification plug-in
Version: 1.3.6
Author: Francis Noel
License: GPLv2
*/

define( 'FNPN_CORE_TEXT_DOMAIN', 'fnpn' );
define( 'FNPN_PATH', plugin_dir_path( __FILE__ ) );
define( 'FNPN_VERSION', '1.3.6' );
define( 'FNPN_FILE', plugin_basename( __FILE__ ) );
define( 'FNPN_URL', plugins_url( 'pushbullet-notification', 'pushbullet-notifications.php' ) );

class FNPushbulletNotifications {
	private static $fnpn_instance;

	private function __construct() {
		require_once( FNPN_PATH . '/includes/misc-functions.php' );
		require_once( FNPN_PATH . '/includes/notification-functions.php' );
		$options = fnpn_get_options();

		add_action( 'init', array( $this, 'fnpn_loaddomain', ) );
		add_action( 'cron_schedules', array( $this, 'add_cron_schedule' ) );
		add_action( 'init', array( $this, 'determine_cron_schedule' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'load_cusom_js' ) );
		
		if ( $options['new_user'] ) {
			add_action( 'user_register', 'fnpn_user_registration' );
		}

        if ( $options['login_user'] ) {
			add_action('wp_login', 'fnpn_user_login' );
		}

        if($options['xmlrpc_publish_post']){
            //http://codex.wordpress.org/Plugin_API/Action_Reference/xmlrpc_publish_post
            add_action( 'xmlrpc_publish_post', 'fnpn_xmlrpc_publish_post' );
        }

		if ( $options['new_comment'] ) {
			add_action( 'comment_post', 'fnpn_new_comment' );
		}
		
		if ( $options['password_reset'] ) {
			add_action( 'lostpassword_post', 'fnpn_lost_password_request' );
		}
		
		if ( $options['new_type']) {
			add_action( 'transition_post_status', 'fnpn_post_published', 10, 3 );
            /*add_action( 'publish_post', 'fnpn_post_published', 10, 3 );
            add_action( 'publish_page', 'fnpn_post_published', 10, 3 );*/
		}

		if ( is_admin() ) {
			require_once( FNPN_PATH . '/includes/admin/admin-pages.php' );
			require_once( FNPN_PATH . '/includes/admin/admin-functions.php' );
			add_action( 'admin_notices', array( $this, 'fnpn_edd_missing_nag' ) );
			
			/** Settings Pages **/
			add_action( 'admin_init', array( $this, 'fnpn_register_settings' ), 1000, 0 );
			add_action( 'admin_menu', array( $this, 'fnpn_setup_admin_menu' ), 1000, 0 );
			add_filter( 'plugin_action_links', array( $this, 'plugin_settings_links' ), 10, 2 );
			
			/** User Profile Settings
			add_filter( 'user_contactmethods', 'fnpn_add_contact_item', 10, 1 );			

			if ( $options['new_post'] ) {
				add_action( 'show_user_profile', 'fnpn_add_profile_settings', 10, 1 );
				add_action( 'edit_user_profile', 'fnpn_add_profile_settings', 10, 1 );
				add_action( 'personal_options_update', 'fnpn_save_profile_settings', 10, 1 );
				add_action( 'edit_user_profile_update', 'fnpn_save_profile_settings', 10, 1 );
			}
			**/
		}
	}

	/**
	 * Get the singleton instance of our plugin
	 * @return class The Instance
	 * @access public
	 */
	public static function getInstance() {
		if ( !self::$fnpn_instance ) {
			self::$fnpn_instance = new FNPushbulletNotifications();
		}

		return self::$fnpn_instance;
	}

	/**
	 * Add a 12 hour cron schedule
	 * @param array $schedules The current list of cron schedules
	 * @access public
	 */
	public function add_cron_schedule( $schedules ) {
		$schedules['twicedaily'] = array(
								'interval'  => 43200,
								'display'	=> __( 'Twice Daily', FNPN_CORE_TEXT_DOMAIN ) );

		return $schedules;
	}

	/**
	 * Determine when to schedule the cron
	 * @return void
	 * @access public
	 */
	public function determine_cron_schedule() {
		$current_options = fnpn_get_options();
		if ( $current_options['plugin_updates'] || $current_options['core_update'] ) {
			if ( !wp_next_scheduled( 'fnpn_plugin_update_check' ) ) {
				$next_run = time();
				wp_schedule_event( $next_run, 'twicedaily', 'fnpn_plugin_update_check' );
			}
			add_action( 'fnpn_plugin_update_check', 'fnpn_plugin_update_checks' );
		}
	}

	/**
	 * Queue up the JavaScript file for the admin page, only on our admin page
	 * @param  string $hook The current page in the admin
	 * @return void
	 * @access public
	 */
	public function load_cusom_js( $hook ) {
		if ( 'settings_page_pushbullet-notifications' != $hook )
			return;

		wp_enqueue_script( 'fnpn_core_custom_js', FNPN_URL.'/includes/scripts/fnpn_custom.js', 'jquery', FNPN_VERSION, true );
	}

	/**
	 * Send notifications
	 * @param  array $passed_args The arguments used by the pushbullet API
	 * @return void
	 * @access public
	 * @deprecated Deprecated since 1.8 - Use the non-class function fnpn_send_notification with the same arguments
	 */
	public function fnpn_send_notification( $passed_args ) {
		// Back compat
		fnpn_send_notification( $passed_args );
	}

	/**
	 * Adds the Settings and pushbullet Link to the Settings page list
	 * @param  array $links The current list of links
	 * @param  string $file The plugin file
	 * @return array        The new list of links, with our additional ones added
	 * @access public
	 */
	public function plugin_settings_links( $links, $file ) {
		if ( $file != FNPN_FILE )
			return $links;

		$settings_link = sprintf( '<a href="%s">%s</a>', admin_url( 'options-general.php?page=pushbullet-notifications' ), __( 'Settings', FNPN_CORE_TEXT_DOMAIN ) );
		$pushbullet_link = sprintf( '<a href="http://www.pushbullet.com" target="_blank">%s</a>', __( 'Visit Pushbullet', FNPN_CORE_TEXT_DOMAIN ) );

		array_unshift( $links, $settings_link );
		$links[] = $pushbullet_link;

		return $links;
	}

	/**
	 * Add the pushbullet Notifications item to the Settings menu
	 * @return void
	 * @access public
	 */
	public function fnpn_setup_admin_menu() {
		add_options_page( __( 'Pushbullet Notifications', FNPN_CORE_TEXT_DOMAIN ), __( 'Pushbullet Notifications', FNPN_CORE_TEXT_DOMAIN ), 'administrator', 'pushbullet-notifications', array( $this, 'determine_tab' ) );
	}

	/**
	 * Determines what tab is being displayed, and executes the display of that tab
	 * @return void
	 * @access public
	 */
	public function determine_tab() {
		$settings = fnpn_get_options();
		?>
		<div id="icon-options-general" class="icon32"></div><h2><?php _e( 'Pushbullet Notifications for WordPress', FNPN_CORE_TEXT_DOMAIN ); ?></h2>
		<?php
		$current = ( !isset( $_GET['tab'] ) ) ? 'general' : $_GET['tab'];
		$default_tabs = array(
				'general' => __( 'Settings', FNPN_CORE_TEXT_DOMAIN ),
				'logs' => __( 'Logs', FNPN_CORE_TEXT_DOMAIN ),
				'sysinfo' => __( 'System Info', FNPN_CORE_TEXT_DOMAIN )
			);

		
		$tabs = apply_filters( 'fnpn_settings_tabs', $default_tabs );

		?><h2 class="nav-tab-wrapper"><?php
		foreach( $tabs as $tab => $name ){
			$class = ( $tab == $current ) ? ' nav-tab-active' : '';
			echo "<a class='nav-tab$class' href='?page=pushbullet-notifications&tab=$tab'>$name</a>";
		}
		?>
		</h2>
		<div class="wrap">
		<?php
		if ( !isset( $_GET['tab'] ) || $_GET['tab'] == 'general' ) {
			fnpn_admin_page();
		} else {
			// Extension Devs - Your function that shows the tab content needs to be prefaced with 'fnpn_display_' in order to work here.
			$tab_function = 'fnpn_display_'.$_GET['tab'];
			$tab_function();
		}
		?>
		</div>
		<?php
	}

	/**
	 * Register/Whitelist our settings on the settings page, allow extensions and other plugins to hook into this
	 * @return void
	 * @access public
	 */
	public function fnpn_register_settings() {
		register_setting( 'fnpn-update-options', 'fnpn_pushbullet_notifications_settings' );
		do_action( 'fnpn_register_additional_settings' );
	}

	/**
	 * Display a warning if the user doesn't have the required plugins or versions active. Also notify them of available extensions
	 * @return void
	 * @access public
	 */
	public function fnpn_edd_missing_nag() {
		if ( !isset($_REQUEST['page'] ) || $_REQUEST['page'] != 'pushbullet-notifications' )
			return;

		/*if ( is_plugin_active( 'easy-digital-downloads/easy-digital-downloads.php' ) && 
			! is_plugin_active( 'pushbullet-notifications-edd-ext/pushbullet-notifications-edd-ext.php' ) ) {
			printf( '<div class="error"> <p> %s </p> </div>', __( 'Get pushbullet Notifications for your Easy Digital Downloads Sales with the <a href="https://easydigitaldownloads.com/extension/pushbullet-notifications/?ref=371" target="_blank">pushbullet Notifications Extension</a>.', FNPN_CORE_TEXT_DOMAIN ) );
		}
		if ( is_plugin_active( 'bbpress/bbpress.php' ) && 
			! is_plugin_active( 'pushbullet-notifications-bbp-ext/pushbullet-notifications-bbp-ext.php' ) ) {
			printf( '<div class="error"> <p> %s </p> </div>', __( 'Get pushbullet Notifications for bbPress with the <a href="https://wp-push.com/extensions/bbpress-extension/" target="_blank">pushbullet Notifications Extension</a>.', FNPN_CORE_TEXT_DOMAIN ) );
		}*/
	}

	/**
	 * Load the Text Domain for i18n
	 * @return void
	 * @access public
	 */
	public function fnpn_loaddomain() {
		load_plugin_textdomain(FNPN_CORE_TEXT_DOMAIN, false, '/pushbullet-notifications/languages/' );
	}

	/**
	 * Original get_options unifier
	 * @return array List of options
	 * @deprecated as of 1.5
	 * @access public
	 */
	public function get_options() {
		return fnpn_get_options();
	}

}

$fnpn_loaded = FNPushbulletNotifications::getInstance();
