<?php
rcl_enqueue_style('allcomments',__FILE__);

function ajax_all_user_comments($array_tabs){
    $array_tabs['allcomments']='all_user_comments';
    return $array_tabs;
}
add_filter('ajax_tabs_rcl','ajax_all_user_comments');

if(function_exists('add_rayting_comment')) add_filter('allcomments_text_rcl','add_rayting_comment',10,2);

add_action('init','add_tab_manager_allcomments');
function add_tab_manager_allcomments(){
    add_tab_rcl('allcomments','all_user_comments','Комментарии',array('order'=>55,'class'=>'fa-comment','public'=>1));
}
function all_user_comments($user_LK){
    global $wpdb;
    
    $cnt = $wpdb->get_var("SELECT COUNT(comment_ID) FROM ".$wpdb->prefix ."comments WHERE user_id = '$user_LK'");
    
    $rclnavi = new RCL_navi(30,$cnt,'&view=allcomments');
    
    $comments = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix ."comments WHERE user_id = '$user_LK' ORDER BY comment_date DESC LIMIT ".$rclnavi->limit());
    $deletes = $wpdb->get_col("SELECT comment_id FROM ".$wpdb->prefix ."commentmeta WHERE meta_key = '_wp_trash_meta_status' AND meta_value='1'");
    
    foreach($deletes as $id){
        $ds[$id] = '1';
    }
    
    if($comments){
        $content = '<div id="commentlist">';
        $content .= '<h3>Комментарии пользователя</h3>'; 
        
        foreach($comments as $comment){
            //if(get_comment_meta ( $comment->comment_ID, '_wp_trash_meta_status', true )) continue;
            if(isset($ds[$comment->comment_ID])) continue;
            $content .= '<div id="comment-'.$comment->comment_post_ID.'" class="comment">'
                    . '<p class="comment-meta">'
                        . '<span class="comment-date">'.mysql2date('j F Y G:i:s', $comment->comment_date).'</span> '
                        . '<span class="comment-post">'
                            . 'к записи: <a href="'.get_permalink( $comment->comment_post_ID ).'">'.get_the_title($comment->comment_post_ID).'</a>'
                        . '</span>'
                    . '</p>'; 
            
            $comment_content = apply_filters('allcomments_text_rcl',$comment->comment_content,$comment);
            
            $content .= '<div class="comment-content">'.$comment_content.'</div>'
                        . '<p class="comment-link">'
                            . '<a target="_blank" href="'.get_permalink( $comment->comment_post_ID ).'#comment-'.$comment->comment_ID.'">'
                                . 'Перейти к комментарию'
                            . '</a>'
                        . '</p>'
                    . '</div>';
        }   
        $content .= '</div>';
        
        $content .= $rclnavi->navi();
        
        return $content;      
    }else{
        return '<h3>Комментариев пока нет</h3>';
    }
}