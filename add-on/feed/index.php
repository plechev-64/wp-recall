<?php
if(function_exists('rcl_enqueue_style')) rcl_enqueue_style('feed',__FILE__);

function rcl_get_user_feed($user_id, $feed_id){
	global $wpdb;
	return $wpdb->get_var("SELECT meta_value FROM $wpdb->usermeta WHERE user_id='$user_id' AND meta_key='rcl_feed' AND meta_value='$feed_id'");
}

//считаем сколько подписано на пользователя
function rcl_get_count_feed($user_id){
	global $wpdb;
	return $wpdb->get_var("SELECT COUNT(umeta_id) FROM $wpdb->usermeta WHERE meta_value='$user_id' AND meta_key='rcl_feed'");
}

//считаем на сколько подписан пользователь
function rcl_count_user_feed($user_id){
	global $wpdb;
	return $wpdb->get_var("SELECT COUNT(umeta_id) FROM $wpdb->usermeta WHERE user_id='$user_id' AND meta_key='rcl_feed'");
}

//получаем всех пользователей на которых подписан указанный юзер
function get_user_feeds($user_id){
	global $wpdb;
	return $wpdb->get_results($wpdb->prepare("SELECT * FROM $wpdb->usermeta WHERE meta_key = 'rcl_feed' AND user_id='%d' ORDER BY umeta_id DESC",$user_id));
}

function rcl_get_feed_button($userid){
	global $user_ID;

	if(!$user_ID||$user_ID==$userid) return false;
	
	$feed = rcl_get_user_feed($user_ID, $userid);
	
	$feed_status = (!$feed)? __('Subscribe','rcl'): __('Unsubscribe','rcl');
	$icon = (!$feed)? 'fa-bell': 'fa-bell-slash';

	$button = '<span class="feed-control feed-control-'.$userid.'"';
	if(!is_single()) $button .= ' class="alignright"';
	$button .= '>'
			.rcl_get_button($feed_status,'#',array('icon'=>$icon,'class'=>'feed-user','attr'=>'data-feed='.$userid.' title='.$feed_status));
	$button .= '</span>';

	return $button;
}

class Rcl_Feed{

    public function __construct() {
        global $user_ID;

        add_action('wp_ajax_get_posts_feed_recall', array(&$this, 'get_posts_feed_recall'));
		add_action('wp_ajax_get_all_users_feed_recall', array(&$this, 'get_all_users_feed_recall'));
		add_action('wp_ajax_get_all_your_feed_users', array(&$this, 'get_all_your_feed_users'));
		add_action('wp_ajax_get_comments_feed_recall', array(&$this, 'get_comments_feed_recall'));
        if($user_ID) add_action('wp_ajax_add_feed_user_recall', array(&$this, 'add_feed_user_recall'));

        add_filter('file_scripts_rcl',array(&$this, 'get_scripts_feed_rcl'));
        if(function_exists('rcl_comment_rating'))
            add_filter('feed_comment_text_rcl','rcl_comment_rating',10,2);

        if (!is_admin()):
                if(function_exists('add_shortcode')) add_shortcode('feed',array(&$this, 'last_post_and_comments_feed'));
                if(function_exists('rcl_block')){
                    rcl_block('sidebar',array(&$this, 'add_feed_button_user_lk'),array('id'=>'fd-block','order'=>5));
                    rcl_block('footer','rcl_get_feed_button',array('id'=>'fd-footer','order'=>5,'public'=>-1));
                }
        endif;

    }

	//получаем пользователей которые подписаны на указанного юзера
	function get_users_feed($user_id=false){
		global $wpdb,$user_ID;
		if(!$user_id) $user_id = $user_ID;
		return $wpdb->get_results($wpdb->prepare("SELECT * FROM $wpdb->usermeta WHERE meta_key = 'rcl_feed' AND meta_value='%d' ORDER BY umeta_id DESC",$user_id));
	}

	function get_feedout_button($user_id){
		return '<div class="alignright feed-control feed-control-'.$userid.'">'.rcl_get_button(__('Unsubscribe','rcl'),'#',array('icon'=>'fa-bell-slash','class'=>'feed-user','attr'=>'data-feed='.$userid.' title='.__('Unsubscribe','rcl'))).'</div>';
	}


	function add_feed_button_user_lk($author_lk){
		global $wpdb,$user_ID;

		$feed_count = rcl_get_count_feed($author_lk);

		if(!$feed_count) $feed_info = __('Subscribers','rcl').': '.$feed_count;
		else $feed_info = '<a class="count_users_feed" id="user-feed-'.$author_lk.'">'.__('Subscribers','rcl').': '.$feed_count.'</a>';

		$feed_info = '<div class="feed-counter">'.$feed_info.'</div>';

		if($user_ID==$author_lk){
			$cnt = rcl_count_user_feed($author_lk);
			if($cnt){
				$feed_info .= '<div class="feed-current"><a class="all-users-feed">'.__('My subscriptions','rcl').': <span id="feed-count">'.$cnt.'</span></a></div>';
			}
		}

		return $feed_info;
	}


	function last_post_and_comments_feed(){

		global $user_ID;

		if(!$user_ID){
			$feedlist = '<p class="aligncenter">'.__('Login or register to view the latest publications and comments from users on which you will you subscribed.','rcl').'</p>';
			return $feedlist;
		}


		$feedlist = '<p class="alignright" id="feed-button">
		'.rcl_get_button(__('Comments','rcl'),'#',array('icon'=>false,'class'=>'get-feed ','id'=>'commentfeed')).'
		'.rcl_get_button(__('Publication','rcl'),'#',array('icon'=>false,'class'=>'get-feed active','id'=>'postfeed')).'
		</p>';

		$feedlist .= '<div id="feedlist">';
		$feedlist .= rcl_get_public_feed($user_ID);
		$feedlist .= '</div>';

		return $feedlist;


	}

	function feed_comment_loop($comments_feed){

		global $user_ID,$wpdb,$comment;

		$postsids = array();

		$comments_children=$wpdb->get_results(
			$wpdb->prepare("SELECT      com2.comment_ID,com2.comment_parent,com2.user_id,com2.comment_post_ID,com2.comment_content,com2.comment_date
                        FROM        $wpdb->comments com1
                        INNER JOIN  $wpdb->comments com2
			on com2.comment_parent = com1.comment_ID
                        where com1.user_id = '%d'
			ORDER BY com2.comment_date DESC limit %d",$user_ID,40));

		if($comments_feed)
			foreach($comments_feed as $c){
				$postsids[] = $c->comment_post_ID;
			}

		if($postsids)
			$posts_title = $wpdb->get_results($wpdb->prepare("SELECT ID,post_title FROM $wpdb->posts WHERE ID IN (".rcl_format_in($postsids).")",$postsids));

		$titles = array();
		if($posts_title){
			foreach($posts_title as $p){
				$titles[$p->ID] = $p->post_title;
			}
		}

		if($comments_feed){
                   foreach($comments_feed as $comment){

			if($comment->user_id==$user_ID){ //если автор комментария я сам, то проверяю на наличие дочерних комментариев

                            if($comments_children){

                                $childrens = false;
                                $a=0;
                                foreach((array)$comments_children as $child_com){
                                        if($child_com->comment_parent==$comment->comment_ID){
                                                $childrens[$a++] = $child_com;
                                        }
                                }

                                if($childrens){ //если есть, то вывожу свой и дочерний

                                        $comments .= $this->get_feed_comment($comment,$titles);

                                        $comments .= '<div class="comment-child">';
                                        $comments .= $this->get_childrens($childrens);
                                        $comments .='</div>';
                                }

                            }

			}else{ //если автор комментария не я
                            if($comment->comment_parent!=0){ //то проверяю, есть ли является ли он дочерним комментарием
                                    $parent = $wpdb->get_row($wpdb->prepare("SELECT * FROM $wpdb->comments WHERE comment_ID = '%d'",$comment->comment_parent));
                                    if($parent->user_id!=$user_ID){ //если автор родительского комментария не я, то вывожу
                                            $comments .= $this->get_feed_comment($comment,$titles);
                                    }
                            }else{ //если комментарий не дочерний, то вывожу
                                    $comments .= $this->get_feed_comment($comment,$titles);
                            }
                        }
                    }
                    /*foreach($comments_feed as $k=>$comment){

			if($comment->user_id==$user_ID){ //если автор комментария я сам, то проверяю на наличие дочерних комментариев

                            if($comments_children){

                                $childrens = false;
                                $a=0;
                                foreach((array)$comments_children as $child_com){
                                        if($child_com->comment_parent==$comment->comment_ID){
                                            $a++;
                                                $child_com->post_type = 'comment';
                                                $childrens[$a] = $child_com;
                                        }
                                }

                                if($childrens){ //если есть, то вывожу свой и дочерний
                                    $comments[$k] = $comment;
                                    $comments[$k]->post_date = $childrens[1]->comment_date;
                                    $comments[$k]->post_type = 'comment';
                                    $comments[$k]->childrens = $childrens;
                                    $comments[$k]->parent_comment = $titles[$comment->comment_post_ID];
                                }

                            }

			}else{ //если автор комментария не я
                            if($comment->comment_parent!=0){ //то проверяю, есть ли является ли он дочерним комментарием
                                    $parent = $wpdb->get_row($wpdb->prepare("SELECT * FROM $wpdb->comments WHERE comment_ID = '%d'",$comment->comment_parent));
                                    if($parent->user_id!=$user_ID){ //если автор родительского комментария не я, то вывожу
                                            $comments[$k] = $comment;
                                            $comments[$k]->post_type = 'comment';
                                            $comments[$k]->post_date = $comment->comment_date;
                                            $comments[$k]->parent_comment = $titles[$comment->comment_post_ID];
                                    }
                            }else{ //если комментарий не дочерний, то вывожу
                                    $comments[$k] = $comment;
                                    $comments[$k]->post_type = 'comment';
                                    $comments[$k]->post_date = $comment->comment_date;
                                    $comments[$k]->parent_comment = $titles[$comment->comment_post_ID];
                            }
                        }

                    }*/

		}else{
                    return false;
		}

		return $comments;
	}

        function get_childrens($childrens){
			global $comment;
            foreach($childrens as $comment){
                $feedlist .= $this->get_feed_comment($comment);
            }
            return $feedlist;
        }

	function get_feed_comment($comment,$titles=false){
		global $user_ID,$comment;
		if(isset($titles[$comment->comment_post_ID])) $comment->parent_comment = $titles[$comment->comment_post_ID];
		return rcl_get_include_template('feed-comment.php',__FILE__);
	}

	/*************************************************
	Получаем всех своих подписчиков
	*************************************************/
	function get_all_your_feed_users(){
		global $wpdb;
		global $user_ID;
		if($user_ID){

			$userid = intval($_POST['userid']);
			if(!$userid) return false;

				$page = intval($_POST['page']);
				if(!$page) $page = 1;

				$inpage = 36;
				$start = ($page-1)*$inpage;
				$limit = $start.','.$inpage;
				$next = $page+1;

				$cnt = rcl_get_count_feed($userid);

			if($cnt){

				$users_feed = $wpdb->get_results($wpdb->prepare("SELECT * FROM $wpdb->usermeta WHERE meta_key = 'rcl_feed' AND meta_value='%d' ORDER BY umeta_id DESC LIMIT %d,%d",$userid,$start,$inpage));

				$now = count($users_feed);

				$names = rcl_get_usernames($users_feed,'user_id');

				$feed_list = '';
				if($page==1){
					$feed_list = '<div id="users-feed-'.$userid.'" class="float-window-recall">
					<div id="close-votes-'.$userid.'" class="close"><i class="fa fa-times-circle"></i></div>';
					$feed_list .= '<div>';
				}

				foreach((array)$users_feed as $user){
						$feed_list .= '<a href="'.get_author_posts_url($user->user_id).'" title="'.$names[$user->user_id].'">'.get_avatar($user->user_id,50).'</a>';
				}

				if($now==$inpage) $feed_list .= rcl_get_button('Еще','#',array('class'=>'horizontal-more-button','id'=>'more-users-feed','attr'=>'data-page='.$next));

				if($page==1){
					$feed_list .= '</div>';
					$feed_list .= '</div>';
				}

				$log['otvet']=100;
				$log['user_id']=$userid;
				$log['feed-list']=$feed_list;
			}
		} else {
			$log['otvet']=1;
		}
		echo json_encode($log);
		exit;
	}

	/*************************************************
	Смотрим всех пользователей в своей подписке
	*************************************************/
	function get_all_users_feed_recall(){
		global $user_ID;
		$users_feed = get_user_feeds($user_ID);

		if(!$users_feed){
			$log['recall']=1;
			echo json_encode($log);
			exit;
		}

		$names = rcl_get_usernames($users_feed,'meta_value');

		$feed_list = '<div id="users-feed-'.$user_ID.'" class="float-window-recall">
			 <div id="close-votes-'.$user_ID.'" class="close alignright"><i class="fa fa-times-circle"></i></div>
				<div class="rcl-feedlist">';
		foreach((array)$users_feed as $user){
			$feed_list .= '<span>
					<a href="'.get_author_posts_url($user->meta_value).'" title="'.$names[$user->meta_value].'">'.get_avatar($user->meta_value,50).'</a>'
					.rcl_get_feed_button($user->meta_value)
				.'</span>';
		}
		$feed_list .= '</div></div>';

		$log['recall']=100;
		$log['user_id']=$user_ID;
		$log['feed-list']=$feed_list;
		echo json_encode($log);
		exit;
	}

	/*************************************************
	Подписываемся на пользователя
	*************************************************/
	function add_feed_user_recall(){
		global $user_ID;

		$id_user = intval($_POST['id_user']);
		if(!$id_user) return false;

		if($user_ID){

			$feed = get_user_meta($user_ID,'rcl_feed');

			if(!$feed||array_search($id_user,$feed)===false){

				$res = add_user_meta($user_ID, 'rcl_feed', $id_user);

				if($res){

					do_action('rcl_add_user_feed',$id_user);

					$log['int']=100;
					$log['count']=1;
					$log['recall'] = rcl_get_button(__('Unsubscribe','rcl'),'#',array('icon'=>'fa-bell-slash','class'=>'feed-user','attr'=>'data-feed='.$id_user));
				}
			}else{

				delete_user_meta($user_ID,'rcl_feed',$id_user);

				do_action('rcl_remove_user_feed',$id_user);

				$log['int']=100;
				$log['count']=-1;
				$log['recall'] = rcl_get_button(__('Subscribe','rcl'),'#',array('icon'=>'fa-bell','class'=>'feed-user','attr'=>'data-feed='.$id_user));
			}
		}
		echo json_encode($log);
		exit;
	}

	/*************************************************
	Получаем комментарии из фида
	*************************************************/
	function get_comments_feed_recall(){
		global $user_ID;

		if($user_ID){

			$comments = $this->get_comments_feed();
			if($comments) $loop = $this->feed_comment_loop($comments);

			$feedlist .= '<h2>'.__('Comments','rcl').'</h2>';

			if(!$loop){
				$res['int'] = 100;
				$res['recall'] = '<h3>'.__('It seems that you have not left a single comment or not subscribed.','rcl').'</h3>'
                                        . '<p>'.__('Comment you publish and subscribe to other users, then you can track responses to your comments and to see new comments from users.Comment you publish and subscribe to other users, then you can track responses to your comments and to see new comments from users.','rcl').'</p>';
				echo json_encode($res);
				exit;
			}else{
				$feedlist .= $loop;
			}

			$res['int'] = 100;
			$res['recall'] = $feedlist;

		}

		echo json_encode($res);
		exit;
	}

	function get_comments_feed(){
		global $wpdb,$user_ID;

		$feeds = array();

		$feed_users = get_user_feeds($user_ID);

		foreach((array)$feed_users as $user){ $feeds[] = $user->meta_value; }

		if($feeds){

			$feeds[] = $user_ID;
			$comments_feed = $wpdb->get_results($wpdb->prepare("SELECT cts.comment_ID,cts.comment_parent,cts.user_id,cts.comment_post_ID,cts.comment_content,cts.comment_date FROM $wpdb->comments as cts WHERE cts.user_id IN (".rcl_format_in($feeds).") && cts.comment_approved = '1' GROUP BY cts.comment_ID ORDER BY cts.comment_date DESC LIMIT 40",$feeds));

			if(!$comments_feed)
				$comments_feed = $wpdb->get_results($wpdb->prepare("SELECT * FROM $wpdb->comments WHERE user_id IN (".rcl_format_in($feeds).") && comment_approved = '1' ORDER BY comment_date DESC LIMIT 40",$feeds));

        }else{

			$comments_feed=$wpdb->get_results($wpdb->prepare("
				SELECT      com1.comment_ID,com1.comment_parent,com1.user_id,com1.comment_post_ID,com1.comment_content,com1.comment_date
				FROM        $wpdb->comments com1
				INNER JOIN  $wpdb->comments com2
				on com2.comment_parent = com1.comment_ID
				where com1.user_id = '%d'
				GROUP BY com1.comment_ID ORDER BY com1.comment_date DESC limit %d",$user_ID,40));
        }
		return $comments_feed;
	}

	/*************************************************
	Получаем публикации из фида
	*************************************************/
	function get_posts_feed_recall(){
		global $user_ID;

		if($user_ID){
                    $res['int'] = 100;
                    $res['recall'] = rcl_get_public_feed($user_ID);
		}
		echo json_encode($res);
		exit;
	}

	function get_scripts_feed_rcl($script){

		//$ajaxfile = "type: 'POST', data: dataString, dataType: 'json', url: rcl_url+'add-on/feed/ajax-request.php',";
                $ajaxdata = "type: 'POST', data: dataString, dataType: 'json', url: wpurl+'wp-admin/admin-ajax.php',";

		$script .= "
			/* Смотрим всех пользователей в своей подписке */
				jQuery('.all-users-feed').live('click',function(){
					var dataString = 'action=get_all_users_feed_recall';
					jQuery.ajax({
						".$ajaxdata."
						success: function(data){
							if(data['recall']==100){
								jQuery('.all-users-feed').after(data['feed-list']);
								jQuery('#users-feed-'+data['user_id']).slideDown(data['feed-list']);
							} else {
								rcl_notice('Ошибка!','error');
							}
						}
					});
				return false;
				});
			/* Получаем всех своих подписчиков */
				jQuery('.count_users_feed').live('click',function(){
					var page = 1;
					get_page_users_feed(page);
					return false;
				});
				jQuery('#more-users-feed').live('click',function(){
					var page = jQuery(this).data('page');
					get_page_users_feed(page);
					return false;
				});
				function get_page_users_feed(page){
					rcl_preloader_show('#fd-block .float-window-recall > div');
					var userid = parseInt(jQuery('.wprecallblock').attr('id').replace(/\D+/g,''));
					var dataString = 'action=get_all_your_feed_users&userid='+userid+'&page='+page;
					jQuery.ajax({
							".$ajaxdata."
							success: function(data){
									if(data['otvet']==100){
										if(page==1){
											jQuery('#user-feed-'+data['user_id']).after(data['feed-list']);
											jQuery('#users-feed-'+data['user_id']).slideDown(data['feed-list']);
										}else{
											jQuery('#users-feed-'+data['user_id']+' #more-users-feed').replaceWith(data['feed-list']);
										}
									}else{
										rcl_notice('Авторизуйтесь, чтобы смотреть подписчиков пользователя!','warning');
									}
									rcl_preloader_hide();
							}
					});
                }
			/* Подписываемся на пользователя */
				jQuery('.feed-user').live('click',function(){
					var id_user = jQuery(this).data('feed');
					var dataString = 'action=add_feed_user_recall&id_user='+id_user;
					jQuery.ajax({
						".$ajaxdata."
						success: function(data){
							if(data['int']==100){
								 jQuery('.feed-control-'+id_user).empty().html(data['recall']);
								 var feed_count = jQuery('#feed-count').html();
								 feed_count = parseInt(feed_count) + parseInt(data['count']);
								 jQuery('#feed-count').html(feed_count);
							} else {
								rcl_notice('Ошибка!','error');
							}
						}
					});
					return false;
				});
			/* Получаем комментарии из фида */
				jQuery('#commentfeed').live('click',function(){
					if(jQuery(this).hasClass('active')) return false;
					rcl_preloader_show('#feedlist');
					jQuery('.get-feed').removeClass('active');
					jQuery(this).addClass('active');
					jQuery('#feedlist').slideUp();
					var dataString = 'action=get_comments_feed_recall';
					jQuery.ajax({
						".$ajaxdata."
						success: function(data){
							if(data['int']==100){
								jQuery('#feedlist').delay(1000).queue(function () {jQuery('#feedlist').html(data['recall']);jQuery('#feedlist').dequeue();});
								jQuery('#feedlist').slideDown(1000);
							} else {
								rcl_notice('Ошибка!','error');
							}
							rcl_preloader_hide();
						}
					});
					return false;
				});
			/* Получаем публикации из фида */
				jQuery('#postfeed').live('click',function(){
					if(jQuery(this).hasClass('active')) return false;
					rcl_preloader_show('#feedlist');
					jQuery('.get-feed').removeClass('active');
					jQuery(this).addClass('active');
					jQuery('#feedlist').slideUp();
					var dataString = 'action=get_posts_feed_recall';
					jQuery.ajax({
						".$ajaxdata."
						success: function(data){
							if(data['int']==100){
								jQuery('#feedlist').delay(1000).queue(function () {jQuery('#feedlist').html(data['recall']);jQuery('#feedlist').dequeue();});
								jQuery('#feedlist').slideDown(1000);
							} else {
								rcl_notice('Ошибка!','error');
							}
							rcl_preloader_hide();
						}
					});
					return false;
				});";
		return $script;
	}
}
$Rcl_Feed = new Rcl_Feed();

function rcl_get_public_feed($user_id=false){
    global $user_ID,$wpdb,$active_addons,$post;

    if(!$user_id) $user_id = $user_ID;

    $Rcl_Feed = new Rcl_Feed();
    $feed_users = get_user_feeds($user_id);

    if($feed_users){

            foreach((array)$feed_users as $user){ $feeds[] = $user->meta_value; }

            $post_types = "'post','post-group'";
            if($active_addons['video-gallery']) $post_types .= ",'video'";
            if($active_addons['notes']) $post_types .= ",'notes'";
            if($active_addons['gallery-recall']){
                //$post_types .= ",'attachment'";
                $where = "OR post_type='attachment' AND post_author IN (".implode(',',$feeds).") AND post_excerpt LIKE 'gallery-%'";
            }
            $posts_users = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix ."posts WHERE
				post_author IN (".implode(',',$feeds).") AND post_type IN ($post_types) AND post_status = 'publish' $where ORDER BY post_date DESC LIMIT 15");

            $admin_groups = $wpdb->get_results($wpdb->prepare("SELECT meta_value FROM ".$wpdb->prefix ."usermeta WHERE meta_key LIKE '%s' AND user_id = '%d'",'admin_group_%',$user_id));
            $user_groups = $wpdb->get_results($wpdb->prepare("SELECT meta_value FROM ".$wpdb->prefix ."usermeta WHERE meta_key LIKE '%s' AND user_id = '%d'",'user_group_%',$user_id));

            foreach($admin_groups as $ad){
                    $group_ar[$ad->meta_value] = $ad->meta_value;
            }
            foreach($user_groups as $us){
                    $group_ar[$us->meta_value] = $us->meta_value;
            }

            $posts_groups = get_posts(array(
                'post_type'=>'post-group',
                'numberposts'=>15,
                'author'=>-$user_id

            ));

            /*if($group_ar){
                $posts_groups['tax_query'] = array(
                    array(
                        'taxonomy' => 'groups',
                        'field' => 'id',
                        'terms' => $group_ar,
                        'operator' => 'IN',
                    )
                );
            }*/

            //if($active_addons['video-gallery']) include($active_addons['video-gallery']['src'].'class_video.php');

            foreach($posts_users as $posts_us){
                    $posts_list[$posts_us->ID] = (array)$posts_us;
            }
            foreach($posts_groups as $posts_gr){
                    $posts_list[$posts_gr->ID] = (array)$posts_gr;
            }

            /*if(!$posts_list){
                    return '<h3>'.__('Publications yet','rcl').'</h3><p>'.__('Maybe later, someone will post the news.','rcl').'</p>';
            }*/

            /*$feed_coms = $Rcl_Feed->get_comments_feed();
            if($feed_coms) $comments = $Rcl_Feed->feed_comment_loop($feed_coms);

            foreach($comments as $comment){
                $posts_list[] = (array)$comment;
            }*/

            if(!$posts_list){
                    return '<h3>'.__('Publications yet','rcl').'</h3><p>'.__('Maybe later, someone will post the news.','rcl').'</p>';
            }

            $posts_list = rcl_multisort_array($posts_list, 'post_date', SORT_DESC);

            //print_r($posts_list);

            $posts_list = apply_filters('feed_posts_array',$posts_list);

            $feedlist .= '<h2>'.__('Публикации','rcl').'</h2>';

            foreach($posts_list as $post){

                $post = (object)$post;

                if(!$post->ID&&!$post->comment_ID) continue;

                if($post->ID) setup_postdata($post);

                if(rcl_get_template_path('feed-'.$post->post_type.'.php',__FILE__))
                        $feedlist .= rcl_get_include_template('feed-'.$post->post_type.'.php',__FILE__);
                else $feedlist .= rcl_get_include_template('feed-post.php',__FILE__);

                if($post->childrens){
                    $feedlist .= '<div class="comment-child">';
                    foreach($post->childrens as $post){
                        $post = (object)$post;
                        $feedlist .= rcl_get_include_template('feed-'.$post->post_type.'.php',__FILE__);
                    }
                    $feedlist .= '</div>';
                }

            }

            wp_reset_query();

            return $feedlist;

    }else{
            return '<h3>'.__('You havent signed up anyone elses publication.','rcl').'</h3>'
                    . '<p>'.__('Go to the profile of the user and click the "Subscribe" button and you will be able to monitor his recent publications here.','rcl').'</p>';
    }
}