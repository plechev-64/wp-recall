<?php

if(function_exists('rcl_enqueue_style')) rcl_enqueue_style('private',__FILE__);

function rcl_count_noread_messages($user_id){
	global  $wpdb;
	$where = "WHERE adressat_mess = '$user_id' AND status_mess='0'";
	return $wpdb->get_var("SELECT COUNT(ID) FROM ".RCL_PREF."private_message $where");
}

add_action('wp','rcl_download_file_message');
function rcl_download_file_message(){
	global $user_ID,$wpdb;

	if ( !isset( $_GET['rcl-download-id'] ) ) return false;
	$id_file = base64_decode($_GET['rcl-download-id']);

	if ( !$user_ID||!wp_verify_nonce( $_GET['_wpnonce'], 'user-'.$user_ID ) ) return false;

	$file = $wpdb->get_row($wpdb->prepare("SELECT * FROM ".$wpdb->prefix."rcl_private_message WHERE ID = '%d' AND adressat_mess = '%d' AND status_mess = '5'",$id_file,$user_ID));

	if(!$file) wp_die(__('File does not exist on the server or it has already been loaded!','rcl'));

	$name = explode('/',$file->content_mess);
	$cnt = count($name);
	$f_name = $name[--$cnt];

	$wpdb->update( RCL_PREF.'private_message',array( 'status_mess' => 6,'content_mess' => __('The file was loaded.','rcl') ),array( 'ID' => $file->ID ));

	header('Content-Description: File Transfer');
	header('Content-Disposition: attachment; filename="'.$f_name.'"');
	header('Content-Type: application/octet-stream; charset=utf-8');
	readfile($file->content_mess);

	$upload_dir = wp_upload_dir();
	$path_temp = $upload_dir['basedir'].'/temp-files/'.$f_name;
	unlink($path_temp);

	exit;
}

add_action('wp_enqueue_scripts', 'rcl_messages_scripts');
function rcl_messages_scripts(){
	global $user_ID,$rcl_options,$post,$wpdb;
	if(isset($rcl_options['notify_message'])&&$rcl_options['notify_message'])
            return false;
	wp_enqueue_script( 'jquery' );
	$glup = $rcl_options['global_update_private_message'];
	if(!$glup) $new_mess = $wpdb->get_row($wpdb->prepare("SELECT ID FROM ".RCL_PREF."private_message WHERE adressat_mess = '%d' AND status_mess = '0' OR adressat_mess = '%d' AND status_mess = '4'",$user_ID,$user_ID));
	else $new_mess = true;
	if($new_mess){
		$scr = false;
		if($rcl_options['view_user_lk_rcl']==1){
			$get = 'user';
			if($rcl_options['link_user_lk_rcl']!='') $get = $rcl_options['link_user_lk_rcl'];
			if(isset($_GET[$get])&&$user_ID==$_GET[$get]||$rcl_options['lk_page_rcl']!=$post->ID) $scr = true;
		}else{
			if(!is_author()||is_author($user_ID)) $scr = true;
		}
		if($scr) wp_enqueue_script( 'newmess_recall', plugins_url('js/new_mess.js', __FILE__) );
	}
}

if(function_exists('rcl_tab')){
    add_action('init','add_tab_message');
    function add_tab_message(){
        rcl_tab('privat',array('Rcl_Messages','recall_user_private_message'),__('Private chat','rcl'),
                                array(
                                    'public'=>1,
                                    'class'=>'fa-comments',
                                    'order'=>10,
                                    'path'=>__FILE__
                                ));
    }
}

class Rcl_Messages{

	public $room;
	public $user_lk;
	public $mess_id;
	public $ava_user_lk;
	public $ava_user_ID;

    public function __construct() {

		if (!is_admin()):
                        //if(function_exists('rcl_fileapi_scripts')) rcl_fileapi_scripts();
			add_action('wp_enqueue_scripts', array(&$this, 'output_style_scripts_private_mess'));
			add_action('init', array(&$this, 'delete_blacklist_user_recall_activate'));
			add_action('init', array(&$this, 'delete_private_message_recall'));
			add_action('init', array(&$this, 'old_status_message_recall_activate'));

			//add_filter('rcl_header_user',array(&$this, 'get_header_black_list_button'),5,2);

			add_filter('wp_head',array(&$this, 'add_global_update_new_mess_script'));
			add_filter('wp_footer',array(&$this, 'add_rcl_new_mess_conteiner'));
                        add_filter('access_chat_rcl',array(&$this, 'get_chek_ban_user'),10,2);
                        add_action('init',array(&$this, 'rcl_add_block_black_list_button'));
			//if(function_exists('add_shortcode'))
                            //add_shortcode('chat',array(&$this, 'get_shortcode_chat'));
		endif;

		if (is_admin()):
			add_filter('file_footer_scripts_rcl',array(&$this, 'get_footer_scripts_privat_rcl'));
			add_filter('admin_options_wprecall',array(&$this, 'get_admin_private_mess_page_content'));
			add_filter('file_scripts_rcl',array(&$this, 'get_scripts_message_rcl'));
		endif;

                add_filter('ajax_tabs_rcl',array(&$this, 'add_tab_privat_rcl'));
		add_action('wp_ajax_update_message_history_recall', array(&$this, 'update_message_history_recall'));
		add_action('days_garbage_file_rcl', array(&$this, 'garbage_file_rcl'));
		add_action('wp', array(&$this, 'activation_days_garbage_file_rcl'));
		add_action('wp_ajax_add_private_message_recall', array(&$this, 'add_private_message_recall'));
		add_action('wp_ajax_close_new_message_recall', array(&$this, 'close_new_message_recall'));
		add_action('wp_ajax_manage_blacklist_recall', array(&$this, 'manage_blacklist_recall'));
		add_action('wp_ajax_delete_history_private_recall', array(&$this, 'delete_history_private_recall'));
		add_action('wp_ajax_remove_ban_list_rcl', array(&$this, 'remove_ban_list_rcl'));
		add_action('wp_ajax_get_old_private_message_recall', array(&$this, 'get_old_private_message_recall'));
		add_action('wp_ajax_get_important_message_rcl', array(&$this, 'get_important_message_rcl'));
		add_action('wp_ajax_get_interval_contacts_rcl', array(&$this, 'get_interval_contacts_rcl'));
		add_action('wp_ajax_update_important_rcl', array(&$this, 'update_important_rcl'));
		add_action('wp_ajax_get_new_outside_message', array(&$this, 'get_new_outside_message'));
    }

	function add_rcl_new_mess_conteiner(){
		echo '<div id="rcl-new-mess"></div>';
	}

        function rcl_add_block_black_list_button(){
            rcl_block('header',array(&$this, 'get_header_black_list_button'),array('id'=>'bl-block','order'=>50,'public'=>1));
        }

	function add_global_update_new_mess_script(){
		global $rcl_options;
		$global_update = 1000*$rcl_options['global_update_private_message'];
		echo '<script type="text/javascript">var global_update_num_mess = '.$global_update.';</script>'."\n";
	}

	function output_style_scripts_private_mess(){
		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'sounds_recall', rcl_addon_url('js/ion.sound.min.js', __FILE__) );
	}


	function activation_days_garbage_file_rcl() {
		global $rcl_options;
		if(!isset($rcl_options['file_exchange'])||!$rcl_options['file_exchange']) return false;
		if ( !wp_next_scheduled( 'days_garbage_file_rcl' ) ) {
			$start_date = strtotime("2014-04-20 0:50:0");
			wp_schedule_event( $start_date, 'daily', 'days_garbage_file_rcl');
		}
	}

	function garbage_file_rcl(){
		global $wpdb,$rcl_options;

		$savetime = ($rcl_options['savetime_file'])? $rcl_options['savetime_file']: 7;

		$files = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".RCL_PREF."private_message WHERE status_mess = '4' AND status_mess = '5' AND time_mess < (NOW() - INTERVAL %d DAY)",$savetime));

		if(!$files) return false;

		$upload_dir = wp_upload_dir();
		foreach($files as $file){
			$name = explode('/',$file->content_mess);
			$cnt = count($name);
			$f_name = $name[--$cnt];
			$path_temp = $upload_dir['basedir'].'/temp-files/'.$f_name;
			unlink($path_temp);
		}

		$wpdb->query($wpdb->prepare("DELETE FROM ".RCL_PREF."private_message WHERE status_mess = '4' AND status_mess = '5' AND time_mess < (NOW() - INTERVAL %d DAY)",$savetime));

	}

	function get_admin_private_mess_page_content($content){
		global $rcl_options;

		if(!isset($rcl_options['file_exchange'])||!$rcl_options['file_exchange']){
                    wp_clear_scheduled_hook('days_garbage_file_rcl');
		}

                $opt = new Rcl_Options(__FILE__);

                $content .= $opt->options(
                    __('Settings private messages','rcl'),
                    $opt->option_block(
                        array(
                            $opt->title(__('Private messages','rcl')),
                            $opt->label(__('Displaying messages in the correspondence','rcl')),
                            $opt->option('select',array(
                                'name'=>'sort_mess',
                                'options'=>array(__('Top-Down','rcl'),__('Bottom-Up','rcl'))
                            )),

							$opt->label(__('Limit words message','rcl')),
                            $opt->option('number',array('name'=>'ms_limit_words')),
                            $opt->notice(__('the default is 400','rcl')),

                            $opt->label(__('The number of messages in the conversation','rcl')),
                            $opt->option('number',array('name'=>'max_private_message')),
                            $opt->notice(__('the default is 100 messages in the conversation (per correspondence user)','rcl')),

                            $opt->label(__('Pause between requests for new posts to show per page of correspondence with another user in seconds','rcl')),
                            $opt->option('number',array('name'=>'update_private_message')),

                            $opt->label(__('The number of requests you receive a new message page correspondence','rcl')),
                            $opt->option('number',array('name'=>'max_request_new_message')),
                            $opt->notice(__('Specify the maximum number of requests to retrieve a new message from a friend on the page of correspondence.'
                                    . 'If the number of requests exceeds the specified value, then the requests will stop. If nothing is specified or you specify zero, then there is no limit.','rcl')),

                            $opt->label(__('The pause between requests for new messages on all other pages of the website in seconds','rcl')),
                            $opt->option('number',array('name'=>'global_update_private_message')),
                            $opt->notice(__('If null, then the receipt of new messages only when the page loads, without subsequent requests','rcl')),

                            $opt->label(__('Lock requests if the person offline','rcl')),
                            $opt->option('select',array(
                                'name'=>'block_offrequest',
                                'options'=>array(__('Do not block','rcl'),__('To block requests','rcl'))
                            )),
                            $opt->notice(__('We mean a request to retrieve new messages from the user to the page which you are','rcl')),

                            $opt->label(__('File sharing','rcl')),
                            $opt->option('select',array(
                                'name'=>'file_exchange',
                                'parent'=>true,
                                'options'=>array(__('Prohibited','rcl'),__('Allowed','rcl'))
                            )),
                            $opt->child(
                                array(
                                    'name'=>'file_exchange',
                                    'value'=>1
                                ),
                                array(
                                    $opt->label(__('Maximum file size, Mb','rcl')),
                                    $opt->option('number',array('name'=>'file_exchange_weight')),
                                    $opt->notice(__('To restrict downloading of files this value in megabytes. By default, 2MB','rcl')),

                                    $opt->label(__('The retention time of the file','rcl')),
                                    $opt->option('number',array('name'=>'savetime_file')),
                                    $opt->notice(__('Specify the maximum number of unclaimed files in days. After this period, the file will be deleted. The default is 7 days.','rcl')),

                                    $opt->label(__('Limit unmatched files')),
                                    $opt->option('number',array('name'=>'file_limit')),
                                    $opt->notice(__('Specify the number of files missed by the recipients in which the user loses the possibility of further transfer of files. Protection from spam. Default-without any restrictions.','rcl'))
                                )
                            )
                        )
                    )
                );

		return $content;
	}

	function get_header_black_list_button($author_lk){
		global $user_ID;
		if(!$user_ID||$user_ID==$author_lk) return false;

		$header_lk = $this->get_blacklist_html($author_lk);

		return $header_lk;
	}

	function get_blacklist_html($author_lk){
		global $user_ID,$wpdb;

		$banlist = $wpdb->get_row($wpdb->prepare("SELECT * FROM ".RCL_PREF."black_list_user WHERE user = '%d' AND ban = '%d'",$user_ID,$author_lk));

		$title = ($banlist)? __('Unblock','rcl'): __('In the black list','rcl');
		$class = ($banlist)? 'remove_black_list': 'add_black_list';

		$button = rcl_get_button($title,'#',array('class'=>$class,'id'=>'manage-blacklist','icon'=>'fa-bug','attr'=>'data-contact='.$author_lk));

		return $button;
	}

	function mess_preg_replace_rcl($mess){
		//$mess = $this->oembed_filter( $mess );
		$mess = popuplinks(make_clickable($mess));
		if(function_exists('wp_oembed_get')){
			$links='';
			preg_match_all('/href="([^"]+)"/', $mess, $links);
			foreach( $links[1] as $link ){
				$m_lnk = wp_oembed_get($link,array('width'=>300,'height'=>250));
				if($m_lnk){
					$mess = str_replace('<a href="'.$link.'" rel="nofollow">'.$link.'</a>','',$mess);
					$mess .= $m_lnk;
				}
			}
		}
		//$mess = preg_replace("~(http|https|ftp|ftps)://(.*?)(\s|\n|[,.?!](\s|\n)|$)~", '<a target="_blank" href="$1://$2">$1://$2</a>$3', $mess);
		if(function_exists('convert_smilies')) $mess = str_replace( 'style="height: 1em; max-height: 1em;"', '', convert_smilies( $mess ) );
		return $mess;
	}

	function oembed_filter( $text ) {
		add_filter( 'embed_oembed_discover', '__return_false', 999 );
		remove_filter( 'embed_oembed_discover', '__return_false', 999 );
		return $text;
	}

        function add_tab_privat_rcl($array_tabs){
            $array_tabs['privat']=array('Rcl_Messages','recall_user_private_message');
            return $array_tabs;
        }


        function get_chek_ban_user($chat,$author_lk){
            global $user_ID,$wpdb;
            $ban = false;
            if($wpdb->get_var("show tables like '".RCL_PREF."black_list_user'"))
		$ban = $wpdb->get_row($wpdb->prepare("SELECT * FROM ".RCL_PREF."black_list_user WHERE user = '%d' AND ban = '%d'",$author_lk,$user_ID));
            if($ban){
		$chat = '<p class="b-upload__dnd">'.__('The user is forbidden to write to him','rcl').'</p>';
            }
            return $chat;
        }

	function recall_user_private_message($author_lk){
		global $user_ID,$rcl_options,$wpdb,$rcl_userlk_action;

                $last_action = rcl_get_useraction($rcl_userlk_action);
                if(!$last_action) $online = 1;
                else $online = 0;

		if(!$user_ID){
			return __('Sign in to start a conversation with the user.','rcl');
		}

		$privat_block = $this->get_private_message_content($author_lk, $online);
		if(isset($rcl_options['tab_newpage'])&&$rcl_options['tab_newpage']==2) $privat_block .= '<script type="text/javascript" src="'.RCL_UPLOAD_URL.'scripts/footer-scripts.js"></script>';

		return $privat_block;
	}

	function get_num_important(){
		global $wpdb,$user_ID;
		$st = $user_ID+100;
		$cnt = $wpdb->get_var($wpdb->prepare("SELECT COUNT(ID) FROM ".RCL_PREF."private_message
				WHERE
					author_mess = '$user_ID' AND adressat_mess = '%d' AND status_mess IN (7,%d)
				OR  author_mess = '%d' AND adressat_mess = '$user_ID' AND status_mess IN (7,%d)
				ORDER BY ID DESC",$this->user_lk,$st,$this->user_lk,$st));
		return $cnt;
	}

	function get_chat($online=0){

        rcl_resizable_scripts();

		global $user_ID,$rcl_options,$wpdb;

        $access = '';
		$access = apply_filters('access_chat_rcl',$access,$this->user_lk);

		if($this->room){
			$user_ID = $this->room;
			$user_lk = 0;
			$online=1;
		}else{
			$user_lk = $this->user_lk;
		}

		if(!$this->room) $where = $wpdb->prepare("WHERE author_mess = '%d' AND adressat_mess = '%d' OR author_mess = '%d' AND adressat_mess = '%d'", $user_ID,$this->user_lk,$this->user_lk,$user_ID);
		else $where = $wpdb->prepare("WHERE author_mess = '%d' OR adressat_mess = '%d'",$user_ID,$user_ID);

		$private_messages = $wpdb->get_results("SELECT * FROM ".RCL_PREF."private_message $where ORDER BY id DESC LIMIT 10");
		$num_mess = $wpdb->get_var("SELECT COUNT(ID) FROM ".RCL_PREF."private_message $where");

		if(!$this->room) $this->ava_user_lk = get_avatar($this->user_lk, 40);
		$this->ava_user_ID = get_avatar($user_ID, 40);

		$max_private_mess = $rcl_options['max_private_message'];
		if(!$max_private_mess) $max_private_mess = 100;
		if($num_mess>$max_private_mess&&!$this->room){
			$delete = $num_mess - $max_private_mess;
			$st = $user_ID+100;
			$us = $this->user_lk+100;
			$delete_num = $wpdb->query($wpdb->prepare("DELETE FROM ".RCL_PREF."private_message WHERE author_mess = '%d' AND adressat_mess = '%d' AND status_mess NOT IN (7,%d,%d) OR author_mess = '%d' AND adressat_mess = '%d' AND status_mess NOT IN (7,%d,%d) ORDER BY id ASC LIMIT %s",$user_ID,$this->user_lk,$st,$us,$this->user_lk,$user_ID,$st,$us,$st,$us));
			$num_mess = $num_mess - $delete_num;
		}

		$num=0;

		if(!$rcl_options['sort_mess']) krsort($private_messages);

		foreach((array)$private_messages as $message){
			$num++;
			$messlist = $this->get_private_message_block_rcl($messlist,$message);
			if($num==10) break;
		}

		if(!$access){
                    $textarea = '<div class="prmess">';
                    if($this->room) $textarea .= '<span title="'.__('Interlocutor','rcl').'" id="opponent"></span> '.rcl_get_button(__('All contacts','rcl'),'#',array('icon'=>'fa-book','id'=>'get-all-contacts'));
                    if($rcl_options['file_exchange']==1){
                            $textarea .= '<div id="upload-box-message" class="fa fa-paperclip recall-button rcl-upload-button">
											<span>Выбрать файл</span>
											<span class="progress-bar"></span>
											<input name="filedata" id="upload-private-message" type="file">
										</div>';
                    }
                    $textarea .='<span class="fa fa-exclamation-triangle notice">'.__('<b>Enter</b> - line break, <b>Ctrl+Enter</b> - send','rcl').'</span>';
                    $textarea .= '<textarea name="content_mess" id="content_mess" rows="3"></textarea>';
                    $textarea .= '
                    <input type="hidden" name="adressat_mess" id="adressat_mess" value="'.$user_lk.'">
                    <input type="hidden" name="online" id="online" value="'.$online.'">';

                    $textarea .= rcl_get_smiles('content_mess');

					$words = (isset($rcl_options['ms_limit_words'])&&$rcl_options['ms_limit_words'])? $rcl_options['ms_limit_words']: 400;

                    $textarea .= '<div class="fa fa-edit" id="count-word">'.$words.'</div>';

                    $textarea .= '<div class="private-buttons">
                            '.rcl_get_button(__('Send','rcl'),'#',array('icon'=>'fa-mail-forward','class'=>'addmess alignright','attr'=>false,'id'=>false));
                            if($this->get_num_important()>0) $textarea .= rcl_get_button(__('Important messages','rcl'),'#',array('icon'=>'fa-star','class'=>'important alignleft','id'=>'get-important-rcl'));
                    $textarea .= '</div>'
                            . '<div id="resize"></div>'
                            . '</div>';

                    if(!$private_messages) $newblock = '<div class="new_mess" align="center">'.__('Here will display correspondence history','rcl').'</div>';
                    else $newblock = '<div class="new_mess"></div>';

                    if($num_mess>10) $getold = '<div class="old_mess_block"><a href="#" class="old_message">'.__('Show older messages','rcl').'</a></div>';

                    if(!$rcl_options['sort_mess']){
                        $messlist = $getold.$messlist;
                        $messlist .= $newblock;
                        $privat_block = '<div id="resize-content"><div id="message-list">'.$messlist.'</div></div>';
                        $privat_block .= $textarea;
                        $privat_block .= "<script>jQuery(document).ready(function() {

                                var div = jQuery('#resize-content');
                                div.scrollTop( div.get(0).scrollHeight );

                                var chatHeight = 'chatHeight';
                                var chatNow = jQuery.cookie(chatHeight);
                                if(chatNow != null)
                                    jQuery('#resize-content,#resize').css('height', chatNow + 'px');
                                jQuery('#resize').resizable( {
                                    alsoResize: '#resize-content',
                                    stop: function(event, ui) {
                                        chatNow = jQuery('#resize-content').height();
                                        jQuery.cookie(chatHeight, chatNow);
                                    }
                                });
                            });"
                            . "</script>";
                    }else{
                        $privat_block = $textarea;
                        $messlist = $newblock.$messlist;
                        $messlist .= $getold;
                        $privat_block .= '<div id="message-list">'.$messlist.'</div>';
                    }


		} else {
                    $privat_block .= $access;
		}



		$privat_block .= "<script type='text/javascript'>var old_num_mess = ".$num_mess."; var block_mess = 1; var user_old_mess = ".$user_lk.";</script>";

		if(($rcl_options['block_offrequest']==1&&$online==0)||$access) return $privat_block;

		if(!$rcl_options['update_private_message']) $rcl_options['update_private_message'] = 10;
		$sec_update = 1000*$rcl_options['update_private_message'];
		$privat_block .= "<script type='text/javascript'>

		var update_mass_ID; var max_sec_update_rcl=0;

		function update_mass(){";
			if($rcl_options['max_request_new_message']>0)$privat_block .= "
			max_sec_update_rcl++; if(max_sec_update_rcl>".$rcl_options['max_request_new_message'].") return false;
			";
			$privat_block .= "jQuery(function(){
					var dataString_new_mess = 'action=update_message_history_recall&user='+user_old_mess;
					jQuery.ajax({
					type: 'POST',
					data: dataString_new_mess,
					dataType: 'json',
					url: wpurl+'wp-admin/admin-ajax.php',
					success: function(data){
						if(data['recall']==100){
							jQuery('.new_mess').replaceWith(data['message_block']);";
                                                        if(!$rcl_options['sort_mess']) $privat_block .= "var div = jQuery('#resize-content');
                                                                                        div.scrollTop( div.get(0).scrollHeight );";
							$privat_block .= "jQuery.ionSound.play('water_droplet');
							max_sec_update_rcl = 0;
						}
						if(data['read']==200){
							jQuery('.mess_status').remove();
						}
					}
					});
					return false;
				});
		}
		setInterval(function(){update_mass();},".$sec_update.");
		window.onload=function(){update_mass();}
		</script>";

		return $privat_block;
	}

	function get_private_message_content($user_id, $online, $room=false){

		global $user_ID,$wpdb;

		$this->user_lk = $user_id;

		if($user_ID==$this->user_lk){

			$privat_block = '<div class="correspond">';

			$contacts = $wpdb->get_col($wpdb->prepare("SELECT contact FROM ".RCL_PREF."private_contacts WHERE user = '%d' AND status = '1'",$user_ID));

            $contacts = apply_filters('rcl_chat_contacts',$contacts);

			if($contacts){

				$days = 7;
				$ban = $wpdb->get_var($wpdb->prepare("SELECT ID FROM ".RCL_PREF."black_list_user WHERE user = '%d'",$user_ID));

				$privat_block .= '<div class="buttons-navi">
				<a data="'.$days.'" class="sec_block_button active" href="#"><i class="fa fa-clock-o"></i>'.$days.' '.__('days','rcl').'</a>
				<a data="30" class="sec_block_button" href="#"><i class="fa fa-clock-o"></i>'.__('month','rcl').'</a>
				<a data="0" class="sec_block_button" href="#"><i class="fa fa-clock-o"></i>'.__('all the time','rcl').'</a>';
				if(isset($ban)) $privat_block .= '<a data="-1" class="sec_block_button" href="#"><i class="fa fa-bug"></i>'.__('Blacklist','rcl').'</a>';
				$privat_block .= '<a data="important" class="sec_block_button" href="#"><i class="fa fa-clock-o"></i>'.__('Important','rcl').'</a>';
				$privat_block .= '</div>';

				$privat_block .= '<div id="contact-lists">'.$this->get_loop_contacts_rcl($contacts,$days).'</div>';

			} else {
				$privat_block .= '<div class="single_correspond"><p>'.__('You havent been in conversation with','rcl').'</p></div>';
			}
			$privat_block .= '</div>';
		} else {

			$privat_block = $this->get_chat($online);

		}
		return $privat_block;

	}

	function get_interval_contacts_rcl(){
		global $wpdb,$user_ID;

		if(!$user_ID) exit;

		$days = esc_sql($_POST['days']);

		if($days=='important'){
			$privat_block = $this->get_all_important_mess();
		}else{
			if($days<0){
				$contacts = $wpdb->get_col($wpdb->prepare("SELECT ban FROM ".RCL_PREF."black_list_user WHERE user = '%d'",$user_ID));
			}else{
				$contacts = $wpdb->get_col($wpdb->prepare("SELECT contact FROM ".RCL_PREF."private_contacts WHERE user = '%d' AND status = '1'",$user_ID));
                                $contacts = apply_filters('rcl_chat_contacts',$contacts);
			}

			if(!$contacts) $privat_block = '<h3>'.__('Contacts not found!','rcl').'</h3>';
			else $privat_block = $this->get_loop_contacts_rcl($contacts,$days);
		}
		$log['message_block'] = $privat_block;
		$log['recall']=100;

		echo json_encode($log);
		exit;

	}

	function get_loop_contacts_rcl($contacts,$days){
		global $wpdb,$user_ID;

		$interval = $days*24*3600;
		$sql_int = '';
                $contact_list = array();

		if($days>0) $sql_int = "AND time_mess > (NOW() - INTERVAL $interval SECOND)";

		if(!$contacts) return '<h3>'.__('Contacts not found!','rcl').'</h3>';

		$rcl_action_users = $wpdb->get_results($wpdb->prepare("SELECT user,time_action FROM ".RCL_PREF."user_action WHERE user IN (".rcl_format_in($contacts).")",$contacts));

		if($days>=0){
			$cntctslist = implode(',',$contacts);
			$su_list  = $wpdb->get_results("
			SELECT author_mess,time_mess,adressat_mess,status_mess FROM (
			SELECT * FROM ".RCL_PREF."private_message WHERE adressat_mess IN ($cntctslist) AND author_mess = '$user_ID' $sql_int
			OR author_mess IN ($cntctslist) AND adressat_mess = '$user_ID' $sql_int ORDER BY time_mess DESC
			) TBL GROUP BY author_mess,adressat_mess");

			if($su_list){

				foreach((array)$su_list as $s){$list[] = (array)$s;}
				$list = rcl_multisort_array((array)$list, 'time_mess', SORT_ASC);
				foreach((array)$list as $l){
						if($l['author_mess']!=$user_ID) $s_contact=$l['author_mess'];
						if($l['adressat_mess']!=$user_ID) $s_contact=$l['adressat_mess'];
						$contact_list[$s_contact]['time'] = $l['time_mess'];
						$contact_list[$s_contact]['contact'] = $s_contact;
						$contact_list[$s_contact]['status'] = $l['status_mess'];
				}
				$contact_list = rcl_multisort_array((array)$contact_list, 'time', SORT_DESC);

			}else{
				$contacts = false;
				$contacts = apply_filters('rcl_chat_contacts',$contacts);
				if($contacts){
					foreach($contacts as $c){
						$contact_list[]['contact'] = $c;
					}
				}
			}

		}else{

			foreach((array)$contacts as $c){
				$contact_list[]['contact'] = $c;
			}

		}

		$name_users = $wpdb->get_results($wpdb->prepare("SELECT ID,display_name FROM $wpdb->users WHERE ID IN (".rcl_format_in($contacts).")",$contacts));

		foreach((array)$name_users as $name){
			$names[$name->ID] = $name->display_name;
		}

		$privat_block = '';
                if($contact_list){
                    foreach($contact_list as $data){

                            if(!$names[$data['contact']]) continue;

                            foreach((array)$rcl_action_users as $action){
                                    if($action->user==$data['contact']){$time_action = $action->time_action; break;}
                            }
                            $last_action = rcl_get_useraction($time_action);
                            $privat_block .= '<div class="single_correspond history-'.$data['contact'];
                            if($data['status']==0) $privat_block .= ' redline';
                            $privat_block .= '">';
                            $privat_block .= '<div class="floatright">';
                            if(!$last_action)
                                    $privat_block .= '<div class="status_author_mess online"><i class="fa fa-circle"></i></div>';
                            else
                                    $privat_block .= '<div class="status_author_mess offline"><i class="fa fa-circle"></i></div>';

                            $redirect_url = rcl_format_url(get_author_posts_url($data['contact']),'privat');

                            $privat_block .= '<span user_id="'.$data['contact'].'" class="author-avatar"><a href="'.$redirect_url.'">'.get_avatar($data['contact'], 40).'</a></span><a href="#" class="recall-button ';

                            if($days>0) $privat_block .= 'del_history';
                            else $privat_block .= 'remove_black_list';

                            $privat_block .='" data-contact="'.$data['contact'].'"><i class="fa fa-remove"></i></a>
                            </div>
                            <p><a href="'.$redirect_url.'">'.$names[$data['contact']].'</a>';
                            if(isset($data['time'])) $privat_block .='<br/><small>'.__('Last message','rcl').': '.$data['time'].'</small>';
                            else $privat_block .='<br/><small>'.__('The chat history is missing','rcl').'</small>';
                            $privat_block .='</p></div>';
                    }
                }
		if(!$privat_block) $privat_block = '<h3>'.__('Contacts not found!','rcl').'</h3>';
		return $privat_block;
	}

	function get_delete_private_mess_rcl($message){
		global $user_ID;
		if(!function_exists('get_bloginfo')) return false;
                $button = false;
		if($message->status_mess==0&&$message->author_mess==$user_ID){
			$button = '<a title="'.__('Delete?','rcl').'" class="fa fa-trash mess_status" href="'.wp_nonce_url( get_bloginfo('wpurl').'/?id_mess='.$message->ID.'&user_id='.$this->user_lk.'&delete_private_message_recall=true', $user_ID ).'"></a>';
		}
		return $button;
	}

	function get_private_message_block_rcl($privat_block,$message){
	global $user_ID,$wpdb;

		if($this->room){
			if($message->author_mess!=$user_ID) $this->user_lk = $message->author_mess;
			if($message->adressat_mess!=$user_ID) $this->user_lk = $message->adressat_mess;
			$this->ava_user_lk = get_avatar($this->user_lk, 40);
		}

		$this->mess_id = $message->ID;

		$privat_block .= $this->get_delete_private_mess_rcl($message);

		$privat_block = $this->get_content_private_message_rcl($message,$privat_block);

		if($message->author_mess==$this->user_lk){
			if($message->status_mess==0) $new_st = 1;
			if($message->status_mess==4) $new_st = 5;
			if(isset($new_st)&&($new_st==1||$new_st==5)) $wpdb->update( RCL_PREF.'private_message',array( 'status_mess' => $new_st ),array( 'ID' => $message->ID ));
		}

		return $privat_block;
	}

	function get_content_private_message_rcl($message,$privat_block){

		if($message->author_mess == $this->user_lk){
			$avatar_mess = $this->ava_user_lk;
			$class="you";
			if($message->status_mess==6) $class="file";
		}else{
			$avatar_mess = $this->ava_user_ID;
			$class="im";
			if($message->status_mess==4||$message->status_mess==5) $class="file";
			if($message->status_mess==6){
				$avatar_mess = $this->ava_user_lk;
				$class="you";
			}
		}

		$content_message = $this->mess_preg_replace_rcl($message->content_mess);

		$content_message = $this->get_url_file_message($message,$content_message);

		$content_message = $this->str_nl2br_rcl($content_message);

		if($class=='you') $uslk = 'user_id="'.$this->user_lk.'"';
		else $uslk = false;

		$privat_block .= '<div id="message-'.$this->mess_id.'" class="public-post message-block '.$class.'">';
		if($class!="file")$privat_block .= '<div '.$uslk.' class="author-avatar">'.$avatar_mess.'</div>';
		$privat_block .= '<div class="content-mess">';
		if($class!="file")$privat_block .= '<span class="privat-balloon"></span>';
		$privat_block .= '<div class="balloon-message">'
                        . '<p class="time-message"><span class="time">'.$message->time_mess.'</span></p>'
                        . '<p>'.$content_message.'</p></div>
		</div>';

		$st = $message->status_mess;
		if($st!=0&&$st!=4&&$st!=5&&$st!=6){
			$cl = $this->class_important($message->status_mess);
			$ttl = ($cl)?  __('Uncheck','rcl'): __('Mark as important','rcl');
			$privat_block .= '<a href="#" idmess="'.$this->mess_id.'" title="'.$ttl.'" class="important '.$cl.'"></a>';
		}
		$privat_block .= '</div>';

		return 	$privat_block;
	}

	function class_important($status){
		global $user_ID;
		if($status==$user_ID + 100||$status==7) return 'active';
	}

	function update_important_rcl(){
		global $wpdb;
		global $user_ID;

		$id_mess = intval($_POST['id_mess']);
		if(!$user_ID||!$id_mess)return false;

		$mess = $wpdb->get_row($wpdb->prepare("SELECT * FROM ".RCL_PREF."private_message WHERE ID = '%d'",$id_mess));

		if($mess->author_mess==$user_ID) $user = $mess->adressat_mess;
		else $user = $mess->author_mess;

		if($mess->status_mess==1){
			$status = $user_ID + 100;
			$log['res']=100;
		}else if($mess->status_mess==7){
			$status = $user + 100;
			$log['res']=200;
		}else if($mess->status_mess==$user + 100){
			$status = 7;
			$log['res']=100;
		}else if($mess->status_mess==$user_ID + 100){
			$status = 1;
			$log['res']=200;
		}else{
			return false;
		}

		$wpdb->update( RCL_PREF.'private_message',
			array( 'status_mess' => $status ),
			array( 'ID' => $id_mess)
		);

		echo json_encode($log);
		exit;
	}

	function get_all_important_mess(){
		global $user_ID;
		global $wpdb;

		$st = $user_ID+100;
		$private_messages = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".RCL_PREF."private_message WHERE author_mess = '%d' AND status_mess IN (7,%d) OR adressat_mess = '%d' AND status_mess IN (7,%d) ORDER BY ID DESC",$user_ID,$st,$user_ID,$st));
		$message_block = '';
		foreach((array)$private_messages as $message){
                    if($message->author_mess!=$user_ID) $this->user_lk = $message->author_mess;
                    if($message->adressat_mess!=$user_ID) $this->user_lk = $message->adressat_mess;
                    $this->ava_user_lk = '<a href="'.get_author_posts_url($this->user_lk).'">'.get_avatar($this->user_lk, 40).'</a>';
                    $this->ava_user_ID = get_avatar($user_ID, 40);
                    $message_block = $this->get_private_message_block_rcl($message_block,(object)$message);
		}

		if(!$message_block) $message_block = '<h3>'.__('No posts found!','rcl').'</h3>';

		$log['message_block'] = $message_block;
		$log['recall']=100;

		echo json_encode($log);
		exit;
	}

	/*************************************************
	Получаем помеченные сообщения
	*************************************************/
	function get_important_message_rcl(){
		global $user_ID,$wpdb,$rcl_options;

		$this->user_lk = intval($_POST['user']);
		$type = intval($_POST['type']);

		if($user_ID){

			$num_mess = 0;

			if($type==1){
				$where = $wpdb->prepare("author_mess = '%d' AND adressat_mess = '%d' OR author_mess = '%d' AND adressat_mess = '%d'",$user_ID,$this->user_lk,$this->user_lk,$user_ID);
				$private_messages = $wpdb->get_results("SELECT * FROM ".RCL_PREF."private_message WHERE $where ORDER BY id DESC LIMIT 10");
				$num_mess = $wpdb->get_var("SELECT COUNT(ID) FROM ".RCL_PREF."private_message WHERE $where");
			}else{
				$st = $user_ID+100;
				$private_messages = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".RCL_PREF."private_message
				WHERE
					author_mess = '%d' AND adressat_mess = '%d' AND status_mess IN (7,%d)
				OR  author_mess = '%d' AND adressat_mess = '%d' AND status_mess IN (7,%d)
				ORDER BY ID DESC",$user_ID,$this->user_lk,$st,$this->user_lk,$user_ID,$st));
			}

                        if($num_mess>10) $getold = '<div class="old_mess_block"><a href="#" class="old_message">'.__('Show more recent messages','rcl').'</a></div>';
                        $message_block = '';
                        $newmess = '<div class="new_mess"></div>';

                        if(!$rcl_options['sort_mess']) krsort($private_messages);
			foreach((array)$private_messages as $message){
				//$content_message = $this->mess_preg_replace_rcl($message->content_mess);
				$this->ava_user_lk = get_avatar($message->author_mess, 40);
				$this->ava_user_ID = $this->ava_user_lk;
				$message_block = $this->get_private_message_block_rcl($message_block,(object)$message);
			}

                        if(!$rcl_options['sort_mess']){
                            $message_block = $getold.$message_block.$newmess;
                        }else{
                            $message_block = $newmess.$message_block;
                            $message_block .= $getold;
                        }

			$log['recall']=100;
			$log['content']=$message_block;
		}
		echo json_encode($log);
		exit;
	}

	//Отмечаем входящее сообщение как прочтенное
	function old_status_message_recall(){
		global $wpdb;
		global $user_ID;

		if(!$user_ID)return false;

		//$id_mess = $_POST['id_mess'];
		$author_mess = intval($_POST['author_mess']);

		$result = $wpdb->update( RCL_PREF.'private_message',
			array( 'status_mess' => 1 ),
			array( 'author_mess' => "$author_mess", 'adressat_mess' => $user_ID, 'status_mess'=>0)
		);

		wp_redirect( rcl_format_url(get_author_posts_url($author_mess),'privat')); exit;
	}

	function old_status_message_recall_activate ( ) {
		if ( isset( $_POST['old_status_message_recall'] ) ) add_action( 'wp', array(&$this, 'old_status_message_recall'));
	}


	//Удаление непрочтенного сообщения из переписки
	function delete_private_message_recall(){
	global $wpdb,$user_ID;
		if ( !isset( $_GET['delete_private_message_recall'] ) ) return false;
		if( !wp_verify_nonce( $_GET['_wpnonce'], $user_ID ) ) wp_die('Error');
		$user_id = $_GET['user_id']; $id_mess = $_GET['id_mess'];
		$result = $wpdb->query($wpdb->prepare("DELETE FROM ".RCL_PREF."private_message WHERE ID = '%d'",$id_mess));
		if (!$result) wp_die('Error');
		wp_redirect( rcl_format_url(get_author_posts_url($user_id),'privat') );  exit;
	}


	//Удаляем из черного списка
	function delete_blacklist_user_recall(){
		global $wpdb;
		global $user_ID;
		if($user_ID){
			//$idbanlist = $_POST['idbanlist'];
			$ban_user = intval($_POST['ban_user']);
			$result = $wpdb->query($wpdb->prepare("DELETE FROM ".RCL_PREF."black_list_user WHERE user = '%d' AND ban = '%d'",$user_ID,$ban_user));

			do_action('rcl_delete_user_blacklist',$ban_user,$user_ID);

			if ($result) {
				wp_redirect( get_author_posts_url($ban_user) );  exit;
			} else {
			  wp_die('Error');
			}
		}
	}

	function delete_blacklist_user_recall_activate ( ) {
	  if ( isset( $_POST['remove_black_list'] ) ) {
		add_action( 'wp', array(&$this, 'delete_blacklist_user_recall'));
	  }
	}

	/*************************************************
	Добавление личного сообщения
	*************************************************/
	function add_private_message_recall(){
		global $user_ID,$wpdb,$rcl_options;

		if(!$user_ID) exit;

			$_POST = stripslashes_deep( $_POST );
			$this->user_lk = intval($_POST['adressat_mess']);
			$content_mess = esc_textarea($_POST['content_mess']);

			$online = 0;
			$status_mess = 0;
			$time = current_time('mysql');

			$rcl_action_users = $wpdb->get_row($wpdb->prepare("SELECT * FROM ".RCL_PREF."user_action WHERE user = '%d'",$this->user_lk));
			$last_action = rcl_get_useraction($rcl_action_users->time_action);
			if(!$last_action) $online = 1;

			$result = rcl_add_message(array('addressat'=>$this->user_lk,'content'=>$content_mess));

			if ($result) {

				rcl_update_timeaction_user();

				if($_POST['widget']!='undefined'){
					$wpdb->update(
						RCL_PREF.'private_message',
						array( 'status_mess' => 1 ),
						array( 'ID' => intval($_POST['widget']) )
					);
					$message_block = '<p class="success-mess">'.__('Your message has been sent!','rcl').'</p>';
					$log['recall']=200;
				}else{

					$id_mess = $wpdb->get_var("SELECT ID FROM ".RCL_PREF."private_message WHERE author_mess = '$user_ID' AND time_mess = '$time'");
                                        $message_block = '';
					$message = array('ID'=>$id_mess,'content_mess'=>$content_mess,'status_mess'=>0,'author_mess'=>$user_ID,'time_mess'=>$time);
					$this->ava_user_lk = '';
					$this->ava_user_ID = get_avatar($user_ID, 40);
					$message_block = $this->get_private_message_block_rcl($message_block,(object)$message);

                                        $newmess = '<div class="new_mess"></div>';

                                        if(!$rcl_options['sort_mess']){
                                            $message_block .= $newmess;
                                        }else{
                                            $message_block = $newmess.$message_block;
                                        }

					$log['recall']=100;
				}

				$log['message_block']=$message_block;

			}else{
				$log['recall']=120;
			}

		echo json_encode($log);
		exit;
	}

	/*************************************************
	Удаление истории переписки
	*************************************************/
	function delete_history_private_recall(){
		global $wpdb,$user_ID;
		if($user_ID){
			$this->user_lk = intval($_POST['id_user']);
			$status = $wpdb->get_var($wpdb->prepare("SELECT status FROM ".RCL_PREF."private_contacts WHERE user='%d' AND contact='%d'",$this->user_lk,$user_ID));
			if($status==3){
				//Если собеседник тоже удалил пользователя из контактов, то удаляем всю переписку между ними, тк она им не нужна
				$wpdb->query($wpdb->prepare("DELETE FROM ".RCL_PREF."private_contacts WHERE user='%d' AND contact='%d'
				OR user='%d' AND contact='%d'",$user_ID,$this->user_lk,$this->user_lk,$user_ID));
				$wpdb->query($wpdb->prepare("DELETE FROM ".RCL_PREF."private_message WHERE author_mess='%d' AND adressat_mess='%d'
				OR author_mess='%d' AND adressat_mess='%d'",$user_ID,$this->user_lk,$this->user_lk,$user_ID));
			}else{
				$wpdb->update(
					RCL_PREF.'private_contacts',
						array( 'status' => 3 ),
						array( 'user' => "$user_ID", 'contact' => "$this->user_lk" )
					);
			}
			$log['id_user']=$this->user_lk;
			$log['otvet']=100;
		} else{
			$log['otvet']=1;
		}
		echo json_encode($log);
		exit;
	}

	/*************************************************
	Удаление из черного списка
	*************************************************/
	function remove_ban_list_rcl(){
		global $wpdb,$user_ID;
		if($user_ID){
			$this->user_lk = intval($_POST['id_user']);
			$id_ban = $wpdb->get_var($wpdb->prepare("SELECT ID FROM ".RCL_PREF."black_list_user WHERE user='%d' AND ban='%d'",$user_ID,$this->user_lk));
			if($id_ban){
				$wpdb->query($wpdb->prepare("DELETE FROM ".RCL_PREF."black_list_user WHERE ID='%d'",$id_ban));
			}
			$log['id_user']=$this->user_lk;
			$log['otvet']=100;
		} else{
			$log['otvet']=1;
		}
		echo json_encode($log);
		exit;
	}

	/*************************************************
	Отмечаем сообщение как прочтенное
	*************************************************/
	function close_new_message_recall(){
		global $wpdb;
		global $user_ID;

		if($user_ID){
			$wpdb->update(
				RCL_PREF.'private_message',
				array( 'status_mess' => 1 ),
				array( 'ID' => intval($_POST['id_mess']) )
			);
			$log['message_block'] = '<p class="success-mess">'.__('The message is marked as read','rcl').'</p>';
			$log['recall']=100;
		}
		echo json_encode($log);
		exit;
	}

	/*************************************************
	Черный список
	*************************************************/
	function manage_blacklist_recall(){
		global $wpdb,$user_ID;
		if(!$user_ID) exit;

		$this->user_lk = intval($_POST['user_id']);

		$ban_id = $wpdb->get_var($wpdb->prepare("SELECT ID FROM ".RCL_PREF."black_list_user WHERE user = '%d' AND ban = '%d'",$user_ID,$this->user_lk));

		if($ban_id){
			$result = $wpdb->query($wpdb->prepare("DELETE FROM ".RCL_PREF."black_list_user WHERE ID='%d'",$ban_id));

			do_action('remove_user_blacklist',$this->user_lk);
		}else{
			$result = $wpdb->insert(RCL_PREF.'black_list_user',
				array( 'user' => "$user_ID", 'ban' => "$this->user_lk" )
			);

			do_action('add_user_blacklist',$this->user_lk);
		}


		if ($result){
			$log['content'] = $this->get_blacklist_html($this->user_lk);
			$log['otvet']=100;
		}else{
			$log['otvet']=1;
		}

		echo json_encode($log);
		exit;
	}

	/*************************************************
	Обновление истории переписки на странице собеседника
	*************************************************/
	function update_message_history_recall(){
		global $user_ID,$wpdb,$rcl_options;

		$this->user_lk = intval($_POST['user']);

		if($user_ID){

			if(!$this->user_lk){
				$where = $wpdb->prepare("WHERE adressat_mess = '%d' AND status_mess = '0' OR adressat_mess = '%d' AND status_mess = '4'",$user_ID,$user_ID);
			}else{
				$where = $wpdb->prepare("WHERE author_mess = '%d' AND adressat_mess = '%d' AND status_mess = '0' OR author_mess = '%d' AND adressat_mess = '%d' AND status_mess = '4'",$this->user_lk,$user_ID,$this->user_lk,$user_ID);
			}

			$private_messages = $wpdb->get_results("SELECT * FROM ".RCL_PREF."private_message $where ORDER BY id DESC");

			if($private_messages){

			$message_block = '';
			foreach((array)$private_messages as $message){

					if(!$this->user_lk){
							if($message->author_mess!=$user_ID) $this->user_lk = $message->author_mess;
							else $this->user_lk = $message->adressat_mess;
					}

					//$content_message = $this->mess_preg_replace_rcl($message->content_mess);
					//$content_message = $this->str_nl2br_rcl($content_mess);
					$content_mess = apply_filters('rcl_get_new_private_message',$content_mess,$this->user_lk,$user_ID);
					$message_block .= $this->get_delete_private_mess_rcl($message);
					$this->ava_user_lk = get_avatar($message->author_mess, 40);
					$this->ava_user_ID = $this->ava_user_lk;
					$message_block = $this->get_content_private_message_rcl((object)$message,$message_block);

					if($message->author_mess==$this->user_lk){
							if($message->status_mess==0) $new_st = 1;
							if($message->status_mess==4) $new_st = 5;
							if($new_st==1||$new_st==5) $wpdb->update( RCL_PREF.'private_message',array( 'status_mess' => $new_st ),array( 'ID' => $message->ID )	);
							$log['delete']=200;
					}

			}

			$newmess = '<div class="new_mess"></div>';

			if(!$rcl_options['sort_mess']){
				$message_block .= $newmess;
			}else{
				$message_block = $newmess.$message_block;
			}

			$log['recall']=100;
			$log['message_block']=$message_block;

			}else{
				$log['recall']=0;
			}

			/*проверяем прочитаны ли отправленные собеседнику сообщения*/
			$no_read_mess = $wpdb->get_var($wpdb->prepare("SELECT COUNT(ID) FROM ".RCL_PREF."private_message
			WHERE author_mess = '%d' AND adressat_mess = '%d' AND status_mess = '0'
			OR author_mess = '%d' AND adressat_mess = '%d' AND status_mess = '4'",$user_ID,$this->user_lk,$user_ID,$this->user_lk));
			if($no_read_mess==0){
				$log['read']=200;
			}

		}
		echo json_encode($log);
		exit;
	}

	/*************************************************
	Запрос на получение новых сообщений на сайте
	*************************************************/
	function get_new_outside_message(){

		global $user_ID,$wpdb,$rcl_options;

		if(!$user_ID) return false;

		$mess = $wpdb->get_row($wpdb->prepare("SELECT * FROM ".RCL_PREF."private_message WHERE adressat_mess = '%d' AND status_mess ='0'",$user_ID));

		if(!$mess){
			$log['recall']=0;
			echo json_encode($log);
			exit;
		}

                $rcl_action_users = $wpdb->get_row($wpdb->prepare("SELECT * FROM ".RCL_PREF."user_action WHERE user = '%d'",$mess->author_mess));
		$last_action = rcl_get_useraction($rcl_action_users->time_action);
                $class = (!$last_action)?'online':'offline';
                $online = (!$last_action)?1:0;

				$words = (isset($rcl_options['ms_limit_words'])&&$rcl_options['ms_limit_words'])? $rcl_options['ms_limit_words']: 400;

		$message_block .= '<div id="privatemess">'
                            .'<div id="'.$mess->ID.'" class="close-mess-window">'
                            . '<i class="fa fa-times-circle"></i>'
                        . '</div>'
			.'<p class="title-new-mess">'.__('You a new message!','rcl').'</p>'

                        . '<div class="private-message">'

                            . '<div class="content-notice">'
                                . '<div class="notice-ava">'
                                    . '<div class="mini_status_user '.$class.'">'
                                        . '<i class="fa fa-circle"></i>'
                                    . '</div>'
                                    .get_avatar($mess->author_mess,60)
                                .'</div>
                                <p class="name-author-mess">
                                    Отправитель: '.get_the_author_meta('display_name', $mess->author_mess).'
                                </p>
                                <p class="content-mess">'.$mess->content_mess.'</p>

                                <div class="prmess">
                                    <textarea name="content_mess" id="minicontent_mess" rows="3" style="width:98%;padding:5px;"></textarea>
                                    <div id="minicount-word">'.$words.'</div>

                                    <input type="button" name="addmess" class="miniaddmess recall-button" value="Отправить">
                                    <input type="hidden" name="adressat_mess" id="miniadressat_mess" value="'.$mess->author_mess.'">
                                    <input type="hidden" name="online" id="minionline" value="'.$online.'">
                                    <input type="hidden" name="widget-mess" id="widget-mess" value="'.$mess->ID.'">
                                </div>
                            </div>


                            <form class="form_new_message" action="" method="post">
                                <input type="hidden" name="id_mess" value="'.$mess->ID.'">
                                <input type="hidden" name="author_mess" value="'.$mess->author_mess.'">
                                <input class="reading_mess  recall-button" type="submit" name="old_status_message_recall" value="'.__('Go to the correspondence','rcl').'">
                            </form>
                            <input type="button" name="view-form" class="recall-button view-form" value="'.__('Reply','rcl').'">

                            </div>
                        </div>';

		$log['recall']=100;
		$log['message_block']=$message_block;

		echo json_encode($log);
		exit;
	}

	/*************************************************
	Получаем старые сообщения из истории переписки
	*************************************************/
	function get_old_private_message_recall(){
		global $user_ID,$wpdb,$rcl_options;

		$old_num_mess = intval($_POST['old_num_mess']);
		$this->user_lk = intval($_POST['user']);
		$block_mess = intval($_POST['block_mess']);
		$post_mess = 10;
		$start_limit = ($block_mess-1)*$post_mess;
		$mess_show = $block_mess*$post_mess;

		if($this->user_lk) $where = $wpdb->prepare("WHERE author_mess = '%d' AND adressat_mess = '%d' OR author_mess = '%d' AND adressat_mess = '%d'", $user_ID,$this->user_lk,$this->user_lk,$user_ID);
		else $where = $wpdb->prepare("WHERE author_mess = '%d' OR adressat_mess = '%d'",$user_ID,$user_ID);

		$private_messages = $wpdb->get_results("SELECT * FROM ".RCL_PREF."private_message $where ORDER BY id DESC LIMIT $start_limit,10");
		$num_mess = $wpdb->get_var("SELECT COUNT(ID) FROM ".RCL_PREF."private_message $where");



		if($user_ID){


			if(!$this->user_lk) $user_lk = 0;

                        if(!$rcl_options['sort_mess'])krsort($private_messages);

			foreach((array)$private_messages as $message){

                            if(!$user_lk){
                                    if($message->author_mess!=$user_ID) $this->user_lk = $message->author_mess;
                                    else $this->user_lk = $message->adressat_mess;
                            }

                            $this->ava_user_lk = get_avatar($message->author_mess, 40);
                            $this->ava_user_ID = $this->ava_user_lk;
                            $message_block = $this->get_private_message_block_rcl($message_block,(object)$message);

			}

                        if($old_num_mess>$mess_show) $getold = '<div class="old_mess_block"><a href="#" class="old_message">'.__('Show more recent posts','rcl').'</a></div>';

                        if(!$rcl_options['sort_mess']) $message_block = $getold.$message_block;
                        else $message_block .= $getold;

			$log['recall']=100;
			$log['message_block']=$message_block;
			$log['num_mess_now']=$num_mess;
		}
		echo json_encode($log);
		exit;
	}

	function get_shortcode_chat($atts,$content=null){
		global $user_ID;
		extract(shortcode_atts(array('room'=>false),$atts));
		$this->room = $user_ID;
		return '<div id="lk-content" class="chatroom rcl-content">
		<div class="privat_block recall_content_block active" style="display: block;">
		'.$this->get_chat().'
		</div>
		</div>';
	}

	function get_url_file_message($mess,$content){
		global $user_ID;
		if($mess->status_mess==6) return __('The file was loaded.','rcl');
		if($mess->status_mess==4||$mess->status_mess==5){
			if($mess->author_mess==$user_ID&&$mess->status_mess==5) return __('The file has been received, but not yet loaded.','rcl');
			if($mess->author_mess==$user_ID&&$mess->status_mess==4) return __('The file was sent to the recipient.','rcl');
			$content = wp_nonce_url(get_bloginfo('wpurl').'/?rcl-download-id='.base64_encode($mess->ID), 'user-'.$user_ID );
			$short_url = substr($content, 0, 25)."...".substr($content, -15);
			$content = __('Link to sent the file','rcl').': <br><a class="link-file-rcl" target="_blank" href="'.$content.'">'
                                .$short_url.'</a><br> <small>'
                                .__('(accept files only from trusted sources)','rcl')
                                .'</small>';
		}
		return $content;
	}

	function str_nl2br_rcl($content){
		$content_message = str_replace("\'","'",$content);
		$content_message = str_replace('\"','"',$content_message);
		$content_message = nl2br($content_message);
		return $content_message;
	}

	function get_footer_scripts_privat_rcl($script){
		global $rcl_options;

		$maxsize_mb = (isset($rcl_options['file_exchange_weight'])&&$rcl_options['file_exchange_weight'])? $rcl_options['file_exchange_weight']: 2;
		$maxsize = $maxsize_mb*1024*1024;

		$replace = "<div class=\"public-post message-block file\"><div class=\"content-mess\"><p style=\"margin-bottom:0px;\" class=\"time-message\"><span class=\"time\">'+result['time']+'</span></p><p class=\"balloon-message\">'+text+'</p></div></div>";
		$newmess = "<div class=\"new_mess\"></div>";

		if(!$rcl_options['sort_mess']) $replace .= $newmess;
		else $replace = $newmess.$replace;

		$done = "
			var result = data.result;
			if(result['recall']==100){
				var text = '".__("The file was sent successfully.","rcl")."';
			}
			if(result['recall']==150){
				var text = '".__("You have exceeded the limit on the number of uploaded files. Wait until the files sent previously will be accepted.","rcl")."';
			}
			$('.new_mess').replaceWith('$replace');
			$('#upload-box-message .progress-bar').hide();";

		if(!$rcl_options['sort_mess']) $done .= "var div = $('#resize-content');
			div.scrollTop( div.get(0).scrollHeight );";

		$script .= "
			$('#lk-content').on('click','.link-file-rcl',function(){
				$(this).parent().text('".__("Removes the file from the server","rcl")."');
			});
			var talker = $('input[name=\"adressat_mess\"]').val();
			var online = $('input[name=\"online\"]').val();


			$('#upload-private-message').fileupload({
			dataType: 'json',
			type: 'POST',
			url: wpurl+'wp-admin/admin-ajax.php',
			formData:{action:'rcl_message_upload',talker:talker,online:online},
			loadImageMaxFileSize: ".$maxsize.",
			autoUpload:true,
			progressall: function (e, data) {
				var progress = parseInt(data.loaded / data.total * 100, 10);
				$('#upload-box-message .progress-bar').show().css('width',progress+'px');
			},
			change:function (e, data) {
				if(data.files[0]['size']>".$maxsize."){
					rcl_notice('Превышен максимальный размер для изображения! Макс. ".$maxsize_mb."MB','error');
					return false;
				}
			},
			done: function (e, data) {".$done."}
		});";
		return $script;
	}

	function get_scripts_message_rcl($script){
                global $rcl_options;

		$ajaxdata = "type: 'POST', data: dataString, dataType: 'json', url: wpurl+'wp-admin/admin-ajax.php',";
		$words = (isset($rcl_options['ms_limit_words'])&&$rcl_options['ms_limit_words'])? $rcl_options['ms_limit_words']: 400;

		$script .= "
			jQuery('#private-smiles').hover(
				function(){
				  jQuery('#private-smiles .smiles').show();
				},
				function(){
				  jQuery('#private-smiles .smiles').hide();
				}
			);

			jQuery('#rcl-new-mess').on('click','.view-form',function(){
				jQuery('#rcl-new-mess .prmess').slideDown();
				jQuery(this).slideUp();
				return false;
			});

			jQuery('.delete_old_message').delay(60000).fadeOut();

			function count_word_in_message(word){
				var count = $words - word.val().length;
				return count;
			}

			function get_color_count_word(count){
				var color;
				if(count>150) color = 'green';
				if(count<150) color = 'orange';
				if(count<50) color = 'red';
				return color;
			}

			jQuery('#lk-content').on('keyup','#content_mess',function(){
				var word = jQuery(this);
				count = count_word_in_message(word);
				color = get_color_count_word(count);
				jQuery('#count-word').css('color', color).text(count);
				if(word.val().length > ".($words-1).")
				word.val(word.val().substr(0, ".($words-1)."));
			});

			jQuery('#rcl-new-mess').on('keyup','#minicontent_mess',function(){
				var word = jQuery(this);
				count = count_word_in_message(word);
				color = get_color_count_word(count);
				jQuery('#minicount-word').css('color', color).text(count);
				if(word.val().length > ".($words-1).")
				word.val(word.val().substr(0, ".($words-1)."));
			});

			jQuery.ionSound({
				sounds: ['e-oh','water_droplet'],
				path: '".rcl_addon_url('sounds/',__FILE__)."',
				multiPlay: false,
				volume: '0.5'
			});

		/* Добавление личного сообщения */
			function add_private_message_recall(){
				var content_mess = encodeURIComponent(jQuery('#content_mess').attr('value'));
				var widget = jQuery('#widget-mess').attr('value');
				var adressat_mess = jQuery('#adressat_mess').attr('value');
				if(adressat_mess=='0'){
					rcl_notice('Выберите собеседника!','error'); return false;
				}
				var online = jQuery('#online').attr('value');
				max_sec_update_rcl = 0;
				jQuery('#content_mess').attr('value', '');
				if(content_mess)
					var dataString = 'action=add_private_message_recall&content_mess='+content_mess+'&adressat_mess='+adressat_mess+'&online='+online+'&widget='+widget+'&user_ID='+user_ID;
				else
					return false;
				jQuery.ajax({
					".$ajaxdata."
					success: function(data){
						if(data['recall']==100){
							jQuery('.new_mess').replaceWith(data['message_block']);";
                                                        if(!$rcl_options['sort_mess']) $script .= "var div = jQuery('#resize-content');
                                                        div.scrollTop( div.get(0).scrollHeight );";
						$script .= "}
						if(data['recall']==200){
							jQuery('#privatemess').html(data['message_block']).fadeOut(5000);
						}
					}
				});
				return false;
			}
			jQuery('#lk-content').on('click','.addmess',function(){
			var content_text = jQuery('#content_mess').val();
			if(content_text) add_private_message_recall();
			return false;
			});

			ctrl = false;

			function breakText() {
			  var caret = jQuery('#content_mess').getSelection().start;
			  jQuery('#content_mess').insertText('".'\r\n'."', caret, false).setSelection(caret+1, caret+1);
			}

			jQuery('#content_mess').keydown(function(event){
			  switch (event.which) {
				case 13: return false;
				case 17: ctrl = true;
			  }
			});

			jQuery('#content_mess').keyup(function(event){
			var content_text = jQuery('#content_mess').val();
			  switch (event.which) {
				case 13:
				  if (ctrl){
				  if(content_text)
					add_private_message_recall();
					return false;
				  }
				  breakText();
				break;
				case 17: ctrl = false;
			  }
			});

			function add_private_minimessage_recall(){
				var content_mess = jQuery('#minicontent_mess').attr('value');
				var widget = jQuery('#widget-mess').attr('value');
				var adressat_mess = jQuery('#miniadressat_mess').attr('value');
				if(content_mess)
					var dataString = 'action=add_private_message_recall&content_mess='+content_mess+'&adressat_mess='+adressat_mess+'&widget='+widget+'&user_ID='+user_ID;
				else
					return false;
				jQuery.ajax({
					".$ajaxdata."
					success: function(data){
						if(data['recall']==200){
							jQuery('#privatemess').html(data['message_block']).fadeOut(5000);
							jQuery('#rcl-new-mess').delay(2000).queue(function () {jQuery('#rcl-new-mess').empty();jQuery('#rcl-new-mess').dequeue();});
						}
					}
				});
				return false;
			}
			jQuery('#rcl-new-mess').on('click','.miniaddmess',function(){
			var content_text = jQuery('#rcl-new-mess #minicontent_mess').val();
			if(content_text)
                            add_private_minimessage_recall();
			});

			ctrl = false;

			function minibreakText() {
			  var caret = jQuery('#minicontent_mess').getSelection().start;
			  jQuery('#minicontent_mess').insertText('".'\r\n'."', caret, false).setSelection(caret+1, caret+1);
			}

			jQuery('#minicontent_mess').keydown(function(event){
			  switch (event.which) {
				case 13: return false;
				case 17: ctrl = true;
			  }
			});

		/* Отмечаем сообщение как прочтенное */
			jQuery('#rcl-new-mess').on('click','.close-mess-window',function(){
				var id_mess = parseInt(jQuery(this).attr('id').replace(/\D+/g,''));
				var dataString = 'action=close_new_message_recall&id_mess='+id_mess+'&user_ID='+user_ID;
				jQuery.ajax({
					".$ajaxdata."
					success: function(data){
						if(data['recall']==100){
							jQuery('#privatemess').html(data['message_block']).fadeOut(5000);
							jQuery('#rcl-new-mess').delay(2000).queue(function () {jQuery('#rcl-new-mess').empty();jQuery('#rcl-new-mess').dequeue();});
						} else {
							rcl_notice('Ошибка!','error');
						}
					}
				});
				return false;
			});
		/* Добавление в черный список */
			jQuery('#manage-blacklist').click(function(){
				var user_id = jQuery(this).data('contact');
				var dataString = 'action=manage_blacklist_recall&user_id='+user_id;
				jQuery.ajax({
					".$ajaxdata."
					success: function(data){
						if(data['otvet']==100){
							jQuery('#manage-blacklist').replaceWith(data['content']);
						} else {
							rcl_notice('Ошибка!','error');
						}
					}
				});
				return false;
			});
		jQuery('#lk-content').on('click','.remove_black_list',function(){
				var id_user = jQuery(this).data('contact');
				var dataString = 'action=remove_ban_list_rcl&id_user='+id_user+'&user_ID='+user_ID;
				jQuery.ajax({
					".$ajaxdata."
					success: function(data){
						if(data['otvet']==100){
							 jQuery('.history-'+data['id_user']).remove();
						} else {
							rcl_notice('Ошибка!','error');
						}
					}
				});
				return false;
			});
		/* Удаление истории переписки */
			jQuery('#lk-content').on('click','.del_history',function(){
				var id_user = jQuery(this).data('contact');
				var dataString = 'action=delete_history_private_recall&id_user='+id_user+'&user_ID='+user_ID;
				jQuery.ajax({
					".$ajaxdata."
					success: function(data){
						if(data['otvet']==100){
							 jQuery('.history-'+data['id_user']).remove();
						} else {
							rcl_notice('Ошибка!','error');
						}
					}
				});
				return false;
			});

		/* Получаем старые сообщения в переписке */
			jQuery('#lk-content').on('click','.old_message',function(){
				rcl_preloader_show('#tab-privat > div');
				block_mess++;
				var dataString = 'action=get_old_private_message_recall&block_mess='+block_mess+'&old_num_mess='+old_num_mess+'&user='+user_old_mess+'&user_ID='+user_ID;

				jQuery.ajax({
					".$ajaxdata."
					success: function(data){
						if(data['recall']==100){
							jQuery('.old_mess_block').replaceWith(data['message_block']);
							old_num_mess = data['num_mess_now'];
						}
						rcl_preloader_hide();
					}
				});
				return false;
			});

		jQuery('#lk-content').on('click','#get-important-rcl',function(){
			rcl_preloader_show('#tab-privat > div');
			if(jQuery(this).hasClass('important')){
				jQuery(this).removeClass('important').text('Вся переписка');
				var type = 0;
				if(block_mess) block_mess = 1;
			}else{
				jQuery(this).addClass('important').text('Важные сообщения');
				var type = 1;
			}
				var userid = parseInt(jQuery('.wprecallblock').attr('id').replace(/\D+/g,''));
				var dataString = 'action=get_important_message_rcl&user='+userid+'&type='+type+'&user_ID='+user_ID;

				jQuery.ajax({
					".$ajaxdata."
					success: function(data){
						if(data['recall']==100){
							jQuery('#message-list').html(data['content']);
						}
						rcl_preloader_hide();
					}
				});
				return false;
			});

		jQuery('#lk-content').on('click','#tab-privat .sec_block_button',function(){
				if(jQuery(this).hasClass('active'))return false;
                                rcl_preloader_show('#tab-privat > div');
				var days = jQuery(this).attr('data');
				jQuery('.correspond .sec_block_button').removeClass('active');
				jQuery(this).addClass('active');
				var dataString = 'action=get_interval_contacts_rcl&days='+days+'&user_ID='+user_ID;
				jQuery.ajax({
					".$ajaxdata."
					success: function(data){
						if(data['recall']==100){
							jQuery('.correspond #contact-lists').html(data['message_block']);
						} else {
							rcl_notice('Ошибка!','error');
						}
                                                rcl_preloader_hide();
					}
				});
				return false;
			});
		jQuery('#lk-content').on('click','#get-all-contacts',function(){
			var dataString = 'action=get_interval_contacts_rcl&days=0&user_ID='+user_ID;

			jQuery.ajax({
				".$ajaxdata."
				success: function(data){
					if(data['recall']==100){
						jQuery('#rcl-overlay').fadeIn();
						jQuery('#rcl-popup').html('<a href=# class=close-popup></a>'+data['message_block']);
						var screen_top = jQuery(window).scrollTop();
						var popup_h = jQuery('#rcl-popup').height();
						var window_h = jQuery(window).height();
						screen_top = screen_top + 60;
						jQuery('#rcl-popup').css('top', screen_top+'px').delay(100).slideDown(400);
					}else{
						rcl_notice('Ошибка!','error');
					}
				}
			});
			return false;
		});
		jQuery('#lk-content').on('click','#message-list .important',function(){
			update_important_rcl(jQuery(this).attr('idmess'));
			return false;
		});
		function update_important_rcl(id_mess){
			var dataString = 'action=update_important_rcl&id_mess='+id_mess+'&user_ID='+user_ID;
			jQuery.ajax({
				".$ajaxdata."
				success: function(data){
					if(data['res']==100) jQuery('#message-'+id_mess+' .important').addClass('active');
					if(data['res']==200) jQuery('#message-'+id_mess+' .important').removeClass('active');
				}
			});
			return false;
		}
		";
		return $script;
	}
}
$Rcl_Messages = new Rcl_Messages();

function rcl_add_message($args){

	global $user_ID,$wpdb;

	if($args['author']) $author = $args['author'];
	else $author = $user_ID;

	if(!$args['content']) return false;

	$content = $args['content'];
	$addressat = $args['addressat'];

	$status_mess = 0;
	$time = current_time('mysql');

	$content_mess = apply_filters('rcl_pre_save_private_message',$content);

	$result = $wpdb->insert(
            RCL_PREF.'private_message',
            array(
                'author_mess' => $author,
                'content_mess' => $content_mess,
                'adressat_mess' => $addressat,
                'time_mess' => $time,
                'status_mess' => $status_mess
            )
	);

        $users = array(
            (object)array(
                'ID'=>$author,
                'addressat_id'=>$addressat,
            ),
            (object)array(
                'ID'=>$addressat,
                'addressat_id'=>$author,
            ),
        );

	$statuses = $wpdb->get_results($wpdb->prepare("SELECT user,contact,status FROM ".RCL_PREF."private_contacts "
                . "WHERE user = '%d' AND contact = '%d' OR user = '%d' AND contact = '%d'"
                ,$author,$addressat,$addressat,$author));

        $contacts = array();

        foreach($statuses as $status){
            $contacts[$status->user]['contact'] = $status->contact;
            $contacts[$status->user]['status'] = $status->status;
        }

        foreach($users as $user){
            if(isset($contacts[$user->ID])){
                if($contacts[$user->ID]['status']!=3) continue;

                $wpdb->update(
                RCL_PREF.'private_contacts',
                        array( 'status' => 1 ),
                        array( 'user' => $user->ID, 'contact' => $contacts[$user->ID]['contact'] )
                );
                continue;
            }
            $wpdb->insert(
                RCL_PREF.'private_contacts',
                        array(
                        'user' => $user->ID,
                        'contact' => $user->addressat_id,
                        'status' => 1
                )
            );
        }

	do_action('rcl_new_private_message', $addressat, $user_ID);

	return $result;

}

include_once 'notify.php';
include_once 'upload-file.php';
