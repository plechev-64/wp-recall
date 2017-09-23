<?php

add_shortcode('wp-recall','rcl_get_shortcode_wp_recall');
function rcl_get_shortcode_wp_recall(){
    global $user_LK;

    if(!$user_LK){
        return '<h4>'.__('To use your personal account, please log in or register on this site','wp-recall').'</h4>
        <div class="authorize-form-rcl">'.rcl_get_authorize_form().'</div>';
    }

    ob_start();

    wp_recall();

    $content = ob_get_contents();
    ob_end_clean();

    return $content;
}

add_shortcode('userlist','rcl_get_userlist');
function rcl_get_userlist($atts){
    global $rcl_user,$rcl_users_set,$user_ID;
    
    require_once RCL_PATH.'classes/class-rcl-users-list.php';

    $users = new Rcl_Users_List($atts);
    
    $count_users = false;

    if(!isset($atts['number'])){

        $count_users = $users->count();
        
        $id_pager = ($users->id)? 'rcl-users-'.$users->id: 'rcl-users';
        
        $pagenavi = new Rcl_PageNavi($id_pager,$count_users,array('in_page'=>$users->query['number']));
        
        $users->query['offset'] = $pagenavi->offset;
    }

    $timecache = ($user_ID && $users->query['number']=='time_action')? rcl_get_option('timeout',600): 0;

    $rcl_cache = new Rcl_Cache($timecache);
        
    if($rcl_cache->is_cache){
        if(isset($users->id) && $users->id=='rcl-online-users') 
            $string = json_encode($users);
        else 
            $string = json_encode($users->query);

        $file = $rcl_cache->get_file($string);

        if(!$file->need_update){
            
            $users->remove_filters();
            
            return $rcl_cache->get_cache();

        }
        
    }
    
    $usersdata = $users->get_users();
    
    $userlist = $users->get_filters($count_users);

    if(!$usersdata){
        $userlist .= '<p align="center">'.__('Users not found','wp-recall').'</p>';
        $users->remove_filters();

        return $userlist;
    }
    
    if(!isset($atts['number']) && $pagenavi->in_page)
        $userlist .= $pagenavi->pagenavi();

    $userlist .= '<div class="userlist '.$users->template.'-list">';

    $rcl_users_set = $users;

    foreach($usersdata as $rcl_user){ $users->setup_userdata($rcl_user);
        $userlist .= rcl_get_include_template('user-'.$users->template.'.php');
    }

    $userlist .= '</div>';

    if(!isset($atts['number']) && $pagenavi->in_page)
        $userlist .= $pagenavi->pagenavi();

    $users->remove_filters();
    
    if($rcl_cache->is_cache){        
        $rcl_cache->update_cache($userlist);        
    }

    return $userlist;
}

add_shortcode('slider-rcl','rcl_slider');
function rcl_slider($atts, $content = null){
    
    rcl_bxslider_scripts();

    extract(shortcode_atts(array(
	'num' => 5,
	'term' => '',
        'type' => 'post',
        'post_meta' => false,
        'meta_value' => false,
        'tax' => 'category',
	'exclude' => false,
        'include' => false,
	'orderby'=> 'post_date',
	'title'=> true,
	'desc'=> 280,
        'order'=> 'DESC',
        'size'=> '9999,300'
	),
    $atts));

    $args = array(
        'numberposts'     => $num,
        'orderby'         => $orderby,
        'order'           => $order,
        'exclude'         => $exclude,
        'post_type'       => $type,
        'post_status'     => 'publish',
        'meta_key'        => '_thumbnail_id'
    );

    if($term)
	$args['tax_query'] = array(
            array(
                'taxonomy'=>$tax,
                'field'=>'id',
                'terms'=> explode(',',$term)
            )
	);

	if($post_meta)
		$args['meta_query'] = array(
            array(
                'key'=>$post_meta,
                'value'=>$meta_value
            )
	);
        
	$posts = get_posts($args);

	if(!$posts) return false;

        $size = explode(',',$size);
        $size = (isset($size[1]))? $size: $size[0];

	$plslider = '<ul class="slider-rcl">';
	foreach($posts as $post){

            $thumb_id = get_post_thumbnail_id($post->ID);
            $large_url = wp_get_attachment_image_src( $thumb_id, 'full');
            $thumb_url = wp_get_attachment_image_src( $thumb_id, $size);
            $plslider .= '<li><a href="'.get_permalink($post->ID).'">';
            if($type=='products'){
                $plslider .= rcl_get_price($post->ID);
            }
            $plslider .= '<img src='.$thumb_url[0].'>';

            if($post->post_excerpt) $post_content = strip_tags($post->post_excerpt);
            else $post_content = strip_tags($post->post_content);

            if($desc > 0 && strlen($post_content) > $desc){
                    $post_content = substr($post_content, 0, $desc);
                    $post_content = preg_replace('@(.*)\s[^\s]*$@s', '\\1 ...', $post_content);
            }
            $plslider .= '<div class="content-slide">';
            if($title) $plslider .= '<h3>'.$post->post_title.'</h3>';
            if($desc > 0 )$plslider .= '<p>'.$post_content.'</p>';
            $plslider .= '</div>';
            $plslider .= '</a></li>';

	}
	$plslider .= '</ul>'
                . '<script>rcl_do_action("rcl_slider");</script>';

	return $plslider;
}

add_shortcode('rcl-cache','rcl_cache_shortcode');
function rcl_cache_shortcode($atts,$content = null){
    global $post;

    extract(shortcode_atts(array(
	'key' => '',
        'only_guest' => false,
        'time' => false
	),
    $atts));
    
    if($post->post_status=='publish'){
    
        $key .= '-cache-'.$post->ID;

        $rcl_cache = new Rcl_Cache($time,$only_guest);

        if($rcl_cache->is_cache){

            $file = $rcl_cache->get_file($key);

            if(!$file->need_update){
                return $rcl_cache->get_cache();
            }

        }
    
    }
    
    $content = do_shortcode( shortcode_unautop( $content ) );
    if ( '</p>' == substr( $content, 0, 4 )
    and '<p>' == substr( $content, strlen( $content ) - 3 ) )
    $content = substr( $content, 4, strlen( $content ) - 7 );
    
    if($post->post_status=='publish'){

        if($rcl_cache->is_cache){
            $rcl_cache->update_cache($content);
        }
    
    }
    
    return $content;
}

add_shortcode('rcl-tab','rcl_tab_shortcode');
function rcl_tab_shortcode($atts){
    global $user_ID,$user_LK;
    
    $user_LK = $user_ID;
    
    extract(shortcode_atts(array(
	'tab_id' => '',
        'subtab_id' => ''
	),
    $atts));
    
    if(!$user_ID){
        return '<h4>'.__('To use your personal account, please log in or register on this site','wp-recall').'</h4>
        <div class="authorize-form-rcl">'.rcl_get_authorize_form().'</div>';
    }
    
    $tab = rcl_get_tab($tab_id);
    
    if(!$tab_id||!$tab) 
        return '<p>'.__('Such tab was not found!','wp-recall').'</p>';
    
    if (!class_exists('Rcl_Tab')) 
        require_once RCL_PATH.'classes/class-rcl-tab.php';

    $Rcl_Tab = new Rcl_Tab($tab);
    
    if(!$Rcl_Tab->is_user_access($user_ID)) return false;
    
    $content = '<div id="rcl-office" class="wprecallblock" data-account="'.$user_ID.'">';   
        $content .= '<div id="lk-content">';
            
            $content .= sprintf('<div id="tab-%s" class="%s_block recall_content_block %s">%s</div>',$tab_id,$tab_id,'active',$Rcl_Tab->get_tab_content($user_ID, $subtab_id));

        $content .= '</div>';    
    $content .= '</div>';
    
    return $content;
}