<?php
class Rcl_Public{
	function __construct() {
		add_action('wp_ajax_get_media', array(&$this, 'get_media'));
	}
	function get_media(){
		global $user_ID,$wpdb;
                $page = 1;
		if(isset($_POST['page'])) $page = intval($_POST['page']);
		if($user_ID){

			$where = $wpdb->prepare("WHERE post_author='%d' AND post_type='attachment' AND post_mime_type LIKE '%s'",$user_ID,'image%');
			$cnt = $wpdb->get_var("SELECT COUNT(ID) FROM ".$wpdb->prefix."posts $where");
			$rclnavi = new RCL_navi(20,$cnt,false,$page);
			$limit_us = $rclnavi->limit();

			$medias = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."posts $where ORDER BY ID DESC LIMIT $limit_us");

                        $custom_url = '<div id="custom-image-url" style="padding: 10px;">
                                        <h3>'.__('The URL to the image','rcl').':</h3>
                                        <input type="text" id="custom-url" name="custom-url" value="">

                                        <input type="button" onclick="add_custom_image_url();return false;" class="recall-button" value="'.__('Insert image','rcl').'">
                                        <script type="text/javascript">
                                            function add_custom_image_url(){
                                                var url = jQuery("#custom-url").val();
                                                var image = "<img class=alignleft src="+url+">";
                                                var ifr = jQuery("#contentarea_ifr").contents().find("#tinymce").html();
                                                jQuery("#contentarea").insertAtCaret(image+"&nbsp;");
                                                jQuery("#contentarea_ifr").contents().find("#tinymce").focus().html(ifr+image+"&nbsp;");
                                                return false;
                                            }
                                        </script>
                                    </div>';

			if($medias){
                            $fls .= '<div id="user-media-list">';
				$fls = '<span class="close-popup"></span>
                                    '.$custom_url.'
                                    <div id="user-medias" style="padding: 10px;">
                                        <h3>'.__('Media library user','rcl').':</h3>
					<ul class="media-list">';
				foreach($medias as $m){
					$fls .= '<li>'.rcl_get_insert_image($m->ID).'</li>';
				}
				$fls .= '</ul>'
                                    . '</div>';
				$fls .= $rclnavi->navi();
                                $fls .= '</div>';
				$log['result']=100;
				$log['content']= $fls;
			}else{
				$log['result']=100;
				$log['content']= $custom_url.'<div class="clear"><h3 align="center">'.__('Images in the media library is not found!','rcl').'</h3>
				<p class="aligncenter">'.__('Upload to your image and you will be able to use them in future from your media library.','rcl').'</p></div>';
			}
		}
		echo json_encode($log);
		exit;
	}

}
$Rcl_Public = new Rcl_Public();

add_action('wp_ajax_rcl_ajax_delete_post', 'rcl_ajax_delete_post');
function rcl_ajax_delete_post(){
	global $user_ID;

	if(!$user_ID) return false;

	$post = get_post(intval($_POST['post_id']));
	$res = wp_delete_post( $post->ID );

	if($res){
		$temp_gal = get_user_meta($user_ID,'tempgallery',1);
		if($temp_gal){
			$cnt = count($temp_gal);
			foreach((array)$temp_gal as $key=>$gal){ if($gal['ID']==$_POST['post_id']) unset($temp_gal[$key]); }
			foreach((array)$temp_gal as $t){ $new_temp[] = $t; }
			if($new_temp) update_user_meta($user_ID,'tempgallery',$new_temp);
			else delete_user_meta($user_ID,'tempgallery');
		}

		$log['result']=100;
		$log['post_type']=$post->post_type;
	}else {
		$log['result']=1;
	}

	echo json_encode($log);
    exit;
}

add_action('wp_ajax_rcl_get_edit_postdata', 'rcl_get_edit_postdata');
function rcl_get_edit_postdata(){
	global $user_ID;
	$post_id = intval($_POST['post_id']);
	$post = get_post($post_id);

	if($user_ID){
		$log['result']=100;
		$log['content']= "
		<form id='rcl-edit-form' method='post'>
			<label>".__("Name",'rcl').":</label>
			 <input type='text' name='post_title' value='$post->post_title'>
			 <label>".__("Description",'rcl').":</label>
			 <textarea name='post_content' rows='10'>$post->post_content</textarea>
			 <input type='hidden' name='post_id' value='$post_id'>
		</form>";
	}
	echo json_encode($log);
	exit;
}

add_action('wp_ajax_rcl_edit_postdata', 'rcl_edit_postdata');
function rcl_edit_postdata(){
	global $user_ID,$wpdb;
	if(!$user_ID) exit;

	$post_array = array();
	$post_array['post_title'] = sanitize_text_field($_POST['post_title']);
	$post_array['post_content'] = esc_textarea($_POST['post_content']);

	$post_array = apply_filters('rcl_pre_edit_post',$post_array);

	$result = $wpdb->update(
		$wpdb->prefix.'posts',
		$post_array,
		array('ID'=>intval($_POST['post_id']))
	);

	if($result){
		$log['result']=100;
	}
	echo json_encode($log);
	exit;
}

function rcl_button_fast_edit_post($post_id){
	return '<a class="rcl-edit-post rcl-service-button" data-post="'.$post_id.'" onclick="rcl_edit_post(this); return false;"><i class="fa fa-pencil-square-o"></i></a>';
}

function rcl_button_fast_delete_post($post_id){
	return '<a class="rcl-delete-post rcl-service-button" data-post="'.$post_id.'" onclick="rcl_delete_post(this); return false;"><i class="fa fa-trash"></i></a>';
}