<?php

function pfm_sort_array_by_string($a, $b){
    if (strlen($a) < strlen($b)) { return 1; } elseif (strlen($a) == strlen($b)) { return 0; } else { return -1; }
}

add_action('pfm_init','pfm_reset_oembed_filter');
add_action('pfm_pre_ajax_action','pfm_reset_oembed_filter');
function pfm_reset_oembed_filter(){
    remove_filter( 'pre_oembed_result', 'wp_filter_pre_oembed_result', 10 );
}

add_filter('pfm_the_post_content','pfm_filter_content',10);
function pfm_filter_content($content){

    preg_match_all('/<pre>(.+)<\/pre>/Uuis', $content, $pres);
    
    if($pres){
        
        foreach( $pres[0] as $k=>$pre ){

            $content = str_replace($pre, '<!--pre'.$k.'-->', $content);

        }
    }
    
    preg_match_all('/<code>(.+)<\/code>/Uuis', $content, $codes);
    
    if($codes){
        
        foreach( $codes[0] as $k=>$code ){

            $content = str_replace($code, '<!--code'.$k.'-->', $content);

        }
    }
    
    $content = apply_filters('pfm_content_without_code',$content);

    if($codes){
        
        foreach( $codes[1] as $k=>$codeContent ){

            $content = str_replace('<!--code'.$k.'-->', '<code>'.esc_html($codeContent).'</code>', $content);

        }
    }
    
    if($pres){
        
        foreach( $pres[1] as $k=>$preContent ){
            
            $content = str_replace(
            array(
                '<!--pre'.$k.'-->',
                '&lt;!--pre'.$k.'--&gt;'
            ), 
            array(
                '<pre>'.esc_html($preContent).'</pre>',
                esc_html('<pre>'.$preContent.'</pre>')
            ), $content);

        }
    }
    
    return $content;
}

add_filter('pfm_content_without_code','pfm_filter_allowed_tags',10);
function pfm_filter_allowed_tags($content){
    
    $allowed_tags = apply_filters('pfm_content_allowed_tags', array(
        'a' => array(
            'href' => true,
            'title' => true,
	),
        'img' => array(
            'src' => true,
            'alt' => true,
            'class' => true,
	),
        'p' => array(
            'style' => true
        ),
        'blockquote' => array(),
        'h3' => array(),
        'code' => array(),
        'pre' => array(),
        'del' => array(),
        'b' => array(),
        's' => array(),
	'br' => array(),
	'em' => array(),
	'strong' => array(),
        'details' => array(),
        'summary' => array(),
        'span' => array(
            'class' => true,
            'style' => true
        )
    ));
    
    $content = wp_kses($content, $allowed_tags);
    
    return $content;
    
}

add_filter('pfm_content_without_code','pfm_filter_urls',11);
function pfm_filter_urls($content){

    preg_match_all("/(\s|^|])(https?:[_a-z0-9\/\.%+\-#?=&]+)/ui", $content, $urls);
    
    if($urls[0]){
        
        $oembedSupport = (pfm_get_option('support-oembed') && function_exists('wp_oembed_get'))? true: false;
        
        $sortStrings = $urls[2];
        
        usort($sortStrings, 'pfm_sort_array_by_string');
        
        $replace = array();
        
        foreach( $sortStrings as $k => $url ){
            
            if($oembedSupport){
                
                $oembed = wp_oembed_get($url, array('width'=>400, 'height'=>400, 'discover'=>false));
                
                if($oembed){
                    $replace[] = $oembed;
                    //$content = str_replace($url,$oembed,$content);
                    continue;
                }
            
            }
            
            if(pfm_get_option('view-links') || pfm_is_can('post_create')){

                $replace[] = ' <a href="'.$url.'" target="_blank" rel="nofollow">'.$url.'</a>';

            }else{

                $replace[] = pfm_get_notice(__('You are unable to view published links','wp-recall'),'warning');

            }
            
            //$content = preg_replace('/(\s|^|])('.str_replace(array('/','?'),array('\/','\?'),$url).')/ui', $replace, $content);

        }
        
        if($replace) $content = str_replace($sortStrings,$replace,$content);
    
    }
    
    
    return $content;
}

add_filter('pfm_content_without_code','pfm_filter_links',12);
function pfm_filter_links($content){
    
    preg_match_all('/<a(.+)href=([^\s].+)>(.+)<\/a>/iUus', $content, $links);
    
    if($links[0]){
        
        foreach( $links[0] as $k=>$link ){
            
            if(pfm_get_option('view-links') || pfm_is_can('post_create')){
                
                $replace = '<a href='.$links[2][$k].' target="_blank" rel="nofollow">'.$links[3][$k].'</a>';

            }else{

                $replace = pfm_get_notice(__('You are unable to view published links','wp-recall'),'warning');

            }

            $content = str_replace($link, $replace, $content);

        }
    
    }
    
    return $content;
}

add_filter('pfm_content_without_code','pfm_filter_smilies',13);
function pfm_filter_smilies($content){
    
    if(function_exists('convert_smilies')) 
        $content = str_replace( 'style="height: 1em; max-height: 1em;"', '', convert_smilies( $content ) );
    
    return $content;
}

add_filter('pfm_content_without_code','wpautop',14);
add_filter('pfm_content_without_code','pfm_do_shortcode',15);

add_filter('pfm_the_post_content','pfm_add_topic_meta_box',20);
function pfm_add_topic_meta_box($content){
    global $PrimeTopic,$PrimePost;
    
    if($PrimePost->post_index != 1) return $content;
    
    $content = pfm_get_topic_meta_box($PrimeTopic->topic_id) . $content;
    
    return $content;
}

add_filter('pfm_the_post_content','pfm_add_post_edition',25);
function pfm_add_post_edition($content){
    global $PrimePost;

    if(!$PrimePost->post_edit) return $content;
    
    $postEdition = pfm_get_post_edition();
    
    if(!$postEdition) return $content;
    
    $content .= '<div class="post-edit-list">';
    
    $content .= '<div class="post-edit-title">'.__('The wording of the message','wp-recall').'</div>';
    
    foreach($postEdition as $edit){
        $content .= '<div class="post-edit-item">'
                . '<span class="edit-time">'.mysql2date('d.m.Y H:i', $edit['time']).'</span>'
                . '<span class="edit-author">'.$edit['author'].'</span>'
                . '<span class="edit-reason">'.__('The reason','wp-recall').': '.($edit['reason']? $edit['reason']: __('not specified','wp-recall')).'</span>'
                . '</div>';
    }
    
    $content .= '</div>';

    return $content;
}

