<?php
/**
 * Get the core settings - This array can be supplemented by extensions using the 'fnpn_options_defaults' filter;
 * 
 * @return array The options used for Pushbullet Notifications
 */
function fnpn_get_options() {
	static $current_options = NULL;

	if ( $current_options !== NULL )
		return $current_options;

	$defaults = array(
			'application_key' 		 => false,
			'new_user'				 => false,
			'new_post'				 => false,
			'post_notification_role' => 'administrator',
			'new_comment'			 => false,
			'notify_authors'	 	 => false,
			'password_reset'		 => false,
			'plugin_updates'		 => false,
			'core_update'			 => false,
			'sslverify'				 => false,
			'multiple_keys'			 => false,
			'logging'				 => false,
            'login_user'             => false,
            'new_post_title'         => 'New item %postname%',
            'new_post_message'         => '%posturl%',
            'new_user_title'        =>'%blogname% : new user',
            'new_user_message'        =>'%accountname% created an account.',
            'new_comment_title'        =>'%blogname% : %commenttype%',
            'new_comment_message'        =>'by %commentauthor% on %postname%',
            'login_user_title'          => '%blogname% : user logged',
            'login_user_message'        => '%accountname% is logged'
		);

	$defaults = apply_filters( 'fnpn_options_defaults', $defaults );
	
	$options = wp_parse_args( get_option( 'fnpn_pushbullet_notifications_settings' ), $defaults );

	return $options;
}

/**
 * Get a core setting - This array can be supplemented by extensions using the 'fnpn_options_defaults' filter;
 * 
 * @return mixed The value from the main settings array
 */
function fnpn_get_option( $setting = NULL ) {
	$options = fnpn_get_options();

	if ( is_null( $setting ) || !isset( $options[$setting] ) )
		return false;

	return $options[$setting];
}

/**
 * Get the extension licenses that have been added on the filter 'fnpn_licenses_array'
 * @return array The extensions with license options
 */
function fnpn_get_licenses() {
	$licenses = array();
	$licenses = apply_filters( 'fnpn_licenses_array', $licenses );

	return $licenses;
}

/**
 * Get all the application keys
 * @return array Contains an array of all the application keys
 *
 * The array format is
 * [id (default_key for primary key, numeric increment for additional keys)]
 * 		['name']	=> <App Key Name>
 * 		['app_key']	=> <application key from pushbullet.com>
 */
function fnpn_get_application_keys() {
	$basic_options = fnpn_get_options();
	$additional_keys = get_option( '_fnpn_additional_app_keys' );

	$all_keys = array( 'default_key' => array( 'name' => 'Default Key', 'app_key' => $basic_options['application_key'] ) );

	if ( !empty( $additional_keys ) )
		$all_keys = $all_keys + $additional_keys;

	return $all_keys;
}

/**
 * Given the additional key id, return the application key
 * @param  string $id The application key id from _fnpn_additional_app_keys
 * @return string     The application key to send the notification with
 */
function fnpn_get_application_key_by_id( $id = 'default_key' ) {
	$application_key = false;

	$all_keys = fnpn_get_application_keys();

	if ( !isset( $all_keys[$id] ) )
		return false;

	return $all_keys[$id]['app_key'];
}

/**
 * Given the setting name, return the application key
 * @param  string $setting The setting name associated with the notification being sent
 * @return string          The application key to send the notification with
 */
function fnpn_get_application_key_by_setting( $setting = NULL ) {
	if ( empty( $setting ) )
		return false;

	$current_mappings = get_option( '_fnpn_additional_key_mapping' );
	$mapped_key = $current_mappings[$setting];

	$all_keys = fnpn_get_application_keys();

	if ( !isset( $all_keys[$mapped_key] ) )
		return false;

	return $all_keys[$mapped_key]['app_key'];
}

/**
 * Format the log entry uniformly
 * @param  string $message The message to log
 * @return string          Formatted correctly with the Date and new line wrapping the message
 */
function fnpn_log_entry_format( $message = '' ) {
    if(! isset($message)){
        $message='';
    }
	if ( $message == '' )
		return $message;

    if(is_wp_error($message))
    {
         return date( 'm-d-Y H:i:s' ) . ' -- '. $message->get_error_message() . "\n";
    }
    else
    {
	    return date( 'm-d-Y H:i:s' ) . ' -- '. $message . "\n";
    }
}

function fnpn_startsWith($haystack, $needle)
{
    return $needle === "" || strpos($haystack, $needle) === 0;
}
function fnpn_endsWith($haystack, $needle)
{
    return $needle === "" || substr($haystack, -strlen($needle)) === $needle;
}