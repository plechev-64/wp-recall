<?php

function rcl_ajax_action($function_name, $guest_access = false){
    
    add_action('wp_ajax_'.$function_name, $function_name);
    
    if($guest_access)
        add_action('wp_ajax_nopriv_'.$function_name, $function_name);
    
}

