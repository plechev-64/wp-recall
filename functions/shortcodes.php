<?php

add_shortcode('userlist','rcl_get_userlist');
function rcl_get_userlist($atts, $content = null){
    global $rcl_user,$rcl_users_set;

    require_once 'class-rcl-users.php';

    $users = new Rcl_Users($atts);

    $count_users = false;

    if(!$users->number){

        $rqst = $users->search_request();

        $search_string = ($rqst)? '&'.implode('&',$rqst): '';

        $count_users = $users->count_users();

        $rclnavi = new RCL_navi($users->inpage,$count_users,$search_string,$users->paged);
        $users->offset = $rclnavi->offset;
        $users->number = $rclnavi->inpage;
    }

    $usersdata = $users->get_users();

    $userlist = $users->get_filters($count_users);

    if(!$usersdata){
        $userlist .= '<p align="center">'.__('Users not found','wp-recall').'</p>';
        $users->remove_data();

        return $userlist;
    }

    $userlist .= '<div class="userlist '.$users->template.'-list">';

    $rcl_users_set = $users;

    foreach($usersdata as $rcl_user){ $users->setup_userdata($rcl_user);
        $userlist .= rcl_get_include_template('user-'.$users->template.'.php');
    }

    $userlist .= '</div>';

    if(isset($rclnavi->inpage)&&$rclnavi->inpage)
        $userlist .= $rclnavi->navi();

    $users->remove_data();

    return $userlist;
}

function rcl_user_name(){
    global $rcl_user;
    echo $rcl_user->display_name;
}

function rcl_user_url(){
    global $rcl_user;
    echo get_author_posts_url($rcl_user->ID);
}

function rcl_user_avatar($size=50){
    global $rcl_user;
    echo get_avatar($rcl_user->ID,$size);
}

function rcl_user_rayting(){
    global $rcl_user,$rcl_users_set;
    if(!function_exists('rcl_get_rating_block')) return false;
    if(false!==array_search('rating_total', $rcl_users_set->data)||isset($rcl_user->rating_total)){
        if(!isset($rcl_user->rating_total)) $rcl_user->rating_total = 0;
        echo rcl_rating_block(array('value'=>$rcl_user->rating_total));
    }
}

add_action('user_description','rcl_user_comments');
function rcl_user_comments(){
    global $rcl_user,$rcl_users_set;
    if(false!==array_search('comments_count', $rcl_users_set->data)||isset($rcl_user->comments_count)){
        if(!isset($rcl_user->comments_count)) $rcl_user->comments_count = 0;
        echo '<span class="filter-data"><i class="fa fa-comment"></i>'.__('Comments','wp-recall').': '.$rcl_user->comments_count.'</span>';
    }
}
add_action('user_description','rcl_user_posts');
function rcl_user_posts(){
    global $rcl_user,$rcl_users_set;
    if(false!==array_search('posts_count', $rcl_users_set->data)||isset($rcl_user->posts_count)){
        if(!isset($rcl_user->posts_count)) $rcl_user->posts_count = 0;
        echo '<span class="filter-data"><i class="fa fa-file-text-o"></i>'.__('Publics','wp-recall').': '.$rcl_user->posts_count.'</span>';
    }
}

function rcl_user_action($type=1){
    global $rcl_user;
    switch($type){
        case 1: $last_action = rcl_get_useraction($rcl_user->time_action);
                if(!$last_action) echo '<span class="status_user online"><i class="fa fa-circle"></i></span>';
                else echo '<span class="status_user offline" title="'.__('not online','wp-recall').' '.$last_action.'"><i class="fa fa-circle"></i></span>';
        break;
        case 2: echo rcl_get_miniaction($rcl_user->time_action); break;
    }
}

function rcl_user_description(){
    global $rcl_user;
    if(!$rcl_user->description) return false;
    echo '<div class="ballun-status">';
        //'<span class="ballun"></span>'
        echo '<p class="status-user-rcl">'.nl2br(esc_textarea($rcl_user->description)).'</p>
    </div>';
}

add_action('user_description','rcl_user_register');
function rcl_user_register(){
    global $rcl_user,$rcl_users_set;
    if(false!==array_search('user_registered', $rcl_users_set->data)||isset($rcl_user->user_registered)){
        if(!isset($rcl_user->user_registered)) return false;
        echo '<span class="filter-data"><i class="fa fa-calendar-check-o"></i>'.__('Registration','wp-recall').': '.mysql2date('d-m-Y', $rcl_user->user_registered).'</span>';
    }
}

add_action('user_description','rcl_filter_user_description');
function rcl_filter_user_description(){
    global $rcl_user;
    $cont = '';
    echo $cont = apply_filters('rcl_description_user',$cont,$rcl_user->ID);
}

add_filter('users_search_form_rcl','rcl_default_search_form');
function rcl_default_search_form($form){

        $search_text = ((isset($_GET['search_text'])))? $_GET['search_text']: '';
        $search_field = (isset($_GET['search_field']))? $_GET['search_field']: '';

	$form .='<div class="rcl-search-users">
                <form method="get" action="">
                    <p>'.__('Search users','wp-recall').'</p>
                    <input type="text" name="search_text" value="'.$search_text.'">
                    <select name="search_field">
                        <option '.selected($search_field,'display_name',false).' value="display_name">'.__('by name','wp-recall').'</option>
                        <option '.selected($search_field,'user_login',false).' value="user_login">'.__('by login','wp-recall').'</option>
                    </select>
                    <input type="submit" class="recall-button" name="search-user" value="'.__('Search','wp-recall').'">
                    <input type="hidden" name="default-search" value="1">
                </form>
            </div>';
	return $form;
}

add_shortcode('wp-recall','rcl_get_shortcode_wp_recall');
function rcl_get_shortcode_wp_recall(){
	global $user_LK;

	if(!$user_LK){
		return '<h4>'.__('To begin to use the capabilities of your personal account, please log in or register on this site','wp-recall').'</h4>
		<div class="authorize-form-rcl">'.rcl_get_authorize_form().'</div>';
	}

	ob_start();

	wp_recall();

	$content = ob_get_contents();
	ob_end_clean();

	return $content;
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
        //print_r($args);
	$posts = get_posts($args);

	if(!$posts) return false;

        $size = explode(',',$size);
        $size = (isset($size[1]))? $size: $size[0];

	$plslider = '<ul class="slider-rcl">';
	foreach($posts as $post){
            //if( !has_post_thumbnail($post->ID)) continue;

            $thumb_id = get_post_thumbnail_id($post->ID);
            $large_url = wp_get_attachment_image_src( $thumb_id, 'full');
            $thumb_url = wp_get_attachment_image_src( $thumb_id, $size);
            $plslider .= '<li><a href="'.get_permalink($post->ID).'">';
            if($type='products'){
                $plslider .= rcl_get_price($post->ID);
            }
            $plslider .= '<img src='.$thumb_url[0].'>';

            if($post->post_excerpt) $post_content = strip_tags($post->post_excerpt);
            else $post_content = apply_filters('the_content',strip_tags($post->post_content));

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
	$plslider .= '</ul>';

	return $plslider;
}