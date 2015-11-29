<?php

//формируем массив данных о вкладках личного кабинета
if(is_admin()) add_filter('tab_data_rcl','rcl_get_data_tab',10);
function rcl_get_data_tab($data){
    global $tabs_rcl;
    $tabs_rcl[$data['id']] = $data;
    return $data;
}

function rmag_global_options(){
    $content = ' <div id="recall" class="left-sidebar wrap">
    <form method="post" action="">
            '.wp_nonce_field('update-options-rmag','_wpnonce',true,false);

    $content = apply_filters('admin_options_rmag',$content);

    $content .= '<div class="submit-block">
    <p><input type="submit" class="button button-primary button-large right" name="primary-rmag-options" value="'.__('Save settings','wp-recall').'" /></p>
    </form></div>
    </div>';
    echo $content;
}

function rmag_update_options ( ) {
  if ( isset( $_POST['primary-rmag-options'] ) ) {
	if( !wp_verify_nonce( $_POST['_wpnonce'], 'update-options-rmag' ) ) return false;
	$_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);

    foreach($_POST as $key => $value){
        if($key=='primary-rmag-options') continue;
        $options[$key]=$value;
    }
    update_option('primary-rmag-options',$options);
    wp_redirect(admin_url('admin.php?page=manage-wpm-options'));
    exit;
  }
}
add_action('init', 'rmag_update_options');

