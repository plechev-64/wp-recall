<?php

add_action('wp_footer','rcl_recallbar_menu',3);
function rcl_recallbar_menu(){
    rcl_include_template('recallbar.php');
}

add_action('wp','rcl_bar_setup',10);
function rcl_bar_setup(){
    do_action('rcl_bar_setup');
}

add_action('rcl_bar_setup','rcl_setup_bar_default_data',10);
function rcl_setup_bar_default_data(){
    global $rcl_user_URL;
    
    if(!is_user_logged_in()) return false;

    rcl_bar_add_menu_item('account-link',
        array(                
            'url'=>$rcl_user_URL,
            'icon'=>'fa-user',
            'label'=>__('To personal account','wp-recall')
        )
    );
    
    if(current_user_can('activate_plugins')){
        rcl_bar_add_menu_item('admin-link',
            array(                
                'url'=>admin_url(),
                'icon'=>'fa-external-link-square',
                'label'=>__('To admin area','wp-recall')
            )
        );
    }

}

add_action('rcl_bar_print_icons','rcl_print_bar_icons',10);
function rcl_print_bar_icons(){
    global $rcl_bar;
    if(!isset($rcl_bar['icons'])||!$rcl_bar['icons']) return false;
    
    if(is_array($rcl_bar['icons'])){
        
        $rcl_bar_icons = apply_filters('rcl_bar_icons',$rcl_bar['icons']);
        
        foreach($rcl_bar_icons as $id_icon=>$icon){
            if(!isset($icon['icon'])) continue;
            
            $class = (isset($icon['class']))? $icon['class']: '';
        
            echo '<div id="'.$id_icon.'" class="rcb_icon '.$class.'">';
            
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
                echo '<div class="rcb_nmbr '.($icon['counter']>0?'counter_not_null':'').'">'.$icon['counter'].'</div>';
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

add_filter('rcl_inline_styles','rcl_bar_add_inline_styles',10,2);
function rcl_bar_add_inline_styles($styles,$rgb){
    
    if (is_admin_bar_showing()){ 
        // 68 = 32 админбар + 36 реколлбар
        // на 782 пикселях 82 = 46 + 36 соответственно отступ
        $styles .= 'html {margin-top:68px !important;}
        * html body {margin-top:68px !important;}
        #recallbar{margin-top:32px;}
        @media screen and (max-width:782px) {
        html {margin-top: 82px !important;}
        * html body {margin-top: 82px !important;}
        #recallbar{margin-top:46px;}
        }';
    } else {
        $styles .= 'html {margin-top:36px !important;}
        * html body {margin-top:36px !important;}';
    }
    
    if(rcl_get_option('rcb_color')){
        
        list($r, $g, $b) = $rgb;

        // разбиваем строку на нужный нам формат
        $rs = round($r * 0.45);
        $gs = round($g * 0.45);
        $bs = round($b * 0.45);

        // $r $g $b - родные цвета от кнопки
        // $rs $gs $bs - темный оттенок от кнопки
        $styles .= '#recallbar {
        background:rgba('.$rs.','.$gs.','.$bs.',0.85);}
        #recallbar .rcb_menu,#recallbar .pr_sub_menu {
        border-top: 2px solid rgba('.$r.','.$g.','.$b.',0.8);}
        #recallbar .rcb_right_menu:hover {
        border-left: 2px solid rgba('.$r.','.$g.','.$b.',0.8);}
        #recallbar .rcb_right_menu .fa-ellipsis-h {
        color: rgba('.$r.','.$g.','.$b.',0.8);}
        #recallbar .rcb_nmbr {
        background: rgba('.$r.','.$g.','.$b.',0.8);}
        #recallbar .rcb_menu,#recallbar .pr_sub_menu,#recallbar .rcb_menu .sub-menu {
        background: rgba('.$rs.','.$gs.','.$bs.',0.95);}
        .rcb_icon div.rcb_hiden span {
        background: rgba('.$rs.','.$gs.','.$bs.',0.9);
        border-top: 2px solid rgba('.$r.','.$g.','.$b.',0.8);}';

    }
    
    return $styles;
}

