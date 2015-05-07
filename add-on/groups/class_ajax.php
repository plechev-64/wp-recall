<?php
class Rcl_Ajax_Group{
	
	function __construct(){
		add_action('wp_ajax_remove_user_publics_group', array(&$this, 'remove_user_publics_group'));
		add_action('wp_ajax_group_ban_user', array(&$this, 'group_ban_user'));
		add_action('wp_ajax_all_users_group', array(&$this, 'rcl_get_users_group'));
		add_action('wp_ajax_edit_group', array(&$this, 'edit_group'));
	}
	
	function remove_user_publics_group(){
		global $wpdb,$user_ID;
		if(!$user_ID) exit;
		
		$userid = intval($_POST['user_id']);
		$group_id = intval($_POST['group_id']);
		if(!$group_id||!$userid) return false;
		
		$admin_id = $wpdb->get_var($wpdb->prepare("SELECT meta_value FROM ".$wpdb->prefix ."usermeta WHERE meta_key = 'admin_group_%d' AND user_id = '%d'",$group_id,$user_ID));
		if(!$admin_id) exit;

		$posts = $wpdb->get_results($wpdb->prepare("
			SELECT
				b.ID
			FROM
				`wp_term_relationships` as a
			INNER JOIN
				`wp_posts` as b on (b.ID = a.object_id)
			WHERE
				a.term_taxonomy_id = '%d' && b.post_author = '%d' && b.post_type = 'post-group'
		",$group_id,$userid));

		foreach($posts as $p){ $p_list[] = $p->ID; }

		$wpdb->get_results($wpdb->prepare("SELECT * FROM ".RCL_PREF."total_rayting_posts WHERE post_id IN (".rcl_format_in($p_list).")",$p_list));

		$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->prefix ."posts WHERE ID IN (".rcl_format_in($p_list).")",$p_list));
		$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->prefix ."comments WHERE comment_post_ID IN (".rcl_format_in($p_list).")",$p_list));

		$log['content']= __('Deleted user!','rcl').' '.rcl_get_button(__('To delete all publications','rcl'),'#',array('icon'=>false,'class'=>'remove-public-group','id'=>false,'attr'=>'user-data='.$userid.' group-data='.$group_id));
		$log['content']= __('Deleted user!','rcl');
		$log['int']=100;
		echo json_encode($log);
		exit;
	}
	function group_ban_user(){
		global $wpdb,$user_ID;
		if(!$user_ID) exit;
		
		$userid = intval($_POST['user_id']);
		$group_id = intval($_POST['group_id']);
		if(!$userid||!$group_id) return false;
		
		$admin_id = $wpdb->get_var($wpdb->prepare("SELECT meta_value FROM ".$wpdb->prefix ."usermeta WHERE meta_key = 'admin_group_%d' AND user_id = '%d'",$group_id,$user_ID));
		if(!$admin_id) exit;
		$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->prefix ."usermeta WHERE meta_key = 'user_group_%d' AND user_id = '%d'",$group_id,$userid));
		//$log['content']='Пользователь удален! <a href="#" user-data="'.$userid.'" group-data="'.$group_id.'" class="remove-public-group recall-button">Удалить все публикации</a>';
		$log['content']= __('Deleted user!','rcl');
		$log['int']=100;
		echo json_encode($log);
		exit;
	}

	function rcl_get_users_group(){
		global $wpdb;
		$page = intval($_POST['page']);
		$id_group = intval($_POST['id_group']);
		if(!$page) $page = 1;

		$group = new Rcl_Group($id_group);
		
		$block_users = '<div class="backform" style="display: block;"></div>
		<div class="float-window-recall" style="display:block;"><p align="right">'.rcl_get_button('Закрыть','#',array('icon'=>false,'class'=>'close_edit','id'=>false,'attr'=>false)).'</p><div>';
		$block_users .= $group->rcl_get_users_group($page);
		$block_users .= '</div></div>
		<script type="text/javascript"> jQuery(function(){ jQuery(".close_edit").click(function(){ jQuery(".group_content").empty(); }); }); </script>';
		$log['recall']=100;
		$log['block_users']=$block_users;
		echo json_encode($log);
		exit;
	}

	function edit_group(){
		global $wpdb,$user_ID;
		if(!$user_ID) exit;
		
		$new_name_group = sanitize_text_field($_POST['new_name_group']);
		$new_desc_group = sanitize_text_field($_POST['new_desc_group']);
		$id_group = intval($_POST['id_group']);
		
		$admin_id = $wpdb->get_var($wpdb->prepare("SELECT meta_value FROM ".$wpdb->prefix ."usermeta WHERE meta_key = 'admin_group_%d' AND user_id = '%d'",$id_group,$user_ID));
		if(!$admin_id){
			$options_gr = unserialize($wpdb->get_var($wpdb->prepare("SELECT option_value FROM ".RCL_PREF."groups_options WHERE group_id='%d'",$id_group)));
			if($options_gr['admin']==$user_ID) $admin_id = $options_gr['admin'];
		}

		if($admin_id){

			$taxonomy = 'groups';

			if($new_name_group){
			   $res = $wpdb->update( $wpdb->prefix.'terms',
					array( 'name' => $new_name_group ),
					array( 'term_id' => $id_group )
				);
			}
			if($new_desc_group){
			   $res = $wpdb->update(  $wpdb->prefix.'term_taxonomy',
					array( 'description' => $new_desc_group ),
					array( 'term_id' => $id_group )
				);
			}

			if($res) $log['int']=100;
			else $log['int']=200;

		}
		echo json_encode($log);
		exit;
	}
}
$Rcl_Ajax_Group = new Rcl_Ajax_Group();