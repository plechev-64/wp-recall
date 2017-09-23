<?php

function pfm_get_form($args = false){
    
    $PrimeForm = new PrimeForm($args);
    
    return $PrimeForm->get_form();
    
}

function pfm_the_topic_form(){
    global $PrimeForum, $PrimeQuery, $user_ID;
    
    if(!$PrimeForum || !$PrimeForum->forum_id || $PrimeQuery->errors) return;
    
    if($PrimeForum->forum_closed){
        
        echo pfm_get_notice(__('The forum is closed. It is impossible to create new topics.','wp-recall'));
        
        return;
        
    }

    if(!pfm_is_can('topic_create')){
        
        echo apply_filters('pfm_notice_noaccess_topic_form', pfm_get_notice(__('You are not authorised to publish new topics in this forum','wp-recall'),'warning'));
        
        return;
        
    }
    
    echo pfm_get_form(array(
        'forum_id' => $PrimeForum->forum_id,
        'action' => 'topic_create',
        'submit' => __('Create topic','wp-recall')
    ));
    
}

function pfm_the_post_form(){
    global $PrimeTopic, $PrimeQuery, $user_ID;
    
    if(!$PrimeTopic || !$PrimeTopic->topic_id || $PrimeQuery->errors) return;
    
    if($PrimeTopic->forum_closed){
        
        echo pfm_get_notice(__('The forum is closed. It is impossible to create new topics.','wp-recall'));
        
        return;
        
    }
    
    if($PrimeTopic->topic_closed){
        
        echo pfm_get_notice(__('The topic is closed. It is prohibited to publish new topics.','wp-recall'));
        
        return;
        
    }

    if(!pfm_is_can('post_create')){
        
        echo apply_filters('pfm_notice_noaccess_post_form', pfm_get_notice(__('You are not authorised to publish messages in this topic','wp-recall'),'warning'));
        
        return;
        
    }
    
    $args = array(
        'method' => 'post_create',
        'serialize_form' => 'prime-topic-form'
    );

    $formArgs = array(
        'topic_id' => $PrimeTopic->topic_id,
        'action' => 'post_create',
        'onclick' => 'pfm_ajax_action('.json_encode($args).',this);return false;',
        'submit' => __('Add message','wp-recall')
    );

    $pageAmount = ceil($PrimeTopic->post_count/$PrimeQuery->number);
    
    if($PrimeQuery->current_page < $pageAmount){
        $formArgs['fields'][] = array(
            'type' => 'hidden',
            'slug' => 'redirect',
            'value' => 'post-url'
        );
    }
    
    echo pfm_get_form($formArgs);
    
}

add_filter('pfm_form_bottom','pfm_add_manager_fields_post_form',10,2);
function pfm_add_manager_fields_post_form($content,$action){
    global $PrimeTopic;
    
    if($action != 'post_create') return $content;
    
    if(!pfm_is_can('topic_close')) return $content;
    
    $fields = array(
        array(
            'type' => 'checkbox',
            'slug' => 'close-topic',
            'name' => 'pfm-data[close-topic]',
            'values' => array(
                1 => __('Close topic','wp-recall')
            )
        )
    );
    
    $CF = new Rcl_Custom_Fields();
    
    $content .= '<div class="post-form-manager">';
    
    foreach($fields as $field){
       
        $content .= $CF->get_input($field);
    }
    
    $content .= '</div>';
    
    return $content;

}

add_filter('pfm_form_bottom','pfm_add_smilies_post_form', 10, 2);
function pfm_add_smilies_post_form($content, $action){
    
    if(!in_array($action,array(
        'topic_create',
        'post_create',
        'post_edit',
        'topic_from_post_create'
    ))) return $content;

    $content .= rcl_get_smiles('editor-action_'.$action);
    
    return $content;
}

add_filter('pfm_form_fields','pfm_add_post_reason_edit_field', 10, 2);
function pfm_add_post_reason_edit_field($fields, $action){
    
    if($action != 'post_edit') return $fields;

    $fields[] = array(
        'type' => 'text',
        'title' => __('The reason for the edit','wp-recall'),
        'slug' => 'reason_edit',
        'maxlength' => 70
    );
    
    return $fields;
}