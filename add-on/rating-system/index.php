<?php
require_once 'core.php';
require_once 'addon-options.php';
require_once 'class_rayting.php';

if(function_exists('rcl_enqueue_style')) rcl_enqueue_style('rating-system',__FILE__);

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
		'type_name'=>__('Posts','rcl'),
		'style'=>true,
		'data_type'=>true,
		'limit_votes'=>true,
		'icon'=>'fa-thumbs-o-up'
	));
    rcl_register_rating_type(
		array(
		'rating_type'=>'comment',
		'type_name'=>__('Comments','rcl'),
		'style'=>true,
		'data_type'=>true,
		'limit_votes'=>true,
		'icon'=>'fa-thumbs-o-up'
	));
}

add_filter('rcl_post_options','rcl_get_post_rating_options',10,2);
function rcl_get_post_rating_options($options,$post){
    $mark_v = get_post_meta($post->ID, 'rayting-none', 1);
    $options .= '<p>'.__('To disable the rating for publication','rcl').':
        <label><input type="radio" name="wprecall[rayting-none]" value="" '.checked( $mark_v, '',false ).' />'.__('No','rcl').'</label>
        <label><input type="radio" name="wprecall[rayting-none]" value="1" '.checked( $mark_v, '1',false ).' />'.__('Yes','rcl').'</label>
    </p>';
    return $options;
}

function rcl_get_rating_admin_column( $columns ){
	return array_merge( $columns,array( 'user_rating_admin' => __('Rating','rcl') ));
}
add_filter( 'manage_users_columns', 'rcl_get_rating_admin_column' );

function rcl_get_rating_column_content( $custom_column, $column_name, $user_id ){
	  switch( $column_name ){
		case 'user_rating_admin':
			$custom_column = '<input type="text" class="raytinguser-'.$user_id.'" size="4" value="'.rcl_get_user_rating($user_id).'">
			<input type="button" class="recall-button edit_rayting" id="user-'.$user_id.'" value="'.__('OK','rcl').'">';
		break;
	  }
	  return $custom_column;
}
add_filter( 'manage_users_custom_column', 'rcl_get_rating_column_content', 10, 3 );

//if(function_exists('rcl_block')) rcl_block('sidebar','rcl_get_content_rating',array('id'=>'rt-block','order'=>2));
function rcl_get_content_rating($author_lk){
    global $user_ID,$rcl_rating_types,$rcl_options;

    $karma = rcl_get_user_rating($author_lk);

    /*foreach($rcl_rating_types as $type=>$val){

	if(!isset($rcl_options['rating_user_'.$type])||!$rcl_options['rating_user_'.$type])continue;

        $args = array(
            'object_author' => $author_lk,
            'rating_type'=>$type
        );
        break;
    }

    if($karma){
        if($user_ID) return '<a href="#" data-rating="'.rcl_encode_data_rating('user',$args).'" id="rating-user-'.$author_lk.'" class="rating-rcl view-votes">'.rcl_format_rating($karma).'</a>';
        else return rcl_rating_block(array('value'=>$karma));
    } else {
        return rcl_rating_block(array('value'=>$karma));
    }*/

    return rcl_rating_block(array('value'=>$karma));
}

add_filter('ajax_tabs_rcl','rcl_ajax_rating_tab');
function rcl_ajax_rating_tab($array_tabs){
    return array_merge( $array_tabs,array( 'rating' => 'rcl_rating_tab' ));
}

add_action('init','rcl_add_rating_tab');
function rcl_add_rating_tab(){
    rcl_tab('rating','rcl_rating_tab',__('Rating','rcl'),array('public'=>1,'output'=>'sidebar','class'=>'fa-balance-scale'));
}

add_filter('tab_data_rcl','rcl_add_counter_rating',10);
function rcl_add_counter_rating($tab){
     global $user_LK;
    if($tab['id']!='rating') return $tab;
    $cnt = rcl_rating_block(array('value'=>rcl_get_user_rating($user_LK)));
    $tab['name'] .= ': '.$cnt;
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

    $content .= rcl_get_list_votes($args,$votes);

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

    return sprintf('<span title="%s" class="rating-rcl %s">%s</span>', __('rating','rcl'), $class, rcl_format_rating($value));
}

function rcl_get_html_post_rating($object_id,$type,$object_author=false){
    global $post,$comment,$rcl_options,$user_ID;

    if(!isset($rcl_options['rating_'.$type])||!$rcl_options['rating_'.$type]) return false;

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
	global $rcl_options,$comment,$user_ID;

	if(is_object($comment)&&$args['rating_type']=='comment'&&$args['object_id']==$comment->comment_ID){
		if($rcl_options['rating_overall_comment']==1) $value = $comment->rating_votes;
		else $value = $comment->rating_total;
	}else{
		$value = rcl_get_total_rating($args['object_id'],$args['rating_type']);
	}

    $block = '<div class="'.$args['rating_type'].'-value rating-value-block '.rcl_rating_class($value).'">'
            . __('Rating','rcl').': '.rcl_format_rating($value);

	$access = $rcl_options['rating_results_can'];

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
	if(is_front_page()||$user_ID==$args['object_author']) return $content;
	$content .= rcl_get_buttons_rating($args);
	return $content;
}

function rcl_get_buttons_rating($args){
    global $user_ID,$rating_value,$rcl_options;

    if(!$user_ID) return $block;

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
    return '<a data-rating="'.rcl_encode_data_rating('cancel',$args).'" onclick="rcl_edit_rating(this);return false;" class="rating-cancel edit-rating" href="#">'.__('To remove your vote','rcl').'</a>';
}

function rcl_get_button_add_rating($args){
    global $rcl_options;

    if($rcl_options['rating_type_'.$args['rating_type']]==1)
            return '<a href="#" data-rating="'.rcl_encode_data_rating('plus',$args).'" onclick="rcl_edit_rating(this);return false;" class="rating-like edit-rating" title="'.__('I like','rcl').'"><i class="fa fa-thumbs-o-up"></i></a>';
    else
        return '<a href="#" data-rating="'.rcl_encode_data_rating('minus',$args).'" onclick="rcl_edit_rating(this);return false;" class="rating-minus edit-rating" title="'.__('minus','rcl').'"><i class="fa fa-minus-square-o"></i></a>'
            . '<a href="#" data-rating="'.rcl_encode_data_rating('plus',$args).'" onclick="rcl_edit_rating(this);return false;" class="rating-plus edit-rating" title="'.__('plus','rcl').'"><i class="fa fa-plus-square-o"></i></a>';
}

if(!is_admin()):
    add_filter('the_content', 'rcl_post_content_rating',20);
    add_filter('the_excerpt', 'rcl_post_content_rating',20);
endif;
function rcl_post_content_rating($content){
    global $post;
    if(doing_filter('get_the_excerpt')) return $content;
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

	if($new_rating){

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

		$args = rcl_decode_data_rating(sanitize_text_field($_POST['rating']));

		$navi = false;

		if($args['rating_status']=='user') $navi = rcl_rating_navi($args);

		$votes = rcl_get_rating_votes($args,array(0,100));

		$window = rcl_get_votes_window($args,$votes,$navi);

		$log['result']=100;
		$log['window']=$window;
		echo json_encode($log);
		exit;
}

add_action('wp_ajax_rcl_edit_rating_post', 'rcl_edit_rating_post');
function rcl_edit_rating_post(){
		global $rcl_options,$rcl_rating_types;

		$args = rcl_decode_data_rating(sanitize_text_field($_POST['rating']));

		if($rcl_options['rating_'.$args['rating_status'].'_limit_'.$args['rating_type']]){
			$timelimit = ($rcl_options['rating_'.$args['rating_status'].'_time_'.$args['rating_type']])? $rcl_options['rating_'.$args['rating_status'].'_time_'.$args['rating_type']]: 3600;
			$votes = rcl_count_votes_time($args,$timelimit);
			if($votes>=$rcl_options['rating_'.$args['rating_status'].'_limit_'.$args['rating_type']]){
				$log['error']=sprintf(__('exceeded the limit of votes for the period - %d seconds','rcl'),$timelimit);
				echo json_encode($log);
				exit;
			}
		}

		$value = rcl_get_vote_value($args);

		if($value){

			if($args['rating_status']=='cancel'){

				$rating = rcl_delete_rating($args);

			}else{
				$log['result']=110;
				echo json_encode($log);
				exit;
			}

		}else{

			$args['rating_value'] = rcl_get_rating_value($args['rating_type']);

			$rating = rcl_insert_rating($args);

		}

		$total = rcl_get_total_rating($args['object_id'],$args['rating_type']);

		$log['result']=100;
		$log['object_id']=$args['object_id'];
		$log['rating_type']=$args['rating_type'];
		$log['rating']=$total;

		echo json_encode($log);
		exit;
}

add_filter('rcl_functions_js','rcl_rating_functions_js');
function rcl_rating_functions_js($string){

    $ajaxdata = "type: 'POST', data: dataString, dataType: 'json', url: wpurl+'wp-admin/admin-ajax.php',";

    $string .= "
        function rcl_close_votes_window(e){
            jQuery(e).parent().remove();
            return false;
        }
        function rcl_edit_rating(e){
            var block = jQuery(e);
            var rating = block.data('rating');

            var dataString = 'action=rcl_edit_rating_post&rating='+rating;

            jQuery.ajax({
                ".$ajaxdata."
                success: function(data){
                    if(data['error']){
                        rcl_notice(data['error'],'error');
                        return false;
                    }
                    if(data['result']==100){
                        var val = jQuery('.'+data['rating_type']+'-rating-'+data['object_id']+' .rating-value');
                        val.empty().text(data['rating']);
                        if(data['rating']<0){
                                val.parent().css('color','#FF0000');
                        }else{
                                val.parent().css('color','#008000');
                        }
                        block.parent().remove();
                    }else{
                        rcl_notice('".__('You can not vote','rcl')."!','error');
                    }
                }
            });
            return false;
        }
        function rcl_get_list_votes(e){
            if(jQuery(this).hasClass('active')) return false;
            rcl_preloader_show('#tab-rating .votes-list');
            jQuery('#tab-rating a.get-list-votes').removeClass('active');
            jQuery(e).addClass('active');
            var rating = jQuery(e).data('rating');

            var dataString = 'action=rcl_view_rating_votes&rating='+rating+'&content=list-votes';

            jQuery.ajax({
                ".$ajaxdata."
                success: function(data){
                    if(data['result']==100){
                        jQuery('#tab-rating .votes-list').replaceWith(data['window']);
                    }else{
                        rcl_notice('".__('Error','rcl')."!','error');
                    }
                    rcl_preloader_hide();
                }
            });
            return false;
        }
        function rcl_view_list_votes(e){
            jQuery('.rating-value-block .votes-window').remove();
            var block = jQuery(e);
            var rating = block.data('rating');

            var dataString = 'action=rcl_view_rating_votes&rating='+rating;

            jQuery.ajax({
                ".$ajaxdata."
                success: function(data){
                    if(data['result']==100){
                        block.after(data['window']);
                        block.next().slideDown();
                    }else{
                        rcl_notice('".__('Error','rcl')."!','error');
                    }
                }
            });
            return false;
        }
        ";
    return $string;
}