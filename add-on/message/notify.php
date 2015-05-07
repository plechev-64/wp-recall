<?php
add_action('wp', 'rcl_activation_hourly_notify_new_message');
function rcl_activation_hourly_notify_new_message() {
	//wp_clear_scheduled_hook('hourly_notify_new_message');
	if ( !wp_next_scheduled( 'hourly_notify_new_message' ) ) {
		$start_date = strtotime(current_time('mysql'));
		wp_schedule_event( $start_date, 'hourly', 'hourly_notify_new_message');
	}
}

add_action('hourly_notify_new_message','rcl_send_notify_messages');
function rcl_send_notify_messages(){
    global $wpdb;

    $mess = $wpdb->get_results("SELECT author_mess,adressat_mess,time_mess FROM ".RCL_PREF."private_message WHERE status_mess='0' && time_mess  > date_sub(now(), interval 1 hour)");

    if(!$mess) return false;

    foreach($mess as $m){
        $arrs[$m->adressat_mess][$m->author_mess] = $m->time_mess;
    }

    foreach($arrs as $add_id=>$vals){
        $mess = '';
        $to = get_the_author_meta('user_email',$add_id);

        $cnt = count($vals);

        foreach($vals as $auth_id=>$time){
            $url = rcl_format_url(get_author_posts_url($auth_id),'privat');
            $mess .= '<div style="overflow:hidden;clear:both;">
                <p>'.__('You were sent a private message','rcl').'</p>
                <div style="float:left;margin-right:15px;">'.get_avatar($auth_id,60).'</div>'
                . '<p>'.__('from the user','rcl').' '.get_the_author_meta('display_name',$auth_id).'</p>'
                . '<p>'.__('You can read the message by clicking on the link:','rcl').' <a href="'.$url.'">'.$url.'</a></p>'
                . '</div>';
        }
        if($cnt==1) $title = __('For you','rcl').' '.$cnt.' '.__('new message','rcl');
        else $title = __('For you','rcl').' '.$cnt.' '.__('new messages','rcl');
        rcl_mail($to, $title, $mess);
    }

}