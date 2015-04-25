<?php
$path_parts = pathinfo(__FILE__);
preg_match_all("/(?<=)[A-z0-9\-\/\.\_\s\�]*(?=wp\-content)/i", $path_parts['dirname'], $string_value);
require_once( $string_value[0][0].'/wp-load.php' );

	global $user_ID,$wpdb,$rcl_options;
	$adressat_mess = $_POST['talker'];
	$online = $_POST['online'];

	//print_r($_POST);
	//print_r($_FILES); exit;

	if(!$user_ID) exit;

		if($rcl_options['file_limit']){
			$file_num = $wpdb->get_var("SELECT COUNT(ID) FROM ".RCL_PREF."private_message WHERE author_mess = '$user_ID' AND status_mess = '4'");
			if($file_num>$rcl_options['file_limit']){
				$log['recall']=150;
				echo json_encode($log);
				exit;
			}
		}

		rcl_update_timeaction_user();

		$time = current_time('mysql');

		$mime = explode('/',$_FILES['filedata']['type']);

		$name = explode('/',$_FILES['filedata']['tmp_name']);
		$cnt = count($name);
		$t_name = $name[--$cnt];

		$file_name = $_FILES['filedata']['name'];
		$type = substr($file_name, -4);
		if ( false !== strpos($type, '.') ) $type = substr($file_name, -3);

		$upload_dir = wp_upload_dir();
		$path_temp = $upload_dir['basedir'].'/temp-files/';
		if(!is_dir($path_temp)){
			mkdir($path_temp);
			chmod($path_temp, 0755);
		}

		$file = $path_temp.$t_name.'.'.$type;

		if($mime[0]!='video'&&$mime[0]!='image'&&$mime[0]!='audio'){

			$arch_name = $t_name.'.zip';
			$url_arhive = $path_temp.$arch_name;
			$url_file = get_bloginfo('wpurl').'/wp-content/uploads/temp-files/'.$arch_name;

			$zip = new ZipArchive;
			if ($zip -> open($url_arhive, ZipArchive::CREATE) === TRUE){
				$zip->addFile($_FILES['filedata']['tmp_name'], $file_name);
				$zip->close();
			} else {
				print_r($_FILES); exit;
			}

		}else{
			$filename = $t_name.'.'.$type;
                        if($type=='php'||$type=='html') exit;
			move_uploaded_file($_FILES['filedata']['tmp_name'], $path_temp.$filename);
			$url_file = get_bloginfo('wpurl').'/wp-content/uploads/temp-files/'.$filename;
		}

		$wpdb->insert(
			RCL_PREF.'private_message',
				array(
				'author_mess' => "$user_ID",
				'content_mess' => "$url_file",
				'adressat_mess' => "$adressat_mess",
				'time_mess' => $time,
				'status_mess' => 4
			)
		);

		$result = $wpdb->get_var("SELECT ID FROM ".RCL_PREF."private_message WHERE author_mess = '$user_ID' AND content_mess = '$url_file'");

		if ($result) {

			$url_file = wp_nonce_url(get_bloginfo('wpurl').'/wp-content/plugins/recall/add-on/message/download.php?fileid='.$result, 'user-'.$user_ID );

			$log['recall']=100;
			$log['time'] = $time;

		}else{
			$log['recall']=120;
		}

		echo json_encode($log);
		exit;

?>