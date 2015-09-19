<?php

function rcl_count_votes_time($args,$second){
	global $wpdb;
	$now = current_time('mysql');
	$mark = ($args['rating_status']=='plus')? ">": "<";
	return $wpdb->get_var(
		$wpdb->prepare("
			SELECT COUNT(ID) FROM ".RCL_PREF."rating_values
			WHERE user_id='%d' AND object_author='%d' AND rating_type='%s' AND rating_value $mark 0 AND rating_date >= DATE_SUB('%s', INTERVAL %d SECOND)",
			$args['user_id'],$args['object_author'],$args['rating_type'],$now,$second)
	);
}

function rcl_get_rating_by_id($rating_id){
	global $wpdb;
	return $wpdb->get_row("SELECT * FROM ".RCL_PREF."rating_values WHERE ID='$rating_id'");
}

function rcl_get_ratings($args){
	global $wpdb;

	/*$args = array(
		'rating_type'=>array(),
		'object_id'=>array(),
		'object_author'=>array()
	);*/

	$fields = "*";
	$object_id = 'object_id';

	if(isset($args['fields'])){
		$fields = implode(',',$args['fields']);
	}

	$table = RCL_PREF."rating_totals";

	$where = array();

	if(isset($args['data_type'])&&$args['data_type']=='values'||isset($args['days'])){
		$table = RCL_PREF."rating_values";
	}

	if(isset($args['rating_type'])){
		if($args['rating_type']=='users'){
			$object_id = 'user_id';
			$table = RCL_PREF."rating_users";
		}else{
			$where[] = "rating_type IN ('".implode("','",$args['rating_type'])."')";
		}
	}
	if(isset($args['object_id'])){
		$where[] = "$object_id IN (".implode(",",$args['object_id']).")";
	}
	if(isset($args['object_author'])){
		$where[] = "object_author IN (".implode(",",$args['object_author']).")";
	}

	if(isset($args['days'])){
		$where[] = "rating_date > '".current_time('mysql')."' - INTERVAL ".$args['days']." DAY";
	}

	if($where) $query = "WHERE ".implode(' AND ',$where);

	if(isset($args['order'])){

		$query .= " ORDER BY";

		if($args['order']=='rating_total') $query .= " CAST(".$args['order']." AS DECIMAL) ";
		else $query .= " ".$args['order']." ";

		if(isset($args['order_by'])){
			$query .= $args['order_by'];
		}else{
			$query .= "DESC";
		}
	}

	if(isset($args['group_by'])&&$args['group_by']){
		$query .= " GROUP BY ".$args['group_by'];
	}

	if(isset($args['limit'])&&$args['limit']){
		$query .= " LIMIT ".implode(',',$args['limit']);
	}

	$query = "SELECT $fields FROM $table $query";
	//echo $query;
	return $wpdb->get_results($query);
}

function rcl_register_rating_type($args){
    global $rcl_rating_types,$rcl_options;

    $args['rating_type'] = (isset($args['post_type']))? $args['post_type']: $args['rating_type'];

    if(!isset($args['rating_type'])) return false;

    $type = $args['rating_type'];

    if(!isset($rcl_options['rating_'.$type])||!$rcl_options['rating_'.$type]) $rcl_options['rating_point_'.$type] = 0;

    $args['type_point'] = $rcl_options['rating_point_'.$type];
    $rcl_rating_types[$type] = $args;

	//print_r($args);
    //return $rcl_rating_types;
}

function rcl_get_rating_value($type){
    global $rcl_rating_types;
    $value = (isset($rcl_rating_types[$type]['type_point']))? $rcl_rating_types[$type]['type_point']: 1;
    return $value;
}

//добавляем голос пользователя к публикации
function rcl_insert_rating($args){
    global $wpdb;
    //print_r($args);exit;
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

    $wpdb->insert( RCL_PREF.'rating_values',  $data );

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

    $wpdb->insert( RCL_PREF.'rating_totals',  $data );

    do_action('rcl_insert_total_rating',$data);
}

//Вносим общий рейтинг пользователя в БД
add_action('user_register','rcl_insert_user_rating');
function rcl_insert_user_rating($user_id,$point=0){
    global $wpdb;
    $wpdb->insert(
        RCL_PREF.'rating_users',
        array( 'user_id' => $user_id, 'rating_total' => $point )
    );
}

//Получаем значение голоса пользователя к публикации
function rcl_get_vote_value($args){
    global $wpdb;
    return $wpdb->get_var(
            $wpdb->prepare(
                    "SELECT rating_value FROM ".RCL_PREF."rating_values "
                    . "WHERE object_id = '%d' AND rating_type='%s' AND user_id='%d'",
                    $args['object_id'],$args['rating_type'],$args['user_id']
            ));
}

//Получаем значение рейтинга публикации
function rcl_get_total_rating($object_id,$rating_type){
    global $wpdb,$rcl_options;
	$total = 0;

	if(!$rcl_options['rating_overall_'.$rating_type]){
		$total =  $wpdb->get_var(
            $wpdb->prepare(
                    "SELECT rating_total FROM ".RCL_PREF."rating_totals "
                    . "WHERE object_id = '%d' AND rating_type='%s'",
                    $object_id,$rating_type
            ));
	}else{
		$values =  $wpdb->get_results(
			$wpdb->prepare(
					"SELECT rating_value FROM ".RCL_PREF."rating_values "
					. "WHERE object_id = '%d' AND rating_type='%s'",
                    $object_id,$rating_type));
		if($values){
			foreach($values as $value){
				if($value->rating_value>0){
					$total ++;
				}else{
					$total --;
				}
			}
		}
	}
    return $total;
}

//Получаем значение рейтинга пользователя
function rcl_get_user_rating($user_id){
    $value = rcl_get_user_rating_value($user_id);
    if(!$value) $value = 0;
    return $value;
}

function rcl_get_user_rating_value($user_id){
    global $wpdb;
    return $wpdb->get_var("SELECT rating_total FROM ".RCL_PREF."rating_users WHERE user_id = '$user_id'");
}

function rcl_rating_navi($args){
    global $rcl_rating_types,$rcl_options;
    $navi = false;

	$rcl_rating_types['edit-admin'] = array(
		'rating_type'=>'edit-admin',
		'icon'=>'fa-cogs',
		'type_name'=>__('Correction','rcl')
	);

    foreach($rcl_rating_types as $type){

		if(!isset($rcl_options['rating_user_'.$type['rating_type']])||!$rcl_options['rating_user_'.$type['rating_type']])continue;

        $args['rating_type'] = $type['rating_type'];
        $active = (!$navi)? 'active' : '';
		$icon = (isset($type['icon']))? $type['icon']: 'fa-list-ul';
        $navi .= rcl_get_button($type['type_name'],'#',array('class'=> 'fa '.$icon.' get-list-votes '.$active,'attr'=>'onclick="rcl_get_list_votes(this);return false;" data-rating="'.rcl_encode_data_rating('user',$args).'"')).' ';
    }

    return $navi;
}

function rcl_get_rating_votes($args,$diap=false){
    global $wpdb;

    if(!$args) return false;

    $cols = array(
        'object_id',
        'rating_type',
        'object_author'
    );

    $wheres = array();

    foreach($cols as $col){
        if(isset($args[$col])){
            $wheres[$col] = $args[$col];
        }
    }

    foreach($wheres as $key=>$val){
        $where[] = $key."='$val'";
    }

    if(!$where) return false;

    $limit = '';

    if($diap){
        $limit = " LIMIT ".implode(',',$diap);
    }
	//print_r($args);
    $query = "SELECT * FROM ".RCL_PREF."rating_values WHERE ".implode(' AND ',$where)." ORDER BY rating_date DESC ".$limit;

    return $wpdb->get_results($query);
}

function rcl_get_votes_window($args,$votes,$navi=false){
    global $wpdb,$rcl_rating_types;

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

function rcl_get_list_votes($args,$votes){
    global $rcl_rating_types,$rcl_options;
	//print_r($rcl_rating_types);
    $list = '<ul class="votes-list">';

    if($votes){
        $names = rcl_get_usernames($votes,'user_id');
        foreach($votes as $vote){

            if(isset($rcl_options['rating_temp_'.$vote->rating_type])&&$args['rating_status']=='user'){
                    $row = $rcl_options['rating_temp_'.$vote->rating_type];
            }else{
                    $row = '%USER% '.__('voted','rcl').': %VALUE%';
            }

            $temps = array(
                    '%USER%',
                    '%VALUE%'
            );
            $reps = array(
                    '<a class="" target="_blank" href="'.get_author_posts_url($vote->user_id).'">'.$names[$vote->user_id].'</a>',
                    rcl_format_rating($vote->rating_value)
            );

            $row = str_replace($temps,$reps,$row);

            if($args['rating_status']=='user'){
                    $temps = array(
                            '%DATE%',
                            '%COMMENT%',
                            '%POST%'
                    );
                    $reps = array(
                            mysql2date('d F Y',$vote->rating_date),
                            '<a href="'.get_comment_link( $vote->object_id ).'">'.__('comment','rcl').'</a>',
                            '<a href="'.get_permalink($vote->object_id).'">'.get_the_title( $vote->object_id ).'</a>'
                    );
                    $row = str_replace($temps,$reps,$row);
            }

            $row = apply_filters('rcl_list_votes',$row,$vote);

            $class = ( $vote->rating_value > 0 ) ? 'fa-thumbs-o-up' : 'fa-thumbs-o-down';
            $list .= '<li><i class="fa '.$class.'"></i> '.$row.'</li>';
        }
    }else{
		$list .= '<li><b>'.$rcl_rating_types[$args['rating_type']]['type_name'].'</b>: '.__('Rating changes not made','rcl').'</li>';
    }

    $list .= '</ul>';

    return $list;
}


//Обновляем общий рейтинг публикации
add_action('rcl_delete_rating','rcl_update_total_rating');
add_action('rcl_insert_rating','rcl_update_total_rating');
function rcl_update_total_rating($args){
    global $wpdb;

    $total = rcl_get_total_rating($args['object_id'],$args['rating_type']);

    if(isset($total)){
        $total += $args['rating_value'];
        $wpdb->update(
                RCL_PREF.'rating_totals',
                array('rating_total'=>$total),
                array('object_id'=>$args['object_id'],'rating_type' => $args['rating_type'])
        );
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
    global $rcl_options;
	if(!$args['object_author']) return false;
    if($rcl_options['rating_user_'.$args['rating_type']]==1||$args['rating_type']=='edit-admin'||isset($args['user_overall']))
		rcl_update_user_rating($args);
}

//Обновляем общий рейтинг пользователя
function rcl_update_user_rating($args){
    global $wpdb;

    $total = rcl_get_user_rating_value($args['object_author']);

    if(isset($total)){
        $total += $args['rating_value'];
        $wpdb->update(
            RCL_PREF.'rating_users',
            array('rating_total'=>$total),
            array('user_id' => $args['object_author'])
        );
    }else{
		$total = $args['rating_value'];
        rcl_insert_user_rating($args['object_author'],$args['rating_value']);
    }

    do_action('rcl_update_user_rating',$args,$total);

	return $total;

}

//Удаляем из БД всю информацию об активности пользователя на сайте
//Корректируем рейтинг других пользователей
function rcl_delete_ratingdata_user($user_id){
    global  $wpdb;

    $datas = array();

    $r_posts = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".RCL_PREF."rating_values WHERE user_id = '%d'",$user_id));

    if($r_posts){
        foreach($r_posts as $r_post){
            $datas[$r_post->object_author][$r_post->rating_type][$r_post->object_id] += $r_post->rating_value;
        }
    }

    if($datas){
        foreach($datas as $object_author=>$val){
            foreach($val as $type=>$data){
                foreach($data as $object_id=>$value){
                    $rayt = -1*$rayt;
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
    $wpdb->query($wpdb->prepare("DELETE FROM ".RCL_PREF."rating_totals WHERE user_id = '%d' OR object_author='%d'",$user_id,$user_id));
    $wpdb->query($wpdb->prepare("DELETE FROM ".RCL_PREF."rating_users WHERE user_id = '%d'",$user_id));
}
add_action('delete_user','rcl_delete_ratingdata_user');

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

		$rating = rcl_get_vote_value($args);

		if(!isset($rating)) return false;

		$args['rating_value'] = (isset($args['rating_value']))? $args['rating_value']: $rating;

		$query = $wpdb->prepare(
			"DELETE FROM ".RCL_PREF."rating_values WHERE object_id = '%d' AND rating_type='%s' AND user_id='%s'",
			$args['object_id'],$args['rating_type'],$args['user_id']
		);

	}

    $res = $wpdb->query($query);

    $args['rating_value'] = -1 * $args['rating_value'];

    do_action('rcl_delete_rating',$args);

    return $args['rating_value'];
}

function rcl_delete_rating_with_post($args){
    global $wpdb;

    $args['rating_value'] = rcl_get_total_rating($args['object_id'],$args['rating_type']);

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
add_action('delete_post', 'rcl_delete_rating_post');
function rcl_delete_rating_post($post_id){
    rcl_delete_rating_with_post(array('object_id'=>$post_id,'rating_type'=>get_post_type($post_id)));
}
add_action('delete_comment', 'rcl_delete_rating_comment');
function rcl_delete_rating_comment($comment_id){
    rcl_delete_rating_with_post(array('object_id'=>$comment_id,'rating_type'=>'comment'));
}

add_filter('comments_array','rcl_add_data_rating_comments');
function rcl_add_data_rating_comments($comments){

	if(!$comments) return $comments;

	$users = array();
	$comms = array();

	foreach($comments as $comment){
		$users[$comment->user_id] = $comment->user_id;
		$comms[] = $comment->comment_ID;
	}

	$rating_authors = rcl_get_ratings(array('rating_type'=>'users','object_id'=>$users));
	$rating_comments = rcl_get_ratings(array('rating_type'=>array('comment'),'object_id'=>$comms));
	$rating_values = rcl_get_ratings(array('rating_type'=>array('comment'),'object_id'=>$comms,'data_type'=>'values'));

	if($rating_authors){
		foreach($rating_authors as $rating){
			$rt_authors[$rating->user_id] = $rating->rating_total;
		}
	}
	if($rating_comments){
		foreach($rating_comments as $rating){
			$rt_comments[$rating->object_id] = $rating->rating_total;
		}
	}
	if($rating_values){
		foreach($rating_values as $rating){
			if(!isset($rt_values[$rating->object_id])) $rt_values[$rating->object_id] = 0;
			if($rating->rating_value>0){
				$rt_values[$rating->object_id] += 1;
			}else{
				$rt_values[$rating->object_id] -= 1;
			}
		}
	}

	foreach($comments as $comment){
		$comment->rating_author = (isset($rt_authors[$comment->user_id]))? $rt_authors[$comment->user_id]: 0;
		$comment->rating_total = (isset($rt_comments[$comment->comment_ID]))? $rt_comments[$comment->comment_ID]: 0;
		$comment->rating_votes = (isset($rt_values[$comment->comment_ID]))? $rt_values[$comment->comment_ID]: 0;
	}

	return $comments;
}

/*add_action( 'wp', 'rcl_add_data_rating_posts');
function rcl_add_data_rating_posts(){
	global $wp_query;

	if(!is_admin()){

		$users = array();
		$posts = array();
		$posttypes = array();

		foreach($wp_query->posts as $post){
			$users[$post->post_author] = $post->post_author;
			$posttypes[$post->post_type] = $post->post_type;
			$posts[] = $post->ID;
		}

		$rating_authors = rcl_get_ratings(array('rating_type'=>'users','object_id'=>$users));
		$rating_posts = rcl_get_ratings(array('rating_type'=>$posttypes,'object_id'=>$posts));

		if($rating_authors){
			foreach($rating_authors as $rating){
				$rt_authors[$rating->user_id] = $rating->rating_total;
			}
		}
		if($rating_posts){
			foreach($rating_posts as $rating){
				$rt_posts[$rating->object_id] = $rating->rating_total;
			}
		}

		foreach($wp_query->posts as $post){
			$post->rating_author = (isset($rt_authors[$post->post_author]))? $rt_authors[$post->post_author]: 0;
			$post->rating_total = (isset($rt_posts[$post->ID]))? $rt_posts[$post->ID]: 0;
		}

		print_r($wp_query->posts);

	}
}*/

add_shortcode('rcl-rating','rcl_rating_shortcode');
function rcl_rating_shortcode($atts){
	global $rating;

	extract(shortcode_atts(array(
		'rating_type'=>false,
		'days'=>false,
		'number'=>10,
		'offset'=>0,
		'order'=>'ID',
		'list_type' => 'row',
		'group_by' => ''
	),
	$atts));

	$rcl_rating = new Rcl_Rating();

	$ratings = $rcl_rating->get_values($atts);

	//print_r($ratings);

	$userlist ='<div class="ratinglist '.$list_type.'-list">';
	foreach($ratings as $rating){
		$rating = (object)$rating;
		$userlist .= rcl_get_include_template('rating-'.$list_type.'.php',__FILE__);
	}

	$userlist .='</div>';

	return $userlist;

}