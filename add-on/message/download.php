<?php
if ( !isset( $_GET['fileid'] ) ) return false;

$path_parts = pathinfo(__FILE__);
$url_ar = explode('/',$path_parts['dirname']);
for($a=count($url_ar);$a>=0;$a--){if($url_ar[$a]=='wp-content'){ $path .= 'wp-load.php'; break; }else{ $path .= '../'; }}
require_once( $path );

global $user_ID,$wpdb;

	if ( !$user_ID||!wp_verify_nonce( $_GET['_wpnonce'], 'user-'.$user_ID ) ) return false;
	
	$file = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."rcl_private_message WHERE ID = '".$_GET['fileid']."' AND adressat_mess = '$user_ID' AND status_mess = '5'");
	
	if(!$file) wp_die(__('Файла нет на сервере или он уже был загружен!'));
	
	$name = explode('/',$file->content_mess);
	$cnt = count($name);
	$f_name = $name[--$cnt];
	
	$wpdb->update( RCL_PREF.'private_message',array( 'status_mess' => 6,'content_mess' => __('Файл был загружен.') ),array( 'ID' => $file->ID ));
	
	header('Content-Description: File Transfer');
	header('Content-Disposition: attachment; filename="'.$f_name.'"');
	header('Content-Type: application/octet-stream; charset=utf-8');
	readfile($file->content_mess);
	
	$upload_dir = wp_upload_dir();
	$path_temp = $upload_dir['basedir'].'/temp-files/'.$f_name;
	unlink($path_temp);
	
	exit;
?>