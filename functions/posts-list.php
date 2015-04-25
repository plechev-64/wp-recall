<?php
require_once( '../load-rcl.php' );

function raytout($rayt){
	if($rayt>0){$class="rayt-plus";$rayt='+'.$rayt;}
	elseif($rayt<0)$class="rayt-minus";
	else{$class="null";$rayt=0;}
	return '<span class="'.$class.'">'.$rayt.'</span>';
}

function get_rayting_block_rcl($rayt){
	return '<span title="'.__('rating','rcl').'" class="rayting-rcl">'.raytout($rayt).'</span>';
}

global $wpdb;

	$type = $_POST['type'];
	$start = $_POST['start'];
	$author_lk = $_POST['id_user'];

	$rcl_options = get_option('primary-rcl-options');

	$start .= ',';

	//$edit_url = get_redirect_url_rcl('/?page='.$rcl_options['public_form_page_rcl']);

	$posts = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."posts WHERE post_author='$author_lk' AND post_type='$type' AND post_status NOT IN ('draft','auto-draft') ORDER BY post_date DESC LIMIT $start 20");
		$p_list='';
		$b=0;
		foreach((array)$posts as $p){if(++$b>1) $p_list .= ',';$p_list .= $p->ID;}

		$rayt_p = $wpdb->get_results("SELECT * FROM ".RCL_PREF."total_rayting_posts WHERE post_id IN ($p_list)");
		if($rayt_p) foreach((array)$rayt_p as $r){$rayt[$r->post_id] = $r->total;}

		$posts_block ='<table class="publics-table-rcl">
		<tr>
			<td>'.__('Date','rcl').'</td><td>'.__('Title','rcl').'</td><td>'.__('Status','rcl').'</td>';
			//if($user_ID==$author_lk) $posts_block .= '<td>Ред.</td>';
			$posts_block .= '</tr>';
		foreach((array)$posts as $post){
			$date = date("d.m.y", strtotime($post->post_date));
			if($post->post_status=='pending') $status = '<span class="pending">'.__('on approval','rcl').'</span>';
			elseif($post->post_status=='trash') $status = '<span class="pending">'.__('deleted','rcl').'</span>';
			else $status = '<span class="publish">'.__('publish','rcl').'</span>';
			$posts_block .= '<tr>
			<td width="50">'.$date.'</td><td><a target="_blank" href="'.$post->guid.'">'.$post->post_title.'</a>';
			if($rayt_p) $posts_block .= ' '.get_rayting_block_rcl($rayt[$post->ID]);
			$posts_block .= '</td><td>'.$status.'</td>';
			//if($user_ID==$author_lk) $posts_block .= '<td><a target="_blank" href="'.$edit_url.'rcl-post-edit='.$post->ID.'">Ред.</a></td>';
			$posts_block .= '</tr>';
		}
		$posts_block .= '</table>';

	$log['post_content']=$posts_block;
	$log['recall']=100;

	echo json_encode($log);
    exit;