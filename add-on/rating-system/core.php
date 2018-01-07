<?php

//регистрация типа рейтинга
function rcl_register_rating_type($args){
    global $rcl_rating_types,$rcl_options;

    $args['rating_type'] = (isset($args['post_type']))? $args['post_type']: $args['rating_type'];

    if(!isset($args['rating_type'])) return false;
    
    $args['comment_type'] = (isset($args['comment_type']))? $args['comment_type']: 'comment';

    $type = $args['rating_type'];

    if(!rcl_get_option('rating_'.$type)) $rcl_options['rating_point_'.$type] = 0;

    $args['type_point'] = $rcl_options['rating_point_'.$type];
    $rcl_rating_types[$type] = $args;
}

//получение данных полного рейтинга пользователей
function rcl_get_rating_users($args = false){
    $users = new Rcl_Rating_Users_Query();
    return $users->get_results($args);
}

//получение данных полного рейтинга произвольных сущностей
function rcl_get_rating_totals($args = false){
    $totals = new Rcl_Rating_Totals_Query();
    return $totals->get_results($args);
}

//подсчет количества значений
function rcl_count_rating_values($args = false){
    $rating = new Rcl_Rating_Values_Query();
    return $rating->count($args);
}

function rcl_get_vote_values($args = false){
    $rating = new Rcl_Rating_Values_Query();
    return $rating->get_results($args);
}

function rcl_get_vote_value($user_id,$object_id,$rating_type){
    
    $rating = new Rcl_Rating_Values_Query();

    return $rating->get_var(array(
        'object_id' => $object_id,
        'user_id' => $user_id,
        'rating_type' => $rating_type,
        'fields' => array('rating_value')
    ));

}

function rcl_count_votes_time($args,$second){

    $cachekey = json_encode(array('rcl_count_votes_time',$args,$second));
    $cache = wp_cache_get( $cachekey );
    if ( $cache )
        return $cache;
    
    $rating = new Rcl_Rating_Values_Query();
    
    $rating->set_query(array(
        'user_id' => $args['user_id'],
        'object_author' => $args['object_author'],
        'rating_type' => $args['rating_type']
    ));
    
    $rating->query['where'][] = "rating_value $mark 0";
    $rating->query['where'][] = "rating_date >= DATE_SUB('$now', INTERVAL $second SECOND)";
    
    $result = $rating->count();

    wp_cache_add( $cachekey, $result );

    return $result;
}

function rcl_get_rating_by_id($rating_id){
    global $wpdb;

    $cachekey = json_encode(array('rcl_get_rating_by_id',$rating_id));
    $cache = wp_cache_get( $cachekey );
    if ( $cache )
        return $cache;
    
    $rating = new Rcl_Rating_Values_Query();
    
    $result = $rating->get_row(array(
        'ID' => $rating_id
    ));

    wp_cache_add( $cachekey, $result );

    return $result[0];
}

//добавляем голос пользователя к публикации
function rcl_insert_rating($args){
    global $wpdb;
    
    $rating_date = current_time('mysql');

    if($args['rating_status']=='minus') $args['rating_value'] = -1 * $args['rating_value'];

    $args['rating_date'] = $rating_date;

    $data = array(
        'object_id' => $args['object_id'],
        'object_author' => $args['object_author'],
        'rating_type' => $args['rating_type'],
        'user_id' => $args['user_id'],
        'rating_value' => $args['rating_value'],
        'rating_date' => $rating_date
    );

    $result = $wpdb->insert( RCL_PREF.'rating_values',  $data );
    
    if(!$result){
        rcl_add_log('rcl_insert_rating: '.__('Failed to add user vote','wp-recall'), $data);
    }

    $value_id = $wpdb->insert_id;

    do_action('rcl_insert_rating',$args);

    return $value_id;
}

//Вносим значение общего рейтинга публикации в БД
function rcl_insert_total_rating($args){
    global $wpdb;

    $args['rating_total'] = (!isset($args['rating_total'])&&isset($args['rating_value']))? $args['rating_value']: $args['rating_total'];

    $data = array(
        'object_id' => $args['object_id'],
        'object_author' => $args['object_author'],
        'rating_total' => $args['rating_total'],
        'rating_type' => $args['rating_type']
    );

    $result = $wpdb->insert( RCL_PREF.'rating_totals',  $data );
    
    if(!$result){
        rcl_add_log('rcl_insert_total_rating: '.__('Failed to add general rating of the object','wp-recall'), $data);
    }

    do_action('rcl_insert_total_rating',$data);
}

//Вносим общий рейтинг пользователя в БД
add_action('user_register','rcl_insert_user_rating');
function rcl_insert_user_rating($user_id,$point=0){
    global $wpdb;
    
    $result = $wpdb->insert(
        RCL_PREF.'rating_users',
        array( 'user_id' => $user_id, 'rating_total' => $point )
    );
    
    if(!$result){
        rcl_add_log('rcl_insert_user_rating: '.__('Failed to add general rating of the user','wp-recall'), array($user_id,$point));
    }
}

//Получаем значение рейтинга публикации
function rcl_get_total_rating($object_id,$rating_type){
    
    $total = 0;

    if(!rcl_get_option('rating_overall_'.$rating_type)){

        $total = rcl_get_rating_sum($object_id,$rating_type);
        
    }else{
        
        $total = rcl_get_votes_sum($object_id,$rating_type);
        
    }
    
    return $total;
}

function rcl_get_rating_sum($object_id,$rating_type){
    
    $totals = new Rcl_Rating_Totals_Query();
    
    $total = $totals->get_var(array(
        'object_id' => $object_id,
        'rating_type' => $rating_type,
        'fields' => array('rating_total')
    ));
    
    return $total;
}

function rcl_get_votes_sum($object_id,$rating_type){
    
    $cachekey = json_encode(array('rcl_get_votes_sum',$object_id,$rating_type));
    $cache = wp_cache_get( $cachekey );
    if ( $cache )
        return $cache;
    
    $rating = new Rcl_Rating_Values_Query();
    
    $total = $rating->get_sum_values(array(
        'object_id' => $object_id,
        'rating_type' => $rating_type
    ));
    
    wp_cache_add( $cachekey, $total );
    
    return $total;
}

//Получаем значение рейтинга пользователя
function rcl_get_user_rating($user_id){
    return ($value = rcl_get_user_rating_value($user_id))? $value: 0;
}

function rcl_get_user_rating_value($user_id){
    
    $cachekey = json_encode(array('rcl_get_user_rating_value',$user_id));
    $cache = wp_cache_get( $cachekey );
    if ( $cache )
        return $cache;

    $users = new Rcl_Rating_Users_Query();
    $value = $users->get_var(array(
        'user_id' => $user_id,
        'fields' => array('rating_total')
    ));
    
    wp_cache_add( $cachekey, $value );
    
    return $value;

}

function rcl_rating_navi($args){
    global $rcl_rating_types;
    $navi = false;

    $rcl_rating_types['edit-admin'] = array(
        'rating_type'=>'edit-admin',
        'icon'=>'fa-cogs',
        'type_name'=>__('Correction','wp-recall')
    );

    foreach($rcl_rating_types as $type){

        if(!rcl_get_option('rating_user_'.$type['rating_type']))continue;

        $args['rating_type'] = $type['rating_type'];
        $active = (!$navi)? 'active' : '';
        $icon = (isset($type['icon']))? $type['icon']: 'fa-list-ul';
        $navi .= rcl_get_button($type['type_name'],'#',array('icon'=>'fa '.$icon,'class'=> 'get-list-votes '.$active,'attr'=>'onclick="rcl_get_list_votes(this);return false;" data-rating="'.rcl_encode_data_rating('user',$args).'"')).' ';
    }

    return $navi;
}

function rcl_get_votes_window($args,$votes,$navi=false){
    global $rcl_rating_types;
    
    $list_votes = rcl_get_list_votes($args,$votes);

    if(isset($_POST['content'])&&$_POST['content']=='list-votes') return $list_votes;

    $window = '<div class="votes-window">';

    if($navi) $window .= $navi;

    $window .= '<a href="#" onclick="rcl_close_votes_window(this);return false;" class="close">'
        . '<i class="fa fa-times-circle"></i>'
    . '</a>';

    $window .= $list_votes;

    $window .= '</div>';

    return $window;
}

function rcl_get_usernames($objects,$name_data){
    global $wpdb;

    if(!$objects||!$name_data) return false;

    foreach((array)$objects as $object){ $userslst[] = $object->$name_data; }

    $display_names = $wpdb->get_results($wpdb->prepare("SELECT ID,display_name FROM ".$wpdb->prefix."users WHERE ID IN (".rcl_format_in($userslst).")",$userslst));

    if(!$display_names)
        return false;
    
    foreach((array)$display_names as $name){
        $names[$name->ID] = $name->display_name;
    }
    
    return $names;
}

function rcl_get_list_votes($args,$votes){
    global $rcl_rating_types,$rcl_options,$wpdb;
    
    $list = '<ul class="votes-list">';

    if($votes){
        
        $userslst = array();
        
        foreach($votes as $vote){ 
            $userslst[] = $vote->user_id; 
        }

        $display_names = $wpdb->get_results($wpdb->prepare("SELECT ID,display_name FROM ".$wpdb->prefix."users WHERE ID IN (".rcl_format_in($userslst).")",$userslst));

        if($display_names){
            $names = array();
            foreach($display_names as $name){
                $names[$name->ID] = $name->display_name;
            }
        }
        
        foreach($votes as $vote){

            if(isset($rcl_options['rating_temp_'.$vote->rating_type])&&$args['rating_status']=='user'){
                
                $row = $rcl_options['rating_temp_'.$vote->rating_type];
                
            }else{
                
                $row = ($vote->rating_date!='0000-00-00 00:00:00')? mysql2date('d.m.Y',$vote->rating_date).' ': '';
                $row .= '%USER% '.__('has voted','wp-recall').': %VALUE%';
                
            }

            $temps = array(
                '%USER%',
                '%VALUE%'
            );
            
            $user_name = (isset($names[$vote->user_id]))? $names[$vote->user_id]: '';
            
            $reps = array(
                '<a class="" target="_blank" href="'.get_author_posts_url($vote->user_id).'">'.$user_name.'</a>',
                rcl_format_rating($vote->rating_value)
            );
            
            $row = str_replace($temps,$reps,$row);

            if($args['rating_status']=='user'){
                
                $temps = array(
                    '%DATE%',
                    '%COMMENT%',
                    '%POST%'
                );

                $date = ($vote->rating_date!='0000-00-00 00:00:00')? mysql2date('d F Y',$vote->rating_date): '';

                $reps = array(
                    $date,
                    '<a href="'.get_comment_link( $vote->object_id ).'">'.__('comment','wp-recall').'</a>',
                    '<a href="'.get_permalink($vote->object_id).'">'.get_the_title( $vote->object_id ).'</a>'
                );

                $row = str_replace($temps,$reps,$row);
            }
            
            $row = apply_filters('rcl_list_votes',$row,$vote);
            
            $class = ( $vote->rating_value > 0 ) ? 'fa-thumbs-o-up' : 'fa-thumbs-o-down';
            $list .= '<li class="vote-type-'.$vote->rating_type.'"><i class="fa '.$class.'"></i> '.$row.'</li>';
        }
        
    }else{
        
        $list .= '<li><b>'.$rcl_rating_types[$args['rating_type']]['type_name'].'</b>: '.__('No changes to rating','wp-recall').'</li>';

    }

    $list .= '</ul>';

    return $list;
}


//Обновляем общий рейтинг публикации
add_action('rcl_delete_rating','rcl_update_total_rating');
add_action('rcl_insert_rating','rcl_update_total_rating');
function rcl_update_total_rating($args){
    global $wpdb;

    $total = rcl_get_rating_sum($args['object_id'],$args['rating_type']);

    if(isset($total)){
        
        $total += $args['rating_value'];
        
        $result = $wpdb->update(
            RCL_PREF.'rating_totals',
            array(
                'rating_total'=>$total
            ),
            array(
                'object_id'=>$args['object_id'],
                'rating_type' => $args['rating_type']
            )
        );
        
        if(!$result){
            rcl_add_log('rcl_update_total_rating: '.__('Failed to change general rating of the object','wp-recall'), array($total,$args));
        }
        
    }else{
        
        rcl_insert_total_rating($args);
        
        $total = $args['rating_value'];
        
    }

    do_action('rcl_update_total_rating',$args,$total);

    return $total;
}

//Определяем изменять ли рейтинг пользователю
add_action('rcl_update_total_rating','rcl_post_update_user_rating');
add_action('rcl_delete_rating_with_post','rcl_post_update_user_rating');
function rcl_post_update_user_rating($args){
    
    if(!isset($args['object_author'])||!$args['object_author']) return false;
    if(rcl_get_option('rating_user_'.$args['rating_type'])==1||$args['rating_type']=='edit-admin'||isset($args['user_overall']))
        rcl_update_user_rating($args);
}

//Обновляем общий рейтинг пользователя
function rcl_update_user_rating($args){
    global $wpdb;
    
    wp_cache_delete(json_encode(array('rcl_get_user_rating_value',$args['object_author'])));

    $total = rcl_get_user_rating_value($args['object_author']);

    if(isset($total)){
        $total += $args['rating_value'];
        $result = $wpdb->update(
            RCL_PREF.'rating_users',
            array('rating_total'=>$total),
            array('user_id' => $args['object_author'])
        );
        
        if(!$result){
            rcl_add_log('rcl_update_user_rating: '.__('Failed to change general rating of the user','wp-recall'), $args);
        }
        
    }else{
        $total = $args['rating_value'];
        rcl_insert_user_rating($args['object_author'],$args['rating_value']);
    }

    do_action('rcl_update_user_rating',$args,$total);

    return $total;

}

function rcl_get_rating_value($type){
    global $rcl_rating_types;
    $value = (isset($rcl_rating_types[$type]['type_point']))? $rcl_rating_types[$type]['type_point']: 1;
    return $value;
}

//Удаляем голос пользователя за публикацию
function rcl_delete_rating($args){

    global $wpdb;

    if(isset($args['ID'])){

        $data = rcl_get_rating_by_id($args['ID']);

        $query = $wpdb->prepare(
                "DELETE FROM ".RCL_PREF."rating_values WHERE ID = '%d'",
                $args['ID']
        );

        $args = array(
                'object_id'=>$data->object_id,
                'object_author'=>$data->object_author,
                'rating_type'=>$data->rating_type,
                'rating_value'=>$data->rating_value,
        );

    }else{

        $rating = rcl_get_vote_value($args['user_id'],$args['object_id'],$args['rating_type']);

        if(!isset($rating)) return false;

        $args['rating_value'] = (isset($args['rating_value']))? $args['rating_value']: $rating;

        $query = $wpdb->prepare(
                "DELETE FROM ".RCL_PREF."rating_values WHERE object_id = '%d' AND rating_type='%s' AND user_id='%s'",
                $args['object_id'],$args['rating_type'],$args['user_id']
        );

    }

    $result = $wpdb->query($query);

    $args['rating_value'] = -1 * $args['rating_value'];

    do_action('rcl_delete_rating',$args);

    return $args['rating_value'];
}

function rcl_delete_rating_with_post($args){
    global $wpdb;

    $args['rating_value'] = rcl_get_rating_sum($args['object_id'],$args['rating_type']);
    
    $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM ".RCL_PREF."rating_values "
            . "WHERE object_id = '%d' AND rating_type='%s'",
            $args['object_id'],$args['rating_type']));

    $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM ".RCL_PREF."rating_totals "
            . "WHERE object_id = '%d' AND rating_type='%s'",
            $args['object_id'],$args['rating_type']));

    $args['rating_value'] = -1 * $args['rating_value'];

    do_action('rcl_delete_rating_with_post',$args);
}

//Удаляем данные рейтинга публикации
add_action('delete_post', 'rcl_delete_rating_post',10);
function rcl_delete_rating_post($post_id){
    $post = get_post($post_id);
    rcl_delete_rating_with_post(array('object_id'=>$post_id,'object_author'=>$post->post_author,'rating_type'=>$post->post_type));
}

add_action('delete_comment', 'rcl_delete_rating_comment',10);
function rcl_delete_rating_comment($comment_id){
    $comment = get_comment($comment_id);   
    rcl_delete_rating_with_post(array('object_id'=>$comment_id,'object_author'=>$comment->user_id,'rating_type'=>'comment'));
}

//Удаляем из БД всю информацию об активности пользователя на сайте
//Корректируем рейтинг других пользователей
add_action('delete_user','rcl_delete_ratingdata_user');
function rcl_delete_ratingdata_user($user_id){
    global  $wpdb;

    $datas = array();
    
    $r_posts = rcl_get_vote_values(array(
        'user_id' => $user_id
    ));

    if($r_posts){
        foreach($r_posts as $r_post){
            $datas[$r_post->object_author][$r_post->rating_type][$r_post->object_id] += $r_post->rating_value;
        }
    }

    if($datas){
        foreach($datas as $object_author=>$val){
            foreach($val as $type=>$data){
                foreach($data as $object_id=>$value){
                    $value = -1*$value;
                    $args = array(
                        'user_id' => $user_id,
                        'object_id' => $object_id,
                        'object_author' => $object_author,
                        'rating_value' => $value,
                        'rating_type' => $type
                    );
                    rcl_update_total_rating($args);
                }
            }
        }
    }

    $wpdb->query($wpdb->prepare("DELETE FROM ".RCL_PREF."rating_values WHERE user_id = '%d' OR object_author='%d'",$user_id,$user_id));
    $wpdb->query($wpdb->prepare("DELETE FROM ".RCL_PREF."rating_totals WHERE object_author='%d'",$user_id));
    $wpdb->query($wpdb->prepare("DELETE FROM ".RCL_PREF."rating_users WHERE user_id = '%d'",$user_id));
}

/*$atts
array(
 * 'object_id',
 * 'object_author',
 * 'rating_type',
 * 'days',
 * 'object_id__in',
 * 'object_author__in',
 * 'rating_type__in'
 * 'object_id__not_in',
 * 'object_author__not_in',
 * 'rating_type__not_in',
 * 'rating_total',
 * 'rating_total__from',
 * 'rating_total__to',
 * 'number',
 * 'per_page',
 * 'offset',
 * 'orderby',
 * 'order'
 * ) */

add_shortcode('ratinglist','rcl_rating_shortcode');
function rcl_rating_shortcode($atts){
    global $rating;

    $rcl_rating = new Rcl_Rating_Totals_Query();
    
    $atts['fields'] = array(
        'object_id',
        'object_author',
        'rating_total',
        'rating_type'
    );

    $rcl_rating->set_query($atts);
    
    if(isset($atts['days'])){
        
        $tableAs = $rcl_rating->query['table']['as'];

        $rcl_rating->query['select'][] = "SUM(rating_values.rating_value) AS days_value_sum";
        $rcl_rating->query['orderby'] = (isset($atts['orderby']))? $rcl_rating->query['orderby']: "days_value_sum";
        $rcl_rating->query['join'][] = "INNER JOIN ".RCL_PREF."rating_values AS rating_values ON $tableAs.object_id = rating_values.object_id";
        $rcl_rating->query['where'][] = "rating_values.rating_date > ('".current_time('mysql')."' - INTERVAL ".$atts['days']." DAY)";
        $rcl_rating->query['where'][] = "$tableAs.rating_type = rating_values.rating_type";
        $rcl_rating->query['where'][] = "$tableAs.object_author = rating_values.object_author";
        $rcl_rating->query['groupby'] = "$tableAs.object_id";
        
    }
    
    $template = (isset($atts['template']))? $atts['template']: 'post';
    
    $count_users = false;

    if(!isset($atts['number'])){

        $count_values = $rcl_rating->count();
        $rclnavi = new Rcl_PageNavi('rcl-rating',$count_values,array('in_page'=>$rcl_rating->query['number']));
        $rcl_rating->query['offset'] = $rclnavi->offset;
        
    }
    
    $rcl_cache = new Rcl_Cache();
    
    if($rcl_cache->is_cache){

        $file = $rcl_cache->get_file($rcl_rating->get_sql());
        
        if(!$file->need_update){

            return $rcl_cache->get_cache();

        }
    }
    
    $ratings = $rcl_rating->get_data();
    
    if(!$ratings){
        
        $content = '<p align="center">'.__('Data not found','wp-recall').'</p>';

        return $content;
    }

    $content ='<div class="ratinglist rating-'.$template.'">';
    
    foreach($ratings as $rating){
        $rating = (object)$rating;
        $rating->days_value_sum = ($rating->days_value_sum>0)? '+'.$rating->days_value_sum: $rating->days_value_sum;
        $content .= rcl_get_include_template('rating-'.$template.'.php',__FILE__);
    }

    $content .='</div>';
    
    if(!isset($atts['number']))
        $content .= $rclnavi->pagenavi();
    
    if($rcl_cache->is_cache){
        $rcl_cache->update_cache($content);        
    }

    return $content;

}