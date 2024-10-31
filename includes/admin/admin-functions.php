<?php
/**
 * Whitelist the mapping setting to the general settings tab
 * @return void
 */
function fnpn_register_additional_key_mappings() {
	register_setting( 'fnpn-update-options', '_fnpn_additional_key_mapping' );
}

/**
 * Add the mapping setting to the list of page_options on the general settings tab
 * @param  array $settings The current list of settings on the page, supplied by the filter: fnpn_settings_page_options
 * @return array           The settings list with mapping added
 */
function fnpn_enable_additional_key_mappings( $settings ) {
	$settings[] = '_fnpn_additional_key_mapping';

	return $settings;
}

/**
 * Shows the additional application key drop down, given a setting name
 * @param  string $setting_name The corresponding setting name (should match that of the options name for sending notifications
 * to keep things simple)
 * @return void                Echo's out the drop down, no return
 */
function fnpn_display_application_key_dropdown( $setting_name = false ) {
	$options = fnpn_get_options();
	if ( !( $options['multiple_keys'] ) )
		return false;

	if ( empty( $setting_name ) )
		return false;

	$all_keys = fnpn_get_application_keys();
	$current_mappings = get_option( '_fnpn_additional_key_mapping' );

	?><select name="_fnpn_additional_key_mapping[<?php echo $setting_name; ?>]"><?php
	foreach ( $all_keys as $id => $key ) {
		$currently_mapped_option = ( isset( $current_mappings[$setting_name] ) ) ? $current_mappings[$setting_name] : false;
		?><option value="<?php echo $id; ?>" <?php selected( $id, $currently_mapped_option, true ); ?>><?php echo $key['name']; ?></option><?php
	}
	?></select><?php
}

function fnpn_wp_dropdown_roles( $selected = false ) {
	var_dump($selected);
	$p = '';
	$r = '';
	$editable_roles = get_editable_roles();
	foreach ( $editable_roles as $role => $details ) {
		$name = translate_user_role($details['name'] );
		if ( $selected == $role ) // preselect specified role
			$p = "\n\t<option selected='selected' value='" . esc_attr($role) . "'>$name</option>";
		else
			$r .= "\n\t<option value='" . esc_attr($role) . "'>$name</option>";
	}
	echo $p . $r;
}