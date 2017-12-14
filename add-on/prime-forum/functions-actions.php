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
        
        if(function_exists('rcl_get_user_details')){
        
            $actions['get_author_info'] = array(
                'name' => __('Details about the author','wp-recall'),
                'icon' => 'fa-info-circle'
            );
        
        }

        if(rcl_exist_addon('rcl-chat')){

            $actions['get_private_chat'] = array(
                'name' => __('Go to the private chat','wp-recall'),
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
    global $user_ID;
    
    $actions = array(
        'get_last_updated_topics' => array(
            'name' => __('Get the list of updated topics','wp-recall'),
            'icon' => 'fa-bell-o'
        )
    );
    
    if($user_ID){
        $actions['get_author_topics'] = array(
            'name' => __('Get your started topics','wp-recall'),
            'icon' => 'fa-address-book'
        );
    }
    
    $actions['get_structure'] = array(
        'name' => __('Jump to the needed forum','wp-recall'),
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
            'name' => __('Delete message','wp-recall'),
            'icon' => 'fa-trash',
            'options' => array(
                'confirm' => __('Are you sure?','wp-recall')
            )
        );
        
    }

    if(pfm_is_can('post_migrate')){
        
        $actions['start_post_migrate'] = array(
            'name' => __('Transfer to the existing topic','wp-recall'),
            'icon' => 'fa-share-square-o'
        );
        
        $actions['get_form_topic_create'] = array(
            'name' => __('Transfer to the new topic','wp-recall'),
            'icon' => 'fa-code-fork'
        );
        
    }
    
    if(pfm_is_can_post_edit($PrimePost->post_id)){
        
        $actions['get_form_post_edit'] = array(
            'name' => __('Edit message','wp-recall'),
            'icon' => 'fa-pencil-square-o'
        );
    }
    
    if(pfm_is_can('post_create') && !$PrimeTopic->topic_closed && !$PrimeTopic->forum_closed){
        
        $actions['get_post_excerpt'] = array(
            'name' => __('Quote message','wp-recall'),
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
                'name' => __('Transfer to this topic','wp-recall')
            );
            $actions['cancel_post_migrate'] = array(
                'name' => __('Cancel transfer','wp-recall'),
                'icon' => 'fa-times'
            );
        }
        
    }
    
    if(pfm_is_can_topic_delete($PrimeTopic->topic_id)){
    
        $actions['topic_delete'] = array(
            'name' => __('Delete topic','wp-recall'),
            'icon' => 'fa-trash',
            'options' => array(
                'confirm' => __('Are you sure?','wp-recall')
            )
        );
    
    }

    if(pfm_is_can('topic_migrate')){
        
        $actions['get_form_topic_migrate'] = array(
            'name' => __('Transfer topic','wp-recall'),
            'icon' => 'fa-chain-broken'
        );
        
    }
    
    if(pfm_is_can('topic_fix')){
    
        if($PrimeTopic->topic_fix){
            $actions['topic_unfix'] = array(
                'name' => __('Unpin topic','wp-recall'),
                'icon' => 'fa-star'
            );
        }else{
            $actions['topic_fix'] = array(
                'name' => __('Pin topic','wp-recall'),
                'icon' => 'fa-star-o'
            );
        }
    
    }
    
    if(pfm_is_can('topic_close')){
    
        if($PrimeTopic->topic_closed){
            $actions['topic_unclose'] = array(
                'name' => __('Open topic','wp-recall'),
                'icon' => 'fa-lock'
            );
        }else{
            $actions['topic_close'] = array(
                'name' => __('Close topic','wp-recall'),
                'icon' => 'fa-unlock'
            );
        }
    
    }
    
    if(pfm_is_can_topic_edit($PrimeTopic->topic_id)){
        
        $actions['get_form_topic_edit'] = array(
            'name' => __('Change name','wp-recall'),
            'icon' => 'fa-pencil-square-o'
        );
    
    }

    $actions = apply_filters('pfm_topic_manager_actions', $actions, $PrimeTopic);
    
    if(!$actions) return false;
    
    $content = pfm_get_manager($actions,'topic',$PrimeTopic->topic_id);
    
    echo $content;
    
}

add_action('pfm_after_init_query', 'pfm_init_actions', 30);
function pfm_init_actions(){
    global $user_ID;
    
    if(!isset($_REQUEST['pfm-data']) || !isset($_REQUEST['pfm-data']['action'])) return;
    
    if(!isset($_REQUEST['_wpnonce']) || !wp_verify_nonce($_REQUEST['_wpnonce'],'pfm-action')) return;
    
    $pfmData = $_REQUEST['pfm-data'];
    
    $action = $pfmData['action'];
    
    switch($action){
        case 'topic_create': //создание топика
            
            if(!pfm_is_can('topic_create') || !$pfmData['forum_id']) return false;
            
            if(!$pfmData['post_content']){
                wp_die(__('Empty message! Go back and write something.','wp-recall'));
            }
            
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
        /*case 'post_create': //сооздение поста
            
            if(!pfm_is_can('post_create') || !$pfmData['topic_id']) return false;
            
            $topic = pfm_get_topic($pfmData['topic_id']);
            
            if($topic->topic_closed) return false;
            
            if(!$pfmData['post_content']){
                wp_die(__('Empty message! Go back and write something.','wp-recall'));
            }
            
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
            
        break;*/
        case 'post_edit': //редактирование поста
            
            if(!pfm_is_can_post_edit($pfmData['post_id']) || !$pfmData['topic_id'] || !$pfmData['post_id']) return false;
            
            $post_edit = maybe_unserialize(pfm_get_post_field($pfmData['post_id'], 'post_edit'));

            $reasonEdit = '';
            if(isset($_POST['reason_edit']) && $_POST['reason_edit']){
                
                $reasonEdit = $_POST['reason_edit'];
                
            }
            
            $post_edit[] = array(
                'time' => current_time('mysql'),
                'author' => pfm_get_user_name($user_ID),
                'reason' => $reasonEdit
            );
            
            pfm_update_post(array(
                'post_content' => $pfmData['post_content'],
                'post_id' => $pfmData['post_id'],
                'post_edit' => $post_edit
            ));
            
            wp_redirect(pfm_get_post_permalink($pfmData['post_id'])); exit;
            
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

                $posts = pfm_get_posts(array(
                    'topic_id' => $migratedPost->topic_id,
                    'post_index__from' => $migratedPost->post_index,
                    'number' => -1
                ));

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
            
            pfm_update_topic(array(
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

rcl_ajax_action('pfm_ajax_action', true);
function pfm_ajax_action(){
    global $PrimeActions, $PrimeQuery, $PrimeUser;
    
    rcl_verify_ajax_nonce();
    
    $method = $_POST['method'];
    $itemType = $_POST['item_type'];
    $itemID = (isset($_POST['item_id']))? $_POST['item_id']: null;
    
    if(!isset($PrimeActions[$method])) exit;
    
    $PrimeUser = new PrimeUser();
    
    $PrimeQuery = new PrimeQuery();
    
    do_action('pfm_pre_ajax_action');
    
    $funcName = $PrimeActions[$method];
    
    $result = $funcName($itemID);
    
    if(!$result){
        $result['error'] = __('Unable to perform action','wp-recall');
    }
    
    do_action('pfm_ajax_action', $method, $itemID);
    
    $result = apply_filters('pfm_action_result',$result, $method, $itemID);
    
    wp_send_json($result);

}

//сохранение ИД поста в куках для переноса в другой пост
pfm_add_ajax_action('confirm_migrate_post','pfm_action_confirm_migrate_post');
function pfm_action_confirm_migrate_post($post_id){
    
    if(!pfm_is_can('post_migrate')) return false;
    
    if(isset($_POST['formdata'])){
        $formdata = array();
        parse_str($_POST['formdata'], $formdata);
    }
    
    $migrateData = array(
        'post_id' => $post_id,
        'next_posts' => 0
    );
    
    if(isset($formdata['next_posts_migrate'][0]) && $formdata['next_posts_migrate'][0]){
        
        $migrateData['next_posts'] = 1;
        
    }
    
    setcookie('pfm_migrate_post',json_encode($migrateData), time()+3600, '/', $_SERVER['HOST']);
    
    return array(
        'content' => pfm_get_notice(__('Go to the page of the necessary topic and press the "Transfer to this topic" button to end message transfer','wp-recall'),'warning'),
        'title' => __('Data is ready to be transferred','wp-recall'),
        'dialog' => true
    );
    
}

//показ формы с настройками миграции сообщения топика
pfm_add_ajax_action('start_post_migrate','pfm_action_start_post_migrate');
function pfm_action_start_post_migrate($post_id){
    
    if(!pfm_is_can('post_migrate')) return false;
    
    $fields = array(

        array(
            'type' => 'checkbox',
            'slug' => 'next_posts_migrate',
            'values' => array(
                1 => __('Also transfer all subsequent messages','wp-recall')
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
                $content .= '<a href="#" title="'.__('Confirm transfer','wp-recall').'" class="recall-button topic-action action-migrate_posts" onclick=\'pfm_ajax_action('.json_encode($args).');return false;\'>';
                $content .= __('Confirm transfer','wp-recall');
                $content .= '</a>';
            $content .= '</div>';
            
        $content .= '</form>';
    $content .= '</div>';

    return array(
        'content' => $content,
        'title' => __('Transfer messages to another topic','wp-recall'),
        'dialog' => true
    );
    
}

pfm_add_ajax_action('cancel_post_migrate','pfm_action_cancel_post_migrate');
function pfm_action_cancel_post_migrate($topic_id){
    setcookie('pfm_migrate_post','', time()+3600, '/', $_SERVER['HOST']);
    return array(
        'update-page' => true,
        'preloader_live' => true
    );
}

//перенос поста в другой топик
pfm_add_ajax_action('end_post_migrate','pfm_action_end_post_migrate');
function pfm_action_end_post_migrate($topic_id){
    
    if(!pfm_is_can('post_migrate')) return false;
            
    $migrateData = json_decode(wp_unslash($_COOKIE['pfm_migrate_post']));
    
    $post_id = intval($migrateData->post_id);

    if(!$migrateData || !$post_id){
        return array('error' => __('Unsuccessful transfer','wp-recall'));
    }

    $post = pfm_get_post($post_id);

    if(!$post) return false;

    $topicOld = $post->topic_id;

    if(isset($migrateData->next_posts) && $migrateData->next_posts){

        $posts = pfm_get_posts(array(
            'topic_id' => $topicOld,
            'post_index__from' => $post->post_index,
            'number' => -1
        ));

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

    return array(
        'url-redirect' => pfm_get_post_permalink($post_id),
        'preloader_live' => 1
    );

}

//вызов формы создания топика из поста
pfm_add_ajax_action('get_form_topic_create','pfm_action_get_form_topic_create');
function pfm_action_get_form_topic_create($post_id){
    
    if(!pfm_is_can('post_migrate')) return false;
            
    $post = pfm_get_post($post_id);

    return array(
        'content' => pfm_get_form(array(
            'action' => 'topic_from_post_create',
            'submit' => __('Save changes','wp-recall'),
            'forum_list' => true,
            'post_id' => $post_id,
            'values' => array(
                'post_content' => wp_slash($post->post_content)
            ),
            'fields' => array(
                array(
                    'type' => 'checkbox',
                    'slug' => 'next_posts',
                    'name' => 'pfm-data[next_posts]',
                    'values' => array(1 => __('Also transfer all subsequent messages','wp-recall'))
                )
            )
        )),
        'title' => __('Transfer message to a new topic','wp-recall'),
        'dialog' => true
    );
    
}

//вызов формы редактирования поста
pfm_add_ajax_action('get_form_post_edit','pfm_action_get_form_post_edit');
function pfm_action_get_form_post_edit($post_id){
    
    if(!pfm_is_can_post_edit($post_id)) return false;
            
    $post = pfm_get_post($post_id);

    return array(
        'content' => pfm_get_form(
            array(
                'action' => 'post_edit',
                'submit' => __('Save changes','wp-recall'),
                'post_id' => $post_id,
                'topic_id' => $post->topic_id,
                'values' => array(
                    'post_content' => wp_slash($post->post_content)
                )
            )
        ),
        'title' => __('Edit messages','wp-recall'),
        'dialog' => true
    );
    
}

//удаление поста
pfm_add_ajax_action('post_delete','pfm_action_post_delete');
function pfm_action_post_delete($post_id){
    
    if(!pfm_is_can_post_delete($post_id)) return false;
            
    $post = pfm_get_post($post_id);

    $res = pfm_delete_post($post_id);

    if(!$res){
        return array('error' => __('Unsuccessful deletion','wp-recall'));
    }
        
    $result = array(
        'remove-item' => 'topic-post-'.$post_id
    );
    
    $topic = pfm_get_topic($post->topic_id);

    if($topic->post_count == 1){
        $result['url-redirect'] = pfm_get_forum_permalink($topic->forum_id);
        $result['preloader_live'] = 1;
    }
    
    return $result;
    
}

//закрытие топика
pfm_add_ajax_action('topic_close','pfm_action_topic_close');
function pfm_action_topic_close($topic_id){
   
    if(!pfm_is_can('topic_close')) return false;
            
    $topic = pfm_get_topic($topic_id);

    if(!$topic){ 
        return array('error' => __('Unable to close topic','wp-recall'));
    }

    pfm_topic_close($topic_id);

    return array(
        'update-page' => true,
        'preloader_live' => 1
    );

}

//открытие топика
pfm_add_ajax_action('topic_unclose','pfm_action_topic_unclose');
function pfm_action_topic_unclose($topic_id){
    
    if(!pfm_is_can('topic_close')) return false;
            
    $topic = pfm_get_topic($topic_id);

    if(!$topic){ 
        return array('error' => __('Unable to open topic','wp-recall'));
    }

    pfm_topic_unclose($topic_id);

    return array(
        'update-page' => 1,
        'preloader_live' => 1
    );
    
}

//удаление топика
pfm_add_ajax_action('topic_delete','pfm_action_topic_delete');
function pfm_action_topic_delete($topic_id){
    
    if(!pfm_is_can_topic_delete($topic_id)) return false;
       
    $topic = pfm_get_topic($topic_id);

    if(!$topic){ 
        return array('error' => __('Unable to delete topic','wp-recall'));
    }

    pfm_delete_topic($topic_id);

    $url = pfm_get_forum_permalink($topic->forum_id);

    if(isset($_POST['topic_id']) && $_POST['topic_id'])
        $result = array('url-redirect' => $url);
    else
        $result = array('url-redirect' => pfm_add_number_page($url, $_POST['current_page']));

    $result['preloader_live'] = 1;
    
    return $result;
}

//вызов формы переноса топика в другой форум
pfm_add_ajax_action('get_form_topic_migrate','pfm_action_get_form_topic_migrate');
function pfm_action_get_form_topic_migrate($topic_id){
    
    if(!pfm_is_can('topic_migrate')) return false;
            
    $topic = pfm_get_topic($topic_id);

    if(!$topic){ 
        return array('error' => __('Unable to get topic','wp-recall'));
    }

    $content = '<div id="post-manager" class="manager-box">';
    $content .= pfm_get_form(array(
        'action' => 'topic_migrate',
        'submit' => __('Transfer topic','wp-recall'),
        'topic_id' => $topic_id,
        'forum_list' => true,
        'exclude_fields' => array(
            'topic_name',
            'post_content'
        )
    ));
    $content .= '</div>';

    return array(
        'content' => $content,
        'dialog' => true,
        'title' => __('Transfer topic to another forum','wp-recall')
    );

}

//вызов формы изменения названия топика
pfm_add_ajax_action('get_form_topic_edit','pfm_action_get_form_topic_edit');
function pfm_action_get_form_topic_edit($topic_id){
    
    if(!pfm_is_can_topic_edit($topic_id)) return false;
            
    $topic = pfm_get_topic($topic_id);

    if(!$topic){ 
        return array('error' => __('Unable to get topic','wp-recall'));
    }
        
    $args = array(
        'action' => 'topic_edit',
        'submit' => __('Save changes','wp-recall'),
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

    return array(
        'content' => $content,
        'dialog' => true,
        'title' => __('Edit topic','wp-recall')
    );
    
}

//закрепление топика
pfm_add_ajax_action('topic_fix','pfm_action_topic_fix');
function pfm_action_topic_fix($topic_id){
    
    if(!pfm_is_can('topic_fix')) return false;
            
    $topic = pfm_get_topic($topic_id);

    if(!$topic){ 
        return array('error' => __('Unable to pin topic','wp-recall'));
    }

    pfm_update_topic(array(
        'topic_id' => $topic_id,
        'topic_fix' => 1
    ));

    return array(
        'update-page' => true,
        'preloader_live' => 1
    );

}

//открепление топика
pfm_add_ajax_action('topic_unfix','pfm_action_topic_unfix');
function pfm_action_topic_unfix($topic_id){
    
    if(!pfm_is_can('topic_fix')) return false;
            
    $topic = pfm_get_topic($topic_id);

    if(!$topic){ 
        return array('error' => __('Unable to unpin topic','wp-recall'));
    }

    pfm_update_topic(array(
        'topic_id' => $topic_id,
        'topic_fix' => 0
    ));

    return array(
        'update-page' => true,
        'preloader_live' => 1
    );

}

//получение цитаты публикации
pfm_add_ajax_action('get_post_excerpt','pfm_action_get_post_excerpt');
function pfm_action_get_post_excerpt($post_id){
    
    if(!pfm_is_can('post_create')) return false;
            
    $post = pfm_get_post($post_id);

    if(!$post){ 
        return array('error' => __('Unable to get the message quote','wp-recall'));
    }
        
    $author_name = $post->user_id? pfm_get_user_name($post->user_id): $post->guest_name;

    if(isset($_POST['excerpt']) && $_POST['excerpt']){

        $content = wp_unslash($_POST['excerpt']);

        if(strpos($post->post_content, $content) !== false){
            $content = '<blockquote><strong>'.$author_name.' '.__('said','wp-recall').' </strong><br />'.$content.'</blockquote><br />';
        }else{
            $content = '<blockquote>'.$content.'</blockquote><br />';
        }

    }else{

        $content = $post->post_content;

        $content = '<blockquote><strong>'.$author_name.' '.__('said','wp-recall').' </strong><br />'.$content.'</blockquote><br />';

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

    return array(
        'content' => $content,
        'place-id' => '#editor-action_post_create'
    );

}

//получение списка форумов
pfm_add_ajax_action('get_structure','pfm_action_get_structure');
function pfm_action_get_structure(){

    $content = '<div id="forum-manager" class="manager-box">';
    $content .= pfm_get_form(array(
        'action' => 'member_go',
        'submit' => __('Go to the selected forum','wp-recall'),
        'forum_list' => true,
        'exclude_fields' => array(
            'topic_name',
            'post_content'
        )
    ));
    $content .= '</div>';

    return array(
        'content' => $content,
        'dialog' => true,
        'title' => __('Jump to the forum','wp-recall')
    );

}

//получение списка форумов
pfm_add_ajax_action('get_author_topics','pfm_action_get_author_topics');
function pfm_action_get_author_topics(){
    global $user_ID;
    
    return array(
        'content' => pfm_get_user_topics_list($user_ID, false),
        'dialog' => true,
        'title' => __('Last started topics','wp-recall')
    );
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
        . "ORDER BY MAX(pfm_posts.post_date) DESC "
        . "LIMIT 20"
    );

    $PrimeQuery->last['posts'] = $PrimeQuery->get_topics_last_post($topics);

    $content = '<div id="prime-forum">';
    
    if($topics){
        $content .= '<div class="prime-topics-list prime-loop-list">';
        foreach(wp_unslash($topics) as $PrimeTopic){
            $content .= rcl_get_include_template('pfm-single-topic.php',$theme['path']);
        }
        $content .= '</div>';
    }else{
        
        $content .= pfm_get_notice(__('Nothing found','wp-recall'));
        
    }
    
    $content .= '</div>';

    return array(
        'content' => $content,
        'dialog' => true,
        'title' => __('Updated forum topics','wp-recall')
    );
}

//получение приватного чата
pfm_add_ajax_action('get_private_chat','pfm_action_get_private_chat');
function pfm_action_get_private_chat($user_id){
    
    $chatdata = rcl_get_chat_private($user_id);

    return array(
        'content' => $chatdata['content'],
        'dialog' => true,
        'dialog-width' => 'small',
        'title' => __('Chat with','wp-recall').' '.get_the_author_meta('display_name',$user_id)
    );
}

//получение информации о пользователе
pfm_add_ajax_action('get_author_info','pfm_action_get_author_info');
function pfm_action_get_author_info($user_id){
    return array(
        'content' => rcl_get_user_details($user_id,array('zoom'=>false)),
        'dialog' => true,
        'dialog-width' => 'auto',
        'dialog-class' => 'rcl-user-getails',
        'title' => __('Detailed information','wp-recall')
    );
}

//предпросмотр сообщения
pfm_add_ajax_action('get_preview','pfm_action_get_preview');
function pfm_action_get_preview($action){
    
    if(isset($_POST['formdata'])){
        $formdata = array();
        parse_str($_POST['formdata'], $formdata);
    }
    
    $pfmData = $formdata['pfm-data'];
    
    $postContent = wp_unslash($pfmData['post_content']);
    
    if(!$postContent){

        return array('error' => __('Empty message!','wp-recall'));
        
    }
    
    global $PrimeShorts,$PrimePost,$PrimeUser,$user_ID;
    
    $PrimeShorts = pfm_get_shortcodes();   

    $theme = rcl_get_addon(get_option('rcl_pforum_template'));
    
    $postData = array(
        'post_id' => 0,
        'user_id' => $user_ID,
        'post_content' => $postContent,
        'post_date' => current_time('mysql'),
        'display_name' => $user_ID? get_the_author_meta('display_name',$user_ID): '',
        'guest_name' => !$user_ID? $pfmData['guest_name']: '',
        'guest_email' => !$user_ID? $pfmData['guest_email']: '',
        'user_registered' => $user_ID? get_the_author_meta('user_registered',$user_ID): ''
    );
    
    $PrimePost = apply_filters('pfm_preview_postdata', $postData);
    
    $PrimePost = (object)$PrimePost;

    $content = '<div id="prime-forum">';

    $content .= rcl_get_include_template('pfm-single-post.php',$theme['path']);
        
    $content .= '</div>';

    return array(
        'content' => $content,
        'dialog' => true,
        'dialog-width' => 'small',
        'title' => __('Preview','wp-recall')
    );
}

pfm_add_ajax_action('post_create','pfm_action_post_create');
function pfm_action_post_create(){
    global $user_ID;

    if(isset($_POST['formdata'])){
        $formdata = array();
        parse_str($_POST['formdata'], $formdata);
    }
    
    $pfmData = $formdata['pfm-data'];
    
    if(!pfm_is_can('post_create') || !$pfmData['topic_id']){
        return array('error' => __('Insufficient rights to publish','wp-recall'));
    }
            
    $topic = pfm_get_topic($pfmData['topic_id']);

    if($topic->topic_closed){
        return array('error' => __('Topic closed','wp-recall'));
    }

    if(!$pfmData['post_content']){
        return array('error' => __('Empty message! Go back and write something.','wp-recall'));
    }
    
    $lastPost = get_topic_last_post($pfmData['topic_id']);
    
    if($lastPost->post_content == wp_unslash($pfmData['post_content'])){
        return array('error' => __('Repeat the last message!','wp-recall'));
    }

    $args = array(
        'post_content' => $pfmData['post_content'],
        'topic_id' => $pfmData['topic_id']
    );

    if(!$user_ID){
        
        if(!$pfmData['guest_email'] || !$pfmData['guest_name']){
            return array('error' => __('Error','wp-recall'));
        }

        $args['guest_email'] = $pfmData['guest_email'];
        $args['guest_name'] = $pfmData['guest_name'];
    }
    
    do_action('pfm_before_add_post', $args);

    $post_id = pfm_add_post($args);

    if(pfm_is_can('topic_close')){
        
        if(isset($pfmData['close-topic'][0]) && $pfmData['close-topic'][0]){
            
            $topicClose = pfm_topic_close($pfmData['topic_id']);
            
            if($topicClose){
                return array(
                    'url-redirect' => pfm_get_post_permalink($post_id),
                    'preloader_live' => 1
                );
            }
            
        }
        
    }

    if(isset($formdata['redirect']) && $formdata['redirect'] == 'post-url'){
        return array(
            'url-redirect' => pfm_get_post_permalink($post_id),
            'preloader_live' => 1
        );
    }

    $posts = new PrimePosts();

    $lastPosts = $posts->get_col(array(
        'topic_id' => $pfmData['topic_id'],
        'fields' => array('post_id'),
        'orderby' => 'post_id',
        'order' => 'ASC',
        'date_query' => array(
            array(
                'column' => 'post_date',
                'value' => $pfmData['form_load'],
                'compare' => '>'
            )
        )
    ));
    
    $result = array();

    if($lastPosts){
        foreach($lastPosts as $lastPost){
            $result['content'][] = pfm_get_post_box($lastPost);
        }
    }

    $result['post_id'] = $post_id;
    $result['topic_id'] = $pfmData['topic_id'];
    $result['current_url'] = pfm_get_post_permalink($post_id);
    $result['form_load'] = current_time('mysql');
    $result['append'] = '#prime-forum .prime-posts';

    return $result;
    
}