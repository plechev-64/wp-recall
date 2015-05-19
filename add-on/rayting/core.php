<?php
//Вносим общий рейтинг публикации в БД
function rcl_insert_total_post_rating($post_id,$user_id,$point=0){
    global $wpdb;
    $wpdb->insert(
        RCL_PREF.'total_rayting_posts',
        array( 'author_id' => $user_id, 'post_id' => $post_id, 'total' => $point )
    );
}
//Вносим общий рейтинг комментария в БД
function rcl_insert_total_comment_rating($comment_id,$user_id,$point=0){
    global $wpdb;
    $wpdb->insert(
        RCL_PREF.'total_rayting_comments',
        array( 'author_id' => $user_id, 'comment_id' => $comment_id, 'total' => $point )
    );
}
//Вносим общий рейтинг пользователя в БД
add_action('user_register','rcl_insert_user_rating');
function rcl_insert_user_rating($user_id,$point=0){
    global $wpdb;
    $wpdb->insert(
        RCL_PREF.'total_rayting_users',
        array( 'user_id' => $user_id, 'total' => $point )
    );
}
//добавляем голос пользователя к публикации
function rcl_insert_post_rating($post_id,$user_id,$point){
    global $wpdb;
    $post = get_post($post_id);

    $wpdb->insert(
	RCL_PREF.'rayting_post',
	array( 'user' => $user_id, 'post' => $post->ID, 'author_post' => $post->post_author, 'status' => $point )
    );

    do_action('rcl_insert_post_rating',$post_id,$point);
}
//добавляем голос пользователя к комментарию
function rcl_insert_comment_rating($comment_id,$user_id,$point){
    global $wpdb;
    $comment = get_comment($comment_id);

    $wpdb->insert(
	RCL_PREF.'rayting_comments',
	array(
            'user' => $user_id,
            'comment_id' => $comment->comment_ID,
            'author_com' => $comment->user_id,
            'rayting' => $point,
            'time_action' => current_time('mysql')
        )
    );

    do_action('rcl_insert_comment_rating',$comment_id,$point);
}
//Получаем значение голоса пользователя к публикации
function rcl_get_post_rating($post_id,$user_id){
    global $wpdb;
    return $wpdb->get_var($wpdb->prepare("SELECT status FROM ".RCL_PREF."rayting_post WHERE post = '%d' AND user = '%d'",$post_id,$user_id));
}
function rcl_get_comment_rating($comment_id,$user_id){
    global $wpdb;
    return $wpdb->get_var($wpdb->prepare("SELECT rayting FROM ".RCL_PREF. "rayting_comments WHERE comment_id = '%d' AND user = '%d'",$comment_id,$user_id));
}
//Получаем значение рейтинга пользователя
function rcl_get_user_rating($user_id){
    global $wpdb;
    return $wpdb->get_var("SELECT total FROM ".RCL_PREF."total_rayting_users WHERE user_id = '$user_id'");
}
//Получаем значение рейтинга комментария
function rcl_get_total_comment_rating($comment_id){
    global $wpdb;
    return $wpdb->get_var($wpdb->prepare("SELECT total FROM ".RCL_PREF."total_rayting_comments WHERE comment_id = '%d'",$comment_id));
}
//Получаем значение рейтинга публикации
function rcl_get_total_post_rating($id_post){
    global $wpdb;
    return $wpdb->get_var($wpdb->prepare("SELECT total FROM ".RCL_PREF."total_rayting_posts WHERE post_id = '%d'",$id_post));
}

//Обновляем общий рейтинг публикации
add_action('rcl_delete_post_rating','rcl_update_total_post_rating',10,2);
add_action('rcl_insert_post_rating','rcl_update_total_post_rating',10,2);
function rcl_update_total_post_rating($post_id,$point){
    global $wpdb,$rcl_options;

    $total = rcl_get_total_post_rating($post_id);
    $post = get_post($post_id);

    if(isset($total)){
        $total += $point;
        $wpdb->update(
                RCL_PREF.'total_rayting_posts',
                array('total'=>$total),
                array('post_id'=>$post_id,'author_id' => $post->post_author)
        );

    }else{
        rcl_insert_total_post_rating($post_id,$post->post_author,$point);
        $total = $point;
    }

    do_action('rcl_update_total_post_rating',$post_id,$post->post_author,$point);

    return $total;
}
//Обновляем общий рейтинг комментария
//comment_id - идентификатор комментария
//user_id - автор комментария
add_action('rcl_delete_comment_rating','rcl_update_total_comment_rating',10,2);
add_action('rcl_insert_comment_rating','rcl_update_total_comment_rating',10,2);
function rcl_update_total_comment_rating($comment_id,$point){
    global $wpdb,$rcl_options;

    $total = rcl_get_total_comment_rating($comment_id);
    $comment = get_comment($comment_id);

    if(isset($total)){
        $total += $point;
        $wpdb->update(
                RCL_PREF.'total_rayting_comments',
                array('total'=>$total),
                array('comment_id'=>$comment_id,'author_id' => $comment->user_id)
        );

    }else{
        rcl_insert_total_comment_rating($comment_id,$comment->user_id,$point);
        $total = $point;
    }

    do_action('rcl_update_total_comment_rating',$comment_id,$comment->user_id,$point);

    return $total;

}
//Определяем изменять ли рейтинг пользователю
add_action('rcl_update_total_post_rating','rcl_post_update_user_rating',10,3);
add_action('rcl_delete_rating_with_post','rcl_post_update_user_rating',10,3);
function rcl_post_update_user_rating($public_id,$user_id,$point){
    global $rcl_options;
    $post_type = get_post_type($public_id);
    $rcl_options['rayt_products'] = 1;
    if($rcl_options['rayt_'.$post_type]==1) rcl_update_user_rating($user_id,$point,$public_id);
}
//Определяем изменять ли рейтинг пользователю
add_action('rcl_update_total_comment_rating','rcl_comment_update_user_rating',10,3);
add_action('rcl_delete_rating_with_comment','rcl_comment_update_user_rating',10,3);
//add_action('rcl_delete_comment_rating','rcl_comment_update_user_rating',10,3);
function rcl_comment_update_user_rating($public_id,$user_id,$point){
    global $rcl_options;
    if($rcl_options['rayt_comment']==1) rcl_update_user_rating($user_id,$point,$public_id);
}
//Обновляем общий рейтинг пользователя
function rcl_update_user_rating($user_id,$point,$public_id=false){
    global $wpdb;

    $total = rcl_get_user_rating($user_id);

    if(isset($total)){
        $total += (int)$point;
        $wpdb->update(
                RCL_PREF.'total_rayting_users',
                array('total'=>$total),
                array('user_id' => $user_id)
        );
    }else{
        rcl_insert_user_rating($user_id,$point);
    }

    do_action('rcl_update_user_rating',$user_id,$point,$public_id);

}

//Удаляем из БД всю информацию об активности пользователя на сайте
//Корректируем рейтинг других пользователей
function rcl_delete_ratingdata_user($user){
	global  $wpdb;
        $datas = array();

        $r_comments = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".RCL_PREF."rayting_comments WHERE user = '%d'",$user));

        if($r_comments){
            foreach($r_comments as $r_comment){
                //$datas[$r_comment->author_com]['user'][$user] += $r_comment->rayting;
                $datas[$r_comment->author_com]['comment'][$r_comment->comment_id] += $r_comment->rayting;
            }
        }

        $r_posts = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".RCL_PREF."rayting_post WHERE user = '%d'",$user));

        if($r_posts){
            foreach($r_posts as $r_post){
                //$datas[$r_post->author_post]['user'][$user] += $r_post->status;
                $datas[$r_post->author_post]['post'][$r_post->post] += $r_post->status;
            }
        }

        if($datas){
            foreach($datas as $user_id=>$val){
                foreach($val as $type=>$data){
                    foreach($data as $id=>$rayt){
                        $rayt = -1*$rayt;
                        if($type=='comment'){
                            rcl_update_total_comment_rating($id,$user_id,$rayt);
                        }
                        if($type=='post'){
                            rcl_update_total_post_rating($id,$user_id,$rayt);
                        }
                        /*if($type=='user'){
                            update_raytuser_rcl($user_id,$rayt);
                        }*/
                    }
                }
            }
        }

        $wpdb->query($wpdb->prepare("DELETE FROM ".RCL_PREF."rayting_comments WHERE user = '%d'",$user));
        $wpdb->query($wpdb->prepare("DELETE FROM ".RCL_PREF."rayting_post WHERE user = '%d'",$user));

	$wpdb->query($wpdb->prepare("DELETE FROM ".RCL_PREF."rayting_comments WHERE author_com = '%d'",$user));
	$wpdb->query($wpdb->prepare("DELETE FROM ".RCL_PREF."rayting_post WHERE author_post = '%d'",$user));
	$wpdb->query($wpdb->prepare("DELETE FROM ".RCL_PREF."total_rayting_comments WHERE author_id = '%d'",$user));
	$wpdb->query($wpdb->prepare("DELETE FROM ".RCL_PREF."total_rayting_posts WHERE author_id = '%d'",$user));
	$wpdb->query($wpdb->prepare("DELETE FROM ".RCL_PREF."total_rayting_users WHERE user_id = '%d'",$user));
}
add_action('delete_user','rcl_delete_ratingdata_user');

//Удаляем голос пользователя у комментария
function rcl_delete_comment_rating($comment_id,$user_id,$point){
    global $wpdb;
    $wpdb->query($wpdb->prepare("DELETE FROM ".RCL_PREF."rayting_comments WHERE comment_id = '%d' AND user='%d'",$comment_id,$user_id));
    $point = -1*$point;
    do_action('rcl_delete_comment_rating',$comment_id,$point);
}
//Удаляем голос пользователя за публикацию
function rcl_delete_post_rating($post_id,$user_id,$point){
    global $wpdb;
    $wpdb->query($wpdb->prepare("DELETE FROM ".RCL_PREF."rayting_post WHERE post = '%d' AND user='%d'",$post_id,$user_id));
    $point = -1*$point;
    do_action('rcl_delete_post_rating',$post_id,$point);
}
//Удаляем данные рейтинга публикации
add_action('delete_post', 'rcl_delete_rating_with_post');
function rcl_delete_rating_with_post($postid){
    global  $wpdb;
    $data_p = get_post($postid);
    $point = rcl_get_total_post_rating($postid);

    $wpdb->query($wpdb->prepare("DELETE FROM ".RCL_PREF."rayting_post WHERE post = '%d'",$postid));
    $wpdb->query($wpdb->prepare("DELETE FROM ".RCL_PREF."total_rayting_posts WHERE post_id = '%d'",$postid));

    $point = -1*$point;

    do_action('rcl_delete_rating_with_post',$postid,$data_p->post_author,$point);
}
//Удаляем данные рейтинга комментария
add_action('delete_comment', 'rcl_delete_rating_with_comment');
function rcl_delete_rating_with_comment($comment_id){
    global  $wpdb;
    $data_c = get_comment($comment_id);
    $point = rcl_get_total_comment_rating($comment_id);

    $wpdb->query($wpdb->prepare("DELETE FROM ".RCL_PREF."rayting_comments WHERE comment_id = '%d'",$comment_id));
    $wpdb->query($wpdb->prepare("DELETE FROM ".RCL_PREF."total_rayting_comments WHERE comment_id = '%d'",$comment_id));

    $point = -1*$point;

    do_action('rcl_delete_rating_with_comment',$comment_id,$data_c->user_id,$point);
}