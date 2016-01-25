<?php
function rcl_group_init(){
    global $wp_query,$wpdb,$rcl_group,$user_ID;

    if(!isset($wp_query->query_vars['groups'])) return false;

    $group_id = 0;

    $curent_term = get_term_by('slug', $wp_query->query_vars['groups'], 'groups');

    if($curent_term->parent!=0) $group_id = $curent_term->parent;
    else $group_id = $curent_term->term_id;

    if(!$group_id) return false;

    $rcl_group = rcl_get_group($group_id);

    $rcl_group->current_user = rcl_group_current_user_status();

    $rcl_group->single_group = 1;

    if(rcl_is_group_can('admin')||current_user_can('edit_others_posts'))
        rcl_sortable_scripts();

    return $rcl_group;

}

function rcl_create_group($groupdata){
    global $wpdb;

    $args = array(
         'alias_of'     => ''
        ,'description'  => ''
        ,'parent'       => 0
        ,'slug'         => ''
    );

    $data = wp_insert_term( $groupdata['name'], 'groups', $args );

    if(isset($data->error_data)){

        $term = get_term((int)$data->error_data['term_exists'], 'groups');

        for($a=2;$a<10;$a++){
            $args['slug'] = $term->slug.'-'.$a;
            $data = wp_insert_term( $groupdata['name'], 'groups', $args );
            if(!isset($data->error_data)) break;
        }

    }

    if(!$data||isset($data->errors)) return false;

    $group_id = $data['term_id'];

    $result = $wpdb->insert(
        RCL_PREF.'groups',
        array(
            'ID'=>$group_id,
            'admin_id'=>$groupdata['admin_id'],
            'group_status'=>'open',
            'group_date'=>current_time('mysql')
        )
    );

    if(!$result) return false;

    rcl_update_group_option($group_id,'can_register',1);
    rcl_update_group_option($group_id,'default_role','author');

    do_action('rcl_create_group',$group_id);

    return $group_id;
}

function rcl_update_group($args){
    global $wpdb;

    if(isset($args['name'])){
        $res = $wpdb->update( $wpdb->prefix.'terms',
            array( 'name' => $args['name'] ),
            array( 'term_id' => $args['group_id'] )
         );
     }
     if(isset($args['description'])){
        $res = $wpdb->update(  $wpdb->prefix.'term_taxonomy',
            array( 'description' => esc_html(stripslashes_deep($args['description']))),
            array( 'term_id' => $args['group_id'] )
         );
     }
     if(isset($args['status'])){
        $res = $wpdb->update(  RCL_PREF.'groups',
            array( 'group_status' => $args['status'] ),
            array( 'ID' => $args['group_id'] )
         );
     }

     if(isset($args['default_role'])){
        rcl_update_group_option($args['group_id'],'default_role',$args['default_role']);
     }

     if($args['category']){
        $category = array_map('trim', explode(',',$args['category']));
        rcl_update_group_option($args['group_id'],'category',$category);
     }

     $can_register = (!isset($args['can_register']))? 0: 1;
     rcl_update_group_option($args['group_id'],'can_register',$can_register);


     do_action('rcl_update_group',$args);
}

function rcl_delete_group($group_id){    
    rcl_delete_term_groups($group_id, $group_id, 'groups');
}

add_action('delete_term', 'rcl_delete_term_groups',10,3);
function rcl_delete_term_groups($term_id, $tt_id, $taxonomy){
    if(!$taxonomy||$taxonomy!='groups') return false;
    global  $wpdb;
    
    do_action('rcl_pre_delete_group',$term_id);
    
    $imade_id = rcl_get_group_option($term_id,'avatar_id');
    wp_delete_attachment($imade_id,true);
    $wpdb->query($wpdb->prepare("DELETE FROM ".RCL_PREF."groups_options WHERE group_id = '%d'",$term_id));
    $wpdb->query($wpdb->prepare("DELETE FROM ".RCL_PREF."groups_users WHERE group_id = '%d'",$term_id));
    $wpdb->query($wpdb->prepare("DELETE FROM ".RCL_PREF."groups WHERE ID = '%d'",$term_id));
}

function rcl_register_group_area($contents){
    global $rcl_group_area;
    $rcl_group_area[] = $contents;
}

function rcl_remove_group_area($area_id){
    global $rcl_group_area;    
    foreach($rcl_group_area as $key=>$area){
        if(isset($area['id'])&&$area['id']==$area_id){
            unset($rcl_group_area[$key]);
            return true;
        }
    } 
    return false;
}

function rcl_is_group_area($area_id){
    global $rcl_group_area;
    foreach($rcl_group_area as $key=>$area){
        if(isset($area['id'])&&$area['id']==$area_id){
            return true;      
        }
    } 
    return false;
}

function rcl_is_group_single(){
    global $rcl_group;
    if(isset($rcl_group->single_group)&&$rcl_group->single_group) return true;
    return false;
}

function rcl_get_group_roles(){

    $group_roles = array(
        'banned'=>array(
            'user_level'=>0,
            'role_name'=>__('Ban','wp-recall')
        ),
        'reader'=>array(
            'user_level'=>1,
            'role_name'=>__('Reader','wp-recall')
        ),
        'author'=>array(
            'user_level'=>5,
            'role_name'=>__('Author','wp-recall')
        ),
        'moderator'=>array(
            'user_level'=>7,
            'role_name'=>__('Moderator','wp-recall')
        ),
        'admin'=>array(
            'user_level'=>10,
            'role_name'=>__('Administrator','wp-recall')
        )
    );

    return $group_roles;
}

function rcl_is_group_user(){
    global $rcl_group;
    if($rcl_group->current_user) return true;
    else return false;
}

function rcl_is_group_can($role){
    global $rcl_group;

    $group_roles = rcl_get_group_roles();

    if(!isset($rcl_group->current_user))
        $rcl_group->current_user = rcl_group_current_user_status();

    $user_role = $rcl_group->current_user;

	if(!$user_role) return false;

    if($group_roles[$user_role]['user_level']>=$group_roles[$role]['user_level']) return true;
    else return false;
}

function rcl_get_group_permalink($term_id){
    return get_term_link( (int)$term_id,'groups');
}

function rcl_group_permalink(){
    global $rcl_group;
    if(!$rcl_group) return false;
    echo rcl_get_group_permalink($rcl_group->term_id);
}

function rcl_group_name(){
    global $rcl_group;
    if(!$rcl_group) return false;
    echo $rcl_group->name;
}

function rcl_group_post_counter(){
    global $rcl_group;
    if(!$rcl_group) return false;
    echo $rcl_group->count;
}

function rcl_group_status(){
    global $rcl_group;
    if(!$rcl_group) return false;

    switch($rcl_group->group_status){
        case 'open': echo __('Opened group','wp-recall'); break;
        case 'closed': echo __('Closed group','wp-recall'); break;
    }
}

function rcl_group_count_users(){
    global $rcl_group;
    if(!$rcl_group) return false;
    echo $rcl_group->group_users;
}

function rcl_get_group_thumbnail($group_id,$size='thumbnail'){
    $avatar_id = rcl_get_group_option($group_id,'avatar_id');
    if(!$avatar_id){
        $url = rcl_addon_url('img/group-avatar.png',__FILE__);
    }else{
        $image_attributes = wp_get_attachment_image_src($avatar_id,$size);
        $url = $image_attributes[0];
    }

    $attr = (isset($image_attributes))? "width=$image_attributes[1] height=$image_attributes[2]": '';

    $content = '<img src="'.$url.'" '.$attr.'>';

    if(rcl_is_group_single())  $content = apply_filters('rcl_group_thumbnail',$content);

    return $content;
}

function rcl_group_thumbnail($size='thumbnail'){
    global $rcl_group;
    if(!$rcl_group) return false;
    echo rcl_get_group_thumbnail($rcl_group->term_id,$size);
}

function rcl_has_group_thumbnail($group_id){
    return rcl_get_group_option($group_id,'avatar_id');
}

function rcl_get_group_description($group_id){
    return term_description( $group_id, 'groups' );
}

function rcl_group_description(){
    global $rcl_group;
    if(!$rcl_group) return false;
    echo rcl_get_group_description($rcl_group->term_id);
}

function rcl_group_current_user_status(){
    global $wpdb,$rcl_group,$user_ID;
    if($rcl_group->admin_id==$user_ID) return 'admin';
    return rcl_get_group_user_status($user_ID,$rcl_group->term_id);
}

function rcl_get_group_user_status($user_id,$group_id){
    global $wpdb;
    return $wpdb->get_var("SELECT user_role FROM ".RCL_PREF."groups_users WHERE group_id='$group_id' AND user_id='$user_id'");
}

//вносим изменения в запрос вывода пользователей
//при получении юзеров через фильтры группы
function rcl_group_add_users_query($query){
    global $rcl_group;

    $role = (isset($_POST['value']))? $_POST['value']: false;

    $role_query = ($role&&$role!='all')? "='".$role."'": "NOT IN ('admin','moderator')";


    $query->query['select'][] = "groups_users.user_role";

    if($role=='admin'){
        $query->query['join'][] = "LEFT JOIN ".RCL_PREF."groups_users AS groups_users ON users.ID=groups_users.user_id";
        $query->query['where'][] = "(groups_users.user_role = 'admin' AND groups_users.group_id='$rcl_group->term_id') OR (users.ID='$rcl_group->admin_id')";
        $query->query['group'] = "users.ID";
    }else{
        $query->query['join'][] = "INNER JOIN ".RCL_PREF."groups_users AS groups_users ON users.ID=groups_users.user_id";
        $query->query['where'][] = "groups_users.group_id = '$rcl_group->term_id' AND groups_users.user_role $role_query";
    }

    return $query;
}

function rcl_group_users($number,$template='mini'){
    global $rcl_group;
    if(!$rcl_group) return false;
    add_filter('rcl_users_query','rcl_group_add_users_query');
    switch($template){
        case 'rows': $data = 'descriptions,rating_total,posts_count,comments_count,user_registered'; break;
        case 'avatars': $data = 'rating_total'; break;
        default: $data = '';
     }
    echo rcl_get_userlist(array('number'=>$number,'template'=>$template,'orderby'=>'time_action','data'=>$data));
}

function rcl_get_group_users($group_id){
    global $rcl_group,$user_ID;

    add_filter('rcl_users_query','rcl_group_add_users_query');

    if(rcl_is_group_can('moderator')||current_user_can('edit_others_posts'))
        add_action('rcl_user_description','rcl_add_group_user_options');

    $page = (isset($_POST['page']))? $_POST['page']: false;
    $users_role = (isset($_POST['value']))? $_POST['value']: "all";

    $content = '<div id="group-userlist">';

    $group_roles = rcl_get_group_roles();

    $content .= '<div class="rcl-data-filters">'
            . 'Сортировка по статусу: ';

    foreach($group_roles as $role=>$data){
        $class = ($role==$users_role)? 'filter-active': 'data-filter';
        $class .= ' recall-button';
        $content .=  rcl_get_group_link('rcl_get_group_users',$data['role_name'],array('value'=>$role,'class'=>$class));
    }
    $content .= '</div>';

    $content .= '<h3>'.__('Group members','wp-recall').'</h3>';
    $content .= rcl_get_userlist(array('paged'=>$page,'filters'=>0,'orderby'=>'time_action','data'=>'rating_total,posts_count,comments_count,description,user_registered','add_uri'=>array('value'=>$users_role)));
    $content .= '</div>';

    return $content;
}

function rcl_get_group_option($group_id,$option_key){
    global $wpdb;
    $value = $wpdb->get_var("SELECT option_value FROM ".RCL_PREF."groups_options WHERE group_id='$group_id' AND option_key='$option_key'");
    return maybe_unserialize( $value );
}

function rcl_update_group_option($group_id,$option_key,$new_value){
    global $wpdb;

    $value = rcl_get_group_option($group_id,$option_key);

    if(!isset($value)) return rcl_add_group_option($group_id,$option_key,$new_value);

    $new_value = maybe_serialize($new_value);

    return $wpdb->update(
        RCL_PREF."groups_options",
        array('option_value'=>$new_value),
        array('group_id'=>$group_id,'option_key'=>$option_key)
    );
}

function rcl_add_group_option($group_id,$option_key,$value){
    global $wpdb;

    $value = maybe_serialize($value);

    return $wpdb->insert(
        RCL_PREF."groups_options",
        array(
            'option_value'=>$value,
            'group_id'=>$group_id,
            'option_key'=>$option_key
        )
    );
}

function rcl_delete_group_option($group_id,$option_key){
    global $wpdb;
    return $wpdb->query("DELETE FROM ".RCL_PREF."groups_options WHERE group_id='$group_id' AND option_key='$option_key'");
}

function rcl_get_group($group_id){

    $group = rcl_get_groups(array('include'=>$group_id));

    return $group[0];
}

function rcl_get_groups($args){

    include_once 'classes/rcl-groups.php';
    $groups = new Rcl_Groups($args);

    $groupsdata = $groups->get_groups();

    if(!$groupsdata){
        return false;
    }

    return $groupsdata;
}

function rcl_update_group_user_role($user_id,$group_id,$new_role){
    global $wpdb;

    $result = $wpdb->update(
        RCL_PREF."groups_users",
        array(
            'user_role'=>$new_role
        ),
        array(
            'user_id'=>$user_id,
            'group_id'=>$group_id
        )
    );

    do_action('rcl_update_group_user_role',array(
            'user_id'=>$user_id,
            'group_id'=>$group_id,
            'user_role'=>$new_role
        ));

    return $result;
}

function rcl_group_add_user($user_id,$group_id){
    global $wpdb;

    if(rcl_get_group_user_status($user_id,$group_id)) return false;

    $default_role = rcl_get_group_option($group_id, 'default_role');

    $role = ($default_role)?$default_role:'author';

    $args = array(
        'group_id'      =>  $group_id,
        'user_id'       =>  $user_id,
        'user_role'     =>  $role,
        'status_time'   =>  0,
        'user_date'     =>  current_time('mysql')
    );

    $result = $wpdb->insert(
        RCL_PREF.'groups_users',
        $args
    );

    rcl_group_update_users_count($group_id);

    do_action('rcl_group_add_user',$args);

    return $result;
}

function rcl_group_remove_user($user_id,$group_id){
    global $wpdb;

    if(!rcl_get_group_user_status($user_id,$group_id)) return false;

    $result = $wpdb->query("DELETE FROM ".RCL_PREF."groups_users WHERE group_id='$group_id' AND user_id='$user_id'");

    rcl_group_update_users_count($group_id);
    
    $args = array(
        'group_id'      =>  $group_id,
        'user_id'       =>  $user_id        
    );

    do_action('rcl_group_remove_user',$args);

    return $result;
}

function rcl_group_update_users_count($group_id){
    global $wpdb;

    $amount = $wpdb->get_var("SELECT COUNT(ID) FROM ".RCL_PREF."groups_users WHERE group_id='$group_id'");

    $result = $wpdb->update(
        RCL_PREF."groups",
        array(
            'group_users'=>$amount
        ),
        array(
            'ID'=>$group_id
        )
    );

    return $result;
}

function rcl_group_add_request_for_membership($user_id,$group_id){

    $rcl_group = rcl_get_group($group_id);

    $requests = rcl_get_group_option($group_id,'requests_group_access');
    $requests[] = $user_id;
    rcl_update_group_option($group_id,'requests_group_access',$requests);

    $subject = __('Request for access to the group','wp-recall');
    $textmail = '
    <p>Вы получили новый запрос на доступ к администрируемой вами группе "'.$rcl_group->name.'" на сайте "'.get_bloginfo('name').'".</p>
    <h3>Информация о пользователе:</h3>
    <p><b>Профиль пользователя</b>: <a href="'.get_author_posts_url($user_id).'">'.get_the_author_meta('display_name',$user_id).'</a></p>
    <p>Вы можете одобрить или отклонить запрос перейдя по ссылке:</p>
    <p>'.get_term_link( (int)$group_id, 'groups' ).'</p>';
    $admin_email = get_the_author_meta('user_email',$rcl_group->admin_id);
    rcl_mail($admin_email, $subject, $textmail);
}

/*deprecated*/
function rcl_get_options_group($group_id){
    $category = rcl_get_group_option($group_id,'category');
    $category = (is_array($category))? implode(', ',$category): $category;
    return array('tags'=>$category);
}

function rcl_get_tags_list_group($tags,$post_id=null,$first=null){
	$tg_lst = '';
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

function rcl_get_group_link($callback,$name,$args=false){
    global $rcl_group;

    $value = (isset($args['value']))? 'data-value="'.$args['value'].'"': '';

    $class = (isset($args['class']))? $args['class']: '';

    $content = '<a href="#" data-callback="'.$callback.'" data-group="'.$rcl_group->term_id.'" '.$value.' class="rcl-group-link recall-button '.$class.'">
                    <span>'.$name.'</span>
                </a>';
    return $content;
}

function rcl_get_group_callback($callback,$name,$args=false){
    global $rcl_group;

    if($args){
        $attr = 'data-name="'.implode(',',$args).'"';
    }

    $content = '<a href="#" data-callback="'.$callback.'" data-group="'.$rcl_group->term_id.'" '.$attr.' class="rcl-group-callback recall-button">
                    <span>'.$name.'</span>
                </a>';
    return $content;
}

add_action('wp_ajax_rcl_get_group_link_content','rcl_get_group_link_content');
add_action('wp_ajax_nopriv_rcl_get_group_link_content','rcl_get_group_link_content');
function rcl_get_group_link_content(){
    global $rcl_group;
    
    rcl_verify_ajax_nonce();
    
    $group_id = intval($_POST['group_id']);
    $callback = $_POST['callback'];
    $rcl_group = rcl_get_group($group_id);

    $content = '<div id="group-link-content">';
    $content .= '<a href="#" class="close-content" onclick="jQuery(\'#group-link-content\').remove();jQuery(\'#group-popup\').removeAttr(\'style\');return false;"><i class="fa fa-reply"></i>'.__('Return to the group','wp-recall').'</a>';
    $content .= $callback($group_id);
    $content .= '</div>';

    echo json_encode($content);exit;
}


add_action('wp_ajax_rcl_group_callback','rcl_group_callback');
function rcl_group_callback(){
    global $rcl_group;
    $group_id = intval($_POST['group_id']);
    $user_id = intval($_POST['user_id']);
    $callback = $_POST['callback'];
    $rcl_group = rcl_get_group($group_id);

    $result = $callback($group_id,$user_id);

    echo json_encode($result);exit;
}

function rcl_group_ajax_delete_user($group_id,$user_id){
    $result = rcl_group_remove_user($user_id,$group_id);
    if($result){
        $log['success'] = __('User removed','wp-recall');
        $log['place'] = 'buttons';
    }else{
        $log['error'] = __('Error','wp-recall');
        $log['place'] = 'notice';
    }
    return $log;
}

function rcl_group_ajax_update_role($group_id,$user_id){
    global $user_ID;

    if($user_ID==$user_id) return false;

    $new_role = $_POST['user_role'];
    $result = rcl_update_group_user_role($user_id,$group_id,$new_role);
    if($result){
        $log['success'] = __('User Status updated','wp-recall');
    }else{
        $log['error'] = __('Error','wp-recall');
    }
    $log['place'] = 'notice';
    return $log;
}

function rcl_get_group_category_list(){
    global $rcl_group;

    $targs = array(
        'number'        => 0
        ,'hide_empty'   => true
        ,'hierarchical' => false
        ,'pad_counts'   => false
        ,'get'          => ''
        ,'child_of'     => 0
        ,'parent'       => $rcl_group->term_id
    );

    $tags = get_terms('groups', $targs);

    if($tags) return '<div class="search-form-rcl">
            <form method="get">
                    '.rcl_get_tags_list_group((object)$tags,'',__('Display all records','wp-recall')).'
                    <input type="hidden" name="search-p" value="'.$rcl_group->term_id.'">
                    <input type="submit" class="recall-button" value="'.__('Show','wp-recall').'">
            </form>
    </div>';
}

function rcl_group_admin_panel(){
    global $rcl_group;

    $admins_buttons = array(
        array(
            'callback' => 'rcl_get_group_options',
            'name' => __('Primary options','wp-recall')
        ),
        array(
            'callback' => 'rcl_get_group_widgets',
            'name' => __('Widgets manage','wp-recall')
        )
    );

    if($rcl_group->group_status=='closed'){

        $requests = rcl_get_group_option($rcl_group->term_id,'requests_group_access');

        $admins_buttons[] = array(
                'callback' => 'rcl_get_group_requests_content',
                'name' => __('Requests for access','wp-recall').' - '.count($requests)
            );

    }

    $admins_buttons = apply_filters('rcl_group_admin_panel',$admins_buttons);

    foreach($admins_buttons as $button){
        $buttons[] = '<li class="admin-button">'.rcl_get_group_link($button['callback'],$button['name']).'</li>';
    }

    $panel = '<div id="group-admin-panel">'
            . '<span class="title-panel"><i class="fa fa-cogs"></i>'.__('Administration','wp-recall').'</span>'
            . '<ul>'.implode('',$buttons).'</ul>'
            . '</div>';

    echo $panel;

}

add_action('pre_get_posts','rcl_edit_group_pre_get_posts');
function rcl_edit_group_pre_get_posts($query){
	global $wpdb,$user_ID,$post,$rcl_group;

        if(!$query->is_main_query()) return $query;

        /*if($query->is_search){

	}*/

        if($query->is_tax&&isset($query->query['groups'])){
            $rcl_group = rcl_group_init();
	}

	if($query->is_single&&$query->query['post_type']=='post-group'&&$query->query['name']){

            if(!$post) $post_id = $wpdb->get_var($wpdb->prepare("SELECT ID FROM ".$wpdb->prefix."posts WHERE post_name='%s'",$query->query['name']));
            else $post_id = $post->ID;

            $cur_terms = get_the_terms( $post_id, 'groups' );

            foreach((array)$cur_terms as $cur_term){
                if($cur_term->parent!=0) continue;
                $term_id = $cur_term->term_id; break;
            }

            $rcl_group = rcl_get_group($term_id);

	}

	if($rcl_group){

            if(isset($_GET['group-tag'])&&$_GET['group-tag']!=''){

                if(!$_GET['search-p']){

                    $query->set( 'groups', $_GET['group-tag'] );

                    return $query;
                }else{
                    wp_redirect(get_term_link( (int)$_GET['search-p'], 'groups' ).'/?group-tag='.$_GET['group-tag']);exit;
                }

            }

            if(isset($_GET['group-page'])&&$_GET['group-page']!=''){
                     $query->set( 'posts_per_page', 1 );
            }

            if($rcl_group->admin_id==$user_ID) return $query;

            if(!$rcl_group->current_user&&$user_ID) $in_group = rcl_get_group_user_status($user_ID,$rcl_group->term_id);
            else $in_group = $rcl_group->current_user;

            if($rcl_group->group_status=='closed'){

                if(!$in_group||$in_group=='banned'){
                        if($query->is_single){
                            global $comments_array;

                            add_filter('the_content','rcl_close_group_post_content');
                            add_filter('the_content','rcl_get_link_group_tag',80);
                            add_filter('the_content','rcl_add_namegroup',80);
                            add_filter('comments_array','rcl_close_group_comments_content');
                            add_filter( 'comments_open', 'rcl_close_group_comments', 10 );
                            remove_filter('rating_block_content','rcl_add_buttons_rating',10);
                        }else{
                            add_filter('the_content','rcl_close_group_post_content');
                        }
                }else{

                }
            }else{
                if($in_group=='banned'){
                    if($query->is_single){
                        add_filter( 'comments_open', 'rcl_close_group_comments', 10 );
                        remove_filter('rating_block_content','rcl_add_buttons_rating',10);
                    }
                }
            }
	}

	return $query;
    }

function rcl_close_group_post_content(){
    return '<p align="center" style="color:red;">'.__('Access to content is closed privacy settings','wp-recall').'</p>';
}


function rcl_close_group_comments_content($comments){
    foreach($comments as $comment){
        $comment->comment_content = '<p>'.__('(Comment hidden privacy settings)','wp-recall').'</p>';
    }
    return $comments;
}

function rcl_close_group_comments( $open ) {
    $open = false;
    return $open;
}