<?php

require_once 'addon-settings.php';

add_action('admin_menu', 'rcl_profile_admin_menu',30);
function rcl_profile_admin_menu(){
    add_submenu_page( 'manage-wprecall', __('Profile fields','wp-recall'), __('Profile fields','wp-recall'), 'manage_options', 'manage-userfield', 'rcl_profile_fields_manager');
}

add_action('rcl_update_custom_fields','rcl_update_page_users',10);
function rcl_update_page_users(){
    
    if(!isset($_POST['users_page_rcl'])) return false;
    
    rcl_update_option('users_page_rcl',$_POST['users_page_rcl']);
    
}
 
add_filter('rcl_custom_field_options','rcl_add_register_profile_field_option',10,3);
function rcl_add_register_profile_field_option($options, $field, $type){
    
    if($type != 'profile' || !rcl_is_register_open()) return $options;
    
    $options[] = array(
        'type' => 'select',
        'slug'=>'register',
        'title'=>__('display in registration form','wp-recall'),
        'values'=> array(
            __('No','wp-recall'),
            __('Yes','wp-recall')
        )
    );
    
    return $options;
    
}

function rcl_profile_fields_manager(){

    rcl_sortable_scripts();

    $profileFields = new Rcl_Profile_Fields('profile',array('custom-slug'=>1));
    
    $content = '<h2>'.__('Manage profile fields','wp-recall').'</h2>';
    
    $content .= $profileFields->active_fields_box();

    $content .= $profileFields->inactive_fields_box();

    echo $content;
    
}

//Сохраняем изменения в произвольных полях профиля со страницы пользователя
add_action('personal_options_update', 'rcl_save_profile_fields');
add_action('edit_user_profile_update', 'rcl_save_profile_fields');
function rcl_save_profile_fields($user_id) {
    
    if ( !current_user_can( 'edit_user', $user_id ) ) return false;

    rcl_update_profile_fields($user_id);
    
}

//Выводим произвольные поля профиля на странице пользователя в админке
if (is_admin()):
    add_action('profile_personal_options', 'rcl_get_custom_fields_profile');
    add_action('edit_user_profile', 'rcl_get_custom_fields_profile');
endif;
function rcl_get_custom_fields_profile($user){
    
    $args = array(
        'exclude' => array(
            'first_name',
            'last_name',
            'description',
            'user_url',
            'display_name',
            'user_email',
            'primary_pass',
            'repeat_pass',
            'show_admin_bar_front'
        )
    );

    $fields = rcl_get_profile_fields($args);

    $cf = new Rcl_Custom_Fields();

    if($fields){
        
        $content = '<h3>'.__('Custom Profile Fields','wp-recall').':</h3>
        <table class="form-table rcl-form">';
        
        foreach($fields as $field){
            
            $field['value_in_key'] = true;

            $value = get_the_author_meta($field['slug'],$user->ID);
            
            $content .= '<tr><th><label>'.$cf->get_title($field).':</label></th>';
            $content .= '<td>'.$cf->get_input($field,$value).'</td>';
            $content .= '</tr>';
            
        }
        
        $content .= '</table>';
        
        echo $content;
        
    }
}

