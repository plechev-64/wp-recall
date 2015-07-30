<?php
function rcl_update_old_feeds(){
    global $wpdb;
    $meta = $wpdb->get_var("SELECT  umeta_id $wpdb->usermeta WHERE meta_key LIKE 'feed_user_%'");
    if($meta) $wpdb->query("UPDATE $wpdb->usermeta SET meta_key='rcl_feed' WHERE meta_key LIKE 'feed_user_%'");
}
/*13.0.0*/
function rcl_update_rating_data(){
    global $wpdb;

    $objs = $wpdb->get_results("SELECT * FROM ".RCL_PREF."rayting_comments ORDER BY time_action DESC");

    if($objs){
        $totals = array();
        $values = array();
        foreach($objs as $obj){
            $values[] = "(".$obj->user.",".$obj->comment_id.",".$obj->author_com.",".$obj->rayting.",'".$obj->time_action."','comment')";
            if(!isset($totals['comment'][$obj->comment_id])){
                $totals['comment'][$obj->comment_id]['value'] = $obj->rayting;
                $totals['comment'][$obj->comment_id]['author'] = $obj->author_com;
            }else{
                $totals['comment'][$obj->comment_id]['value'] += $obj->rayting;
            }
        }

        $query = "INSERT INTO ".RCL_PREF."rating_values (user_id,object_id,object_author,rating_value,rating_date,rating_type)"
                . " VALUES ".implode(',',$values).";";
        $wpdb->query($query);

		$wpdb->query("DROP TABLE ".RCL_PREF."rayting_comments");
		$wpdb->query("DROP TABLE ".RCL_PREF."total_rayting_comments");
    }

    $objs = $wpdb->get_results("SELECT * FROM ".RCL_PREF."rayting_post ORDER BY ID DESC");

    if($objs){
        $values = array();
        foreach($objs as $obj){
            $type = get_post_type($obj->post);
            if(!$type) continue;
            $values[] = "(".$obj->user.",".$obj->post.",".$obj->author_post.",".$obj->status.",DEFAULT,'".$type."')";
            if(!isset($totals[$type][$obj->post])){
                $totals[$type][$obj->post]['value'] = $obj->status;
                $totals[$type][$obj->post]['author'] = $obj->author_post;
            }else{
                $totals[$type][$obj->post]['value'] += $obj->status;
            }
        }
       $query = "INSERT INTO ".RCL_PREF."rating_values (user_id,object_id,object_author,rating_value,rating_date,rating_type)"
                . " VALUES ".implode(',',$values).";";
        $wpdb->query($query);

		$wpdb->query("DROP TABLE ".RCL_PREF."rayting_post");
		$wpdb->query("DROP TABLE ".RCL_PREF."total_rayting_posts");
    }

    $objs = $wpdb->get_results("SELECT * FROM ".RCL_PREF."profile_otziv");

    if($objs){
        $values = array();
        foreach($objs as $obj){
            $values[] = "(".$obj->author_id.",".$obj->ID.",".$obj->user_id.",".$obj->status.",DEFAULT,'rcl-review')";
            if(!isset($totals['rcl-review'][$obj->ID])){
                $totals['rcl-review'][$obj->ID]['value'] = $obj->status;
                $totals['rcl-review'][$obj->ID]['author'] = $obj->user_id;
            }else{
                $totals['rcl-review'][$obj->ID]['value'] += $obj->status;
            }
        }
        $query = "INSERT INTO ".RCL_PREF."rating_values (user_id,object_id,object_author,rating_value,rating_date,rating_type)"
                . " VALUES ".implode(',',$values).";";
        $wpdb->query($query);

		$wpdb->query("ALTER TABLE ".RCL_PREF."profile_otziv MODIFY status VARCHAR(5)");
    }

    if(!$totals) return false;

    $values = array();
    $users = array();
    foreach($totals as $type=>$total){
        foreach($total as $id=>$val){
            $totals[$type][$obj->post]['value'] += $obj->status;
            $values[] = "(".$id.",".$val['author'].",".$val['value'].",'".$type."')";

            if(!isset($users[$val['author']])) $users[$val['author']] = $val['value'];
            else $users[$val['author']] += $val['value'];
        }
    }

    $query = "INSERT INTO ".RCL_PREF."rating_totals (object_id,object_author,rating_total,rating_type)"
            . " VALUES ".implode(',',$values).";";
    $wpdb->query($query);

    $values = array();
    foreach($users as $user_id=>$total){
        $values[] = "(".$user_id.",".$total.")";
    }

    $query = "INSERT INTO ".RCL_PREF."rating_users (user_id,rating_total)"
            . " VALUES ".implode(',',$values).";";
    $wpdb->query($query);

	$wpdb->query("DROP TABLE ".RCL_PREF."total_rayting_users");
}
/*12.2.0*/
function rcl_update_avatar_data(){
    global $wpdb;

    $avatars = $wpdb->get_results("SELECT * FROM $wpdb->options WHERE option_name LIKE 'avatar_user_%'");

    if(!$avatars) return false;

    foreach($avatars as $avatar){
        $user_id = str_replace('avatar_user_', '', $avatar->option_name);
        update_user_meta($user_id,'rcl_avatar',$avatar->option_value);
    }
    $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE 'avatar_user_%'");
}