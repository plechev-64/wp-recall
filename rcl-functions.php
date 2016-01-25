<?php
//добавляем контентный блок в указанное место личного кабинета
function rcl_block($place,$callback,$args=false){

    $data = array(
        'place'=>$place,
        'callback'=>$callback,
        'args'=>$args
    );

    $data = apply_filters('block_data_rcl',$data);

    if(is_admin())return false;

    if(isset($data['args']['gallery'])){
        rcl_bxslider_scripts();
    }

    if (!class_exists('Rcl_Blocks')) include_once plugin_dir_path( __FILE__ ).'functions/rcl_blocks.php';
    $block = new Rcl_Blocks($data);
}

//добавляем вкладку в личный кабинет
function rcl_tab($id,$callback,$name='',$args=false){
    global $rcl_tabs;
    
    $data = array(
        'id'=>$id,
        'callback'=>$callback,
        'name'=>$name,
        'args'=>$args
    );

    if($name) $data = apply_filters('tab_data_rcl',$data);
    
    $rcl_tabs[$id] = $data;

    if(is_admin())return false;

    if (!class_exists('Rcl_Tabs')) include_once plugin_dir_path( __FILE__ ).'functions/rcl_tabs.php';

    $tab = new Rcl_Tabs($data);
}

add_action('init','rcl_register_recallbar');
function rcl_register_recallbar(){
    global $rcl_options;
    if( isset( $rcl_options['view_recallbar'] ) && $rcl_options['view_recallbar'] != 1 ) return false;
    register_nav_menus(array( 'recallbar' => __('Recallbar','wp-recall') ));
}

function rcl_key_addon($path_parts){
    if(!isset($path_parts['dirname'])) return false;    
    return rcl_get_addon_dir($path_parts['dirname']);
}

function rcl_get_addon_dir($path){
    if(function_exists('wp_normalize_path')) 
        $path = wp_normalize_path($path);
    $dir = false;
    $ar_dir = explode('/',$path);
    if(!isset($ar_dir[1])) $ar_dir = explode('\\',$path);
    $cnt = count($ar_dir)-1;
    for($a=$cnt;$a>=0;$a--){if($ar_dir[$a]=='add-on'){$dir=$ar_dir[$a+1];break;}}
    return $dir;
}

//добавляем вкладку со списком публикаций хозяина ЛК указанного типа записей в личный кабинет
function rcl_postlist($id,$posttype,$name='',$args=false){
    global $rcl_options;
    if(!$rcl_options) $rcl_options = get_option('rcl_global_options');
    if($rcl_options['publics_block_rcl']!=1) return false;
    if (!class_exists('Rcl_Postlist')) include_once RCL_PATH .'add-on/publicpost/rcl_postlist.php';
    $plist = new Rcl_Postlist($id,$posttype,$name,$args);
}

function rcl_crop($filesource,$width,$height,$file){
    if (!class_exists('Rcl_Crop'))
        require_once(RCL_PATH.'functions/rcl_crop.php');

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

function rcl_addon_url($file,$path){
    if(function_exists('wp_normalize_path')) $path = wp_normalize_path($path);
    $array = explode('/',$path);
    $url = '';
	$content_dir = basename(content_url());
    foreach($array as $key=>$ar){
        if($array[$key]==$content_dir){
            $url = get_bloginfo('wpurl').'/'.$array[$key].'/';
            continue;
        }
        if($url){
            $url .= $ar.'/';
            if($array[$key-1]=='add-on') break;
        }
    }
    $url .= $file;
    return $url;
}

function rcl_addon_path($path){
    if(function_exists('wp_normalize_path')) $path = wp_normalize_path($path);
    $array = explode('/',$path);
    $addon_path = '';
    foreach($array as $key=>$ar){
        $addon_path .= $ar.'/';
        if(!$key) continue;
        if($array[$key-1]=='add-on')
           return $addon_path;
    }
    return false;
}

function rcl_path_to_url($path,$dir=false){
    if(!$dir) $dir = basename(content_url());
    if(function_exists('wp_normalize_path')) $path = wp_normalize_path($path);
    $array = explode('/',$path);
    $cnt = count($array);
    $url = '';
	$content_dir = $dir;
    foreach($array as $key=>$ar){
        if($array[$key]==$content_dir){
            $url = get_bloginfo('wpurl').'/'.$array[$key].'/';
            continue;
        }
        if($url){
            $url .= $ar;
            if($cnt>$key+1) $url .= '/';
        }
    }
    return $url;
}

function rcl_path_by_url($url,$dir=false){
    if(!$dir) $dir = basename(content_url());
    if(function_exists('wp_normalize_path')) $path = wp_normalize_path($path);
    $array = explode('/',$url);
    $cnt = count($array);
    $path = '';
    $content_dir = $dir;
    foreach($array as $key=>$ar){
        if($array[$key]==$content_dir){
            $path = $_SERVER['DOCUMENT_ROOT'].'/'.$array[$key].'/';
            continue;
        }
        if($path){
            $path .= $ar;
            if($cnt>$key+1) $path .= '/';
        }
    }
    return $path;
}

function rcl_format_url($url,$id_tab=null){
	$ar_perm = explode('?',$url);
	$cnt = count($ar_perm);
	if($cnt>1) $a = '&';
	else $a = '?';
	$url = $url.$a;
	if($id_tab) $url = $url.'tab='.$id_tab;
	return $url;
}

if (! function_exists('get_called_class')) :
    function get_called_class(){
        $arr = array(); 
        $arrTraces = debug_backtrace();
        foreach ($arrTraces as $arrTrace){
           if(!array_key_exists("class", $arrTrace)) continue;
           if(count($arr)==0) $arr[] = $arrTrace['class'];
           else if(get_parent_class($arrTrace['class'])==end($arr)) $arr[] = $arrTrace['class'];
        }
        return end($arr);
    }
endif;

function rcl_encode_post($array){
    return base64_encode(json_encode($array));
}

function rcl_decode_post($string){
    return json_decode(base64_decode($string));
}

function rcl_ajax_tab($post){
    global $user_LK,$rcl_tabs;

    $id_tab = sanitize_title($post->tab_id);
    $user_LK = intval($post->user_LK);

    if (!class_exists('Rcl_Tabs')) 
        include_once plugin_dir_path( __FILE__ ).'functions/rcl_tabs.php';
    
    $ajax = (!isset($rcl_tabs[$id_tab]['args']['ajax-load'])||!$rcl_tabs[$id_tab]['args']['ajax-load'])? 0: 1;
    
    if(!$ajax){
        
        return __('Error! Perhaps this addition does not support ajax loading','wp-recall');
        
    }else{
        
        if(!isset($rcl_tabs[$id_tab])) return false;
        
        $data = $rcl_tabs[$id_tab];
        
        $tab = new Rcl_Tabs($data);
        return $tab->add_tab('',$user_LK);
    }
    
    return array('error'=>__('Error','wp-recall').'!');

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
    rcl_update_scripts();
    rcl_minify_style();
}

//запрещаем доступ в админку
add_action('init','rcl_admin_access',1);
function rcl_admin_access(){
    global $current_user,$rcl_options;
    if(defined( 'DOING_AJAX' ) && DOING_AJAX) return;
    if(defined( 'IFRAME_REQUEST' ) && IFRAME_REQUEST) return;
    if(is_admin()){
        $rcl_options = get_option('rcl_global_options');
        get_currentuserinfo();
        $access = 7;
        if(isset($rcl_options['consol_access_rcl'])) $access = $rcl_options['consol_access_rcl'];

        if ( $current_user->user_level < $access ){
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

/* Удаление поста вместе с его вложениями*/
add_action('before_delete_post', 'rcl_delete_attachments_with_post');
function rcl_delete_attachments_with_post($postid){
    $attachments = get_posts( array( 'post_type' => 'attachment', 'posts_per_page' => -1, 'post_status' => null, 'post_parent' => $postid ) );
    if($attachments){
	foreach((array)$attachments as $attachment ){
            wp_delete_attachment( $attachment->ID, true );         
        }
    }
}

add_action('init','rcl_init_avatar_sizes');
function rcl_init_avatar_sizes(){
    global $rcl_avatar_sizes;

    $sizes = array(70,150,300);

    $rcl_avatar_sizes = apply_filters('rcl_avatar_sizes',$sizes);
    asort($rcl_avatar_sizes);

}

//Функция вывода своего аватара
add_filter('get_avatar','rcl_avatar_replacement', 20, 5);
function rcl_avatar_replacement($avatar, $id_or_email, $size, $default, $alt){

    $user_id = 0;

    if (is_numeric($id_or_email)){
        $user_id = $id_or_email;
    }elseif( is_object($id_or_email)){
        $user_id = $id_or_email->user_id;
    }elseif(is_email($id_or_email)){
        if ( $user = get_user_by('email', $id_or_email) ) $user_id = $user->ID;
    }

    if($user_id){

        $avatar_data = get_user_meta($user_id,'rcl_avatar',1);

        if($avatar_data){

            if(is_numeric($avatar_data)){
                    $image_attributes = wp_get_attachment_image_src($avatar_data);
                    if($image_attributes) $url = $image_attributes[0];
            }else if(is_string($avatar_data)){
                    $url = rcl_get_url_avatar($avatar_data,$user_id,$size);
            }

            if($url&&file_exists(rcl_path_by_url($url))){
                $avatar = "<img class='avatar' src='".$url."' alt='".$alt."' height='".$size."' width='".$size."' />";
            }

        }
    }

    if ( !empty($id_or_email->user_id)) $avatar = '<a height="'.$size.'" width="'.$size.'" href="'.get_author_posts_url($id_or_email->user_id).'">'.$avatar.'</a>';

    return $avatar;
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
if(!function_exists('sanitize_title_with_translit'))
    add_action('sanitize_title', 'rcl_sanitize_title_with_translit', 0);


function rcl_get_postmeta($post_id){

    $post = get_post($post_id);

    switch($post->post_type){
            case 'post':
                $id_form = ($post)?  get_post_meta($post->ID,'publicform-id',1): 1;
                $id_field = 'rcl_fields_post_'.$id_form;
            break;
            case 'products': $id_field = 'rcl_fields_products'; break;
            default: $id_field = 'rcl_fields_'.$post->post_type;
    }

    $get_fields = get_option($id_field);

    if(!$get_fields) return false;

    $show_custom_field = '';

    $cf = new Rcl_Custom_Fields();

    foreach((array)$get_fields as $custom_field){
        $slug = $custom_field['slug'];
        $value = get_post_meta($post_id,$slug,1);
        $show_custom_field .= $cf->get_field_value($custom_field,$value);
    }

    return $show_custom_field;

}

add_filter('author_link','rcl_author_link',999,2);
function rcl_author_link($link, $author_id){
    global $rcl_options;
    if(!isset($rcl_options['view_user_lk_rcl'])||$rcl_options['view_user_lk_rcl']!=1) return $link;
    $get = ! empty( $rcl_options['link_user_lk_rcl'] ) ? $rcl_options['link_user_lk_rcl'] : 'user';
    return add_query_arg( array( $get => $author_id ), get_permalink( $rcl_options['lk_page_rcl'] ) );	
}

function rcl_format_in($array){
    $separats = array_fill(0, count($array), '%d');
    return implode(', ', $separats);
}

function rcl_get_postmeta_array($post_id){
    global $wpdb;
    $mts = array();
    $metas = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".$wpdb->prefix."postmeta WHERE post_id='%d'",$post_id));
    if(!$metas) return false;
    foreach($metas as $meta){
        $mts[$meta->meta_key] = $meta->meta_value;
    }
    return $mts;
}

function rcl_setup_chartdata($mysqltime,$data){
    global $chartArgs;
    
    $day = date("Y.m.j", strtotime($mysqltime));
    $price = $data/1000;

    $chartArgs[$day]['summ'] += $price;
    $chartArgs[$day]['cnt'] += 1;
    $chartArgs[$day]['days'] = date("t", strtotime($mysqltime));
    
    return $chartArgs;
}

function rcl_get_chart($arr=false){
    global $chartData;

    if(!$arr) return false;
    
    //$titles = $chartData['data'][0];

    foreach($arr as $month=>$data){
        $cnt = (isset($data['cnt']))?$data['cnt']:0;
        $summ = (isset($data['summ']))?$data['summ']:0;
        $chartData['data'][] = array('"'.$month.'"', $cnt,$summ);
    }
    
    if(!$chartData) return false;
    
    //$array_pop($chartData['data']);
    //print_r($titles);
    krsort($chartData['data']);
    array_unshift($chartData['data'], array_pop($chartData['data']));
    
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

    rcl_verify_ajax_nonce();

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

function rcl_mail($email, $title, $text){
    add_filter('wp_mail_content_type',create_function('', 'return "text/html";'));
    $headers = 'From: '.get_bloginfo('name').' <noreply@'.$_SERVER['HTTP_HOST'].'>' . "\r\n";

    $text .= '<p><small>-----------------------------------------------------<br/>
    '.__('This letter was created automatically, no need to answer it.','wp-recall').'<br/>
    "'.get_bloginfo('name').'"</small></p>';
    wp_mail($email, $title, $text, $headers);
}

function rcl_multisort_array($array, $key, $type = SORT_ASC, $cmp_func = 'strcmp'){
    $GLOBALS['ARRAY_MULTISORT_KEY_SORT_KEY']  = $key;
    usort($array, create_function('$a, $b', '$k = &$GLOBALS["ARRAY_MULTISORT_KEY_SORT_KEY"];
        return ' . $cmp_func . '($a[$k], $b[$k]) * ' . ($type == SORT_ASC ? 1 : -1) . ';'));
    return $array;
}

function rcl_a_active($param1,$param2){
	if($param1==$param2) return 'filter-active';
}

function rcl_get_usernames($objects,$name_data){
	global $wpdb;

	if(!$objects||!$name_data) return false;

	foreach((array)$objects as $object){ $userslst[] = $object->$name_data; }

	$display_names = $wpdb->get_results($wpdb->prepare("SELECT ID,display_name FROM ".$wpdb->prefix."users WHERE ID IN (".rcl_format_in($userslst).")",$userslst));

	foreach((array)$display_names as $name){
		$names[$name->ID] = $name->display_name;
	}
	return $names;
}

function rcl_get_useraction($user_action=false){
	global $rcl_options,$rcl_userlk_action;

        if(!$user_action) $user_action = $rcl_userlk_action;

	$timeout = (isset($rcl_options['timeout'])&&$rcl_options['timeout'])? $rcl_options['timeout']*60: 600;

	$unix_time_action = strtotime(current_time('mysql'));
	$unix_time_user = strtotime($user_action);

	if(!$user_action)
		return $last_go = __('long ago','wp-recall');

	if($unix_time_action > $unix_time_user+$timeout){
                return human_time_diff($unix_time_user,$unix_time_action );
	} else {
		return false;
	}
}

function rcl_update_timeaction_user(){
	global $user_ID,$wpdb;

        if(!$user_ID) return false;

	$rcl_current_action = $wpdb->get_var($wpdb->prepare("SELECT time_action FROM ".RCL_PREF."user_action WHERE user='%d'",$user_ID));

	$last_action = rcl_get_useraction($rcl_current_action);

	if($last_action){

            $time = current_time('mysql');

            $res = $wpdb->update(
                                    RCL_PREF.'user_action',
                                    array( 'time_action' => $time ),
                                    array( 'user' => $user_ID )
                            );

            if(!isset($res)||$res==0){
                    $act_user = $wpdb->get_var($wpdb->prepare("SELECT COUNT(time_action) FROM ".RCL_PREF."user_action WHERE user ='%d'",$user_ID));
                    if($act_user==0){
                            $wpdb->insert(
                                    RCL_PREF.'user_action',
                                    array( 'user' => $user_ID,
                                    'time_action'=> $time )
                            );
                    }
                    if($act_user>1){
                            rcl_delete_user_action($user_ID);
                    }
            }
	}

	do_action('rcl_update_timeaction_user');

}

//удаляем данные об активности юзера при удалении
add_action('delete_user','rcl_delete_user_action');
function rcl_delete_user_action($user_id){
    global $wpdb;
    return $wpdb->query($wpdb->prepare("DELETE FROM ".RCL_PREF."user_action WHERE user ='%d'",$user_id));
}

function rcl_get_insert_image($image_id,$mime='image'){
    global $rcl_options;
    if($mime=='image'){
            $small_url = wp_get_attachment_image_src( $image_id, 'thumbnail' );
            $full_url = wp_get_attachment_image_src( $image_id, 'full' );
            if($rcl_options['default_size_thumb']) $sizes = wp_get_attachment_image_src( $image_id, $rcl_options['default_size_thumb'] );
            else $sizes = $small_url;
            $act_sizes = wp_constrain_dimensions($full_url[1],$full_url[2],$sizes[1],$sizes[2]);
            return '<a onclick="addfile_content(\'<a href='.$full_url[0].'><img height='.$act_sizes[1].' width='.$act_sizes[0].' class=aligncenter  src='.$full_url[0].'></a>\');return false;" href="#"><img src="'.$small_url[0].'"></a>';
    }else{
            return wp_get_attachment_link( $image_id, array(100,100),false,true );
    }
}

function rcl_get_button($ancor,$url,$args=false){
	$button = '<a href="'.$url.'" ';
	if(isset($args['attr'])) $button .= $args['attr'].' ';
	if(isset($args['id'])) $button .= 'id="'.$args['id'].'" ';
	$button .= 'class="recall-button ';
	if(isset($args['class'])) $button .= $args['class'];
	$button .= '">';
	if(isset($args['icon'])) $button .= '<i class="fa '.$args['icon'].'"></i>';
	$button .= '<span>'.$ancor.'</span>';
	$button .= '</a>';
	return $button;
}

//сортируем вкладки согласно настроек
add_filter('tab_data_rcl','rcl_edit_options_tab',5);
function rcl_edit_options_tab($tab){
    global $rcl_order_tabs;
    
    if(!$rcl_order_tabs) $rcl_order_tabs = get_option('rcl_order_tabs');

    if(isset($rcl_order_tabs['name'][$tab['id']])) 
        $tab['name'] = $rcl_order_tabs['name'][$tab['id']];

    if(isset($rcl_order_tabs['order'])){
        foreach($rcl_order_tabs['order'] as $order=>$key){
            if($key!=$tab['id']) continue;
                $tab['args']['order'] = $order+10;
        }
    }

    return $tab;
}

function rcl_add_balloon_menu($data,$args){
    if($data['id']!=$args['tab_id']||!$args['ballon_value']) return $data;
    $data['name'] = sprintf('%s <span class="rcl-menu-notice">%s</span>',$data['name'],$args['ballon_value']);
    return $data;
}

/*14.0.0*/
function rcl_verify_ajax_nonce(){
    if ( ! wp_verify_nonce( $_POST['ajax_nonce'], 'rcl-post-nonce' ) ){
        echo json_encode(array('error'=>'Проверка подписи не пройдена!'));
        exit;
    }
}