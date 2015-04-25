<?php
rcl_enqueue_style('groups',__FILE__);

function add_post_in_group(){
	global $user_ID,$gr_data;

	include('class_group.php');
	$gr_data = new Rcl_Group();

	$gr_data->init_variables();
	$gr_data->get_post_request();

	include_template_rcl('group.php',__FILE__);
}

function class_group(){
	global $gr_data;
	echo $gr_data->class_gr();
}

function images_group(){
	global $gr_data;
	echo $gr_data->get_images();
}

function group_name(){
	global $gr_data;
	echo $gr_data->get_name();
}

function admin_group($txt=false){
	global $gr_data;
	echo $gr_data->get_admin($txt);
}

function desc_group(){
	global $gr_data;
	echo $gr_data->get_desc();
}

function options_group(){
	global $gr_data;
	echo $gr_data->get_options();
}

function after_header_group(){
	global $gr_data;
	echo $gr_data->get_after_header();
}

function buttons_group(){
	global $gr_data;
	echo $gr_data->get_buttons();
}

function userlist_group(){
	global $gr_data;
	echo $gr_data->get_userlist();
}

function imagelist_group(){
	global $gr_data;
	echo $gr_data->get_imagelist();
}

function content_group(){
	global $gr_data;
	echo $gr_data->get_content();
}

function form_group(){
	global $gr_data;
	echo $gr_data->get_form();
}

function footer_group(){
	global $gr_data;
	echo $gr_data->get_footer();
}

function group_rcl(){
	add_post_in_group();
}

add_action( 'init', 'register_terms_rec_post_group' );
function register_terms_rec_post_group() {

	$labels = array(
			'name' => __('Record groups','rcl'),
			'singular_name' => __('Record groups','rcl'),
			'add_new' => __('Add entry','rcl'),
			'add_new_item' => __('Add entry','rcl'),
			'edit_item' => __('Edit','rcl'),
			'new_item' => __('New','rcl'),
			'view_item' => __('View','rcl'),
			'search_items' => __('Search','rcl'),
			'not_found' => __('Not found','rcl'),
			'not_found_in_trash' => __('Cart is empty','rcl'),
			'parent_item_colon' => __('Parent record','rcl'),
			'menu_name' => __('Record groups','rcl'),
	);

	$args = array(
		'labels' => $labels,
		'hierarchical' => false,
		'supports' => array( 'title', 'editor','custom-fields', 'comments', 'thumbnail', 'author'),
		'taxonomies' => array( 'groups','post_tag' ),
		'public' => true,
		'show_ui' => true,
		'show_in_menu' => true,
		'menu_position' => 10,
		'show_in_nav_menus' => true,
		'publicly_queryable' => true,
		'exclude_from_search' => false,
		'has_archive' => true,
		'query_var' => true,
		'can_export' => true,
		'rewrite' => true,
		'capability_type' => 'post'
	);

	register_post_type( 'post-group', $args );
}
add_action( 'init', 'register_taxonomy_groups' );

function register_taxonomy_groups() {

	$labels = array(
		 'name' => __('Groups','rcl'),
		'singular_name' => __('Groups','rcl'),
		'search_items' => __('Search','rcl'),
		'popular_items' => __('Popular Groups','rcl'),
		'all_items' => __('All categories','rcl'),
		'parent_item' => __('Parent group','rcl'),
		'parent_item_colon' => __('Parent group','rcl'),
		'edit_item' => __('Edit','rcl'),
		'update_item' => __('Update','rcl'),
		'add_new_item' => __('To add a new','rcl'),
		'new_item_name' => __('New','rcl'),
		'separate_items_with_commas' => __('Separate with commas','rcl'),
		'add_or_remove_items' => __('To add or remove','rcl'),
		'choose_from_most_used' => __('Click to use','rcl'),
		'menu_name' => __('Groups','rcl')
	);

	$args = array(
		'labels' => $labels,
		'public' => true,
		'show_in_nav_menus' => true,
		'show_ui' => true,
		'show_tagcloud' => true,
		'hierarchical' => true,

		'rewrite' => true,
		'query_var' => true
	);

	register_taxonomy( 'groups', array('post-group'), $args );
}

add_filter('taxonomy_public_form_rcl','add_taxonomy_public_groups');
function add_taxonomy_public_groups($tax){
    if (!isset($tax['post-group'])) $tax['post-group'] = 'groups';
    return $tax;
}

if(function_exists('add_postlist_rcl')){
    add_postlist_rcl('group','post-group',__('Record groups'),array('order'=>40));
}

add_filter('tag_link','add_post_type_link_tags');
function add_post_type_link_tags($link){
    global $post;
    if($post->post_type=='group-post') return $link.'?post_type='.$post->post_type;
    return $link;
}

//Получаем ИД группы которой принадлежит публикация
function get_group_id_by_post($post_id){
    $groups = get_the_terms( $post_id, 'groups' );
    if(!$groups) return false;
    foreach($groups as $group){
        if($group->parent!=0) continue;
        $group_id = $group->term_id;
    }
    if($group_id) return $group_id;
    else return false;
}

//Получаем настройки и данные группы
function get_options_group($group_id){
    global $wpdb;
    return unserialize($wpdb->get_var("SELECT option_value FROM ".RCL_PREF."groups_options WHERE group_id='$group_id'"));
}

//Проверяем возможность пользователя редактировать публикации группы
function user_can_edit_post_group($post_id){
    global $user_ID;
    $group_id = get_group_id_by_post($post_id);
    if(!$group_id) return false;
    $options_gr = get_options_group($group_id);
    if(!isset($options_gr['admin'])||$options_gr['admin']!=$user_ID) return false;
    return true;
}

//получаем кол-во участников группы
function get_userscount_group($group_id){
    global $wpdb;
    return $wpdb->get_var("SELECT COUNT(user_id) FROM ".$wpdb->prefix ."usermeta WHERE meta_key = 'user_group_$group_id'");
}

//Ищем идентификатор админа группы по метаполям пользователей
function get_admin_group_by_meta($group_id){
    global $wpdb;
    return $wpdb->get_var("SELECT user_id FROM ".$wpdb->prefix ."usermeta WHERE meta_key = 'admin_group_$group_id'");
}

function add_post_group_edit_button_rcl($content){
	global $post,$user_ID,$gr_data,$rcl_options;
	if(!is_tax('groups')) return $content;

	if($gr_data->group_id&&$gr_data->admin_id==$user_ID){
            $edit_url = get_redirect_url_rcl(get_permalink($rcl_options['public_form_page_rcl']));
            $content = '<p class="post-edit-button">'
                . '<a title="'.__('Edit','rcl').'" object-id="none" href="'. $edit_url.'rcl-post-edit='.$post->ID .'">'
                    . '<i class="fa fa-pencil-square-o"></i>'
                . '</a>'
            . '</p>'.$content;
	}
	return $content;
}
add_filter('the_content','add_post_group_edit_button_rcl',999);
add_filter('the_excerpt','add_post_group_edit_button_rcl',999);

if (is_admin()) add_action('admin_init', 'recall_postmeta_pgroups');
function recall_postmeta_pgroups() {
    add_meta_box( 'recall_meta', __('Settings Wp-Recall','rcl'), 'options_box_rcl', 'post-group', 'normal', 'high'  );
}

add_filter('admin_options_wprecall','get_admin_groups_page_content');
function get_admin_groups_page_content($content){

        $opt = new Rcl_Options(__FILE__);

        $content .= $opt->options(
            __('Group settings','rcl'),
            $opt->option_block(
                array(
                    $opt->title(__('Groups','rcl')),
                    $opt->label(__('Creating groups is allowed','rcl')),
                    $opt->option('select',array(
                        'name'=>'public_group_access_recall',
                        'options'=>array(
                            10=>__('only Administrators','rcl'),
                            7=>__('Editors and older','rcl'),
                            2=>__('Authors and older','rcl'),
                            1=>__('Participants and older','rcl'))
                    )),

                    $opt->label(__('Publishing group','rcl')),
                    $opt->option('select',array(
                        'name'=>'user_public_access_group',
                        'options'=>array(
                            10=>__('only Administrators','rcl'),
                            7=>__('Editors and older','rcl'),
                            2=>__('Authors and older','rcl'),
                            1=>__('Participants and older','rcl'),
                            0=>__('All users','rcl'))
                    )),

                    $opt->label(__('Moderation of publications in the group','rcl')),
                    $opt->option('select',array(
                        'name'=>'moderation_public_group',
                        'options'=>array(
                            __('To publish immediately','rcl'),
                            __('Send for moderation','rcl'))
                    )),
                    $opt->notice(__('If used in moderation:</b> To allow the user to see their publication before it is moderated, it is necessary to have on the website right below the Author','rcl')),

                    $opt->label(__('Rating value publications','rcl')),
                    $opt->option('text',array('name'=>'count_rayt_post-group')),
                    $opt->notice(__('Specify a value of negative and positive evaluations for publishing in the group. The default is 1','rcl')),

                    $opt->label(__('The influence of the rating of the publications in the group on the rating of the author','rcl')),
                    $opt->option('select',array(
                        'name'=>'rayt_post-group',
                        'options'=>array(__('No','rcl'),__('Yes','rcl'))
                    )),
                )
            )
        );
	return $content;
}

add_filter('pre_update_postdata_rcl','add_publicdata_group_rcl',10,2);
function add_publicdata_group_rcl($postdata,$data){
    global $rcl_options,$user_ID;
    if($data->post_type!='post-group') return $postdata;

    if($rcl_options['moderation_public_group']==1) $post_status = 'pending';
    else $post_status = 'publish';

    if($rcl_options['nomoder_rayt']){
            $all_r = get_all_rayt_user(0,$user_ID);
            if($all_r >= $rcl_options['nomoder_rayt']) $post_status = 'publish';
    }
    $postdata['post_status'] = $post_status;

    return $postdata;

}

add_action('update_post_rcl','update_grouppost_meta_rcl',10,3);
function update_grouppost_meta_rcl($post_id,$postdata,$action){

    if($postdata['post_type']!='post-group') return false;

    if(isset($_POST['term_id'])) $term_id = base64_decode($_POST['term_id']);

    if(isset($term_id)) wp_set_object_terms( $post_id, (int)$term_id, 'groups' );

    $gr_tag = $_POST['group-tag'];
    if($gr_tag){

            if(!$term_id){
                $groups = get_the_terms( $post_id, 'groups' );
                foreach($groups as $group){if($group->parent!=0) continue; $group_id = $group->term_id;}
            }else{
                $group_id = $term_id;
            }

            $term = term_exists( $gr_tag, 'groups',$group_id );
            if(!$term){
                    $term = wp_insert_term(
                      $gr_tag,
                      'groups',
                      array(
                            'description'=> '',
                            'slug' => '',
                            'parent'=> $group_id
                      )
                    );
            }
            wp_set_object_terms( $post_id, array((int)$term['term_id'],(int)$group_id), 'groups' );
    }

}

add_filter('array_rayt_chek','add_rayt_array_postgroup');
function add_rayt_array_postgroup($array){
	global $rcl_options;
	$array['rayt_post-group'] = $rcl_options['rayt_post-group'];
	return $array;
}

function add_tab_groups_rcl($array_tabs){
    $array_tabs['groups']='recall_groups_block';
    return $array_tabs;
}
add_filter('ajax_tabs_rcl','add_tab_groups_rcl');

add_action('init','add_tab_groups');
function add_tab_groups(){
    add_tab_rcl('groups','recall_groups_block','Группы',array('public'=>1,'class'=>'fa-group','order'=>15,'path'=>__FILE__));
}

function recall_groups_block($author_lk){

	global $wpdb;
	global $user_ID;
	global $rcl_options;

	$admin_groups = $wpdb->get_results("SELECT meta_value FROM ".$wpdb->prefix ."usermeta WHERE meta_key LIKE 'admin_group_%' AND user_id = '$author_lk'");
	$user_groups = $wpdb->get_results("SELECT meta_value FROM ".$wpdb->prefix ."usermeta WHERE meta_key LIKE 'user_group_%' AND user_id = '$author_lk'");//print_r($admin_groups);
	if($admin_groups){
		$ad_groups = '<ul class="group-list">';
		foreach((array)$admin_groups as $ad_group){
			$ad_term = get_term($ad_group->meta_value, 'groups');
			if($ad_term->term_id){
				$ad_groups .= '<li id="list-'.$ad_term->term_id.'">';
				$ad_groups .= '<a href="'.get_term_link((int)$ad_term->term_id,'groups' ).'"><i class="fa fa-group"></i>'.$ad_term->name. '</a>';
				$ad_groups .= '</li>';
			}
		}
		$ad_groups .= '</ul>';
	}


	if($user_groups){
		$us_groups = '<ul class="group-list">';
		foreach((array)$user_groups as $group){

			$term = get_term($group->meta_value, 'groups');
			if($term->term_id){
			$us_groups .= '<li id="list-'.$term->term_id.'"><a href="'. get_term_link( (int)$term->term_id, 'groups' ) .'"><i class="fa fa-group"></i>'.$term->name.'</a></li>';
			}
		}
		$us_groups .= '</ul>';
	}

	$group_can_public = $rcl_options['public_group_access_recall'];
	if($group_can_public){
		$userdata = get_userdata( $user_ID );
		if($userdata->user_level>=$group_can_public){
			$public_groups = true;
		}else{
			$public_groups = false;
		}
	}else{
		$public_groups = true;
	}
        //echo $userdata->user_level.' - '.$group_can_public;

	if($user_ID==$author_lk&&$public_groups) $groups_block = '<p align="right"><input type="button" class="show_form_add_group recall-button" value="'.__('To create a group','rcl').'">
            </p><div class="add_new_group">
            <h3>'.__('To create a group','rcl').'</h3>
	<form action="" method="post" enctype="multipart/form-data">
	<p>'.__('Name','rcl').'</p>
	<input type="text" required maxlength="140" size="30" class="title_groups" name="title_groups" value="">
	<p>'.__('Group description','rcl').'</p>
	<textarea required name="group_desc" id="group_desc" rows="2" style="width:90%;"></textarea>
	<p>'.__('The status of the group','rcl').'</p>
	<input type="checkbox" class="status_groups" name="status_groups" value="1"> - '.__('Back to the group. The access group just approved the request of the user.','rcl').'
	<p>'.__('Group avatar','rcl').' <input required type="file" name="image_group" class="field"/></p>
	<p align="right"><input type="submit" class="recall-button" name="addgroups" value="'.__('Сreate','rcl').'"></p>
	</form></div>';
        else if(!$public_groups) $groups_block = '<h3>'.__('You are not allowed to create new groups.','rcl').'</h3>';
	if($admin_groups) $groups_block .= '<h3>'.__('Created group','rcl').'</h3>'.$ad_groups;
	if($user_groups) $groups_block .= '<h3>'.__('Joined the group','rcl').'</h3>'.$us_groups;

	if($user_ID!=$author_lk&&!$admin_groups&&!$user_groups) $groups_block .= '<h3>'.__('The user is not in group','rcl').'</h3>';

	return $groups_block;
}

//Удаляем всех пользователей и админа группы и ее аватарку при ее удалении из БД
add_action('delete_term', 'delete_users_group_rcl',10,3);
function delete_users_group_rcl($term, $tt_id=null, $taxonomy=null){
	if(!$taxonomy||$taxonomy!='groups') return false;
	global  $wpdb;
	$imade_id = get_option('image_group_'.$term);
	delete_option('image_group_'.$term);
	wp_delete_attachment($imade_id,true);
	$wpdb->query("DELETE FROM ".$wpdb->prefix."usermeta WHERE meta_key = 'admin_group_".$term."'");
	$wpdb->query("DELETE FROM ".$wpdb->prefix."usermeta WHERE meta_key = 'user_group_".$term."'");
	$wpdb->query("DELETE FROM ".RCL_PREF."groups_options WHERE group_id = '".$term."'");
}

//add_action('wp_head','delete_remove_groups');
function delete_remove_groups(){
	global $wpdb,$user_ID;
	if($user_ID!=1) return false;
	$datas = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."usermeta WHERE meta_key LIKE 'admin_group_%'");
	//print_r($datas);
	foreach($datas as $data){
		$term = term_exists((int)$data->meta_value,'groups');
		if(!$term){
			//echo 'Delete: '.$data->meta_value.'<br>';
                        delete_users_group_rcl($data->meta_value, false, 'groups');
		}else{
			//echo 'Groups: '.$data->meta_value.'<br>';
		}
	}
}

function get_link_group_tag_rcl($content){
	global $post;
	if($post->post_type!='post-group') return $content;

	$group_data = get_the_terms( $post->ID, 'groups' );

	foreach((array)$group_data as $data){
		if($data->parent==0) $group_id = $data->term_id;
		else $tag = $data;
	}

	if(!$tag) return $content;

	$cat = '<p><i class="fa fa-folder"></i>'.__('Category in the group','rcl').': <a href="'. get_term_link( (int)$group_id, 'groups' ) .'?group-tag='.$tag->slug.'">'. $tag->name .'</a></p>';

	return $cat.$content;
}
function init_get_link_group_tag(){
	if(is_single()) add_filter('the_content','get_link_group_tag_rcl',80);
	else add_filter('the_excerpt','get_link_group_tag_rcl',80);
}
add_action('wp','init_get_link_group_tag');

//Создаем новую группу
function add_new_group_recall(){

	global $user_ID,$wpdb;
	$option = array();

	if($_POST['status_groups']) $option['private'] = 1;

	$args = array(
		'alias_of'=>''
		,'description'=>$_POST['group_desc']
		,'parent'=>0
		,'slug'=>''
	);

	$ret = wp_insert_term( $_POST['title_groups'], 'groups', $args );

	foreach((array)$ret as $r){
		if ($ret && !is_wp_error($ret)){
			update_usermeta($user_ID,'admin_group_'.$r, $r);
			$option['admin'] = $user_ID;
		}
		break;
	}

	require_once(ABSPATH . "wp-admin" . '/includes/image.php');
	require_once(ABSPATH . "wp-admin" . '/includes/file.php');
	require_once(ABSPATH . "wp-admin" . '/includes/media.php');

	$image = wp_handle_upload( $_FILES['image_group'], array('test_form' => FALSE) );
	if($image['file']){
		$option['avatar'] = update_image_group($r,$image);;
	}

	$option = serialize($option);
	$wpdb->insert(
		RCL_PREF.'groups_options',
		array('group_id'=>$r,'option_value'=>$option)
	);

	wp_redirect(get_term_link( (int)$r, 'groups' )); exit;

}

function add_new_group_recall_activate ( ) {
  if ( isset($_POST['addgroups']) ) {
    add_action( 'wp', 'add_new_group_recall' );
  }
}
add_action('init', 'add_new_group_recall_activate');

function chek_access_private_group_posts($query){
    if (!class_exists('Group_Private')) include_once plugin_dir_path( __FILE__ ).'group_private.php';
    $gp = new Group_Private($query);
}
add_action('pre_get_posts','chek_access_private_group_posts');

function get_group_globals_rcl(){
	global $wp_query,$wpdb,$group_id,$options_gr;
        if(!isset($wp_query->query_vars['groups'])) return false;
	$curent_term = get_term_by('slug', $wp_query->query_vars['groups'], 'groups');

	if($curent_term->parent!=0) $group_id = $curent_term->parent;
	else $group_id = $curent_term->term_id;
	$options_gr = get_options_group($group_id);
}
add_action('wp','get_group_globals_rcl',1);

function login_group_request_rcl(){
	global $group_id,$options_gr,$user_ID;
	if(isset($_POST['login_group'])&&$user_ID){
		if( !wp_verify_nonce( $_POST['_wpnonce'], 'login-group-request-rcl' ) ) return false;

		if($user_ID) $in_group = get_the_author_meta('user_group_'.$group_id,$user_ID);

		$admin_id = $options_gr['admin'];

		if($in_group){
				delete_usermeta( $user_ID, 'user_group_'.$group_id );
				$in_group = false;
			}else{
				if($options_gr['private']==1){

					$curent_term = get_term_by('ID', $group_id, 'groups');
					$requests = unserialize(get_option('request_group_access_'.$group_id));
					$requests[$user_ID] = get_usermeta($user_ID,'display_name');
					$requests = serialize($requests);
					update_option('request_group_access_'.$group_id,$requests);

					$subject = 'Запрос на доступ к группе!';
					$textmail = '
					<p>Вы получили новый запрос на доступ к администрируемой вами группе "'.$curent_term->name.'" на сайте "'.get_bloginfo('name').'".</p>
					<h3>Информация о пользователе:</h3>
					<p><b>Профиль пользователя</b>: <a href="'.get_author_posts_url($user_ID).'">'.get_the_author_meta('display_name',$user_ID).'</a></p>
					<p>Вы можете одобрить или отклонить запрос перейдя по ссылке:</p>
					<p>'.get_term_link( (int)$group_id, 'groups' ).'</p>';
					$admin_email = get_the_author_meta('user_email',$admin_id);
					rcl_mail($admin_email, $subject, $textmail);

				}else{
					update_usermeta($user_ID,'user_group_'.$group_id, $group_id);
					$in_group = true;
				}
			}
		wp_redirect(get_term_link((int)$group_id,'groups')); exit;
	}
}
add_action('wp','login_group_request_rcl',20);

function update_image_group($group_id,$image){
	global $options_gr;

	$opt_image = get_option('image_group_'.$group_id);
	if($opt_image) delete_option('image_group_'.$group_id);

	if($options_gr['avatar']) wp_delete_post($options_gr['avatar'],true);

	$attachment = array(
		'post_mime_type' => $image['type'],
		'post_title' => 'image_group_'.$group_id,
		'post_content' => $image['url'],
		'guid' => $image['url'],
		'post_parent' => '',
		'post_status' => 'inherit'
	);

	$imade_id = wp_insert_attachment( $attachment, $image['file'] );
	$attach_data = wp_generate_attachment_metadata( $imade_id, $image['file'] );
	wp_update_attachment_metadata( $imade_id, $attach_data );

	return $imade_id;
}

function upload_image_group_rcl(){
	global $wpdb,$group_id,$options_gr,$user_ID;
	if(isset($_FILES['image_group'])&&$user_ID){
		$file_name = $_FILES['image_group']['name'];
		$rest = substr($file_name, -4);//получаем расширение файла
		if($rest=='.png'||$rest=='.jpg'||$rest=='jpeg'||$rest=='.gif'||$rest=='.PNG'||$rest=='.JPG'||$rest=='.JPEG'||$rest=='.GIF'||$rest=='.bmp'){
			require_once(ABSPATH . "wp-admin" . '/includes/image.php');
			require_once(ABSPATH . "wp-admin" . '/includes/file.php');
			require_once(ABSPATH . "wp-admin" . '/includes/media.php');

			$image = wp_handle_upload( $_FILES['image_group'], array('test_form' => FALSE) );
			if($image['file']){

				$options_gr['avatar'] = update_image_group($group_id,$image);

				$options_ser = serialize($options_gr);

				$res = $wpdb->update(
					RCL_PREF.'groups_options',
					array('option_value'=>$options_ser),
					array('group_id'=>$group_id)
				);

				if(!$res){
						$wpdb->insert(
						RCL_PREF.'groups_options',
						array('group_id'=>$group_id,'option_value'=>$options_ser)
					);
				}
			}
		}
		wp_redirect(get_term_link((int)$group_id,'groups')); exit;
	}
}
add_action('wp','upload_image_group_rcl',30);

function init_get_name_group(){
	if(is_single()) add_filter('the_content','add_name_rcl_groups',80);
}
add_action('wp','init_get_name_group');

function get_tags_list_group_rcl($tags,$post_id=null,$first=null){
	if(isset($tags)){
                $name = '';
		if($post_id){

			$group_data = get_the_terms( $post_id, 'groups' );
			foreach($group_data as $data){
				if($data->parent==0) $group_id = $data->term_id;
				else $name = $data->name;
			}

		}else{
			if(isset($_GET['group-tag'])) $name = $_GET['group-tag'];
		}

		$tg_lst = '<select name="group-tag">';
		if($first) $tg_lst .= '<option value="">'.$first.'</option>';

		if(!is_object($tags)){
			$ar_tags = explode(',',$tags);
                        $i=0;
			foreach($ar_tags as $tag){
				$ob_tags[++$i] = new stdClass();
				$ob_tags[$i]->name = trim($tag);
			}
		}else{
                        $a=0;
			foreach($tags as $tag){
				$ob_tags[++$a] = new stdClass();
				$ob_tags[$a]->name =$tag->name;
				$ob_tags[$a]->slug =$tag->slug;
			}
		}

		foreach($ob_tags as $gr_tag){
                        if(!$gr_tag->name) continue;
			if(!isset($gr_tag->slug)) $slug = $gr_tag->name;
			else $slug = $gr_tag->slug;
			$tg_lst .= '<option '.selected($name,$slug,false).' value="'.$slug.'">'.trim($gr_tag->name).'</option>';
		}
		$tg_lst .= '</select>';
	}
	return $tg_lst;
}

function add_name_rcl_groups($content){
	global $post;
	if(get_post_type( $post->ID )!='post-group') return $content;

	$groups = get_the_terms( $post->ID, 'groups' );
	foreach((array)$groups as $group){
		if($group->parent) continue;
		$group_link = '<p><i class="fa fa-users"></i>'.__('Published in the group','rcl').': <a href="'. get_term_link( (int)$group->term_id, 'groups' ) .'">'. $group->name .'</a></p>';
	}
	$content = $group_link.$content;
	return $content;
}

/*************************************************
Смотрим всех пользователей группы
*************************************************/
function all_users_group_recall(){
	$page = $_POST['page'];
	if(!$_POST['page']) $page = 1;
	include('class_group.php');
	$group = new Rcl_Group($_POST['id_group']);
	$block_users = '<div class="backform" style="display: block;"></div>
	<div class="float-window-recall" style="display:block;"><p align="right">'.get_button_rcl(__('Close','rcl'),'#',array('icon'=>false,'class'=>'close_edit','id'=>false)).'</p><div>';
	$block_users .= $group->all_users_group($page);
	$block_users .= '</div></div>
	<script type="text/javascript"> jQuery(function(){ jQuery(".close_edit").click(function(){ jQuery(".group_content").empty(); }); }); </script>';
	$log['recall']=100;
	$log['block_users']=$block_users;
	echo json_encode($log);
	exit;
}
add_action('wp_ajax_all_users_group_recall', 'all_users_group_recall');
add_action('wp_ajax_nopriv_all_users_group_recall', 'all_users_group_recall');

function request_users_group_access_rcl(){

	$id_group = $_POST['id_group'];
	$id_user = $_POST['id_user'];
	$req = $_POST['req'];

	$all_request = unserialize(get_option('request_group_access_'.$id_group));
	$curent_term = get_term_by('id', $id_group, 'groups');
	if($req==1){
		update_usermeta($id_user,'user_group_'.$id_group, $id_group);
		$subject = __('Request access to the group approved!','rcl');
		$textmail = '
		<h3>Добро пожаловать в группу "'.$curent_term->name.'"!</h3>
		<p>Поздравляем, ваш запрос на доступ к приватной группе на сайте "'.get_bloginfo('name').'" был одобрен.</p>
		<p>Теперь вы можете принимать участие в жизни этой группы как полноценный ее участник.</p>
		<p>Вы можете перейти в группу прямо сейчас, перейдя по ссылке:</p>
		<p>'.get_term_link( (int)$id_group, 'groups' ).'</p>';
		unset($all_request[$id_user]);
	}
	if($req==2){
		unset($all_request[$id_user]);
		$subject = __('The request to access the group rejected.','rcl');
		$textmail = '
		<p>Сожалеем, но ваш запрос на доступ к приватной группе "'.$curent_term->name.'" на сайте "'.get_bloginfo('name').'" был отклонен ее админом.</p>';
	}

	$user_email = get_the_author_meta('user_email',$id_user);
	rcl_mail($user_email, $subject, $textmail);

	$all_request = serialize($all_request);
	update_option('request_group_access_'.$id_group,$all_request);

	$log['result']=100;
	$log['user']=$id_user;
	echo json_encode($log);
	exit;
}
add_action('wp_ajax_request_users_group_access_rcl', 'request_users_group_access_rcl');

function get_group_by_event($status){
	global $wpdb;

	$group_ids = $wpdb->results("SELECT * FROM ".RCL_PREF."groups_options WHERE option_value LIKE '%active%'");

    $a=0;
	foreach($group_ids as $data){
		if(++$a>1) $lst .= ',';
		$lst .= $data->group_id;
	}

	$group_data = $wpdb->results("SELECT * FROM ".$wpdb->prefix."terms WHERE term_id ON ($lst)");
	return $group_data;
}

add_shortcode('grouplist','shortcode_grouplist');
function shortcode_grouplist($atts, $content = null){
global $wpdb,$post;

	if(isset($_GET['navi'])) $navi = $_GET['navi'];
	else $navi=1;

	extract(shortcode_atts(array(
		'orderby' => 'id',
		'order' => 'DESC',
		'inpage' => 10
	),
	$atts));

	if(!isset($_GET['event'])){

		if(isset($_GET['filter'])) $orderby = $_GET['filter'];

                if(isset($_GET['search-group'])) $search = $_GET['search-group'];
                else $search = false;

		$args = array(
			'number'        => 0
			,'offset'       => 0
			,'orderby'      => $orderby
			,'order'        => $order
			,'hide_empty'   => false
			,'fields'       => 'all'
			,'slug'         => ''
			,'hierarchical' => false
			,'name__like'   => $search
			,'pad_counts'   => false
			,'get'          => ''
			,'child_of'     => 0
			,'parent'       => 0
		);

		$groups = get_terms('groups', $args);

	}else{
		$groups = get_group_by_event($_GET['event']);
	}

	if($inpage){
		$count_group = count($groups);
		$num_page = ceil($count_group/$inpage);
		$max_group_inpage = $inpage*$navi;
	}

	$n=0;

	$users_groups = $wpdb->get_results("SELECT user_id,meta_key FROM ".$wpdb->prefix ."usermeta WHERE meta_key LIKE 'user_group_%' OR meta_key LIKE 'admin_group_%'");

	$a = 0;
        $userslst = '';
	foreach((array)$users_groups as $user_gr){
		if(++$a>1) $userslst .= ',';
		$userslst .= $user_gr->user_id;
	}

	$display_names = $wpdb->get_results("SELECT ID,display_name FROM ".$wpdb->prefix."users WHERE ID IN ($userslst)");

	foreach((array)$display_names as $name){
		$names[$name->ID] = $name->display_name;
	}

	$grouplist = '<form method="get" action="">
			<p class="alignright">'.__('Search group','rcl').': <input type="text" required name="search-group" value="'.$search.'">
			<input type="submit" class="recall-button" value="'.__('Find','rcl').'"><br>
			</p>
			</form>';

	if(isset($_GET['filter'])) $flt = $_GET['filter'];
        else $flt = false;

	$grouplist .= '<p class="alignleft">'.__('Filter by','rcl').':
		<a '.a_active($flt,'id').' href="'.get_permalink($post->ID).'?filter=id">'.__('Created date','rcl').'</a>
		<a '.a_active($flt,'name').' href="'.get_permalink($post->ID).'?filter=name">'.__('By name','rcl').'</a>
		<a '.a_active($flt,'count').' href="'.get_permalink($post->ID).'?filter=count">'.__('The number of records','rcl').'</a>
	</p>';
	$grouplist .= '<div class="group-list">';

	/*старые аватарки*/
	$images_gr = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."options WHERE option_name LIKE 'image_group_%'");
	foreach((array)$images_gr as $imag){
		$old_ava[$imag->option_name] = $imag->option_value;
	}
	/**/

	$option_gr = $wpdb->get_results("SELECT * FROM ".RCL_PREF."groups_options");

	foreach((array)$option_gr as $option){
		$opt_groups[$option->group_id] = $option->option_value;
	}

	foreach((array)$groups as $group){

	$n++;
		if($n > $max_group_inpage-$inpage){

			$users_count = 0;

			if(isset($opt_groups[$group->term_id])) $options_gr = unserialize($opt_groups[$group->term_id]);

			if(isset($options_gr['admin'])) $admin_id = $options_gr['admin'];
                        else $admin_id = false;
			if(isset($options_gr['avatar'])) $imade_id = $options_gr['avatar'];
                        else $imade_id = false;

			foreach((array)$users_groups as $user){
				if($user->meta_key=="user_group_".$group->term_id) $users_count++;
				if($user->meta_key=="admin_group_".$group->term_id&&!$admin_id) $admin_id = $user->user_id;
			}

			$grouplist .= '<div id="single-group-'.$group->term_id.'" class="group-info">';

			//if(!$imade_id) $imade_id = get_option('image_group_'.$term_id);

			if(!$imade_id&&isset($old_ava['image_group_'.$group->term_id])) $imade_id = $old_ava['image_group_'.$group->term_id];

			$src = wp_get_attachment_image_src( $imade_id, 'thumbnail');
			$group_desc = $group->description;
			if(strlen($group_desc) > 300){
				$allowed_html = array(
					'br' => array(),
					'em' => array(),
					'strong' => array()
				);

				$group_desc = wp_kses(preg_replace('@(.*)\s[^\s]*$@s', '\\1 ...', $group_desc), $allowed_html);
				$group_desc = mb_substr($group_desc, 0, 300);

			}
			if($src[0]) $grouplist .= '<img src="'.$src[0].'" class="avatar_gallery_group">';
			else $grouplist .= '<img src="'.plugins_url('img/empty-avatar.jpg', __FILE__).'" class="avatar_gallery_group">';
			$grouplist .= '<h2 class="groupname-list"><a href="'.get_term_link( (int)$group->term_id,'groups').'">'.$group->name.'</a></h2>

			<div class="desc_group_list">'.$group_desc.'</div>
			<div class="author-users">
			<p class="admin-group">'.__('Creator','rcl').': <a href="'.get_author_posts_url($admin_id).'">'.$names[$admin_id].'</a></p>
			<p class="users-group">'.__('Participants','rcl').': '.$users_count.'</p>
			</div>';

			$grouplist .= '</div>';
			$admin_id = false;
			$imade_id = false;
                        $options_gr = false;

		}
		if($n==$max_group_inpage) break;

	}
	$grouplist .= '</div>';

	$page_navi = navi_rcl($inpage,$count_group,$num_page,'','&filter='.$orderby);

	return $grouplist.$page_navi;

}
//Добавляем кнопку на удаление из группы при выводе пользователей группы
add_action('user_description','add_group_manage_users_button',100);
function add_group_manage_users_button(){
    global $user_ID,$group_id,$group_admin,$user;

    if(!$user_ID||$user_ID!=$group_admin||$user->user_id==$user_ID) return false;
    echo '<p class="alignright">'
        . '<a href="#" id="usergroup-'.$user->user_id.'" user-data="'.$user->user_id.'" group-data="'.$group_id.'" class="ban-group recall-button">'
            . __('Remove from group','rcl')
        . '</a>'
    . '</p>';
}

add_filter('file_scripts_rcl','get_scripts_group_rcl');
function get_scripts_group_rcl($script){

	$ajaxdata = "type: 'POST', data: dataString, dataType: 'json', url: wpurl+'wp-admin/admin-ajax.php',";
	$ajaxfile = "type: 'POST', data: dataString, dataType: 'json', url: rcl_url+'add-on/groups/ajax-request.php',";

	$script .= "
		jQuery('.edit .groupname').live('click',function(){
			var group_name = jQuery(this).text();
			var idgroup = jQuery('.group-info').attr('id');
			var id_group = parseInt(idgroup.replace(/\D+/g,''));
			jQuery(this).attr('class','groupname_edit');
			jQuery(this).html('<input class=\"new-name-group\" type=\"text\" id=\"name-group\" value=\"'+group_name+'\"><input id=\"edit-group-'+id_group+'\" class=\"edit_name_group\" type=\"button\" value=\"Обновить\"><input class=\"cancel_title\" type=\"button\" value=\"Отмена\">');
		});
		jQuery('.edit .avatar_gallery_group').live('click',function(){
			jQuery('.edit-avatar').html('<form action=\"\" method=\"post\" enctype=\"multipart/form-data\"><input type=\"file\" name=\"image_group\" class=\"field\"/><input type=\"submit\" name=\"addava\" value=\"Загрузить\"><input class=\"cancel_avatar\" type=\"button\" value=\"Отмена\"></form>');
		});
		jQuery('.edit .desc_group').live('click',function(){
			var desc_group = jQuery(this).text();
			jQuery(this).attr('class','text_desc_group');
			jQuery(this).html('<textarea name=\"group_desc\" id=\"group_desc\" rows=\"3\" style=\"width:70%;height:150px;\">'+desc_group+'</textarea><input  class=\"edit_desc_group\" type=\"button\" value=\"Обновить\" style=\"float: right; margin-bottom: 15px;\"><input class=\"cancel_desc\" type=\"button\" value=\"Отмена\">');
		});
		jQuery('.cancel_title').live('click',function(){
			var group_name = jQuery('#name-group').attr('value');
			jQuery('.groupname_edit').html(group_name);
			jQuery('.groupname_edit').attr('class','groupname');
		});
		jQuery('.cancel_avatar').live('click',function(){
			jQuery('.edit-avatar').empty();
		});
		jQuery('.cancel_desc').live('click',function(){
			var desc_group = jQuery('#group_desc').attr('value');
			jQuery('.text_desc_group').html('<p>'+desc_group+'</p>');
			jQuery('.text_desc_group').attr('class','desc_group');
		});

		jQuery('.show_form_add_group').live('click',function(){
			jQuery('.add_new_group').slideToggle();
		return false;
		});

	/* Смотрим всех пользователей группы */
		jQuery('.all-users-group, .float-window-recall .rcl-navi a').live('click',function(){
			var idgroup = jQuery('.group-info').attr('id');
			var page = parseInt(jQuery(this).text().replace(/\D+/g,''));
			var id_group = parseInt(idgroup.replace(/\D+/g,''));
			var dataString = 'action=all_users_group_recall&id_group='+ id_group;
			if(page) dataString += '&page='+ page;

			jQuery.ajax({
				".$ajaxdata."
				success: function(data){
					if(data['recall']==100){
						jQuery('.group_content').html(data['block_users']).fadeIn();
                                                var offsetTop = jQuery('.float-window-recall').offset().top;
                                                jQuery('body,html').animate({scrollTop:offsetTop -50}, 1000);
					} else {
						alert('Ошибка!');
					}
				}
			});
		return false;
		});
	/* Одобряем или отклоняем запрос на вступление в группу */
		jQuery('.request-list .request-access').click(function(){
			var idbutt = jQuery(this).attr('id');
			var id_group = parseInt(idbutt.replace(/\D+/g,''));
			var id_user = parseInt(jQuery(this).parent().parent().attr('id').replace(/\D+/g,''));
			var type_req = 0;
			if(idbutt == 'add-user-req-'+id_group) type_req = 1;
			if(idbutt == 'del-user-req-'+id_group) type_req = 2;
			var dataString = 'action=request_users_group_access_rcl&id_group='+id_group+'&req='+type_req+'&id_user='+id_user;
			jQuery.ajax({
				".$ajaxdata."
				success: function(data){
					if(data['result']==100){
						jQuery('#user-req-'+data['user']).remove();
					} else {
						alert('Ошибка!');
					}
				}
			});
		return false;
		});
	/* Редактируем название и описание группы */
		jQuery('.edit_name_group').live('click',function(){
			var idgroup = jQuery('.group-info').attr('id');
			var id_group = parseInt(idgroup.replace(/\D+/g,''));
			var new_name_group = jQuery('#name-group').attr('value');
			var dataString = 'action=edit_group_wp_recall&new_name_group='+new_name_group+'&id_group='+id_group+'&user_ID='+user_ID;
			jQuery.ajax({
			".$ajaxfile."
			success: function(data){
				if(data['int']==100){
						jQuery('.groupname_edit').html(new_name_group);
						jQuery('.groupname_edit').attr('class','groupname');
				} else {
					alert(data['res']+'-'+data['group']);
				}
			}
			});
			return false;
		});
		jQuery('.ban-group').live('click',function(){
			var user_id = jQuery(this).attr('user-data');
			var group_id = jQuery(this).attr('group-data');
			var dataString = 'action=group_ban_user_rcl&user_id='+user_id+'&group_id='+group_id+'&user_ID='+user_ID;
			jQuery.ajax({
			".$ajaxfile."
			success: function(data){
				if(data['int']==100){
					jQuery('#usergroup-'+user_id).replaceWith(data['content']);
				} else {
					alert('Ошибка');
				}
			}
			});
			return false;
		});
		jQuery('.remove-public-group').live('click',function(){
			var user_id = jQuery(this).attr('user-data');
			var group_id = jQuery(this).attr('group-data');
			var dataString = 'action=remove_user_publics_group_rcl&user_id='+user_id+'&group_id='+group_id+'&user_ID='+user_ID;
			jQuery.ajax({
			".$ajaxfile."
			success: function(data){
				if(data['int']==100){
					jQuery('#usergroup-'+user_id).replaceWith(data['content']);
				} else {
					alert('Ошибка');
				}
			}
			});
			return false;
		});
		jQuery('.edit_desc_group').live('click',function(){
			var idgroup = jQuery('.group-info').attr('id');
			var id_group = parseInt(idgroup.replace(/\D+/g,''));
			var new_desc_group = jQuery('#group_desc').attr('value');
			var dataString = 'action=edit_group_wp_recall&new_desc_group='+new_desc_group+'&id_group='+id_group+'&user_ID='+user_ID;
			jQuery.ajax({
			".$ajaxfile."
			success: function(data){
				if(data['int']==100){
					jQuery('.text_desc_group').html('<p>'+new_desc_group+'</p>');
					jQuery('.text_desc_group').attr('class','desc_group');
				} else {
					alert('Ошибка изменения!');
				}
			}
			});
				return false;
		});
		jQuery('.posts_group_block .sec_block_button').live('click',function(){
			var btn = jQuery(this);
			get_page_content_rcl(btn,'posts_group_block');
			return false;
		});
	";
	return $script;
}

function get_footer_scripts_groups_rcl($script){
	global $rcl_options;
	if($rcl_options['public_gallery_weight']) $weight = $rcl_options['public_gallery_weight'];
	else $weight = '2';

	if($rcl_options['count_image_gallery']) $cnt = $rcl_options['count_image_gallery'];
	else $cnt = '3';

	$script .= "
	var term_id_group = jQuery('input[name=\"term_id\"]').val();
	jQuery('#postgroupupload').fileapi({
		   url: rcl_url+'add-on/groups/upload-file.php?id_group='+term_id_group,
		   multiple: true,
		   maxSize: ".$weight." * FileAPI.MB,
		   maxFiles:".$cnt.",
		   clearOnComplete:true,
		   paramName:'uploadfile',
		   accept: 'image/*',
		   elements: {
			  ctrl: { upload: '.js-upload' },
			  empty: { show: '.b-upload__hint' },
			  emptyQueue: { hide: '.js-upload' },
			  list: '.js-files',
			  file: {
				 tpl: '.js-file-tpl',
				 preview: {
					el: '.b-thumb__preview',
					width: 100,
					height: 100
				 },
				 upload: { show: '.progress', hide: '.b-thumb__rotate' },
				 complete: { hide: '.progress' },
				 progress: '.progress .bar'
				}
		   },onSelect: function (evt, data){
				data.all;
				data.files;
				if( data.other.length ){
					var errors = data.other[0].errors;
					if( errors ){
						if(errors.maxSize) alert('Превышен допустимый размер файла.\nОдин файл не более ".$weight."MB');
					}
				}
			},
			onFilePrepare:function(evt, uiEvt){";
				if($cnt){
					$script .= "var num = jQuery('#temp-files li').size();
					if(num>=".$cnt."){
						jQuery('#status-temp').html('<span style=\"color:red;\">Вы уже достигли предела загрузок</span>');
						jQuery('#postgroupupload').fileapi('abort');
					}";
				}
			$script .= "},
			onFileComplete:function(evt, uiEvt){
				var result = uiEvt.result;
				if(result['string']){
					jQuery('#temp-files').append(result['string']);";
					if($cnt){
						$script .= "var num = jQuery('#temp-files li').size();
						if(num>=".$cnt."){
							jQuery('#status-temp').html('<span style=\"color:red;\">Вы уже достигли предела загрузок</span>');
							jQuery('#postgroupupload').fileapi('abort');
						}";
					}
				$script .= "}
			},
			onComplete:function(evt, uiEvt){
				var result = uiEvt.result;
				jQuery('#postgroupupload .js-files').empty();
			}
	});";
	return $script;
}
add_filter('file_footer_scripts_rcl','get_footer_scripts_groups_rcl');
?>
