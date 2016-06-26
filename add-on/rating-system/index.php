<?php
require_once 'core.php';
require_once 'addon-options.php';

if (!is_admin()):
    add_action('rcl_enqueue_scripts','rcl_rating_scripts',10);
endif;

function rcl_rating_scripts(){
    rcl_enqueue_style('rcl-rating-system',rcl_addon_url('style.css', __FILE__));
    rcl_enqueue_script( 'rcl-rating-system', rcl_addon_url('js/scripts.js', __FILE__) );
}

if (is_admin()):
    add_action('admin_head','rcl_add_admin_rating_scripts');
endif;

function rcl_add_admin_rating_scripts(){
    wp_enqueue_script( 'jquery' );
    wp_enqueue_script( 'rcl_admin_rating_scripts', plugins_url('js/admin.js', __FILE__) );
}

if(!is_admin()) add_action('init','rcl_register_rating_base_type',30);
if(is_admin()) add_action('admin_init','rcl_register_rating_base_type',30);
function rcl_register_rating_base_type(){
    rcl_register_rating_type(
		array(
		'post_type'=>'post',
		'type_name'=>__('Posts','wp-recall'),
		'style'=>true,
		'data_type'=>true,
		'limit_votes'=>true,
		'icon'=>'fa-thumbs-o-up'
	));
    rcl_register_rating_type(
		array(
		'rating_type'=>'comment',
		'type_name'=>__('Comments','wp-recall'),
		'style'=>true,
		'data_type'=>true,
		'limit_votes'=>true,
		'icon'=>'fa-thumbs-o-up'
	));
}

add_filter('rcl_post_options','rcl_get_post_rating_options',10,2);
function rcl_get_post_rating_options($options,$post){
    $mark_v = get_post_meta($post->ID, 'rayting-none', 1);
    $options .= '<p>'.__('To disable the rating for publication','wp-recall').':
        <label><input type="radio" name="wprecall[rayting-none]" value="" '.checked( $mark_v, '',false ).' />'.__('No','wp-recall').'</label>
        <label><input type="radio" name="wprecall[rayting-none]" value="1" '.checked( $mark_v, '1',false ).' />'.__('Yes','wp-recall').'</label>
    </p>';
    return $options;
}

function rcl_get_rating_admin_column( $columns ){
	return array_merge( $columns,array( 'user_rating_admin' => __('Rating','wp-recall') ));
}
add_filter( 'manage_users_columns', 'rcl_get_rating_admin_column' );

function rcl_get_rating_column_content( $custom_column, $column_name, $user_id ){
	  switch( $column_name ){
		case 'user_rating_admin':
			$custom_column = '<input type="text" class="raytinguser-'.$user_id.'" size="4" value="'.rcl_get_user_rating($user_id).'">
			<input type="button" class="recall-button edit_rayting" id="user-'.$user_id.'" value="'.__('OK','wp-recall').'">';
		break;
	  }
	  return $custom_column;
}
add_filter( 'manage_users_custom_column', 'rcl_get_rating_column_content', 10, 3 );

//if(function_exists('rcl_block')) rcl_block('sidebar','rcl_get_content_rating',array('id'=>'rt-block','order'=>2));
function rcl_get_content_rating($author_lk){
    return rcl_rating_block(array('value'=>rcl_get_user_rating($author_lk)));
}

add_action('init','rcl_add_rating_tab');
function rcl_add_rating_tab(){
    rcl_tab('rating','rcl_rating_tab',__('Rating','wp-recall'),array('ajax-load'=>true,'public'=>1,'cache'=>true,'output'=>'sidebar','class'=>'fa-balance-scale'));
}

if(!is_admin()) add_filter('tab_data_rcl','rcl_add_counter_rating',10);
function rcl_add_counter_rating($tab){
     global $user_LK;
    if($tab['id']!='rating') return $tab;
    $cnt = rcl_rating_block(array('value'=>rcl_get_user_rating($user_LK)));
    $tab['name'] .= ' '.$cnt;
    return $tab;
}

function rcl_rating_tab($author_lk){
    global $rcl_rating_types,$rcl_options;

    foreach($rcl_rating_types as $type=>$val){

	if(!isset($rcl_options['rating_user_'.$type])||!$rcl_options['rating_user_'.$type])continue;

        $args = array(
            'object_author' => $author_lk,
            'rating_type'=>$type
        );
        break;
    }

    $args['rating_status'] = 'user';

    $votes = rcl_get_rating_votes($args,array(0,100));

    $content = rcl_rating_navi($args);

    $content .= '<div class="rating-list-votes">'.rcl_get_list_votes($args,$votes).'</div>';

    return $content;
}

function rcl_rating_class($value){
	if($value>0){
        return "rating-plus";
    }elseif($value<0){
        return "rating-minus";
    }else{
        return "rating-null";
    }
}

function rcl_format_value($value){
	if(!$value) $value = 0;

	$cnt = strlen(round($value));
	if($cnt>4){
		$th = $cnt-3;
		$value = substr($value, 0, $th).'k';//1452365 - 1452k
	}else{
		$val = explode('.',$value);
		$fl = (isset($val[1])&&$val[1])? strlen($val[1]): 0;
		$fl = ($fl>2)?2:$fl;
		$value = number_format($value, $fl, ',', ' ');

	}
	/*if($value>0){
        return "+".$value;
    }elseif($value<0){
        return $value;
    }else{*/
    return $value;
    //}
}

function rcl_format_rating($value){
    return sprintf('<span class="rating-value %s">%s</span>',rcl_rating_class($value),rcl_format_value($value));
}

function rcl_rating_block($args){
    global $wpdb;
    if(!isset($args['value'])){
        if(!isset($args['ID'])||!isset($args['type'])) return false;
        switch($args['type']){
            case 'user': $value = rcl_get_user_rating($args['ID']); break;
            default: $value = rcl_get_total_rating($args['ID'],$args['type']);
        }
    }else{
        $value = $args['value'];
    }

    $class = (isset($args['type']))? 'rating-type-'.$args['type']: '';

    return sprintf('<span title="%s" class="rating-rcl %s">%s</span>', __('rating','wp-recall'), $class, rcl_format_rating($value));
}

function rcl_get_html_post_rating($object_id,$type,$object_author=false){
    global $post,$comment,$rcl_options,$user_ID;

    if(!isset($rcl_options['rating_'.$type])||!$rcl_options['rating_'.$type]) return false;

    if($post&&!$comment){
        $rayting_none = (isset($post->rating_none))? $post->rating_none: get_post_meta($post->ID, 'rayting-none', 1);
        if($rayting_none) return false;
    }

    $block = '';

    if(!$object_author){
        if($type=='comment'){
            $object = ($comment)? $comment: get_comment($object_id);
            $object_author = $object->user_id;
        }else{
            $object = ($post)? $post: get_post($object_id);
            $object_author = $object->post_author;
        }
    }

    $args = array(
        'object_id'=>$object_id,
        'object_author'=>$object_author,
        'rating_type'=>$type,
    );

	$content = '';
	$content = apply_filters('rating_block_content',$content,$args);

    $content = '<div class="'.$type.'-rating-'.$object_id.' post-rating">'.$content.'</div>';

    return $content;
}

add_filter('rating_block_content','rcl_add_rating_block',20,2);
function rcl_add_rating_block($content,$args){
	$content .= rcl_get_rating_block($args);
	return $content;
}

function rcl_get_rating_block($args){
	global $rcl_options,$comment,$post,$user_ID;

	if(is_object($comment)&&$args['rating_type']=='comment'&&$args['object_id']==$comment->comment_ID){
		if($rcl_options['rating_overall_comment']==1)
                    $value = $comment->rating_votes;
		else
                    $value = $comment->rating_total;
	}else{
            if(is_object($post)&&$args['object_id']==$post->ID&&$post->rating_total)
                $value = $post->rating_total;
            else
                $value = rcl_get_total_rating($args['object_id'],$args['rating_type']);
	}

    $block = '<div class="'.$args['rating_type'].'-value rating-value-block '.rcl_rating_class($value).'">'
            . __('Rating','wp-recall').': '.rcl_format_rating($value);

	$access = (isset($rcl_options['rating_results_can']))? $rcl_options['rating_results_can']: false;

	$can = true;

	if($access){
		$user_info = get_userdata($user_ID);
		if ( $user_info->user_level < $access )	$can = false;
	}

    if($value&&$can)$block .= '<a href="#" onclick="rcl_view_list_votes(this);return false;" data-rating="'.rcl_encode_data_rating('view',$args).'" class="view-votes post-votes"><i class="fa fa-question-circle"></i></a>';
    $block .=  '</div>';

    return $block;
}

add_filter('rating_block_content','rcl_add_buttons_rating',10,3);
function rcl_add_buttons_rating($content,$args){
	global $user_ID;
	if(doing_filter('the_excerpt')) return $content;
	if(is_front_page()||!$args['object_author']||$user_ID==$args['object_author']) return $content;
	$content .= rcl_get_buttons_rating($args);
	return $content;
}

function rcl_get_buttons_rating($args){
    global $user_ID,$rating_value,$rcl_options;

    if(!$user_ID) return false;

    $args['user_id'] = $user_ID;

    $rating_value = rcl_get_vote_value($args);

	if($rating_value&&!$rcl_options['rating_delete_voice']) return false;

    $block = '<div class="buttons-rating">';

    if($rating_value) $block .= rcl_get_button_cancel_rating($args);
    else $block .= rcl_get_button_add_rating($args);

    $block .= '</div>';

    return $block;
}

function rcl_get_button_cancel_rating($args){
    return '<a data-rating="'.rcl_encode_data_rating('cancel',$args).'" onclick="rcl_edit_rating(this);return false;" class="rating-cancel edit-rating" href="#">'.__('To remove your vote','wp-recall').'</a>';
}

function rcl_get_button_add_rating($args){
    global $rcl_options;

    if($rcl_options['rating_type_'.$args['rating_type']]==1)
            return '<a href="#" data-rating="'.rcl_encode_data_rating('plus',$args).'" onclick="rcl_edit_rating(this);return false;" class="rating-like edit-rating" title="'.__('I like','wp-recall').'"><i class="fa fa-thumbs-o-up"></i></a>';
    else
        return '<a href="#" data-rating="'.rcl_encode_data_rating('minus',$args).'" onclick="rcl_edit_rating(this);return false;" class="rating-minus edit-rating" title="'.__('minus','wp-recall').'"><i class="fa fa-minus-square-o"></i></a>'
            . '<a href="#" data-rating="'.rcl_encode_data_rating('plus',$args).'" onclick="rcl_edit_rating(this);return false;" class="rating-plus edit-rating" title="'.__('plus','wp-recall').'"><i class="fa fa-plus-square-o"></i></a>';
}

if(!is_admin()):
    add_filter('the_content', 'rcl_post_content_rating',20);
    add_filter('the_excerpt', 'rcl_post_content_rating',20);
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

function rcl_edit_rating_user(){
	global $wpdb,$user_ID;
        
	$user_id = intval($_POST['user']);
	$new_rating = floatval($_POST['rayting']);

	if(isset($new_rating)){

		$rating = rcl_get_user_rating($user_id);

		$val = $new_rating - $rating;

		$args = array(
			'user_id' => $user_ID,
			'object_id' => $user_id,
			'object_author' => $user_id,
			'rating_value' => $val,
			'rating_type' => 'edit-admin'
		);

		rcl_insert_rating($args);

		$log['otvet']=100;

	}else {
		$log['otvet']=1;
	}
	echo json_encode($log);
    exit;
}
if(is_admin()) add_action('wp_ajax_rcl_edit_rating_user', 'rcl_edit_rating_user');


add_action('wp_ajax_rcl_view_rating_votes', 'rcl_view_rating_votes');
add_action('wp_ajax_nopriv_rcl_view_rating_votes', 'rcl_view_rating_votes');
function rcl_view_rating_votes(){
    global $rcl_options;
    
    rcl_verify_ajax_nonce();

    $string = sanitize_text_field($_POST['rating']);
    
    if(isset($rcl_options['use_cache'])&&$rcl_options['use_cache']){
           
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

    $log['result']=100;
    $log['window']=$content;
    echo json_encode($log);
    exit;
}

function rcl_rating_window_content($string){
    $navi = false;
    $args = rcl_decode_data_rating($string);
    if($args['rating_status']=='user') $navi = rcl_rating_navi($args);
    $votes = rcl_get_rating_votes($args,array(0,100));
    $content = rcl_get_votes_window($args,$votes,$navi);
    return $content;
}

add_action('rcl_edit_rating_post','rcl_remove_cashe_rating_post',10);
function rcl_remove_cashe_rating_post($args){
    global $rcl_options;
    if(isset($rcl_options['use_cache'])&&$rcl_options['use_cache']){

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

add_action('wp_ajax_rcl_edit_rating_post', 'rcl_edit_rating_post');
function rcl_edit_rating_post(){
    global $rcl_options,$rcl_rating_types;
    
    rcl_verify_ajax_nonce();

    $args = rcl_decode_data_rating(sanitize_text_field($_POST['rating']));

    if($rcl_options['rating_'.$args['rating_status'].'_limit_'.$args['rating_type']]){
            $timelimit = ($rcl_options['rating_'.$args['rating_status'].'_time_'.$args['rating_type']])? $rcl_options['rating_'.$args['rating_status'].'_time_'.$args['rating_type']]: 3600;
            $votes = rcl_count_votes_time($args,$timelimit);
            if($votes>=$rcl_options['rating_'.$args['rating_status'].'_limit_'.$args['rating_type']]){
                    $log['error'] = sprintf(__('exceeded the limit of votes for the period - %d seconds','wp-recall'),$timelimit);
                    echo json_encode($log);
                    exit;
            }
    }

    $value = rcl_get_vote_value($args);

    if($value){

            if($args['rating_status']=='cancel'){

                $rating = rcl_delete_rating($args);

            }else{
                $log['error'] = __('You can not vote!','wp-recall');
                echo json_encode($log);
                exit;
            }

    }else{

            $args['rating_value'] = rcl_get_rating_value($args['rating_type']);

            $rating = rcl_insert_rating($args);

    }

    wp_cache_delete(json_encode(array('rcl_get_rating_sum',$args['object_id'],$args['rating_type'])));
    wp_cache_delete(json_encode(array('rcl_get_votes_sum',$args['object_id'],$args['rating_type'])));
    
    $total = rcl_get_total_rating($args['object_id'],$args['rating_type']);

    do_action('rcl_edit_rating_post',$args);

    $log['result']=100;
    $log['object_id']=$args['object_id'];
    $log['rating_type']=$args['rating_type'];
    $log['rating']=$total;

    echo json_encode($log);
    exit;
}