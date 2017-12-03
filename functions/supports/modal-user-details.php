<?php

if (!is_admin()):
    add_action('rcl_enqueue_scripts','rcl_support_user_info_scripts',10);
endif;

function rcl_support_user_info_scripts(){ 
    if(rcl_is_office()){
        rcl_dialog_scripts();
        rcl_enqueue_script( 'rcl-user-info', RCL_URL.'functions/supports/js/user-details.js' );
    }
}

add_filter('rcl_init_js_variables','rcl_init_js_user_info_variables',10);
function rcl_init_js_user_info_variables($data){

    if(rcl_is_office()){
        $data['local']['title_user_info'] = __('Detailed information','wp-recall');
    }
    
    return $data;
}

add_filter('rcl_avatar_icons','rcl_add_user_info_button',10);
function rcl_add_user_info_button($icons){
    
    rcl_dialog_scripts();
    
    $icons['user-info'] = array(
        'icon' => 'fa-info-circle',
        'atts' => array(
            'title' => __('User info','wp-recall'),
            'onclick' => 'rcl_get_user_info(this);return false;',
            'url' => '#'
        )
    );

    return $icons;
}

rcl_ajax_action('rcl_return_user_details', true);
function rcl_return_user_details(){

    wp_send_json(array(
        'content' => rcl_get_user_details(intval($_POST['user_id']))
    ));
    
}

function rcl_get_user_details($user_id, $args = false){
    global $user_LK, $rcl_blocks;
    
    $user_LK = $user_id;
    
    $defaults = array(
        'zoom' => true,
        'description' => true,
        'custom_fields' => true
    );
    
    $args = wp_parse_args( $args, $defaults );
    
    if (!class_exists('Rcl_Blocks')) 
        require_once RCL_PATH.'classes/class-rcl-blocks.php';

    $content = '<div id="rcl-user-details">';
    
    $content .= '<div class="rcl-user-avatar">';
    
    $content .= get_avatar($user_LK,300);
    
    if($args['zoom']){
    
        $avatar = get_user_meta($user_LK,'rcl_avatar',1);

        if($avatar){
            if(is_numeric($avatar)){
                $image_attributes = wp_get_attachment_image_src($avatar);
                $url_avatar = $image_attributes[0];
            }else{
                $url_avatar = $avatar;
            }
            $content .= '<a title="'.__('Zoom avatar','wp-recall').'" data-zoom="'.$url_avatar.'" onclick="rcl_zoom_avatar(this);return false;" class="rcl-avatar-zoom" href="#"><i class="fa fa-search-plus"></i></a>';

        }
    
    }
    
    $content .= '</div>';
    
    if($args['description']){
    
        $desc = get_the_author_meta('description',$user_LK);
        if($desc) 
            $content .= '<div class="ballun-status">'
            . '<p class="status-user-rcl">'.nl2br(wp_strip_all_tags($desc)).'</p>'
            . '</div>';
    
    }
    
    if($args['custom_fields']){
    
        if($rcl_blocks&&(isset($rcl_blocks['details'])||isset($rcl_blocks['content']))){

            $details = isset($rcl_blocks['details'])? $rcl_blocks['details']: array();
            $old_output = isset($rcl_blocks['content'])? $rcl_blocks['content']: array();

            $details = array_merge($details,$old_output);

            foreach($details as $a=>$detail){
                if(!isset($details[$a]['args']['order'])) 
                    $details[$a]['args']['order'] = 10;
            }

            for($a = 0;$a < count($details); $a++){

                $min = $details[$a];
                $newArray = $details;

                for($n = $a;$n < count($newArray); $n++){

                    if($newArray[$n]['args']['order']<$min['args']['order']){
                        $details[$n] = $min;
                        $min = $newArray[$n];
                        $details[$a] = $min;
                    }
                }
            }

            foreach($details as $block){
                $Rcl_Blocks = new Rcl_Blocks($block);
                $content .= $Rcl_Blocks->get_block($user_LK);
            }

        }
    
    }
    
    $content .= '</div>';
    
    return $content;
}
