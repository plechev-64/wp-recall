<?php

function pfm_the_topic_name(){
    global $PrimeTopic;
    echo $PrimeTopic->topic_name;
}

function pfm_get_topic_name($topic_id){
    global $PrimeTopic;
    
    if($PrimeTopic && $PrimeTopic->topic_id == $topic_id){
        return $PrimeTopic->topic_name;
    }
    
    return pfm_get_topic_field($topic_id,'topic_name');
}

function pfm_the_post_count(){
    global $PrimeTopic;
    echo $PrimeTopic->post_count;
}

function pfm_time_diff_last_post(){
    global $PrimeTopic;
    echo human_time_diff( strtotime($PrimeTopic->last_post_date), current_time('timestamp') );
}

function pfm_topic_field($field_name, $echo = 1){
    global $PrimeTopic;
    
    if(isset($PrimeTopic->$field_name)){
        if($echo)
            echo $PrimeTopic->$field_name;
        else
            return $PrimeTopic->$field_name;
    }
    
    return false;
    
}

function pfm_the_topic_classes(){
    global $PrimeTopic;
    
    $classes = array(
        'prime-topic',
        'prime-topic-'.$PrimeTopic->topic_id
    );
    
    if($PrimeTopic->topic_fix){
        $classes[] = 'topic-fixed';
    }
    
    if($PrimeTopic->topic_closed){
        $classes[] = 'topic-closed';
    }
    
    $classes = apply_filters('pfm_topic_classes', $classes);
    
    echo implode(' ',$classes);
    
}

function pfm_get_topic_meta_box($topic_id){
    
    $forum_id = pfm_get_topic_field($topic_id,'forum_id');
    
    $group_id = pfm_get_forum_field($forum_id,'group_id');
    
    $fields = get_option('rcl_fields_pfm_group_'.$group_id);
    
    if(!$fields) return false;
    
    $CF = new Rcl_Custom_Fields();
    
    $content = '';
    
    foreach($fields as $field){

        $value = pfm_get_topic_meta($topic_id,$field['slug']);
        
        $content .= $CF->get_field_value($field,$value);
    }
    
    if(!$content) return false;
    
    $content = '<div class="prime-topic-metabox">' . $content . '</div>';
    
    return $content;
    
}

function pfm_the_last_post_url(){
    global $PrimeTopic;
    echo pfm_get_post_permalink($PrimeTopic->last_post_id);
}

function pfm_update_topic_custom_fields($topic_id){

    require_once(ABSPATH . "wp-admin" . '/includes/image.php');
    require_once(ABSPATH . "wp-admin" . '/includes/file.php');
    require_once(ABSPATH . "wp-admin" . '/includes/media.php');

    $forum_id = pfm_get_topic_field($topic_id,'forum_id');
    
    $group_id = pfm_get_forum_field($forum_id,'group_id');

    $fields = get_option('rcl_fields_pfm_group_'.$group_id);
    
    if(!$fields) return false;

    if($fields){

        $POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);

        foreach($fields as $field){
            
            $slug = $field['slug'];
            $value = isset($POST[$slug])? $POST[$slug]: false;

            if($field['type']=='checkbox'){
                $vals = array();

                $count_field = count($field['values']);
                
                if($value && is_array($value)){
                    foreach($value as $val){
                        for($a=0;$a<$count_field;$a++){
                            if($field['values'][$a]==$val){
                                $vals[] = $val;
                            }
                        }
                    }
                }
                
                if($vals){
                    pfm_update_topic_meta($topic_id, $slug, $vals);
                }else{
                    pfm_delete_topic_meta($topic_id, $slug);
                }

            }else if($field['type']=='file'){

                $attach_id = rcl_upload_meta_file($field,$topic->user_id,0);
                
                if($attach_id) 
                    pfm_update_topic_meta($topic_id, $slug, $attach_id);

            }else{

                if($value){
                    pfm_update_topic_meta($topic_id, $slug, $value);
                }else{
                    if(pfm_get_topic_meta($topic_id, $slug, 1)) 
                            pfm_delete_topic_meta($topic_id, $slug);
                }

            }
        }
    }
}

add_action('pfm_add_topic','pfm_send_admin_mail_new_topic',10);
function pfm_send_admin_mail_new_topic($topic_id){
    global $user_ID;
    
    if(!pfm_get_option('admin-notes') || rcl_is_user_role($user_ID, 'administrator')) return false;

    $topic = pfm_get_topic($topic_id);
    
    if(!$topic) return false;
    
    $email = get_option('admin_email');
    $subject = __('New forum topic','wp-recall');
    
    $textmail = '<p>'.sprintf(__('On the forum of the site "%s" created a new topic!','wp-recall'),get_bloginfo('name')).'</p>';
    $textmail .= '<p>'.__('The name of the topic','wp-recall').': <a href="'.pfm_get_topic_permalink($topic_id).'">'.$topic->topic_name.'</a>'.'</p>';
    $textmail .= '<p>'.__('The topic author','wp-recall').': '.( $topic->user_id? get_the_author_meta('display_name',$topic->user_id): __('Guest','wp-recall') ).'</p>';
    
    rcl_mail($email, $subject, $textmail);
    
}

add_action('pfm_add_topic','pfm_add_topic_form_custom_meta',10);
add_action('pfm_update_topic','pfm_add_topic_form_custom_meta',10);
function pfm_add_topic_form_custom_meta($topic_id){
    
    $topic = pfm_get_topic($topic_id);
    
    if(!$topic) return false;
    
    pfm_update_topic_custom_fields($topic_id);

}

add_action('pfm_delete_topic','pfm_delete_topic_form_custom_meta',10);
function pfm_delete_topic_form_custom_meta($topic_id){
    
    $metas = pfm_get_metas(array(
        'object_id' => $topic_id,
        'object_type' => 'topic',
        'fields' => array(
            'meta_key'
        )
    ));
    
    if(!$metas) return false;
    
    foreach($metas as $meta){
        pfm_delete_topic_meta($topic_id,$meta->meta_key);
    }
    
}

add_action('pfm_add_topic','pfm_update_topic_count',10);
function pfm_update_topic_count($topic_id){
    
    $topic = pfm_get_topic($topic_id);
    
    if(!$topic) return false;
    
    pfm_update_forum_counter($topic->forum_id);

}

add_action('pfm_pre_delete_topic','pfm_update_topic_author_count',10);
add_action('pfm_add_topic','pfm_update_topic_author_count',10);
function pfm_update_topic_author_count($topic_id){
    
    $topic = pfm_get_topic($topic_id);
    
    if(!$topic) return false;
    
    $Topics = new PrimeTopics();
    
    $topicCount = $Topics->count(array('user_id' => $topic->user_id));
    
    pfm_update_author_meta($topic->user_id,'topic_count',$topicCount);

}