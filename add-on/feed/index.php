<?php
if(function_exists('rcl_enqueue_style')) rcl_enqueue_style('feed',__FILE__);

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
                    rcl_block('footer',array(&$this, 'get_feed_button'),array('id'=>'fd-footer','order'=>5,'public'=>-1));
                }
        endif;

    }

	function get_users_feed($user_id=false){
		global $wpdb,$user_ID;
		if(!$user_id) $user_id = $user_ID;
		return $wpdb->get_results($wpdb->prepare("SELECT * FROM $wpdb->usermeta WHERE meta_key LIKE '%s' AND user_id = '%d'",'feed_user_%',$user_id));
	}

	function get_feed_button($author_lk){
		global $user_ID;

		if(!$user_ID||$user_ID==$author_lk) return false;

                $feed_status = (!get_usermeta($user_ID, 'feed_user_'.$author_lk, true))? __('Subscribe','rcl'): __('Unsubscribe','rcl');

                $footer_lk = '<span id="feed-control"';
                if(!is_single()) $footer_lk .= ' class="alignright"';
                $footer_lk .= '>'
                        .rcl_get_button($feed_status,'#',array('icon'=>'fa-twitter','class'=>'feed-user','id'=>'feed-'.$author_lk,'attr'=>'title='.$feed_status));

		return $footer_lk;
	}

	function get_feedout_button($user_id){
		return '<div id="feed-control" class="alignright">'.rcl_get_button(__('Unsubscribe','rcl'),'#',array('icon'=>'fa-twitter','class'=>'feed-user','id'=>'feed-'.$user_id,'attr'=>'title='.__('Unsubscribe','rcl'))).'</div>';
	}


	function add_feed_button_user_lk($author_lk){
		global $wpdb,$user_ID;
		$yours_feed = '';
                $feed_count = $wpdb->get_var($wpdb->prepare("SELECT count(umeta_id) FROM $wpdb->usermeta WHERE meta_key = '%s'",'feed_user_'.$author_lk));
                if($user_ID==$author_lk){
                        $users_group = $wpdb->get_var($wpdb->prepare("SELECT umeta_id FROM $wpdb->usermeta WHERE meta_key LIKE '%s' AND user_id = '%d'",'feed_user_%',$user_ID));
                        if($users_group) $yours_feed = '<p><a class="all-users-feed">'.__('My subscriptions','rcl').'</a></p>';
                }
                if($feed_count==0) $feed_info = '<p><b>'.__('Subscribers','rcl').': <span id="feed-count">'.$feed_count.'</span></b></p>'.$yours_feed;
                else $feed_info = '<p><b><a class="count_users_feed" id="user-feed-'.$author_lk.'">'.__('Subscribers','rcl').': <span id="feed-count">'.$feed_count.'</span></a></b></p>'.$yours_feed;

                return $feed_info;
	}


	function last_post_and_comments_feed(){

		global $user_ID;

		if(!$user_ID){
			$feedlist = '<p class="aligncenter">'.__('Login or register to view the latest publications and comments from users on which you will you subscribed.','rcl').'</p>';
			return $feedlist;
		}

		$comments_feed = $this->get_comments_feed();

		$feedlist = '<p class="alignright" id="feed-button">
		'.rcl_get_button(__('Comments','rcl'),'#',array('icon'=>false,'class'=>'get-feed active','id'=>'commentfeed')).'
		'.rcl_get_button(__('Publication','rcl'),'#',array('icon'=>false,'class'=>'get-feed ','id'=>'postfeed')).'
		</p>
		<span class="loader"></span>
		<div id="feedlist">';

		if(!$comments_feed){
			$feedlist .= '<h3 align="center">'.__('No comment from the users you follow, as well as any responses to your comments. More activity!','rcl').'</h3>';
			$feedlist .= '</div>';
			return $feedlist;
		}

		$feedlist .= '<h2>'.__('Comments','rcl').'</h2>';
		$feedlist .= $this->feed_comment_loop($comments_feed);

		$feedlist .= '</div>';

		return $feedlist;


	}

	function feed_comment_loop($comments_feed){

		global $user_ID,$wpdb;

		$comments_children=$wpdb->get_results(
			$wpdb->prepare("SELECT      com2.comment_ID,com2.comment_parent,com2.user_id,com2.comment_post_ID,com2.comment_content,com2.comment_date
                        FROM        $wpdb->comments com1
                        INNER JOIN  $wpdb->comments com2
			on com2.comment_parent = com1.comment_ID
                        where com1.user_id = '%d'
			ORDER BY com2.comment_date DESC limit %d",$user_ID,40));

		foreach((array)$comments_feed as $c){ $postsids[] = $c->comment_post_ID; }

		$posts_title = $wpdb->get_results($wpdb->prepare("SELECT ID,post_title FROM $wpdb->posts WHERE ID IN (".rcl_format_in($postsids).")",$postsids));

		foreach((array)$posts_title as $p){
			$titles[$p->ID] = $p->post_title;
		}

		foreach((array)$comments_feed as $comment){
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

						$feedlist .= $this->get_feed_comment($comment,$titles);

						$feedlist .= '<div class="comment-child">';
						$feedlist .= $this->get_childrens($childrens);
						$feedlist .='</div>';
					}
                                    }

				}else{ //если автор комментария не я
					if($comment->comment_parent!=0){ //то проверяю, есть ли является ли он дочерним комментарием
						$parent = $wpdb->get_row($wpdb->prepare("SELECT * FROM $wpdb->comments WHERE comment_ID = '%d'",$comment->comment_parent));
						if($parent->user_id!=$user_ID){ //если автор родительского комментария не я, то вывожу
							$feedlist .= $this->get_feed_comment($comment,$titles);
						}
					}else{ //если комментарий не дочерний, то вывожу
						$feedlist .= $this->get_feed_comment($comment,$titles);
					}
				}
			}

		return $feedlist;
	}

        function get_childrens($childrens){
            foreach($childrens as $child){
                $feedlist .= $this->get_feed_comment($child);
            }
            return $feedlist;
        }

	function get_feed_comment($comment,$titles=false){
		global $user_ID;

		$feedlist = '<div id="feed-comment-'.$comment->comment_post_ID.'" class="feedcomment">
		<div class="feed-author-avatar"><a href="'.get_author_posts_url($comment->user_id).'">'.get_avatar($comment->user_id,50).'</a></div>';
		if($titles) $feedlist .= '<h3 class="feed-title">'.__('for recording','rcl').': <a href="'.get_bloginfo('wpurl').'/?p='.$comment->comment_post_ID.'">'.$titles[$comment->comment_post_ID].'</a></h3>';
		else $feedlist .= '<h4 class="recall-comment">'.__('in reply to your comment','rcl').'</h4>';
		$feedlist .= '<small>'.date('d.m.Y H:i', strtotime($comment->comment_date)).'</small>';

		$comment_content = apply_filters('feed_comment_text_rcl',$comment->comment_content,$comment);

		$feedlist .= '<div class="feed-content">'.$comment_content.'</div>';
		if($comment->user_id!=$user_ID) $feedlist .= '<p align="right"><a target="_blank" href="'.get_bloginfo('wpurl').'/?p='.$comment->comment_post_ID.'#comment-'.$comment->comment_ID.'">Ответить</a></p>';
		$feedlist .= '</div>';

		return $feedlist;
	}

	/*************************************************
	Получаем всех своих подписчиков
	*************************************************/
	function get_all_your_feed_users(){
		global $wpdb;
		global $user_ID;
		if($user_ID){
			//require_once('../../ajax-data/avatar.php');
			$userid = intval($_POST['userid']);
			if(!$userid) return false;
                        $page = intval($_POST['page']);
                        if(!$page) $page = 1;

                        $inpage = 36;
                        $start = ($page-1)*$inpage;
                        $limit = $start.','.$inpage;
                        $next = $page+1;

                        $cnt = $wpdb->get_var($wpdb->prepare("SELECT COUNT(umeta_id) FROM $wpdb->usermeta WHERE meta_key = '%s'",'feed_user_'.$userid));

			if($cnt){

                            $users_feed = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".$wpdb->prefix ."usermeta WHERE meta_key = '%s' ORDER BY umeta_id DESC LIMIT %d,%d",'feed_user_'.$userid,$start,$inpage));
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
		$users_feed = $this->get_users_feed();

		if(!$users_feed){
			$log['recall']=1;
			echo json_encode($log);
			exit;
		}

		$names = rcl_get_usernames($users_feed,'meta_value');

		$feed_list = '<div id="users-feed-'.$user_ID.'" class="float-window-recall">
			 <div id="close-votes-'.$user_ID.'" class="close alignright"><i class="fa fa-times-circle"></i></div>
				<div>';
		foreach((array)$users_feed as $user){
			$feed_list .= '<a href="'.get_author_posts_url($user->meta_value).'" title="'.$names[$user->meta_value].'">'.get_avatar($user->meta_value,50).'</a>';
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
			$feed = get_usermeta($user_ID,'feed_user_'.$id_user);
			if(!$feed){
				$res = update_usermeta($user_ID, 'feed_user_'.$id_user, $id_user);

				if($res){
					do_action('add_user_feed',$user_ID,$id_user);

					$log['int']=100;
					$log['count']=1;
					$log['recall'] = rcl_get_button(__('Unsubscribe','rcl'),'#',array('icon'=>'fa-twitter','class'=>'feed-user ','id'=>'feed-'.$id_user));
				}
			}else{
				delete_usermeta($user_ID,'feed_user_'.$id_user);

				do_action('remove_user_feed',$user_ID,$id_user);

				$log['int']=100;
				$log['count']=-1;
				$log['recall'] = rcl_get_button(__('Subscribe','rcl'),'#',array('icon'=>'fa-twitter','class'=>'feed-user ','id'=>'feed-'.$id_user));
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

			$comments_feed = $this->get_comments_feed();

			if(!$comments_feed){
				$res['int'] = 100;
				$res['recall'] = '<h3>'.__('It seems that you have not left a single comment or not subscribed.','rcl').'</h3>'
                                        . '<p>'.__('Comment you publish and subscribe to other users, then you can track responses to your comments and to see new comments from users.Comment you publish and subscribe to other users, then you can track responses to your comments and to see new comments from users.','rcl').'</p>';
				echo json_encode($res);
				exit;
			}
			$a=0;
			foreach((array)$comments_feed as $c){
				if(++$a>1) $postsids .= ',';
				$postsids .= $c->comment_post_ID;
			}

			$feedlist .= '<h2>'.__('Comments','rcl').'</h2>';
			$feedlist .= $this->feed_comment_loop($comments_feed);
			$res['int'] = 100;
			$res['recall'] = $feedlist;

		}

		echo json_encode($res);
		exit;
	}

	function get_comments_feed(){
		global $wpdb,$user_ID;

		$feed_users = $this->get_users_feed();

		foreach((array)$feed_users as $user){ $feeds[] = $user->meta_value; }

		if($feeds){
			$feeds[] = $user_ID;
			$comments_feed = $wpdb->get_results($wpdb->prepare("SELECT cts.comment_ID,cts.comment_parent,cts.user_id,cts.comment_post_ID,cts.comment_content,cts.comment_date FROM ".$wpdb->prefix."comments as cts WHERE cts.user_id IN(".rcl_format_in($feeds).") && cts.comment_approved = '%d' GROUP BY cts.comment_ID ORDER BY cts.comment_date DESC LIMIT %d",$feeds,1,40));

			if(!$comments_feed) $comments_feed = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".$wpdb->prefix."comments WHERE user_id IN (".rcl_format_in($feeds).") && comment_approved = '%d' ORDER BY comment_date DESC LIMIT %d",$feeds,1,40));
                }else{
                       $comments_feed=$wpdb->get_results($wpdb->prepare("
                        SELECT      com1.comment_ID,com1.comment_parent,com1.user_id,com1.comment_post_ID,com1.comment_content,com1.comment_date
                        FROM        $wpdb->comments com1
                        INNER JOIN  $wpdb->comments com2
			on com2.comment_parent = com1.comment_ID
                        where com1.user_id = '%d'
			GROUP BY com1.comment_ID ORDER BY com1.comment_date DESC limit %d",$user_ID,40));
                       //print_r($comments_feed);
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
					var dataString = 'action=get_all_users_feed_recall&user_ID='+user_ID;
					jQuery.ajax({
						".$ajaxdata."
						success: function(data){
							if(data['recall']==100){
								jQuery('.all-users-feed').after(data['feed-list']);
								jQuery('#users-feed-'+data['user_id']).slideDown(data['feed-list']);
							} else {
								alert('Ошибка!');
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
                                    var userid = parseInt(jQuery('.wprecallblock').attr('id').replace(/\D+/g,''));
                                    var dataString = 'action=get_all_your_feed_users&userid='+userid+'&page='+page+'&user_ID='+user_ID;
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
                                                        alert('Авторизуйтесь, чтобы смотреть подписчиков пользователя!');
                                                    }
                                            }
                                    });
                                }
			/* Подписываемся на пользователя */
				jQuery('.feed-user').live('click',function(){
					var id_user = parseInt(jQuery(this).attr('id').replace(/\D+/g,''));
					var dataString = 'action=add_feed_user_recall&id_user='+id_user+'&user_ID='+user_ID;
					jQuery.ajax({
						".$ajaxdata."
						success: function(data){
							if(data['int']==100){
								 jQuery('#feed-control').empty().html(data['recall']);
								 var feed_count = jQuery('#feed-count').html();
								 feed_count = parseInt(feed_count) + parseInt(data['count']);
								 jQuery('#feed-count').html(feed_count);
							} else {
								alert('Ошибка!');
							}
						}
					});
					return false;
				});
			/* Получаем комментарии из фида */
				jQuery('#commentfeed').live('click',function(){
					if(jQuery(this).hasClass('active')) return false;
					jQuery('.get-feed').removeClass('active');
					jQuery(this).addClass('active');
					jQuery('.loader').html('<img src=\''+rcl_url+'css/img/loader.gif\'>');
					jQuery('#feedlist').slideUp();
					var dataString = 'action=get_comments_feed_recall&user_ID='+user_ID;
					jQuery.ajax({
						".$ajaxdata."
						success: function(data){
							if(data['int']==100){
								jQuery('#feedlist').delay(1000).queue(function () {jQuery('#feedlist').html(data['recall']);jQuery('#feedlist').dequeue();});
								jQuery('#feedlist').slideDown(1000);
								jQuery('.loader').delay(1000).queue(function () {jQuery('.loader').empty();jQuery('.loader').dequeue();});
							} else {
								alert('Ошибка!');
							}
						}
					});
					return false;
				});
			/* Получаем публикации из фида */
				jQuery('#postfeed').live('click',function(){
					if(jQuery(this).hasClass('active')) return false;
					jQuery('.get-feed').removeClass('active');
					jQuery(this).addClass('active');
					jQuery('.loader').html('<img src=\''+rcl_url+'css/img/loader.gif\'>');
					jQuery('#feedlist').slideUp();
					var dataString = 'action=get_posts_feed_recall&user_ID='+user_ID;
					jQuery.ajax({
						".$ajaxdata."
						success: function(data){
							if(data['int']==100){
								jQuery('#feedlist').delay(1000).queue(function () {jQuery('#feedlist').html(data['recall']);jQuery('#feedlist').dequeue();});
								jQuery('#feedlist').slideDown(1000);
								jQuery('.loader').delay(1000).queue(function () {jQuery('.loader').empty();jQuery('.loader').dequeue();});
							} else {
								alert('Ошибка!');
							}
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
    $feed_users = $Rcl_Feed->get_users_feed($user_id);

    if($feed_users){

            foreach((array)$feed_users as $user){ $feeds[] = $user->meta_value; }

            $post_types = "'post','post-group'";
            if($active_addons['video-gallery']) $post_types .= ",'video'";
            if($active_addons['notes']) $post_types .= ",'notes'";
            if($active_addons['gallery-recall']){
                //$post_types .= ",'attachment'";
                $where = $wpdb->prepare("OR post_type='attachment' AND post_author IN (".rcl_format_in($feeds).") AND post_excerpt LIKE '%s'",$feeds,'gallery-%');
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

            if($group_ar){
                $posts_groups['tax_query'] = array(
                    array(
                        'taxonomy' => 'groups',
                        'field' => 'id',
                        'terms' => $group_ar,
                        'operator' => 'IN',
                    )
                );
            }

            //if($active_addons['video-gallery']) include($active_addons['video-gallery']['src'].'class_video.php');

            foreach($posts_users as $posts_us){
                    $posts_list[$posts_us->ID] = (array)$posts_us;
            }
            foreach($posts_groups as $posts_gr){
                    $posts_list[$posts_gr->ID] = (array)$posts_gr;
            }

            if(!$posts_list){
                    return '<h3>'.__('Publications yet','rcl').'</h3><p>'.__('Maybe later, someone will post the news.','rcl').'</p>';
            }

            $posts_list = rcl_multisort_array($posts_list, 'post_date', SORT_DESC);

            $feedlist .= '<h2>'.__('Publication','rcl').'</h2>';
			
            foreach($posts_list as $post){ 
			
					$post = (object)$post;
					setup_postdata($post);

                    $feedlist .= '<div id="feed-post-'.$post->ID.'" class="feed-post">';

                    $post_content = strip_tags($post->post_content);

                    if(strlen($post_content) > 400){
                            $post_content = substr($post_content, 0, 400);
                            $post_content = preg_replace('@(.*)\s[^\s]*$@s', '\\1 ... <a href="'.get_permalink($post->ID).'">далее</a>', $post_content);
                    }

                    $post_content = apply_filters('the_excerpt',$post_content);

                    $feedlist .= '<div class="feed-author-avatar"><a href="'.get_author_posts_url($post->post_author).'">'.get_avatar($post->post_author,50).'</a></div>';
                    $feedlist .= '<h3 class="feed-title"><a href="'.get_permalink($post->ID).'">'.$post->post_title.'</a></h3><small>'.date('d.m.Y H:i', strtotime($post->post_date)).'</small>';

                    if( has_post_thumbnail($post->ID) ) {
                            $feedlist .= get_the_post_thumbnail( $post->ID, 'medium', 'class=aligncenter' );
                    }

                    if($active_addons['gallery-recall']&&$post->post_type=='attachment'){
                        $src = wp_get_attachment_image_src($post->ID,'medium');
                        $feedlist .= '<a href="'.get_permalink($post->ID).'"><img class="aligncenter" src="' . $src[0] . '" alt="" /></a>';
                    }

                    if($post->post_type=='video'&&$active_addons['video-gallery']){
                            $data = explode(':',$post->post_excerpt);
                            $video = new Rcl_Video();
                            $video->service = $data[0];
                            $video->video_id = $data[1];
                            $video->height = 300;
                            $video->width = 450;
                            $feedlist .= '<div class="video-iframe aligncenter">'.$video->rcl_get_video_window().'</div>';
                    }
                    $feedlist .= '<div class="feed-content">'.$post_content.'</div>';
                    //$feedlist .= $this->get_feedout_button($post['post_author']);
                    $feedlist .= '<div class="feed-comment">'.__('Comments','rcl').' ('.get_comments_number( $post->ID ).')</div>';
                    $feedlist .= '</div>';
            }
			wp_reset_query();

            return $feedlist;

    }else{
            return '<h3>'.__('You havent signed up anyone elses publication.','rcl').'</h3>'
                    . '<p>'.__('Go to the profile of the user and click the "Subscribe" button and you will be able to monitor his recent publications here.','rcl').'</p>';
    }
}