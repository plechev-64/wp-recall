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
    add_submenu_page( 'pfm-menu', __('Настройки'), __('Настройки'), 'manage_options', 'pfm-menu', 'pfm_page_options');
    add_submenu_page( 'pfm-menu', __('Структура'), __('Структура'), 'manage_options', 'pfm-forums', 'pfm_page_forums');
    $hook = add_submenu_page( 'pfm-menu', __('Темы'), __('Templates','wp-recall'), 'manage_options', 'pfm-themes', 'pfm_page_themes');
    add_action( "load-$hook", 'pfm_add_options_themes_manager' );
    add_submenu_page( 'pfm-menu', __('Форма топика'), __('Форма топика'), 'manage_options', 'manage-topic-form', 'pfm_page_topic_form');
}

function pfm_page_topic_form(){
    
    $group_id = (isset($_GET['group-id']))? $_GET['group-id']: 0;
    
    if(!$group_id){
        
        $GroupsQuery = new PrimeGroups();
        
        $group_id = $GroupsQuery->get_var(array(
            'order' => 'ASC',
            'orderby' => 'group_seq',
            'fields' => array('group_id')
        ));
        
    }
    
    if(!$group_id)
        return '<p>На форуме пока не создано ни одной группы форумов.</p>'
        . '<p>Создайте группу форумов для управления полями формы публикации топика.</p>';

    rcl_sortable_scripts();

    $formManager = new Prime_Form_Manager(array(
        'form_slug' => 'pfm_group_'.$group_id
    ));
    
    $content = '<h2>'.__('Управление формой топика').'</h2>'
            . '<p>Выберите группу форума и управляйте произвольными полями формы публикации топика внутри этой группы</p>';
    
    $content .= $formManager->form_navi();
    
    $content .= $formManager->active_fields_box();

    echo $content;
}

function pfm_page_options(){
    
    $PfmOptions = get_option('rcl_pforum_options');
    
    $pages = get_posts(array(
        'post_type'=>'page',
        'numberposts'=>-1
    ));
    
    $pagelist = array('Страниц не найдено');
    
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
            'name' => 'rcl_pforum_options[home-page]',
            'title' => __('Страница форума'),
            'notice' => __('Выберите нужную страницу из списка и разместите на ней шорткод [prime-forum]'),
            'values' => $pagelist
        ),
        array(
            'type' => 'select',
            'slug' => 'forum-colors',
            'name' => 'rcl_pforum_options[forum-colors]',
            'title' => __('Цвета форума'),
            'values' => array(
                __('По-умолчанию'),
                __('Основные цвета WP-Recall')
            )
        ),
        array(
            'type' => 'select',
            'slug' => 'view-forums-home',
            'name' => 'rcl_pforum_options[view-forums-home]',
            'title' => __('Вывод всех форумов группы на главной странице'),
            'notice' => __('Если выбрано, то на главной странице будут выводится все форумы'),
            'values' => array(
                __('Не выводить'),
                __('Выводить')
            )
        ),
        array(
            'type' => 'text',
            'slug' => 'forums-home-list',
            'pattern' => '([0-9,\s]+)',
            'name' => 'rcl_pforum_options[forums-home-list]',
            'title' => __('Выводить форумы только для указанных групп'),
            'notice' => __('Если включен вывод форумов на главной, то можно через запятую '
                    . 'указать идентификаторы групп чьи форумы выводить')
        ),
        array(
            'type' => 'number',
            'slug' => 'forums-per-page',
            'name' => 'rcl_pforum_options[forums-per-page]',
            'title' => __('Форумов на странице группы'),
            'default' => 20
        ),
        array(
            'type' => 'number',
            'slug' => 'topics-per-page',
            'name' => 'rcl_pforum_options[topics-per-page]',
            'title' => __('Топиков на странице форума'),
            'default' => 20
        ),
        array(
            'type' => 'number',
            'slug' => 'posts-per-page',
            'name' => 'rcl_pforum_options[posts-per-page]',
            'title' => __('Сообщений на странице топика'),
            'default' => 20
        ),
        array(
            'type' => 'select',
            'slug' => 'guest-post-create',
            'name' => 'rcl_pforum_options[guest-post-create]',
            'title' => __('Публикация сообщений в теме гостям'),
            'values' => array(
                __('Запрещена'),
                __('Разрешена')
            )
        ),
        array(
            'type' => 'select',
            'slug' => 'support-oembed',
            'name' => 'rcl_pforum_options[support-oembed]',
            'title' => __('Поддержка OEMBED в сообщениях'),
            'values' => array(
                __('Запрещена'),
                __('Разрешена')
            )
        )
    );
    
    $CF = new Rcl_Custom_Fields();
    
    ?>   
    <h2>Настройки PrimeForum</h2>

    <div id="prime-options" class="rcl-form wrap-recall-options" style="display:block;">

        <form method="post" action="options.php">

            <?php

            foreach($options as $option){

                $value = isset($PfmOptions[$option['slug']])? $PfmOptions[$option['slug']]: false;

                $required = (isset($option['required']) && $option['required'] == 1)? '<span class="required">*</span>': '';

                echo '<div id="field-'.$option['slug'].'" class="form-field rcl-option">';

                    if(isset($option['title'])){
                        echo '<h3 class="field-title">';
                        echo $CF->get_title($option).' '.$required;
                        echo '</h3>';
                    }

                    echo $CF->get_input($option,$value);

                echo '</div>';
            }

            ?>

            <p align="right">
                <input type="submit" name="Submit" class="button button-primary button-large" value="Сохранить" />
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

    <h2><?php _e('Управление форумами'); ?></h2>
    
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
        <p class="install-help">'.__('Required to update the templates here. Get it  in  your account online <a href="http://codeseller.ru/" target="_blank">http://"codeseller.ru</a>','wp-recall').'</p>';

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
            'title' => __('Текущая роль'),
            'slug' => 'pfm_role',
            'values' => $values
        )
    );
    
    $cf = new Rcl_Custom_Fields();

    if($fields){
        
        $content = '<h3>'.__('Роль пользователя на форуме').':</h3>
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

add_action('wp_ajax_pfm_ajax_manager_update_data','pfm_ajax_manager_update_data');
function pfm_ajax_manager_update_data(){
    
    $post = $_POST;
    
    if(isset($post['group_id'])){
        $result = pfm_manager_update_group(intval($post['group_id']),$post['options']);
    }
    
    if(isset($post['forum_id'])){
        $result = pfm_manager_update_forum(intval($post['forum_id']),$post['options']);
    }
    
    echo json_encode($result); exit;
    
}

function pfm_manager_update_group($group_id,$options){
    
    pfm_update_group(array(
        'group_id' => $group_id,
        'group_name' => $options['group_name'],
        'group_slug' => $options['group_slug'],
        'group_desc' => $options['group_desc']
    ));
    
    return array(
        'success' => __('Изменение сохранены!'),
        'title' => $options['group_name']
    );
    
}

function pfm_manager_update_forum($forum_id,$options){
    
    $forum = pfm_get_forum($forum_id);
    
    pfm_update_forum(array(
        'forum_id' => $forum_id,
        'forum_name' => $options['forum_name'],
        'forum_desc' => $options['forum_desc'],
        'forum_slug' => $options['forum_slug'],
        'forum_closed' => $options['forum_closed'],
        'group_id' => $options['group_id'],
    ));
    
    $result = array(
        'success' => __('Изменение сохранены!'),
        'title' => $options['forum_name']
    );
    
    if(isset($options['group_id']) && $forum->group_id != $options['group_id']){
        
        $result['update-page'] = 1;
        
    }
    
    return $result;
    
}

add_action('wp_ajax_pfm_ajax_update_sort_groups','pfm_ajax_update_sort_groups');
function pfm_ajax_update_sort_groups(){
    
    $sort = json_decode(wp_unslash($_POST['sort']));
    
    foreach($sort as $s => $group){
        pfm_update_group(array(
            'group_id' => $group->id,
            'group_seq' => $s + 1
        ));
    }
    
    echo json_encode(array(
        'success' => __('Изменение сохранены!')
    )); exit;
    
}

add_action('wp_ajax_pfm_ajax_update_sort_forums','pfm_ajax_update_sort_forums');
function pfm_ajax_update_sort_forums(){
    
    $sort = json_decode(wp_unslash($_POST['sort']));
    
    foreach($sort as $s => $forum){
        pfm_update_forum(array(
            'forum_id' => $forum->id,
            'parent_id' => $forum->parent,
            'forum_seq' => $s + 1
        ));
    }
    
    echo json_encode(array(
        'success' => __('Изменение сохранены!')
    )); exit;
    
}

add_action('wp_ajax_pfm_ajax_get_manager_item_delete_form','pfm_ajax_get_manager_item_delete_form');
function pfm_ajax_get_manager_item_delete_form(){
    
    $itemType = $_POST['item-type'];
    $itemID = $_POST['item-id'];
    
    if($itemType == 'groups'){
        
        $groups = pfm_get_groups(array(
            'order' => 'ASC',
            'orderby' => 'group_seq',
            'group_id__not_in' => array($itemID)
        ));
        
        $values = array('' => __('Удалить все форумы внутри группы'));
        
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
                'title' => __('Новая группа для дочерних форумов'),
                'notice' => __('Если при удалении выбранной группы для дочерних форумов не '
                        . 'будет назначена новая группа, то форумы будут также удалены'),
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
        
        $values = array('' => __('Удалить все топики внутри форума'));
        
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
                'title' => __('Новый форум для дочерних топиков'),
                'notice' => __('Если при удалении выбранного форума для дочерних топиков не '
                        . 'будет назначен новый форум, то топики будут также удалены'),
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
    
    echo json_encode(array(
        'success' => true,
        'form' => $form
    )); exit;
    
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
                $content .= '<input type="submit" class="button-primary" value="Подтвердить удаление">';
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