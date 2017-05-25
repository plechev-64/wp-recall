<?php

//регистрируем вкладку личного кабинета
function rcl_tab($tab_data,$deprecated_callback=false ,$deprecated_name='',$deprecated_args=false){
    global $rcl_tabs;
    
    if(!is_array($tab_data)){ //поддержка старого варианта регистрации вкладки
        
        if($deprecated_callback)
            _deprecated_argument( __FUNCTION__, '15.2.0' );
        
        $args_tab = array(
            'id'=> $tab_data, 
            'name'=> $deprecated_name,
            'content'=>array(
                array(
                    'id'=> $tab_data,
                    'name'=> $deprecated_name,
                    'icon'=> (isset($deprecated_args['class']))? $deprecated_args['class']: 'fa-cog',
                    'callback' => array(
                        'name'=> $deprecated_callback
                    )
                )
            )
        );
        
        if(isset($deprecated_args['cache']) && $deprecated_args['cache']){
            $args_tab['supports'][] = 'cache';
        }
        
        if(isset($deprecated_args['ajax-load']) && $deprecated_args['ajax-load']){
            $args_tab['supports'][] = 'ajax';
        }
        
        $args_tab['counter'] = (isset($deprecated_args['counter']))? $deprecated_args['counter']: null;
        $args_tab['public'] = (isset($deprecated_args['public']))? $deprecated_args['public']: 0;
        $args_tab['icon'] = (isset($deprecated_args['class']))? $deprecated_args['class']: 'fa-cog';
        $args_tab['output'] = (isset($deprecated_args['output']))? $deprecated_args['output']: 'menu';
        
        $tab_data = $args_tab;

    }
    
    if(!isset($tab_data['content'][0]['id']))
        $tab_data['content'][0]['id'] = $tab_data['id'];
    
    if(!isset($tab_data['content'][0]['name']))
        $tab_data['content'][0]['name'] = $tab_data['name'];
    
    if(!isset($tab_data['content'][0]['icon']))
        $tab_data['content'][0]['icon'] = $tab_data['icon'];

    $tab_data = apply_filters('rcl_tab',$tab_data);
    
    if(!$tab_data) return false;
    
    $rcl_tabs[$tab_data['id']] = $tab_data;
    
}

//регистрируем созданные произвольные вкладки
add_action('init','rcl_init_custom_tabs',10);
function rcl_init_custom_tabs(){
    
    $areas = rcl_get_area_options();
    
    foreach($areas as $area_id => $tabs){
        
        if(!$tabs) continue;
        
        foreach($tabs as $tab){
            
            if(isset($tab['default-tab'])) continue;
        
            $supports = (isset($tab['supports-tab']))? $tab['supports-tab']: array();

            $tab_data = array(
                'id'=> $tab['slug'], 
                'name'=> $tab['title'],
                'public'=> ($tab['public-tab'])? 1: 0,
                'icon'=> ($tab['icon'])? $tab['icon']: 'fa-cog',
                'output'=> $area_id,
                'custom-tab'=> true,
                'content'=> array(
                    array(
                        'id'=> 'subtab-1',
                        'name'=> $tab['title'],
                        'icon'=> ($tab['icon'])? $tab['icon']: 'fa-cog',
                        'callback'=> array(
                            'name'=>'rcl_custom_tab_content',
                            'args'=> array($tab['content'])
                        )
                    )
                )
            );

            if(in_array('cache', $supports)){
                $tab_data['supports'][] = 'cache';
            }

            if(in_array('ajax', $supports)){
                $tab_data['supports'][] = 'ajax';
            }
            
            if(in_array('dialog', $supports)){
                $tab_data['supports'][] = 'dialog';
            }

            rcl_tab($tab_data);
        
        }
        
    }

}

//регистрация дочерней вкладки
function rcl_add_sub_tab($tab_id,$subtab){
    global $rcl_tabs;   
    if(!isset($rcl_tabs[$tab_id])) return false;    
    $rcl_tabs[$tab_id]['content'][] = $subtab;
}

//вывод контента произвольной вкладки
add_filter( 'rcl_custom_tab_content', 'do_shortcode', 11 );
add_filter( 'rcl_custom_tab_content', 'wpautop', 10 );
function rcl_custom_tab_content($content){
    return apply_filters('rcl_custom_tab_content',stripslashes_deep($content));
}

//выводим все зарегистрированные вкладки в личном кабинете
add_action('wp','rcl_setup_tabs',10);
function rcl_setup_tabs(){
    global $rcl_tabs,$user_LK;

    if(is_admin()||!$user_LK) return false;

    $rcl_tabs = apply_filters('rcl_tabs',$rcl_tabs);
    
    do_action('rcl_setup_tabs');

    if(!$rcl_tabs) return false;
    
    if (!class_exists('Rcl_Tabs')) 
        require_once RCL_PATH.'classes/class-rcl-tabs.php';
    
    foreach($rcl_tabs as $tab){
        $Rcl_Tabs = new Rcl_Tabs($tab);
        $Rcl_Tabs->add_tab();
    }
    
}

//сортируем вкладки и изменяем их данные согласно настроек
add_filter('rcl_tabs','rcl_add_custom_tabs',5);
function rcl_add_custom_tabs($tabs){
    
    $areas = rcl_get_area_options();
    
    if(!$areas) return $tabs;
    
    foreach($tabs as $tab_id => $tab){
        
        $tabArea = (isset($tab['output']))? $tab['output']: 'menu';
        
        if(!isset($areas[$tabArea]) || !$areas[$tabArea]) continue;
        
        foreach($areas[$tabArea] as $k => $field){
            
            if($field['slug'] != $tab_id) continue;

            $tabs[$tab_id]['icon'] = $field['icon'];
            $tabs[$tab_id]['name'] = $field['title'];
            $tabs[$tab_id]['order'] = ++$k;

        }
        
    }
    
    return $tabs;
}

//выясняем какую вкладку ЛК показывать пользователю, 
//если ни одна не указана для вывода
add_filter('rcl_tabs','rcl_get_order_tabs',10);
function rcl_get_order_tabs($rcl_tabs){
    global $user_ID,$user_LK;
    
    if(isset($_GET['tab']) || !$rcl_tabs) return $rcl_tabs;
    
    $counter = array();
    $a = 10;
    foreach($rcl_tabs as $id => $data){
        if(isset($data['output'])&&$data['output']!='menu') continue;
        
        if(!isset($data['public'])||$data['public']!=1){
            if(!$user_ID||$user_ID!=$user_LK) continue;
        }
        
        $order = (isset($data['order']))? $data['order']: ++$a;
        $rcl_tabs[$id]['order'] = $order;
        $counter[$order] = $id;
    }
    
    ksort($counter);
    $id_first = array_shift($counter);
    $rcl_tabs[$id_first]['first'] = 1;
    
    return $rcl_tabs;
}

//регистрируем контентые блоки
function rcl_block($place,$callback,$args=false){
    global $rcl_blocks,$user_LK;
    
    $data = array(
        'place'=>$place,
        'callback'=>$callback,
        'args'=>$args
    );

    $data = apply_filters('block_data_rcl',$data);

    if($user_LK&&isset($data['args']['gallery'])){
        rcl_bxslider_scripts();
    }
    
    $rcl_blocks[$place][] = $data;
    
    $rcl_blocks = apply_filters('rcl_blocks',$rcl_blocks);
  
}

//формируем вывод зарегистрированных контентных блоков в личном кабинете
add_action('wp','rcl_setup_blocks');
function rcl_setup_blocks(){
    global $rcl_blocks,$user_LK;

    if(is_admin()||!$user_LK)return false;
    
    if(!$rcl_blocks) return false;

    if (!class_exists('Rcl_Blocks')) 
        require_once RCL_PATH.'classes/class-rcl-blocks.php';

    foreach($rcl_blocks as $place_id=>$blocks){
        if(!$blocks) continue;
        foreach($blocks as $data){
            $Rcl_Blocks = new Rcl_Blocks($data);
            $Rcl_Blocks->add_block();
        }
    }
    
    do_action('rcl_setup_blocks');
}

function rcl_is_office($user_id=null){
    global $rcl_office;
    
    if(isset($_POST['action'])&&$_POST['action']=='rcl_ajax'){
        $post = rcl_decode_post($_POST['post']);
        
        if($post->user_LK) 
            $rcl_office = $post->user_LK;
    }
    
    if($rcl_office){
        
        if(isset($user_id)){
            if($user_id==$rcl_office) return true;
            return false;
        }
        
        return true;       
    }
    
    return false;
}

//регистрируем список публикаций указанного типа записи
function rcl_postlist($id,$post_type,$name='',$args=false){
    global $rcl_options,$rcl_postlist;

    if(!isset($rcl_options['publics_block_rcl'])||$rcl_options['publics_block_rcl']!=1) return false;
    
    $rcl_postlist[$post_type] = array('id'=>$id,'post_type'=>$post_type,'name'=>$name,'args'=>$args);

}

//регистрация recalolbar`a
add_action('after_setup_theme','rcl_register_recallbar');
function rcl_register_recallbar(){
    global $rcl_options;
    if( isset( $rcl_options['view_recallbar'] ) && $rcl_options['view_recallbar'] != 1 ) return false;
    
    register_nav_menus(array( 'recallbar' => __('Recallbar','wp-recall') ));

}

function rcl_key_addon($path_parts){
    if(!isset($path_parts['dirname'])) return false;    
    return rcl_get_addon_dir($path_parts['dirname']);
}

//очищаем кеш плагина раз в сутки
add_action('rcl_cron_daily','rcl_clear_cache',20);
function rcl_clear_cache(){
    $rcl_cache = new Rcl_Cache();
    $rcl_cache->clear_cache();
}

//удаление определенного файла кеша
function rcl_delete_file_cache($string){
    $rcl_cache = new Rcl_Cache();       
    $rcl_cache->get_file($string);
    $rcl_cache->delete_file();
}

//кроп изображений
function rcl_crop($filesource,$width,$height,$file){
       
    $image = wp_get_image_editor( $filesource );
    
    if ( ! is_wp_error( $image ) ) { 
        $image->resize($width,$height,true);
        $image->save( $file );
    }
    
    return $image;
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

    $id_tab = rcl_sanitize_string($post->tab_id);
    $user_LK = intval($post->user_LK);
    
    $rcl_tabs = apply_filters('rcl_tabs',$rcl_tabs);
    
    if(!isset($rcl_tabs[$id_tab])) return false;

    if (!class_exists('Rcl_Tabs')) 
        require_once RCL_PATH.'classes/class-rcl-tabs.php';
    
    $ajax = (in_array('ajax',$rcl_tabs[$id_tab]['supports']))? 1: 0;
    
    if(!$ajax){
        
        return __('Error! Perhaps this add-on does not support ajax loading','wp-recall');
        
    }else{
        
        $subtab_id = (isset($post->subtab_id))? $post->subtab_id: false;

        do_action('rcl_setup_tabs');
        
        $data = $rcl_tabs[$id_tab];
        
        $data['first'] = 1;

        $tab = new Rcl_Tabs($data);
        
        $content = $tab->get_tab($user_LK,$subtab_id);
        
        return $content;
        
    }
    
    return array('error'=>__('Error','wp-recall').'!');

}

function rcl_get_tab_button($tab_id, $user_id = false){
    global $rcl_tabs,$user_LK;
   
    if(!isset($rcl_tabs[$tab_id])) return false;
    
    if(!$user_id) $user_id = $user_LK;
   
    if (!class_exists('Rcl_Tabs'))
        require_once RCL_PATH.'classes/class-rcl-tabs.php';
   
    $data = $rcl_tabs[$tab_id];
    $tab = new Rcl_Tabs($data);
   
    return $tab->get_tab_button($user_id);
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

//запрещаем доступ в админку
add_action('init','rcl_admin_access',1);
function rcl_admin_access(){

    if(defined( 'DOING_AJAX' ) && DOING_AJAX) return;
    if(defined( 'IFRAME_REQUEST' ) && IFRAME_REQUEST) return;

    if(is_admin()){
        
        global $user_ID;

        $access = rcl_check_access_console();

        if ( $access ) 
            return true;
            
        if(isset($_POST['short'])&&intval($_POST['short'])==1||isset($_POST['fetch'])&&intval($_POST['fetch'])==1){
            
            return true;
            
        }else{
            
            if(!$user_ID) return true;
            
            wp_redirect('/'); exit;
            
        }
       
    }
}

function rcl_check_access_console(){
    global $current_user,$rcl_options;
     
    if(!$rcl_options)
        $rcl_options = get_option('rcl_global_options');

    $need_access = (isset($rcl_options['consol_access_rcl']))? $rcl_options['consol_access_rcl']: 7;

    if($current_user->user_level){
        $access = ( $current_user->user_level < $need_access )? false: true;
    }else{
        $access = ( isset($current_user->allcaps['level_'.$need_access]) && $current_user->allcaps['level_'.$need_access] == 1 )? true: false;
    }
    
    return $access;
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

//регистрируем размеры миниатюра загружаемого аватара пользователя
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
    global $rcl_user;
    
    $user_id = 0;
    $avatar_data = false;
    
    if($rcl_user && $rcl_user->ID == $id_or_email){
        
        $user_id = $rcl_user->ID;
        
        if(isset($rcl_user->avatar_data) && $rcl_user->avatar_data){
            $avatar_data = $rcl_user->avatar_data;
        }
        
    }else{
        
        if (is_numeric($id_or_email)){
            $user_id = $id_or_email;
        }elseif( is_object($id_or_email)){
            $user_id = $id_or_email->user_id;
        }elseif(is_email($id_or_email)){
            if ( $user = get_user_by('email', $id_or_email) ) $user_id = $user->ID;
        }
        
    }

    if($user_id){

        if(!$avatar_data)
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
    
    if(!$rcl_avatar_sizes) return $url_image;

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

function rcl_sanitize_string($title) {
    
    $title = mb_strtolower($title);
    
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
    
    $cachekey = json_encode(array('rcl_get_postmeta_array',$post_id));
    $cache = wp_cache_get( $cachekey );
    if ( $cache )
        return $cache;
    
    $mts = array();
    $metas = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".$wpdb->prefix."postmeta WHERE post_id='%d'",$post_id));
    if(!$metas) return false;
    foreach($metas as $meta){
        $mts[$meta->meta_key] = $meta->meta_value;
    }
    
     wp_cache_add( $cachekey, $mts );
    
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

    foreach($arr as $month=>$data){
        $cnt = (isset($data['cnt']))?$data['cnt']:0;
        $summ = (isset($data['summ']))?$data['summ']:0;
        $chartData['data'][] = array('"'.$month.'"', $cnt,$summ);
    }
    
    if(!$chartData) return false;

    krsort($chartData['data']);
    array_unshift($chartData['data'], array_pop($chartData['data']));
    
    return rcl_get_include_template('chart.php');
}

//добавляем уведомление в личном кабинете
function rcl_notice_text($text,$type='warning'){
    if(is_admin())return false;
    if (!class_exists('Rcl_Notify'))
        include_once RCL_PATH.'functions/notify.php';
    $block = new Rcl_Notify($text,$type);
}

class Rcl_Form_Fields{

	public $type;
	public $placeholder;
	public $label;
	public $name;
        public $id;
        public $class;
	public $value;
	public $maxlength;
	public $checked;
	public $required;

	function get_field($args){
            $this->type = (isset($args['type']))? $args['type']: 'text';
            $this->id = (isset($args['id']))? $args['id']: false;
            $this->class = (isset($args['class']))? $args['class']: false;
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
                case 'radio': 
                    $content = '<span class="rcl-'.$this->type.'-box">';
                    $content .= sprintf('%s<label for="%s" class="block-label">%s</label>',$field,$this->id,$this->label);
                    $content .= '</span>';
                    break;
                case 'checkbox': 
                    $content = '<span class="rcl-'.$this->type.'-box">';
                    $content .= sprintf('%s<label for="%s" class="block-label">%s</label>',$field,$this->id,$this->label); 
                    $content .= '</span>';
                    break;
                default: $content = sprintf('<label class="block-label">%s</label>%s',$this->label,$field);
            }
            
            return $content;
	}

	function get_type_field(){

            switch($this->type){
                case 'textarea': $field = sprintf('<textarea name="%s" placeholder="%s" '.$this->required().' %s>%s</textarea>',$this->name,$this->placeholder,$this->id,$this->value); break;
                default: $field = sprintf('<input type="%s" name="%s" value="%s" placeholder="%s" maxlength="%s" '.$this->get_class().' '.$this->selected().' '.$this->required().' id="%s">',$this->type,$this->name,$this->value,$this->placeholder,$this->maxlength,$this->id);
            }

            if($this->label) $field = $this->add_label($field);

            return $field;

	}
        
        function get_class(){
            
            if($this->class)
                return 'class="'.$this->class.'"';
            
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
    global $wpsmiliestrans;
    
    if(!$wpsmiliestrans) return false;

    $smiles = '<div class="rcl-smiles" data-area="'.$id_area.'">';
        $smiles .= '<i class="fa fa-smile-o" aria-hidden="true"></i>';
        $smiles .= '<div class="rcl-smiles-list">
                        <div class="smiles"></div>
                    </div>';
    $smiles .= '</div>';

    return $smiles;
}

function rcl_get_smiles_ajax(){
    global $wpsmiliestrans;

    rcl_verify_ajax_nonce();

    $content = array();
    
    $smilies = array();
    foreach($wpsmiliestrans as $emo=>$smilie){
        $smilies[$smilie] = $emo;
    }

    foreach($smilies as $smilie=>$emo){
        if(!$emo) continue;
        $content[] = str_replace( 'style="height: 1em; max-height: 1em;"', '', convert_smilies( $emo ) );
    }

    $log['result'] = ($content)? 1: 0;
    $log['content'] = implode('',$content);
    echo json_encode($log);
    exit;
}
add_action('wp_ajax_rcl_get_smiles_ajax','rcl_get_smiles_ajax');

function rcl_mail($email, $title, $text, $from = false, $attach = false){
    
    $from_name = (isset($from['name']))? $from['name']: get_bloginfo('name');
    $from_mail = (isset($from['email']))? $from['email']: 'noreply@'.$_SERVER['HTTP_HOST'];
    
    add_filter('wp_mail_content_type',create_function('', 'return "text/html";'));
    $headers = 'From: '.$from_name.' <'.$from_mail.'>' . "\r\n";

    $text .= '<p><small>-----------------------------------------------------<br/>
    '.__('This letter was created automatically, no need to answer it.','wp-recall').'<br/>
    "'.get_bloginfo('name').'"</small></p>';
    wp_mail($email, $title, $text, $headers, $attach);
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

function rcl_human_time_diff($time_action){
    $unix_current_time = strtotime(current_time('mysql'));
    $unix_time_action = strtotime($time_action);
    return human_time_diff($unix_time_action,$unix_current_time );
}

function rcl_update_timeaction_user(){
    global $user_ID,$wpdb;

    if(!$user_ID) return false;

    $rcl_current_action = rcl_get_time_user_action($user_ID);

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

function rcl_get_button($ancor,$url,$args=false){
    $button = '<a href="'.$url.'" ';
    if(isset($args['attr'])&&$args['attr']) $button .= $args['attr'].' ';
    if(isset($args['id'])&&$args['id']) $button .= 'id="'.$args['id'].'" ';
    $button .= 'class="recall-button ';
    if(isset($args['class'])&&$args['class']) $button .= $args['class'];
    $button .= '">';
    if(isset($args['icon'])&&$args['icon']) $button .= '<i class="fa '.$args['icon'].'"></i>';
    $button .= '<span>'.$ancor.'</span>';
    $button .= '</a>';
    return $button;
}

function rcl_add_balloon_menu($data,$args){
    if($data['id']!=$args['tab_id']) return $data;
    $data['name'] = sprintf('%s <span class="rcl-menu-notice">%s</span>',$data['name'],$args['ballon_value']);
    return $data;
}

/*14.0.0*/
function rcl_verify_ajax_nonce(){
    if(!defined( 'DOING_AJAX' ) || !DOING_AJAX) return false;
    if ( ! wp_verify_nonce( $_POST['ajax_nonce'], 'rcl-post-nonce' ) ){
        echo json_encode(array('error'=>__('Signature verification failed','wp-recall').'!'));
        exit;
    }
}

function rcl_office_class(){
    global $rcl_options,$active_addons,$user_LK,$user_ID;
    
    $class = array('wprecallblock','rcl-office');
    
    $active_template = get_site_option('rcl_active_template');
    
    if($active_template){
        if(isset($active_addons[$active_template])) 
            $class[] = 'office-'.strtolower(str_replace(' ','-',$active_addons[$active_template]['template']));
    }
    
    if($user_ID){       
        $class[] = ($user_LK==$user_ID)? 'visitor-master': 'visitor-guest';
    }else{
        $class[] = 'visitor-guest';
    }
    
    $class[] = (isset($rcl_options['buttons_place'])&&$rcl_options['buttons_place']==1)? "vertical-menu":"horizontal-menu";
    
    echo 'class="'.implode(' ',$class).'"';
}

function rcl_template_support($support){  
    
    //if(!rcl_is_office()) return false;
    
    switch($support){
        case 'avatar-uploader': 
            include_once 'functions/supports/uploader-avatar.php';
            break;
        case 'cover-uploader': 
            include_once 'functions/supports/uploader-cover.php';
            break;
        case 'modal-user-details':
            include_once 'functions/supports/modal-user-details.php';
            break;
    }
}

function rcl_is_user_role($user_id,$role){
    $user_data = get_userdata( $user_id );
    $roles = $user_data->roles;
    if(!$roles) return false;
    $current_role = array_shift($roles);
    
    if(is_array($role)){
        if(in_array($current_role,$role)) return true;
    }else{
        if($current_role==$role) return true;
    }

    return false;
}

function rcl_is_register_open(){
    $users_can = apply_filters('rcl_users_can_register',get_option('users_can_register'));
    return $users_can;
}

/*16.0.0*/
function rcl_update_profile_fields($user_id){

    require_once(ABSPATH . "wp-admin" . '/includes/image.php');
    require_once(ABSPATH . "wp-admin" . '/includes/file.php');
    require_once(ABSPATH . "wp-admin" . '/includes/media.php');

    $profileFields = rcl_get_profile_fields();
    
    if($profileFields){
        
        $defaultFields = array(
            'user_email',
            'description',
            'user_url',
            'first_name',
            'last_name',
            'display_name',
            'primary_pass',
            'repeat_pass'
        );
        
        $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
        
        foreach($profileFields as $field){
            
            if(isset($field['field_select']))
                $field['values'] = $field['field_select'];
            
            $field = apply_filters('rcl_pre_update_profile_field', $field);

            if(!$field || !$field['slug']) continue;

            $slug = $field['slug'];
            
            $value = (isset($_POST[$slug]))? $_POST[$slug]: false;
            
            if(in_array($slug,$defaultFields)){
                
                if($slug == 'repeat_pass') continue;

                if($slug == 'primary_pass' && $value){
                    
                    if($value != $_POST['repeat_pass']) continue;
                    
                    $slug = 'user_pass';
                    
                }
                
                if($slug == 'user_email'){
                    
                    if(!$value) continue;
                    
                    $currentEmail = get_the_author_meta('user_email',$user_id);
                    
                    if($currentEmail == $value) continue;
                    
                }

                wp_update_user( array( 'ID' => $user_id, $slug => $value ) );
                
                continue;
                
            }
            
            if($field['admin']==1&&!is_admin()){
                if(get_user_meta($user_id, $slug, $value))
                    continue;
            }

            if($field['type']=='checkbox'){
                
                $vals = array();
                
                if(is_array($value)){
                    
                    $vals = array();

                    foreach($value as $val){
                        if(in_array($val,$field['values']))
                            $vals[] = $val;
                        
                    }
                    
                }
                
                if($vals){
                    update_user_meta($user_id, $slug, $vals);
                }else{
                    delete_user_meta($user_id, $slug);
                }
                
            }else if($field['type']=='file'){

                $attach_id = rcl_upload_meta_file($field,$user_id);
                
                if($attach_id) 
                    update_user_meta($user_id, $slug, $attach_id);

            }else{

                if($value){
                    
                    update_user_meta($user_id, $slug, $value);
                    
                }else{
                    
                    if(get_user_meta($user_id, $slug, $value)) 
                        delete_user_meta($user_id, $slug, $value);
                    
                }
            }
        }
    }

    do_action('rcl_update_profile_fields',$user_id);

}

/*16.0.0*/
function rcl_get_profile_fields($args = false){
    
    $fields = get_option( 'rcl_profile_fields' );
    
    $fields = apply_filters('rcl_profile_fields',$fields);
    
    $profileFields = array();
    
    foreach($fields as $k => $field){
        
        if(isset($args['exclude']) && in_array($field['slug'],$args['exclude'])){
            
            continue;
            
        }
        
        if(isset($field['field_select'])){
            
            $field['field_select'] = rcl_edit_old_option_fields($field['field_select']);
            
        }
        
        $profileFields[] = $field;
        
    }
    
    return $profileFields;
    
}

function rcl_get_area_options(){
    
    $areas = array(
        'menu'      =>  get_option('rcl_fields_area-menu'),
        'counters'  =>  get_option('rcl_fields_area-counters'),
        'actions'   =>  get_option('rcl_fields_area-actions'),
    );
    
    return $areas;
}

function rcl_add_log($title, $data = false, $force = false){
    global $rcl_options;
    
    if(!$force && (!isset($rcl_options['rcl-log']) || !$rcl_options['rcl-log'])) return false;
    
    $RclLog = new Rcl_Log();
    
    $RclLog->insert_title($title);
    
    if($data)
        $RclLog->insert_log($data);
    
}

function rcl_get_addon_paths(){
    
    $paths = array(
        RCL_TAKEPATH.'add-on',
        RCL_PATH.'add-on'
    );
    
    $paths = apply_filters('rcl_addon_paths',$paths);
    
    return $paths;
    
}

function rcl_get_tab_permalink($user_id,$tab_id,$subtab_id = false){
    return rcl_format_url(get_author_posts_url($user_id),$tab_id,$subtab_id);
}