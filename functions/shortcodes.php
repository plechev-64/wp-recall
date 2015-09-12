<?php

add_shortcode('userlist','rcl_get_userlist');
function rcl_get_userlist($atts, $content = null){
    global $post,$wpdb,$user_ID,$user,$group_id,$group_admin,$active_addons;

	extract(shortcode_atts(array(
            'inpage' => 10,
            'orderby' => 'registered',
            'exclude' => 0,
            'include' => false,
            'order' => 'DESC',
            'type' => 'rows',
            'usergroup' => false,
            'usergroup_compare'=>'=',
            'limit' => 0,
            'onlyaction' => false,
            'group' => 0,
            'search' => 'yes',
            'widget' => false,
            'page' => ''
	),
	$atts));

        $us_data = '';
        $flt = '';
        $us_lst = false;

        if(isset($_GET['usergroup'])){
            $usergroup = $_GET['usergroup'];
        }
        if(isset($_GET['order'])){
            $order = $_GET['order'];
        }
        if(isset($_GET['type'])){
            $type = $_GET['type'];
        }
        if(isset($_GET['inpage'])){
            $inpage = $_GET['inpage'];
        }

        if (!class_exists('Rcl_Userlist')) include_once plugin_dir_path( __FILE__ ).'rcl_userlist.php';
	$UserList = new Rcl_Userlist();

        if($page) $navi = $page;

        if(isset($_GET['filter'])&&!$widget) $orderby = $_GET['filter'];

        switch($orderby){
            case 'posts': $order_by = 'post_count'; break;
            case 'feeds': $order_by = 'feeds'; break;
            case 'comments': $order_by = 'comments_count'; break;
            case 'rayting': $order_by = 'rating_total'; break;
            case 'action': $order_by = 'time_action'; break;
            case 'registered': $order_by = 'user_registered'; break;
            case 'display_name': $order_by = 'display_name'; break;
        }

        $atts['add_uri']['filter']=$orderby;

    if((isset($_GET['search-user'])&&$search=='yes')||$include){

        if(isset($_GET['search-user'])){

            if($orderby!='action'&&$orderby!='rayting'){
                $orderby = 'action';
                $order_by = 'time_action';
            }

            $args = apply_filters('search_filter_rcl',$args);

            $exclude= 0;
            $type='rows';
            $search='yes';

            if(isset($_GET['default-search'])) $args = $UserList->get_args();

        }else if($include){
            $args = array('include'=>explode(',',$include),'exclude'=>explode(',',$exclude));
        }

        $args['fields'] = 'ID';
        $allusers = get_users($args);
	$count_user = count($allusers);

        $rqst = $UserList->search_request();

        if(isset($atts['add_uri'])){
            foreach($atts['add_uri'] as $k=>$v){
                $rqst[] = $k.'='.$v;
            }
        }

        $rclnavi = new RCL_navi($inpage,$count_user,'&'.implode('&',$rqst),$page);
        if(!$limit) $limit_us = $rclnavi->limit();
        else $limit_us = $limit;

        /*unset($args['fields']);
        $args['number'] = $inpage;
        $args['offset'] = $rclnavi->offset;
        $users = get_users($args);*/

        $us_lst = $UserList->get_users_lst($allusers,'data');

        if($us_lst){

            $UserList->exclude = $exclude;
            $UserList->orderby = $order_by;
            $UserList->order = $order;
            $UserList->limit = $limit_us;
            $UserList->inpage = $inpage;

            $flt_sql = "IN ($us_lst)";

            if($order_by == 'rating_total') $us_data = $UserList->get_usdata($order_by,$us_data,$us_lst);
            if($order_by == 'time_action')  $us_data = $UserList->get_usdata($order_by,$us_data,$us_lst);

	}

    }else{

        if($group){
            $group_id = $group;
            $gr = new Rcl_Group($group);
            $count_user = $gr->users_count;

        }else if($usergroup){
            if($limit) $inpage = $limit;

            $usergroup = explode('|',$usergroup);
            foreach($usergroup as $k=>$filt){
                    $f = explode(':',$filt);
                    $args['meta_query'][] = array(
                        'key' => $f[0],
                        'value' => $f[1],
                        'compare' => $usergroup_compare,
                    );
            }
            $args['meta_query']['relation'] = 'AND';
            $args['fields'] = 'ID';
            $allusers = get_users($args);

            //unset($args['fields']);
            //$args['number'] = $inpage;
            //$args['offset'] = $rclnavi->offset;
            //$users = get_users($args);

            $us_lst = $UserList->get_users_lst($allusers,'data');
            $count_user = count($allusers);

        }else{

            $count_user = $wpdb->get_var($wpdb->prepare("SELECT COUNT(ID) FROM $wpdb->users WHERE ID NOT IN (".rcl_format_in(explode(',',$exclude)).")",explode(',',$exclude)));

        }

        if(isset($atts['add_uri'])){
            foreach($atts['add_uri'] as $k=>$v){
                $rqst[] = $k.'='.$v;
            }
        }

        $rclnavi = new RCL_navi($inpage,$count_user,'&'.implode('&',$rqst),$page);
        if(!$limit) $limit_us = $rclnavi->limit();
        else $limit_us = $limit;

        $UserList->exclude = $exclude;
        $UserList->orderby = $order_by;
        $UserList->order = $order;
        $UserList->limit = $limit_us;
        $UserList->inpage = $inpage;

        if($group){
            $users = $wpdb->get_results($wpdb->prepare("SELECT user_id FROM $wpdb->usermeta WHERE meta_key = '%s'",'user_group_'.$group));
            $us_lst = $UserList->get_users_lst((object)$users,'user_id');
            $group_admin = $wpdb->get_var($wpdb->prepare("SELECT user_id FROM $wpdb->usermeta WHERE meta_key = '%s'",'admin_group_'.$group));
            $us_data = $UserList->get_usdata_actions($us_data,$us_lst);
        }else{

            if($order_by){

                if($order_by=='comments_count'){
                    if(!$limit&&!$us_lst){
                        $allusers = $wpdb->get_results($wpdb->prepare("
                            SELECT COUNT(user_id) AS comments_count
                            FROM ".$wpdb->prefix."comments
                            WHERE user_id != '' AND comment_approved = 1 GROUP BY user_id ORDER BY %s %s",$order_by,$order)
                        );

                        $rclnavi->cnt_data = count($allusers);
                        $rclnavi->num_page = ceil($rclnavi->cnt_data/$inpage);
                    }
                }

                if($order_by=='post_count'){
                    if(!$limit&&!$us_lst){
                        $allusers = $wpdb->get_results($wpdb->prepare("
                                SELECT COUNT(post_author) AS post_count
                                FROM ".$wpdb->prefix."posts
                                WHERE post_status = 'publish' GROUP BY post_author ORDER BY %s %s",$order_by,$order)
                        );

                        $rclnavi->cnt_data = count($allusers);
                        $rclnavi->num_page = ceil($rclnavi->cnt_data/$inpage);
                    }
                }

                $us_data = $UserList->get_usdata($order_by,$us_data,$us_lst);
            }

            if($us_data){
                $us_lst = $UserList->get_users_lst($us_data);
                $UserList->orderby = false;
                $UserList->order = false;
                $UserList->limit = false;
                $us_data = $UserList->get_usdata_actions($us_data,$us_lst);
                $us_data = $UserList->get_usdata_rayts($us_data,$us_lst);
            }
       }
    }

    if($type=='rows'){
            if($order_by!='post_count') $us_data = $UserList->add_post_count_data($us_data,$us_lst);
            if($order_by!='comments_count') $us_data = $UserList->add_comments_count_data($us_data,$us_lst);
            if($order_by!='user_registered') $us_data = $UserList->add_user_registered_data($us_data,$us_lst);
    }

    $uslst_array = explode(',',$us_lst);

    $users_desc = $wpdb->get_results($wpdb->prepare("SELECT user_id,meta_value FROM $wpdb->usermeta WHERE user_id IN (".rcl_format_in($uslst_array).") AND meta_key = 'description'",$uslst_array));
    foreach($users_desc as $us_desc){
        $us_data[$us_desc->user_id]['description'] = $us_desc->meta_value;
    }

    $display_names = $wpdb->get_results($wpdb->prepare("SELECT ID,display_name FROM $wpdb->users WHERE ID IN (".rcl_format_in($uslst_array).")",$uslst_array));
    foreach((array)$display_names as $name){
        $us_data[$name->ID]['display_name'] = $name->display_name;
        $us_data[$name->ID]['user_id'] = $name->ID;
    }

//Форма поиска
    $userlist = '';
    if($search == 'yes'){
        $searchform = '';
        $userlist .= apply_filters('users_search_form_rcl',$searchform);

        $userlist .='<h3>'.__('Total users','rcl').': '.$count_user.'</h3>';

        $rqst = $UserList->search_request();
        $perm = rcl_format_url(get_permalink($post->ID).'?'.$rqst);

        $userlist .= '<div class="rcl-user-filters">'.__('Filter by','rcl').': ';
        $userlist .= '<a class="user-filter '.rcl_a_active($orderby,'action').'" href="'.$perm.'filter=action">'.__('Activity','rcl').'</a> ';
        if(isset($active_addons['rating-system'])) $userlist .= '<a class="user-filter '.rcl_a_active($orderby,'rayting').'" href="'.$perm.'filter=rayting">'.__('Rated','rcl').'</a> ';
        if(!isset($_GET['search-user'])&&isset($active_addons['publicpost'])) $userlist .= '<a class="user-filter '.rcl_a_active($orderby,'posts').'" href="'.$perm.'filter=posts">'.__('Publications','rcl').'</a> ';
        if(!isset($_GET['search-user'])) $userlist .= '<a class="user-filter '.rcl_a_active($orderby,'comments').'" href="'.$perm.'filter=comments">'.__('Comments','rcl').'</a> ';
        if(!isset($_GET['search-user'])) $userlist .= '<a class="user-filter '.rcl_a_active($orderby,'registered').'" href="'.$perm.'filter=registered">'.__('Registration','rcl').'</a>';
        $userlist .= '</p>';
    }

    $userlist .='<div class="userlist '.$type.'-list">';

    $us_data = apply_filters('data_userslist',$us_data);

    $a=0;
    if($us_data){

        foreach((array)$us_data as $id=>$user){ rcl_setup_datauser($user);
            $a++;
            if(!$user->user_action)continue;
            if($onlyaction){
                    if(rcl_get_useraction($user->user_action)) continue;
            }

            $userlist .= rcl_get_include_template('user-'.$type.'.php');
            if($a==$inpage) break;
        }
    }

    if($a==0){
        if(isset($_GET['search-user'])) $userlist .= '<h4 align="center">'.__('Users not found','rcl').'</h4>';
        else $userlist .= '<p align="center">'.__('No one','rcl').'</p>';
    }
    $userlist .='</div>';

    //вывод постраничной навигации
    if(!$limit) $userlist .= $rclnavi->navi();

    return $userlist;
}

add_filter('users_search_form_rcl','rcl_default_search_form');
function rcl_default_search_form($form){
        $name = '';
        $orderuser = '';
        if(isset($_GET['name-user'])) $name = $_GET['name-user'];
        if(isset($_GET['orderuser'])) $orderuser = $_GET['orderuser'];
	$form .='
		<div class="rcl-search-users">
			<form method="get" action="">
			<p>'.__('Search users','rcl').'</p>
			<input type="text" name="name-user" value="'.$name.'">
			<select name="orderuser">
				<option '.selected($orderuser,1,false).' value="1">'.__('by name','rcl').'</option>
				<option '.selected($orderuser,2,false).' value="2">'.__('by login','rcl').'</option>
			</select>
			<input type="submit" class="recall-button" name="search-user" value="'.__('Search','rcl').'">
			<input type="hidden" name="default-search" value="1">
			</form>
		</div>';
	return $form;
}

add_shortcode('wp-recall','rcl_get_shortcode_wp_recall');
function rcl_get_shortcode_wp_recall(){
	global $user_LK;

	if(!$user_LK){
		return '<h4>'.__('To begin to use the capabilities of your personal account, please log in or register on this site','rcl').'</h4>
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