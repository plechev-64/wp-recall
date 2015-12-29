<?php
if(function_exists('rcl_register_rating_type')){
	require_once 'rating-review.php';
}

rcl_enqueue_style('review',__FILE__);

add_action('init','rcl_tab_review');
function rcl_tab_review(){
    rcl_tab('recall',array('Rcl_Review','get_content_review'),__('Reviews','wp-recall'),array(
            'ajax-load'=>true,
            'public'=>1,
            'class'=>'fa-trophy',
            'order'=>50,
            'cache'=>true
	));
}

add_action('rcl_add_review','rcl_review_delete_cache',10);
add_action('rcl_delete_review','rcl_review_delete_cache',10);
function rcl_review_delete_cache($author_lk){
    global $rcl_options;
    if(isset($rcl_options['use_cache'])&&$rcl_options['use_cache']){
        
        $string = rcl_format_url(get_author_posts_url($author_lk),'recall');
        rcl_delete_file_cache($string);
    }
}

class Rcl_Review{

	public function __construct() {
		if (!is_admin()){
			add_action('init', array(&$this, 'add_review_activate'));
			add_action('init', array(&$this, 'delete_review_activate'));

		}else{
			add_filter('admin_options_wprecall',array(&$this, 'get_review_options'));
		}
		add_action('delete_user',array(&$this, 'delete_reviews_user'));

    }

	//Оставляем пользователю отзыв
	function add_review(){
		global $user_ID,$wpdb,$rcl_options,$rcl_rating_types;

		if(!$user_ID) wp_die(__('You dont have that right!','wp-recall'));

		$adressat_id = intval(pow($_POST['user_id'], 0.5));
		$content_otziv = esc_html($_POST['content_otz']);
		$status = intval($_POST['status']);
		$online = intval($_POST['online']);
		$count_rayt = (isset($rcl_options['rating_point_rcl-review']))? $rcl_options['rating_point_rcl-review']: 1;
		if($status<0) $count_rayt = $count_rayt*-1;
		if($status==0)$count_rayt = 0;

		$otziv = $wpdb->get_row($wpdb->prepare("SELECT * FROM ".RCL_PREF."profile_otziv WHERE user_id = '%d' AND author_id = '%d'",$adressat_id,$user_ID));

		if(!$otziv){

			$content_otziv = apply_filters('rcl_content_recall',$content_otziv,$user_ID,$adressat_id);

			$result = $wpdb->insert(
				RCL_PREF.'profile_otziv',
				array( 'author_id' => $user_ID, 'content_otziv' => $content_otziv, 'user_id' => $adressat_id, 'status' => $count_rayt )
			);

			if (!$result) wp_die('Error');

		}

		do_action('rcl_add_review',$adressat_id);

		if($online != 0){
			wp_redirect( rcl_format_url(get_author_posts_url($adressat_id),'recall'));  exit;
		}

		$title = __('You left a review','wp-recall');
		$to = get_the_author_meta('user_email',$adressat_id);
		$mess = '
		<h3>'.__('You have been leaving feedback','wp-recall').'</h3>
		<p>'.__('from the user','wp-recall').' '.get_the_author_meta('display_name',$user_ID).'</p>
		<p>'.__('You can read the message by clicking on','wp-recall').' <a href="'.rcl_format_url(get_author_posts_url($adressat_id),'recall').'">'.__('the link','wp-recall').'</a></p>';

		rcl_mail($to, $title, $mess);

		wp_redirect( rcl_format_url(get_author_posts_url($adressat_id),'recall') );  exit;

	}

	function add_review_activate ( ) {
	  if ( isset( $_POST['add_review'] ) ) {
		add_action( 'wp', array(&$this, 'add_review') );
	  }
	}

	function delete_reviews_user($user){
		global  $wpdb,$rcl_options;
		$rews = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".RCL_PREF."profile_otziv WHERE author_id = '%d'",$user));
		if(!$rews)return false;
		foreach($rews as $rew){
			$wpdb->query($wpdb->prepare("DELETE FROM ".RCL_PREF."profile_otziv WHERE ID = '%d'",$rew->ID));
			if($rcl_options['rayt_recall_user_rayt']==1&&function_exists('rcl_update_user_rating')){
				$rec = $rew->status;
				if($rec>0) $rec = $rec*(-1);
				else if($rec<0) $rec = abs($rec);
                                rcl_update_user_rating($rew->user_id,$rec,'review');
			}
		}
	}

	//Удаляем отзыв
	function delete_review(){
		global $wpdb,$user_ID,$rcl_options;
		if($user_ID){
			$recall_id = intval($_POST['recall_id']);
			$user_id = intval($_POST['user_id']);

			$review = $wpdb->get_row($wpdb->prepare("SELECT * FROM ".RCL_PREF."profile_otziv WHERE ID = '%d'",$recall_id));

			$result = $wpdb->query($wpdb->prepare("DELETE FROM ".RCL_PREF."profile_otziv WHERE ID = '%d'",$recall_id));

			if ($result) {

				do_action('rcl_delete_review',$user_id);

				wp_redirect( rcl_format_url(get_author_posts_url($user_id),'recall') );  exit;

			} else {

				wp_die('Error');

			}
		}
	}

	function delete_review_activate ( ) {
	  if ( isset( $_POST['delete_review'] ) ) {
		add_action( 'wp', array(&$this, 'delete_review') );
	  }
	}


	function get_review_options($content){

            $opt = new Rcl_Options(__FILE__);

            $content .= $opt->options(
                __('Settings reviews','wp-recall'),
                $opt->option_block(
                    array(
                        $opt->title(__('Reviews','wp-recall')),
                        $opt->label(__('To accept and leave feedback can','wp-recall')),
                        $opt->option('select',array(
                            'name'=>'type_recall',
                            'options'=>array(__('All','wp-recall'),__('With published posts','wp-recall'))
                        )),
						$opt->label(__('Restriction rated','wp-recall')),
						$opt->option('number',array('name'=>'rw_limit_rating')),
						$opt->notice(__('the default not limited','wp-recall'))
                    )
                )
            );

            return $content;
	}

	function get_status($st){
            if($st>0) return '<span class="plus status"><i class="fa fa-thumbs-o-up"></i></span>';
            if($st<0) return '<span class="minus status"><i class="fa fa-thumbs-o-down"></i></span>';
	}

        function get_content_review($user_LK){
            global $wpdb,$user_ID,$rcl_options;

            $online = 0;

            $otzivy = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".RCL_PREF."profile_otziv WHERE user_id = '%d'",$user_LK));
            if($otzivy){
                $recall_block = '';
                foreach($otzivy as $otziv){

                    if($otziv->status>0) $status = 1;
                    else if($otziv->status<0) $status = '-1';
                    else $status = 0;

                    $content = stripslashes_deep(esc_html($otziv->content_otziv));

                    $recall_block .= '<div class="public-post recall'.$status.'">
                    <div class="author-avatar">'.get_avatar($otziv->author_id, 60).'</div>
                    <div class="content-recall">
                    '.$this->get_status($otziv->status).'
                    <p>
                    <strong><a href="'.get_author_posts_url($otziv->author_id).'">'.get_the_author_meta('display_name', $otziv->author_id).'</a> '.__('leave a review','wp-recall').':</strong>
                    </p>'.nl2br($content);

                        if(function_exists('rcl_get_html_post_rating')) $recall_block .= rcl_get_html_post_rating($otziv->ID,'review-content',$otziv->author_id);

                        $recall_block .= '</div>';

                    if($user_ID==$otziv->author_id){
                            $recall_block .= '<form method="post" action="" class="review-delete">
                            <input type="hidden" name="user_id" value="'.$otziv->user_id.'">
                            <input type="hidden" name="recall_id" value="'.$otziv->ID.'">
                            <input type="submit" class="recall-button" name="delete_review" value="'.__('Delete','wp-recall').'">
                            </form>';
                    }

                    $recall_block .= '</div>';
                }
            }else if($user_ID==$user_LK){
                    $recall_block = '<p>'.__('You have not left any reviews','wp-recall').'</p>';
            }else if($user_ID!=$user_LK){
                    $recall_block = '<h3>'.__('The user has no reviews yet','wp-recall').'</h3>';
            }
            //получаем кол-во отзывов текущего пользователя об авторе

            if($user_ID!=$user_LK&&$user_ID) {

					$rt_limit = (isset($rcl_options['rw_limit_rating'])&&$rcl_options['rw_limit_rating'])? $rcl_options['rw_limit_rating']: 0;

					if($rt_limit){
						if(function_exists('rcl_get_user_rating')){
							$rating = rcl_get_user_rating($user_ID);
							if($rating<$rt_limit){
								$notice = '<div class="notify-lk"><div class="warning">'.sprintf(__('Members with a value rating of less than %d can not leave a review.','wp-recall'),$rt_limit).'</div></div>';
                                $notice .= $recall_block;
                                return $notice;
							}
						}
					}

                    if($rcl_options['type_recall']==1){

                        $count_post_author = $wpdb->get_var($wpdb->prepare("SELECT COUNT(ID) FROM ".$wpdb->prefix ."posts WHERE post_author = '%d' AND post_status = 'publish' LIMIT 1",$user_ID));
                        $count_post_user = $wpdb->get_var($wpdb->prepare("SELECT COUNT(ID) FROM ".$wpdb->prefix ."posts WHERE post_author = '%d' AND post_status = 'publish' LIMIT 1",$user_LK));

                        if(!$count_post_author||!$count_post_user){
                                $notice = '<div class="notify-lk"><div class="warning">'.__('Users without published records cannot accept and add reviews.','wp-recall').'</div></div>';
                                $notice .= $recall_block;
                                return $block_wprecall;
                        }

                    }

                    $user_ID_true = $wpdb->get_var($wpdb->prepare("SELECT COUNT(ID) FROM ".RCL_PREF."profile_otziv WHERE user_id = '%d' AND author_id = '%d' LIMIT 1",$user_LK,$user_ID));

                    if($user_ID_true==0):

                        $addres_user = pow($user_LK, 2);
                        $recall_form = '<div class="otziv">
                                <form name="addrecall" method="post" action="">
                                <p>'.__('Review text','wp-recall').':</p>
                                <input type="radio" name="status" value="1" id="labeled_1" /><label for="labeled_1">'.__('Positively','wp-recall').'</label>
                                <input type="radio" name="status" value="0" id="labeled_2" checked="checked"/><label for="labeled_2">'.__('Neutral','wp-recall').'</label>
                                <input type="radio" name="status" value="-1" id="labeled_3" /><label for="labeled_3">'.__('Negatively','wp-recall').'</label><br />
                                <label for="content_otz"></label>
                                <textarea required name="content_otz" id="content_otz" rows="5" style="width:100%;padding:0;"></textarea>
                                <input type="hidden" name="online" value="'.$online.'">
                                <input type="hidden" name="user_id" value="'.$addres_user.'">
                                <p style="text-align:right;"><input type="submit" name="add_review" class="recall-button" value="'.__('Add a review','wp-recall').'"></p>
                                </form>
                                </div>';

					$recall_block .= apply_filters('rcl_form_review',$recall_form);

                    endif;
            }

            return $recall_block;
        }
}

$Rcl_Review = new Rcl_Review();