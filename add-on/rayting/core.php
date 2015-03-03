<?php
//Вносим общий рейтинг публикации в БД
function insert_post_total_rayting($post_id,$user_id,$point=0){
    global $wpdb;
    $wpdb->insert(  
        RCL_PREF.'total_rayting_posts',  
        array( 'author_id' => $user_id, 'post_id' => $post_id, 'total' => $point )
    );
}
//Вносим общий рейтинг комментария в БД
function insert_comment_total_rayting($comment_id,$user_id,$point=0){
    global $wpdb;
    $wpdb->insert(  
        RCL_PREF.'total_rayting_comments',  
        array( 'author_id' => $user_id, 'comment_id' => $comment_id, 'total' => $point )
    );
}
//Вносим общий рейтинг пользователя в БД
add_action('user_register','insert_user_rayting');
function insert_user_rayting($user_id,$point=0){
    global $wpdb;
    $wpdb->insert(  
        RCL_PREF.'total_rayting_users',  
        array( 'user_id' => $user_id, 'total' => $point )
    );
}
//добавляем голос пользователя к публикации
function insert_post_rayting($post_id,$user_id,$point){
    global $wpdb;
    $post = get_post($post_id);

    $wpdb->insert(  
	RCL_PREF.'rayting_post',  
	array( 'user' => $user_id, 'post' => $post->ID, 'author_post' => $post->post_author, 'status' => $point )
    );
    
    do_action('insert_post_rayting',$post_id,$point);
}
//добавляем голос пользователя к комментарию
function insert_comment_rayting($comment_id,$user_id,$point){
    global $wpdb;
    $comment = get_comment($comment_id);

    $wpdb->insert(  
	RCL_PREF.'rayting_comments',  
	array( 
            'user' => $user_id, 
            'comment_id' => $comment->comment_ID, 
            'author_com' => $comment->user_id, 
            'rayting' => $point, 
            'time_action' => date("Y-m-d H:i:s")
        )
    );
    
    do_action('insert_comment_rayting',$comment_id,$point);
}
//Получаем значение голоса пользователя к публикации
function get_post_rayting($post_id,$user_id){
    global $wpdb;
    return $wpdb->get_var("SELECT status FROM ".RCL_PREF."rayting_post WHERE post = '$post_id' AND user = '$user_id'");
}
function get_comment_rayting($comment_id,$user_id){
    global $wpdb;
    return $wpdb->get_var("SELECT rayting FROM ".RCL_PREF. "rayting_comments WHERE comment_id = '$comment_id' AND user = '$user_id'");
}
//Получаем значение рейтинга пользователя
function get_user_rayting($user_id){
    global $wpdb;
    return $wpdb->get_var("SELECT total FROM ".RCL_PREF."total_rayting_users WHERE user_id = '$user_id'");
}
//Получаем значение рейтинга комментария
function get_comment_total_rayting($comment_id){
    global $wpdb;   
    return $wpdb->get_var("SELECT total FROM ".RCL_PREF."total_rayting_comments WHERE comment_id = '".$comment_id."'");
}
//Получаем значение рейтинга публикации
function get_post_total_rayting($id_post){
    global $wpdb;
    return $wpdb->get_var("SELECT total FROM ".RCL_PREF."total_rayting_posts WHERE post_id = '$id_post'");
}

//Обновляем общий рейтинг публикации
add_action('delete_post_rayting','update_post_total_rayting',10,2);
add_action('insert_post_rayting','update_post_total_rayting',10,2);
function update_post_total_rayting($post_id,$point){
    global $wpdb,$rcl_options;

    $total = get_post_total_rayting($post_id);
    $post = get_post($post_id);

    if(isset($total)){
        $total += $point;
        $wpdb->update(  
                RCL_PREF.'total_rayting_posts',  
                array('total'=>$total),
                array('post_id'=>$post_id,'author_id' => $post->post_author)
        );

    }else{
        insert_post_total_rayting($post_id,$post->post_author,$point);
        $total = $point;
    }

    do_action('update_post_total_rayting',$post_id,$post->post_author,$point); 
    
    return $total;
}
//Обновляем общий рейтинг комментария
//comment_id - идентификатор комментария
//user_id - автор комментария
add_action('delete_comment_rayting','update_comment_total_rayting',10,2);
add_action('insert_comment_rayting','update_comment_total_rayting',10,2);
function update_comment_total_rayting($comment_id,$point){
    global $wpdb,$rcl_options;

    $total = get_comment_total_rayting($comment_id);
    $comment = get_comment($comment_id);

    if(isset($total)){
        $total += $point;
        $wpdb->update(  
                RCL_PREF.'total_rayting_comments',  
                array('total'=>$total),
                array('comment_id'=>$comment_id,'author_id' => $comment->user_id)
        );

    }else{
        insert_comment_total_rayting($comment_id,$comment->user_id,$point);
        $total = $point;
    }
    
    do_action('update_comment_total_rayting',$comment_id,$comment->user_id,$point);  
    
    return $total;

}
//Определяем изменять ли рейтинг пользователю
add_action('update_post_total_rayting','post_update_user_rayting',10,3);
add_action('delete_rayting_with_post','post_update_user_rayting',10,3);
function post_update_user_rayting($public_id,$user_id,$point){
    global $rcl_options;
    $post_type = get_post_type($public_id);
    $rcl_options['rayt_products'] = 1;       
    if($rcl_options['rayt_'.$post_type]==1) update_user_rayting($user_id,$point,$public_id);
}
//Определяем изменять ли рейтинг пользователю
add_action('update_comment_total_rayting','comment_update_user_rayting',10,3);
add_action('delete_rayting_with_comment','comment_update_user_rayting',10,3);
//add_action('delete_comment_rayting','comment_update_user_rayting',10,3);
function comment_update_user_rayting($public_id,$user_id,$point){
    global $rcl_options;     
    if($rcl_options['rayt_comment']==1) update_user_rayting($user_id,$point,$public_id);
}
//Обновляем общий рейтинг пользователя
function update_user_rayting($user_id,$point,$public_id=false){
    global $wpdb;

    $total = get_user_rayting($user_id);

    if(isset($total)){
        $total += (int)$point;
        $wpdb->update(  
                RCL_PREF.'total_rayting_users',  
                array('total'=>$total),
                array('user_id' => $user_id)
        );	
    }else{
        insert_user_rayting($user_id,$point);		
    }
    
    do_action('update_user_rayting',$user_id,$point,$public_id); 
    
}

//Удаляем из БД всю информацию об активности пользователя на сайте
//Корректируем рейтинг других пользователей
function delete_all_rayt_user_rcl($user){
	global  $wpdb;
        $datas = array();
        
        $r_comments = $wpdb->get_results("SELECT * FROM ".RCL_PREF."rayting_comments WHERE user = '$user'" );

        if($r_comments){
            foreach($r_comments as $r_comment){
                //$datas[$r_comment->author_com]['user'][$user] += $r_comment->rayting;
                $datas[$r_comment->author_com]['comment'][$r_comment->comment_id] += $r_comment->rayting;
            }
        }
        
        $r_posts = $wpdb->get_results("SELECT * FROM ".RCL_PREF."rayting_post WHERE user = '$user'" );
        
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
                            update_comment_total_rayting($id,$user_id,$rayt);
                        }
                        if($type=='post'){ 
                            update_post_total_rayting($id,$user_id,$rayt);
                        }
                        /*if($type=='user'){ 
                            update_raytuser_rcl($user_id,$rayt);
                        }*/
                    }
                }
            }
        }

        $wpdb->query("DELETE FROM ".RCL_PREF."rayting_comments WHERE user = '$user'");
        $wpdb->query("DELETE FROM ".RCL_PREF."rayting_post WHERE user = '$user'");
        
	$wpdb->query("DELETE FROM ".RCL_PREF."rayting_comments WHERE author_com = '$user'");
	$wpdb->query("DELETE FROM ".RCL_PREF."rayting_post WHERE author_post = '$user'");
	$wpdb->query("DELETE FROM ".RCL_PREF."total_rayting_comments WHERE author_id = '$user'");
	$wpdb->query("DELETE FROM ".RCL_PREF."total_rayting_posts WHERE author_id = '$user'");
	$wpdb->query("DELETE FROM ".RCL_PREF."total_rayting_users WHERE user_id = '$user'");
}
add_action('delete_user','delete_all_rayt_user_rcl');

//Удаляем голос пользователя у комментария
function delete_comment_rayting($comment_id,$user_id,$point){
    global $wpdb;
    $wpdb->query("DELETE FROM ".RCL_PREF."rayting_comments WHERE comment_id = '$comment_id' AND user='$user_id'");
    $point = -1*$point;
    do_action('delete_comment_rayting',$comment_id,$point);
}
//Удаляем голос пользователя за публикацию
function delete_post_rayting($post_id,$user_id,$point){
    global $wpdb;
    $wpdb->query("DELETE FROM ".RCL_PREF."rayting_post WHERE post = '$post_id' AND user='$user_id'");	
    $point = -1*$point;
    do_action('delete_post_rayting',$post_id,$point);
}
//Удаляем данные рейтинга публикации
add_action('delete_post', 'delete_rayting_with_post');
function delete_rayting_with_post($postid){ 
    global  $wpdb;
    $data_p = get_post($postid);
    $point = get_post_total_rayting($postid);

    $wpdb->query("DELETE FROM ".RCL_PREF."rayting_post WHERE post = '$postid'");
    $wpdb->query("DELETE FROM ".RCL_PREF."total_rayting_posts WHERE post_id = '$postid'");

    $point = -1*$point;

    do_action('delete_rayting_with_post',$postid,$data_p->post_author,$point);
} 
//Удаляем данные рейтинга комментария
add_action('delete_comment', 'delete_rayting_with_comment');
function delete_rayting_with_comment($comment_id){
    global  $wpdb;
    $data_c = get_comment($comment_id);
    $point = get_comment_total_rayting($comment_id);

    $wpdb->query("DELETE FROM ".RCL_PREF."rayting_comments WHERE comment_id = '$comment_id'");
    $wpdb->query("DELETE FROM ".RCL_PREF."total_rayting_comments WHERE comment_id = '$comment_id'");

    $point = -1*$point;

    do_action('delete_rayting_with_comment',$comment_id,$data_c->user_id,$point);
} 