<?php
require_once( '../../load-rcl.php' );

if(isset($_GET['lk'])){
	$user_lk = esc_sql($_GET['lk']);
	$user_ID = esc_sql($_GET['user']);
	
	if($user_lk){
		$where1 = "WHERE author_mess='$user_lk' AND adressat_mess = '$user_ID' AND status_mess ='0' OR author_mess = '$user_lk' AND adressat_mess = '$user_ID' AND status_mess = '4'";
		$where2 = "WHERE author_mess = '$user_ID' AND adressat_mess = '$user_lk' AND status_mess = '0' OR author_mess = '$user_ID' AND adressat_mess = '$user_lk' AND status_mess = '4'";
	}else{
		$where1 = "WHERE adressat_mess = '$user_ID' AND status_mess ='0' OR adressat_mess = '$user_ID' AND status_mess = '4'";
		$where2 = "WHERE author_mess = '$user_ID' AND status_mess = '0' OR author_mess = '$user_ID' AND status_mess = '4'";
	}
	
	$mess_ID = $wpdb->get_var("SELECT ID FROM ".$wpdb->prefix."rcl_private_message $where1");
	
	$no_read_mess = $wpdb->get_var("SELECT COUNT(ID) FROM ".$wpdb->prefix."rcl_private_message $where2");
	
	//echo $where;
	
	
	if(!$mess_ID) $mess_ID = 0;

	echo $mess_ID.'|'.$no_read_mess; exit;
			
	echo json_encode($log);	
	
}else{
	$mess_ID = $wpdb->get_var("SELECT ID FROM ".RCL_PREF."private_message WHERE adressat_mess = '$user_ID' AND status_mess ='0'");
	if(!$mess_ID) exit;
	echo $mess_ID;
}


exit;							
	
?>