<?php

require_once 'settings.php';

add_action('admin_menu', 'rcl_profile_admin_menu',30);
function rcl_profile_admin_menu(){
    add_submenu_page( 'manage-wprecall', __('Profile fields','wp-recall'), __('Profile fields','wp-recall'), 'manage_options', 'manage-userfield', 'rcl_profile_fields_manager');
}

add_action('rcl_update_custom_fields','rcl_update_page_users',10);
function rcl_update_page_users(){
    global $rcl_options;
    
    if(!isset($_POST['users_page_rcl'])) return false;
    
    $rcl_options['users_page_rcl'] = $_POST['users_page_rcl'];
    
    update_option('rcl_global_options', $rcl_options );
    
}

add_filter('rcl_inactive_profile_fields','rcl_add_default_profile_fields',10);
function rcl_add_default_profile_fields($fields){
    
    $fields[] = array(
        'slug' => 'first_name',
        'title' => __('Firstname','wp-recall'),
        'type' => 'text'
    );
    
    $fields[] = array(
        'slug' => 'last_name',
        'title' => __('Surname','wp-recall'),
        'type' => 'text'
    );
    
    $fields[] = array(
        'slug' => 'display_name', 
        'title' => __('Name to be displayed','wp-recall'),
        'type' => 'text'
    );
    
    $fields[] = array(
        'slug' => 'url',
        'title' => __('Website','wp-recall'),
        'type' => 'url'
    );
    
    $fields[] = array(
        'slug' => 'description',
        'title' => __('Status','wp-recall'),
        'type' => 'textarea'
    );
    
    return $fields;
    
}
 
add_filter('rcl_custom_field_options','rcl_add_register_profile_field_option',10,3);
function rcl_add_register_profile_field_option($options, $field, $type){
    
    if($type != 'profile' || !rcl_is_register_open()) return $options;
    
    $options[] = array(
        'type' => 'select',
        'slug'=>'register',
        'title'=>__('display in registration form','wp-recall'),
        'values'=>array(__('No','wp-recall'),__('Yes','wp-recall'))
    );
    
    return $options;
    
}

function rcl_profile_fields_manager(){
    global $rcl_options;

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
            'user_pass',
            'first_name',
            'last_name',
            'description',
            'url',
            'display_name',
            'user_email',
            'repeat_pass',
            'show_admin_bar_front'
        )
    );

    $get_fields = rcl_get_profile_fields($args);

    $cf = new Rcl_Custom_Fields();

    if($get_fields){
        $field = '<h3>'.__('Custom Profile Fields','wp-recall').':</h3>
        <table class="form-table rcl-table">';
        foreach($get_fields as $custom_field){
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

