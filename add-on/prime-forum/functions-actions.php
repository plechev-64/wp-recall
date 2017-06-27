<?php

function pfm_add_ajax_action($action,$functionName){
    global $PrimeActions;
    $PrimeActions[$action] = $functionName;
}

function pfm_get_manager($actions,$itemType,$itemID = false){
    
    $content = '<div class="'.$itemType.'-manager prime-manager">';
        $content .= '<ul>';

    foreach($actions as $action => $options){
        
        $args = array(
            'item_type' => $itemType,
            'method' => $action
        );
        
        if($itemID)
            $args['item_id'] = $itemID;
        
        if(isset($options['options'])){
            $args = array_merge($args, $options['options']);
        }

        $content .= '<li>';
        $content .= '<a href="#" title="'.$options['name'].'" class="topic-action action-'.$action.'" onclick=\'pfm_ajax_action('.json_encode($args).');return false;\'>';
        $content .= (isset($options['icon']))? '<i class="fa '.$options['icon'].'" aria-hidden="true"></i>': $options['name'];
        $content .= '</a>';
        $content .= '</li>';
    }
    
        $content .= '</ul>';
    $content .= '</div>';
    
    return $content;
    
}

function pfm_the_author_manager(){
    global $PrimePost, $user_ID;
    
    $actions = array();
    
    if($user_ID && $PrimePost->user_id && $user_ID != $PrimePost->user_id){
        
        $actions['get_author_info'] = array(
            'name' => __('Подробно об авторе'),
            'icon' => 'fa-info-circle'
        );

        if(rcl_exist_addon('rcl-chat')){

            $actions['get_private_chat'] = array(
                'name' => __('Перейти в приватный чат'),
                'icon' => 'fa-comments-o'
            );
        }
    }
    
    if(!$actions)
        return false;

    $content = pfm_get_manager($actions,'author',$PrimePost->user_id);
    
    echo $content;
    
}

function pfm_get_primary_manager(){
    
    $actions['get_last_updated_topics'] = array(
        'name' => __('Получить список обновленных тем'),
        'icon' => 'fa-bell-o'
    );
    
    $actions['get_structure'] = array(
        'name' => __('Быстрый переход на нужный форум'),
        'icon' => 'fa-rocket'
    );

    $content = pfm_get_manager($actions,'primary');
    
    return $content;
    
}

function pfm_the_post_manager(){
    global $PrimePost,$PrimeTopic;
    
    if(!$PrimePost->post_id) return false;

    $actions = array();
    
    if(pfm_is_can_post_delete($PrimePost->post_id)){  
        
        $actions['post_delete'] = array(
            'name' => __('Удалить сообщение'),
            'icon' => 'fa-trash',
            'options' => array(
                'confirm' => __('Вы уверены?')
            )
        );
        
    }

    if(pfm_is_can('post_migrate')){
        
        $actions['start_post_migrate'] = array(
            'name' => __('Перенести в существующий топик'),
            'icon' => 'fa-share-square-o'
        );
        
        $actions['get_form_topic_create'] = array(
            'name' => __('Перенести в новый топик'),
            'icon' => 'fa-code-fork'
        );
        
    }
    
    if(pfm_is_can_post_edit($PrimePost->post_id)){
        
        $actions['get_form_post_edit'] = array(
            'name' => __('Редактировать сообщение'),
            'icon' => 'fa-pencil-square-o'
        );
    }
    
    if(pfm_is_can('post_create') && !$PrimeTopic->topic_closed && !$PrimeTopic->forum_closed){
        
        $actions['get_post_excerpt'] = array(
            'name' => __('Цитировать сообщение'),
            'icon' => 'fa-quote-right'
        );
        
    }
    
    $actions = apply_filters('pfm_post_manager_actions', $actions, $PrimePost);
    
    if(!$actions) return false;

    $content = pfm_get_manager($actions,'post',$PrimePost->post_id);
    
    echo $content;
    
}

function pfm_the_topic_manager(){
    global $PrimeTopic;
    
    if(!$PrimeTopic->topic_id) return false;
    
    $actions = array();
    
    if(pfm_is_can('post_migrate')){
        
        if(isset($_COOKIE['pfm_migrate_post'])){
            $actions['end_post_migrate'] = array(
                'name' => __('Перенести в этот топик')
            );
            $actions['cancel_post_migrate'] = array(
                'name' => __('Отменить перенос'),
                'icon' => 'fa-times'
            );
        }
        
    }
    
    if(pfm_is_can_topic_delete($PrimeTopic->topic_id)){
    
        $actions['topic_delete'] = array(
            'name' => __('Удалить топик'),
            'icon' => 'fa-trash',
            'options' => array(
                'confirm' => __('Вы уверены?')
            )
        );
    
    }

    if(pfm_is_can('topic_migrate')){
        
        $actions['get_form_topic_migrate'] = array(
            'name' => __('Перенести топик'),
            'icon' => 'fa-chain-broken'
        );
        
    }
    
    if(pfm_is_can('topic_fix')){
    
        if($PrimeTopic->topic_fix){
            $actions['topic_unfix'] = array(
                'name' => __('Открепить топик'),
                'icon' => 'fa-star'
            );
        }else{
            $actions['topic_fix'] = array(
                'name' => __('Закрепить топик'),
                'icon' => 'fa-star-o'
            );
        }
    
    }
    
    if(pfm_is_can('topic_close')){
    
        if($PrimeTopic->topic_closed){
            $actions['topic_unclose'] = array(
                'name' => __('Открыть топик'),
                'icon' => 'fa-lock'
            );
        }else{
            $actions['topic_close'] = array(
                'name' => __('Закрыть топик'),
                'icon' => 'fa-unlock'
            );
        }
    
    }
    
    if(pfm_is_can_topic_edit($PrimeTopic->topic_id)){
        
        $actions['get_form_topic_edit'] = array(
            'name' => __('Изменить наименование'),
            'icon' => 'fa-pencil-square-o'
        );
    
    }

    $actions = apply_filters('pfm_topic_manager_actions', $actions, $PrimeTopic);
    
    if(!$actions) return false;
    
    $content = pfm_get_manager($actions,'topic',$PrimeTopic->topic_id);
    
    echo $content;
    
}

add_action('init','pfm_init_actions');
function pfm_init_actions(){
    global $user_ID;
    
    if(!isset($_REQUEST['pfm-data']) || !isset($_REQUEST['pfm-data']['action'])) return;
    
    if(!isset($_REQUEST['_wpnonce']) || !wp_verify_nonce($_REQUEST['_wpnonce'],'pfm-action')) return;
    
    $pfmData = $_REQUEST['pfm-data'];
    
    $action = $pfmData['action'];
    
    switch($action){
        case 'group_create': //добавление группы
            
            pfm_add_group(array(
                'group_name' => $pfmData['group_name'],
                'group_slug' => $pfmData['group_slug'],
                'group_desc' => $pfmData['group_desc']
            ));
            
        break;
        case 'forum_create': //создание форума
            
            pfm_add_forum(array(
                'forum_name' => $pfmData['forum_name'],
                'forum_desc' => $pfmData['forum_desc'],
                'forum_slug' => $pfmData['forum_slug'],
                'group_id' => $pfmData['group_id']
            ));
            
        break;
        case 'topic_create': //создание топика
            
            if(!pfm_is_can('topic_create') || !$pfmData['forum_id']) return false;
            
            $topic_id = pfm_add_topic(
                array(
                    'topic_name' => $pfmData['topic_name'],
                    'forum_id' => $pfmData['forum_id']
                ),
                array(
                    'post_content' => $pfmData['post_content']
                )
            );
            
            wp_redirect(pfm_get_topic_permalink($topic_id)); exit;
            
        break;
        case 'post_create': //сооздение поста
            
            if(!pfm_is_can('post_create') || !$pfmData['topic_id']) return false;
            
            $topic = pfm_get_topic($pfmData['topic_id']);
            
            if($topic->topic_closed) return false;
            
            $args = array(
                'post_content' => $pfmData['post_content'],
                'topic_id' => $pfmData['topic_id']
            );
            
            if(!$user_ID){
                
                if(!$pfmData['guest_email'] || !$pfmData['guest_name']) return false;
                
                $args['guest_email'] = $pfmData['guest_email'];
                $args['guest_name'] = $pfmData['guest_name'];
            }
            
            $post_id = pfm_add_post($args);
            
            if(pfm_is_can('topic_close')){
                if(isset($pfmData['close-topic'][0]) && $pfmData['close-topic'][0]){
                    pfm_update_topic(array(
                        'topic_id' => $pfmData['topic_id'],
                        'topic_closed' => 1
                    ));
                }
            }
            
            wp_redirect(pfm_get_post_permalink($post_id)); exit;
            
        break;
        case 'post_edit': //редактирование поста
            
            if(!pfm_is_can_post_edit($pfmData['post_id']) || !$pfmData['topic_id'] || !$pfmData['post_id']) return false;
            
            pfm_update_post(array(
                'post_content' => $pfmData['post_content'],
                'post_id' => $pfmData['post_id']
            ));
            
            wp_redirect(pfm_get_post_permalink($pfmData['post_id'])); exit;
            
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
        case 'topic_from_post_create': //создание топика из поста
            
            if(!pfm_is_can('post_migrate') || !$pfmData['forum_id']) return false;
            
            $migratedPost = pfm_get_post($pfmData['post_id']);
            
            $topic_id = pfm_add_topic(array(
                    'topic_name' => $pfmData['topic_name'],
                    'forum_id' => $pfmData['forum_id'],
                    'user_id' => $migratedPost->user_id
                )
            );

            if(isset($pfmData['next_posts']) && $pfmData['next_posts']){
        
                global $wpdb;

                $posts = $wpdb->get_results("SELECT * FROM ".RCL_PREF."pforum_posts "
                        . "WHERE topic_id='$migratedPost->topic_id' "
                        . "AND post_index >= '$migratedPost->post_index'");

                foreach($posts as $post){
                    pfm_update_post(array(
                        'post_id' => $post->post_id,
                        'topic_id' => $topic_id
                    ));
                }

            }else{
                
                pfm_update_post(array(
                    'post_id' => $migratedPost->post_id,
                    'topic_id' => $topic_id
                ));
                
            }
            
            pfm_update_topic_data($migratedPost->topic_id);
            pfm_update_topic_data($topic_id);

            wp_redirect(pfm_get_topic_permalink($topic_id)); exit;
            
        break;
        case 'topic_migrate': //перенос топика в другой форум
            
            if(!pfm_is_can('topic_migrate') || !$pfmData['forum_id']) return false;
            
            $migratedTopic = pfm_get_topic($pfmData['topic_id']);
            
            $topic_id = pfm_update_topic(array(
                'topic_id' => $pfmData['topic_id'],
                'forum_id' => $pfmData['forum_id']
            ));
            
            pfm_update_forum_counter($migratedTopic->forum_id);
            pfm_update_forum_counter($pfmData['forum_id']);
            
            wp_redirect(pfm_get_topic_permalink($pfmData['topic_id'])); exit;
            
        break;
        case 'topic_edit': //изменение заголовка топика
            
            if(!pfm_is_can_topic_edit($pfmData['topic_id']) || !$pfmData['topic_id']) return false;
            
            $topic_id = pfm_update_topic(array(
                'topic_id' => $pfmData['topic_id'],
                'topic_name' => $pfmData['topic_name']
            ));
            
            wp_redirect(pfm_get_topic_permalink($pfmData['topic_id'])); exit;
            
        break;
        case 'member_go':
            
            wp_redirect(pfm_get_forum_permalink($pfmData['forum_id'])); exit;
            
        break;
    }
    
    wp_redirect($_POST['_wp_http_referer']); exit;
    
}

add_action('wp_ajax_pfm_ajax_action','pfm_ajax_action');
add_action('wp_ajax_nopriv_pfm_ajax_action','pfm_ajax_action');
function pfm_ajax_action(){
    global $PrimeActions;
    
    rcl_verify_ajax_nonce();
    
    $method = $_POST['method'];
    $itemType = $_POST['item_type'];
    $itemID = (isset($_POST['item_id']))? $_POST['item_id']: null;
    
    if(!isset($PrimeActions[$method])) exit;
    
    do_action('pfm_pre_ajax_action');
    
    $funcName = $PrimeActions[$method];
    
    $result = $funcName($itemID);
    
    if(!$result){
        $result['error'] = __('Не удалось выполнить действие');
    }

    if(!isset($result['error'])){
        $result['success'] = true;
    }
    
    echo json_encode($result); exit;

}

//сохранение ИД поста в куках для переноса в другой пост
pfm_add_ajax_action('confirm_migrate_post','pfm_action_confirm_migrate_post');
function pfm_action_confirm_migrate_post($post_id){
    
    if(!pfm_is_can('post_migrate')) return false;
    
    if(isset($_POST['formdata'])){
        
        $formdata = array();
        foreach($_POST['formdata'] as $data){
            $formdata[$data['name']] = $data['value'];
        }
        
    }
    
    $migrateData = array(
        'post_id' => $post_id,
        'next_posts' => 0
    );
    
    if(isset($formdata['next_posts_migrate[]']) && $formdata['next_posts_migrate[]']){
        
        $migrateData['next_posts'] = 1;
        
    }
    
    setcookie('pfm_migrate_post',json_encode($migrateData), time()+3600, '/', $_SERVER['HOST']);

    $result['content'] = pfm_get_notice(__('Перейдите на страницу нужного топика и нажмите на кнопку "Перенести в этот топик" для окончания переноса сообщения'),'warning');
    $result['dialog'] = true;
    $result['title'] = __('Данные готовы для переноса');
    
    return $result;
    
}

//показ формы с настройками миграции сообщения топика
pfm_add_ajax_action('start_post_migrate','pfm_action_start_post_migrate');
function pfm_action_start_post_migrate($post_id){
    
    if(!pfm_is_can('post_migrate')) return false;
    
    $fields = array(
        /*array(
            'type' => 'text',
            'slug' => 'posts_list_migrate',
            'title' => __('Перенести перечисленные сообщения'),
            'notice' => __('укажите через запятую номера сообщений, которые надо перенести в другой топик')
        ),*/
        array(
            'type' => 'checkbox',
            'slug' => 'next_posts_migrate',
            'values' => array(
                1 => __('Перенести также все последующие сообщения')
            )
        )
    );
    
    $args = array(
        'method' => 'confirm_migrate_post',
        'serialize_form' => 'manager-migrate-form',
        'item_id' => $post_id
    );
    
    $CF = new Rcl_Custom_Fields();
    
    $content = '<div id="manager-migrate" class="rcl-custom-fields-box">';
        $content .= '<form id="manager-migrate-form" method="post">';

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
                $content .= '<a href="#" title="'.__('Подтвердить перенос').'" class="recall-button topic-action action-migrate_posts" onclick=\'pfm_ajax_action('.json_encode($args).');return false;\'>';
                $content .= __('Подтвердить перенос');
                $content .= '</a>';
            $content .= '</div>';
            
        $content .= '</form>';
    $content .= '</div>';

    $result['content'] = $content;
    $result['dialog'] = true;
    $result['title'] = __('Перенос сообщения в другую тему');
    
    return $result;
    
}

pfm_add_ajax_action('cancel_post_migrate','pfm_action_cancel_post_migrate');
function pfm_action_cancel_post_migrate($topic_id){
    setcookie('pfm_migrate_post','', time()+3600, '/', $_SERVER['HOST']);
    $result['update-page'] = true;
    return $result;
}

//перенос поста в другой топик
pfm_add_ajax_action('end_post_migrate','pfm_action_end_post_migrate');
function pfm_action_end_post_migrate($topic_id){
    
    if(!pfm_is_can('post_migrate')) return false;
            
    $migrateData = json_decode(wp_unslash($_COOKIE['pfm_migrate_post']));
    
    $post_id = intval($migrateData->post_id);
    
    if(!$migrateData || !$post_id){
        $result['error'] = __('Перенос не удался');
    }else{

        $post = pfm_get_post($post_id);

        if(!$post) return false;
        
        $topicOld = $post->topic_id;
        
        if(isset($migrateData->next_posts) && $migrateData->next_posts){
        
            global $wpdb;

            $posts = $wpdb->get_results("SELECT * FROM ".RCL_PREF."pforum_posts "
                    . "WHERE topic_id='$topicOld' "
                    . "AND post_index >= '$post->post_index'");

            foreach($posts as $post){
                pfm_update_post(array(
                    'post_id' => $post->post_id,
                    'topic_id' => $topic_id
                ));
            }
        
        }else{

            pfm_update_post(array(
                'post_id' => $post_id,
                'topic_id' => $topic_id
            ));
        
        }

        setcookie('pfm_migrate_post','', time()+3600, '/', $_SERVER['HOST']);

        pfm_update_topic_data($topicOld);
        pfm_update_topic_data($topic_id);

        $result['url-redirect'] = pfm_get_post_permalink($post_id);

    }
    
    return $result;
}

//вызов формы создания топика из поста
pfm_add_ajax_action('get_form_topic_create','pfm_action_get_form_topic_create');
function pfm_action_get_form_topic_create($post_id){
    
    if(!pfm_is_can('post_migrate')) return false;
            
    $post = pfm_get_post($post_id);

    $result['content'] = pfm_get_form(array(
        'action' => 'topic_from_post_create',
        'submit' => __('Сохранить изменения'),
        'forum_list' => true,
        'post_id' => $post_id,
        'values' => array(
            'post_content' => $post->post_content
        ),
        'fields' => array(
            array(
                'type' => 'checkbox',
                'slug' => 'next_posts',
                'name' => 'pfm-data[next_posts]',
                'values' => array(1 => __('Перенести также все последующие сообщения'))
            )
        )
    ));

    $result['dialog'] = true;
    $result['title'] = __('Перенос сообщения в новую тему');
    
    return $result;
    
}

//вызов формы редактирования поста
pfm_add_ajax_action('get_form_post_edit','pfm_action_get_form_post_edit');
function pfm_action_get_form_post_edit($post_id){
    
    if(!pfm_is_can_post_edit($post_id)) return false;
            
    $post = pfm_get_post($post_id);

    $result['content'] = pfm_get_form(
        array(
            'action' => 'post_edit',
            'submit' => __('Сохранить изменения'),
            'post_id' => $post_id,
            'topic_id' => $post->topic_id,
            'values' => array(
                'post_content' => $post->post_content
            )
        )
    );

    $result['dialog'] = true;
    $result['title'] = __('Редактирование сообщения');
    
    return $result;
    
}

//удаление поста
pfm_add_ajax_action('post_delete','pfm_action_post_delete');
function pfm_action_post_delete($post_id){
    
    if(!pfm_is_can_post_delete($post_id)) return false;
            
    $post = pfm_get_post($post_id);

    $topic = pfm_get_topic($post->topic_id);

    $res = pfm_delete_post($post_id);

    if($res){
        $result['remove-item'] = 'topic-post-'.$post_id;

        if($topic->post_count == 1){
            $result['url-redirect'] = pfm_get_forum_permalink($topic->forum_id);
        }else{
            $result['dialog-close'] = true;
        }

    }else{
        $result['error'] = __('Удаление не удалось');
    }
    
    return $result;
    
}

//закрытие топика
pfm_add_ajax_action('topic_close','pfm_action_topic_close');
function pfm_action_topic_close($topic_id){
   
    if(!pfm_is_can('topic_close')) return false;
            
    $topic = pfm_get_topic($topic_id);

    if(!$topic){ 

        $result['error'] = __('Не удалось закрыть топик');

    }else{

        pfm_update_topic(array(
            'topic_id' => $topic_id,
            'topic_closed' => 1
        ));

        $result['update-page'] = true;

    }
    
    return $result;
}

//открытие топика
pfm_add_ajax_action('topic_unclose','pfm_action_topic_unclose');
function pfm_action_topic_unclose($topic_id){
    
    if(!pfm_is_can('topic_close')) return false;
            
    $topic = pfm_get_topic($topic_id);

    if(!$topic){ 

        $result['error'] = __('Не удалось открыть топик');

    }else{

        pfm_update_topic(array(
            'topic_id' => $topic_id,
            'topic_closed' => 0
        ));

        $result['update-page'] = true;

    }
    
    return $result;
    
}

//удаление топика
pfm_add_ajax_action('topic_delete','pfm_action_topic_delete');
function pfm_action_topic_delete($topic_id){
    
    if(!pfm_is_can_topic_delete($topic_id)) return false;
            
    $topic = pfm_get_topic($topic_id);

    if(!$topic){ 

        $result['error'] = __('Не удалось удалить топик');

    }else{

        pfm_delete_topic($topic_id);

        $result['url-redirect'] = pfm_get_forum_permalink($topic->forum_id);

    }
    
    return $result;
}

//вызов формы переноса топика в другой форум
pfm_add_ajax_action('get_form_topic_migrate','pfm_action_get_form_topic_migrate');
function pfm_action_get_form_topic_migrate($topic_id){
    
    if(!pfm_is_can('topic_migrate')) return false;
            
    $topic = pfm_get_topic($topic_id);

    if(!$topic){ 

        $result['error'] = __('Не удалось получить топик');

    }else{

        $content = '<div id="post-manager" class="manager-box">';
        $content .= pfm_get_form(array(
            'action' => 'topic_migrate',
            'submit' => __('Перенести топик'),
            'topic_id' => $topic_id,
            'forum_list' => true,
            'exclude_fields' => array(
                'topic_name',
                'post_content'
            )
        ));
        $content .= '</div>';

        $result['content'] = $content;
        $result['dialog'] = true;
        $result['title'] = __('Перенос темы в другой форум');
    }
    
    return $result;
}

//вызов формы изменения названия топика
pfm_add_ajax_action('get_form_topic_edit','pfm_action_get_form_topic_edit');
function pfm_action_get_form_topic_edit($topic_id){
    
    if(!pfm_is_can_topic_edit($topic_id)) return false;
            
    $topic = pfm_get_topic($topic_id);

    if(!$topic){ 

        $result['error'] = __('Не удалось получить топик');

    }else{
        
        $args = array(
            'action' => 'topic_edit',
            'submit' => __('Сохранить изменения'),
            'forum_id' => $topic->forum_id,
            'topic_id' => $topic_id,
            'values' => array(
                'topic_name' => $topic->topic_name
            ),
            'exclude_fields' => array(
                'post_content'
            )
        );
        
        $Meta = new PrimeMeta();
        
        $metas = $Meta->get_results(array(
            'object_id' => $topic_id,
            'object_type' => 'topic',
            'fields' => array(
                'meta_key',
                'meta_value'
            )
        ));
        
        if($metas){
            $metadata = array();
            foreach($metas as $meta){
                $args['values'][$meta->meta_key] = maybe_unserialize($meta->meta_value);
            }
        }

        $content = '<div id="post-manager" class="manager-box">';
        $content .= pfm_get_form($args);
        $content .= '</div>';

        $result['content'] = $content;
        $result['dialog'] = true;
        $result['title'] = __('Редактирование темы');
    }
    
    return $result;
    
}

//закрепление топика
pfm_add_ajax_action('topic_fix','pfm_action_topic_fix');
function pfm_action_topic_fix($topic_id){
    
    if(!pfm_is_can('topic_fix')) return false;
            
    $topic = pfm_get_topic($topic_id);

    if(!$topic){ 

        $result['error'] = __('Не удалось закрепить топик');

    }else{

        pfm_update_topic(array(
            'topic_id' => $topic_id,
            'topic_fix' => 1
        ));

        $result['update-page'] = true;

    }
    
    return $result;
}

//открепление топика
pfm_add_ajax_action('topic_unfix','pfm_action_topic_unfix');
function pfm_action_topic_unfix($topic_id){
    
    if(!pfm_is_can('topic_fix')) return false;
            
    $topic = pfm_get_topic($topic_id);

    if(!$topic){ 

        $result['error'] = __('Не удалось открепить топик');

    }else{

        pfm_update_topic(array(
            'topic_id' => $topic_id,
            'topic_fix' => 0
        ));

        $result['update-page'] = true;

    }

    return $result;
}

//получение цитаты публикации
pfm_add_ajax_action('get_post_excerpt','pfm_action_get_post_excerpt');
function pfm_action_get_post_excerpt($post_id){
    
    if(!pfm_is_can('post_create')) return false;
            
    $post = pfm_get_post($post_id);

    if(!$post){ 

        $result['error'] = __('Не удалось получить цитату сообщения');

    }else{
        
        $author_name = $post->user_id? get_the_author_meta('display_name',$post->user_id): $post->guest_name;

        if(isset($_POST['excerpt']) && $_POST['excerpt']){

            $content = wp_unslash($_POST['excerpt']);

            if(strpos($post->post_content, $content) !== false){
                $content = '<blockquote><strong>'.$author_name.' сказал(а) </strong><br />'.$content.'</blockquote><br />';
            }else{
                $content = '<blockquote>'.$content.'</blockquote><br />';
            }

        }else{

            $content = wp_unslash($post->post_content);

            $content = '<blockquote><strong>'.$author_name.' сказал(а) </strong><br />'.$content.'</blockquote><br />';

        }
        
        $content = str_replace(array(
            '<br />'.chr(13).chr(10),
            '<br />',
            '<br/>',
            '<br>'
            ), "\n", $content);

        $content = str_replace('<p></p>', "\n\n", $content);
        $content = str_replace('<p> </p>', "\n\n", $content);
        $content = str_replace('<p>', '', $content);
        $content = str_replace('</p>', chr(13).chr(10), $content);

        $content = htmlspecialchars_decode($content, ENT_COMPAT);

        $result['content'] = $content;
        $result['place-id'] = '#editor-action_post_create';

    }
    
    return $result;
}

//получение списка форумов
pfm_add_ajax_action('get_structure','pfm_action_get_structure');
function pfm_action_get_structure(){

    $content = '<div id="forum-manager" class="manager-box">';
    $content .= pfm_get_form(array(
        'action' => 'member_go',
        'submit' => __('Перейти в выбранный форум'),
        'forum_list' => true,
        'exclude_fields' => array(
            'topic_name',
            'post_content'
        )
    ));
    $content .= '</div>';

    $result['content'] = $content;
    $result['dialog'] = true;
    $result['title'] = __('Быстрый переход на форум');

    return $result;
}

//получение обновленных тем
pfm_add_ajax_action('get_last_updated_topics','pfm_action_get_last_updated_topics');
function pfm_action_get_last_updated_topics(){
    global $wpdb,$PrimeTopic,$PrimeQuery;
    
    $theme = pfm_get_current_theme();
    
    $topics = $wpdb->get_results(
        "SELECT "
            . "ptopics.*, "
            . "MAX(pfm_posts.post_date) AS last_post_date, "
            . "MAX(pfm_posts.post_id) AS last_post_id "
        . "FROM "
        . RCL_PREF."pforum_topics AS ptopics "
        . "INNER JOIN ".RCL_PREF."pforum_posts AS pfm_posts ON ptopics.topic_id = pfm_posts.topic_id "
            . "GROUP BY ptopics.topic_id "
        . "ORDER BY MAX(pfm_posts.post_date)DESC "
        . "LIMIT 20"
    );
    
    $PrimeQuery = new PrimeQuery();
    
    $PrimeQuery->last['posts'] = $PrimeQuery->get_topics_last_post($topics);

    $content = '<div id="prime-forum">';
    
    if($topics){
        $content .= '<div class="prime-topics-list prime-loop-list">';
        foreach(wp_unslash($topics) as $PrimeTopic){
            $content .= rcl_get_include_template('pfm-single-topic.php',$theme['path']);
        }
        $content .= '</div>';
    }else{
        
        $content .= pfm_get_notice(__('Ничего не найдено'));
        
    }
    
    $content .= '</div>';

    $result['content'] = $content;
    $result['dialog'] = true;
    $result['title'] = __('Обновленные темы форума');
    
    return $result;
}

//получение приватного чата
pfm_add_ajax_action('get_private_chat','pfm_action_get_private_chat');
function pfm_action_get_private_chat($user_id){
    
    $chatdata = rcl_get_chat_private($user_id);
    $chat = $chatdata['content'];
    
    $result['content'] = $chatdata['content'];
    $result['dialog'] = true;
    $result['dialog-width'] = 'small';
    $result['title'] = __('Чат с '.get_the_author_meta('display_name',$user_id));
    
    return $result;
}

//получение информации о пользователе
pfm_add_ajax_action('get_author_info','pfm_action_get_author_info');
function pfm_action_get_author_info($user_id){
    
    $result['content'] = rcl_get_user_details($user_id,array('zoom'=>false));
    $result['dialog'] = true;
    $result['dialog-width'] = 'auto';
    $result['dialog-class'] = 'rcl-user-getails';
    $result['title'] = __('Подробная информация');
    
    return $result;
}

//предпросмотр сообщения
pfm_add_ajax_action('get_preview','pfm_action_get_preview');
function pfm_action_get_preview($action){
    
    if(isset($_POST['formdata'])){
        
        $formdata = array();
        foreach($_POST['formdata'] as $data){
            $formdata[$data['name']] = $data['value'];
        }
        
    }
    
    $postContent = wp_unslash($formdata['pfm-data[post_content]']);
    
    if(!$postContent){
        
        $result['error'] = __('Пустое сообщение!');
    
        return $result;
        
    }
    
    global $PrimeShorts,$PrimePost,$PrimeUser,$user_ID;
    
    $PrimeUser = new PrimeUser();
    $PrimeShorts = pfm_get_shortcodes();   

    $theme = rcl_get_addon(get_option('rcl_pforum_template'));
    
    $PrimePost = array(
        'post_id' => 0,
        'user_id' => $user_ID,
        'post_content' => $postContent,
        'post_date' => current_time('mysql'),
        'display_name' => get_the_author_meta('display_name',$user_ID),
        'user_registered' => get_the_author_meta('user_registered',$user_ID)
    );
    
    $PrimePost = (object)$PrimePost;

    $content = '<div id="prime-forum">';

    $content .= rcl_get_include_template('pfm-single-post.php',$theme['path']);
        
    $content .= '</div>';
    
    $result['content'] = $content;
    $result['dialog'] = true;
    $result['dialog-width'] = 'small';
    $result['title'] = __('Предпросмотр');
    
    return $result;
}