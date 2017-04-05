<?php

add_shortcode('public-form','rcl_publicform');
function rcl_publicform($atts, $content = null){
    $form = new Rcl_Public_Form($atts);
    return $form->get_form();
}

add_shortcode('gallery-rcl','rcl_shortcode_gallery');
function rcl_shortcode_gallery($atts, $content = null){
    global $post;

    rcl_bxslider_scripts();

    extract(shortcode_atts(array(
            'post_id' => false
    ),
    $atts));

    $post_id = $post->ID;

    $args = array(
        'post_parent' => $post_id,
        'post_type'   => 'attachment',
        'numberposts' => -1,
        'post_status' => 'any',
        'post_mime_type'=> 'image'
    );
    
    $childrens = get_children($args);

    if( $childrens ){
        $gallery = '<ul class="rcl-gallery">';
        foreach((array) $childrens as $children ){
                $large = wp_get_attachment_image_src( $children->ID, 'large' );
                $gallery .= '<li><a class="fancybox" href="'.$large[0].'"><img src="'.$large[0].'"></a></li>';
                $thumbs[] = $large[0];
        }
        $gallery .= '</ul>'
                . '<script>rcl_do_action("rcl_slider");</script>';

        if(count($thumbs)>1){
                $gallery .= '<div id="bx-pager">';
                        foreach($thumbs as $k=>$src ){
                                $gallery .= '<a data-slide-index="'.$k.'" href=""><img src="'.$src.'" /></a>';
                        }
                $gallery .= '</div>';
        }
    }

    return $gallery;
}
