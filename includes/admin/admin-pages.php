<?php
/**
 * Display the General settings tab
 * @return void
 */
function fnpn_admin_page() {
	$current = fnpn_get_options();
	$roles = get_editable_roles();
    $users = get_users('orderby=user_login');
    $args_filter_types = array(
        '_builtin' => false,
        'public'=> TRUE
        );

    $post_types = get_post_types( $args_filter_types, 'objects','and');
    $post_types = array_merge($post_types, get_post_types( array('name'=>'post'), 'objects'));
    $post_types = array_merge($post_types, get_post_types( array('name'=>'page'), 'objects'));

	if ( isset( $_GET['settings-updated'] ) && $current['plugin_updates'] == false && $timestamp = wp_next_scheduled( 'fnpn_plugin_update_check' ) ) {
		wp_unschedule_event( $timestamp, 'fnpn_plugin_update_check' );
	}
	?>
	<form method="post" action="options.php">
		<?php wp_nonce_field( 'fnpn-update-options' ); ?>
		<table class="form-table">
			<tr valign="top">
				<th scope="row"><?php _e( 'API key', FNPN_CORE_TEXT_DOMAIN ); ?><br /><span style="font-size: x-small;"><?php _e( 'Available from within your Pushbullet Account', FNPN_CORE_TEXT_DOMAIN ); ?></span></th>
				<td>
				<input id="applicationkey-textbox" size="50" type="text" name="fnpn_pushbullet_notifications_settings[application_key]" placeholder="<?php _e( 'Enter API Key', FNPN_CORE_TEXT_DOMAIN ); ?>" <?php if ( $current['application_key'] != '' ) {?>value="<?php echo htmlspecialchars( $current['application_key'] ); ?>"<?php ;}?> />
				<br />
				<?php _e( 'To view your API key visit <a href="https://www.pushbullet.com/account" target="_blank">https://www.pushbullet.com/account</a>', FNPN_CORE_TEXT_DOMAIN ); ?>
				</td>
			</tr>

            <tr valign="top">
				<th scope="row"><?php _e( 'Device iden', FNPN_CORE_TEXT_DOMAIN ); ?></th>
				<td>
                <select id="deviceiden-select" style="display: none"></select>
                <div id="deviceiden-block">
				    <input id="deviceiden-textbox" size="50" type="text" name="fnpn_pushbullet_notifications_settings[device_iden]" placeholder="<?php _e( 'Enter device iden', FNPN_CORE_TEXT_DOMAIN ); ?>" <?php if ( $current['device_iden'] != '' ) {?>value="<?php echo htmlspecialchars( $current['device_iden'] ); ?>"<?php ;}?> />
				    <br />
				    <?php _e( 'To view your device iden visit <a href="https://api.pushbullet.com/v2/devices" target="_blank">https://api.pushbullet.com/v2/devices</a> username is your API key', FNPN_CORE_TEXT_DOMAIN ); ?>
                </div>
                 <input id="test-button" type="button" class="button-primary" value="<?php _e( 'Test notification', FNPN_CORE_TEXT_DOMAIN ) ?>" />
				</td>
			</tr>
			
			<tr valign="top">
				<th scope="row"><?php _e( 'Send Notifications For:', FNPN_CORE_TEXT_DOMAIN ); ?><br /><span style="font-size: x-small;"><?php _e( 'What do you want to be notified of?', FNPN_CORE_TEXT_DOMAIN ); ?></span></th>
				<td>
					<input id="new-user-checkbox" type="checkbox" name="fnpn_pushbullet_notifications_settings[new_user]" value="1" <?php checked( $current['new_user'], '1', true ); ?> /> 
					<label for="fnpn_pushbullet_notifications_settings[new_user]"><?php _e( 'New Users', FNPN_CORE_TEXT_DOMAIN ); ?></label> <?php fnpn_display_application_key_dropdown('new_user'); ?><br />
                    <div id="new-user-detail" style="padding-left: 25px;<?php if ( $current['new_user'] == 0 ) : ?>display: none<?php endif; ?>">
					    
                        <div>
                        <strong><?php _e( 'Title', FNPN_CORE_TEXT_DOMAIN ); ?></strong><br />
				        <input size="50" type="text" name="fnpn_pushbullet_notifications_settings[new_user_title]" <?php if ( $current['new_user_title'] != '' ) {?>value="<?php echo htmlspecialchars( $current['new_user_title'] ); ?>"<?php ;}?> />
				        <br />
				        <span style="font-size: x-small;"><?php _e( '%blogname% , %accountname%', FNPN_CORE_TEXT_DOMAIN ); ?></span>
                         <br />
                         <br />
				        </div>

                        <div>
                        <strong><?php _e( 'Message', FNPN_CORE_TEXT_DOMAIN ); ?></strong><br />
				        <input size="50" type="text" name="fnpn_pushbullet_notifications_settings[new_user_message]" <?php if ( $current['new_user_message'] != '' ) {?>value="<?php echo htmlspecialchars( $current['new_user_message'] ); ?>"<?php ;}?> />
				        <br />
				        <span style="font-size: x-small;"><?php _e( '%blogname% , %accountname%', FNPN_CORE_TEXT_DOMAIN ); ?></span>
				        </div>
                     </div>
                     <br />

                    <input id="login-user-checkbox" type="checkbox" name="fnpn_pushbullet_notifications_settings[login_user]" value="1" <?php checked( $current['login_user'], '1', true ); ?> /> 
					<label for="fnpn_pushbullet_notifications_settings[login_user]"><?php _e( 'Login users', FNPN_CORE_TEXT_DOMAIN ); ?></label> <?php fnpn_display_application_key_dropdown('login_user'); ?><br />
                    <div id="login-user-detail" style="padding-left: 25px;<?php if ( $current['login_user'] == 0 ) : ?>display: none<?php endif; ?>">
					     <strong><?php _e( 'User to Notify', FNPN_CORE_TEXT_DOMAIN ); ?></strong><br />
					    <?php foreach( $users as $user_login => $user ) : ?>
						    <input type="checkbox" name="fnpn_pushbullet_notifications_settings[login_user_users][<?php echo $user->ID; ?>]" value="1" <?php checked( $current['login_user_users'][$user->ID], '1', true ); ?> /> 
						    <label for="fnpn_pushbullet_notifications_settings[login_user_users][<?php echo $user->ID; ?>]"><?php echo $user->user_login; ?></label><br />	
					    <?php endforeach; ?>
					    <br />

                        <div>
                        <strong><?php _e( 'Title', FNPN_CORE_TEXT_DOMAIN ); ?></strong><br />
				        <input size="50" type="text" name="fnpn_pushbullet_notifications_settings[login_user_title]" <?php if ( $current['login_user_title'] != '' ) {?>value="<?php echo htmlspecialchars( $current['login_user_title'] ); ?>"<?php ;}?> />
				        <br />
				        <span style="font-size: x-small;"><?php _e( '%blogname% , %accountname%', FNPN_CORE_TEXT_DOMAIN ); ?></span>
                         <br />
                         <br />
				        </div>

                        <div>
                        <strong><?php _e( 'Message', FNPN_CORE_TEXT_DOMAIN ); ?></strong><br />
				        <input size="50" type="text" name="fnpn_pushbullet_notifications_settings[login_user_message]" <?php if ( $current['login_user_message'] != '' ) {?>value="<?php echo htmlspecialchars( $current['login_user_message'] ); ?>"<?php ;}?> />
				        <br />
				        <span style="font-size: x-small;"><?php _e( '%blogname% , %accountname%', FNPN_CORE_TEXT_DOMAIN ); ?></span>
				        </div>
                     </div>
                     <br />

                    <input id="new-type-checkbox" type="checkbox" name="fnpn_pushbullet_notifications_settings[new_type]" value="1" <?php checked( $current['new_type'], '1', true ); ?> /> 
					<label for="fnpn_pushbullet_notifications_settings[new_type]"><?php _e( 'New items are Published', FNPN_CORE_TEXT_DOMAIN ); ?></label>  <?php fnpn_display_application_key_dropdown('new_type'); ?><br />
                    <div id="new-post-detail" style="padding-left: 25px;<?php if ( $current['new_type'] == 0 ) : ?>display: none<?php endif; ?>">
					    <p id="new-post-roles">
					    <strong><?php _e( 'Roles to Notify', FNPN_CORE_TEXT_DOMAIN ); ?></strong><br />
					    <?php foreach( $roles as $role_id => $role ) : ?>
						    <input type="checkbox" name="fnpn_pushbullet_notifications_settings[new_post_roles][<?php echo $role_id; ?>]" value="1" <?php checked( $current['new_post_roles'][$role_id], '1', true ); ?> /> 
						    <label for="fnpn_pushbullet_notifications_settings[new_post_roles][<?php echo $role_id; ?>]"><?php echo $role['name']; ?></label><br />	
					    <?php endforeach; ?>
					    <br />
					    </p>

                        <p id="new-post-types">
					    <strong><?php _e( 'Types to Notify', FNPN_CORE_TEXT_DOMAIN ); ?></strong><br />
					    <?php foreach ( $post_types  as $post_type ) { ?>
						    <input type="checkbox" name="fnpn_pushbullet_notifications_settings[new_post_types][<?php echo $post_type->name; ?>]" value="1" <?php checked( $current['new_post_types'][$post_type->name], '1', true ); ?> /> 
						    <label for="fnpn_pushbullet_notifications_settings[new_post_types][<?php echo $post_type->name; ?>]"><?php echo $post_type->labels->singular_name; ?></label><br />	
					    <?php } ?>
					    <br />
					    </p>

                        <div>
                        <strong><?php _e( 'Title', FNPN_CORE_TEXT_DOMAIN ); ?></strong><br />
				        <input size="50" type="text" name="fnpn_pushbullet_notifications_settings[new_post_title]" <?php if ( $current['new_post_title'] != '' ) {?>value="<?php echo htmlspecialchars( $current['new_post_title'] ); ?>"<?php ;}?> />
				        <br />
				        <span style="font-size: x-small;"><?php _e( '%blogname% , %author%, %postname%,%posturl%', FNPN_CORE_TEXT_DOMAIN ); ?></span>
                         <br />
                            <br />
				        </div>

                        <div>
                        <strong><?php _e( 'Message', FNPN_CORE_TEXT_DOMAIN ); ?></strong><br />
				        <input size="50" type="text" name="fnpn_pushbullet_notifications_settings[new_post_message]" <?php if ( $current['new_post_message'] != '' ) {?>value="<?php echo htmlspecialchars( $current['new_post_message'] ); ?>"<?php ;}?> />
				        <br />
				        <span style="font-size: x-small;"><?php _e( '%blogname% , %author%, %oostname%,%posturl%', FNPN_CORE_TEXT_DOMAIN ); ?></span>
				        </div>
                     </div>
                    <br />

                    <input type="checkbox" name="fnpn_pushbullet_notifications_settings[xmlrpc_publish_post]" value="1" <?php checked( $current['xmlrpc_publish_post'], '1', true ); ?> /> 
					<label for"fnpn_pushbullet_notifications_settings[xmlrpc_publish_post]"><?php _e( 'Publish by xmlrpc', FNPN_CORE_TEXT_DOMAIN ); ?></label>  <?php fnpn_display_application_key_dropdown('xmlrpc_publish_post'); ?><br />
                     <br />

                    <input id="new-comment-checkbox" type="checkbox" name="fnpn_pushbullet_notifications_settings[new_comment]" value="1" <?php checked( $current['new_comment'], '1', true ); ?> /> 
					<label for"fnpn_pushbullet_notifications_settings[new_comment]"><?php _e( 'New Comments', FNPN_CORE_TEXT_DOMAIN ); ?></label>  <?php fnpn_display_application_key_dropdown('new_comment'); ?><br />
                    <div id="new-comment-detail" style="padding-left: 25px;<?php if ( $current['new_comment'] == 0 ) : ?>display: none<?php endif; ?>">
					    
                        <div>
                        <strong><?php _e( 'Title', FNPN_CORE_TEXT_DOMAIN ); ?></strong><br />
				        <input size="50" type="text" name="fnpn_pushbullet_notifications_settings[new_comment_title]" <?php if ( $current['new_comment_title'] != '' ) {?>value="<?php echo htmlspecialchars( $current['new_comment_title'] ); ?>"<?php ;}?> />
				        <br />
				        <span style="font-size: x-small;"><?php _e( '%blogname% , %commenttype% , %commentauthor% , %postname%', FNPN_CORE_TEXT_DOMAIN ); ?></span>
                         <br />
                         <br />
				        </div>

                        <div>
                        <strong><?php _e( 'Message', FNPN_CORE_TEXT_DOMAIN ); ?></strong><br />
				        <input size="50" type="text" name="fnpn_pushbullet_notifications_settings[new_comment_message]" <?php if ( $current['new_comment_message'] != '' ) {?>value="<?php echo htmlspecialchars( $current['new_comment_message'] ); ?>"<?php ;}?> />
				        <br />
				        <span style="font-size: x-small;"><?php _e( '%blogname% , %commenttype% , %commentauthor% , %postname%', FNPN_CORE_TEXT_DOMAIN ); ?></span>
				        </div>
                     </div>
                     <br />

					<input type="checkbox" name="fnpn_pushbullet_notifications_settings[password_reset]" value="1" <?php checked( $current['password_reset'], '1', true ); ?> /> 
					<label for="fnpn_pushbullet_notifications_settings[password_reset]"><?php _e( 'Notify users when password resets are requested for their accounts?', FNPN_CORE_TEXT_DOMAIN ); ?></label>  <?php fnpn_display_application_key_dropdown('password_reset'); ?><br />
                     <br />
					<input type="checkbox" name="fnpn_pushbullet_notifications_settings[core_update]" value="1" <?php checked( $current['core_update'], '1', true ); ?> /> 
					<label for="fnpn_pushbullet_notifications_settings[core_update]"><?php _e( 'WordPress Core Update is Available', FNPN_CORE_TEXT_DOMAIN ); ?></label>  <?php fnpn_display_application_key_dropdown('core_update'); ?><br />
                     <br />
					<input type="checkbox" name="fnpn_pushbullet_notifications_settings[plugin_updates]" value="1" <?php checked( $current['plugin_updates'], '1', true ); ?> /> 
					<label for="fnpn_pushbullet_notifications_settings[plugin_updates]"><?php _e( 'Plugin & Theme Updates are Available', FNPN_CORE_TEXT_DOMAIN ); ?></label>  <?php fnpn_display_application_key_dropdown('plugin_updates'); ?><br />
				</td>
			</tr>

			<?php do_action( 'fnpn_notification_checkbox_filter' ); ?>

			<tr valign="top">
				<th scope="row"><?php _e( 'Advanced &amp; Debug Options:', FNPN_CORE_TEXT_DOMAIN ); ?><br /><span style="font-size: x-small;"><?php _e( 'With great power, comes great responsiblity.', FNPN_CORE_TEXT_DOMAIN ); ?></span></th>
				<td>
					<input type="checkbox" name="fnpn_pushbullet_notifications_settings[logging]" value="1" <?php checked( $current['logging'], '1', true ); ?> /> 
					<label for="fnpn_pushbullet_notifications_settings[logging]"><?php _e( 'Enable Logging', FNPN_CORE_TEXT_DOMAIN ); ?></label><br />
					<small><?php _e( 'Enable or Disable Logging', FNPN_CORE_TEXT_DOMAIN ); ?></small><br />
				</td>
			</tr>

            <tr valign="top">
				<th scope="row"><?php _e( 'Send e-mail on fail:', FNPN_CORE_TEXT_DOMAIN ); ?></th>
				<td>
					<input id="send-error" type="checkbox" name="fnpn_pushbullet_notifications_settings[error_send]" value="1" <?php checked( $current['error_send'], '1', true ); ?> /> 
					<label for="fnpn_pushbullet_notifications_settings[error_send]"><?php _e( 'Send e-mail', FNPN_CORE_TEXT_DOMAIN ); ?></label><br />
					<div id="send-error-detail" style="padding-left: 25px;<?php if ( $current['error_send'] == 0 ) : ?>display: none<?php endif; ?>">
                        <input size="50" type="text" name="fnpn_pushbullet_notifications_settings[error_send_email]" <?php if ( $current['error_send_email'] != '' ) {?>value="<?php echo htmlspecialchars( $current['error_send_email'] ); ?>"<?php ;}?> />
                    </div>
				</td>
			</tr>


			<input type="hidden" name="action" value="update" />
			<?php $page_options = apply_filters( 'fnpn_settings_page_options', array( 'fnpn_pushbullet_notifications_settings' ) ); ?>
			<input type="hidden" name="page_options" value="<?php echo implode( ',', $page_options ); ?>" />

			<?php settings_fields( 'fnpn-update-options' ); ?>
		</table>
		<input type="submit" class="button-primary" value="<?php _e( 'Save Changes', FNPN_CORE_TEXT_DOMAIN ) ?>" />
	</form>
	<div style="margin-top: 10px;">
		<sup>&dagger;</sup> <a href="#" onClick="jQuery( '#cron-help' ).toggle(); return false;"><?php _e( 'Not receiving reports?', FNPN_CORE_TEXT_DOMAIN ); ?></a><br />
		<div id="cron-help" style="display:none">
			&nbsp;&nbsp;&nbsp;&nbsp;<?php _e( 'This feature uses WP-Cron to run. If your site doesn\'t get much traffic, the scheduled task to send your reports might not execute at the specified time. There are 2 options:', FNPN_CORE_TEXT_DOMAIN ); ?><br />
			&nbsp;&nbsp;&nbsp;&nbsp;<?php printf( __( '1. You may need to use the <a href="%s" target="_blank">Improved Cron</a> plugin to help scheduled tasks run.', FNPN_CORE_TEXT_DOMAIN ), 'http://wordpress.org/plugins/improved-cron/' ); ?><br />
			&nbsp;&nbsp;&nbsp;&nbsp;<?php _e( '2. If you have access to create cron jobs and know how, you can use the following cron to execute wp-cron.php every hour.', FNPN_CORE_TEXT_DOMAIN ); ?><br />
			&nbsp;&nbsp;&nbsp;&nbsp;<code>0 */1 * * * GET <?php echo home_url(); ?>/wp-cron.php</code>
		</div>
	</div>
	<?php
}

/**
 * Display the Logs tab
 * @return void
 */
function fnpn_display_logs() {
	if ( isset( $_GET['clear_logs'] ) && $_GET['clear_logs'] == 'true' ) {
		check_admin_referer( 'fnpn_clear_logs' );
		update_option( 'fnpn_logs', '' );
		$logs_cleared = true;
	}

	if ( isset( $logs_cleared ) && $logs_cleared ) {
	?><div id="setting-error-settings_updated" class="updated settings-error"><p><strong><?php _e( 'Logs Cleared', FNPN_CORE_TEXT_DOMAIN ); ?></strong></p></div><?php
	}

	$current = fnpn_get_options();
	if ( $current['logging'] == false )
		printf( '<div class="error"> <p> %s </p> </div>', esc_html__( 'Logging currently disabled.', FNPN_CORE_TEXT_DOMAIN ) );
	?>
	<a class="button gray" href="<?php echo wp_nonce_url( '?page=pushbullet-notifications&tab=logs&clear_logs=true', 'fnpn_clear_logs' ); ?>"><?php _e( 'Clear Logs', FNPN_CORE_TEXT_DOMAIN ); ?></a>
	<h3><?php _e( 'Logs:', FNPN_CORE_TEXT_DOMAIN ); ?></h3>
<pre>
<?php // No indent to preserve formatting
echo get_option( 'fnpn_logs' );
?>
</pre>
	<?php
}

/**
 * Display the Licenses tab
 * @return void
 */
function fnpn_display_licenses() {
	$licenses_page_options = fnpn_get_licenses();
	if ( isset( $_POST['action'] ) ) {
		foreach ( $licenses_page_options as $license ) {
			$license_key = $_POST[$license];
			update_option( $license, $license_key );
		}
		printf( '<div class="updated settings-error"> <p> %s </p> </div>', __( 'Licenses Saved.', FNPN_CORE_TEXT_DOMAIN ) );
	}
	?>
	<form method="post" action="<?php admin_url( 'options-general.php?page=pushbullet-notifications&tab=licenses' ); ?>">
		<?php wp_nonce_field( 'fnpn-update-licenses' ); ?>
		<table class="form-table">

			<?php do_action( 'fnpn_notification_licenses_page' ); ?>

			<input type="hidden" name="action" value="update" />
			<input type="hidden" name="page_options" value="<?php echo implode(',', $licenses_page_options ); ?>" />

			<?php settings_fields( 'fnpn-update-licenses' ); ?>

		</table>
		<input type="submit" class="button-primary" value="<?php _e( 'Save Licenses', FNPN_CORE_TEXT_DOMAIN ) ?>" />
	</form>
	<?php
}

/**
 * Display the System Info Tab
 * @return void
 */
function fnpn_display_sysinfo() {
	global $wpdb;
	$options = fnpn_get_options();
	if ( $options['application_key'] != false )
		$options['application_key'] = '[removed for display]';

	if ( $options['api_key'] != false )
		$options['api_key'] = '[removed for display]';

	?>
	<textarea style="font-family: Menlo, Monaco, monospace; white-space: pre" onclick="this.focus();this.select()" readonly cols="150" rows="35">
SITE_URL:                 <?php echo site_url() . "\n"; ?>
HOME_URL:                 <?php echo home_url() . "\n"; ?>

FNPN Version:             <?php echo FNPN_VERSION . "\n"; ?>
WordPress Version:        <?php echo get_bloginfo( 'version' ) . "\n"; ?>

PUSHBULLET NOTIFICATIONS SETTINGS:
<?php
foreach ( $options as $name => $value ) {
if ( $value == false )
	$value = 'false';

if ( $value == '1' )
	$value = 'true';

echo $name . ': ' . $value . "\n";
}
?>

ACTIVE PLUGINS:
<?php
$plugins = get_plugins();
$active_plugins = get_option( 'active_plugins', array() );

foreach ( $plugins as $plugin_path => $plugin ) {
	// If the plugin isn't active, don't show it.
	if ( ! in_array( $plugin_path, $active_plugins ) )
		continue;

echo $plugin['Name']; ?>: <?php echo $plugin['Version'] ."\n";

}
?>

CURRENT THEME:
<?php
if ( get_bloginfo( 'version' ) < '3.4' ) {
	$theme_data = get_theme_data( get_stylesheet_directory() . '/style.css' );
	echo $theme_data['Name'] . ': ' . $theme_data['Version'];
} else {
	$theme_data = wp_get_theme();
	echo $theme_data->Name . ': ' . $theme_data->Version;
}
?>


Multi-site:               <?php echo is_multisite() ? 'Yes' . "\n" : 'No' . "\n" ?>

ADVANCED INFO:
PHP Version:              <?php echo PHP_VERSION . "\n"; ?>
MySQL Version:            <?php echo mysql_get_server_info() . "\n"; ?>
Web Server Info:          <?php echo $_SERVER['SERVER_SOFTWARE'] . "\n"; ?>

PHP Memory Limit:         <?php echo ini_get( 'memory_limit' ) . "\n"; ?>
PHP Post Max Size:        <?php echo ini_get( 'post_max_size' ) . "\n"; ?>
PHP Time Limit:           <?php echo ini_get( 'max_execution_time' ) . "\n"; ?>

WP_DEBUG:                 <?php echo defined( 'WP_DEBUG' ) ? WP_DEBUG ? 'Enabled' . "\n" : 'Disabled' . "\n" : 'Not set' . "\n" ?>

WP Table Prefix:          <?php echo "Length: ". strlen( $wpdb->prefix ); echo " Status:"; if ( strlen( $wpdb->prefix )>16 ) {echo " ERROR: Too Long";} else {echo " Acceptable";} echo "\n"; ?>

Show On Front:            <?php echo get_option( 'show_on_front' ) . "\n" ?>
Page On Front:            <?php $id = get_option( 'page_on_front' ); echo get_the_title( $id ) . ' #' . $id . "\n" ?>
Page For Posts:           <?php $id = get_option( 'page_on_front' ); echo get_the_title( $id ) . ' #' . $id . "\n" ?>

Session:                  <?php echo isset( $_SESSION ) ? 'Enabled' : 'Disabled'; ?><?php echo "\n"; ?>
Session Name:             <?php echo esc_html( ini_get( 'session.name' ) ); ?><?php echo "\n"; ?>
Cookie Path:              <?php echo esc_html( ini_get( 'session.cookie_path' ) ); ?><?php echo "\n"; ?>
Save Path:                <?php echo esc_html( ini_get( 'session.save_path' ) ); ?><?php echo "\n"; ?>
Use Cookies:              <?php echo ini_get( 'session.use_cookies' ) ? 'On' : 'Off'; ?><?php echo "\n"; ?>
Use Only Cookies:         <?php echo ini_get( 'session.use_only_cookies' ) ? 'On' : 'Off'; ?><?php echo "\n"; ?>

UPLOAD_MAX_FILESIZE:      <?php if ( function_exists( 'phpversion' ) ) echo ini_get( 'upload_max_filesize' ); ?><?php echo "\n"; ?>
POST_MAX_SIZE:            <?php if ( function_exists( 'phpversion' ) ) echo ini_get( 'post_max_size' ); ?><?php echo "\n"; ?>
WordPress Memory Limit:   <?php echo WP_MEMORY_LIMIT; ?><?php echo "\n"; ?>
DISPLAY ERRORS:           <?php echo ( ini_get( 'display_errors' ) ) ? 'On (' . ini_get( 'display_errors' ) . ')' : 'N/A'; ?><?php echo "\n"; ?>
FSOCKOPEN:                <?php echo ( function_exists( 'fsockopen' ) ) ? __( 'Your server supports fsockopen.', 'edd' ) : __( 'Your server does not support fsockopen.', 'edd' ); ?><?php echo "\n"; ?>
	</textarea>
	<?php
}

/**
 * Display the Additional Application Keys Tab
 * @return void
 */
function fnpn_display_additional_keys() {
	if ( isset( $_POST['action'] ) && $_POST['action'] == 'update' ) {
		$new_keys = array();
		$has_errors = false;
		
		if ( !empty( $_POST['additional_keys'] ) ) {
			foreach ( $_POST['additional_keys'] as $array_key => $key_attrs ) {
				if ( !empty( $key_attrs['name'] ) && !empty( $key_attrs['app_key'] ) ) {
					$new_keys[$array_key] = array( 'name' => $key_attrs['name'], 'app_key' => $key_attrs['app_key'] );
				} else {
					$has_errors = true;
				}
			}
		}

		update_option( '_fnpn_additional_app_keys', $new_keys );
		
		if ( !$has_errors ) {
			printf( '<div class="updated settings-error"> <p> %s </p> </div>', __( 'Application Keys Saved.', FNPN_CORE_TEXT_DOMAIN ) );
		} else {
			printf( '<div class="error settings-error"> <p> %s </p> </div>', __( 'One or more of your keys was not saved. Please verify your Name and Key.', FNPN_CORE_TEXT_DOMAIN ) );
		}
	}
	$additional_keys = get_option( '_fnpn_additional_app_keys' );
	?>
	<p>
		<?php _e( 'If your site uses more than your alloted API calls per month, you can specify multiple application keys here to help disperse your requests to the Pushbullet API.', FNPN_CORE_TEXT_DOMAIN ); ?>
	</p>
	<p>
		<div id="fnpn-add-new-key" class="button-secondary"><?php _e( 'Add New Key', FNPN_CORE_TEXT_DOMAIN ); ?></div>
		<a id="fnpn-add-new-key" class="button-secondary" href="https://pushbullet.com/apps/build" target="_blank"><?php _e( 'Create a New Application', FNPN_CORE_TEXT_DOMAIN ); ?></a>
	</p>
	<form method="post" action="<?php admin_url( 'options-general.php?page=pushbullet-notifications&tab=application_keys' ); ?>" id="fnpn_additional_keys_form">
		<?php do_action( 'fnpn_additional_app_keys_settings_after' ); ?>
		<?php wp_nonce_field( 'fnpn-update-keys' ); ?>
		<table class="wp-list-table widefat fixed posts">
			<thead>
				<tr>
					<th width="5%"><?php _e('ID', FNPN_CORE_TEXT_DOMAIN ); ?></th>
					<th width="25%"><?php _e('Key Name', FNPN_CORE_TEXT_DOMAIN ); ?></th>
					<th><?php _e('Application Key', FNPN_CORE_TEXT_DOMAIN ); ?></th>
					<th width="20px"></th>
				</tr>
			</thead>
			<tfoot>
				<tr>
					<th width="5%"><?php _e('ID', FNPN_CORE_TEXT_DOMAIN ); ?></th>
					<th width="25%"><?php _e('Key Name', FNPN_CORE_TEXT_DOMAIN ); ?></th>
					<th><?php _e('Application Key', FNPN_CORE_TEXT_DOMAIN ); ?></th>
					<th width="20px"></th>
				</tr>
			</tfoot>
			<tbody id="fnpn-additional-keys-table-body">
			<?php
			if( !empty( $additional_keys ) ) :
				foreach( $additional_keys as $id => $addtl_key ) : ?>
					<tr class="item">
						<td width="5%"><input type="hidden" name="additional_keys[<?php echo $id; ?>]" value="<?php echo $id; ?>" /><?php echo $id; ?></td>
						<td width="25%"><input type="text" name="additional_keys[<?php echo $id; ?>][name]" value="<?php echo $addtl_key['name']; ?>" /></td>
						<td><input type="text" size="75" name="additional_keys[<?php echo $id; ?>][app_key]" value="<?php echo $addtl_key['app_key']; ?>" /></td>
						<th width="20px"><div class="fnpn-delete-item" style="background-image: url('/wp-admin/images/no.png');height:16px;width:16px"></div></th>
					</tr>
					<?php
				endforeach;
			else : ?>
				<tr id="no-rows-notice">
					<td colspan="4"><?php _e('No additinal keys found', FNPN_CORE_TEXT_DOMAIN ); ?></td>
				</tr>
				<?php 
			endif; 
			?>	
			</tbody>
		</table>
		<br />
		<?php do_action( 'fnpn_additional_app_keys_settings_after' ); ?>
		<?php settings_fields( apply_filters( 'fnpn_additional_keys_settings_fields', 'fnpn-update-keys' ) ); ?>
		<input type="hidden" name="action" value="update" />

		<?php $page_options = apply_filters( 'fnpn_additional_keys_page_options', array( '_fnpn_additional_app_keys' ) ); ?>
		<input type="hidden" name="page_options" value="<?php echo implode(',', $page_options ); ?> " />
		
		<input type="submit" class="button-primary" value="<?php _e( 'Save Keys', FNPN_CORE_TEXT_DOMAIN ) ?>" />
	</form>
	<?php
}
