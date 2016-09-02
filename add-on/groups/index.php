<?php

if (!is_admin()):
    add_action('rcl_enqueue_scripts','rcl_groups_scripts',10);
endif;

function rcl_groups_scripts(){
    rcl_enqueue_style('rcl-groups',rcl_addon_url('style.css', __FILE__));
    rcl_enqueue_script( 'rcl-groups', rcl_addon_url('js/scripts.js', __FILE__) );       
}

add_filter('rcl_init_js_variables','rcl_init_js_groups_variables',10);
function rcl_init_js_groups_variables($data){
    global $rcl_options;
    $data['groups']['avatar_size'] = (isset($rcl_options['avatar_weight']))? $rcl_options['avatar_weight']: 2;
    return $data;
}

require_once 'groups-init.php';
require_once 'groups-core.php';
require_once 'shortcodes.php';
if(is_admin()) require_once 'groups-options.php';
require_once 'groups-widgets.php';
if(!is_admin()||defined( 'DOING_AJAX' )) 
    require_once 'groups-public.php';
require_once 'upload-avatar.php';

if(function_exists('rcl_register_rating_type')){
    if(!is_admin()) add_action('init','rcl_register_rating_group_type');
    if(is_admin()) add_action('admin_init','rcl_register_rating_group_type');
    function rcl_register_rating_group_type(){
        rcl_register_rating_type(array('post_type'=>'post-group','type_name'=>__('Record groups','wp-recall'),'style'=>true));
    }
}

//обновление кеша вкладки групп ее админа
add_action('rcl_create_group','rcl_tab_groups_remove_cache',10);
add_action('rcl_pre_delete_group','rcl_tab_groups_remove_cache',10);
add_action('rcl_group_add_user','rcl_tab_groups_remove_cache',10);
add_action('rcl_group_remove_user','rcl_tab_groups_remove_cache',10);
function rcl_tab_groups_remove_cache($groupdata){
    global $rcl_options;
    if(isset($rcl_options['use_cache'])&&$rcl_options['use_cache']){
        
        if(is_array($groupdata)){
            $group_id = $groupdata['group_id'];
            $user_id = $groupdata['user_id'];
        }else{
            $group_id = $groupdata;
            $group = rcl_get_group($group_id);
            $user_id = $group->admin_id;
        }

        $string = rcl_format_url(get_author_posts_url($user_id),'groups');
        
        rcl_delete_file_cache($string);       
    }
}

/*add_action('update_post_rcl','rcl_groups_widget_posts_remove_cache',10,2);
function rcl_groups_widget_posts_remove_cache($post_id,$postdata){
    if($postdata['post_type']!='post-group') return false;
    
    global $rcl_options;
    if(isset($rcl_options['use_cache'])&&$rcl_options['use_cache']){
        $group_id = rcl_get_group_id_by_post($post_id);
        rcl_delete_file_cache('group-posts-widget:'.$group_id);
    }
}*/

add_action('init','rcl_add_postlist_group',10);
function rcl_add_postlist_group(){
    rcl_postlist('group','post-group',__('Record groups','wp-recall'),array('order'=>40));
}

add_action('init','rcl_add_tab_groups');
function rcl_add_tab_groups(){
    rcl_tab('groups','rcl_tab_groups',__('Groups','wp-recall'),array('ajax-load'=>true,'public'=>1,'cache'=>true,'class'=>'fa-group'));
}

add_action('init','rcl_register_default_group_sidebars',10);
function rcl_register_default_group_sidebars(){

    rcl_register_group_area(
        array(
            'name'=>__('Sidebar','wp-recall'),
            'id'=>'sidebar'
        )
    );

    rcl_register_group_area(
        array(
            'name'=>__('Main','wp-recall'),
            'id'=>'content'
        )
    );

    rcl_register_group_area(
        array(
            'name'=>__('Footer','wp-recall'),
            'id'=>'footer'
        )
    );

}

function rcl_group(){
    global $rcl_group;
    
    if(rcl_is_group_can('admin'))
        rcl_enqueue_script( 'groups-image-uploader', rcl_addon_url('js/groups-image-uploader.js', __FILE__),false,true);

    $admin = (rcl_is_group_can('admin')||current_user_can('edit_others_posts'))? 1: 0;

    $class = ($admin)? 'class="admin-view"': '';

    echo '<div id="rcl-group" data-group="'.$rcl_group->term_id.'" '.$class.'>';

    if($admin)
        echo rcl_group_admin_panel();

    echo '<div id="group-popup"></div>';

    rcl_include_template('single-group.php',__FILE__);

    echo '</div>';

}

/*deprecated*/
function add_post_in_group(){
    rcl_group();
}

function rcl_tab_groups($master_id){

    global $wpdb,$user_ID,$rcl_options;
    
    $content = '';

    if($master_id==$user_ID){

        $group_can_public = (isset($rcl_options['public_group_access_recall']))? $rcl_options['public_group_access_recall']: false;
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

        if($public_groups){
            $content = '<div id="create-group">'
                . '<form method="post">'
                    . '<div class="form-field">'
                        . '<input type="text" required placeholder="'.__('Enter the name of the new group','wp-recall').'" name="rcl_group[name]">'
                        . '<input type="submit" class="recall-button" name="rcl_group[create]" value="'.__('Create','wp-recall').'">'
                    . '</div>'
                    . wp_nonce_field('rcl-group-create','_wpnonce',true,false)
                . '</form>'
            . '</div>';
        }
    }

    $content .= rcl_get_grouplist(array('filters'=>1,'search_form'=>0,'user_id'=>$master_id));

    return $content;
}

function rcl_get_link_group_tag($content){
	global $post,$user_ID,$rcl_group;
	if($post->post_type!='post-group') return $content;

	$group_data = get_the_terms( $post->ID, 'groups' );

	foreach((array)$group_data as $data){
		if($data->parent==0) $group_id = $data->term_id;
		else $tag = $data;
	}

	if(!isset($tag)||!$tag) return $content;

        if( doing_filter('the_excerpt') ){

            if(!$rcl_group) $rcl_group = rcl_get_group($group_id);

            if($rcl_group->group_status=='closed'){
                if($rcl_group->admin_id!=$user_ID){

                    $user_status = rcl_get_group_user_status($user_ID,$rcl_group->term_id);

                    if(!$user_status) $content = rcl_close_group_post_content();

                }
            }
        }

	$cat = '<p class="post-group-meta"><i class="fa fa-folder-open rcl-icon"></i>'.__('Category in the group','wp-recall').': <a href="'. get_term_link( (int)$group_id, 'groups' ) .'?group-tag='.$tag->slug.'">'. $tag->name .'</a></p>';

	return $cat.$content;
}

function rcl_init_get_link_group_tag(){
	if(is_single()) add_filter('the_content','rcl_get_link_group_tag',80);
	else add_filter('the_excerpt','rcl_get_link_group_tag',80);
}
add_action('wp','rcl_init_get_link_group_tag');

function rcl_init_namegroup(){
	if(is_single()) add_filter('the_content','rcl_add_namegroup',80);
        if(is_search()) add_filter('the_excerpt','rcl_add_namegroup',80);
}
add_action('wp','rcl_init_namegroup');

function rcl_add_namegroup($content){
    global $post;
    if(get_post_type( $post->ID )!='post-group') return $content;

    $group = rcl_get_group_by_post($post->ID);
    
    if(!$group) return $content;

    $group_link = '<p class="post-group-meta"><i class="fa fa-users rcl-icon"></i><span>'.__('Published in the group','wp-recall').'</span>: <a href="'. get_term_link( (int)$group->term_id, 'groups' ) .'">'. $group->name .'</a></p>';

    $content = $group_link.$content;
    return $content;
}

if(is_admin())
    add_action('created_groups','rcl_groups_admin_create',10);
function rcl_groups_admin_create($term_id){
   global $user_ID,$wpdb;
   
   $term = get_term( $term_id, 'groups' );
   
   if($term->parent) return false;
   
   $result = $wpdb->insert(
        RCL_PREF.'groups',
        array(
            'ID'=>$term_id,
            'admin_id'=>$user_ID,
            'group_status'=>'open',
            'group_date'=>current_time('mysql')
        )
    );

    if(!$result) return false;

    rcl_update_group_option($term_id,'can_register',1);
    rcl_update_group_option($term_id,'default_role','author');

    do_action('rcl_create_group',$term_id);
}

//Создаем новую группу
function rcl_new_group(){

    global $user_ID,$wpdb;

    $name_group = sanitize_text_field($_POST['rcl_group']['name']);
    $group_id = rcl_create_group(array('name'=>$name_group,'admin_id'=>$user_ID));

    if(!$group_id){
        rcl_notice_text(__('Create a group failed','wp-recall'),'error');
    }else{
        wp_redirect(get_term_link( (int)$group_id, 'groups' ));
        exit;
    }

}

function rcl_init_group_create( ) {
  if ( isset($_POST['rcl_group']) ) {
    if( !wp_verify_nonce( $_POST['_wpnonce'], 'rcl-group-create' ) ) return false;
    add_action( 'wp', 'rcl_new_group' );
  }
}
add_action('init', 'rcl_init_group_create');

add_filter('rcl_group_thumbnail','rcl_group_add_thumb_buttons');
function rcl_group_add_thumb_buttons($content){
    $rcl_group;

    if(!rcl_is_group_can('admin')) return $content;

    $content .= '<div id="group-avatar-upload">
            <span id="file-upload" class="fa fa-download">
                <input type="file" id="groupavatarupload" accept="image/*" name="uploadfile">
            </span>
	</div>
	<span id="avatar-upload-progress"></span>';
    return $content;
}

add_action('wp','rcl_group_actions');
function rcl_group_actions(){
    global $user_ID,$rcl_group;

    if(!isset($_POST['group-submit'])) return false;
    if( !wp_verify_nonce( $_POST['_wpnonce'], 'group-action-' . $user_ID ) ) return false;

    switch($_POST['group-action']){
        case 'leave': rcl_group_remove_user($user_ID,$rcl_group->term_id); break;
        case 'join': rcl_group_add_user($user_ID,$rcl_group->term_id); break;
        case 'ask': rcl_group_add_request_for_membership($user_ID,$rcl_group->term_id); break;
        case 'update':
            $args = $_POST['group-options'];
            $args['group_id'] = $rcl_group->term_id;
            rcl_update_group($args);
            break;
        case 'update-widgets':
            $data = $_POST['data'];
            rcl_update_group_widgets($rcl_group->term_id,$data);
            break;
    }

    wp_redirect(rcl_get_group_permalink($rcl_group->term_id)); exit;
}

function rcl_get_group_options($group_id){
    global $rcl_group,$user_ID;

    $default_role = rcl_get_group_option($group_id,'default_role');
    $category = rcl_get_group_option($group_id,'category');

    $category = (is_array($category))? implode(', ',$category): $category;

    /*$data = array( 'wpautop' => 1
        ,'media_buttons' => 0
        ,'textarea_name' => 'group-options[description]'
        ,'textarea_rows' => 10
        ,'tabindex' => null
        ,'editor_css' => ''
        ,'editor_class' => 'autosave'
        ,'teeny' => 0
        ,'dfw' => 0
        ,'tinymce' => 1
        ,'quicktags' => 1
    );

    ob_start();
    wp_editor( esc_textarea(strip_tags(rcl_get_group_description($group_id))), 'contentarea', $data );
    $editor = ob_get_contents();
    ob_end_clean();*/

    $content = '<div id="group-options">'
        . '<h3>'.__('Group settings','wp-recall').'</h3>'
        . '<form method="post">'
            . '<div class="group-option">'
                . '<label>'.__('Group name','wp-recall').'</label>'
                . '<input type="text" name="group-options[name]" value="'.$rcl_group->name.'">'
            . '</div>'
            . '<div class="group-option">'
                . '<label>'.__('Description','wp-recall').'</label>'
                . '<textarea name="group-options[description]">'.esc_html(strip_tags(rcl_get_group_description($group_id))).'</textarea>'
            . '</div>'
            . '<div class="group-option">'
                . '<label>'.__('Group status','wp-recall').'</label>'
                . '<select name="group-options[status]">'
                . '<option '.selected($rcl_group->group_status,'open',false).' value="open">'.__('Open group','wp-recall').'</option>'
                . '<option '.selected($rcl_group->group_status,'closed',false).' value="closed">'.__('Closed group','wp-recall').'</option>'
                . '</select>'
            . '</div>'
            . '<div class="group-option">'
                . '<label>'.__('Membership','wp-recall').'</label>'
                . '<input type="checkbox" name="group-options[can_register]" '.checked(rcl_get_group_option($group_id,'can_register'),1,false).' value="1"> '.__('Registration is permitted','wp-recall')
                . '<label>'.__('The role of the new user','wp-recall').'</label>'
                . '<select name="group-options[default_role]">'
                . '<option '.selected($default_role,'reader',false).' value="reader">'.__('Visitor','wp-recall').'</option>'
                . '<option '.selected($default_role,'author',false).' value="author">'.__('Author','wp-recall').'</option>'
                . '</select>'
            . '</div>'
            . '<div class="group-option">'
                . '<label>'.sprintf('%s <small>(%s)</small>',__('Group categories','wp-recall'),__('separated by commas','wp-recall')).'</label>'
                . '<textarea name="group-options[category]">'.$category.'</textarea>'
            . '</div>';

            $content = apply_filters('rcl_group_options',$content);

            $content .= '<div class="group-option">'
                . '<input type="submit" class="recall-button" name="group-submit" value="'.__('Save settings','wp-recall').'">'
                . '<input type="hidden" name="group-action" value="update">'
                . wp_nonce_field( 'group-action-' . $user_ID,'_wpnonce',true,false )
            . '</div>'
        . '</form>'
    . '</div>';

    return $content;
}

function rcl_get_group_requests_content($group_id){

    $requests = rcl_get_group_option($group_id,'requests_group_access');

    $content = '<h3>'.__('Requests for access to the group','wp-recall').'</h3>';

    if(!$requests){
        $content .= '<p>'.__('No queries','wp-recall').'</p>';
        return $content;
    }

    add_action('rcl_user_description','rcl_add_group_access_button');

    $content .= rcl_get_userlist(array('include'=>implode(',',$requests),'filters'=>0,'orderby'=>'time_action','data'=>'rating_total,posts_count,comments_count,description,user_registered'));

    return $content;
}

function rcl_add_group_access_button(){
    global $rcl_user;
    echo '<div class="group-request" data-user="'.$rcl_user->ID.'">';
    echo rcl_get_button(__('Approve request','wp-recall'),'#',array('icon'=>'fa-thumbs-up','class'=>'apply-request','attr'=>'data-request=1'));
    echo rcl_get_button(__('Reject request','wp-recall'),'#',array('icon'=>'fa-thumbs-down','class'=>'apply-request','attr'=>'data-request=0'));
    echo '</div>';
}

function rcl_add_group_user_options(){
    global $rcl_user,$rcl_group,$user_ID;

    if($user_ID==$rcl_user->ID) return false;
    if($rcl_user->ID==$rcl_group->admin_id) return false;

    $group_roles = rcl_get_group_roles();

    echo '<div id="options-user-'.$rcl_user->ID.'" class="group-request" data-user="'.$rcl_user->ID.'">';

        echo '<div class="group-user-option">';
            echo rcl_get_group_callback('rcl_group_ajax_delete_user',__('Delete','wp-recall'));
        echo '</div>';

        echo '<div class="group-user-option">';
            echo __('User status','wp-recall').' <select name="user_role">';
            foreach($group_roles as $role=>$data){
                echo '<option value="'.$role.'" '.selected($rcl_user->user_role,$role,false).'>'.$data['role_name'].'</option>';
            }
            echo '</select>';
            echo rcl_get_group_callback('rcl_group_ajax_update_role',__('Save','wp-recall'),array('user_role'));
        echo '</div>';

    echo '</div>';
}

add_action('wp_ajax_rcl_apply_group_request','rcl_apply_group_request');
function rcl_apply_group_request(){
    global $rcl_group,$user_ID;
    
    rcl_verify_ajax_nonce();

    $user_id = intval($_POST['user_id']);
    $apply = intval($_POST['apply']);
    $group_id = intval($_POST['group_id']);

    $rcl_group = rcl_get_group($group_id);

    if($rcl_group->admin_id!=$user_ID) return false;

    $requests = rcl_get_group_option($group_id,'requests_group_access');
    $key = array_search($user_id, $requests);

    if(!$requests||false===$key) return false;

    unset($requests[$key]);

    if($apply){

        $subject = __('Request access to the group approved!','wp-recall');
        $textmail = sprintf(
                '<h3>%s "'.$rcl_group->name.'"!</h3>
                <p>%s</p>
                <p>%s.</p>
                <p>%s:</p>
                <p>'.get_term_link( (int)$group_id, 'groups' ).'</p>',
                __('Welcome to the group','wp-recall'),
                sprintf(__('Congratulations , your request for access to a private group on "%s" website has been approved','wp-recall'),get_bloginfo('name')),
                __('Now you can take part in the life of the group as it is a full participant.','wp-recall'),
                __('You can visit the group by clicking on the link','wp-recall')
            );

        rcl_group_add_user($user_id,$group_id);

        $log['result']='<span class="success">'.__('The request was accepted','wp-recall').'</span>';

    }else{

        $log['result']='<span class="error">'.__('Request rejected','wp-recall').'</span>';
        $subject = __('The request to access the group rejected.','wp-recall');
        $textmail = sprintf('<p>'.__('We are sorry, but your request to access the private group "%s" on the site "%s" was rejected by its administrator','wp-recall').'.</p>',
                $rcl_group->name,
                get_bloginfo('name')
            );

    }

    $user_email = get_the_author_meta('user_email',$user_id);
    rcl_mail($user_email, $subject, $textmail);

    rcl_update_group_option($group_id,'requests_group_access',$requests);


    $log['user_id']=$user_id;
    echo json_encode($log);
    exit;
}

//исключаем из поиска публикации из закрытых групп 
add_action('pre_get_posts','rcl_search_filter_closed_posts',10);
function rcl_search_filter_closed_posts($query) {

    if ( ! is_admin() && $query->is_main_query() ) {
        
        global $user_ID;
        
        if ($query->is_search) {
          $groups = rcl_get_closed_groups($user_ID);

          if(!$groups) return $query; 

          $query->set( 'tax_query', array(
                array(
                    'taxonomy' => 'groups',
                    'field' => 'id',
                    'terms' => $groups,
                    'operator' => 'NOT IN'
                )
            ) );
        }
    }
    return $query;
}

//исключаем из фида комментарии из закрытых групп
add_filter('rcl_feed_ignored_posts_in_comments','rcl_add_feed_ignored_posts_in_comments',10);
function rcl_add_feed_ignored_posts_in_comments($posts){
    global $user_ID;
    
    $ignored_posts = rcl_get_closed_group_posts($user_ID);
    
    return $ignored_posts;
}

//исключаем из фида публикации из закрытых групп
add_filter('rcl_feed_posts_array','rcl_add_feed_group_posts',10);
function rcl_add_feed_group_posts($posts){
    global $wpdb,$user_ID;
    
    $cachekey = json_encode(array('rcl_add_feed_group_posts',$posts));
    $cache = wp_cache_get( $cachekey );
    if ( $cache )
        return $cache;
    
    $ignored_posts = rcl_get_closed_group_posts($user_ID);

    if($ignored_posts) $posts = array_diff($posts,$ignored_posts);

    $groups = $wpdb->get_col("SELECT groups_users.group_id, groups.ID "
            . "FROM ".RCL_PREF."groups_users AS groups_users "
            . "INNER JOIN ".RCL_PREF."groups AS groups ON groups_users.user_id=groups.admin_id "
            . "WHERE (groups_users.user_id='$user_ID' OR groups.admin_id='$user_ID') "
            . "GROUP BY groups_users.group_id, groups.ID");

    if($groups){
       
        $groups = array_unique($groups);

        $objects = $wpdb->get_col("SELECT term_relationships.object_id "
            . "FROM $wpdb->term_relationships AS term_relationships "
            . "INNER JOIN $wpdb->term_taxonomy AS term_taxonomy ON term_relationships.term_taxonomy_id=term_taxonomy.term_taxonomy_id "
            . "WHERE term_taxonomy.term_id IN (".implode(',',$groups).")");

        if($objects) $posts = array_unique(array_merge($posts,$objects));

    }
    
    wp_cache_add( $cachekey, $posts );

    return $posts;
}