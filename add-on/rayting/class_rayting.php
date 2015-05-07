<?php

class Rcl_Rating{
	function __construct() {
		add_action('wp_ajax_add_rating_post', array(&$this, 'add_rating_post'));
		add_action('wp_ajax_add_rating_comment', array(&$this, 'add_rating_comment'));
		add_action('wp_ajax_get_votes_comment', array(&$this, 'get_votes_comment'));
		add_action('wp_ajax_get_votes_post', array(&$this, 'get_votes_post'));
		add_action('wp_ajax_get_votes_usercomments', array(&$this, 'get_votes_usercomments'));
		add_action('wp_ajax_get_votes_userposts', array(&$this, 'get_votes_userposts'));
		add_action('wp_ajax_get_votes_user', array(&$this, 'get_votes_user'));
		add_action('wp_ajax_cancel_rating', array(&$this, 'cancel_rating'));		
	}
	function add_rating_post(){
		global $wpdb,$rcl_options,$user_ID;
		if(!$user_ID) exit;

		$p = esc_sql($_POST['post']);
		$post = explode('-', $p);
		$id = intval($_POST['id_rayt']);
		$id_rayt = round(pow($id, 0.5));
		$rayt = $id_rayt - $post[1];
		$post_id = $post[1];

                $rcl_options['count_rayt_products'] = 10;

		$point = rcl_get_post_rating($post_id, $user_ID);

		if($point){
                    $log['otvet']=110;
                    echo json_encode($log);
                    exit;
		}

		$post_data = get_post($post_id);

                if(!$rcl_options['count_rayt_'.$post_data->post_type]) $rcl_options['count_rayt_'.$post_data->post_type] = 1;
                if(absint($rayt)!=$rcl_options['count_rayt_'.$post_data->post_type]){ echo 'Error abs '.$rayt.'-'.$rcl_options['count_rayt_'.$post_data->post_type]; exit;}
                //print_r($post_data);exit;
		if($post_data->post_type=='products'){
			$salefile = $wpdb->get_var($wpdb->prepare("SELECT ID FROM ".$wpdb->prefix."posts WHERE post_parent = '%d' AND post_title = 'salefile'",$post[1]));
			if($salefile){
				$sale = $wpdb->get_var($wpdb->prepare("SELECT ID FROM ".$wpdb->prefix."rmag_files_downloads WHERE parent_id = '%d' AND user_id = '%d'",$post[1],$user_ID));
				if(!$sale){
					$log['otvet']=120;
					$log['message'] = __('You cant change the rating of a product that was purchased personally!','rcl');
					echo json_encode($log);
					exit;
				}
			}
		}

		rcl_insert_post_rating($post_id,$user_ID,$rayt);

		$log['otvet']=100;
		$log['post']=$post[1];
		$log['rayt']=$rayt;

		echo json_encode($log);
		exit;
	}
	function add_rating_comment(){
            global $wpdb,$user_ID,$rcl_options;

            if(!$user_ID){
                    $log['otvet']=110;
                    echo json_encode($log);
                    exit;
            }

            $c = esc_sql($_POST['com']);
            $com = explode('-', $c);
            $id = intval($_POST['id_rayt']);
            $id_rayt = round(pow($id, 0.5));
            $rayt = $id_rayt - $com[1];
            $comment_id = intval($com[1]);

            if(!$rcl_options['count_rayt_comment']) $rcl_options['count_rayt_comment'] = 1;
            if(abs($rayt)!=$rcl_options['count_rayt_comment']) exit;

            $point = rcl_get_comment_rating($comment_id,$user_ID);

            if($point){
                $log['otvet']=110;
                echo json_encode($log);
                exit;
            }

            rcl_insert_comment_rating($comment_id,$user_ID,$rayt);

            $log['otvet']=100;
            $log['com']=$com[1];
            $log['rayt']=$rayt;

            echo json_encode($log);
            exit;
	}
	function get_votes_comment(){
		global $wpdb;
		$id_com = intval($_POST['id_com']);
		$votes_com = $wpdb->get_results($wpdb->prepare("SELECT rayting,user FROM ".RCL_PREF."rayting_comments WHERE comment_id = '%d' ORDER BY ID DESC LIMIT 200",$id_com));
		if($votes_com){

			$names = rcl_get_usernames($votes_com,'user');

			$recall_votes = '<ul>';
			foreach((array)$votes_com as $vote){
				$rayt = $vote->rayting;
                                $class = ($rayt>0) ? 'fa-thumbs-o-up' : 'fa-thumbs-o-down';
				$recall_votes .= '<li><a class="fa '.$class.'" target="_blank" href="'.get_author_posts_url($vote->user).'">'.$names[$vote->user].'</a> '.__('vote','rcl').': '.rcl_get_rating($rayt).'</li>';
			}
			$recall_votes .= '</ul>';

			$log['otvet']=100;
			$log['id_com']=$id_com;
			$log['votes'] = $this->block_rayting($recall_votes,$id_com,'comment');
		}
		echo json_encode($log);
		exit;
	}
	function get_votes_post(){
		global $wpdb;
		$id_post = intval($_POST['id_post']);
		$votes_post = $wpdb->get_results($wpdb->prepare("SELECT user,status FROM ".RCL_PREF."rayting_post WHERE post = '%d' ORDER BY ID DESC LIMIT 200",$id_post));
		if($votes_post){

			$names = rcl_get_usernames($votes_post,'user');

			$recall_votes = '<ul>';
			foreach((array)$votes_post as $vote){
				$rayt = $vote->status;
                                $class = ($rayt>0) ? 'fa-thumbs-o-up' : 'fa-thumbs-o-down';
				$recall_votes .= '<li><a class="fa '.$class.'" target="_blank" href="'.get_author_posts_url($vote->user).'">'.$names[$vote->user].'</a> '.__('vote','rcl').': '.rcl_get_rating($rayt).'</li>';
			}
			$recall_votes .= '</ul>';

			$log['otvet']=100;
			$log['id_post']=$id_post;
			$log['votes']= $this->block_rayting($recall_votes,$id_post,'post');
		}
		echo json_encode($log);
		exit;
	}
	function get_votes_usercomments(){
		global $wpdb,$user_ID;
		if(!$user_ID){
			$log['otvet']=1;
			echo json_encode($log);
			exit;
		}

		$id_user = intval($_POST['iduser']);
		$rcl_comments_rayt = $wpdb->get_results($wpdb->prepare("SELECT user,comment_id,author_com,rayting FROM ".RCL_PREF."rayting_comments WHERE author_com = '%d' ORDER BY ID DESC LIMIT 200",$id_user));

			$recall_votes = '<ul class="rayt-list-user">';
			$n=0;

			$names = rcl_get_usernames($rcl_comments_rayt,'user');

			foreach((array)$rcl_comments_rayt as $comments){

				$n++;
				$rayt = $comments->rayting;
                                $class = ($rayt>0) ? 'fa-thumbs-o-up' : 'fa-thumbs-o-down';
				$recall_votes .= '<li>'.$comments->ID.'<a class="fa '.$class.'" target="_blank" href="'.get_author_posts_url($comments->user).'">'.$names[$comments->user].'</a> '.__('vote','rcl').': '.rcl_get_rating($rayt).' <a href="'.get_comment_link( $comments->comment_id ).'">'.__('comment','rcl').'</a> '.__('entry','rcl').'</li>';

			}

			$recall_votes .= '</ul>';

		if($n!=0){
			$log['otvet']=100;
			$log['iduser']=$id_user;
			$log['votes']=$recall_votes;
		}else{
			$log['otvet']=100;
			$log['iduser']=$id_user;
			$log['votes']='<p>'.__('For user comments no one voted','rcl').'</p>';
		}
		echo json_encode($log);
		exit;
	}
	function get_votes_userposts(){
		global $wpdb,$user_ID;
		if(!$user_ID){
                    $log['otvet']=1;
                    echo json_encode($log);
                    exit;
		}

		$id_user = intval($_POST['iduser']);


                $rcl_rayting_post = $wpdb->get_results($wpdb->prepare("SELECT user,post,status,author_post FROM ".RCL_PREF."rayting_post WHERE author_post = '%d' ORDER BY ID DESC LIMIT 200",$id_user));

                if(!$rcl_rayting_post){
                    $log['otvet']=100;
                    $log['iduser']=$id_user;
                    $log['votes']='<p>'.__('For publishing user nobody voted','rcl').'</p>';
                    echo json_encode($log);
                    exit;

                }

                $recall_votes = '<ul class="rayt-list-user">';
		$n=0;

		foreach((array)$rcl_rayting_post as $user){
			$userslst[$user->user] = $user->user;
			$postslst[$user->post] = $user->post;
		}

		$display_names = $wpdb->get_results($wpdb->prepare("SELECT ID,display_name FROM ".$wpdb->prefix."users WHERE ID IN (".rcl_format_in($userslst).")",$userslst));

		foreach((array)$display_names as $name){
			$names[$name->ID] = $name->display_name;
		}

		$postdata = $wpdb->get_results($wpdb->prepare("SELECT ID,post_title FROM ".$wpdb->prefix."posts WHERE ID IN (".rcl_format_in($postslst).")",$postslst));

		foreach((array)$postdata as $p){
			$title[$p->ID] = $p->post_title;
		}

		foreach((array)$rcl_rayting_post as $post){
			if($post->author_post==$id_user){
				$n++;
				$rayt = $post->status;
                                $class = ($rayt>0) ? 'fa-thumbs-o-up' : 'fa-thumbs-o-down';
				$recall_votes .= '<li><a class="fa '.$class.'" target="_blank" href="'.get_author_posts_url($post->user).'">'.$names[$post->user].'</a> '.__('vote','rcl').': '.rcl_get_rating($rayt).' '.__('entry','rcl').' <a href="/?p='.$post->post.'">'.$title[$post->post].'</a></li>';
			}
		}

		$recall_votes .= '</ul>';

		if($n!=0){
			$log['otvet']=100;
			$log['iduser']=$id_user;
			$log['votes']=$recall_votes;
		}else{
			$log['otvet']=100;
			$log['iduser']=$id_user;
			$log['votes']='<p>'.__('For publishing user nobody voted','rcl').'</p>';
		}
		echo json_encode($log);
		exit;
	}
	function get_votes_user(){
            global $wpdb,$user_ID;
            if(!$user_ID){
                    $log['otvet']=1;
                    echo json_encode($log);
                    exit;
            }
            $id_user = intval($_POST['iduser']);

            $n=0;

            $rcl_comments_rayt = $wpdb->get_results($wpdb->prepare("SELECT user,comment_id,author_com,rayting FROM ".RCL_PREF."rayting_comments WHERE author_com = '%d' ORDER BY ID DESC LIMIT 200",$id_user));

            if($rcl_comments_rayt){
                    $names = rcl_get_usernames($rcl_comments_rayt,'user');
                    $content = '<ul class="rayt-list-user">';
                    foreach((array)$rcl_comments_rayt as $comments){
                        $n++;
                        $rayt = $comments->rayting;
                        $class = ($rayt>0) ? 'fa-thumbs-o-up' : 'fa-thumbs-o-down';
                        $content .= '<li>'
                                . $comments->ID
                                .'<a class="fa '.$class.'" target="_blank" href="'.get_author_posts_url($comments->user).'">'
                                .$names[$comments->user].'</a> '.__('vote','rcl').': '.rcl_get_rating($rayt)
                                .' <a href="'.get_comment_link( $comments->comment_id ).'">'.__('comment','rcl').'</a> '.__('recording','rcl').'</li>';
                    }
                    $content .= '</ul>';
                    $recall_votes = $this->block_user_rayting($content,$id_user,'comments');
            }

            if($n==0){

                $rcl_rayting_post = $wpdb->get_results($wpdb->prepare("SELECT user,post,status,author_post FROM ".RCL_PREF."rayting_post WHERE author_post = '%d' ORDER BY ID DESC LIMIT 200",$id_user));

                if(!$rcl_rayting_post){
                    $content = '<p>'.__('For publications and user comments no one voted','rcl').'</p>';
                    $recall_votes = $this->block_user_rayting($content,$id_user);
                    $log['otvet']=100;
                    $log['iduser']=$id_user;
                    $log['votes']=$recall_votes;
                    echo json_encode($log);
                    exit;
                }

                foreach((array)$rcl_rayting_post as $user){
                        $userslst[$user->user] = $user->user;
                        $postslst[$user->post] = $user->post;
                }

                $display_names = $wpdb->get_results($wpdb->prepare("SELECT ID,display_name FROM ".$wpdb->prefix."users WHERE ID IN (".rcl_format_in($userslst).")",$userslst));

                foreach((array)$display_names as $name){
                        $names[$name->ID] = $name->display_name;
                }

                $postdata = $wpdb->get_results($wpdb->prepare("SELECT ID,post_title FROM ".$wpdb->prefix."posts WHERE ID IN (".rcl_format_in($postslst).")",$postslst));

                foreach((array)$postdata as $p){
                        $title[$p->ID] = $p->post_title;
                }

                $content = '<ul class="rayt-list-user">';
                foreach((array)$rcl_rayting_post as $post){
                    if($post->author_post==$id_user){
                        $n++;
                        $rayt = $post->status;
                        $class = ($rayt>0) ? 'fa-thumbs-o-up' : 'fa-thumbs-o-down';
                        $content .= '<li><a class="fa '.$class.'" target="_blank" href="'.get_author_posts_url($post->user).'">'.$names[$post->user].'</a> '.__('vote','rcl').': '.rcl_get_rating($rayt).' '.__('entry','rcl').' <a href="/?p='.$post->post.'">'.$title[$post->post].'</a></li>';
                    }
                }
                $content .= '</ul>';
                $recall_votes .= get_block_user_rayting($content,$id_user,'posts');
            }

            if($n!=0){
                $log['otvet']=100;
                $log['iduser']=$id_user;
                $log['votes']=$recall_votes;
            }else{
                $log['otvet']=1;
            }
            echo json_encode($log);
            exit;
	}
	function cancel_rating(){
		global $wpdb,$user_ID;

		$type = esc_sql($_POST['type']);
		$id = intval($_POST['id']);

		if($type=='comment'){
                    $point = rcl_get_comment_rating($id,$user_ID);
                    if(!$point) return false;
                    $total = rcl_get_total_comment_rating($id);
                    rcl_delete_comment_rating($id,$user_ID,$point);
		}
		if($type=='post'){
                    $point = rcl_get_post_rating($id,$user_ID);
                    if(!$point) return false;
                    $total = rcl_get_total_post_rating($id);
                    rcl_delete_post_rating($id,$user_ID,$point);
		}

                $newrayt = $total - $point;

		$log['result']=100;
		$log['type']=$type;
		$log['idpost']=$id;
		$log['rayt']=rcl_get_rating($newrayt);
		echo json_encode($log);
	exit;
	}
	
	function block_rayting($content,$id_block,$type){
		return '<div id="votes-'.$type.'-'.$id_block.'" class="float-window-recall">			
		<div id="close-votes-'.$id_block.'" class="close"><i class="fa fa-times-circle"></i></div>'
		.$content
		.'</div>';
	}
	
	function block_user_rayting($content,$id_user,$type='false'){
    
		$btns = array('posts'=>'Публикации','comments'=>'Комментарии');
		$block = '<div id="votes-user-'.$id_user.'" class="float-window-recall">			
		<div id="close-votes-'.$id_user.'" class="close"><i class="fa fa-times-circle"></i></div>';
		
		foreach($btns as $key=>$title){
			$class = ($type==$key)? 'active' : '';
			$block .= rcl_get_button($title,'#',array('class'=> $class.' view-rayt-'.$key,'id'=>'view-rayt-'.$key.'-'.$id_user)).' ';
		}

		$block .= '<div class="content-rayting-block">'
		.$content
		. '</div>'
		. '</div>'; 
		
		return $block;
	}
}
$Rcl_Rating = new Rcl_Rating();