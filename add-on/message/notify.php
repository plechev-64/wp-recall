<?php

add_action('rcl_cron_hourly','rcl_send_notify_messages',10);
function rcl_send_notify_messages(){
    global $wpdb,$rcl_options;
    
    $mailtext = (isset($rcl_options['messages_mail'])&&$rcl_options['messages_mail'])? $rcl_options['messages_mail']: 0;

    $mess = $wpdb->get_results("SELECT author_mess,adressat_mess,content_mess,time_mess FROM ".RCL_PREF."private_message WHERE status_mess='0' && time_mess  > date_sub(now(), interval 1 hour)");

    if(!$mess) return false;

    foreach($mess as $m){
        $arrs[$m->adressat_mess][$m->author_mess]['time'] = $m->content_mess;
    }

    foreach($arrs as $add_id=>$vals){
        $mess = '';
        $to = get_the_author_meta('user_email',$add_id);

        $cnt = count($vals);

        foreach($vals as $auth_id=>$content){
            $url = rcl_format_url(get_author_posts_url($auth_id),'privat');
            $mess .= '<div style="overflow:hidden;clear:both;">
                <p>'.__('You were sent a private message','wp-recall').'</p>
                <div style="float:left;margin-right:15px;">'.get_avatar($auth_id,60).'</div>'
                . '<p>'.__('from the user','wp-recall').' '.get_the_author_meta('display_name',$auth_id).'</p>';
            
                if($mailtext) $mess .= '<p>'.$content.'</p>';
            
                $mess .= '<p>'.__('You can read the message by clicking on the link:','wp-recall').' <a href="'.$url.'">'.$url.'</a></p>'
                . '</div>';
        }
        if($cnt==1) $title = __('For you','wp-recall').' '.$cnt.' '.__('new message','wp-recall');
        else $title = __('For you','wp-recall').' '.$cnt.' '.__('new messages','wp-recall');
        rcl_mail($to, $title, $mess);
    }

}