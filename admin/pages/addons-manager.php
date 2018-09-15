<?php

rcl_dialog_scripts();

global $active_addons,$Rcl_Addons_Manager;
        
$Rcl_Addons_Manager->get_addons_data();

$cnt_all = count($Rcl_Addons_Manager->addons_data);
$cnt_act = count($active_addons);
$cnt_inact = $cnt_all - $cnt_act;

echo '</pre><div class="wrap">'; 

echo '<div id="icon-plugins" class="icon32"><br></div>
    <h2>'.__('WP-Recall Add-ons','wp-recall').'</h2>';

    if(isset($_GET['update-addon'])){
        switch($_GET['update-addon']){
            case 'activate': $text_notice = __('Addition <strong>activated</strong>. New settings may be available on  WP-Recall page','wp-recall'); $type='updated'; break;
            case 'deactivate': $text_notice = __('Addition <strong>deactivated</strong>.','wp-recall'); $type='updated'; break;
            case 'delete': $text_notice = __('Files and data additions have been <strong>deleted</strong>.','wp-recall'); $type='updated'; break;
            case 'error-info': $text_notice = __('Add-on has not been loaded. Correct headers not found.','wp-recall'); $type='error'; break;
            case 'error-activate': $text_notice = $_GET['error-text']; $type='error'; break;
        }

        echo '<div id="message" class="'.$type.'"><p>'.$text_notice.'</p></div>';
    }

    if(isset($_POST['save-rcl-key'])){
        if( wp_verify_nonce( $_POST['_wpnonce'], 'add-rcl-key' ) ){
            update_option('rcl-key',$_POST['rcl-key']);
            echo '<div id="message" class="updated"><p>'.__('Key has been saved','wp-recall').'!</p></div>';
        }
    }
    
    echo '<div class="rcl-admin-service-box rcl-key-box">';

    echo '<h4>'.__('RCLKEY','wp-recall').'</h4>
    <form action="" method="post">
        '.__('Enter RCLKEY','wp-recall').' <input type="text" name="rcl-key" value="'.get_option('rcl-key').'">
        <input class="button" type="submit" value="'.__('Save','wp-recall').'" name="save-rcl-key">
        '.wp_nonce_field('add-rcl-key','_wpnonce',true,false).'
    </form>
    <p class="install-help">'.__('The key is required to update the add-ons here. You can get it in your personal account online <a href="http://codeseller.ru/" target="_blank">http://codeseller.ru</a>','wp-recall').'</p>';

    echo '</div>';
    
    echo '<div class="rcl-admin-service-box rcl-upload-form-box upload-addon">';

    echo '<h4>'.__('Install the add-on to WP-Recall format .ZIP','wp-recall').'</h4>
    <p class="install-help">'.__('If you have the add-on archive for WP-Recall format .zip, you can upload and install it here.','wp-recall').'</p>
    <form class="wp-upload-form" action="" enctype="multipart/form-data" method="post">
        <label class="screen-reader-text" for="addonzip">'.__('Add-on archive','wp-recall').'</label>
        <input id="addonzip" type="file" name="addonzip">
        <input id="install-plugin-submit" class="button" type="submit" value="'.__('Install','wp-recall').'" name="install-addon-submit">
        '.wp_nonce_field('install-addons-rcl','_wpnonce',true,false).'
    </form>
    
    </div>

    <ul class="subsubsub">
        <li class="all"><b>'.__('All','wp-recall').'<span class="count">('.$cnt_all.')</span></b>|</li>
        <li class="active"><b>'.__('Active','wp-recall').'<span class="count">('.$cnt_act.')</span></b>|</li>
        <li class="inactive"><b>'.__('Inactive','wp-recall').'<span class="count">('.$cnt_inact.')</span></b></li>
    </ul>';

$Rcl_Addons_Manager->prepare_items(); ?>

<form method="post" class="rcl-repository-list">
<input type="hidden" name="page" value="manage-addon-recall">
<?php
$Rcl_Addons_Manager->search_box( 'Search by name', 'search_id' );
$Rcl_Addons_Manager->display(); 
echo '</form></div>'; 

