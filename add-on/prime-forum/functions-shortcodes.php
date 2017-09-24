<?php

add_action('pfm_init','pfm_setup_shortcodes',10);
function pfm_setup_shortcodes(){
    global $PrimeShorts;
    $PrimeShorts = pfm_get_shortcodes();   
}

function pfm_get_shortcodes(){
    global $shortcode_tags;
    
    $whiteList = array(
        'spoiler',
        'offtop'
    );
    
    $whiteList = apply_filters('pfm_whitelist_shortcodes',$whiteList);
    
    $PrimeShorts = array();
    foreach($shortcode_tags as $tag => $function){       
        if(!in_array($tag,$whiteList)) continue;       
        $PrimeShorts[$tag] = $function;
    }
    
    return $PrimeShorts;
            
}

add_filter('pfm_whitelist_shortcodes','pfm_add_admin_support_shortcodes',10);
function pfm_add_admin_support_shortcodes($whiteList){
    $adminShorts = array_map('trim',explode("\n",pfm_get_option('support-shortcodes')));
    if(!$adminShorts) return $whiteList;
    $whiteList = array_merge($whiteList,$adminShorts);
    return $whiteList;
}

function pfm_do_shortcode( $content, $ignore_html = false ) {
    global $PrimeShorts;

    if ( false === strpos( $content, '[' ) ) {
            return $content;
    }

    if (empty($PrimeShorts) || !is_array($PrimeShorts))
            return $content;

    // Find all registered tag names in $content.
    preg_match_all( '@\[([^<>&/\[\]\x00-\x20=]++)@', $content, $matches );
    $tagnames = array_intersect( array_keys( $PrimeShorts ), $matches[1] );
    
    if ( empty( $tagnames ) ) {
            return $content;
    }

    $content = do_shortcodes_in_html_tags( $content, $ignore_html, $tagnames );

    $pattern = get_shortcode_regex( $tagnames );
    $content = preg_replace_callback( "/$pattern/", 'do_shortcode_tag', $content );

    // Always restore square braces so we don't break things like <!--[if IE ]>
    $content = unescape_invalid_shortcodes( $content );

    return $content;
}

add_shortcode('spoiler','pfm_get_spoiler_content');
function pfm_get_spoiler_content($attrs, $content){   
    return '<div class="prime-spoiler">'
        . '<a href="#" class="prime-spoiler-link" onclick="pfm_spoiler(this); return false;">'
            . '<i class="fa fa-plus-square-o"></i> '.__('Spoiler','wp-recall')
        . '</a>'
        . '<div class="prime-spoiler-content">'
            .$content
        .'</div>'
    . '</div>';  
}

add_shortcode('offtop','pfm_get_offtop_content');
function pfm_get_offtop_content($attrs, $content){   
    return '<div class="prime-offtop">'
        . '<span class="prime-offtop-title">'
            . '<i class="fa fa-coffee"></i> '.__('Off-topic','wp-recall')
        . '</span>'
        . '<div class="prime-offtop-content">'
            .$content
        .'</div>'
    . '</div>';  
}

add_shortcode('prime-forum','pfm_get_forum_content');
function pfm_get_forum_content(){
    
    return pfm_get_template_content();
    
}

add_shortcode('prime-posts','pfm_get_posts_shortcode');
function pfm_get_posts_shortcode($attrs){
    
    require_once 'classes/class-prime-last-posts.php';
    
    $LastPosts = new PrimeLastPosts($attrs);
    
    if(!$LastPosts->posts){
        return '<p>'.__('Not found','wp-recall').'</p>';
    }

    $content = $LastPosts->get_content();
    
    return $content;
    
}