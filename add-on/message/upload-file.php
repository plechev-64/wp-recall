<?php
add_action('wp_ajax_rcl_message_upload', 'rcl_message_upload');
function rcl_message_upload(){
	global $user_ID,$wpdb,$rcl_options;

	$adressat_mess = intval($_POST['talker']);
	$online = intval($_POST['online']);

	//print_r($_POST);
	//print_r($_FILES); exit;

	if(!$user_ID) exit;

		if($rcl_options['file_limit']){
			$file_num = $wpdb->get_var($wpdb->prepare("SELECT COUNT(ID) FROM ".RCL_PREF."private_message WHERE author_mess = '%d' AND status_mess = '4'",$user_ID));
			if($file_num>$rcl_options['file_limit']){
				$log['recall']=150;
				echo json_encode($log);
				exit;
			}
		}

		rcl_update_timeaction_user();

		$time = current_time('mysql');

		$mime = explode('/',$_FILES['filedata']['type']);

		$name = explode('/',str_replace('\\','/',untrailingslashit($_FILES['filedata']['tmp_name'])));
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

		$file_path = $path_temp.$t_name.'.'.$type;
		//echo $file_path;exit;

		if($mime[0]!='video'&&$mime[0]!='image'&&$mime[0]!='audio'){

			$archive_name = $t_name.'.zip';
			$arhive_path = $path_temp.$archive_name;
			$file_url = rcl_path_to_url($arhive_path);

			$zip = new ZipArchive;
			if ($zip -> open($arhive_path, ZipArchive::CREATE) === TRUE){
				$zip->addFile($_FILES['filedata']['tmp_name'], $file_name);
				$zip->close();
			} else {
				print_r($_FILES); exit;
			}

		}else{

			if($type=='php'||$type=='html') exit;
			move_uploaded_file($_FILES['filedata']['tmp_name'], $file_path);
			$file_url = rcl_path_to_url($file_path);
		}

		$wpdb->insert(
			RCL_PREF.'private_message',
				array(
				'author_mess' => $user_ID,
				'content_mess' => $file_url,
				'adressat_mess' => $adressat_mess,
				'time_mess' => $time,
				'status_mess' => 4
			)
		);

		$result = $wpdb->get_var($wpdb->prepare("SELECT ID FROM ".RCL_PREF."private_message WHERE author_mess = '%d' AND content_mess = '%s'",$user_ID,$file_url));

		if ($result) {

			$file_url = wp_nonce_url(get_bloginfo('wpurl').'/?rcl-download-id='.base64_encode($result), 'user-'.$user_ID );

			$log['recall']=100;
			$log['time'] = $time;

		}else{
			$log['recall']=120;
		}

		echo json_encode($log);
		exit;
}
?>