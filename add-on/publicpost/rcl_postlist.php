<?php

class Rcl_Postlist {

    public $id;
    public $name;
    public $posttype;
    public $start;

    /**
     * @param $id
     * @param $posttype
     * @param $name
     * @param array $args
     */
    function __construct( $id, $posttype, $name, $args = array() ){

        $this->id = $id;
        $this->posttype = $posttype;
        $this->name = $name;
        $this->start = 0;

        $order = ( isset( $args['order'] ) && ! empty( $args['order'] ) ) ? $args['order'] : 10;
        $this->class = ( isset( $args['class'] ) && ! empty( $args['class'] ) ) ? $args['class'] : 'fa-list';

        add_filter( 'posts_button_rcl', array( $this, 'add_postlist_button' ), $order, 2 );
        add_filter( 'posts_block_rcl', array( $this, 'add_postlist_block' ), $order, 2 );

        add_action('wp_ajax_rcl_posts_list', array( $this, 'rcl_posts_list'));
        add_action('wp_ajax_nopriv_rcl_posts_list', array( $this, 'rcl_posts_list'));
    }

    function add_postlist_button( $button ){
            $status = ! $button ? 'active' : '';
            $button .= ' <a href="#" id="posts_'.$this->id.'" class="child_block_button '.$status.'"><i class="fa '.$this->class.'"></i>'.$this->name.'</a> ';
            return $button;
    }

    function add_postlist_block($posts_block,$author_lk){
            if(!isset($posts_block)||!$posts_block) $status = 'active';
            else $status = '';
            $posts_block .= '<div class="posts_'.$this->id.'_block recall_child_content_block '.$status.'">';
            $posts_block .= $this->get_postslist($author_lk);
            $posts_block .= '</div>';
            return $posts_block;
    }

    function get_postslist_table( $author_lk ){

            global $wpdb,$post,$posts,$ratings;

            $ratings = array();
            $posts = array();

            //print_r($_POST);
            //exit;

            $start = $this->start.',';

            $posts[] = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".$wpdb->base_prefix."posts WHERE post_author='%d' AND post_type='%s' AND post_status NOT IN ('draft','auto-draft') ORDER BY post_date DESC LIMIT $start 20",$author_lk,$this->posttype));

            if(is_multisite()){
                $blog_list = get_blog_list( 0, 'all' );

                foreach ($blog_list as $blog) {
                        $pref = $wpdb->base_prefix.$blog['blog_id'].'_posts';
                        $posts[] = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".$pref." WHERE post_author='%d' AND post_type='%s' AND post_status NOT IN ('draft','auto-draft') ORDER BY post_date DESC LIMIT $start 20",$author_lk,$this->posttype));
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

					$rayt_p = rcl_get_ratings(array('object_id'=>$p_list,'rating_type'=>array($this->posttype)));

					foreach((array)$rayt_p as $r){
						if(!isset($r->object_id)) continue;
						$ratings[$r->object_id] = $r->rating_total;
					}

				}

				$posts_block = rcl_get_include_template('posts-list.php',__FILE__);

				wp_reset_postdata();

            }else{
                $posts_block = '<p>'.$this->name.' '.__('has not yet been published','rcl').'</p>';
            }

            return $posts_block;
    }

    function get_postslist($author_lk){

        $posts_list = $this->get_postslist_table( $author_lk );
        $posts_block = '<h3>'.__('Published','rcl').' '.$this->name.'</h3>';
        $posts_block .= rcl_get_ajax_pagenavi($author_lk,$this->posttype);
        $posts_block .= $posts_list;

        return $posts_block;
    }

    function rcl_posts_list(){

	$this->posttype = sanitize_text_field($_POST['type']);
	$this->start = intval($_POST['start']);
	$author_lk = intval($_POST['id_user']);

	$list = $this->get_postslist_table( $author_lk );

	$log['post_content']=$list;
	$log['recall']=100;

	echo json_encode($log);
        exit;
    }
}
