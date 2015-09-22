<?php
require_once("functions/minify-files/minify-css.php");
require_once('functions/enqueue-scripts.php');
if(is_admin()) require_once("rcl-admin/admin-pages.php");
require_once("functions/deprecated.php");
require_once('functions/migration.php');
require_once("functions/tabs_options.php");
require_once("rcl-widgets.php");
require_once("functions/shortcodes.php");
require_once('functions/includes.php');
require_once('functions/navi-rcl.php');
require_once('functions/recallbar.php');
require_once('functions/rcl_custom_fields.php');
require_once('functions/register.php');
require_once('functions/authorize.php');
require_once('functions/loginform.php');
require_once('functions/rcl_currency.php');

if(class_exists('ReallySimpleCaptcha')){
    require_once('functions/captcha.php');
}

//добавляем вкладку со списком публикаций хозяина ЛК указанного типа записей в личный кабинет
function rcl_postlist($id,$posttype,$name='',$args=false){
    global $rcl_options;
    if(!$rcl_options) $rcl_options = get_option('primary-rcl-options');
    if($rcl_options['publics_block_rcl']!=1) return false;
    if (!class_exists('Rcl_Postlist')) include_once plugin_dir_path( __FILE__ ).'add-on/publicpost/rcl_postlist.php';
    $plist = new Rcl_Postlist($id,$posttype,$name,$args);
}
//добавляем контентный блок в указанное место личного кабинета
function rcl_block($place,$callback,$args=false){

	$data = array(
        'place'=>$place,
        'callback'=>$callback,
        'args'=>$args
    );

    $data = apply_filters('block_data_rcl',$data);

    if(is_admin())return false;

    if (!class_exists('Rcl_Blocks')) include_once plugin_dir_path( __FILE__ ).'functions/rcl_blocks.php';
    $block = new Rcl_Blocks($data);
}
//добавляем уведомление в личном кабинете
function rcl_notice_text($text,$type='warning'){
    if(is_admin())return false;
    if (!class_exists('Rcl_Notify')) include_once plugin_dir_path( __FILE__ ).'functions/rcl_notify.php';
    $block = new Rcl_Notify($text,$type);
}
//добавляем вкладку в личный кабинет
function rcl_tab($id,$callback,$name='',$args=false){

    $data = array(
        'id'=>$id,
        'callback'=>$callback,
        'name'=>$name,
        'args'=>$args
    );

    if($name) $data = apply_filters('tab_data_rcl',$data);

    if(is_admin())return false;

    if (!class_exists('Rcl_Tabs')) include_once plugin_dir_path( __FILE__ ).'functions/rcl_tabs.php';

    $tab = new Rcl_Tabs($data);
}

function rcl_crop($filesource,$width,$height,$file){
    if (!class_exists('Rcl_Crop')) require_once(RCL_PATH.'functions/rcl_crop.php');
	$crop = new Rcl_Crop();
    return $crop->get_crop($filesource,$width,$height,$file);
}

function rcl_get_template_path($file_temp,$path=false){

    $dirs   = array(RCL_TAKEPATH.'templates/', RCL_PATH.'templates/');

    if($path) $dirs[1] = rcl_addon_path($path).'templates/';

    foreach($dirs as $dir){
        if(!file_exists($dir.$file_temp)) continue;
        return $dir.$file_temp;
        break;
    }
    return false;
}

function rcl_include_template($file_temp,$path=false){
    $pathfile = rcl_get_template_path($file_temp,$path);
    if(!$pathfile) return false;
    include $pathfile;
}

function rcl_get_include_template($file_temp,$path=false){
    ob_start();
    rcl_include_template($file_temp,$path);
    $content = ob_get_contents();
    ob_end_clean();
    return $content;
}

function rcl_key_addon($path_parts){
    if(!isset($path_parts['dirname'])) return false;
    $key = false;
    $ar_dir = explode('/',$path_parts['dirname']);
    if(!isset($ar_dir[1])) $ar_dir = explode('\\',$path_parts['dirname']);
    $cnt = count($ar_dir)-1;
    for($a=$cnt;$a>=0;$a--){if($ar_dir[$a]=='add-on'){$key=$ar_dir[$a+1];break;}}
    return $key;
}

//формируем массив данных о вкладках личного кабинета
if(is_admin()) add_filter('tab_data_rcl','rcl_get_data_tab',10);
function rcl_get_data_tab($data){
    global $tabs_rcl;
    $tabs_rcl[$data['id']] = $data;
    return $data;
}

function rcl_add_balloon_menu($data,$args){
    if($data['id']!=$args['tab_id']||!$args['ballon_value']) return $data;
    $data['name'] = sprintf('%s <span class="rcl-menu-notice">%s</span>',$data['name'],$args['ballon_value']);
    return $data;
}

add_action('wp_ajax_rcl_ajax_tab', 'rcl_ajax_tab');
add_action('wp_ajax_nopriv_rcl_ajax_tab', 'rcl_ajax_tab');
function rcl_ajax_tab(){
    global $wpdb,$array_tabs,$user_LK,$rcl_userlk_action;
    $id_tab = sanitize_title($_POST['id']);
    $func = $array_tabs[$id_tab];
    $user_LK = intval($_POST['lk']);
    if(!$rcl_userlk_action) $rcl_userlk_action = $wpdb->get_var($wpdb->prepare("SELECT time_action FROM ".RCL_PREF."user_action WHERE user='%d'",$user_LK));
    if (!class_exists('Rcl_Tabs')) include_once plugin_dir_path( __FILE__ ).'functions/rcl_tabs.php';
    if(!$array_tabs[$id_tab]){
        $log['content']=__('Error! Perhaps this addition does not support ajax loading','rcl');
    }else{
        $data = array(
            'id'=>$id_tab,
            'callback'=>$func,
            'name'=>false,
            'args'=>array('public'=>1)
        );
        $tab = new Rcl_Tabs($data);
        $log['content']=$tab->add_tab('',$user_LK);
    }

    $log['result']=100;
    echo json_encode($log);
    exit;
}

add_action('init','rcl_init_ajax_tabs');
function rcl_init_ajax_tabs(){
        global $array_tabs;
        $id_tabs = array();
	$array_tabs = apply_filters( 'ajax_tabs_rcl', $id_tabs );
	return $array_tabs;
}

function rcl_get_wp_upload_dir(){
    if(defined( 'MULTISITE' )){
        $upload_dir = array(
            'basedir' => WP_CONTENT_DIR.'/uploads',
            'baseurl' => WP_CONTENT_URL.'/uploads'
        );
    }else{
        $upload_dir = wp_upload_dir();
    }

    if (is_ssl()) $upload_dir['baseurl'] = str_replace( 'http://', 'https://', $upload_dir['baseurl'] );

    return $upload_dir;
}

function rcl_update_dinamic_files(){
    //include('class_addons.php');
    $rcl_addons = new rcl_addons();
    $rcl_addons->get_update_scripts_file_rcl();
    $rcl_addons->get_update_scripts_footer_rcl();
    rcl_minify_style();
}
add_action('wp_head','rcl_head_js_data',1);
function rcl_head_js_data(){
    global $user_ID;
    $data = "<script>
	var user_ID = $user_ID;
	var wpurl = '".preg_quote(trailingslashit(get_bloginfo('wpurl')),'/:')."';
	var rcl_url = '".preg_quote(RCL_URL,'/:')."';
	</script>";
    echo $data;
}

add_action('wp_footer','rcl_popup_contayner');
function rcl_popup_contayner(){
    echo '<div id="rcl-overlay"></div>
		  <div id="rcl-popup"></div>';
}

add_filter('wp_footer', 'rcl_footer_url');
function rcl_footer_url(){
	global $rcl_options;
	if($rcl_options['footer_url_recall']!=1) return false;
	if(is_front_page()&&!is_user_logged_in()) echo '<p class="plugin-info">'.__('The site works using the functionality of the plugin').'  <a target="_blank" href="http://wppost.ru/">Wp-Recall</a></p>';
}

function rcl_delete_user_action($user){
	global  $wpdb;
	$wpdb->query($wpdb->prepare("DELETE FROM ".RCL_PREF."user_action WHERE user = '%d'",$user));
}
add_action('delete_user','rcl_delete_user_action');

function rcl_get_author_block(){
    global $post;

    $content = "<div id=block_author-rcl>";
    $content .= "<h3>".__('Author of publication','rcl')."</h3>";

    if(function_exists('rcl_add_userlist_follow_button')) add_filter('user_description','rcl_add_userlist_follow_button',90);
    $content .= rcl_get_userlist(array('type' => 'rows','include' => $post->post_author ,'orderby'=>'action','search'=>'no'));
    if(function_exists('rcl_add_userlist_follow_button')) remove_filter('user_description','rcl_add_userlist_follow_button',90);

    $content .= "</div>";

    return $content;
}

function rcl_get_miniaction($action,$user_id=false){
    global $wpdb;
    if(!$action) $action = $wpdb->get_var($wpdb->prepare("SELECT time_action FROM ".RCL_PREF."user_action WHERE user='%d'",$user_id));
    $last_action = rcl_get_useraction($action);
    $class = (!$last_action&&$action)?'online':'offline';

    $content = '<div class="status_author_mess '.$class.'">';
    if(!$last_action&&$action) $content .= '<i class="fa fa-circle"></i>';
    else $content .= 'не в сети '.$last_action;
    $content .= '</div>';

    return $content;
}

//заменяем ссылку автора комментария на ссылку его ЛК
function rcl_get_link_author_comment($href){
	global $comment;
	if($comment->user_id==0) return $href;
	$href = get_author_posts_url($comment->user_id);
	return $href;
}
function rcl_add_edit_post_button($excerpt,$post=null){
if(!isset($post)) global $post;
global $user_ID;
	if($user_ID){
		if($user_ID==$post->post_author){
			$form_button = "<div class='post-edit-button'>
				<input id='delete-post' type='image' name='delete_post' src='".RCL_URL."img/delete.png' value='".$post->ID."'></div>
				<div class='post-edit-button'>
				<input type='image' id='edit-post' name='update_post' src='".RCL_URL."img/redactor.png' value='".$post->ID."'></div>";
		}

		$form_button = apply_filters('buttons_edit_post_rcl',$form_button,$post);

		if($form_button) $excerpt .= $form_button;
	}
	return $excerpt;
}
//запрещаем доступ в админку
add_action('init','rcl_admin_access',1);
function rcl_admin_access(){
	global $current_user,$rcl_options;
	if(defined( 'DOING_AJAX' ) && DOING_AJAX) return;
	if(defined( 'IFRAME_REQUEST' ) && IFRAME_REQUEST) return;
	if(is_admin()){
		$rcl_options = get_option('primary-rcl-options');
		get_currentuserinfo();
		$access = 7;
		if(isset($rcl_options['consol_access_rcl'])) $access = $rcl_options['consol_access_rcl'];
		$user_info = get_userdata($current_user->ID);
		if ( $user_info->user_level < $access ){
			if(intval($_POST['short'])==1||intval($_POST['fetch'])==1){
				return true;
			}else{
				if(!$current_user->ID) return true;
				wp_redirect('/'); exit;
			}
		}else {
			return true;
		}
	}
}
function rcl_hidden_admin_panel(){
	global $rcl_options,$user_ID;

        if(!$user_ID){
            return show_admin_bar(false);
        }

	$access = 7;
	if(isset($rcl_options['consol_access_rcl'])) $access = $rcl_options['consol_access_rcl'];
	$user_info = get_userdata($user_ID);
	if ( $user_info->user_level < $access ){
		show_admin_bar(false);
	}else{
		return true;
	}
}
function rcl_banned_user_redirect(){
    global $user_ID;
    if(!$user_ID) return false;
    $user_data = get_userdata( $user_ID );
    $roles = $user_data->roles;
    $role = array_shift($roles);
    if($role=='banned') wp_die(__('Congratulations! You have been banned.','rcl'));
}
add_action('init','rcl_banned_user_redirect');

/* Удаление поста вместе с его вложениями*/
function rcl_delete_attachments_with_post($postid){
    $attachments = get_posts( array( 'post_type' => 'attachment', 'posts_per_page' => -1, 'post_status' => null, 'post_parent' => $postid ) );
    if($attachments){
	foreach((array)$attachments as $attachment ){
        wp_delete_attachment( $attachment->ID, true ); }
	}
}

add_action('init','rcl_init_avatar_sizes');
function rcl_init_avatar_sizes(){
	global $rcl_avatar_sizes;

	$sizes = array(70,150,300);

	$rcl_avatar_sizes = apply_filters('rcl_avatar_sizes',$sizes);
	asort($rcl_avatar_sizes);

}

function rcl_get_url_avatar($url_image,$user_id,$size){
	global $rcl_avatar_sizes;

	$optimal_size = 150;
	$optimal_path = false;
	$name = explode('.',basename($url_image));
	foreach($rcl_avatar_sizes as $rcl_size){
		if($size>$rcl_size) continue;

		$optimal_size = $rcl_size;
		$optimal_url = RCL_UPLOAD_URL.'avatars/'.$user_id.'-'.$optimal_size.'.'.$name[1];
		$optimal_path = RCL_UPLOAD_PATH.'avatars/'.$user_id.'-'.$optimal_size.'.'.$name[1];
		break;
	}

	if($optimal_path&&file_exists($optimal_path)) $url_image = $optimal_url;

	return $url_image;
}

//Функция вывода своего аватара
function rcl_avatar_replacement($avatar, $id_or_email, $size, $default, $alt){

    if (is_numeric($id_or_email)){
            $user_id = $id_or_email;
    }elseif( is_object($id_or_email)){
            $user_id = $id_or_email->user_id;
    }

    $avatar_data = get_user_meta($user_id,'rcl_avatar',1);
    if($avatar_data){
            if(is_numeric($avatar_data)){
                    $image_attributes = wp_get_attachment_image_src($avatar_data);
                    if($image_attributes) $avatar = "<img class='avatar' src='".$image_attributes[0]."' alt='".$alt."' height='".$size."' width='".$size."' />";
            }else if(is_string($avatar_data)){
					$avatar_data = rcl_get_url_avatar($avatar_data,$user_id,$size);
                    $avatar = "<img class='avatar' src='".$avatar_data."' alt='".$alt."' height='".$size."' width='".$size."' />";
            }
    }

    if ( !empty($id_or_email->user_id)) $avatar = '<a height="'.$size.'" width="'.$size.'" href="'.get_author_posts_url($id_or_email->user_id).'">'.$avatar.'</a>';

    return $avatar;
}

function rcl_sanitize_title_with_translit($title) {
    $gost = array(
        "Є"=>"EH","І"=>"I","і"=>"i","№"=>"#","є"=>"eh",
        "А"=>"A","Б"=>"B","В"=>"V","Г"=>"G","Д"=>"D",
        "Е"=>"E","Ё"=>"JO","Ж"=>"ZH",
        "З"=>"Z","И"=>"I","Й"=>"JJ","К"=>"K","Л"=>"L",
        "М"=>"M","Н"=>"N","О"=>"O","П"=>"P","Р"=>"R",
        "С"=>"S","Т"=>"T","У"=>"U","Ф"=>"F","Х"=>"KH",
        "Ц"=>"C","Ч"=>"CH","Ш"=>"SH","Щ"=>"SHH","Ъ"=>"'",
        "Ы"=>"Y","Ь"=>"","Э"=>"EH","Ю"=>"YU","Я"=>"YA",
        "а"=>"a","б"=>"b","в"=>"v","г"=>"g","д"=>"d",
        "е"=>"e","ё"=>"jo","ж"=>"zh",
        "з"=>"z","и"=>"i","й"=>"jj","к"=>"k","л"=>"l",
        "м"=>"m","н"=>"n","о"=>"o","п"=>"p","р"=>"r",
        "с"=>"s","т"=>"t","у"=>"u","ф"=>"f","х"=>"kh",
        "ц"=>"c","ч"=>"ch","ш"=>"sh","щ"=>"shh","ъ"=>"",
        "ы"=>"y","ь"=>"","э"=>"eh","ю"=>"yu","я"=>"ya",
        "—"=>"-","«"=>"","»"=>"","…"=>""
    );
    $iso = array(
        "Є"=>"YE","І"=>"I","Ѓ"=>"G","і"=>"i","№"=>"#","є"=>"ye","ѓ"=>"g",
        "А"=>"A","Б"=>"B","В"=>"V","Г"=>"G","Д"=>"D",
        "Е"=>"E","Ё"=>"YO","Ж"=>"ZH",
        "З"=>"Z","И"=>"I","Й"=>"J","К"=>"K","Л"=>"L",
        "М"=>"M","Н"=>"N","О"=>"O","П"=>"P","Р"=>"R",
        "С"=>"S","Т"=>"T","У"=>"U","Ф"=>"F","Х"=>"X",
        "Ц"=>"C","Ч"=>"CH","Ш"=>"SH","Щ"=>"SHH","Ъ"=>"'",
        "Ы"=>"Y","Ь"=>"","Э"=>"E","Ю"=>"YU","Я"=>"YA",
        "а"=>"a","б"=>"b","в"=>"v","г"=>"g","д"=>"d",
        "е"=>"e","ё"=>"yo","ж"=>"zh",
        "з"=>"z","и"=>"i","й"=>"j","к"=>"k","л"=>"l",
        "м"=>"m","н"=>"n","о"=>"o","п"=>"p","р"=>"r",
        "с"=>"s","т"=>"t","у"=>"u","ф"=>"f","х"=>"x",
        "ц"=>"c","ч"=>"ch","ш"=>"sh","щ"=>"shh","ъ"=>"",
        "ы"=>"y","ь"=>"","э"=>"e","ю"=>"yu","я"=>"ya",
        "—"=>"-","«"=>"","»"=>"","…"=>""
    );

    $rtl_standard = get_option('rtl_standard');

    switch ($rtl_standard) {
            case 'off':
                return $title;
            case 'gost':
                return strtr($title, $gost);
            default:
                return strtr($title, $iso);
    }
}
if(!function_exists('sanitize_title_with_translit')) add_action('sanitize_title', 'rcl_sanitize_title_with_translit', 0);

add_filter('the_content','rcl_message_post_moderation');
function rcl_message_post_moderation($cont){
global $post;
	if($post->post_status=='pending'){
		$mess = '<h3 class="pending-message">'.__('Publication pending approval!','rcl').'</h3>';
		$cont = $mess.$cont;
	}
	return $cont;
}
function rcl_get_postmeta($post_id){
	if($post_id){
            $post = get_post($post_id);
            $posttype = $post->post_type;
        }

	switch($posttype){
		case 'post':
			if($post) $id_form = get_post_meta($post->ID,'publicform-id',1);
			if(!$id_form) $id_form = 1;
			$id_field = 'custom_public_fields_'.$id_form;
		break;
		case 'products': $id_field = 'custom_saleform_fields'; break;
		default: $id_field = 'custom_fields_'.$posttype;
	}

	$get_fields = get_option($id_field);

	if(!$get_fields) return false;

	if($get_fields){

            $cf = new Rcl_Custom_Fields();

            foreach((array)$get_fields as $custom_field){
                $slug = $custom_field['slug'];
                $value = get_post_meta($post_id,$slug,1);
                $show_custom_field .= $cf->get_field_value($custom_field,$value);
            }

            return $show_custom_field;
	}
}
add_filter('author_link','rcl_author_link',999,2);
function rcl_author_link($link, $author_id){
	global $rcl_options;
	if(!isset($rcl_options['view_user_lk_rcl'])||$rcl_options['view_user_lk_rcl']!=1) return $link;
	$get = ! empty( $rcl_options['link_user_lk_rcl'] ) ? $rcl_options['link_user_lk_rcl'] : 'user';
	return add_query_arg( array( $get => $author_id ), get_permalink( $rcl_options['lk_page_rcl'] ) );
	//return rcl_format_url( get_permalink( $rcl_options['lk_page_rcl'] ) ).$get.'='.$author_id;
}
/*not found*/
function rcl_userfield_array($array,$field,$name_data){
	global $wpdb;

	foreach((array)$array as $object){
            if(is_object($array))$userslst[] = $object->$name_data;
                    if(is_array($array))$userslst[] = $object[$name_data];
        }

	$users_fields = $wpdb->get_results($wpdb->prepare("SELECT user_id,meta_value FROM ".$wpdb->prefix."usermeta WHERE user_id IN (".rcl_format_in($userslst).") AND meta_key = '%s'",$userslst,$field));

	foreach((array)$users_fields as $user){
		$fields[$user->user_id] = $user->$field;
	}
	return $fields;
}

function rcl_format_in($array){
	$separats = array_fill(0, count($array), '%d');
	return implode(', ', $separats);
}

function rmag_global_options(){
	$content = ' <div id="recall" class="left-sidebar wrap">
	<form method="post" action="">
		'.wp_nonce_field('update-options-rmag','_wpnonce',true,false);

	$content = apply_filters('admin_options_rmag',$content);

	$content .= '<div class="submit-block">
	<p><input type="submit" class="button button-primary button-large right" name="primary-rmag-options" value="'.__('Save settings','rcl').'" /></p>
	</form></div>
	</div>';
	echo $content;
}
function rmag_update_options ( ) {
  if ( isset( $_POST['primary-rmag-options'] ) ) {
	if( !wp_verify_nonce( $_POST['_wpnonce'], 'update-options-rmag' ) ) return false;
	$_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
    foreach($_POST as $key => $value){
		if($key=='primary-rmag-options') continue;
		$options[$key]=$value;
	}
	update_option('primary-rmag-options',$options);
	wp_redirect(admin_url('admin.php?page=manage-wpm-options'));
	exit;
  }
}
add_action('init', 'rmag_update_options');

function rcl_get_postmeta_array($post_id){
    global $wpdb;
    $mts = array();
    $metas = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".$wpdb->prefix."postmeta WHERE post_id='%d'",$post_id));
    if(!$metas) return false;
    foreach($metas as $meta){
        $mts[$meta->meta_key] = $meta->meta_value;
    }
    //print_r($mts);exit;
    return $mts;
}

function rcl_setup_chartdata($mysqltime,$data){
    global $chartArgs;
    $day = date("j", strtotime($mysqltime));
    $price = $data/1000;
    $month = date("n", strtotime($mysqltime));
    $chartArgs[$month][$day]['summ'] += $price;
    $chartArgs[$month]['summ'] += $price;
    $chartArgs[$month][$day]['cnt'] += 1;
    $chartArgs[$month]['cnt'] += 1;
    $chartArgs[$month]['days'] = date("t", strtotime($mysqltime));
    return $chartArgs;
}

function rcl_get_chart($arr=false){
    global $chartData;

    if(!$arr) return false;

    if(count($arr)==1){
        foreach($arr as $month=>$data){
            for($a=1;$a<=$data['days'];$a++){
                $cnt = (isset($data[$a]['cnt']))?$data[$a]['cnt']:0;
                $summ = (isset($data[$a]['summ']))?$data[$a]['summ']:0;
                $chartData['data'][] = array($a, $cnt,$summ);
            }
        }
    }else{
        for($a=1;$a<=12;$a++){
            $cnt = (isset($arr[$a]['cnt']))?$arr[$a]['cnt']:0;
            $summ = (isset($arr[$a]['summ']))?$arr[$a]['summ']:0;
            $chartData['data'][] = array($a, $cnt,$summ);
        }
    }

    if(!$chartData) return false;

    return rcl_get_include_template('chart.php');
}

/*22-06-2015 Удаление папки с содержимым*/
function rcl_remove_dir($dir){
	$dir = untrailingslashit($dir);
	if(!is_dir($dir)) return false;
	if ($objs = glob($dir."/*")) {
	   foreach($objs as $obj) {
		 is_dir($obj) ? rcl_remove_dir($obj) : unlink($obj);
	   }
	}
	rmdir($dir);
}

class Rcl_Form_Fields{

	public $type;
	public $placeholder;
	public $label;
	public $name;
	public $value;
	public $maxlength;
	public $checked;
	public $required;

	function get_field($args){
		$this->type = (isset($args['type']))? $args['type']: 'text';
		$this->id = (isset($args['id']))? 'id="'.$args['id'].'"': false;
		$this->placeholder = (isset($args['placeholder']))? $args['placeholder']: false;
		$this->label = (isset($args['label']))? $args['label']: false;
		$this->name = (isset($args['name']))? $args['name']: false;
		$this->value = (isset($args['value']))? $args['value']: false;
		$this->maxlength = (isset($args['maxlength']))? $args['maxlength']: false;
		$this->checked = (isset($args['checked']))? $args['checked']: false;
		$this->required = (isset($args['required'])&&$args['required'])? true: false;

		return $this->get_type_field();
	}

	function add_label($field){
		switch($this->type){
			case 'radio': return sprintf('<label>%s %s</label>',$field,$this->label); break;
			case 'checkbox': return sprintf('<label>%s %s</label>',$field,$this->label); break;
			default: return sprintf('<label>%s</label>%s',$this->label,$field);
		}
	}

	function get_type_field(){

		switch($this->type){
			case 'textarea': $field = sprintf('<textarea name="%s" placeholder="%s" '.$this->required().' %s>%s</textarea>',$this->name,$this->placeholder,$this->id,$this->value); break;
			default: $field = sprintf('<input type="%s" name="%s" value="%s" placeholder="%s" maxlength="%s" '.$this->selected().' '.$this->required().' %s>',$this->type,$this->name,$this->value,$this->placeholder,$this->maxlength,$this->id);
		}

		if($this->label) $field = $this->add_label($field);

		return $field;

	}

	function selected(){
		if(!$this->checked) return false;
		switch($this->type){
			case 'radio': return 'checked=checked'; break;
			case 'checkbox': return 'checked=checked'; break;
		}
	}

	function required(){
		if(!$this->required) return false;
		return 'required=required';
	}
}

function rcl_form_field($args){
	$field = new Rcl_Form_Fields();
	return $field->get_field($args);
}

function rcl_get_smiles($id_area){
	global $wpsmiliestrans,$rcl_smilies;

	if(isset($rcl_smilies[1])&&is_array($rcl_smilies[1])){
		foreach($rcl_smilies as $key=>$imgs){
			foreach($imgs as $emo=>$img){
				if(isset($rcl_smilies[$key][0])) $smilies_list[$key][0]=$rcl_smilies[$key][0];
				else if(!isset($smilies_list[$key][0])) $smilies_list[$key][0]=$emo;
				if($emo) $smilies_list[$key][$img]=$emo;
			}
		}
	}else{
		if(!$rcl_smilies) $rcl_smilies = $wpsmiliestrans;

                if(!$rcl_smilies) return false;

		foreach($rcl_smilies as $emo=>$img){
			if(!isset($smilies_list[0][0])) $smilies_list[0][0]=$emo;
			$smilies_list[0][$img]=$emo;
		}
	}

	$smiles = '<div class="rcl-smiles" data-area="'.$id_area.'">';

	foreach ( $smilies_list as $key=>$smils ) {
		$smiles .= str_replace( 'style="height: 1em; max-height: 1em;"', 'data-dir="'.$key.'"', convert_smilies( $smils[0] ) );
		$smiles .= '<div class="rcl-smiles-list">
						<div class="smiles"></div>
					</div>';
	}

	$smiles .= '</div>';

	return $smiles;
}

function rcl_get_smiles_ajax(){
	global $wpsmiliestrans,$rcl_smilies;

	if(!$rcl_smilies){
		foreach($wpsmiliestrans as $emo=>$smilie){
			$rcl_smilies[$emo]=$smilie;
		}
	};

	$namedir = $_POST['dir'];
	$area = $_POST['area'];

	$smiles = '';

	$dir = (isset($rcl_smilies[$namedir]))? $rcl_smilies[$namedir]: $rcl_smilies;

	foreach ( $dir as $emo=>$gif ) {
		if(!$emo) continue;
		//$b = array('','img alt="'.$emo.'" onclick="document.getElementById(\''.$area.'\').value=document.getElementById(\''.$area.'\').value+\' '.$emo.' \'"');
		$smiles .= str_replace( 'style="height: 1em; max-height: 1em;"', '', convert_smilies( $emo ) );
	}


	if($smiles){
		$log['result'] = 1;
	}else{
		$log['result'] = 0;
	}

	$log['content'] = $smiles;
	echo json_encode($log);
	exit;
}
add_action('wp_ajax_rcl_get_smiles_ajax','rcl_get_smiles_ajax');

add_filter('file_scripts_rcl','rcl_get_scripts_ajaxload_tabs');
function rcl_get_scripts_ajaxload_tabs($script){

	$ajaxdata = "type: 'POST', data: dataString, dataType: 'json', url: wpurl+'wp-admin/admin-ajax.php',";

	$script .= "
	function setAttr_rcl(prmName,val){
		var res = '';
		var d = location.href.split('#')[0].split('?');
		var base = d[0];
		var query = d[1];
		if(query) {
			var params = query.split('&');
			for(var i = 0; i < params.length; i++) {
				var keyval = params[i].split('=');
				if(keyval[0] != prmName) {
					res += params[i] + '&';
				}
			}
		}
		res += prmName + '=' + val;
		return base + '?' + res;
	}
	function get_ajax_content_tab(id){
		var lk = parseInt(jQuery('.wprecallblock').attr('id').replace(/\D+/g,''));
		var dataString = 'action=rcl_ajax_tab&id='+id+'&lk='+lk+'&locale='+jQuery('html').attr('lang');
		jQuery.ajax({
			".$ajaxdata."
			success: function(data){
				if(data['result']==100){
					jQuery('#lk-content').html(data['content']);
				} else {
					alert('Error');
				}
				rcl_preloader_hide();
			}
		});
		return false;
	}
	jQuery('.rcl-tab-button').on('click','.ajax_button',function(){
            if(jQuery(this).hasClass('active'))return false;
            rcl_preloader_show('#lk-content > div');
            var id = jQuery(this).parent().data('tab');
            jQuery('.rcl-tab-button .recall-button').removeClass('active');
            jQuery(this).addClass('active');
            var url = setAttr_rcl('tab',id);
            if(url != window.location){
                if ( history.pushState ){
                    window.history.pushState(null, null, url);
                }
            }
            get_ajax_content_tab(id);
            return false;
	});
	";
	return $script;
}

require_once("functions/rcl_addons.php");