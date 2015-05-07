<?php

class Group_Private{

    function __construct($query){
        return $this->chek_access($query);
    }

    function chek_access($query){
	global $wpdb,$user_ID,$post; $groups = false;
	if($query->is_search){
		/*foreach((array)$wp_query->posts as $k=>$p){
			if($p->post_type=='post-group'){
				//$wp_query->posts[$k]->post_content = close_content_closed_group();
				unset($wp_query->posts[$k]);
			}
		}
		foreach((array)$wp_query->post as $k=>$p){
			if($p->post_type=='post-group'){
				unset($wp_query->post[$k]);
			}
		}
		print_r($wp_query);*/
	}
	if($query->is_tax&&isset($query->query['groups'])){
		$term = get_term_by('slug', $query->query['groups'], 'groups');
		$term_id = $term->term_id; $groups = true;
	}
	if($query->is_single&&$query->query['post_type']=='post-group'&&$query->query['name']){
		if(!$post) $post_id = $wpdb->get_var($wpdb->prepare("SELECT ID FROM ".$wpdb->prefix."posts WHERE post_name='%s'",$query->query['name']));
		else $post_id = $post->ID;
		$cur_terms = get_the_terms( $post_id, 'groups' );
		foreach((array)$cur_terms as $cur_term){
			if($cur_term->parent!=0) continue;
			$term_id = $cur_term->term_id; break;
		}
		$groups = true;
	}
	if($groups){

		if(isset($_GET['group-tag'])&&$_GET['group-tag']!=''){
			if(!$_GET['search-p']){
				$query->set( 'groups', $_GET['group-tag'] );
			}else{
				wp_redirect(get_term_link( (int)$_GET['search-p'], 'groups' ).'/?group-tag='.$_GET['group-tag']);exit;
			}

		}
		if(isset($_GET['group-page'])&&$_GET['group-page']!=''){
			 $query->set( 'posts_per_page', 1 );
		}

		$options_gr = unserialize($wpdb->get_var($wpdb->prepare("SELECT option_value FROM ".RCL_PREF."groups_options WHERE group_id='%d'",$term_id)));

		if(isset($options_gr['private'])&&$options_gr['private']==1){

			if($user_ID) $in_group = get_the_author_meta('user_group_'.$term_id,$user_ID);
			if(!$in_group&&$options_gr['admin']!=$user_ID){
				if($query->is_single){
					add_filter('the_content',array(&$this,'close_content'),999);
					add_filter('comment_text',array(&$this,'close_comment'),999);
					add_filter('comment_form_default_fields',array(&$this,'close_comment_fields'),999);
					add_filter('comment_form_field_comment',array(&$this,'close_commentform'),999);
				}else{
					$query->set('post_type', 'groups');
				}
			}
		}
	}
	return $query;
    }

    function close_comment_fields(){
            return false;
    }
    function close_commentform(){
            return '<style>.form-submit input[type="submit"]{display:none;}</style>
            <h3 align="center" style="color:red;">'.__('To be able to post comments you must be a member of this group','rcl').'</h3>';
    }
    function close_content(){
            return '<h3 align="center" style="color:red;">'.__('Access to content is closed privacy settings','rcl').'</h3>';
    }
    function close_comment(){
            return '<p>'.__('(Comment hidden privacy settings)','rcl').'</p>';
    }
}
