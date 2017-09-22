<?php

class Rcl_Tab_Ajax{
    
    function __construct(){
        add_action('wp_ajax_rcl_ajax',array($this,'rcl_ajax'));
        add_action('wp_ajax_nopriv_rcl_ajax',array($this,'rcl_ajax'));
    }
    
    function rcl_ajax(){
        global $rcl_tabs;
        
        rcl_verify_ajax_nonce();
        
        do_action('rcl_init_ajax_tab');
        
        $post = rcl_decode_post($_POST['post']);
        
        $post->tab_url = (isset($_POST['tab']))? $_POST['tab_url'].'&tab='.$_POST['tab']: $_POST['tab_url'];
        
        $post->supports = $rcl_tabs[$post->tab_id]['supports'];
        
        $callback = $post->callback;
        
        $content = apply_filters('rcl_ajax_tab_content',$callback($post));

        $result['result'] = $content;
        $result['post'] = $post;
        
        $result = apply_filters('rcl_ajax_tab_result', $result);
        
        wp_send_json($result);
    }
    
}

new Rcl_Tab_Ajax();
