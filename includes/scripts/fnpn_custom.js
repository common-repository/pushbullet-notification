 var $fnapplicationkeyTextbox=jQuery("#applicationkey-textbox");

 $fnapplicationkeyTextbox.change(function () {
     bindDeviceId();
 });

 var $fndeviceidSelect = jQuery("#deviceiden-select");
 var $fndeviceidTextbox=jQuery("#deviceiden-textbox");
 var $fndeviceidBlock=jQuery("#deviceiden-block");

 bindDeviceId();

 $fndeviceidSelect.change(function () {
     $fndeviceidTextbox.val($fndeviceidSelect.val());
 });

 jQuery('#new-type-checkbox').change(function () {
     var checked = jQuery(this).attr('checked');

     if (checked) {
         jQuery('#new-post-detail').show();
     } else {
         jQuery('#new-post-detail').hide();
         jQuery('#new-post-roles > input').removeAttr('checked');
         jQuery('#new-post-types > input').removeAttr('checked');
     }
 });

  jQuery('#login-user-checkbox').change(function () {
     var checked = jQuery(this).attr('checked');

     if (checked) {
         jQuery('#login-user-detail').show();
     } else {
         jQuery('#login-user-detail').hide();
     }
 });

 jQuery('#new-user-checkbox').change(function () {
     var checked = jQuery(this).attr('checked');

     if (checked) {
         jQuery('#new-user-detail').show();
     } else {
         jQuery('#new-user-detail').hide();
     }
 });

 jQuery('#new-comment-checkbox').change(function () {
     var checked = jQuery(this).attr('checked');

     if (checked) {
         jQuery('#new-comment-detail').show();
     } else {
         jQuery('#new-comment-detail').hide();
     }
 });

 jQuery("#test-button").click(function (e) {
     e.preventDefault();

     jQuery.ajax({
         type: "POST",
         url: "https://api.pushbullet.com/v2/pushes",
         dataType: 'json',
         data: {
             device_iden: $fndeviceidTextbox.val(),
             type: "note",
             title: "Test notification",
             body: "Message"
         },
         async: false,
         beforeSend: function (xhr) {
             xhr.setRequestHeader('Authorization', make_base_auth($fnapplicationkeyTextbox.val(), ''));
         },
         success: function (msg) {
             alert("Message is send");
         }
     })
        .fail(function (jqXHR, textStatus) {
            alert(jqXHR.responseJSON.error.message);
        });
 });

 jQuery("#send-error").change(function () {
     var checked = jQuery(this).attr('checked');

     if (checked) {
         jQuery('#send-error-detail').show();
     } else {
         jQuery('#send-error-detail').hide();
     }
 });

function make_base_auth(user, password) {
  var tok = user + ':' + password;
  var hash = btoa(tok);
  return "Basic " + hash;
}

function bindDeviceId() {
    var apiKey = $fnapplicationkeyTextbox.val();
    if (apiKey !== undefined) {
        jQuery.ajax({
            type: "GET",
            url: "https://api.pushbullet.com/v2/devices",
            dataType: 'json',
            async: false,
            beforeSend: function (xhr) {
                xhr.setRequestHeader('Authorization', make_base_auth(apiKey, ''));
            },
            success: function (msg) {
                for (var i = 0; i < msg.devices.length; i++) {
                    var device = msg.devices[i];
                    var id = device.iden;
                    var name = '';
                    if (device.nickname != null) {
                        name += device.nickname;
                    }
                    else {
                        if (device.manufacturer != null) {
                            name += device.manufacturer + ' ';
                        }
                        if (device.model != null) {
                            name += device.model + ' ';
                        }
                        if (device.android_version != null) {
                            name += '(' + device.android_version + ')';
                        }
                    }

                    $fndeviceidSelect.append('<option value="' + id + '">' + name + '</option>');
                }

                if (msg.devices.length > 0) {
                    if ($fndeviceidTextbox.val() != '') {
                        $fndeviceidSelect.val($fndeviceidTextbox.val());
                    } else {
                        $fndeviceidSelect.val(msg.devices[0].id);
                    }

                    $fndeviceidSelect.show();
                    $fndeviceidBlock.hide();
                }
            }
        })
        .fail(function (jqXHR, textStatus) {
            $fndeviceidSelect.empty();
            $fndeviceidSelect.hide();

            $fndeviceidBlock.show();
            $fndeviceidTextbox.val('');
            alert(jqXHR.responseJSON.error.message);
        });
    }
}