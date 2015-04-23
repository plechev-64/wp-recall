<?php
add_action('init','add_recall_bar');
function add_recall_bar(){
	global $rcl_options;
	if( isset( $rcl_options['view_recallbar'] ) && $rcl_options['view_recallbar'] != 1 ) return false;
	register_nav_menus(array( 'recallbar' => __('Recallbar','rcl') ));									
}
add_action('wp_footer','add_recallbar_menu');
function add_recallbar_menu(){
    global $rcl_options;
    if(!isset($rcl_options['view_recallbar'])||$rcl_options['view_recallbar']!=1) return false;
    include_template_rcl('recallbar.php');		
}
function recallbar_right_side(){
    $right_li='';
    echo apply_filters('recallbar_right_content',$right_li);
}
add_filter('recallbar_right_content','get_default_bookmarks',10);
function get_default_bookmarks($links){
    $links .= '<li><a onclick="addfav()" href="javascript://">
                <i class="fa fa-plus"></i>'.__('В закладки').'</a>
            </li>
            <li><a onclick="jQuery(\'#favs\').slideToggle();return false;" href="javascript://">
                <i class="fa fa-bookmark"></i>'.__('Мои закладки').'</a>
            </li>';
    return $links;
}

