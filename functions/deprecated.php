<?php
/*14.0.0*/
add_action('wp_head','rcl_head_js_data',1);
function rcl_head_js_data(){
    global $user_ID;
    $data = "<script>
	var user_ID = $user_ID;
	var wpurl = '".preg_quote(trailingslashit(get_bloginfo('wpurl')),'/:')."';
	var rcl_url = '".preg_quote(RCL_URL,'/:')."';
	</script>\n";
    echo $data;
}

/*14.0.0*/
_deprecated_function( 'rcl_get_user_money', '14.0.0', 'rcl_get_user_balance' );
function rcl_get_user_money($user_id=false){
    global $wpdb,$user_ID;
    if(!$user_id) $user_id = $user_ID;
    return $wpdb->get_var($wpdb->prepare("SELECT user_balance FROM ".RMAG_PREF."users_balance WHERE user_id='%d'",$user_id));
}

/*14.0.0*/
_deprecated_function( 'rcl_update_user_money', '14.0.0', 'rcl_update_user_balance' );
function rcl_update_user_money($newmoney,$user_id=false){
    global $user_ID,$wpdb;
    if(!$user_id) $user_id = $user_ID;

    $money = rcl_get_user_money($user_id);

    if(isset($money)) return $wpdb->update(RMAG_PREF .'users_balance',
            array( 'user_balance' => $newmoney ),
            array( 'user_id' => $user_id )
        );

    return rcl_add_user_money($newmoney,$user_id);
}

/*14.0.0*/
_deprecated_function( 'rcl_add_user_money', '14.0.0', 'rcl_add_user_balance' );
function rcl_add_user_money($money,$user_id=false){
    global $wpdb,$user_ID;
    if(!$user_id) $user_id = $user_ID;
    return $wpdb->insert( RMAG_PREF .'users_balance',
	array( 'user_id' => $user_id, 'user_balance' => $money ));
}