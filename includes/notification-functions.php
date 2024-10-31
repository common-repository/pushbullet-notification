<?php
/**
 * The main function to send notifications
 * @param  array $pushbullet_args Holds the API Arguments used for Pushbullet.com
 * @return void
 */
function fnpn_send_notification( $pushbullet_args ) {
	$logentry = '';
	$invalid = false;
	$options = fnpn_get_options();

	$defaults = array(
		'token' => fnpn_get_application_key_by_id(),
		'user' => NULL );

	$api_args = wp_parse_args( $pushbullet_args, $defaults );

	// Check that we have the required elements
	if ( !isset( $api_args['title'] ) ) {
		$logentry .= fnpn_log_entry_format( '****** Missing Title ******' );
		$invalid = true;
	}

	if ( !isset( $api_args['message'] ) ) {
		$logentry .= fnpn_log_entry_format( '****** Missing Message ******' );
		$invalid = true;
	}

    // log all args
    if ( $options['logging'] == 1 ) {
		foreach ( $api_args as $key => $value ) {
			 $logentry .=fnpn_log_entry_format( $key  . '=' . $value);
		}
    }

	if ( !$invalid ) {
		
       $noteType='note';
       $url='';

        if ( isset( $api_args['url']) && $api_args['url'] !=''){
             $url=$api_args['url'];
        }

       if ( isset( $api_args['message']) && $api_args['message'] !='' && filter_var($api_args['message'], FILTER_VALIDATE_URL))
       {
            $noteType='link';
            $url=$api_args['message'];
       }

       $req_args = array(
       'headers' => array(
            'Authorization' => 'Basic ' . base64_encode( $options['application_key'].':')
            ),
       'timeout' => 50,
       'sslverify' =>FALSE,
       'method' => 'post',
       'body'=>array(
                    'type' => $noteType,
                    'device_id'=>$options['device_id'], 
                    'device_iden'=>$options['device_iden'], 
                    'title' => $api_args['title'],
                    'body'=>$api_args['message'],
                    'url'=>$url)
       );

       if ( !isset( $api_args['device_iden'] ) ) {
		// Api v2
        $response = wp_remote_post( 'https://api.pushbullet.com/v2/pushes', $req_args );
       }
       else
       {
        // Api v 1
		$response = wp_remote_post( 'https://api.pushbullet.com/api/pushes', $req_args );
       }
        
        if ( $options['error_send'] == 1 && is_wp_error( $response ) ) {
            if (! wp_mail( $options['error_send_email'], $api_args['title'], $api_args['message'])) {		
			    if ( $options['logging'] == 1 ) {
				    $logentry .= fnpn_log_entry_format( '******Email Notification Failed******' );
                }
            }
        }
        

		// From here on out it's just managing logging (if enabled)
		if ( $options['logging'] == 1 ) {
			if ( is_wp_error( $response ) ) { 
				
                $logentry .=fnpn_log_entry_format( 'application_key - ' . $options['application_key'] . ' - title: ' . $api_args['title']. ' - body: ' . $api_args['message'] );
                 $logentry .=fnpn_log_entry_format($response->get_error_message()); 
				$logentry .= fnpn_log_entry_format( '******API Notification Failed******' );

			} elseif ( $response['response']['code'] == 500 ) {
				$logentry .= fnpn_log_entry_format( 'API Response - Internal Server Error 500' );
				$logentry .= fnpn_log_entry_format( 'Typically occurs with non-entitied characters' );
			} else {
                 if ( !isset( $api_args['device_iden'] ) ) {
				    $logentry .= fnpn_log_entry_format( 'API V 2- ' . $options['application_key'] . ' | device iden - ' . $options['device_iden'] . ' - ' . $api_args['title'] );
                 }
                 else
                 {
                     $logentry .= fnpn_log_entry_format( 'API V 1- ' . $options['application_key'] . ' | device id - ' . $options['device_id'] . ' - ' . $api_args['title'] );
                 }
			}
		}
	}

	if ( $options['logging'] == 1 ) {
		fnpn_write_to_log($logentry);
	}
}

/*
* @return void
*/
function fnpn_write_to_log($logtext){
    $current_logs = get_option( 'fnpn_logs' );
		$new_logs = $logtext . $current_logs;

		$logs_array = explode( "\n", $new_logs );
		if ( count( $logs_array ) > 100 ) {
			$logs_array = array_slice( $logs_array, 0, 100 );
			$new_logs = implode( "\n", $logs_array );
		}

		update_option( 'fnpn_logs', $new_logs );
}

/**
 * Send Notifications for plugin/theme upgrades
 * @return void
 */
function fnpn_plugin_update_checks() {
	$options = fnpn_get_options();
	require_once ( ABSPATH . 'wp-admin/includes/update.php' );
	require_once ( ABSPATH . 'wp-admin/includes/admin.php' );

	// Default to nothing
	$plugin_count = 0;
	$theme_count  = 0;
	$core_update  = false;

	if ( $options['plugin_updates'] ) {
		// Force an update check
		wp_plugin_update_rows();
		$plugin_updates = get_site_transient( 'update_plugins' );
		$plugin_count 	= count( $plugin_updates->response );

		wp_theme_update_rows();
		$theme_updates  = get_site_transient( 'update_themes' );
		$theme_count	=  count( $theme_updates->response );
	}

	if ( $options['core_update'] ) {
		$core_info      = get_site_transient( 'update_core' );
		$core_update    = version_compare( $core_info->version_checked, $core_info->updates[0]->current, '<' );
	}
	
	if ( empty( $plugin_count ) && empty( $theme_count ) && !$core_update )
		return false;

	$title 		 = apply_filters( 'fnpn_plugin_update_title', get_bloginfo( 'name' ) . ': ' . __( 'Updates Available', FNPN_CORE_TEXT_DOMAIN ) );

	$message  	 = '';
	$core_text   = __( 'WordPress update available', FNPN_CORE_TEXT_DOMAIN );
	$message    .= ( $core_update ) ? $core_text . "\n" : '';

	$plugin_text = _n( 'Plugin has', 'Plugins have', $plugin_count, FNPN_CORE_TEXT_DOMAIN );
	$message 	.= ( !empty( $plugin_count ) ) ? sprintf( __( '%d %s updates', FNPN_CORE_TEXT_DOMAIN ), $plugin_count, $plugin_text ) . "\n" : '';

	$theme_text  = _n( 'Theme has', 'Themes have', $theme_count, FNPN_CORE_TEXT_DOMAIN );
	$message 	.= ( !empty( $theme_count ) ) ? sprintf( __( '%d %s updates', FNPN_CORE_TEXT_DOMAIN ), $theme_count, $theme_text ) : '';
	
	$priority 	 = '1';
	$url 		 = admin_url( 'update-core.php' );
	$url_title	 = 'Update Now';

	$args = array( 'title' => $title, 'message' => $message, 'priority' => $priority, 'url' => $url, 'url_title' => $url_title );

	if ( $options['multiple_keys'] )
		$args['token'] = fnpn_get_application_key_by_setting( 'plugin_updates' );

	fnpn_send_notification( $args );
}

function fnpn_user_login($user_login, $user){
    $the_user = get_user_by('login', $user_login);
    $id_user = $the_user->ID;
    $sendNotify=false;
    $login_user_users = fnpn_get_option( 'login_user_users' );
    
	$config_users = apply_filters('fnpn_user_login_users', $login_user_users );
    if(isset($config_users))
    {
        foreach ( $config_users as $key => $value ) {
          if($key==$id_user){
              $sendNotify=true;
          }
        }
    }

    if($sendNotify)
    {
        $user_data = get_userdata( $user_id );
        $patterns = array();
        $patterns[0] = '%blogname%';
        $patterns[1] = '%accountname%';
        
        $replacements = array();
        $replacements[0] = get_bloginfo('name');
        $replacements[1] = $user_login;
        
        $title = str_ireplace($patterns, $replacements, fnpn_get_option('login_user_title'));
	    $title = apply_filters( 'fnpn_loginuser_subject', $title , $user_id);
	
	    $message = str_ireplace($patterns, $replacements, fnpn_get_option('login_user_message'));
	    $message = apply_filters( 'fnpn_loginuser_message',$message , $user_id, $user_data->user_login);
	
	    if ( $title === false || $message === false )
		    return;

	    $args = apply_filters( 'fnpn_loginuser_args', array( 'title' => $title, 'message' => $message ), $user_id );

	
	    fnpn_send_notification( $args );
    }
}

/**
 * Send Notifications for new user registrations
 * @param  int $user_id User ID of the new registered account, passed from the action
 * @return void
 */
function fnpn_user_registration( $user_id ) {
	$options = fnpn_get_options();

    $user_data = get_userdata( $user_id );
    $patterns = array();
    $patterns[0] = '%blogname%';
    $patterns[1] = '%accountname%';
        
    $replacements = array();
    $replacements[0] = get_bloginfo('name');
    $replacements[1] = $user_data->user_login ;
        
    $title = str_ireplace($patterns, $replacements, fnpn_get_option('new_user_title'));
	$title = apply_filters( 'fnpn_newuser_subject', $title , $user_id);
	
	$message = str_ireplace($patterns, $replacements, fnpn_get_option('new_user_message'));
	$message = apply_filters( 'fnpn_newuser_message',$message , $user_id, $user_data->user_login);
	
	if ( $title === false || $message === false )
		return;

	$args = apply_filters( 'fnpn_newuser_args', array( 'title' => $title, 'message' => $message ), $user_id );

	if ( $options['multiple_keys'] )
		$args['token'] = fnpn_get_application_key_by_setting( 'new_user' );

	fnpn_send_notification( $args );
}

/**
* Send notifications on xmlrpc publish
*/
function fnpn_xmlrpc_publish_post($post_ID){
    // Don't send if new post is trigger
    $pushbulletNotificationStatut=get_post_meta( $post_ID,'Pushbullet notification',TRUE);
    if( empty($pushbulletNotificationStatut)){
        $post=get_post($post_ID);
  	    $title = apply_filters( 'fnpn_xmlrpc_publish_post_title', get_bloginfo( 'name' ) . ': ' . __( 'xml rpc post', FNPN_CORE_TEXT_DOMAIN ) );
		
	    $author_data = get_userdata( $post->post_author );
	    $author_name = $author_data->display_name;

	    $message = apply_filters( 'fnpn_xmlrpc_publish_post_message', get_the_title( $post->ID ) . __( ' by ', FNPN_CORE_TEXT_DOMAIN ) . $author_name );
	    $url = get_permalink( $post->ID );
	    $url_title = __( 'View Post', FNPN_CORE_TEXT_DOMAIN );

	    $args = array( 'title' => $title,
                        'post_title' =>  get_the_title( $post->ID ),
                        'author' => $author_name,
                        'message' => $message, 
                        'url' => $url, 
                        'url_title' => $url_title);
        $meta = get_post_meta( $post->ID );
        foreach ( $meta as $key => $value ) {
		    $args[$key]=$value;
	    }

        add_post_meta($post_ID, 'Pushbullet notification', 'xml rpc publish');

	    fnpn_send_notification( $args );
    }
}

/**
 * Send Notifications for new comments
 * @param  int $comment_id The ID of the newly submitted comment
 * @return void
 */
function fnpn_new_comment( $comment_id ) {
	$options = fnpn_get_options();
	$comment_data = get_comment( $comment_id );
    $post_data = get_post( $comment_data->comment_post_ID );

	// This is not the comment we're looking for. Move Along.
	if ( $comment_data->comment_approved == 'spam' )
		return;

	switch ( $comment_data->comment_type ) {
		case 'pingback':
			$comment_type = __( 'pingback', FNPN_CORE_TEXT_DOMAIN );
			break;
		case 'trackback':
			$comment_type = __( 'trackback', FNPN_CORE_TEXT_DOMAIN );
			break;
		case 'comment':
		default:
			$comment_type = __( 'comment', FNPN_CORE_TEXT_DOMAIN );
			if ( $comment_data->comment_approved != 0 ){
				$url = get_comment_link( $comment_data );
				$url_title = 'View Comment';
			}
			break;
	}

    $patterns = array();
    $patterns[0] = '%blogname%';
    $patterns[1] = '%commenttype%';
    $patterns[2]='%commentauthor%';
    $patterns[3]='%postname%';
    
    $replacements = array();
    $replacements[0] = get_bloginfo('name');
    $replacements[1] = ucfirst( $comment_type );
    $replacements[2]=$comment_data->comment_author;
    $replacements[3]=$post_data->post_title;

    $title = apply_filters( 'fnpn_newcomment_subject',str_ireplace($patterns, $replacements, fnpn_get_option('new_comment_title')));
	
    $message = apply_filters( 'fnpn_newcomment_message',str_ireplace($patterns, $replacements, fnpn_get_option('new_comment_message')));

	// Notify the Admin User
	$args = array( 'title' => $title, 'message' => $message );

	if ( isset( $url ) && isset( $url_title ) ) {
		$args['url'] = $url;
		$args['url_title'] = $url_title;
	}

	if ( $options['multiple_keys'] )
		$args['token'] = fnpn_get_application_key_by_setting( 'new_comment' );

	fnpn_send_notification( $args );

	// Check if we should notify the author as well
	if ( $options['notify_authors'] ) {
		$author_user_key = get_user_meta( $post_data->post_author, 'fnpn_user_key', true );
		if ( $author_user_key != '' && $author_user_key != $options['api_key'] ) { // Only send if the user has a key and it's not the same as the admin key
			// Notify the Author their post has a comment
			$args['user'] = $author_user_key;

			if ( $options['multiple_keys'] )
				$args['token'] = fnpn_get_application_key_by_setting( 'notify_authors' );

			fnpn_send_notification( $args );
		}
	}
}

/**
 * Send notifications for lost password requests
 * @return void
 */
function fnpn_lost_password_request() {
	if ( !empty( $_POST['user_login'] ) ) {
		if ( strpos( $_POST['user_login'], '@' ) ) {
			$user_data = get_user_by( 'email', trim( $_POST['user_login'] ) );
		} else {
			$login = trim( $_POST['user_login'] );
			$user_data = get_user_by( 'login', $login );
		}
	}

	if ( !empty( $user_data ) ) {
		$user_pushbullet_key = get_user_meta( $user_data->data->ID, 'fnpn_user_key', true );
		if ( $user_pushbullet_key != '' ) {
			$options = fnpn_get_options();
			$title = apply_filters( 'fnpn_password_request_subject', get_bloginfo( 'name' ) . ': ' . __( 'Password Reset Request', FNPN_CORE_TEXT_DOMAIN ) );
			$message = apply_filters( 'fnpn_password_request_message', sprintf( __( 'A password reset request was made for your account. If this was not you pelase verify your account is secure.', FNPN_CORE_TEXT_DOMAIN ), $user_data->data->user_login ) );

			$args = array( 'title' => $title, 'message' => $message, 'user' => $user_pushbullet_key, 'priority' => 1 );

			if ( $options['multiple_keys'] )
				$args['token'] = fnpn_get_application_key_by_setting( 'password_reset' );

			fnpn_send_notification( $args );
		}
	}
}

/**
 * Fires when a new blog post is moved into the published status
 * @param  string $new_status Status the blog post is moving to
 * @param  string $old_status Previous post status
 * @param  object $post       The Post Object
 * @return void             
 */
function fnpn_post_published( $new_status, $old_status, $post ) {
    $new_post_types = fnpn_get_option( 'new_post_types' );
    $allowed_post_types=array();
	$config_post_types = apply_filters( 'fnpn_post_publish_types', $new_post_types );
    if(isset($config_post_types))
    {
        foreach ( $config_post_types as $key => $value ) {
          $allowed_post_types[]=$key;
        }
    }

	// Only do this when a post transitions to being published
	if ( in_array( $post->post_type, $allowed_post_types ) && $new_status == 'publish' && $old_status != 'publish' ) {
		
        $author_data = get_userdata( $post->post_author );
		$author_name = $author_data->display_name;

        $patterns = array();
        $patterns[0] = '%blogname%';
        $patterns[1] = '%author%';
        $patterns[2] = '%postname%';
        $patterns[3] = '%posturl%';

        $replacements = array();
        $replacements[0] = get_bloginfo('name');
        $replacements[1] = $author_name;
        $replacements[2] = get_the_title( $post->ID );
        $replacements[3] = get_permalink( $post->ID );

        $title = apply_filters( 'fnpn_new_post_title',str_ireplace($patterns, $replacements, fnpn_get_option('new_post_title')));

		$message = apply_filters( 'fnpn_new_post_message',str_ireplace($patterns, $replacements, fnpn_get_option('new_post_message')));
		
        $args = array( 'title' => $title,
                        'message' => $message);

		$new_post_roles = fnpn_get_option( 'new_post_roles' );
		$user_array = array();

		foreach ( $new_post_roles as $role => $value ) {
			$user_args = array( 'role' => $role, 'fields' => 'ID' );
			$users = get_users( $user_args );

			$user_array = array_unique( array_merge( $users, $user_array ) );
		}

		$super_admins = array();
		if ( defined( 'MULTISITE' ) && MULTISITE ) {
			$super_admin_logins = get_super_admins();
			foreach ( $super_admin_logins as $super_admin_login ) {
				$user = get_user_by( 'login', $super_admin_login );
				if ( $user )
					$super_admins[] = $user->ID;
			}
		}

		$users_to_alert = array_unique( array_merge( $user_array, $super_admins ) );
		$current_user = wp_get_current_user();
		// Unset the Post Author for non-scheduled posts
		if ( $old_status !== 'future' && ( $key = array_search( $post->post_author, $users_to_alert ) ) !== false && $current_user->ID == $post->post_author )
			unset( $users_to_alert[$key] );

		$options = fnpn_get_options();
		// Add the default admin key from settings if it's different than the authors
		if ( get_user_meta( $post->post_author, 'fnpn_user_key', true ) !== $options['api_key'] )
			$user_keys = array( $options['api_key'] );

		// Search the users for their Keys and send the posts
		foreach ( $users_to_alert as $user ) {
			$user_key = get_user_meta( $user, 'fnpn_user_key', true );
			$selected = get_user_meta( $user, 'fnpn_user_notify_posts', true );
			if ( $user_key && $selected )
				$user_keys[] = $user_key;
		}

		$user_keys = array_unique( $user_keys );

		foreach ( $user_keys as $user ) {
			$args['user'] = $user;

            add_post_meta($post->ID, 'Pushbullet notification', 'post published');
			fnpn_send_notification( $args );
		}
	}
}