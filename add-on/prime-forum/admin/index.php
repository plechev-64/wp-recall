<?php

require_once 'classes/class-prime-form-manager.php';
require_once 'classes/class-prime-manager.php';
require_once 'themes-manager.php';

add_action('admin_init','pfm_admin_scripts',10);
function pfm_admin_scripts(){
    wp_enqueue_style('pfm-admin-style', rcl_addon_url('admin/style.css', __FILE__));
    wp_enqueue_script('pfm-admin-script', rcl_addon_url('admin/js/scripts.js', __FILE__));
}

add_action('admin_menu', 'pfm_init_admin_menu',10);
function pfm_init_admin_menu(){
    add_menu_page('PrimeForum', 'PrimeForum', 'manage_options', 'pfm-menu', 'pfm_page_options');
    add_submenu_page( 'pfm-menu', __('Settings','wp-recall'), __('Settings','wp-recall'), 'manage_options', 'pfm-menu', 'pfm_page_options');
    add_submenu_page( 'pfm-menu', __('Structure','wp-recall'), __('Structure','wp-recall'), 'manage_options', 'pfm-forums', 'pfm_page_forums');
    $hook = add_submenu_page( 'pfm-menu', __('Templates','wp-recall'), __('Templates','wp-recall'), 'manage_options', 'pfm-themes', 'pfm_page_themes');
    add_action( "load-$hook", 'pfm_add_options_themes_manager' );
    add_submenu_page( 'pfm-menu', __('Topic form','wp-recall'), __('Topic form','wp-recall'), 'manage_options', 'manage-topic-form', 'pfm_page_topic_form');
}

function pfm_page_topic_form(){
    
    $group_id = (isset($_GET['group-id']))? intval($_GET['group-id']): 0;
    
    if(!$group_id){
        
        $GroupsQuery = new PrimeGroups();
        
        $group_id = $GroupsQuery->get_var(array(
            'order' => 'ASC',
            'orderby' => 'group_seq',
            'fields' => array('group_id')
        ));
        
    }
    
    if(!$group_id){
        echo '<p>'.__('The forum is not yet created any groups of forums','wp-recall').'.</p>'
        . '<p>'.__('Create a group of forums for managing the form fields of the publication of a topic','wp-recall').'.</p>';
        return;
    }
    rcl_sortable_scripts();

    $formManager = new Prime_Form_Manager(array(
        'form_slug' => 'pfm_group_'.$group_id
    ));
    
    $content = '<h2>'.__('Manage topic form','wp-recall').'</h2>'
            . '<p>'.__('Select a forum group and manage custom fields form of publication of a topic within this group','wp-recall').'</p>';
    
    $content .= $formManager->form_navi();
    
    $content .= $formManager->active_fields_box();

    echo $content;
}

function pfm_page_options(){
    
    require_once RCL_PATH.'classes/class-rcl-options.php';
    
    $opt = new Rcl_Options(__FILE__, 'rcl_pforum_options');
    
    $PfmOptions = get_option('rcl_pforum_options');

    $pages = get_posts(array(
        'post_type'=>'page',
        'numberposts'=>-1
    ));
    
    $pagelist = array(__('Pages not found','wp-recall'));
    
    if($pages){
        
        $pagelist = array();
        foreach($pages as $page){
            $pagelist[$page->ID] = $page->post_title;   
        }
        
    }
    
    $options = array(
        array(
            'type' => 'select',
            'slug' => 'home-page',
            'title' => __('Forum page','wp-recall'),
            'notice' => __('Select the needed page from the list and place the '
                    . '[prime-forum] shortcode on it','wp-recall'),
            'values' => $pagelist
        ),
        array(
            'type' => 'select',
            'slug' => 'forum-colors',
            'title' => __('Forum colours','wp-recall'),
            'values' => array(
                __('By default','wp-recall'),
                __('Primary colours of WP-Recall','wp-recall')
            )
        ),
        array(
            'type' => 'select',
            'slug' => 'view-links',
            'title' => __('Показ ссылок в сообщениях','wp-recall'),
            'values' => array(
                __('Скрывать для гостей','wp-recall'),
                __('Показывать всем','wp-recall')
            )
        ),
        array(
            'type' => 'textarea',
            'slug' => 'support-shortcodes',
            'title' => __('Supported shortcodes','wp-recall'),
            'notice' => __('Specify the necessary shortcodes to support them in '
                    . 'forum messages, each should start from a new line. '
                    . 'Specify without brackets, for example: custom-shortcode','wp-recall')
        ),
        array(
            'type' => 'select',
            'slug' => 'view-forums-home',
            'title' => __('Output all forums of the group on the homepage','wp-recall'),
            'notice' => __('If selected, all forums will be displayed on the homepage','wp-recall'),
            'values' => array(
                __('Do not output','wp-recall'),
                __('Output','wp-recall')
            )
        ),
        array(
            'type' => 'text',
            'slug' => 'forums-home-list',
            'pattern' => '([0-9,\s]+)',
            'title' => __('Output forums only for the specified groups','wp-recall'),
            'notice' => __('If output of forums on the homepage is turned on, you may specify IDs of the groups, '
                    . 'whose forums should be output, space separated','wp-recall')
        ),
        array(
            'type' => 'runner',
            'slug' => 'forums-per-page',
            'title' => __('Forums on the group page','wp-recall'),
            'value_min' => 5,
            'value_max' => 50,
            'value_step' => 1,
            'default' => 20
        ),
        array(
            'type' => 'runner',
            'slug' => 'topics-per-page',
            'title' => __('Topics on the forum page','wp-recall'),
            'value_min' => 5,
            'value_max' => 70,
            'value_step' => 1,
            'default' => 20
        ),
        array(
            'type' => 'runner',
            'slug' => 'posts-per-page',
            'title' => __('Messages on the topic page','wp-recall'),
            'value_min' => 5,
            'value_max' => 100,
            'value_step' => 1,
            'default' => 20
        ),
        array(
            'type' => 'select',
            'slug' => 'guest-post-create',
            'title' => __('Publishing of messages in the topic by guests','wp-recall'),
            'values' => array(
                __('Forbidden','wp-recall'),
                __('Allowed','wp-recall')
            )
        ),
        array(
            'type' => 'select',
            'slug' => 'support-oembed',
            'title' => __('Support of OEMBED in messages','wp-recall'),
            'values' => array(
                __('Forbidden','wp-recall'),
                __('Allowed','wp-recall')
            )
        ),
        array(
            'type' => 'runner',
            'slug' => 'beat-time',
            'title' => __('Задержка на получение нового сообщения через AJAX','wp-recall'),
            'value_min' => 0,
            'value_max' => 120,
            'value_step' => 1,
            'default' => 30,
            'notice' => __('В секундах. Новые сообщения в теме форума подгружаются '
                    . 'через AJAX только для тех, кто уже оставил ранее в этой теме сообщение. '
                    . 'Если ноль, то AJAX-подгрузка отключена.','wp-recall')
        ),
        array(
            'type' => 'runner',
            'slug' => 'beat-inactive',
            'title' => __('Предел кол-ва запросов на получение новых сообщений','wp-recall'),
            'value_min' => 10,
            'value_max' => 200,
            'value_step' => 1,
            'default' => 100,
            'notice' => __('Если включена подгрузка новых сообщений через AJAX, '
                    . 'то здесь устанавливаем максимальное кол-во запросов от '
                    . 'одного пользователя, после достижения которого они прекращаются, '
                    . 'после публикации нового сообщения запросы возобновляются.','wp-recall')
        ),
        array(
            'type' => 'custom',
            'title' => __('Templates to form the title tag and name of the page','wp-recall'),
            'content' => __(
                '<p>The following masks may be specified in templates:<br>'
                . '%GROUPNAME% - name of the current group of forums<br>'
                . '%FORUMNAME% - name of the current forum<br>'
                . '%TOPICNAME% - name of the current topic</p>'
                , 'wp-recall'
            )
        ),
        array(
            'type' => 'text',
            'slug' => 'mask-tag-group',
            'notice' => __('Title tag in the group of forums','wp-recall'),
            'default' => __('Group of forums','wp-recall').' %GROUPNAME%'
        ),
        array(
            'type' => 'text',
            'slug' => 'mask-page-group',
            'notice' => __('Name of the page in the group of forums','wp-recall'),
            'default' => __('Group of forums','wp-recall').' %GROUPNAME%'
        ),
        array(
            'type' => 'text',
            'slug' => 'mask-tag-forum',
            'notice' => __('Title tag on the forum page','wp-recall'),
            'default' => __('Forum','wp-recall').' %FORUMNAME%'
        ),
        array(
            'type' => 'text',
            'slug' => 'mask-page-forum',
            'notice' => __('Name of the page of the separate forum','wp-recall'),
            'default' => __('Forum','wp-recall').' %FORUMNAME%'
        ),
        array(
            'type' => 'text',
            'slug' => 'mask-tag-topic',
            'notice' => __('Title tag on the topic page','wp-recall'),
            'default' => '%TOPICNAME% | '. __('Forum','wp-recall').' %FORUMNAME%'
        ),
        array(
            'type' => 'text',
            'slug' => 'mask-page-topic',
            'notice' => __('Name of the page of the separate topic','wp-recall'),
            'default' => '%TOPICNAME%'
        ),
        array(
            'type' => 'select',
            'slug' => 'admin-notes',
            'title' => __('Notification to the administrator about new topics','wp-recall'),
            'values' => array(
                __('Disabled','wp-recall'),
                __('Enabled','wp-recall')
            )
        ),
        array(
            'type' => 'select',
            'slug' => 'author-notes',
            'title' => __('Уведомления автору темы о новых ответах','wp-recall'),
            'values' => array(
                __('Disabled','wp-recall'),
                __('Enabled','wp-recall')
            ),
            'notice' => __('Уведомление высылается на каждое новое сообщение в теме только когда автор темы не находится на форуме','wp-recall')
        )
    );
    
    $options = apply_filters('pfm_options_array', $options);
    
    if($PfmOptions){
        foreach($options as $k => $option){
            
            if(isset($PfmOptions[$option['slug']]))
                $options[$k]['default'] = $PfmOptions[$option['slug']];
            
        }
    }
    
    ?>

    <h2><?php _e('Settings PrimeForum','wp-recall'); ?></h2>

    <div id="prime-options" class="rcl-form wrap-recall-options" style="display:block;">

        <form method="post" action="options.php">

            <?php echo $opt->options(
                    false, array(
                        $opt->options_box(__('General settings','wp-recall'), $options)
                    )
                ); ?>

            <p align="right">
                <input type="submit" name="Submit" class="button button-primary button-large" value="<?php _e('Save','wp-recall'); ?>" />
            </p>
            <input type="hidden" name="action" value="update" />
            <input type="hidden" name="page_options" value="rcl_pforum_options" />
            <?php wp_nonce_field('update-options'); ?>
            
        </form>
        
    </div>
<?php 
}

add_action('admin_init','pfm_flush_rewrite_rules');
function pfm_flush_rewrite_rules(){
    
    if(isset($_POST['rcl_pforum_options'])) flush_rewrite_rules();
    
}

function pfm_page_forums(){ 
    
    wp_enqueue_script('jquery');
    wp_enqueue_script('jquery-ui-dialog');
    wp_enqueue_style('wp-jquery-ui-dialog');
    
    ?>

    <h2><?php _e('Manage forums','wp-recall'); ?></h2>
    
    <?php
    
    $manager = new PrimeManager();
    
    echo $manager->get_manager();

}

function pfm_page_themes(){
    
    global $active_addons,$Prime_Themes_Manager;

    $Prime_Themes_Manager->get_templates_data();

    $cnt_all = $Prime_Themes_Manager->template_number;

    echo '</pre><div class="wrap">'; 

    echo '<div id="icon-plugins" class="icon32"><br></div>
        <h2>'.__('Templates','wp-recall').' PrimeForum</h2>';

        if(isset($_POST['save-rcl-key'])){
            if( wp_verify_nonce( $_POST['_wpnonce'], 'add-rcl-key' ) ){
                update_option('rcl-key',$_POST['rcl-key']);
                echo '<div id="message" class="'.$type.'"><p>'.__('Key has been saved','wp-recall').'!</p></div>';
            }
        }

        echo '<div class="rcl-admin-service-box rcl-key-box">';

        echo '<h4>'.__('RCLKEY','wp-recall').'</h4>
        <form action="" method="post">
            '.__('Enter RCLKEY','wp-recall').' <input type="text" name="rcl-key" value="'.get_option('rcl-key').'">
            <input class="button" type="submit" value="'.__('Save','wp-recall').'" name="save-rcl-key">
            '.wp_nonce_field('add-rcl-key','_wpnonce',true,false).'
        </form>
        <p class="install-help">'.__('Required to update the templates here. Get it  in  your account online <a href="http://codeseller.ru/" target="_blank">http://codeseller.ru</a>','wp-recall').'</p>';

        echo '</div>';

        echo '<div class="rcl-admin-service-box rcl-upload-form-box upload-template">';

        echo '<h4>'.__('Install the add-on to WP-Recall format .ZIP','wp-recall').'</h4>
        <p class="install-help">'.__('If you have an archive template for wp-recall format .zip, here you can upload and install it','wp-recall').'</p>
        <form class="wp-upload-form" action="" enctype="multipart/form-data" method="post">
            <label class="screen-reader-text" for="addonzip">'.__('Add-on archive','wp-recall').'</label>
            <input id="addonzip" type="file" name="addonzip">
            <input id="install-plugin-submit" class="button" type="submit" value="'.__('Install','wp-recall').'" name="pfm-install-template-submit">
            '.wp_nonce_field('install-template-pfm','_wpnonce',true,false).'
        </form>

        </div>

        <ul class="subsubsub">
            <li class="all"><b>'.__('All','wp-recall').'<span class="count">('.$cnt_all.')</span></b></li>
        </ul>';

    $Prime_Themes_Manager->prepare_items(); ?>

    <form method="post">
    <input type="hidden" name="page" value="pfm-themes">
    <?php
    $Prime_Themes_Manager->search_box( 'Search by name', 'search_id' );
    $Prime_Themes_Manager->display(); 
    echo '</form></div>'; 
    
}

if (is_admin()):
    add_action('profile_personal_options', 'pfm_admin_role_field');
    add_action('edit_user_profile', 'pfm_admin_role_field');
endif;
function pfm_admin_role_field($user){
    
    $PrimeUser = new PrimeUser(array( 'user_id' => $user->ID ));

    $values = array();
    foreach($PrimeUser->roles as $role => $prop){
        $values[$role] = $prop['name'];
    }
    
    $fields = array(
        array(
            'type' => 'select',
            'title' => __('Current role','wp-recall'),
            'slug' => 'pfm_role',
            'values' => $values
        )
    );
    
    $cf = new Rcl_Custom_Fields();

    if($fields){
        
        $content = '<h3>'.__('Role of the user on the forum','wp-recall').':</h3>
        <table class="form-table rcl-form">';
        
        foreach($fields as $field){
            
            $content .= '<tr><th><label>'.$cf->get_title($field).':</label></th>';
            $content .= '<td>'.$cf->get_input($field, $PrimeUser->user_role).'</td>';
            $content .= '</tr>';
            
        }
        
        $content .= '</table>';
        
    }
    
    echo $content;

}

add_action('personal_options_update', 'pfm_update_user_role');
add_action('edit_user_profile_update', 'pfm_update_user_role');
function pfm_update_user_role($user_id) {
    
    if ( !current_user_can( 'edit_user', $user_id ) ) 
            return false;
    
    if( !isset($_POST['pfm_role']) ) 
        return false;
    
    update_user_meta($user_id, 'pfm_role', $_POST['pfm_role']);
}

rcl_ajax_action('pfm_ajax_manager_update_data', false);
function pfm_ajax_manager_update_data(){
    
    $post = $_POST;
    
    if(isset($post['group_id'])){
        
        if(isset($post['forum_id']))
            $result = pfm_manager_update_forum($post);
        else
            $result = pfm_manager_update_group($post);
        
        wp_send_json($result);
        
    }
    
    exit;
    
}

function pfm_manager_update_group($options){
    
    pfm_update_group(array(
        'group_id' => $options['group_id'],
        'group_name' => $options['group_name'],
        'group_slug' => $options['group_slug'],
        'group_desc' => $options['group_desc']
    ));
    
    return array(
        'success' => __('Changes saved!','wp-recall'),
        'title' => $options['group_name'],
        'id' => $options['group_id']
    );
    
}

function pfm_manager_update_forum($options){
    
    $forum = pfm_get_forum($options['forum_id']);
    
    pfm_update_forum(array(
        'forum_id' => $options['forum_id'],
        'forum_name' => $options['forum_name'],
        'forum_desc' => $options['forum_desc'],
        'forum_slug' => $options['forum_slug'],
        'forum_closed' => $options['forum_closed'],
        'group_id' => $options['group_id'],
    ));
    
    $result = array(
        'success' => __('Changes saved!','wp-recall'),
        'title' => $options['forum_name'],
        'id' => $options['forum_id']
    );
    
    if(isset($options['group_id']) && $forum->group_id != $options['group_id']){
        
        $result['update-page'] = 1;
        $result['preloader_live'] = 1;
        
    }
    
    return $result;
    
}

rcl_ajax_action('pfm_ajax_update_sort_groups', false);
function pfm_ajax_update_sort_groups(){
    global $wpdb;
    
    $sort = json_decode(wp_unslash($_POST['sort']));
    
    foreach($sort as $s => $group){
        //убрал функции допа на апдейт группы, 
        //ибо срабатывают хуки, а тут они ни к чему
        $wpdb->update(
            RCL_PREF.'pforum_groups',
            array(
                'group_seq' => $s + 1
            ),
            array(
                'group_id' => $group->id
            )
        );
        
    }
    
    wp_send_json(array(
        'success' => __('Changes saved!','wp-recall')
    ));
    
}

rcl_ajax_action('pfm_ajax_update_sort_forums', false);
function pfm_ajax_update_sort_forums(){
    global $wpdb;
    
    $sort = json_decode(wp_unslash($_POST['sort']));
    
    foreach($sort as $s => $forum){
        //убрал функции допа на апдейт форума, 
        //ибо срабатывают хуки, а тут они ни к чему
        $wpdb->update(
            RCL_PREF.'pforums',
            array(
                'parent_id' => $forum->parent,
                'forum_seq' => $s + 1
            ),
            array(
                'forum_id' => $forum->id
            )
        );

    }
    
    wp_send_json(array(
        'success' => __('Changes saved!','wp-recall')
    ));
    
}

rcl_ajax_action('pfm_ajax_get_manager_item_delete_form', false);
function pfm_ajax_get_manager_item_delete_form(){
    
    $itemType = $_POST['item-type'];
    $itemID = $_POST['item-id'];
    
    if($itemType == 'groups'){
        
        $groups = pfm_get_groups(array(
            'order' => 'ASC',
            'orderby' => 'group_seq',
            'group_id__not_in' => array($itemID)
        ));
        
        $values = array('' => __('Delete all forums inside the group','wp-recall'));
        
        if($groups){
            
            foreach($groups as $group){
                $values[$group->group_id] = $group->group_name;
            }
            
        }
        
        $fields = array(
            array(
                'type' => 'select',
                'slug' => 'migrate-group',
                'name' => 'pfm-data[migrate_group]',
                'title' => __('New group for child forums','wp-recall'),
                'notice' => __('If new group is not assigned for child forums, when deleting the selected '
                        . 'group, the forums will also be deleted','wp-recall'),
                'values' => $values
            ),
            array(
                'type' => 'hidden',
                'slug' => 'group-id',
                'name' => 'pfm-data[group_id]',
                'value' => $itemID
            ),
            array(
                'type' => 'hidden',
                'slug' => 'action',
                'name' => 'pfm-data[action]',
                'value' => 'group_delete'
            )
        );
        
    }else if($itemType == 'forums'){
        
        $forums = pfm_get_forums(array(
            'order' => 'ASC',
            'orderby' => 'forum_seq',
            'forum_id__not_in' => array($itemID)
        ));
        
        $values = array('' => __('Delete all topic inside the forum','wp-recall'));
        
        if($forums){
            
            foreach($forums as $forum){
                $values[$forum->forum_id] = $forum->forum_name;
            }
            
        }
        
        $fields = array(
            array(
                'type' => 'select',
                'slug' => 'migrate-group',
                'name' => 'pfm-data[migrate_forum]',
                'title' => __('New forum for child topics','wp-recall'),
                'notice' => __('If new forum is not assigned for child forums, when deleting the selected '
                        . 'forum, the topics will also be deleted','wp-recall'),
                'values' => $values
            ),
            array(
                'type' => 'hidden',
                'slug' => 'group-id',
                'name' => 'pfm-data[forum_id]',
                'value' => $itemID
            ),
            array(
                'type' => 'hidden',
                'slug' => 'action',
                'name' => 'pfm-data[action]',
                'value' => 'forum_delete'
            )
        );
        
    }
    
    $form = pfm_get_manager_item_delete_form($fields);
    
    wp_send_json(array(
        'form' => $form
    ));
    
}

function pfm_get_manager_item_delete_form($fields){
    
    $CF = new Rcl_Custom_Fields();
    
    $content = '<div id="manager-deleted-form" class="rcl-custom-fields-box">';
        $content .= '<form method="post">';

            foreach($fields as $field){

                $required = ($field['required'] == 1)? '<span class="required">*</span>': '';

                $content .= '<div id="field-'.$field['slug'].'" class="form-field rcl-custom-field">';

                    if(isset($field['title'])){
                        $content .= '<label>';
                        $content .= $CF->get_title($field).' '.$required;
                        $content .= '</label>';
                    }

                    $content .= $CF->get_input($field);

                $content .= '</div>';
            }

            $content .= '<div class="form-field fields-submit">';
                $content .= '<input type="submit" class="button-primary" value="'.__('Confirm the deletion','wp-recall').'">';
            $content .= '</div>';
            $content .= wp_nonce_field('pfm-action','_wpnonce',true,false);
        $content .= '</form>';
    $content .= '</div>';

    return $content;
    
}

function pfm_get_templates(){
        
    $paths = array(
        rcl_addon_path(__FILE__).'themes',
        RCL_PATH.'add-on',
        RCL_TAKEPATH.'add-on'
    ) ;

    $add_ons = array();
    foreach($paths as $path){
        if(file_exists($path)){
            $addons = scandir($path,1);

            foreach((array)$addons as $namedir){
                $addon_dir = $path.'/'.$namedir;
                $index_src = $addon_dir.'/index.php';
                if(!is_dir($addon_dir)||!file_exists($index_src)) continue;
                $info_src = $addon_dir.'/info.txt';
                if(file_exists($info_src)){
                    $info = file($info_src);
                    $data = rcl_parse_addon_info($info);

                    if(!isset($data['custom-manager']) || $data['custom-manager'] != 'prime-forum') continue;

                    $add_ons[$namedir] = $data;
                    $add_ons[$namedir]['path'] = $addon_dir;
                }

            }
        }
    }

    return $add_ons;

}

add_action('pfm_deleted_group','pfm_delete_group_custom_fields',10);
function pfm_delete_group_custom_fields($group_id){
    delete_option('rcl_fields_pfm_group_'.$group_id);
}

add_action('rcl_add_dashboard_metabox', 'rcl_add_forum_metabox');
function rcl_add_forum_metabox($screen){
    add_meta_box( 'rcl-forum-metabox', __('Last forum topics','wp-recall'), 'rcl_forum_metabox', $screen->id, 'side' );
}

function rcl_forum_metabox(){
    
    $topics = pfm_get_topics(array('number'=>5));
    
    if(!$topics){
        echo '<p>'.__('No topics on the forum yet','wp-recall').'</p>'; return;
    }
    
    echo '<table class="wp-list-table widefat fixed striped">';
    echo '<tr>'
        . '<th>'.__('Topic','wp-recall').'</th>'
        . '<th>'.__('Messages','wp-recall').'</th>'
        . '<th>'.__('Author','wp-recall').'</th>'
        . '</tr>';
    foreach($topics as $topic){
        echo '<tr>'
        . '<td><a href="'.pfm_get_topic_permalink($topic->topic_id).'" target="_blank">'.$topic->topic_name.'</a></td>'
        . '<td>'.$topic->post_count.'</td>'
        . '<td>'.get_the_author_meta('user_login',$topic->user_id).'</td>'
        . '</tr>';
    }
    echo '</table>';
    echo '<p><a href="'.pfm_get_home_url().'" target="_blank">'.__('Go to forum','wp-recall').'</a></p>';
}

add_action('admin_init','pfm_init_admin_actions');
function pfm_init_admin_actions(){
    global $user_ID;
    
    if(!isset($_REQUEST['pfm-data']) || !isset($_REQUEST['pfm-data']['action'])) return;
    
    if(!isset($_REQUEST['_wpnonce']) || !wp_verify_nonce($_REQUEST['_wpnonce'],'pfm-action')) return;
    
    $pfmData = $_REQUEST['pfm-data'];
    
    $action = $pfmData['action'];
    
    switch($action){
        case 'group_create': //добавление группы
            
            pfm_add_group(array(
                'group_name' => $_REQUEST['group_name'],
                'group_slug' => $_REQUEST['group_slug'],
                'group_desc' => $_REQUEST['group_desc']
            ));
            
        break;
        case 'forum_create': //создание форума
            
            pfm_add_forum(array(
                'forum_name' => $_REQUEST['forum_name'],
                'forum_desc' => $_REQUEST['forum_desc'],
                'forum_slug' => $_REQUEST['forum_slug'],
                'group_id' => $_REQUEST['group_id']
            ));
            
        break;
        case 'group_delete': //удаление группы
            
            if(!$pfmData['group_id']) return false;
            
            pfm_delete_group($pfmData['group_id'], $pfmData['migrate_group']);
            
            wp_redirect(admin_url('admin.php?page=pfm-forums')); exit;
            
        break;
        case 'forum_delete': //удаление форума
            
            if(!$pfmData['forum_id']) return false;
            
            $group = pfm_get_forum($pfmData['forum_id']);
            
            pfm_delete_forum($pfmData['forum_id'], $pfmData['migrate_forum']);
            
            wp_redirect(admin_url('admin.php?page=pfm-forums&group-id='.$group->group_id)); exit;
            
        break;
        
    }
    
    wp_redirect($_POST['_wp_http_referer']); exit;
    
}