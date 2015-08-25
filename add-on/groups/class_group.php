<?php
class Rcl_Group{

	public $curent_term;
	public $term_id;
	public $requests;
	public $in_group;
	public $imade_id;
	public $admin_id;
	public $users_group;
	public $gallery_group;
	public $options_gr;
	public $group_id;
	public $users_count;

	public function __construct($grid=false) {
		global $wpdb,$group_id,$options_gr;
		if($grid) $group_id = $grid;
		$this->group_id = $group_id;
		$this->term_id = $this->group_id;
		$this->options_gr = $options_gr;
		$this->users_count = rcl_get_userscount_group($this->term_id);
		add_filter('options_group_rcl',array(&$this,'get_primary_options'));
		add_filter('content_group_rcl',array(&$this,'private_title'));
		add_filter('after_header_group_rcl',array(&$this,'edit_notify'));
		if(isset($_GET['group-page'])&&$_GET['group-page']=='users') add_filter('footer_group_rcl',array(&$this,'rcl_get_users_group'));
    }

	function init_variables(){
		global $wpdb,$user_ID;

		//add_filter('the_excerpt', 'rcl_add_edit_post_button');

		$this->curent_term = get_term_by('ID', $this->group_id, 'groups');

		if($req = get_option('request_group_access_'.$this->term_id)) $this->requests = unserialize($req);

		if($user_ID) $this->in_group = get_user_meta($user_ID,'user_group_'.$this->term_id,1);

		if ( isset($_POST['delete-group-rcl'])&&$user_ID ){
			if( !wp_verify_nonce( $_POST['_wpnonce'], 'delete-group-rcl' ) ) return false;
				wp_delete_term( $this->term_id, 'groups');
				$this->imade_id = get_option('image_group_'.$this->term_id);
				rcl_delete_users_group($this->term_id, $this->term_id, 'groups');
				echo '<h2 class="aligncenter" style="color:red;">'.__('Your group has been removed!','rcl').'</h2>';
				$this->group_id = false;
				return false;
		}

		$this->imade_id = $this->options_gr['avatar'];
		$this->admin_id = $this->options_gr['admin'];

		if(!$this->admin_id) $this->admin_id = rcl_get_admin_group_by_meta($this->term_id);
		if(!$this->imade_id) $this->imade_id = get_option('image_group_'.$this->term_id);

		$this->users_group = $wpdb->get_results($wpdb->prepare("SELECT * FROM $wpdb->usermeta WHERE meta_key = '%s' ORDER BY RAND() LIMIT 10",'user_group_'.$this->term_id));

		$args = array(
			'post_type'=>'post-group',
			'numberposts'=>15,
			'fields'=>'ids',
			'tax_query' => array(
				array(
					'taxonomy' => 'groups',
					'field' => 'id',
					'terms' => $this->term_id
				)
			)
		);

		$p_list = explode(',',implode(',',get_posts( $args )));

		$this->gallery_group = $wpdb->get_results($wpdb->prepare("SELECT ID,post_parent FROM ".$wpdb->prefix ."posts WHERE post_type = 'attachment' AND post_parent IN (".rcl_format_in($p_list).") ORDER BY ID DESC LIMIT 12",$p_list));
	}

	function get_post_request(){
		global $user_ID,$wpdb,$options_gr;

		if ( isset($_POST['update-group-rcl'])&&$user_ID ){

			if( !wp_verify_nonce( $_POST['_wpnonce'], 'update-options-group-rcl' ) ) return false;

			if($this->options_gr) $opt = true;

			$_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);

			if(!$_POST['private']&&$this->options_gr['private']) unset($this->options_gr['private']);
			if(!$_POST['images']&&$this->options_gr['images']) unset($this->options_gr['images']);
			if(!$_POST['users']&&$this->options_gr['users']) unset($this->options_gr['users']);
			if(!$_POST['no-post']&&$this->options_gr['no-post']) unset($this->options_gr['no-post']);

			foreach($_POST as $p => $data ){
				if($data){
					if($p=='event'){
						if(!$_POST['event']['active']) $_POST['event']['active'] = 0;
						foreach($_POST['event'] as $key=>$date){
							$this->options_gr['event'][$key] = $date;
						}
					}else{
						$this->options_gr[$p] = $data;
					}
				}else if($this->options_gr[$data]){
					unset($this->options_gr[$data]);
				}
			}

			$options_gr = $this->options_gr;

			$options_ser = serialize($this->options_gr);

			if($opt){
				$wpdb->update(
					RCL_PREF.'groups_options',
					array('option_value'=>$options_ser),
					array('group_id'=>$this->group_id)
				);
			}else{
				$wpdb->insert(
					RCL_PREF.'groups_options',
					array('group_id'=>$this->group_id,'option_value'=>$options_ser)
				);
			}
		}
	}

	function get_name(){
		return $this->curent_term->name;
	}

	function class_gr($class = ''){
		global $user_ID;
		if($user_ID&&$user_ID==$this->admin_id) $class .= ' edit';
		return $class;
	}

	function get_images(){
		if($this->imade_id){
			$src = wp_get_attachment_image_src( $this->imade_id, array(150,150));
			return '<img width="150" height="150" src="'.$src[0].'" class="avatar_gallery_group"><div class="edit-avatar"></div>';
		}else{
			return '<img src="'.plugins_url('img/empty-avatar.jpg', __FILE__).'" class="avatar_gallery_group"><div class="edit-avatar"></div>';
		}
	}

	function get_admin($text = ''){
		$text .= ': <a href="'.get_author_posts_url($this->admin_id).'">'.get_the_author_meta('display_name',$this->admin_id).'</a>';
		return $text;
	}

	function edit_notify($cont){
		global $user_ID;
		if($user_ID&&$user_ID==$this->admin_id){
			$cont .= '<p align="right" style="clear:both;"><small style="color:red">'
                                . __('To edit the title, description or pictures of a group just click on this element','rcl')
                                . '</small></p>';
		}
		return $cont;
	}

	function get_desc(){
		$desc = term_description( $this->term_id, 'groups' );
		if($desc) return $desc;
		else return '<b>'. __('Add a description of the group','rcl'). '</b>';
	}

	function get_primary_options($opt){
                $private = false;
                $no_post = false;
                $users = false;
                $images = false;
                $tags = false;
                if(isset($this->options_gr['private'])) $private = $this->options_gr['private'];
                if(isset($this->options_gr['no-post'])) $no_post = $this->options_gr['no-post'];
                if(isset($this->options_gr['users'])) $users = $this->options_gr['users'];
                if(isset($this->options_gr['images'])) $images = $this->options_gr['images'];
                if(isset($this->options_gr['tags'])) $tags = $this->options_gr['tags'];

		$opt .= '<p>'. __('The status of the group','rcl'). ' <input type="checkbox" class="status_groups" '.checked($private,1,false).' name="private" value="1"> '. __('Private group','rcl'). '</p>

		<p><input type="checkbox" class="status_groups" '.checked($no_post,1,false).' name="no-post" value="1"> - '. __('ban the publication team members','rcl'). '</p>

		<p><input type="checkbox" class="status_groups" '.checked($users,1,false).' name="users" value="1"> - '. __('show group members','rcl'). '</p>

		<p><input type="checkbox" class="status_groups" '.checked($images,1,false).' name="images" value="1"> - '. __('to show the last image of the group','rcl'). '</p>

		<p>'. __('Category group (split by comma)','rcl'). ':<br>
		<textarea name="tags" rows="3" cols="60">'.$tags.'</textarea>
		</p>';
		return $opt;
	}

	function get_options(){
		global $user_ID;
		if($user_ID&&$user_ID==$this->admin_id){
                    $opt = '';
			return '<div class="public-post-group">
				<a href="#">'. __('Group settings','rcl'). '</a>
			</div>
			<div class="options-group-rcl public_block" style="clear:both;display:none;">
				<a class="close-public-form" href="#">'. __('To close the settings','rcl'). '</a>
                                <h3>'. __('Group settings','rcl'). ':</h3>
				<form action="" method="post">
					<input type="hidden" name="group_id" value="'.$this->term_id.'">'
					. apply_filters('options_group_rcl',$opt)
					. wp_nonce_field('update-options-group-rcl','_wpnonce',true,false).'
					<p style="text-align:right;">
						<input type="submit" name="update-group-rcl" value="'. __('Save settings','rcl'). '" class="recall-button">
					</p>
				</form>
			</div>';

		}
	}

	function admin_block(){
		return $this->button_del_group()
		. $this->requests_users();
	}

	function button_del_group(){

			$args = array(
				'numberposts' => 1,
				'fields' => 'ids',
				'post_type' => 'post-group',
				'tax_query' => array(
					array(
						'taxonomy' => 'groups',
						'field' => 'id',
						'terms' => $this->term_id
					)
				)
			);
			$post_gr = get_posts($args);
			if(!$post_gr) return '<div class="add-user-group">
				<form action="" method="post">
					'.wp_nonce_field('delete-group-rcl','_wpnonce',true,false).'
					<input type="submit" name="delete-group-rcl" value="'. __('Delete group','rcl'). '" onsubmit="return confirm(\''. __('Are you sure?','rcl'). '\');" class="recall-button">
				</form>
			</div>';

	}

	function requests_users(){
		if($this->requests){
			$rqst = '<h3>'. __('Requests to join the group','rcl'). '</h3>
			<table class="request-list">';
				foreach((array)$this->requests as $user=>$name){
					$rqst .= '<tr id="user-req-'.$user.'">
						<td>'.get_avatar($user,50).'</td><td><a class="name-candidats" href="'.get_author_posts_url($user).'"> '.$name.'</a></td>
						<td>'.rcl_get_button(__('Take','rcl'),'#',array('icon'=>'fa-check','class'=>'request-access','id'=>'add-user-req-'.$this->term_id)).'</td>
						<td>'.rcl_get_button(__('Reject','rcl'),'#',array('icon'=>'fa-times','class'=>'request-access','id'=>'del-user-req-'.$this->term_id)).'</td>
					</tr>';
				}
			$rqst .= '</table>';
			return $rqst;
		}
	}

	function exit_button(){
		if($this->in_group) return '<div class="add-user-group">
			<form action="" method="post">
				'.wp_nonce_field('login-group-request-rcl','_wpnonce',true,false).'
				<input type="submit" class="recall-button" name="login_group" value="'. __('To leave the group','rcl').'">
			</form>
		</div>';
	}

	function private_button(){
		global $user_ID;
		if(!$this->in_group&&$user_ID!=$this->admin_id&&$this->options_gr['private']==1){
			if(isset($this->requests[$user_ID])) return '<p style="clear:both;text-align:right;color:green;">'. __('Your application for membership is accepted','rcl').'</p>';
			else return '<div class="add-user-group">
				<form action="" method="post">
					'.wp_nonce_field('login-group-request-rcl','_wpnonce',true,false).'
					<input type="submit" name="login_group" value="'. __('To apply for membership','rcl').'" class="recall-button">
				</form>
			</div>';
		}
	}

	function sign_button(){
		global $user_ID;
		if(!$user_ID||$user_ID==$this->admin_id) return false;
		if(!$this->in_group&&!$this->options_gr['private']) return '<div class="add-user-group">
				<form action="" method="post">
					'.wp_nonce_field('login-group-request-rcl','_wpnonce',true,false).'
					<input type="submit" name="login_group" value="'. __('Join','rcl').'" class="recall-button">
				</form>
			</div>';
	}

	function users_block(){
		global $user_ID;

		return $this->exit_button()

		. $this->private_button()

		. $this->sign_button();
	}

	function get_buttons(){
		global $user_ID;
		if($user_ID&&$user_ID==$this->admin_id) return $this->admin_block();
		else if($user_ID) return $this->users_block();
                else return false;
	}

	function get_userlist(){
		if($this->users_group&&$this->options_gr['users']==1){
			$block_users = '<h3>'. __('Group members','rcl').':</h3>';
			$a=0;
			$names = rcl_get_usernames($this->users_group,'user_id');
			foreach((array)$this->users_group as $single_user){
				$a++;
				$block_users .= '<a title="'.$names[$single_user->user_id].'" href="'.get_author_posts_url($single_user->user_id).'">'.get_avatar($single_user->user_id,50).'</a>';
				if($a==9)break;
			}
			$block_users .= '
			<p style="clear:both;margin:0;" align="right"><a href="'.get_term_link( (int)$this->group_id, 'groups' ).'?group-page=users#userlist" class="all-users-group">'. __('All members','rcl').'</a></p>';

			return $block_users;
		}
	}

	function private_title($content){
		global $user_ID;
		if(isset($this->options_gr['private'])&&$this->options_gr['private']==1){
			if(!$this->in_group&&$user_ID!=$this->admin_id){
				echo '<h2 align="center" style="color:red;">'. __('Access to the closed group privacy settings.','rcl').'</h2>';
				return false;
			}
		}
                return $content;
	}

	function get_imagelist(){
		if($this->gallery_group&&$this->options_gr['images']==1){
			rcl_bxslider_scripts();
			$lst = '<h3>'. __('The last photo of the group','rcl').':</h3>
			<div id="gallery-group">';
			foreach((array)$this->gallery_group as $foto){
				$src_foto = wp_get_attachment_image_src( $foto->ID, 'thumbnail');
				$lst .= '<a href="'. get_permalink($foto->post_parent) .'"><img src="'.$src_foto[0].'" width="75" align="left"></a>';
			}
			$lst .= '</div>
			<script>jQuery(function($){
				$("#gallery-group").bxSlider({
					pager:false,
					minSlides: 1,
					maxSlides: 10,
					slideWidth: 75,
					slideMargin: 5,
					moveSlides:2
				});
			});
			</script>';
			return $lst;
		}
	}

	function get_content(){
                $content_group = '';
		return apply_filters('content_group_rcl',$content_group);
	}

	function get_after_header(){
                $content_group = '';
		return apply_filters('after_header_group_rcl',$content_group);
	}

	function get_form(){
		global $user_ID;
		if(isset($this->options_gr['no-post'])&&$user_ID!=$this->admin_id) return false;
		if($this->in_group||$user_ID==$this->admin_id){
			return '<div class="public-post-group"><a href="#">'. __('To publish a new entry in the group','rcl').'</a></div>
			<div class="public_block" style="clear:both;display:none;">
			<a class="close-public-form" href="#">'. __('Close form','rcl').'</a><h3>'. __('Record publishing','rcl').'</h3>'
			.do_shortcode('[public-form post_type="post-group" group_id="'.$this->term_id.'"]')
			.'</div>';
		}
	}

	function tags_list(){
		$targs = array(
			'number'        => 0
			,'hide_empty'   => true
			,'hierarchical' => false
			,'pad_counts'   => false
			,'get'          => ''
			,'child_of'     => 0
			,'parent'       => $this->term_id
		);

		$tags = get_terms('groups', $targs);

		if($tags) return '<div class="search-form-rcl">
				<form method="get">
					'.rcl_get_tags_list_group((object)$tags,'',__('Display all records','rcl')).'
					<input type="hidden" name="search-p" value="'.$this->group_id.'">
					<input type="submit" class="recall-button" value="'.__('Show','rcl').'">
				</form>
			</div>';
	}

	function rcl_get_users_group($page){
		return do_shortcode('[userlist page="'.$page.'" orderby="action" group="'.$this->group_id.'" search="no"]');
	}

	function get_footer(){
		$footer = apply_filters('footer_group_rcl',$this->tags_list());
		return $footer;
	}

}