<?php

/*14.0.0*/
function rcl_rename_plugin_options(){
    global $wpdb;
    
    $oldfield = $wpdb->get_var("SELECT option_name FROM $wpdb->options WHERE option_name = 'primary-rcl-options'");

    if(!$oldfield) return false;
    
    $active_addons = get_option('active_addons_recall');
    
    if($active_addons){
        $new_actives = array();
        foreach($active_addons as $addon=>$data){
            $new_actives[$addon]['path'] = $data['src'];
        }
        update_option('active_addons_recall',$new_actives);
    }
    
    $wpdb->update(
        $wpdb->options,
        array('option_name'=>'rcl_global_options'),
        array('option_name'=>'primary-rcl-options')
    );
    
    $wpdb->update(
        $wpdb->options,
        array('option_name'=>'rcl_active_addons'),
        array('option_name'=>'active_addons_recall')
    );
    
    $wpdb->update(
        $wpdb->options,
        array('option_name'=>'rcl_profile_fields'),
        array('option_name'=>'custom_profile_field')
    );
    
    $wpdb->update(
        $wpdb->options,
        array('option_name'=>'rcl_profile_default'),
        array('option_name'=>'show_defolt_field')
    );
    
    $wpdb->update(
        $wpdb->options,
        array('option_name'=>'rcl_cart_fields'),
        array('option_name'=>'custom_orders_field')
    );
    
    $wpdb->update(
        $wpdb->options,
        array('option_name'=>'rcl_fields_products'),
        array('option_name'=>'custom_saleform_fields')
    );
    
    $wpdb->update(
        $wpdb->options,
        array('option_name'=>'rcl_profile_search_fields'),
        array('option_name'=>'custom_profile_search_form')
    );
    
    $formfields = $wpdb->get_col("SELECT option_name FROM $wpdb->options WHERE option_name LIKE 'custom_fields_%'");
    
    if($formfields){
        foreach($formfields as $name){
            $newname = str_replace('custom_fields_','rcl_fields_',$name);
            $wpdb->query("UPDATE $wpdb->options SET option_name='$newname' WHERE option_name='$name'");
        }
    }
    
    $formfields = $wpdb->get_col("SELECT option_name FROM $wpdb->options WHERE option_name LIKE 'custom_public_fields_%'");
    
    if($formfields){
        foreach($formfields as $name){
            $newname = str_replace('custom_public_fields_','rcl_fields_post_',$name);
            $wpdb->query("UPDATE $wpdb->options SET option_name='$newname' WHERE option_name='$name'");
        }
    }
}