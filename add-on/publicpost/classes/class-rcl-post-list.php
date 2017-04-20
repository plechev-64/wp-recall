<?php

class Rcl_Postlist {

    public $post_type;
    public $type_name;
    public $in_page = 24;
    public $offset;

    function __construct( $user_id, $post_type, $type_name ){

        $this->post_type = $post_type;
        $this->type_name = $type_name;
        $this->user_id = $user_id;
        $this->offset = 0;
    }

    function get_postlist_block(){

        $posts_block = '<div id="'.$this->post_type.'-postlist" class="">';
        $posts_block .= $this->get_postslist();
        $posts_block .= '</div>';
        
        return $posts_block;
    }

    function get_postslist_table(){

        global $wpdb,$post,$posts,$ratings;

        $ratings = array();
        $posts = array();

        $posts[] = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".$wpdb->base_prefix."posts WHERE post_author='%d' AND post_type='%s' AND post_status NOT IN ('trash','auto-draft') ORDER BY post_date DESC LIMIT $this->offset, ".$this->in_page,$this->user_id,$this->post_type));

        if(is_multisite()){
            $blog_list = get_blog_list( 0, 'all' );

            foreach ($blog_list as $blog) {
                $pref = $wpdb->base_prefix.$blog['blog_id'].'_posts';
                $posts[] = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".$pref." WHERE post_author='%d' AND post_type='%s' AND post_status NOT IN ('trash','auto-draft') ORDER BY post_date DESC LIMIT $this->offset, ".$this->in_page,$this->user_id,$this->post_type));
            }
        }

        if($posts[0]){

            $p_list = array();


            if(function_exists('rcl_format_rating')){

                foreach($posts as $postdata){
                    foreach($postdata as $p){
                        $p_list[] = $p->ID;
                    }
                }

                $rayt_p = rcl_get_rating_totals(array(
                        'object_id__in' => $p_list,
                        'rating_type' => $this->post_type
                    ));

                foreach((array)$rayt_p as $r){
                    if(!isset($r->object_id)) continue;
                    $ratings[$r->object_id] = $r->rating_total;
                }

            }

            if(rcl_get_template_path('posts-list-'.$this->post_type.'.php',__FILE__)) 
                $posts_block = rcl_get_include_template('posts-list-'.$this->post_type.'.php',__FILE__);
            else 
                $posts_block = rcl_get_include_template('posts-list.php',__FILE__);

            wp_reset_postdata();

        }else{
            $posts_block = '<p>'.$this->type_name.' '.__('has not yet been published','wp-recall').'</p>';
        }

        return $posts_block;
    }

    function get_postslist(){

        $page_navi = $this->page_navi();

        $posts_block = '<h3>'.__('Published','wp-recall').' "'.$this->type_name.'"</h3>';
        
        $posts_block .= $page_navi;
        $posts_block .= $this->get_postslist_table();
        $posts_block .= $page_navi;
        
        return $posts_block;
    }
    
    function page_navi(){
	global $wpdb;

	$count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(ID) FROM ".$wpdb->base_prefix."posts WHERE post_author='%d' AND post_type='%s' AND post_status NOT IN ('trash','auto-draft')",$this->user_id,$this->post_type));
	if(is_multisite()){
            $blog_list = get_blog_list( 0, 'all' );

            foreach ($blog_list as $blog) {
                $pref = $wpdb->base_prefix.$blog['blog_id'].'_posts';
                $count += $wpdb->get_var($wpdb->prepare("SELECT COUNT(ID) FROM ".$pref." WHERE post_author='%d' AND post_type='%s' AND post_status NOT IN ('trash','auto-draft')",$this->user_id,$this->post_type));
            }
	}
        
        if(!$count) return false;
	
        $rclnavi = new Rcl_PageNavi($this->post_type.'-navi', $count, array('in_page'=>$this->in_page));
        
        $this->offset = $rclnavi->offset;

	return $rclnavi->pagenavi();
    }
}
