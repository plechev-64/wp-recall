<?php

function pfm_get_form($args = false){
    
    $PrimeForm = new PrimeForm($args);
    
    return $PrimeForm->get_form();
    
}

function pfm_the_topic_form(){
    global $PrimeForum,$user_ID;
    
    if(!$PrimeForum || !$PrimeForum->forum_id) return;
    
    if($PrimeForum->forum_closed){
        
        echo pfm_get_notice(__('Форум закрыт. Создание новых тем невозможно.'));
        
        return;
        
    }

    if(!pfm_is_can('topic_create')){
        
        echo apply_filters('pfm_notice_noaccess_topic_form', pfm_get_notice(__('Вы не имеете права на публикацию новых тем на этом форуме'),'warning'));
        
        return;
        
    }
    
    echo pfm_get_form(array(
        'forum_id' => $PrimeForum->forum_id,
        'action' => 'topic_create',
        'submit' => __('Создать тему')
    ));
    
}

function pfm_the_post_form(){
    global $PrimeTopic,$user_ID;
    
    if(!$PrimeTopic || !$PrimeTopic->topic_id) return;
    
    if($PrimeTopic->forum_closed){
        
        echo pfm_get_notice(__('Форум закрыт. Создание новых тем невозможно.'));
        
        return;
        
    }
    
    if($PrimeTopic->topic_closed){
        
        echo pfm_get_notice(__('Тема закрыта. Публикация новых сообщений запрещена.'));
        
        return;
        
    }

    if(!pfm_is_can('post_create')){
        
        echo apply_filters('pfm_notice_noaccess_post_form', pfm_get_notice(__('Вы не имеете права на публикацию сообщений в этой теме'),'warning'));
        
        return;
        
    }
    
    echo pfm_get_form(array(
        'topic_id' => $PrimeTopic->topic_id,
        'action' => 'post_create',
        'submit' => __('Добавить сообщение')
    ));
    
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
                1 => __('Закрыть тему')
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

add_filter('pfm_form_bottom','pfm_add_smilies_post_form',10);
function pfm_add_smilies_post_form($content){
    global $PrimePost,$PrimeTopic;
    
    if(!$PrimePost && !$PrimeTopic) return $content;
    
    $content .= rcl_get_smiles('editor-action_post_create');
    
    return $content;
}