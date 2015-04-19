<?php

add_shortcode('userlist','short_user_list_rcl');
function short_user_list_rcl($atts, $content = null){
    global $post,$wpdb,$user_ID;

	extract(shortcode_atts(array(
            'inpage' => 10,        
            'orderby' => 'registered',
            'exclude' => 0,
            'include' => false,
            'order' => 'DESC',
            'type' => 'rows',
            'usergroup' => false,
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
            case 'rayting': $order_by = 'total'; break;
            case 'action': $order_by = 'time_action'; break;
            case 'registered': $order_by = 'user_registered'; break;
            case 'display_name': $order_by = 'display_name'; break;
        }

    if((isset($_GET['search-user'])&&$search=='yes')||$include){
	
        if($_GET['search-user']){
            
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
            $args = array('include'=>$include,'exclude'=>$exclude);
        }
                
        $args['fields'] = 'ID';  
        $allusers = get_users($args);
	$count_user = count($allusers);
        
        $rqst = $UserList->search_request();
        
        $rclnavi = new RCL_navi($inpage,$count_user,'&'.$rqst.'&filter='.$orderby,$page);		
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
            
            if($order_by == 'total') $us_data = $UserList->get_usdata($order_by,$us_data,$us_lst);
            if($order_by == 'time_action')  $us_data = $UserList->get_usdata($order_by,$us_data,$us_lst);

	}
        
    }else{
        
        if($group){

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
                        'compare' => 'LIKE',  
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
            $count_user = $wpdb->get_var("SELECT COUNT(ID) FROM ".$wpdb->prefix ."users WHERE ID NOT IN ($exclude)");
        }

        $rclnavi = new RCL_navi($inpage,$count_user,'&filter='.$orderby,$page);		
        if(!$limit) $limit_us = $rclnavi->limit();
        else $limit_us = $limit;
        
        $UserList->exclude = $exclude;
        $UserList->orderby = $order_by;
        $UserList->order = $order;
        $UserList->limit = $limit_us;
        $UserList->inpage = $inpage;
        
        if($group){
            
            $users = $wpdb->get_results("SELECT user_id FROM ".$wpdb->prefix ."usermeta WHERE meta_key = 'user_group_$group'");
            $us_lst = $UserList->get_users_lst((object)$users,'user_id');
            $group_admin = $wpdb->get_var("SELECT user_id FROM ".$wpdb->prefix ."usermeta WHERE meta_key = 'admin_group_$group'");
            $us_data = $UserList->get_usdata_actions($us_data,$us_lst);
            
        }else{

            

            if($order_by){ 

                if($order_by=='comments_count'){
                    if(!$limit&&!$us_lst){
                        $allusers = $wpdb->get_results("
                            SELECT COUNT(user_id) AS comments_count
                            FROM ".$wpdb->prefix."comments
                            WHERE user_id != '' AND comment_approved = 1 GROUP BY user_id ORDER BY $order_by $order"
                        );

                        $rclnavi->cnt_data = count($allusers);
                        $rclnavi->num_page = ceil($rclnavi->cnt_data/$inpage);
                    }
                }

                if($order_by=='post_count'){
                    if(!$limit&&!$us_lst){
                        $allusers = $wpdb->get_results("
                                SELECT COUNT(post_author) AS post_count
                                FROM ".$wpdb->prefix."posts
                                WHERE post_status = 'publish' GROUP BY post_author ORDER BY $order_by $order"
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
        $users_desc = $wpdb->get_results("SELECT user_id,meta_value FROM ".$wpdb->prefix."usermeta WHERE user_id IN ($us_lst) AND meta_key = 'description'");	
        foreach($users_desc as $us_desc){
            $desc[$us_desc->user_id] = $us_desc->meta_value;
        }
    }

    $display_names = $wpdb->get_results("SELECT ID,display_name FROM ".$wpdb->prefix."users WHERE ID IN ($us_lst)");
    foreach((array)$display_names as $name){
        $names[$name->ID] = $name->display_name;
    }

//Форма поиска
    $userlist = '';
    if($search == 'yes'){
        $searchform = '';
        $userlist .= apply_filters('users_search_form_rcl',$searchform);

        $userlist .='<h3>Всего пользователей: '.$count_user.'</h3>';

        $rqst = $UserList->search_request();
        $perm = get_redirect_url_rcl(get_permalink($post->ID).'?'.$rqst);

        $userlist .= '<p class="alignleft">Фильтровать по: ';
        $userlist .= '<a '.a_active($orderby,'action').' href="'.$perm.'filter=action">Активности</a> ';
        $userlist .= '<a '.a_active($orderby,'rayting').' href="'.$perm.'filter=rayting">Рейтингу</a> ';
        if(!isset($_GET['search-user'])) $userlist .= '<a '.a_active($orderby,'posts').' href="'.$perm.'filter=posts">Публикациям</a> ';
        if(!isset($_GET['search-user'])) $userlist .= '<a '.a_active($orderby,'comments').' href="'.$perm.'filter=comments">Комментариям</a> ';
        if(!isset($_GET['search-user'])) $userlist .= '<a '.a_active($orderby,'registered').' href="'.$perm.'filter=registered">Регистрации</a>';
        $userlist .= '</p>';
    }

    $userlist .='<div class="userlist '.$type.'-list">';	

	$a=0;
	if($us_data){
            foreach((array)$us_data as $id=>$data){
                if(!$us_data[$id]['action'])continue;
                if($onlyaction){
                        if(last_user_action_recall($data['action'])) continue;
                }

                $a++;

                if(function_exists('get_rayting_block_rcl')) {
                    $rtng = (isset($data['rayting']))? $data['rayting']: 0;
                    $rayt_user = get_rayting_block_rcl($rtng);
                }

                $url = get_author_posts_url($id);
               

                $userlist .='<div class="user-single">
                        <div class="thumb-user"><a title="'.$names[$id].'" href="'.$url.'">'.get_avatar($id,70).'</a>';
                
                if($type=='avatars'){
                    $last_action = last_user_action_recall($data['action']);
                    if(!$last_action) $userlist .= '<span class="status_user online"><i class="fa fa-circle"></i></span>';
                    else $userlist .= '<span class="status_user offline" title="не в сети '.$last_action.'"><i class="fa fa-circle"></i></span>';
                }

                if($type!='mini'){
                    
                    $userlist .= $rayt_user;
                }

                $userlist .= '</div>';

                if($type=='rows'){
                    $action = get_miniaction_user_rcl($data['action']);
                    $userlist .='<div class="user-content-rcl">'.$action.'<a href="'.$url.'"><h3 class="user-name">'.$names[$id];
                    if($order_by == 'comments_count') $userlist .='<br><span class="filter-data">Комментариев: '.$data['comments'].'</span>';
                    if($order_by == 'post_count') $userlist .='<br><span class="filter-data">Публикаций: '.$data['posts'].'</span>';
                    if($order_by == 'user_registered') $userlist .='<br><span class="filter-data">Регистрация: '.mysql2date('d-m-Y', $data['register']).'</span>';
                    $userlist .='</h3></a>';

                    if($desc[$id])$userlist .='<div class="ballun-status"><span class="ballun"></span><p class="status-user-rcl">'.nl2br(esc_textarea($desc[$id])).'</p></div>';
                    $cont = '';
                    $cont = apply_filters('rcl_description_user',$cont,$id);		
                    $userlist .= $cont;
                    if($user_ID&&$group&&$group_admin==$user_ID&&$id!=$user_ID) $userlist .='<p class="alignright"><a href="#" id="usergroup-'.$id.'" user-data="'.$id.'" group-data="'.$group.'" class="ban-group recall-button">Удалить из группы</a></p>';
                    $userlist .='</div>';
                    $cont = '';
                }

                $userlist .='</div>';
                if($a==$inpage) break;
            }
	}
	if($a==0){
		if(isset($_GET['search-user'])) $userlist .= '<h4 align="center">'.__('Пользователи не найдены','rcl').'</h4>';
		else $userlist .= '<p align="center">'.__('Никого нет','rcl').'</p>';
    }                      
    $userlist .='</div>';
         
    //вывод постраничной навигации       
    if(!$limit) $userlist .= $rclnavi->navi();
           
    return $userlist;
}

add_filter('users_search_form_rcl','default_search_form_rcl');
function default_search_form_rcl($form){
        $name = '';
        $orderuser = '';
        if(isset($_GET['name-user'])) $name = $_GET['name-user'];
        if(isset($_GET['orderuser'])) $orderuser = $_GET['orderuser'];
	$form .='
        <form method="get" action="">
        <p class="alignright">Поиск пользователей: <input type="text" name="name-user" value="'.$name.'">
        <select name="orderuser">
            <option '.selected($orderuser,1,false).' value="1">по имени</option>
            <option '.selected($orderuser,2,false).' value="2">по логину</option>
        </select>
        <input type="submit" class="recall-button" name="search-user" value="Найти"><br>
        </p>
		<input type="hidden" name="default-search" value="1">
        </form>';
	return $form;
} 

add_shortcode('wp-recall','get_wp_recall_shortcode');
function get_wp_recall_shortcode(){
	global $user_LK;	
	
	if(!$user_LK){		
		return '<h4>Чтобы начать пользоваться возможностями своего личного кабинета, авторизуйтесь или зарегистрируйтесь на этом сайте</h4>
		<div class="authorize-form-rcl">'.get_authorize_form_rcl().'</div>';
	}
        
	ob_start();
        
	wp_recall();
        
	$content = ob_get_contents();
	ob_end_clean();
        
	return $content;
}

add_shortcode('slider-rcl','slider_rcl');
function slider_rcl($atts, $content = null){
    add_bxslider_scripts();
    
    extract(shortcode_atts(array(
	'num' => 5,
	'term' => '',
        'type' => 'post',
        'tax' => 'category',
	'exclude' => false,
        'include' => false,
	'orderby'=> 'post_date',
	'title'=> true,
	'desc'=> 280,
        'order'=> 'DESC',
        'size'=> array(9999,300)
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
                'terms'=> array($term)
            )
	);	

	$posts = get_posts($args);
	
	$plslider = '<ul class="slider-rcl">';
	foreach($posts as $post){ 	
            //if( !has_post_thumbnail($post->ID)) continue;
            
            $thumb_id = get_post_thumbnail_id($post->ID);
            $large_url = wp_get_attachment_image_src( $thumb_id, 'full');
            $thumb_url = wp_get_attachment_image_src( $thumb_id, $size);
            $plslider .= '<li><a href="'.get_permalink($post->ID).'">';
            if($type='products'){
                $price = get_post_meta($post->ID,'price-products',1);
                $class = 'price-prod';
                if(!$price){
                    $price = 'Бесплатно';
                    $class .= ' no-price';
                }else{
                    $price .= ' руб';
                }
                $plslider .= '<span class="'.$class.'">'.$price.'</span>';
            }
            $plslider .= '<img src='.$thumb_url[0].'>';
            $post_content = $post->post_content;
            if($post->post_excerpt) $post_content = $post->post_excerpt;
            if($desc > 0 && strlen($post_content) > $desc){
                    $post_content = strip_tags(substr($post_content, 0, $desc));
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