<?php

function rcl_login_form(){
    echo rcl_get_authorize_form('floatform');
}

add_shortcode('loginform','rcl_get_login_form');
function rcl_get_login_form($atts){
    extract(shortcode_atts(array( 'form' => false ),$atts));
    return rcl_get_authorize_form('pageform',$form);
}

function rcl_get_authorize_form($type=false,$form=false){
    global $user_ID,$rcl_user_URL,$typeform;
    $typeform = $form;
    
    $can_register = rcl_is_register_open();
    
    ob_start();

    echo '<div class="panel_lk_recall '.$type.'">';

        if($type=='floatform') echo '<a href="#" class="close-popup"><i class="fa fa-times-circle"></i></a>';
        if($user_ID){

            echo '<div class="username"><b>'.__('Hi','wp-recall').', '.get_the_author_meta('display_name', $user_ID).'!</b></div>
            <div class="author-avatar">';
            echo '<a href="'.$rcl_user_URL.'" title="'.__('To personal account','wp-recall').'">'.get_avatar($user_ID, 60).'</a>';

            if(function_exists('rcl_rating_block')):
                echo rcl_rating_block(array('ID'=>$user_ID,'type'=>'user'));
            endif;

            echo '</div>';
            
            $buttons = array(
                rcl_get_button(__('To personal account','wp-recall'),$rcl_user_URL,array('icon'=>'fa-home')),
                rcl_get_button(__('Exit','wp-recall'),wp_logout_url( home_url() ),array('icon'=>'fa-external-link'))
            );
            
            echo rcl_get_primary_widget_buttons($buttons);

        }else{

            $login_form = rcl_get_option('login_form_recall');

            if($login_form==1&&$type!='pageform'){

                $redirect_url = rcl_format_url(get_permalink(rcl_get_option('page_login_form_recall')));
                
                $buttons = array(
                    rcl_get_button(__('Entry','wp-recall'),$redirect_url.'action-rcl=login',array('icon'=>'fa-sign-in'))
                );
                
                if($can_register)
                    $buttons[] = rcl_get_button(__('Registration','wp-recall'),$redirect_url.'action-rcl=register',array('icon'=>'fa-book'));

                echo rcl_get_primary_widget_buttons($buttons);

            }else if($login_form==2){
                
                $buttons = array(
                    rcl_get_button(__('Entry','wp-recall'),esc_url(wp_login_url('/')),array('icon'=>'fa-sign-in'))
                );
                
                if($can_register)
                    $buttons[] = rcl_get_button(__('Registration','wp-recall'),esc_url(wp_registration_url()),array('icon'=>'fa-book'));

                echo rcl_get_primary_widget_buttons($buttons);

            }else if($login_form==3||$type){
                
                if($typeform!='register'){
                    rcl_include_template('form-sign.php');
                }
                if($typeform!='sign' && $can_register){
                    rcl_include_template('form-register.php');
                }
                if(!$typeform||$typeform=='sign'){
                    rcl_include_template('form-remember.php');
                }
                
            }else if(!$login_form){
                
                $buttons = array(
                    rcl_get_button(__('Entry','wp-recall'),'#',array('icon'=>'fa-sign-in','class'=>'rcl-login'))
                );
                
                if($can_register)
                    $buttons[] = rcl_get_button(__('Registration','wp-recall'),'#',array('icon'=>'fa-book','class'=>'rcl-register'));
                
                echo rcl_get_primary_widget_buttons($buttons);

            }

        }

    echo '</div>';

    if(!$user_ID&&$type) 
        echo '<script>rcl_do_action("rcl_login_form","'.$type.'")</script>';

    $html = ob_get_contents();
    ob_end_clean();

    return $html;
}

function rcl_get_primary_widget_buttons($buttons){
    
    $content = '';
    
    $buttons = apply_filters('rcl_primary_widget_buttons',$buttons);
    
    if($buttons){
        
        foreach($buttons as $button){
            $content .= sprintf('<div class="rcl-widget-button">%s</div>',$button);
        }
        
    }
    
    $content = sprintf('<div class="rcl-widget-buttons">%s</div>',apply_filters('buttons_widget_rcl',$content));
    
    return $content;
}

function rcl_get_loginform_url($type){

    if($type=='login'){
        switch(rcl_get_option('login_form_recall')){
            case 1: return rcl_format_url(get_permalink(rcl_get_option('page_login_form_recall'))).'action-rcl=login'; break;
            case 2: return wp_login_url(get_permalink(rcl_get_option('page_login_form_recall'))); break;
            default: return '#'; break;
        }
    }

    if($type=='register'){
       switch(rcl_get_option('login_form_recall')){
            case 1: return rcl_format_url(get_permalink(rcl_get_option('page_login_form_recall'))).'action-rcl=register'; break;
            case 2: return wp_registration_url(); break;
            default: return '#'; break;
        }
    }

}