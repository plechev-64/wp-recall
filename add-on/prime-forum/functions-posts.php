<?php

function pfm_get_post_content($post_id){
    global $PrimePost;
    
    if($PrimePost && isset($PrimePost->post_content)){
        $content = $PrimePost->post_content;
    }else{
        $content = pfm_get_post_field($post_id,'post_content');
    }

    return apply_filters('pfm_get_post_content', $content, $post_id);
}

function pfm_the_post_content(){
    global $PrimePost,$PrimeQuery;
    
    $content = pfm_get_post_content($PrimePost->post_id);

    echo apply_filters('pfm_the_post_content',$content);
}

function pfm_post_field($field_name, $echo = 1){
    global $PrimePost;
    
    if(isset($PrimePost->$field_name)){
        if($echo)
            echo $PrimePost->$field_name;
        else
            return $PrimePost->$field_name;
    }
    
    return false;
    
}

function pfm_the_post_classes(){
    global $PrimeTopic,$PrimePost;
    
    $classes = array(
        'prime-post',
        'prime-post-index-'.$PrimePost->post_index
    );
    
    if($PrimeTopic->user_id == $PrimePost->user_id){
        $classes[] = 'topic-author';
    }
    
    if(isset($PrimePost->post_fix) && $PrimePost->post_fix){
        $classes[] = 'post-fixed';
    }
    
    if(isset($PrimePost->post_closed) && $PrimePost->post_closed){
        $classes[] = 'post-closed';
    }
    
    $classes = apply_filters('pfm_post_classes', $classes);
    
    echo implode(' ',$classes);
    
}

function pfm_get_post_edition($post_id = false){
    global $PrimePost;
    
    if(!$post_id || $post_id == $PrimePost->post_id){
        $postEdition = maybe_unserialize($PrimePost->post_edit);
    }else{
        $postEdition = maybe_unserialize(pfm_get_post_field($post_id, 'post_edit'));
    }
    
    return $postEdition;
    
}

function pfm_the_post_bottom(){
    global $PrimePost;
    echo apply_filters('pfm_the_post_bottom','');
}

add_filter('rcl_rating_user_vote', 'pfm_get_forum_post_user_vote', 10, 2);
function pfm_get_forum_post_user_vote($user_vote, $rating){
    if($rating->rating_type == 'forum-post'){
        global $PrimePost;
        if($PrimePost->post_id == $rating->object_id){
            return $PrimePost->user_vote;
        }
    }
    
    return $user_vote;
}

add_filter('pfm_posts','pfm_add_data_rating_posts');
function pfm_add_data_rating_posts($pfmPosts){
    global $user_ID;

    if(!$pfmPosts || !rcl_get_option('rating_forum-post')) return $pfmPosts;

    $userIds = array();
    $postIds = array();

    foreach($pfmPosts as $post){
        $userIds[] = $post->user_id;
        $postIds[] = $post->post_id;
    }
    
    $userIds = array_unique($userIds);

    $rating_authors = rcl_get_rating_users(array(
        'user_id__in' => $userIds
    ));

    $rating_posts = rcl_get_rating_totals(array(
        'rating_type' => 'forum-post',
        'object_id__in' => $postIds,
        'fields' => array(
            'rating_total',
            'object_id'
        )
    ));

    $rating_values = rcl_get_vote_values(array(
        'rating_type' => 'forum-post',
        'object_id__in' => $postIds,
        'fields' => array(
            'rating_value',
            'object_id',
            'user_id'
        )
    ));
    
    $ratingData = array();
    
    if($rating_authors){
        foreach($rating_authors as $rating){
            $ratingData['authors'][$rating->user_id] = $rating->rating_total;
        }
    }

    if($rating_posts){
        foreach($rating_posts as $rating){
            $ratingData['posts'][$rating->object_id] = $rating->rating_total;
        }
    }
    
    $user_votes = array();

    if($rating_values){
        foreach($rating_values as $rating){
            
            if($rating->user_id == $user_ID){
                $user_votes[$rating->object_id] = $rating->rating_value;
            }
            
            if(!isset($ratingData['values'][$rating->object_id])) 
                $ratingData['values'][$rating->object_id] = 0;
            
            if($rating->rating_value > 0){
                $ratingData['values'][$rating->object_id] += 1;
            }else{
                $ratingData['values'][$rating->object_id] -= 1;
            }
            
        }
    }

    foreach($pfmPosts as $k => $post){
        $pfmPosts[$k]->rating_author = (isset($ratingData['authors'][$post->user_id]))? $ratingData['authors'][$post->user_id]: 0;
        $pfmPosts[$k]->user_vote = (isset($user_votes[$post->post_id]))? $user_votes[$post->post_id]: 0;
        $pfmPosts[$k]->rating_total = (isset($ratingData['posts'][$post->post_id]))? $ratingData['posts'][$post->post_id]: 0;
        $pfmPosts[$k]->rating_votes = (isset($ratingData['values'][$post->post_id]))? $ratingData['values'][$post->post_id]: 0;
    }
    
    
    return $pfmPosts;
}

add_filter('pfm_the_post_bottom','pfm_add_rating_post',10,2);
function pfm_add_rating_post($content){
    global $PrimePost;
    
    if(function_exists('rcl_get_html_post_rating')) 
        $content .= rcl_get_html_post_rating($PrimePost->post_id,'forum-post',$PrimePost->user_id);
    
    return $content;
}

add_action('pfm_pre_delete_post','pfm_update_post_author_count',10);
add_action('pfm_add_post','pfm_update_post_author_count',10);
function pfm_update_post_author_count($post_id){
    
    $post = pfm_get_post($post_id);
    
    if(!$post) return false;
    
    $Posts = new PrimePosts();
    
    $postCount = $Posts->count(array('user_id' => $post->user_id));
    
    pfm_update_author_meta($post->user_id,'post_count',$postCount);

}

add_action('pfm_add_post','pfm_send_mail_topic_author',10);
function pfm_send_mail_topic_author($post_id){
    
    if(!pfm_get_option('author-notes')) return false;
    
    $post = pfm_get_post($post_id);    
    $topic = pfm_get_topic($post->topic_id);

    //Если автор топика отвечает сам, то не шлем письмо иначе шлем
    if($topic->user_id == $post->user_id) return false;
    
    $actionData = pfm_get_visitors_data(array(
        'user_id' => $topic->user_id,
        'topic_id' => $post->topic_id
    ),1);
    
    if($actionData) return false;
    
    $title = 'Новый комментарий к вашей теме';
    $to = get_the_author_meta('user_email',$topic->user_id);
    $mess = '
    <p>Добавлен новый ответ к вашей теме "'.$topic->topic_name.'".</p>
    <div style="float:left;margin-right:15px;">'.get_avatar($post->user_id,60).'</div>
    <p><b>ответил:</b></p>
    <p>'.$post->post_content.'</p>
    <p><a href="'.pfm_get_post_permalink($post->post_id).'">Ответить на комментарий</a></p>';

    rcl_mail($to, $title, $mess);

}

add_action('pfm_delete_post','pfm_delete_post_metas',10);
function pfm_delete_post_metas($post_id){
    global $wpdb;
    return $wpdb->query("DELETE FROM ".RCL_PREF."pforum_meta WHERE object_type='post' AND object_id='$post_id'");
}

function pfm_get_post_box($post_id){
    global $PrimeShorts,$PrimePost,$PrimeUser;
    
    $post = pfm_get_post($post_id);
    
    $PrimeUser = new PrimeUser();
    $PrimeShorts = pfm_get_shortcodes();   

    $theme = rcl_get_addon(get_option('rcl_pforum_template'));

    $PrimePost = array(
        'post_id' => $post_id,
        'user_id' => $post->user_id,
        'post_content' => $post->post_content,
        'post_index' => $post->post_index,
        'post_date' => $post->post_date,
        'display_name' => $post->user_id? get_the_author_meta('display_name',$post->user_id): $post->guest_name,
        'guest_name' => $post->guest_name,
        'guest_email' => $post->guest_email,
        'user_registered' => $post->user_id? get_the_author_meta('user_registered',$post->user_id): ''
    );

    $PrimePost = (object)$PrimePost;

    $content = rcl_get_include_template('pfm-single-post.php',$theme['path']);
    
    return $content;
}

function pfm_the_author_name(){
    global $PrimePost;
    echo $PrimePost->user_id? pfm_get_user_name($PrimePost->user_id): $PrimePost->guest_name;
}

function pfm_author_avatar($size = 50){
    global $PrimePost;
    $data = ($PrimePost->user_id)? $PrimePost->user_id: $PrimePost->guest_email;
    echo get_avatar($data, $size);
}

add_action('pfm_post_author_metabox','pfm_add_author_action_status',10);
function pfm_add_author_action_status(){
    global $PrimePost,$PrimeUser; 
    if(!$PrimePost->user_id) return false; ?>
    <div class="prime-author-meta prime-author-status"><?php echo rcl_get_useraction_html($PrimePost->user_id,2); ?></div>
<?php }

add_action('pfm_post_author_metabox','pfm_add_author_registered_data',12);
function pfm_add_author_registered_data(){
    global $PrimePost; 
    if(!$PrimePost->user_id) return false; 
    $user_registered = ($date = pfm_get_user_data($PrimePost->user_id,'user_registered'))? $date: get_the_author_meta('user_registered',$PrimePost->user_id); ?>
    <div class="prime-author-meta prime-author-register"><?php echo __('On the website since','wp-recall').' '.mysql2date('d.m.Y', $user_registered); ?></div>
<?php }

add_action('pfm_post_author_metabox','pfm_add_author_role_meta',14);
function pfm_add_author_role_meta(){
    global $PrimePost,$PrimeUser; ?>
    <div class="prime-author-meta prime-author-role"><?php echo $PrimeUser->get_user_rolename($PrimePost->user_id); ?></div>
<?php }

add_action('pfm_post_author_metabox','pfm_add_author_counters',20);
function pfm_add_author_counters(){
    global $PrimePost; 
    if(!$PrimePost->user_id) return false; 
    
    if($tcount = pfm_get_author_meta($PrimePost->user_id,'topic_count')){ ?>
    <div class="prime-author-meta prime-author-topics">
        <span><?php echo __('Topics','wp-recall'); ?></span>
        <span><?php echo $tcount; ?></span>
    </div>
    <?php }
    if($pcount = pfm_get_author_meta($PrimePost->user_id,'post_count')){ ?>
    <div class="prime-author-meta prime-author-posts">
        <span><?php echo __('Messages','wp-recall'); ?></span>
        <span><?php echo $pcount; ?></span>
    </div>
<?php }
}
