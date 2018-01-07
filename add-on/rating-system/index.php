<?php

require_once 'classes/rcl-rating-query.php';
require_once 'classes/class-rcl-rating-box.php';

require_once 'core.php';

if (is_admin())
    require_once 'admin/index.php';

if (!is_admin()):
    add_action('rcl_enqueue_scripts','rcl_rating_scripts',10);
endif;

function rcl_rating_scripts(){
    rcl_enqueue_style('rcl-rating-system',rcl_addon_url('style.css', __FILE__));
    rcl_enqueue_script( 'rcl-rating-system', rcl_addon_url('js/scripts.js', __FILE__) );
}

add_action('init','rcl_register_rating_base_type',30);
function rcl_register_rating_base_type(){
    
    rcl_register_rating_type(
        array(
            'post_type'=>'post',
            'type_name'=>__('Posts','wp-recall'),
            'style'=>true,
            'data_type'=>true,
            'limit_votes'=>true,
            'icon'=>'fa-thumbs-o-up'
	)
    );
    
    rcl_register_rating_type(
        array(
            'rating_type'=>'comment',
            'type_name'=>__('Comments','wp-recall'),
            'style'=>true,
            'data_type'=>true,
            'limit_votes'=>true,
            'icon'=>'fa-thumbs-o-up'
        )
    );
    
}

add_action('init','rcl_add_rating_tab');
function rcl_add_rating_tab(){
    global $user_LK;
    
    $count = 0;
    if(!is_admin() && rcl_is_office()){
        $count = rcl_format_rating(rcl_get_user_rating($user_LK));
    }
    
    $tab_data = array(
        'id'=>'rating', 
        'name'=>__('Rating','wp-recall'),
        'supports'=>array('ajax','cache'),
        'public'=>1,
        'icon'=>'fa-balance-scale',
        'output'=>'counters',
        'counter'=>$count
    );
    
    rcl_tab($tab_data);
}

add_action('rcl_setup_tabs','rcl_rating_tab_add_types_data',10);
function rcl_rating_tab_add_types_data(){
    global $rcl_rating_types;
    
    $types = array();
    
    foreach($rcl_rating_types as $type){
        
        $types[] = $type['rating_type'];

        if(!rcl_get_option('rating_user_'.$type['rating_type'])) continue;
        
        $args = array(
            'rating_type'=>$type['rating_type'],
            'rating_status'=>'user'
        );
        
        $subtab = array(
            'id' => $type['rating_type'],
            'name' => $type['type_name'],
            'icon' => (isset($type['icon']))? $type['icon']: 'fa-list-ul',
            'callback' => array(
                'name'=>'rcl_rating_get_list_votes_content',
                'args'=>array($args)
            )
        );

        rcl_add_sub_tab('rating',$subtab);

    }
    
    if(rcl_get_option('rating_custom')){
        
        $subtab = array(
            'id' => 'custom',
            'name' => __('Разное','wp-recall'),
            'icon' => 'fa-list-ul',
            'callback' => array(
                'name'=>'rcl_rating_get_list_votes_content',
                'args'=>array(array(
                    'rating_status' => 'user',
                    'rating_type__not_in' => $types
                ))
            )
        );

        rcl_add_sub_tab('rating',$subtab);
        
    }

}

add_action( 'wp', 'rcl_add_data_rating_posts');
function rcl_add_data_rating_posts(){
    global $wp_query,$wpdb;
    
    if(!$wp_query->is_tax && !$wp_query->is_archive) return false;

    $users = array();
    $posts = array();
    $post_types = array();
    $ratingsnone = array();

    foreach($wp_query->posts as $post){
        $users[] = $post->post_author;
        $post_types[] = $post->post_type;
        $posts[] = $post->ID;
    }

    $users = array_unique($users);
    $post_types = array_unique($post_types);

    if($posts){

        $ratingsnone = $wpdb->get_results("SELECT post_id,meta_value FROM $wpdb->postmeta WHERE meta_key='rayting-none' AND post_id IN (".implode(',',$posts).")");
        
        foreach($ratingsnone as $val){
            $none[$val->post_id] = $val->meta_value;
        }

    }

    $rating_authors = rcl_get_rating_users(array(
        'user_id__in' => $users
    ));

    $rating_posts = rcl_get_rating_totals(array(
        'rating_type__in' => $post_types,
        'object_id__in' => $posts,
        'fields' => array(
            'rating_total',
            'object_id'
        )
    ));

    if($rating_authors){
        foreach($rating_authors as $rating){
            $rt_authors[$rating->user_id] = $rating->rating_total;
        }
    }

    if($rating_posts){
        foreach($rating_posts as $rating){
            $rt_posts[$rating->object_id] = $rating->rating_total;
        }
    }

    foreach($wp_query->posts as $post){
        $post->rating_author = (isset($rt_authors[$post->post_author]))? $rt_authors[$post->post_author]: 0;
        $post->rating_total = (isset($rt_posts[$post->ID]))? $rt_posts[$post->ID]: 0;
        $post->rating_none = (isset($none[$post->ID]))? $none[$post->ID]: 0;
    }
    
}

add_filter('rcl_rating_user_vote', 'rcl_get_comment_user_vote', 10, 2);
function rcl_get_comment_user_vote($user_vote, $rating){
    if($rating->rating_type == 'comment'){
        global $comment;
        if($comment->comment_ID == $rating->object_id){
            return $comment->user_vote;
        }
    }
    
    return $user_vote;
}

add_filter('comments_array','rcl_add_data_rating_comments');
function rcl_add_data_rating_comments($comments){
    global $user_ID;

    if(!$comments || !rcl_get_option('rating_comment')) return $comments;

    $users = array();
    $comms = array();

    foreach($comments as $comment){
        $users[] = $comment->user_id;
        $comms[] = $comment->comment_ID;
    }
    
    $users = array_unique($users);

    $rating_authors = rcl_get_rating_users(array(
        'user_id__in' => $users
    ));

    $rating_comments = rcl_get_rating_totals(array(
        'rating_type' => 'comment',
        'object_id__in' => $comms,
        'fields' => array(
            'rating_total',
            'object_id'
        )
    ));

    $rating_values = rcl_get_vote_values(array(
        'rating_type' => 'comment',
        'object_id__in' => $comms,
        'fields' => array(
            'rating_value',
            'object_id',
            'user_id'
        )
    ));
    
    if($rating_authors){
        foreach($rating_authors as $rating){
            $rt_authors[$rating->user_id] = $rating->rating_total;
        }
    }

    if($rating_comments){
        foreach($rating_comments as $rating){
            $rt_comments[$rating->object_id] = $rating->rating_total;
        }
    }

    $user_votes = array();
    
    if($rating_values){
        
        foreach($rating_values as $rating){
            
            if($rating->user_id == $user_ID){
                $user_votes[$rating->object_id] = $rating->rating_value;
            }
            
            if(!isset($rt_values[$rating->object_id])) 
                $rt_values[$rating->object_id] = 0;
            
            if($rating->rating_value>0){
                $rt_values[$rating->object_id] += 1;
            }else{
                $rt_values[$rating->object_id] -= 1;
            }
            
        }
    }
    
    foreach($comments as $k => $comment){
        $comments[$k]->rating_author = (isset($rt_authors[$comment->user_id]))? $rt_authors[$comment->user_id]: 0;
        $comments[$k]->user_vote = (isset($user_votes[$comment->comment_ID]))? $user_votes[$comment->comment_ID]: 0;
        $comments[$k]->rating_total = (isset($rt_comments[$comment->comment_ID]))? $rt_comments[$comment->comment_ID]: 0;
        $comments[$k]->rating_votes = (isset($rt_values[$comment->comment_ID]))? $rt_values[$comment->comment_ID]: 0;
    }

    return $comments;
}

function rcl_rating_get_list_votes_content($args){
    global $user_LK;
    
    $args['object_author'] = $user_LK;
    
    $argsCount = array(
        'object_author' => $user_LK
    );
    
    if(isset($args['rating_type'])){
        $argsCount['rating_type'] = $args['rating_type'];
    }
    
    if(isset($args['rating_type__not_in'])){
        $argsCount['rating_type__not_in'] = $args['rating_type__not_in'];
    }
    
    $amount = rcl_count_rating_values($argsCount);
    
    $pagenavi = new Rcl_PageNavi('rcl-rating',$amount,array('in_page'=>50));
    
    $args['number'] = 50;
    $args['offset'] = $pagenavi->offset;
    
    $votes = rcl_get_vote_values($args);
    
    $content = $pagenavi->pagenavi();
    
    $content .= '<div class="rating-list-votes">'.rcl_get_list_votes($args,$votes).'</div>';
    
    $content .= $pagenavi->pagenavi();
    
    return $content;
}

function rcl_rating_class($value){
    
    if($value > 0){
        return "rating-plus";
    }else if($value < 0){
        return "rating-minus";
    }else{
        return "rating-null";
    }
    
}

function rcl_format_value($value = 0){

    $cnt = strlen(round($value));
    
    if($cnt>4){
        
        $th = $cnt-3;
        $value = substr($value, 0, $th).'K';//1452365 - 1452k
        
    }else{
        
        $val = explode('.',$value);
        $fl = (isset($val[1])&&$val[1])? strlen($val[1]): 0;
        $fl = ($fl>2)?2:$fl;
        $value = number_format($value, $fl, ',', ' ');

    }

    return $value;

}

function rcl_format_rating($value){
    return sprintf('<span class="%s">%s</span>',rcl_rating_class($value),rcl_format_value($value));
}

function rcl_rating_block($args){
    
    if(!isset($args['value'])){
        if(!isset($args['ID'])||!isset($args['type'])) return false;
        switch($args['type']){
            case 'user': $value = rcl_get_user_rating($args['ID']); break;
            default: $value = rcl_get_total_rating($args['ID'],$args['type']);
        }
    }else{
        $value = $args['value'];
    }
    
    $value = apply_filters('rcl_rating_block_value', $value, $args);

    $class = (isset($args['type']))? 'rating-type-'.$args['type']: '';

    return sprintf('<span title="%s" class="rating-rcl %s">%s</span>', __('rating','wp-recall'), $class, rcl_format_rating($value));
}

function rcl_get_html_post_rating($object_id, $rating_type, $object_author = false){

    $props = array(
        'object_id' => $object_id,
        'rating_type' => $rating_type
    );
    
    if($object_author)
        $props['object_author'] = $object_author;
    
    $ratingBox = new Rcl_Rating_Box($props);

    $content = $ratingBox->get_box();

    return $content;
}

if(!is_admin()):
    add_filter('the_content', 'rcl_post_content_rating',10);
    add_filter('the_excerpt', 'rcl_post_content_rating',10);
endif;
function rcl_post_content_rating($content){
    global $post;
    if(doing_filter('get_the_excerpt')||(is_front_page()&&is_singular())) return $content;
    $content .= rcl_get_html_post_rating($post->ID,$post->post_type);
    return $content;
}

if(!is_admin()):
    add_filter('comment_text', 'rcl_comment_content_rating',20);
endif;
function rcl_comment_content_rating($content){
    global $comment;
    if(!$comment) return $content;
    $content .= rcl_get_html_post_rating($comment->comment_ID,'comment');
    return $content;
}

function rcl_encode_data_rating($status,$args){
    $args['rating_status'] = $status;
    foreach($args as $k=>$v){
        $str[] = $k.':'.$v;
    }

    return base64_encode(implode(',',$str));
    //return implode(',',$str);
}

function rcl_decode_data_rating($data){
    global $user_ID;

    $data = explode(',',base64_decode($data));
    //$data = explode(',',$data);

    $args = array();

    foreach($data as $v){
        $a = explode(':',$v);
        $args[$a[0]] = $a[1];
    }

    $args['user_id']=$user_ID;

    return $args;
}

function rcl_rating_window_content($string){
    
    $navi = false;
    
    $args = rcl_decode_data_rating($string);

    if($args['rating_status']=='user') 
        $navi = rcl_rating_navi($args);
    
    $args['in_page'] = 100;
    $args['offset'] = 0;
    
    unset($args['user_id']);

    $votes = rcl_get_vote_values($args);
    
    $content = rcl_get_votes_window($args,$votes,$navi);
    
    return $content;
}

add_action('rcl_edit_rating_post','rcl_remove_cashe_rating_post',10);
function rcl_remove_cashe_rating_post($args){
    
    if(rcl_get_option('use_cache')){

        $array = $args;
        
        unset($array['rating_value']);
        unset($array['user_id']);
        
        $statuses = array('view','user');
        
        foreach($statuses as $status){

            $array['rating_status'] = $status;
            if($status == 'user') unset($array['object_id']);
            
            $str = array();
            foreach($array as $k=>$v){
                $str[] = $k.':'.$v;
            }

            $string = base64_encode(implode(',',$str));
            rcl_delete_file_cache($string);
        }
    }
}

rcl_ajax_action('rcl_edit_rating_post', false);
function rcl_edit_rating_post(){
    global $rcl_options,$rcl_rating_types;
    
    rcl_verify_ajax_nonce();

    $args = rcl_decode_data_rating(sanitize_text_field($_POST['rating']));
    
    do_action('rcl_pre_edit_rating_post',$args);

    if($rcl_options['rating_'.$args['rating_status'].'_limit_'.$args['rating_type']]){
        $timelimit = ($rcl_options['rating_'.$args['rating_status'].'_time_'.$args['rating_type']])? $rcl_options['rating_'.$args['rating_status'].'_time_'.$args['rating_type']]: 3600;
        $votes = rcl_count_votes_time($args,$timelimit);
        if($votes>=$rcl_options['rating_'.$args['rating_status'].'_limit_'.$args['rating_type']]){

            wp_send_json(array('error'=>sprintf(__('exceeded the limit of votes for the period - %d seconds','wp-recall'),$timelimit)));

        }
    }
    
    $output_type = rcl_get_option('rating_type_'.$args['rating_type'],0);

    $value = rcl_get_vote_value($args['user_id'],$args['object_id'],$args['rating_type']);

    if($value){
        
        if($value > 0 && $args['rating_status'] == 'plus' || $value < 0 && $args['rating_status'] == 'minus'){
            
            $rating = rcl_delete_rating($args);
            
        }

        if($value > 0 && $args['rating_status'] == 'minus' || $value < 0 && $args['rating_status'] == 'plus'){
            
            rcl_delete_rating($args);

            $type = $args['rating_type'];

            $args['rating_value'] = (isset($rcl_rating_types[$type]['type_point']))? $rcl_rating_types[$type]['type_point']: 1;

            rcl_insert_rating($args);
            
        }

    }else{
        
        $type = $args['rating_type'];
        
        if(!isset($args['rating_value']) && !$args['rating_value'])
            $args['rating_value'] = (isset($rcl_rating_types[$type]['type_point']))? $rcl_rating_types[$type]['type_point']: 1;

        rcl_insert_rating($args);

    }

    wp_cache_delete(json_encode(array('rcl_get_rating_sum',$args['object_id'],$args['rating_type'])));
    wp_cache_delete(json_encode(array('rcl_get_votes_sum',$args['object_id'],$args['rating_type'])));
    
    $total = rcl_get_total_rating($args['object_id'],$args['rating_type']);
    
    if($output_type == 2){ //если звезды
        
        $vote_count = rcl_count_rating_values(array(
            'object_id' => $args['object_id'],
            'object_author' => $args['object_author'],
            'rating_type' => $args['rating_type']
        )); 
        
        $item_count = rcl_get_option('rating_item_amount_'.$args['rating_type'], 1);
        $vote_max = rcl_get_option('rating_point_'.$args['rating_type'], 1);
        
        $average_rating = $vote_count? $total / $vote_count: 0;
        $item_value = $vote_max / $item_count;
        
        $total = $average_rating / $item_value;
        
        $log['replace_box'] = rcl_get_html_post_rating($args['object_id'],$args['rating_type'],$args['object_author']);
        
    }

    do_action('rcl_edit_rating_post',$args);

    $log['result'] = 100;
    $log['object_id'] = $args['object_id'];
    $log['rating_type'] = $args['rating_type'];
    $log['rating'] = rcl_format_rating($total);
    $log['output_type'] = $output_type;

    wp_send_json($log);

}

rcl_ajax_action('rcl_view_rating_votes', true);
function rcl_view_rating_votes(){
    
    rcl_verify_ajax_nonce();

    $string = sanitize_text_field($_POST['rating']);
    
    if(rcl_get_option('use_cache')){
           
        $rcl_cache = new Rcl_Cache();
    
        $file = $rcl_cache->get_file($string);
        
        if($file->need_update){
            
            $content = rcl_rating_window_content($string);
            $content = $rcl_cache->update_cache($content);
            
        }else{

            $content = $rcl_cache->get_cache();

        }
    
    }else{
        
        $content = rcl_rating_window_content($string);
        
    }
    
    if(!$content){
        wp_send_json(array(
            'error' => __('Unable to obtain data','wp-recall')
        ));
    }

    wp_send_json(array(
        'content' => $content
    ));

}