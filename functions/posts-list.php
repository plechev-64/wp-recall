<?php
add_action('wp_ajax_rcl_posts_list', 'rcl_posts_list');
add_action('wp_ajax_nopriv_rcl_posts_list', 'rcl_posts_list');
function rcl_posts_list(){

	global $wpdb;

	$type = sanitize_text_field($_POST['type']);
	$start = intval($_POST['start']);
	$author_lk = intval($_POST['id_user']);

	$rcl_options = get_option('primary-rcl-options');

	$start .= ',';

	$posts = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".$wpdb->prefix."posts WHERE post_author='%d' AND post_type='%s' AND post_status NOT IN ('draft','auto-draft') ORDER BY post_date DESC LIMIT $start 20",$author_lk,$type));
		$p_list='';

		foreach((array)$posts as $p){ $p_list[] = $p->ID;}

		$rayt_p = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".RCL_PREF."total_rayting_posts WHERE post_id IN (".rcl_format_in($p_list).")",$p_list));
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
			if($rayt_p) $posts_block .= ' '.rcl_get_rating_block($rayt[$post->ID]);
			$posts_block .= '</td><td>'.$status.'</td>';
			//if($user_ID==$author_lk) $posts_block .= '<td><a target="_blank" href="'.$edit_url.'rcl-post-edit='.$post->ID.'">Ред.</a></td>';
			$posts_block .= '</tr>';
		}
		$posts_block .= '</table>';

	$log['post_content']=$posts_block;
	$log['recall']=100;

	echo json_encode($log);
    exit;
}