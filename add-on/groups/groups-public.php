<?php

add_action('update_post_rcl','rcl_update_grouppost_meta',10,3);
function rcl_update_grouppost_meta($post_id,$postdata,$action){

    if($postdata['post_type']!='post-group') return false;

    if(isset($_POST['term_id'])) $term_id = intval(base64_decode($_POST['term_id']));

    if(isset($term_id)) wp_set_object_terms( $post_id, (int)$term_id, 'groups' );

    $gr_tag = sanitize_text_field($_POST['group-tag']);
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

//Получаем ИД группы которой принадлежит публикация
function rcl_get_group_id_by_post($post_id){
    $groups = get_the_terms( $post_id, 'groups' );
    if(!$groups) return false;
    foreach($groups as $group){
        if($group->parent!=0) continue;
        return $group->term_id;
    }

    return false;
}

//Получаем данные группы которой принадлежит публикация
function rcl_get_group_by_post($post_id){
    $groups = get_the_terms( $post_id, 'groups' );
    if(!$groups) return false;
    foreach($groups as $group){
        if($group->parent!=0) continue;
        return $group;
    }

    return false;
}

//Проверяем возможность пользователя редактировать публикации группы
function rcl_can_user_edit_post_group($post_id){
    global $user_ID;
    $group_id = rcl_get_group_id_by_post($post_id);
    if(!$group_id) return false;

    if(current_user_can('edit_post', $post_id)) return true;

    $rcl_group = rcl_get_group($group_id);
    if($rcl_group->admin_id==$user_ID) return true;

    if(rcl_get_group_user_status($user_ID,$group_id)=='moderator') return true;

    return false;
}

add_filter('pre_update_postdata_rcl','rcl_publicdata_group',10,2);
function rcl_publicdata_group($postdata,$data){
    global $rcl_options,$user_ID;
    if($data->post_type!='post-group') return $postdata;

    if($rcl_options['moderation_public_group']==1) $post_status = 'pending';
    else $post_status = 'publish';

    if($rcl_options['nomoder_rayt']){
            $all_r = rcl_get_all_rating_user(0,$user_ID);
            if($all_r >= $rcl_options['nomoder_rayt']) $post_status = 'publish';
    }
    $postdata['post_status'] = $post_status;

    return $postdata;

}

function rcl_post_group_edit_button($content){
	global $post,$user_ID,$rcl_group,$rcl_options;
	if(!is_tax('groups')) return $content;

	if(rcl_is_group_can('moderator')){
            $edit_url = rcl_format_url(get_permalink($rcl_options['public_form_page_rcl']));
            $content = '<p class="post-edit-button">'
                . '<a title="'.__('Edit','wp-recall').'" object-id="none" href="'. $edit_url.'rcl-post-edit='.$post->ID .'">'
                    . '<i class="fa fa-pencil-square-o"></i>'
                . '</a>'
            . '</p>'.$content;
	}
	return $content;
}
add_filter('the_content','rcl_post_group_edit_button',999);
add_filter('the_excerpt','rcl_post_group_edit_button',999);

