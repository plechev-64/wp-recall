<?php

class Rcl_Rating{

	function __construct() {
            add_action('wp_ajax_edit_rating_post', array(&$this, 'edit_rating_post'));
            add_action('wp_ajax_view_rating_votes', array(&$this, 'view_rating_votes'));
            add_action('wp_ajax_nopriv_view_rating_votes', array(&$this, 'view_rating_votes'));
	}

        function view_rating_votes(){

            $args = rcl_decode_data_rating(sanitize_text_field($_POST['rating']));

            $navi = false;

            if($args['rating_status']=='user') $navi = rcl_rating_navi($args);

            $votes = rcl_get_rating_votes($args,array(0,100));

            $window = rcl_get_votes_window($args,$votes,$navi);

            $log['result']=100;
            $log['window']=$window;
            echo json_encode($log);
            exit;
        }

	function edit_rating_post(){
            global $rcl_options,$rcl_rating_types;

            $args = rcl_decode_data_rating(sanitize_text_field($_POST['rating']));
			
			if($rcl_options['rating_'.$args['rating_status'].'_limit_'.$args['rating_type']]){
				$timelimit = ($rcl_options['rating_'.$args['rating_status'].'_time_'.$args['rating_type']])? $rcl_options['rating_'.$args['rating_status'].'_time_'.$args['rating_type']]: 3600;
				$votes = rcl_count_votes_time($args,$timelimit);
				if($votes>=$rcl_options['rating_'.$args['rating_status'].'_limit_'.$args['rating_type']]){
					$log['error']=sprintf(__('exceeded the limit of votes for the period - %d seconds','rcl'),$timelimit);
                    echo json_encode($log);
                    exit;
				}
			}

            $value = rcl_get_vote_value($args);

            if($value){

                if($args['rating_status']=='cancel'){

                    $rating = rcl_delete_rating($args);

                }else{
                    $log['result']=110;
                    echo json_encode($log);
                    exit;
                }

            }else{

                $args['rating_value'] = rcl_get_rating_value($args['rating_type']);

                $rating = rcl_insert_rating($args);

            }

            $total = rcl_get_total_rating($args['object_id'],$args['rating_type']);

            $log['result']=100;
            $log['object_id']=$args['object_id'];
            $log['rating_type']=$args['rating_type'];
            $log['rating']=$total;

            echo json_encode($log);
            exit;
	}

}
$Rcl_Rating = new Rcl_Rating();