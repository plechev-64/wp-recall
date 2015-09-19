<?php

add_action('admin_menu', 'rcl_profile_options_page',30);
function rcl_profile_options_page(){
	add_submenu_page( 'manage-wprecall', __('Profile fields','rcl'), __('Profile fields','rcl'), 'manage_options', 'manage-userfield', 'rcl_manage_profile_fields');
}

add_action('init','rcl_add_block_show_profile_fields');
function rcl_add_block_show_profile_fields(){
    rcl_block('content','rcl_get_show_profile_fields',array('id'=>'pf-block','order'=>20,'public'=>1));
}

function rcl_get_show_profile_fields($author_lk){
	$profile_fields='';
	return apply_filters('show_profile_fields_rcl',$profile_fields,$author_lk);
}

add_action('wp','rcl_update_profile_notice');
function rcl_update_profile_notice(){
    if (isset($_GET['updated'])) rcl_notice_text(__('Your profile was updated','rcl'),'success');
}

//Обновляем профиль пользователя
function rcl_edit_profile(){
    global $user_ID;
    if( !wp_verify_nonce( $_POST['_wpnonce'], 'update-profile_' . $user_ID ) ) return false;

    //if(isset($_POST['pass1']))  $_POST['pass1'] = str_replace('\\\\','\\',$_POST['pass1']);

    if ( defined('ABSPATH') ) {
			require_once(ABSPATH . 'wp-admin/includes/user.php');
	} else {
			require_once('../wp-admin/includes/user.php');
	}
	//require_once( ABSPATH . WPINC . '/registration.php' );
    //echo $_POST['pass1'];exit;

    $redirect_url = rcl_format_url(get_author_posts_url($user_ID),'profile').'&updated=true';

    $args = array('hide_empty'=>false);
    $allterms = get_terms('category', $args );

    rcl_update_profile_fields($user_ID);

    check_admin_referer( 'update-profile_' . $user_ID );
    $errors = edit_user( $user_ID );
    if ( is_wp_error( $errors ) ) {
            foreach ( $errors->get_error_messages() as $message )
                    $errmsg = "$message";
    }
    if(isset($errmsg)) wp_die($errmsg);
    do_action( 'personal_options_update', $user_ID );
    wp_redirect( $redirect_url );
}

function rcl_update_profile_fields($user_id){
    //global $user_ID;

    require_once(ABSPATH . "wp-admin" . '/includes/image.php');
    require_once(ABSPATH . "wp-admin" . '/includes/file.php');
    require_once(ABSPATH . "wp-admin" . '/includes/media.php');

    $get_fields = get_option( 'custom_profile_field' );

    $get_fields = apply_filters('rcl_profile_fields',$get_fields);

    if($get_fields){
        foreach((array)$get_fields as $custom_field){
            $custom_field = apply_filters('update_custom_field_profile',$custom_field);
            if(!$custom_field||!$custom_field['slug']) continue;
            if(!is_admin()&&$custom_field['admin']==1) continue;

            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);

            $slug = $custom_field['slug'];
            if($custom_field['type']=='checkbox'){
                $vals = array();
                if(isset($_POST[$slug])){
                    $select = explode('#',$custom_field['field_select']);
                    $count_field = count($select);
                    foreach($_POST[$slug] as $val){
                        for($a=0;$a<$count_field;$a++){
                            if($select[$a]==$val){
                                $vals[] = $val;
                            }
                        }
                    }
                }
                if($vals){
                    update_user_meta($user_id, $slug, $vals);
                }else{
                    delete_user_meta($user_id, $slug);
                }
            }else if($custom_field['type']=='file'){

                $attach_id = rcl_upload_meta_file($custom_field,$user_id);
                if($attach_id) update_user_meta($user_id, $slug, $attach_id);

            }else{

                if($_POST[$slug]){
                        update_user_meta($user_id, $slug, $_POST[$slug]);
                }else{
                        if(get_user_meta($user_id, $slug, $_POST[$slug])) delete_user_meta($user_id, $slug, $_POST[$slug]);
                }
            }
        }
    }

    do_action('rcl_update_profile_fields',$user_id);

}

//Сохраняем изменения в произвольных полях профиля со страницы пользователя
add_action('personal_options_update', 'rcl_save_profile_fields');
add_action('edit_user_profile_update', 'rcl_save_profile_fields');
function rcl_save_profile_fields($user_id) {
    if ( !current_user_can( 'edit_user', $user_id ) ) return false;

    rcl_update_profile_fields($user_id);
}

function rcl_edit_profile_activate ( ) {
  if ( isset( $_POST['submit_user_profil'] ) ) {
    add_action( 'wp', 'rcl_edit_profile' );
  }
}
add_action('init', 'rcl_edit_profile_activate');

//Удаляем аккаунт пользователя
function rcl_delete_user_account(){
	global $user_ID,$wpdb;
	if( !wp_verify_nonce( $_POST['_wpnonce'], 'delete-user-' . $user_ID ) ) return false;
	$wpdb->query($wpdb->prepare("DELETE FROM ".RCL_PREF."user_action WHERE user ='%d'",$user_ID));
	$delete = wp_delete_account( $user_ID );
	if($delete){
		wp_die(__('Very sorry, but your account has been deleted!','rcl'));
		echo '<a href="/">'.__('Back to main','rcl').'</a>';
	}else{
		wp_die(__('Delete account failed! Go back and try again.','rcl'));
	}
}

function rcl_delete_user_account_activate ( ) {
  if ( isset( $_POST['rcl_delete_user_account'] ) ) {
    add_action( 'wp', 'rcl_delete_user_account' );
  }
}
add_action('init', 'rcl_delete_user_account_activate');

add_filter('admin_options_wprecall','rcl_profile_options');
function rcl_profile_options($content){

    $opt = new Rcl_Options(__FILE__);

    $content .= $opt->options(
        __('Profile settings and account','rcl'),
        $opt->option_block(
            array(
                $opt->title(__('Profile and account','rcl')),

                $opt->label(__('Allow to delete users from your account?','rcl')),
                $opt->option('select',array(
                    'name'=>'delete_user_account',
                    'options'=>array(__('No','rcl'),__('Yes','rcl'))
                )),

                $opt->label(__('The maximum size of the avatar, Mb','rcl')),
                $opt->option('number',array('name'=>'avatar_weight')),
                $opt->notice(__('To restrict the loading of images as avatars this value in megabytes. By default, 2MB','rcl'))
            )
        )
    );

    return $content;
}

add_filter('after-avatar-rcl','rcl_button_avatar_upload',2,2);
function rcl_button_avatar_upload($content,$author_lk){
	global $user_ID;

        $avatar = get_user_meta($author_lk,'rcl_avatar',1);

        if($avatar){
            if(is_numeric($avatar)){
                $image_attributes = wp_get_attachment_image_src($avatar);
                $url_avatar = $image_attributes[0];
            }else{
                $url_avatar = $avatar;
            }
            $content .= '<a title="'.__('Zoom avatar','rcl').'" data-zoom="'.$url_avatar.'" onclick="rcl_zoom_avatar(this);return false;" class="rcl-avatar-zoom" href="#"><i class="fa fa-search-plus"></i></a>';
        }

	if($user_ID!=$author_lk) return $content;

	if($avatar){
            $content .= '<a title="'.__('Delete avatar','rcl').'" class="rcl-avatar-delete" href="'.wp_nonce_url( rcl_format_url(rcl_get_current_url()).'rcl-action=delete_avatar', $user_ID ).'"><i class="fa fa-times"></i></a>';
	}

	$content .= '
	<div id="userpic-upload">
		<span id="file-upload" class="fa fa-download">
			<input type="file" id="userpicupload" accept="image/*" name="uploadfile">
		</span>
		<span id="webcamupload" class="fa fa-camera"></span>
	</div>
	<span id="avatar-upload-progress"></span>';
	return $content;
}

add_action('wp','rcl_delete_avatar_action');
function rcl_delete_avatar_action(){
	global $wpdb,$user_ID,$rcl_avatar_sizes;
	if ( !isset( $_GET['rcl-action'] )||$_GET['rcl-action']!='delete_avatar' ) return false;
	if( !wp_verify_nonce( $_GET['_wpnonce'], $user_ID ) ) wp_die('Error');

	$result = delete_user_meta($user_ID,'rcl_avatar');

	if (!$result) wp_die('Error');

        $dir_path = RCL_UPLOAD_PATH.'avatars/';
        foreach($rcl_avatar_sizes as $key=>$size){
            unlink($dir_path.$user_ID.'-'.$size.'.jpg');
	}
        unlink($dir_path.$user_ID.'.jpg');

	wp_redirect( rcl_format_url(get_author_posts_url($user_ID)).'rcl-avatar=deleted' );  exit;
}

add_filter('rcl_content_lk','rcl_add_more_link_content',100);
function rcl_add_more_link_content($content){
    if(!$content) return $content;
    return '<a href="#" class="rcl-more-link" onclick="rcl_more_view(this); return false;">Подробная информация <i class="fa fa-plus-square-o"></i></a>'
    .'<div class="more-content">'.$content.'</div>';
}

add_action('wp','rcl_notice_avatar_deleted');
function rcl_notice_avatar_deleted(){
    if (isset($_GET['rcl-avatar'])&&$_GET['rcl-avatar']=='deleted') rcl_notice_text(__('Your avatar has been removed','rcl'),'success');
}

function rcl_ajax_tab_profile($array_tabs){
    $array_tabs['profile']='rcl_tab_profile_content';
    return $array_tabs;
}
add_filter('ajax_tabs_rcl','rcl_ajax_tab_profile');

add_action('init','rcl_tab_profile');
function rcl_tab_profile(){
    rcl_tab('profile','rcl_tab_profile_content',__('Profile','rcl'),array('class'=>'fa-user','order'=>20,'path'=>__FILE__));
}

function rcl_tab_profile_content($author_lk){

	global $userdata, $user_ID, $rcl_options;

	if($user_ID!=$author_lk) return false;

	get_currentuserinfo();
	do_action('show_user_profile', $userdata);
	$defolt_field = get_option( 'show_defolt_field' );

	foreach((array)$defolt_field as $onefield){
		switch($onefield){
			case 'user_login': $select_login = 'checked="checked"'; break;
			case 'first_name': $select_first = 'checked="checked"'; break;
			case 'last_name': $select_last = 'checked="checked"'; break;
			case 'nickname': $select_nickname = 'checked="checked"'; break;
			case 'display_name': $select_display = 'checked="checked"'; break;
			//case 'email': $select_email = 'checked="checked"'; break;
			case 'url': $select_url = 'checked="checked"'; break;
			case 'description': $select_description = 'checked="checked"'; break;
		}
	}

	$profile_block = '<h3>'.__('User profile','rcl').' '.$userdata->user_login.'</h3>
	<form name="profile" id="your-profile" action="" method="post" enctype="multipart/form-data">
	'.wp_nonce_field( 'update-profile_' . $user_ID,'_wpnonce',true,false ).'
	<input type="hidden" name="from" value="profile" />
	<input type="hidden" name="checkuser_id" value="'.$user_ID.'" />
	<table class="form-table">';

	$access = 7;
	if(isset($rcl_options['consol_access_rcl'])&&$rcl_options['consol_access_rcl'])
            $access = $rcl_options['consol_access_rcl'];

	if($userdata->user_level >= $access){
		$profile_block .= '<tr>
			<th>
				<span>'.__('Admin toolbar','rcl').'</span>
			</th>
			<td>
				<label for="admin_bar_front">
				<input id="admin_bar_front" '.checked('true',$userdata->show_admin_bar_front,false).' type="checkbox" value="1" name="admin_bar_front">
				'.__('Show the admin bar when viewing site','rcl').'
				</label>
			</td>
		</tr>';
	}

	if(isset($select_login)){
		$profile_block .= '<tr>
		<th><label for="user_login">'.__('Login','rcl').':</label></th>
		<td><input type="text" name="user_login" class="regular-text" id="user_login" value="'.esc_attr( $userdata->user_login ).'" maxlength="100" disabled /></td>
		</tr>';
	}
	if(isset($select_first)){
		$profile_block .= '<tr>
		<th><label for="first_name">'.__('Firstname','rcl').':</label></th>
		<td><input type="text" name="first_name" class="regular-text" id="first_name" value="'.esc_attr( $userdata->first_name ).'" maxlength="100" /></td>
		</tr>';
	}
	if(isset($select_last)){
		$profile_block .= '<tr>
		<th><label for="last_name">'.__('Surname','rcl').':</label></th>
		<td><input type="text" name="last_name" class="regular-text" id="last_name" value="'.esc_attr( $userdata->last_name ).'" maxlength="100" /></td>
		</tr>';
	}
	if(isset($select_nickname)){
		$profile_block .= '<tr>
		<th><label for="nickname">'.__('Nickname','rcl').':</label></th>
		<td><input type="text" name="nickname" class="regular-text" id="nickname" value="'.esc_attr( $userdata->nickname ).'" maxlength="100" /></td>
		</tr>';
	}
	if(isset($select_display)){
		$profile_block .= '<tr>
		<th><label for="display_name">'.__('Display name','rcl').':</label></th>
		<td>
		<select name="display_name" class="regular-dropdown" id="display_name">';
			$public_display = array();
			$public_display['display_displayname'] = esc_attr($userdata->display_name);
			$public_display['display_nickname'] = esc_attr($userdata->nickname);
			$public_display['display_username'] = esc_attr($userdata->user_login);
			$public_display['display_firstname'] = esc_attr($userdata->first_name);
			if($userdata->first_name&&$userdata->last_name) $public_display['display_firstlast'] = esc_attr($userdata->first_name) . '&nbsp;' . esc_attr($userdata->last_name);
			if($userdata->first_name&&$userdata->last_name) $public_display['display_lastfirst'] = esc_attr($userdata->last_name) . '&nbsp;' . esc_attr($userdata->first_name);
			$public_display = array_unique(array_filter(array_map('trim', $public_display)));
			foreach((array)$public_display as $id => $item) {
				$profile_block .= '<option id="'.$id.'" value="'.esc_attr($item).'">'.esc_attr($item).'</option>';
			}
			$profile_block .= '</select>
		</td></tr>';
	}


    $profile_block .= "<script>( function($) {
        $(document).ready( function() {
			var select = $('#display_name');

			if ( select.length ) {
				$('#first_name, #last_name, #nickname').bind( 'blur.user_profile', function() {
					var dub = [],
						inputs = {
							display_nickname  : $('#nickname').val() || '',
							display_username  : $('#user_login').val() || '',
							display_firstname : $('#first_name').val() || '',
							display_lastname  : $('#last_name').val() || ''
						};

					if ( inputs.display_firstname && inputs.display_lastname ) {
						inputs['display_firstlast'] = inputs.display_firstname + ' ' + inputs.display_lastname;
						inputs['display_lastfirst'] = inputs.display_lastname + ' ' + inputs.display_firstname;
					}

					$.each( $('option', select), function( i, el ){
						dub.push( el.value );
					});

					$.each(inputs, function( id, value ) {
						if ( ! value )
							return;

						var val = value.replace(/<\/?[a-z][^>]*>/gi, '');

						if ( inputs[id].length && $.inArray( val, dub ) == -1 ) {
							dub.push(val);
							$('<option />', {
								'text': val
							}).appendTo( select );
						}
					});
				});
			}
		});
		} ) ( jQuery );
	</script>";

		$profile_block .= '<tr>
		<th><label for="email">'.__('E-mail','rcl').':</label></th>
		<td><input type="text" name="email" class="regular-text" id="email" required value="'.esc_attr($userdata->user_email).'" maxlength="100" /></td>
		</tr>';

	if(isset($select_url)){
		$profile_block .= '<tr>
		<th><label for="url">'.__('Your website','rcl').':</label></th>
		<td><input type="text" name="url" class="regular-text" id="url" value="'.esc_url($userdata->user_url).'" maxlength="100" /></td>
		</tr>';
	}

	$profile_block .= '<tr id="password">
            <th><label for="pass1">'.__('New password','rcl').'</label></th><br/>
            <td><input type="password" name="pass1" id="pass1" size="16" value="" autocomplete="off" onkeyup="passwordStrength(this.value)"  /><br>
			<small>'.__('If you want to change your password - enter new','rcl').'</small><br />
                <input type="password" name="pass2" id="pass2" size="16" value="" autocomplete="off" /><br />
                    <small>'.__('Repeat the new password','rcl').'</small>';
            if(isset($rcl_options['difficulty_parole'])&&$rcl_options['difficulty_parole']==1){
                $profile_block .= '<br />
                <div>
                    <b>'.__('The password strength indicator','rcl').':</b>
                    <div id="passwordStrength" class="strength0">
                            <div id="passwordDescription">'.__('A password is not entered','rcl').'</div>
                    </div>
                </div>
                <p>
                <small><strong>'.__('Note','rcl').':</strong> '.__('The password must be at least 7 characters','rcl').'. <br/>
                Используйте верхний и нижний регистр для более надежного пароля. <br/>
                Используйте также символы: ! " ? $ % ^ &amp;</small>
		</p>';
            }
            $profile_block .= '</td>
        </tr>';
	if(isset($select_description)){
		$profile_block .= '<tr>
		<th><label for="description">'.__('Status','rcl').':</label></th>
		<td><textarea name="description" class="regular-text" id="description" rows="3" cols="50">'.esc_textarea($userdata->description).'</textarea></td>
		</tr>';
	}

        $profile_block .= '</table>';

	$get_fields = get_option( 'custom_profile_field' );

        $get_fields = apply_filters('rcl_profile_fields',$get_fields);

	if($get_fields){

            $profile_block .= '<table>';
            $field = '';
            $cf = new Rcl_Custom_Fields();

		$get_fields = stripslashes_deep($get_fields);

		foreach((array)$get_fields as $custom_field){

                    $custom_field = apply_filters('custom_field_profile',$custom_field);

                    if($custom_field['admin']==1&&!$userdata->$custom_field['slug']) continue;
                    if(!$custom_field||!$custom_field['slug']) continue;

                    $class = (isset($custom_field['class']))? $custom_field['class']: '';
                    $id = (isset($custom_field['id']))? 'id='.$custom_field['id']: '';
                    $attr = (isset($custom_field['attr']))? ''.$custom_field['attr']: '';

                    $field .= '<tr class="form-block-rcl '.$class.'" '.$id.' '.$attr.'>';

                    $star = (isset($custom_field['requared'])&&$custom_field['requared']==1)? ' <span class="required">*</span> ': '';
                    $field .= '<th>'
                            . '<label>'.$cf->get_title($custom_field).$star.'';
                            if($custom_field['type']) $field .= ':';
                            $field .= '</label>'
                            . '</th>';
                    $field .= '<td>'.$cf->get_input($custom_field,$userdata->$custom_field['slug']).'</td></tr>';
		}

		$profile_block .= $field;

                $profile_block .= '</table>';

                $profile_block .= "<script>
                            jQuery(function(){
                                jQuery('#your-profile').find('.requared-checkbox').each(function(){
                                    var name = jQuery(this).attr('name');
                                    var chekval = jQuery('#your-profile input[name=\"'+name+'\"]:checked').val();
                                    if(chekval) jQuery('#your-profile input[name=\"'+name+'\"]').attr('required',false);
                                    else jQuery('#your-profile input[name=\"'+name+'\"]').attr('required',true);
                                });"
                            . "});"
                        . "</script>";

	}

    $profile_block = apply_filters('profile_options_rcl',$profile_block,$userdata);

	$profile_block .= '<input type="hidden" name="action" value="update" />
	<input type="hidden" name="user_id" id="user_id" value="'.$user_ID.'" />
	<input type="hidden" name="admin_color" value="'.esc_attr( $userdata->admin_color ).'" />
	<input type="hidden" name="rich_editing" value="'.esc_attr( $userdata->rich_editing ).'" />
	<input type="hidden" name="comment_shortcuts" value="'.esc_attr( $userdata->comment_shortcuts ).'" />';
	if ( !empty($userdata->admin_bar_front) ) {
		$profile_block .= '<input type="hidden" name="admin_bar_front" value="'.esc_attr( $userdata->admin_bar_front ).'" />';
	}
	if ( !empty($userdata->admin_bar_admin) ) {
		$profile_block .= '<input type="hidden" name="admin_bar_admin" value="'.esc_attr( $userdata->admin_bar_admin ).'" />';
	}
	$profile_block .= '<div style="text-align:right;"><input type="submit" id="cpsubmit" class="recall-button" value="'.__('Update profile','rcl').'" name="submit_user_profil" /></div>
	</form>';
	if($rcl_options['delete_user_account']==1){
            $profile_block .= '
            <form method="post" action="" name="delete_account" onsubmit="return confirm(\''.__('Are you sure? Then restore will not work!','rcl').'\');">
            '.wp_nonce_field('delete-user-'.$user_ID,'_wpnonce',true,false).'
            <input type="submit" id="delete_acc" class="recall-button"  value="'.__('To delete your profile','rcl').'" name="rcl_delete_user_account"/>
            </form>';
	}

	return $profile_block;
}

//Редактируем произвольные поля профиля
function rcl_manage_profile_fields(){

        rcl_sortable_scripts();

	if ( ! class_exists( 'Rcl_EditFields' ) ) include_once RCL_PATH.'functions/rcl_editfields.php';

	$f_edit = new Rcl_EditFields('profile');

	$default_form = '';
	$profile_default_fields = rcl_get_default_fields_profile();

	if ( $f_edit->verify() ) {

		$f_edit->update_fields('usermeta');

		$_posts = $_POST;
		$save_data = array();

		foreach( $profile_default_fields as $filed ) {
			if ( isset( $_posts[$filed['id']] ) && $_posts[$filed['id']] == 'on' ) {
				array_push( $save_data, $filed['id'] );
			}
		}

		update_option('show_defolt_field', $save_data );
	}

	$profile_default_fields_styles = "
		<style>
		#inputs_user_fields table {
			cursor: move;
			background:#fafafa;
			border: 1px solid #CCCCCC;
			border-radius: 5px 5px 5px 5px;
			margin: 2px;
			width: 100%;
			}
		table td {
			padding: 2px 10px;
			}
		#inputs_user_fields textarea {
			width:100%;
			}
		.two-col {
			width:20%;
			}
		#inputs_user_fields .new {
			background:yellow;
			cursor: default;
			}
		</style>";

	if ( sizeof( $profile_default_fields ) > 0 ) {
		$default_form .= apply_filters('rcl_profile_default_fields_styles', $profile_default_fields_styles);
		$default_form .= '<p>'.__('Fields to display in the profile note ticks.','rcl').'</p>';
		$default_form .= '<table class="form-table" style="width:600px;">';
			$field_loop = $loop = 0;
			foreach ( $profile_default_fields as $field ) {
				$field_loop++;
				if ( 0 == ( $field_loop - 1 ) % 2 ) $default_form .= '<tr class="rcl_defoult_row">';
                                $df_field = get_option( 'show_defolt_field' );
				$checked = ($df_field&&in_array( $field['id'], $df_field )) ? 'checked="checked"' : '';
				$default_form .= sprintf(__('<td><input type="%s" name="%s" %s /></td><td>%s</td>','rcl'), $field['type'], $field['id'], $checked, $field['label']);
				if ( 0 == $field_loop % 2 || $field_loop == count( $profile_default_fields ) ) $default_form .= '</tr><!-- End .rcl_defoult_row -->';
				$loop++;
			}

		$default_form .= '</table>';
	}

	$users_fields = '<h2>'.__('Manage profile fields','rcl').'</h2>'

        .$f_edit->edit_form(array(
            $f_edit->option('select',array(
                'name'=>'requared',
                'notice'=>__('required field','rcl'),
                'value'=>array(__('No','rcl'),__('Yes','rcl'))
            )),
            $f_edit->option('select',array(
                'name'=>'register',
                'notice'=>__('to display the registration form','rcl'),
                'value'=>array(__('No','rcl'),__('Yes','rcl'))
            )),
            $f_edit->option('select',array(
                'name'=>'order',
                'notice'=>__('display at checkout for guests','rcl'),
                'value'=>array(__('No','rcl'),__('Yes','rcl'))
            )),
            $f_edit->option('select',array(
                'name'=>'req',
                'notice'=>__('to show the content to other users','rcl'),
                'value'=>array(__('No','rcl'),__('Yes','rcl'))
            )),
            $f_edit->option('select',array(
                'name'=>'admin',
                'notice'=>__('it only changes the administration of the site','rcl'),
                'value'=>array(__('No','rcl'),__('Yes','rcl'))
            ))
        ),$default_form);

	echo $users_fields;
}

//Выводим возможность синхронизации соц.аккаунтов в его личном кабинете
//при активированном плагине Ulogin
if(function_exists('ulogin_profile_personal_options')){
    function get_ulogin_profile_options($profile_block,$userdata){
        ob_start();
        ulogin_profile_personal_options($userdata);
	$profile_block .= ob_get_contents();
	ob_end_clean();
	return $profile_block;
    }
    add_filter('profile_options_rcl','get_ulogin_profile_options',10,2);
}

add_filter('show_profile_fields_rcl','rcl_show_custom_fields_profile',10,2);
function rcl_show_custom_fields_profile($fields_content,$author_lk){
	$get_fields = get_option( 'custom_profile_field' );

        $show_custom_field = '';

	if($get_fields){

		$get_fields = stripslashes_deep($get_fields);

                $cf = new Rcl_Custom_Fields();

		foreach((array)$get_fields as $custom_field){
                        $custom_field = apply_filters('custom_field_profile',$custom_field);
                        if(!$custom_field) continue;
			$slug = $custom_field['slug'];
			if($custom_field['req']==1){
                            $meta = get_the_author_meta($slug,$author_lk);
                            $show_custom_field .= $cf->get_field_value($custom_field,$meta);
			}
		}
	}

        if(!$show_custom_field) return false;

	if(isset($show_custom_field))$fields_content .= '<div class="show-profile-fields">'.$show_custom_field.'</div>';
	return $fields_content;
}

//Выводим произвольные поля профиля на странице пользователя в админке
if (is_admin()):
	add_action('profile_personal_options', 'rcl_get_custom_fields_profile');
	add_action('edit_user_profile', 'rcl_get_custom_fields_profile');
endif;
function rcl_get_custom_fields_profile($user){

    $get_fields = get_option( 'custom_profile_field' );

    $cf = new Rcl_Custom_Fields();

    if($get_fields){
        $field = '<h3>Произвольные поля профиля:</h3>
        <table class="form-table">';
        foreach((array)$get_fields as $custom_field){
            $slug = $custom_field['slug'];
            $meta = get_the_author_meta($slug,$user->ID);
            $field .= '<tr><th><label>'.$cf->get_title($custom_field).':</label></th>';
            $field .= '<td>'.$cf->get_input($custom_field,$meta).'</td>';
            $field .= '</tr>';
        }
        $field .= '</table>';
        echo $field;
    }
}

function rcl_get_default_fields_profile() {

	$default_fields = array(
		array(
			'id' => 'user_login',
			'label' => __('Login','rcl'),
			'type' => 'checkbox',
			'std' => 'no',
			'desc' => __('Login user','rcl')
		),
		array(
			'id' => 'first_name',
			'label' => __('Firstname','rcl'),
			'type' => 'checkbox',
			'std' => 'no',
			'desc' => __('Username','rcl')
		),
		array(
			'id' => 'last_name',
			'label' => __('Surname','rcl'),
			'type' => 'checkbox',
			'std' => 'no',
			'desc' => __('Surname user','rcl')
		),
		array(
			'id' => 'nickname',
			'label' => __('Nickname','rcl'),
			'type' => 'checkbox',
			'std' => 'no',
			'desc' => __('Nickname user','rcl')
		),
		array(
			'id' => 'display_name',
			'label' => __('Display name','rcl'),
			'type' => 'checkbox',
			'std' => 'no',
			'desc' => __('Display name user','rcl')
		),
		array(
			'id' => 'email',
			'label' => __('Емейл (be sure)','rcl'),
			'type' => 'checkbox',
			'std' => 'no',
			'desc' => __('Емейл user','rcl')
		),
		array(
			'id' => 'url',
			'label' => __('Website','rcl'),
			'type' => 'checkbox',
			'std' => 'no',
			'desc' => __('Website user','rcl')
		),
		array(
			'id' => 'description',
			'label' => __('Status','rcl'),
			'type' => 'checkbox',
			'std' => 'no',
			'desc' => __('User status','rcl')
		)
	);

	return apply_filters('rcl_profile_default_fields', $default_fields );
}

function rcl_get_profile_scripts($script){
	global $rcl_options;

	$maxsize_mb = (isset($rcl_options['avatar_weight'])&&$rcl_options['avatar_weight'])? $rcl_options['avatar_weight']: 2;
	$maxsize = $maxsize_mb*1024*1024;

	$script .= "
	$('#userpicupload').fileupload({
		dataType: 'json',
		type: 'POST',
		url: wpurl+'wp-admin/admin-ajax.php',
		formData:{action:'rcl_avatar_upload'},
		loadImageMaxFileSize: ".$maxsize.",
		autoUpload:false,
		previewMaxWidth: 900,
		previewMaxHeight: 900,
		imageMinWidth:150,
		imageMinHeight:150,
		disableExifThumbnail: true,
		progressall: function (e, data) {
			var progress = parseInt(data.loaded / data.total * 100, 10);
			$('#avatar-upload-progress').show().html('<span>'+progress+'%</span>');
		},
		processalways: function (e, data) {
			$.each(data.files, function (index, file) {

				$('#rcl-preview').remove();

				if(file.size>".$maxsize."){
					rcl_notice('Превышен максимальный размер для изображения! Макс. ".$maxsize_mb."MB','error');
					return false;
				}

				var canvas = data.files[0].preview;
				var dataURL = canvas.toDataURL();

                                $( '#rcl-preview' ).remove();

				$('body > div').last().after('<div id=\'rcl-preview\' title=\'Загружаемое изображение\'><img src=\''+dataURL+'\'></div>');

				var image = $('#rcl-preview img');

                                image.load(function() {
                                    var img = $(this);
                                    var height = img.height();
                                    var width = img.width();
                                    var jcrop_api;

                                    img.Jcrop({
                                            aspectRatio: 1,
                                            minSize:[150,150],
                                            onSelect:function(c){
                                                    img.attr('data-width',width).attr('data-height',height).attr('data-x',c.x).attr('data-y',c.y).attr('data-w',c.w).attr('data-h',c.h);
                                            }
                                    },function(){
                                            jcrop_api = this;
                                    });

                                    $( '#rcl-preview' ).dialog({
                                      modal: true,
                                      imageQuality: 1,
                                      width: width+32,
                                      resizable: false,
                                      close: function (e, data) {
                                              jcrop_api.destroy();
                                              $( '#rcl-preview' ).remove();
                                      },
                                      buttons: {
                                            Ok: function() {
                                              data.submit();
                                              $( this ).dialog( 'close' );
                                            }
                                      }
                                    });

                                });

			});
		},
		submit: function (e, data) {
			var image = $('#rcl-preview img');
			if (parseInt(image.data('w'))){
				var src = image.attr('src');
				var width = image.data('width');
				var height = image.data('height');
				var x = image.data('x');
				var y = image.data('y');
				var w = image.data('w');
				var h = image.data('h');
				data.formData = {
					coord: x+','+y+','+w+','+h,
					image: width+','+height,
					action:'rcl_avatar_upload'
				};
			}
		},
		done: function (e, data) {

			if(data.result['error']){
				rcl_notice(data.result['error'],'error');
				return false;
			}

			$('#rcl-contayner-avatar .rcl-user-avatar img').attr('src',data.result['avatar_url']);
			$('#avatar-upload-progress').hide().empty();
			$( '#rcl-preview' ).remove();
			rcl_notice(data.result['success'],'success');

		}
	});

	$('#webcamupload').click(function(){

                $( '#rcl-preview' ).remove();
		$('body > div').last().after('<div id=\'rcl-preview\' title=\'Снимок с камеры\'></div>');

                var webCam = new SayCheese('#rcl-preview', { audio: true });

                $( '#rcl-preview' ).dialog({
                    modal: true,
                    imageQuality: 1,
                    resizable: false,
                    width:355,
                    close: function (e, data) {
                        webCam.stop();
                        $( this ).dialog( 'close' );
                        $( '#rcl-preview' ).remove();
                    },
                    open: function (e, data) {
                        webCam.start();
                    },
                    buttons: {
                        Снимок: function() {
                                webCam.takeSnapshot(320, 240);
                        }
                    }
		});

		webCam.on('snapshot', function(snapshot) {

			var img = document.createElement('img');

			$(img).on('load', function() {
				$('#rcl-preview').html(img);
			});
			img.src = snapshot.toDataURL('image/png');

			var dataString = 'action=rcl_avatar_upload&src='+img.src;
			$.ajax({
				type: 'POST',
				data: dataString,
				dataType: 'json',
				url: wpurl+'wp-admin/admin-ajax.php',
				success: function(data){

					if(data['error']){
						rcl_notice(data['error'],'error');
						return false;
					}

					$( '#rcl-preview' ).dialog('close');
					$('#rcl-contayner-avatar .rcl-user-avatar img').attr('src',data['avatar_url']);
					$( '#rcl-preview' ).remove();
					rcl_notice(data['success'],'success');
				}
			});

		});

	});";
	return $script;
}
add_filter('file_footer_scripts_rcl','rcl_get_profile_scripts');

add_filter('rcl_functions_js','rcl_profile_functions_js');
function rcl_profile_functions_js($string){
    $string .= "function rcl_zoom_avatar(e){
        var link = jQuery(e);
        var src = link.data('zoom');
        jQuery('body > div').last().after('<div id=\'rcl-preview\'><img class=aligncenter src=\''+src+'\'></div>');
        jQuery( '#rcl-preview img' ).load(function() {
            jQuery( '#rcl-preview' ).dialog({
                modal: true,
                draggable: false,
                imageQuality: 1,
                resizable: false,
                width:355,
                close: function (e, data) {
                    jQuery( this ).dialog( 'close' );
                    jQuery( '#rcl-preview' ).remove();
                },
                buttons: {
                    Ок: function() {
                        jQuery( this ).dialog( 'close' );
                    }
                }
            });
        });
    }
    function rcl_more_view(e){
        var link = jQuery(e);
        var icon = link.children('i');
        link.parent().children('div').slideToggle();
        icon.toggleClass('fa-plus-square-o fa-minus-square-o');
    }";
    return $string;
}

include_once 'upload-avatar.php';