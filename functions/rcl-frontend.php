<?php
add_action('rcl_account_menu','rcl_buttons',10);
function rcl_buttons(){
    global $user_LK;
    echo apply_filters( 'the_button_wprecall', '', $user_LK );
}

add_action('rcl_account_tabs','rcl_tabs',10);
function rcl_tabs(){
    global $user_LK;
    echo apply_filters( 'the_block_wprecall', '', $user_LK);
}

add_action('rcl_account_before','rcl_before',10);
function rcl_before(){
    global $user_LK;
    echo apply_filters( 'rcl_before_lk', '', $user_LK );
}

add_action('rcl_account_after','rcl_after',10);
function rcl_after(){
    global $user_LK;
    echo apply_filters( 'rcl_after_lk', '', $user_LK );
}

add_action('rcl_account_header','rcl_header',10);
function rcl_header(){
    global $user_LK;
    echo apply_filters('rcl_header_lk','',$user_LK);
}

add_action('rcl_account_sidebar','rcl_sidebar',10);
function rcl_sidebar(){
    global $user_LK;
    echo apply_filters('rcl_sidebar_lk','',$user_LK);
}

add_action('rcl_account_content','rcl_content',10);
function rcl_content(){
    global $user_LK;
    echo apply_filters('rcl_content_lk','',$user_LK);
}

add_action('rcl_account_footer','rcl_footer',10);
function rcl_footer(){
    global $user_LK;
    echo apply_filters('rcl_footer_lk','',$user_LK);
}

//добавляем стили колорпикера в хеадер
add_action('wp_head','rcl_add_colorpicker_style',100);
function rcl_add_colorpicker_style(){
    global $rcl_options;

    $rgb = (isset($rcl_options['primary-color'])&&$rcl_options['primary-color'])? rcl_hex2rgb($rcl_options['primary-color']): array(76, 140, 189);
    
    $color_style = '<style>
    a.recall-button,
    .recall-button.rcl-upload-button,
    input[type="submit"].recall-button,
    input[type="submit"] .recall-button,
    input[type="button"].recall-button,
    input[type="button"] .recall-button,
    a.recall-button:hover,
    .recall-button.rcl-upload-button:hover,
    input[type="submit"].recall-button:hover,
    input[type="submit"] .recall-button:hover,
    input[type="button"].recall-button:hover,
    input[type="button"] .recall-button:hover{
        background: rgb('.$rgb[0].', '.$rgb[1].', '.$rgb[2].');
    }
    a.recall-button.active,
    a.recall-button.active:hover,
    a.recall-button.filter-active,
    a.recall-button.filter-active:hover,
    a.data-filter.filter-active,
    a.data-filter.filter-active:hover,
    #lk-conteyner .rcl-more-link{
        background: rgba('.$rgb[0].', '.$rgb[1].', '.$rgb[2].', 0.4);
    } 
    .rcl_preloader i{
        color: rgb('.$rgb[0].', '.$rgb[1].', '.$rgb[2].');
    }
    p.status-user-rcl::before{
        border-color: transparent transparent transparent rgb('.$rgb[0].', '.$rgb[1].', '.$rgb[2].');   
    }
    .ballun-status p.status-user-rcl{
        border: 1px solid rgb('.$rgb[0].', '.$rgb[1].', '.$rgb[2].');
    }
    </style>';
    
    if(isset($rcl_options['rcb_color'])&&$rcl_options['rcb_color']){

        $lcp_hex = $rcl_options['primary-color'];               // достаем оттуда наш цвет
        list($r, $g, $b) = sscanf($lcp_hex, "#%02x%02x%02x");   // разбиваем строку на нужный нам формат
        $rs = round($r * 0.45);
        $gs = round($g * 0.45);
        $bs = round($b * 0.45);

        // $r $g $b - родные цвета от кнопки
        // $rs $gs $bs - темный оттенок от кнопки
        $color_style .= '<style>
        #recallbar_new.my_recallbar {
        background:rgba('.$rs.','.$gs.','.$bs.',0.85);}
        #recallbar_new .rcb_menu,#recallbar_new .pr_sub_menu {
        border-top: 2px solid rgba('.$r.','.$g.','.$b.',0.8);}
        #recallbar_new .rcb_right_menu:hover {
        border-left: 2px solid rgba('.$r.','.$g.','.$b.',0.8);}
        #recallbar_new .rcb_right_menu .fa-ellipsis-h {
        color: rgba('.$r.','.$g.','.$b.',0.8);}
        #recallbar_new .rcb_nmbr {
        background: rgba('.$r.','.$g.','.$b.',0.8);}
        #recallbar_new .rcb_menu,#recallbar_new .pr_sub_menu,#recallbar_new .rcb_menu .sub-menu {
        background: rgba('.$rs.','.$gs.','.$bs.',0.95);}
        .rcb_icon div.rcb_hiden span {
        background: rgba('.$rs.','.$gs.','.$bs.',0.9);
        border-top: 2px solid rgba('.$r.','.$g.','.$b.',0.8);}
        </style>';
        
        if (is_admin_bar_showing()){ 
            // 68 = 32 админбар + 36 реколлбар
            // на 782 пикселях 82 = 46 + 36 соответственно отступ
            $color_style .= '<style>
            html {margin-top:68px !important;}
            * html body {margin-top:68px !important;}
            #recallbar_new{margin-top:32px;}
            @media screen and (max-width:782px) {
            html {margin-top: 82px !important;}
            * html body {margin-top: 82px !important;}
            #recallbar_new{margin-top:46px;}
            }
            </style>';
        } else {
            $color_style .= '<style>
            html {margin-top:36px !important;}
            * html body {margin-top:36px !important;}
            </style>';
        }
    
    }
    
    // удаляем пробелы, переносы, табуляцию
    $color_style =  preg_replace('/ {2,}/','',str_replace(array("\r\n", "\r", "\n", "\t"), '', $color_style));
    
    $key = (isset($rcl_options['minify_css'])&&$rcl_options['minify_css'])? 'rcl-header': 'rcl-primary';
    
    echo $color_style;

}

add_action('wp_recall_init','init_user_lk',2);
function init_user_lk(){
    global $wpdb,$user_LK,$rcl_userlk_action,$rcl_options,$user_ID;

    $user_LK = false;
    $userLK = false;
    $get='user';
    $nicename = false;
    
    if(isset($rcl_options['link_user_lk_rcl'])&&$rcl_options['link_user_lk_rcl']!='') $get = $rcl_options['link_user_lk_rcl'];
    if(isset($_GET[$get])) $userLK = $_GET[$get];

    if(!$userLK){
        if($rcl_options['view_user_lk_rcl']==1){
                $post_id = url_to_postid($_SERVER['REQUEST_URI']);
                if($rcl_options['lk_page_rcl']==$post_id) $user_LK = $user_ID;
        }else {
            if(isset($_GET['author'])) $user_LK = $_GET['author'];
            else{
                $url = (isset($_SERVER['SCRIPT_URL']))? $_SERVER['SCRIPT_URL']: $_SERVER['REQUEST_URI'];
                $url = preg_replace('/\?.*/', '', $url);
                $url_ar = explode('/',$url);
                foreach($url_ar as $key=>$u){
                    if($u!='author') continue;
                    $nicename = $url_ar[$key+1];
                    break;
                }
                if(!$nicename) return false;
                $user_LK = $wpdb->get_var($wpdb->prepare("SELECT ID FROM ".$wpdb->prefix."users WHERE user_nicename='%s'",$nicename));
            }
        }
    }else{
	$user_LK = $userLK;
    }

    if($user_LK){
        $rcl_userlk_action = rcl_get_time_user_action($user_LK);
    }
}

add_action('wp_footer','rcl_popup_contayner');
function rcl_popup_contayner(){
    echo '<div id="rcl-overlay"></div>
		  <div id="rcl-popup"></div>';
}

add_filter('wp_footer', 'rcl_footer_url');
function rcl_footer_url(){	
	if(is_front_page()&&!is_user_logged_in()){
            if(get_option('rcl_footer_link')==1)
                echo '<p class="plugin-info">'.__('The site works using the functionality of the plugin','wp-recall').'  <a target="_blank" href="https://codeseller.ru/">Wp-Recall</a></p>';
        }
}

function rcl_get_author_block(){
    global $post;

    $content = "<div id=block_author-rcl>";
    $content .= "<h3>".__('Author of publication','wp-recall')."</h3>";

    if(function_exists('rcl_add_userlist_follow_button')) add_filter('rcl_user_description','rcl_add_userlist_follow_button',90);

    $content .= rcl_get_userlist(array(
            'template' => 'rows',
            'include' => $post->post_author,
            'filter' => 0,
            'data'=>'rating_total,description,posts_count,user_registered,comments_count'
            //'orderby'=>'time_action'
        ));

    if(function_exists('rcl_add_userlist_follow_button')) remove_filter('rcl_user_description','rcl_add_userlist_follow_button',90);

    $content .= "</div>";

    return $content;
}

function rcl_get_time_user_action($user_id){
    global $wpdb;
    
    $cachekey = json_encode(array('rcl_get_time_user_action',$user_id));
    $cache = wp_cache_get( $cachekey );
    if ( $cache )
        return $cache;
    
    $action = $wpdb->get_var($wpdb->prepare("SELECT time_action FROM ".RCL_PREF."user_action WHERE user='%d'",$user_id));

    wp_cache_add( $cachekey, $action );
    
    return $action;
}

function rcl_get_miniaction($action,$user_id=false){
    global $wpdb;
    if(!$action) $action = rcl_get_time_user_action($user_id);
    $last_action = rcl_get_useraction($action);
    $class = (!$last_action&&$action)?'online':'offline';

    $content = '<div class="status_author_mess '.$class.'">';
    if(!$last_action&&$action) $content .= '<i class="fa fa-circle"></i>';
    else $content .= __('not online','wp-recall').' '.$last_action;
    $content .= '</div>';

    return $content;
}

//заменяем ссылку автора комментария на ссылку его ЛК
add_filter('get_comment_author_url', 'rcl_get_link_author_comment');
function rcl_get_link_author_comment($href){
    global $comment;
    if($comment->user_id==0) return $href;
    $href = get_author_posts_url($comment->user_id);
    return $href;
}

add_action('wp_head','rcl_hidden_admin_panel');
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

add_action('init','rcl_banned_user_redirect');
function rcl_banned_user_redirect(){
    global $user_ID;
    if(!$user_ID) return false;
    $user_data = get_userdata( $user_ID );
    $roles = $user_data->roles;
    $role = array_shift($roles);
    if($role=='banned') wp_die(__('Congratulations! You have been banned.','wp-recall'));
}

add_filter('the_content','rcl_message_post_moderation');
function rcl_message_post_moderation($cont){
global $post;
    if($post->post_status=='pending'){
        $mess = '<h3 class="pending-message">'.__('Publication pending approval!','wp-recall').'</h3>';
        $cont = $mess.$cont;
    }
    return $cont;
}

function rcl_action(){
    global $rcl_userlk_action;
    $last_action = rcl_get_useraction($rcl_userlk_action);
    $class = (!$last_action)? 'online': 'offline';
    $status = '<div class="status_user '.$class.'"><i class="fa fa-circle"></i></div>';
    if($last_action) $status .= __('not online','wp-recall').' '.$last_action;
    echo $status;
}

function rcl_avatar($size=120){
    global $user_LK; $after='';
    echo '<div id="rcl-contayner-avatar">';
	echo '<span class="rcl-user-avatar">'.get_avatar($user_LK,$size).'</span>';
	echo apply_filters('after-avatar-rcl',$after,$user_LK);
	echo '</div>';

}

function rcl_status_desc(){
    global $user_LK;
    $desc = get_the_author_meta('description',$user_LK);
    if($desc) echo '<div class="ballun-status">'
        //. '<span class="ballun"></span>'
        . '<p class="status-user-rcl">'.nl2br(esc_textarea($desc)).'</p>'
        . '</div>';
}

function rcl_username(){
    global $user_LK;
    echo get_the_author_meta('display_name',$user_LK);
}

function rcl_notice(){
    $notify = '';
    $notify = apply_filters('notify_lk',$notify);
    if($notify) echo '<div class="notify-lk">'.$notify.'</div>';
}

function rcl_sort_gallery($attaches,$key,$user_id=false){
    global $user_ID;

    if(!$attaches) return false;
    if(!$user_id) $user_id = $user_ID;
    $cnt = count($attaches);
    $v=$cnt+10;
    foreach($attaches as $attach){
        $id = str_replace($key.'-'.$user_id.'-','',$attach->post_name);
        if(!is_numeric($id)||$id>100) $id = $v++;
        if(!$id) $id = 0;
        foreach($attach as $k=>$att){
                $gallerylist[(int)$id][$k]=$attach->$k;
        }
    }

    $b=0;
    $cnt = count($gallerylist);
    for($a=0;$b<$cnt;$a++){
        if(!isset($gallerylist[$a])) continue;
        $new[$b] = $gallerylist[$a];
        $b++;
    }
    for($a=$cnt-1;$a>=0;$a--){$news[]=(object)$new[$a];}

    return $news;
}

function rcl_hex2rgb($hex) {
   $hex = str_replace("#", "", $hex);

   if(strlen($hex) == 3) {
      $r = hexdec(substr($hex,0,1).substr($hex,0,1));
      $g = hexdec(substr($hex,1,1).substr($hex,1,1));
      $b = hexdec(substr($hex,2,1).substr($hex,2,1));
   } else {
      $r = hexdec(substr($hex,0,2));
      $g = hexdec(substr($hex,2,2));
      $b = hexdec(substr($hex,4,2));
   }
   $rgb = array($r, $g, $b);
   //return implode(",", $rgb); // returns the rgb values separated by commas
   return $rgb; // returns an array with the rgb values
}

function rcl_bar_add_icon($id_icon,$args){
    global $rcl_bar,$rcl_options;
    if( !isset( $rcl_options['view_recallbar'] ) || $rcl_options['view_recallbar'] != 1 ) return false;
    $rcl_bar['icons'][$id_icon] = $args;
}
function rcl_bar_add_menu_item($id_item,$args){
    global $rcl_bar,$rcl_options;
    if( !isset( $rcl_options['view_recallbar'] ) || $rcl_options['view_recallbar'] != 1 ) return false;
    $rcl_bar['menu'][$id_item] = $args;
}

add_action('rcl_bar_setup','rcl_setup_bar_default_data',10);
function rcl_setup_bar_default_data(){
    global $rcl_user_URL;
    
    if(!is_user_logged_in()) return false;

    rcl_bar_add_menu_item('account-link',
        array(                
            'url'=>$rcl_user_URL,
            'icon'=>'fa-user',
            'label'=>__('В личный кабинет','wp-recall')
        )
    );
    
    if(current_user_can('activate_plugins')){
        rcl_bar_add_menu_item('admin-link',
            array(                
                'url'=>admin_url(),
                'icon'=>'fa-external-link-square',
                'label'=>__('В админку','wp-recall')
            )
        );
    }
    
    rcl_bar_add_menu_item('logout-link',
        array(                
            'url'=>wp_logout_url('/'),
            'icon'=>'fa-sign-out',
            'label'=>__('Выход','wp-recall')
        )
    );
}

add_action('rcl_bar_print_icons','rcl_print_bar_icons',10);
function rcl_print_bar_icons(){
    global $rcl_bar;
    if(!isset($rcl_bar['icons'])||!$rcl_bar['icons']) return false;
    
    if(is_array($rcl_bar['icons'])){
        
        $rcl_bar_icons = apply_filters('rcl_bar_icons',$rcl_bar['icons']);
        
        foreach($rcl_bar_icons as $icon){
            if(!isset($icon['icon'])) continue;
            
            $class = (isset($icon['class']))? $icon['class']: '';
        
            echo '<div class="rcb_icon '.$class.'">';
            
            if(isset($icon['url'])):
                echo '<a href="'.$icon['url'].'">';
            endif;
            
                echo '<i class="fa '.$icon['icon'].'" aria-hidden="true"></i>';
                echo '<div class="rcb_hiden"><span>';
                
                if(isset($icon['label'])):
                    echo $icon['label'];
                endif;
                
                echo '</span></div>';
                
            if(isset($icon['url'])):
                echo '</a>';
            endif;
            
            if(isset($icon['counter'])):
                echo '<div class="rcb_nmbr">'.$icon['counter'].'</div>';
            endif;

            echo '</div>';
        }
    }
}

add_action('rcl_bar_print_menu','rcl_print_bar_right_menu',10);
function rcl_print_bar_right_menu(){
    global $rcl_bar;
    if(!isset($rcl_bar['menu'])||!$rcl_bar['menu']) return false;
    
    if(is_array($rcl_bar['menu'])){
        
        $rcl_bar_menu = apply_filters('rcl_bar_menu',$rcl_bar['menu']);
        
        foreach($rcl_bar_menu as $icon){
            if(!isset($icon['url'])) continue;
            
            echo '<div class="rcb_line">';
            echo '<a href="'.$icon['url'].'">';
            
            if(isset($icon['icon'])):
                echo '<i class="fa '.$icon['icon'].'" aria-hidden="true"></i>';
            endif;

            echo '<span>'.$icon['label'].'</span>';
            echo '</a>';
            echo '</div>';
        }
    }
}