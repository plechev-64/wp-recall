<?php

class Rcl_Ajax{
    
    function __construct(){
        add_action('wp_ajax_rcl_ajax',array($this,'rcl_ajax'));
        add_action('wp_ajax_nopriv_rcl_ajax',array($this,'rcl_ajax'));
    }
    
    function rcl_ajax(){
        
        rcl_verify_ajax_nonce();
        
        $post = rcl_decode_post($_POST['post']);
        
        $post->tab_url = $_POST['tab_url'];
        
        $callback = $post->callback;
        $result['result'] = $callback($post);
        $result['post'] = $post;
        echo json_encode($result); exit;
    }
    
}
new Rcl_Ajax();
