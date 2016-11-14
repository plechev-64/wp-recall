<?php

if (!is_admin()):
    add_action('rcl_enqueue_scripts','rcl_profile_scripts',10);
endif;

function rcl_profile_scripts(){
    global $user_ID,$user_LK;   
    if($user_LK){
        if($user_ID==$user_LK){
            rcl_enqueue_style( 'rcl-profile', rcl_addon_url('style.css', __FILE__) );
        }
    }
}

add_action('rcl_bar_setup','rcl_bar_add_profile_link',10);
function rcl_bar_add_profile_link(){
    global $user_ID;
    
    if(!is_user_logged_in()) return false;
    
    rcl_bar_add_menu_item('profile-link',
        array(                
            'url'=>rcl_format_url(get_author_posts_url($user_ID),'profile'),
            'icon'=>'fa-user-secret',
            'label'=>__('Profile settings','wp-recall')
        )
    );

}

add_action('admin_menu', 'rcl_profile_options_page',30);
function rcl_profile_options_page(){
    add_submenu_page( 'manage-wprecall', __('Profile fields','wp-recall'), __('Profile fields','wp-recall'), 'manage_options', 'manage-userfield', 'rcl_manage_profile_fields');
}

add_action('init','rcl_add_block_show_profile_fields');
function rcl_add_block_show_profile_fields(){
    rcl_block('details','rcl_show_custom_fields_profile',array('id'=>'pf-block','order'=>20,'public'=>1));
}

function rcl_show_custom_fields_profile($master_id){
    
    $get_fields = get_option( 'rcl_profile_fields' );

    $show_custom_field = '';

    if($get_fields){

        $get_fields = stripslashes_deep($get_fields);

        $cf = new Rcl_Custom_Fields();

        foreach((array)$get_fields as $custom_field){
            $custom_field = apply_filters('custom_field_profile',$custom_field);
            if(!$custom_field) continue;
            $slug = $custom_field['slug'];
            if(isset($custom_field['req'])&&$custom_field['req']==1){
                $meta = get_the_author_meta($slug,$master_id);
                $show_custom_field .= $cf->get_field_value($custom_field,$meta);
            }
        }
    }

    if(!$show_custom_field) return false;

    return '<div class="show-profile-fields">'.$show_custom_field.'</div>';
}

if(!is_admin()) add_action('wp','rcl_update_profile_notice');
function rcl_update_profile_notice(){
    if (isset($_GET['updated'])) 
        rcl_notice_text(__('Your profile has been updated','wp-recall'),'success');
}

//Обновляем профиль пользователя
function rcl_edit_profile(){
    global $user_ID;
    
    if( !wp_verify_nonce( $_POST['_wpnonce'], 'update-profile_' . $user_ID ) ) return false;

    if ( defined('ABSPATH') ) {
        require_once(ABSPATH . 'wp-admin/includes/user.php');
    } else {
        require_once('../wp-admin/includes/user.php');
    }

    rcl_update_profile_fields($user_ID);

    check_admin_referer( 'update-profile_' . $user_ID );
    
    $errors = edit_user( $user_ID );
    
    if ( is_wp_error( $errors ) ) {
        foreach ( $errors->get_error_messages() as $message )
                $errmsg = "$message";
    }
    
    if(isset($errmsg)){
        wp_die($errmsg);
    }

    do_action( 'personal_options_update', $user_ID );
    
    $redirect_url = rcl_format_url(get_author_posts_url($user_ID),'profile').'&updated=true';

    wp_redirect( $redirect_url );
    
    exit;
}

function rcl_edit_profile_activate ( ) {
  if ( isset( $_POST['submit_user_profil'] ) ) {
    add_action( 'wp', 'rcl_edit_profile' );
  }
}
add_action('init', 'rcl_edit_profile_activate',10);

function rcl_update_profile_fields($user_id){

    require_once(ABSPATH . "wp-admin" . '/includes/image.php');
    require_once(ABSPATH . "wp-admin" . '/includes/file.php');
    require_once(ABSPATH . "wp-admin" . '/includes/media.php');

    $get_fields = get_option( 'rcl_profile_fields' );

    $get_fields = apply_filters('rcl_profile_fields',$get_fields);

    if($get_fields){
        foreach((array)$get_fields as $custom_field){
            $custom_field = apply_filters('update_custom_field_profile',$custom_field);
            if(!$custom_field||!$custom_field['slug']) continue;

            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
            
            $slug = $custom_field['slug'];
            
            if($custom_field['admin']==1&&!is_admin()){
                if(get_user_meta($user_id, $slug, $_POST[$slug]))
                    continue;
            }

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
                        if(get_user_meta($user_id, $slug, $_POST[$slug])) 
                                delete_user_meta($user_id, $slug, $_POST[$slug]);
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

//Удаляем аккаунт пользователя
function rcl_delete_user_account(){
    global $user_ID,$wpdb;
    if( !wp_verify_nonce( $_POST['_wpnonce'], 'delete-user-' . $user_ID ) ) return false;

    require_once(ABSPATH.'wp-admin/includes/user.php' );

    $wpdb->query($wpdb->prepare("DELETE FROM ".RCL_PREF."user_action WHERE user ='%d'",$user_ID));
    $delete = wp_delete_user( $user_ID );
    if($delete){
        wp_die(__('We are very sorry but your account has been deleted!','wp-recall'));
        echo '<a href="/">'.__('Back to main page','wp-recall').'</a>';
    }else{
        wp_die(__('Account deletion failed! Go back and try again.','wp-recall'));
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
        __('Profile and account settings','wp-recall'),
        $opt->option_block(
            array(
                $opt->title(__('Profile and account','wp-recall')),

                $opt->label(__('Allow users to delete their account?','wp-recall')),
                $opt->option('select',array(
                    'name'=>'delete_user_account',
                    'options'=>array(__('No','wp-recall'),__('Yes','wp-recall'))
                )),

                $opt->label(__('The maximum size of the avatar, Mb','wp-recall')),
                $opt->option('number',array('name'=>'avatar_weight')),
                $opt->notice(__('To limit the size uploading of avatars images, the size in megabytes by default is set at 2MB','wp-recall'))
            )
        )
    );

    return $content;
}

add_action('init','rcl_tab_profile');
function rcl_tab_profile(){
    
    rcl_tab(
        array(
            'id'=>'profile',
            'name'=>__('Profile','wp-recall'),
            'supports'=>array('ajax'),
            'public'=>0,
            'icon'=>'fa-user',
            'content'=>array(
                array(
                    'callback' => array(
                        'name'=>'rcl_tab_profile_content'
                    )
                )
            )
        )
    );

}

function rcl_tab_profile_content($master_id){

    global $userdata, $user_ID, $rcl_options;

    if($user_ID!=$master_id) return false;
    
    $defolt_field = get_option( 'rcl_profile_default' );

    foreach((array)$defolt_field as $onefield){
        switch($onefield){
            case 'user_login': $select_login = 'checked="checked"'; break;
            case 'first_name': $select_first = 'checked="checked"'; break;
            case 'last_name': $select_last = 'checked="checked"'; break;
            //case 'nickname': $select_nickname = 'checked="checked"'; break;
            case 'display_name': $select_display = 'checked="checked"'; break;
            //case 'email': $select_email = 'checked="checked"'; break;
            case 'url': $select_url = 'checked="checked"'; break;
            case 'description': $select_description = 'checked="checked"'; break;
        }
    }

    $profile_block = '<h3>'.__('User profile','wp-recall').' '.$userdata->user_login.'</h3>
    <form name="profile" id="your-profile" action="" method="post"  enctype="multipart/form-data">
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
                            <span>'.__('Admin toolbar','wp-recall').'</span>
                    </th>
                    <td>
                            <label for="admin_bar_front">
                            <input id="admin_bar_front" '.checked('true',$userdata->show_admin_bar_front,false).' type="checkbox" value="1" name="admin_bar_front">
                            '.__('Show admin bar when viewing the site','wp-recall').'
                            </label>
                    </td>
            </tr>';
    }

    $profile_block .= '<tr>
    <th><label for="nickname">'.__('Nickname','wp-recall').' ('.__('required','wp-recall').'):</label></th>
    <td><input type="text" name="nickname" required class="regular-text" id="nickname" value="'.esc_attr( $userdata->nickname ).'" maxlength="100" /></td>
    </tr>';

    $profile_block .= '<tr>
    <th><label for="email">'.__('E-mail','wp-recall').' ('.__('required','wp-recall').'):</label></th>
    <td><input type="text" name="email" class="regular-text" id="email" required value="'.esc_attr($userdata->user_email).'" maxlength="100" /></td>
    </tr>';

    if(isset($select_login)){
            $profile_block .= '<tr>
            <th><label for="user_login">'.__('Login','wp-recall').':</label></th>
            <td><input type="text" name="user_login" class="regular-text" id="user_login" value="'.esc_attr( $userdata->user_login ).'" maxlength="100" disabled /></td>
            </tr>';
    }
    if(isset($select_first)){
            $profile_block .= '<tr>
            <th><label for="first_name">'.__('Firstname','wp-recall').':</label></th>
            <td><input type="text" name="first_name" class="regular-text" id="first_name" value="'.esc_attr( $userdata->first_name ).'" maxlength="100" /></td>
            </tr>';
    }
    if(isset($select_last)){
            $profile_block .= '<tr>
            <th><label for="last_name">'.__('Surname','wp-recall').':</label></th>
            <td><input type="text" name="last_name" class="regular-text" id="last_name" value="'.esc_attr( $userdata->last_name ).'" maxlength="100" /></td>
            </tr>';
    }

    if(isset($select_display)){
        $profile_block .= '<tr>
        <th><label for="display_name">'.__('Name to be displayed','wp-recall').':</label></th>
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

	if(isset($select_url)){
		$profile_block .= '<tr>
		<th><label for="url">'.__('Your website','wp-recall').':</label></th>
		<td><input type="text" name="url" class="regular-text" id="url" value="'.esc_url($userdata->user_url).'" maxlength="100" /></td>
		</tr>';
	}

	$profile_block .= '<tr id="password">
            <th><label for="pass1">'.__('New password','wp-recall').'</label></th><br/>
            <td><input type="password" name="pass1" id="pass1" size="16" value="" autocomplete="off" onkeyup="passwordStrength(this.value)"  /><br>
			<small>'.__('If you want to change your password - enter a new one','wp-recall').'</small><br />
                <input type="password" name="pass2" id="pass2" size="16" value="" autocomplete="off" /><br />
                    <small>'.__('Repeat the new password','wp-recall').'</small>';
            if(isset($rcl_options['difficulty_parole'])&&$rcl_options['difficulty_parole']==1){
                $profile_block .= '<br />
                <div>
                    <b>'.__('Password strength indicator','wp-recall').':</b>
                    <div id="passwordStrength" class="strength0">
                            <div id="passwordDescription">'.__('A password has not been entered','wp-recall').'</div>
                    </div>
                </div>
                <p>
                <small><strong>'.__('Note','wp-recall').':</strong> '.__('The password must be at least 7 characters','wp-recall').'. <br/>
                '.__('Use upper and lower case for a strong password','wp-recall').'. <br/>
                '.__('Use symbols','wp-recall').': ! " ? $ % ^ &amp;</small>
		</p>';
            }
            $profile_block .= '</td>
        </tr>';
	if(isset($select_description)){
		$profile_block .= '<tr>
		<th><label for="description">'.__('Status','wp-recall').':</label></th>
		<td><textarea name="description" class="regular-text" id="description" rows="3" cols="50">'.esc_textarea($userdata->description).'</textarea></td>
		</tr>';
	}

        $profile_block .= '</table>';

	$get_fields = get_option( 'rcl_profile_fields' );

        $get_fields = apply_filters('rcl_profile_fields',$get_fields);

	if($get_fields){

            $profile_block .= '<table>';
            $field = '';
            $cf = new Rcl_Custom_Fields();

		$get_fields = stripslashes_deep($get_fields);

		foreach((array)$get_fields as $custom_field){

                    $custom_field = apply_filters('custom_field_profile',$custom_field);
					
                    $slug = $custom_field['slug'];

                    if(!$custom_field||!$slug) continue;

                    $value = (isset($userdata->$slug))? $userdata->$slug: '';

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
                    $field .= '<td>'.$cf->get_input($custom_field,$value).'</td></tr>';
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

	$profile_block .= '<input type="hidden" name="user_id" id="user_id" value="'.$user_ID.'" />
	<input type="hidden" name="admin_color" value="'.esc_attr( $userdata->admin_color ).'" />
	<input type="hidden" name="rich_editing" value="'.esc_attr( $userdata->rich_editing ).'" />
	<input type="hidden" name="comment_shortcuts" value="'.esc_attr( $userdata->comment_shortcuts ).'" />';
	if ( !empty($userdata->admin_bar_front) ) {
		$profile_block .= '<input type="hidden" name="admin_bar_front" value="'.esc_attr( $userdata->admin_bar_front ).'" />';
	}
	if ( !empty($userdata->admin_bar_admin) ) {
		$profile_block .= '<input type="hidden" name="admin_bar_admin" value="'.esc_attr( $userdata->admin_bar_admin ).'" />';
	}
	$profile_block .= '<div style="text-align:right;"><input type="submit" id="cpsubmit" class="recall-button" value="'.__('Update profile','wp-recall').'" name="submit_user_profil" /></div>
	</form>';
	if($rcl_options['delete_user_account']==1){
            $profile_block .= '
            <form method="post" action="" name="delete_account" onsubmit="return confirm(\''.__('Are you sure? It can’t be restaured!','wp-recall').'\');">
            '.wp_nonce_field('delete-user-'.$user_ID,'_wpnonce',true,false).'
            <input type="submit" id="delete_acc" class="recall-button"  value="'.__('Delete your profile','wp-recall').'" name="rcl_delete_user_account"/>
            </form>';
	}

	return $profile_block;
}

//Редактируем произвольные поля профиля
function rcl_manage_profile_fields(){
        global $rcl_options;
        
        rcl_sortable_scripts();

	if ( ! class_exists( 'Rcl_EditFields' ) ) 
            include_once RCL_PATH.'functions/class-rcl-editfields.php';

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

		update_option('rcl_profile_default', $save_data );
                
                $rcl_options['users_page_rcl'] = $_POST['users_page_rcl'];
                update_option('rcl_global_options', $rcl_options );
	}

	$profile_default_fields_styles = "
		<style>
                #users_page{
                    width:400px;
                }
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
                $default_form .= '<h3>'.__('Profile fields by default','wp-recall').'</h3>';
		$default_form .= apply_filters('rcl_profile_default_fields_styles', $profile_default_fields_styles);
		$default_form .= '<p>'.__('Fields to be displayed in the profile.','wp-recall').'</p>';
		$default_form .= '<table class="form-table" style="width:600px;">';
			$field_loop = $loop = 0;
			foreach ( $profile_default_fields as $field ) {
				$field_loop++;
				if ( 0 == ( $field_loop - 1 ) % 2 ) $default_form .= '<tr class="rcl_defoult_row">';
                                $df_field = get_option( 'rcl_profile_default' );
				$checked = ($df_field&&in_array( $field['id'], $df_field )) ? 'checked="checked"' : '';
				$default_form .= sprintf(__('<td><input type="%s" name="%s" %s /></td><td>%s</td>','wp-recall'), $field['type'], $field['id'], $checked, $field['label']);
				if ( 0 == $field_loop % 2 || $field_loop == count( $profile_default_fields ) ) $default_form .= '</tr><!-- End .rcl_defoult_row -->';
				$loop++;
			}

		$default_form .= '</table>';
	}
        
        $default_form .= '<h3>'.__('Users page','wp-recall').'</h3>';
        
        $default_form .= wp_dropdown_pages( array(
            'selected'   => $rcl_options['users_page_rcl'],
            'name'       => 'users_page_rcl',
            'show_option_none' => __('Not selected','wp-recall'),
            'echo'             => 0 )
        );
        
        $default_form .= '<p>'.__('This page is required to filter users by value of profile fields','wp-recall').'</p>';
        
        $default_form .= '<h3>'.__('Custom profile fields','wp-recall').'</h3>';

	$users_fields = '<h2>'.__('Manage profile fields','wp-recall').'</h2>';

        $users_fields .= $f_edit->edit_form(array(
            $f_edit->option('textarea',array(
                'name'=>'notice',
                'label'=>__('field description','wp-recall')
            )),
            $f_edit->option('select',array(
                'name'=>'requared',
                'notice'=>__('required field','wp-recall'),
                'value'=>array(__('No','wp-recall'),__('Yes','wp-recall'))
            )),
            $f_edit->option('select',array(
                'name'=>'register',
                'notice'=>__('display in registration form','wp-recall'),
                'value'=>array(__('No','wp-recall'),__('Yes','wp-recall'))
            )),
            $f_edit->option('select',array(
                'name'=>'order',
                'notice'=>__('display at checkout for guests','wp-recall'),
                'value'=>array(__('No','wp-recall'),__('Yes','wp-recall'))
            )),
            $f_edit->option('select',array(
                'name'=>'req',
                'notice'=>__('show the content to other users','wp-recall'),
                'value'=>array(__('No','wp-recall'),__('Yes','wp-recall'))
            )),
            $f_edit->option('select',array(
                'name'=>'admin',
                'notice'=>__('can be changed only by the site administration','wp-recall'),
                'value'=>array(__('No','wp-recall'),__('Yes','wp-recall'))
            )),
            $f_edit->option('select',array(
                'name'=>'filter',
                'notice'=>__('Filter users by this field','wp-recall'),
                'value'=>array(__('No','wp-recall'),__('Yes','wp-recall'))
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

//Выводим произвольные поля профиля на странице пользователя в админке
if (is_admin()):
	add_action('profile_personal_options', 'rcl_get_custom_fields_profile');
	add_action('edit_user_profile', 'rcl_get_custom_fields_profile');
endif;
function rcl_get_custom_fields_profile($user){

    $get_fields = get_option( 'rcl_profile_fields' );

    $cf = new Rcl_Custom_Fields();

    if($get_fields){
        $field = '<h3>'.__('Custom Profile Fields','wp-recall').':</h3>
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
			'label' => __('Login','wp-recall'),
			'type' => 'checkbox',
			'std' => 'no',
			'desc' => __('User login','wp-recall')
		),
		array(
			'id' => 'first_name',
			'label' => __('Firstname','wp-recall'),
			'type' => 'checkbox',
			'std' => 'no',
			'desc' => __('Username','wp-recall')
		),
		array(
			'id' => 'last_name',
			'label' => __('Surname','wp-recall'),
			'type' => 'checkbox',
			'std' => 'no',
			'desc' => __('User name','wp-recall')
		),
		array(
			'id' => 'display_name',
			'label' => __('Name to be displayed','wp-recall'),
			'type' => 'checkbox',
			'std' => 'no',
			'desc' => __('Name to be displayed','wp-recall')
		),
		array(
			'id' => 'url',
			'label' => __('Website','wp-recall'),
			'type' => 'checkbox',
			'std' => 'no',
			'desc' => __('User website','wp-recall')
		),
		array(
			'id' => 'description',
			'label' => __('Status','wp-recall'),
			'type' => 'checkbox',
			'std' => 'no',
			'desc' => __('User status','wp-recall')
		)
	);

	return apply_filters('rcl_profile_default_fields', $default_fields );
}