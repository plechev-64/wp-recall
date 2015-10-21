<?php
add_action('init','rcl_register_recallbar');
function rcl_register_recallbar(){
	global $rcl_options;
	if( isset( $rcl_options['view_recallbar'] ) && $rcl_options['view_recallbar'] != 1 ) return false;
	register_nav_menus(array( 'recallbar' => __('Recallbar','rcl') ));
}
add_action('wp_footer','rcl_recallbar_menu');
function rcl_recallbar_menu(){
    global $rcl_options;
    if(!isset($rcl_options['view_recallbar'])||$rcl_options['view_recallbar']!=1) return false;
    rcl_include_template('recallbar.php');
}
function rcl_recallbar_rightside(){
    $right_li='';
    echo apply_filters('recallbar_right_content',$right_li);
}

add_filter('recallbar_right_content','rcl_get_modified_bookmarks',20);
function rcl_get_modified_bookmarks($links){ // добавил span вокруг текста. Мог и регуляркой, но при изменении чего - она поломается
    $links = '<li><a onclick="addfav()" href="javascript://">
                <i class="fa fa-plus"></i><span>'.__('In bookmarks','rcl').'</span></a>
            </li>
            <li><a onclick="jQuery(\'#favs\').slideToggle();return false;" href="javascript://">
                <i class="fa fa-bookmark"></i><span>'.__('My bookmarks','rcl').'</span></a>
            </li>';
    return $links;
}

