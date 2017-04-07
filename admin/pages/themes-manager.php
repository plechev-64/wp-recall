<?php

global $active_addons,$Rcl_Templates_Manager;

$Rcl_Templates_Manager->get_templates_data();

$cnt_all = $Rcl_Templates_Manager->template_number;

echo '</pre><div class="wrap">'; 

echo '<div id="icon-plugins" class="icon32"><br></div>
    <h2>'.__('Templates','wp-recall').' Wp-Recall</h2>';

    if(isset($_POST['save-rcl-key'])){
        if( wp_verify_nonce( $_POST['_wpnonce'], 'add-rcl-key' ) ){
            update_option('rcl-key',$_POST['rcl-key']);
            echo '<div id="message" class="'.$type.'"><p>'.__('Key has been saved','wp-recall').'!</p></div>';
        }
    }
    
    echo '<div class="rcl-admin-service-box rcl-key-box">';

    echo '<h4>'.__('RCLKEY','wp-recall').'</h4>
    <form action="" method="post">
        '.__('Enter RCLKEY','wp-recall').' <input type="text" name="rcl-key" value="'.get_option('rcl-key').'">
        <input class="button" type="submit" value="'.__('Save','wp-recall').'" name="save-rcl-key">
        '.wp_nonce_field('add-rcl-key','_wpnonce',true,false).'
    </form>
    <p class="install-help">'.__('Required to update the templates here. Get it  in  your account online <a href="http://codeseller.ru/" target="_blank">http://"codeseller.ru</a>','wp-recall').'</p>';

    echo '</div>';
    
    echo '<div class="rcl-admin-service-box rcl-upload-form-box upload-template">';
    
    echo '<h4>'.__('Install the add-on to WP-Recall format .ZIP','wp-recall').'</h4>
    <p class="install-help">'.__('If you have an archive template for wp-recall format .zip, here you can upload and install it','wp-recall').'</p>
    <form class="wp-upload-form" action="" enctype="multipart/form-data" method="post">
        <label class="screen-reader-text" for="addonzip">'.__('Add-on archive','wp-recall').'</label>
        <input id="addonzip" type="file" name="addonzip">
        <input id="install-plugin-submit" class="button" type="submit" value="'.__('Install','wp-recall').'" name="install-template-submit">
        '.wp_nonce_field('install-template-rcl','_wpnonce',true,false).'
    </form>
    
    </div>

    <ul class="subsubsub">
        <li class="all"><b>'.__('All','wp-recall').'<span class="count">('.$cnt_all.')</span></b></li>
    </ul>';

$Rcl_Templates_Manager->prepare_items(); ?>

<form method="post">
<input type="hidden" name="page" value="manage-addon-recall">
<?php
$Rcl_Templates_Manager->search_box( 'Search by name', 'search_id' );
$Rcl_Templates_Manager->display(); 
echo '</form></div>'; 

