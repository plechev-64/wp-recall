<?php

class Rcl_Postlist {

    public $name;
    public $id;
    public $post_type;
    public $in_page = 30;
    public $offset;
    public $subtab;
    public $actived;

    /**
     * @param $post_type
     * @param $name
     * @param array $args
     */
    function __construct( $id, $post_type, $name, $args = array() ){

        $this->id = $id;
        $this->post_type = $post_type;
        $this->name = $name;
        $this->offset = 0;
        $this->subtab = (isset($_GET['subtab']))? $_GET['subtab']: 'post';
        $this->actived = ($this->subtab==$this->post_type)? true: false;

        $order = ( isset( $args['order'] ) && ! empty( $args['order'] ) ) ? $args['order'] : 10;
        $this->class = ( isset( $args['class'] ) && ! empty( $args['class'] ) ) ? $args['class'] : 'fa-list';

        add_filter( 'posts_button_rcl', array( $this, 'add_postlist_button' ), $order, 2 );
        
        if($this->actived)
            add_filter( 'posts_block_rcl', array( $this, 'add_postlist_block' ), $order, 2 );
    }

    function add_postlist_button( $button ){
        global $user_LK;
        $status = $this->actived ? 'active' : '';
        $button .= rcl_get_button($this->name,rcl_format_url(get_author_posts_url($user_LK), 'publics').'&subtab='.$this->post_type,array('class'=>$status,'icon'=>$this->class));
        return $button;
    }

    function add_postlist_block($posts_block,$author_lk){

        $id = 'posts_'.$this->id.'_block';

        $posts_block .= '<div id="'.$id.'" class="'.$id.'">';
        $posts_block .= $this->get_postslist($author_lk);
        $posts_block .= '</div>';
        return $posts_block;
    }

    function get_postslist_table( $author_lk ){

        global $wpdb,$post,$posts,$ratings;

        $ratings = array();
        $posts = array();

        $offset = $this->offset.',';

        $posts[] = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".$wpdb->base_prefix."posts WHERE post_author='%d' AND post_type='%s' AND post_status NOT IN ('draft','auto-draft') ORDER BY post_date DESC LIMIT $offset ".$this->in_page,$author_lk,$this->post_type));

        if(is_multisite()){
            $blog_list = get_blog_list( 0, 'all' );

            foreach ($blog_list as $blog) {
                $pref = $wpdb->base_prefix.$blog['blog_id'].'_posts';
                $posts[] = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".$pref." WHERE post_author='%d' AND post_type='%s' AND post_status NOT IN ('draft','auto-draft') ORDER BY post_date DESC LIMIT $offset ".$this->in_page,$author_lk,$this->post_type));
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

                $rayt_p = rcl_get_ratings(array('object_id'=>$p_list,'rating_type'=>array($this->post_type)));

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
            $posts_block = '<p>'.$this->name.' '.__('has not yet been published','wp-recall').'</p>';
        }

        return $posts_block;
    }

    function get_postslist($author_lk){

        $page_navi = $this->page_navi($author_lk,$this->post_type);

        $posts_block = '<h3>'.__('Published','wp-recall').' "'.$this->name.'"</h3>';
        
        $posts_block .= $page_navi;
        $posts_block .= $this->get_postslist_table( $author_lk );
        $posts_block .= $page_navi;
        
        return $posts_block;
    }
    
    function page_navi($userid,$post_type){
	global $wpdb;

	$count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(ID) FROM ".$wpdb->base_prefix."posts WHERE post_author='%d' AND post_type='%s' AND post_status NOT IN ('draft','auto-draft')",$userid,$post_type));
	if(is_multisite()){
            $blog_list = get_blog_list( 0, 'all' );

            foreach ($blog_list as $blog) {
                $pref = $wpdb->base_prefix.$blog['blog_id'].'_posts';
                $count += $wpdb->get_var($wpdb->prepare("SELECT COUNT(ID) FROM ".$pref." WHERE post_author='%d' AND post_type='%s' AND post_status NOT IN ('draft','auto-draft')",$userid,$post_type));
            }
	}
        
        if(!$count) return false;
	
        $rclnavi = new Rcl_PageNavi($post_type.'-navi', $count);
        
        $this->offset = $rclnavi->offset;

	return $rclnavi->pagenavi();
    }
}
