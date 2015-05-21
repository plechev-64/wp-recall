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
            if(!$posts_block) $status = 'active';
            else $status = '';
            $posts_block .= '<div class="posts_'.$this->id.'_block recall_child_content_block '.$status.'">';
            $posts_block .= $this->get_postslist($author_lk);
            $posts_block .= '</div>';
            return $posts_block;
    }

    function get_postslist_table( $author_lk ){

            global $wpdb;

            $rayt = array();

            $start .= $this->start.',';

            $posts = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".$wpdb->prefix."posts WHERE post_author='%d' AND post_type='%s' AND post_status NOT IN ('draft','auto-draft') ORDER BY post_date DESC LIMIT $start 20",$author_lk,$this->posttype));

            $posts_block = '';

            if($posts){
                $p_list = '';
                    $rayting = false;
                    if(function_exists('rcl_get_rating_block')){
                            $b=0;
                            foreach((array)$posts as $p){ $p_list[] = $p->ID; }
                            $rayt_p = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".RCL_PREF."total_rayting_posts WHERE post_id IN (".rcl_format_in($p_list).")",$p_list));
                            foreach((array)$rayt_p as $r){
                                if(!isset($r->post_id)) continue;
                                $rayt[$r->post_id] = $r->total;
                            }
                            $rayting = true;
                    }

                    $posts_block .= '<table class="publics-table-rcl">
                    <tr>
                        <td>'.__('Date','rcl').'</td>
                        <td>'.__('Title','rcl').'</td>
                        <td>'.__('Status','rcl').'</td>';
                            //if($user_ID==$author_lk) $posts_block .= '<td>Ред.</td>';
                            $posts_block .= '</tr>';
                    foreach($posts as $post){
                            if($post->post_status=='pending') $status = '<span class="pending">'.__('on approval','rcl').'</span>';
                            elseif($post->post_status=='trash') $status = '<span class="pending">'.__('deleted','rcl').'</span>';
                            else $status = '<span class="publish">'.__('publish','rcl').'</span>';
                            $posts_block .= '<tr>
                            <td width="50">'.mysql2date('d.m.y', $post->post_date).'</td>'
                                    . '<td>';

                            $content = ($post->post_status=='trash')? $post->post_title: '<a target="_blank" href="'.$post->guid.'">'.$post->post_title.'</a>';

                            if($rayting) {
                                $rtng = (isset($rayt[$post->ID]))? $rayt[$post->ID]: 0;
                                $content .= ' '.rcl_get_rating_block($rtng);
                            }
                            $content = apply_filters('content_postslist',$content);
                            $posts_block .= $content;
                            $posts_block .= '</td>'
                                    . '<td>'.$status.'</td>';
                            //if($user_ID==$author_lk) $posts_block .= '<td><a target="_blank" href="'.get_permalink($rcl_options['public_form_page_rcl']).'?rcl-post-edit='.$post->ID.'">Ред.</a></td>';
                            $posts_block .= '</tr>';
                    }
                    $posts_block .= '</table>';
            }else{
                    $posts_block .= '<h3>'.$this->name.' '.__('has not yet been published','rcl').'</h3>';
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
