<?php
class Rcl_Public{
	function __construct() {
		add_action('wp_ajax_get_media', array(&$this, 'get_media'));
		add_action('wp_ajax_step_one_redactor_post', array(&$this, 'step_one_redactor_post'));
		add_action('wp_ajax_step_two_redactor_post', array(&$this, 'step_two_redactor_post'));
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

	function step_one_redactor_post(){
		global $user_ID,$wpdb;
		$post_id = intval($_POST['post_id']);
		$post_array = $wpdb->get_row($wpdb->prepare("SELECT * FROM ".$wpdb->prefix."posts WHERE ID='%d'",$post_id));
		$title = $post_array->post_title;
		$content = $post_array->post_content;

		if($user_ID){
			 $log['result']=100;
			 $log['content']= "
			 <div style='display:block;' class='float-window-recall'>
				 <a href='#' class='close-popup'></a>
				 <h3>".__("Name",'rcl').":</h3>
				 <input type='text' name='post_title' id='post_title_edit' style='width:95%;' value='".$title."'>
				 <h3>".__("Description",'rcl').":</h3>
				 <textarea name='post_content' id='content_area_edit' rows='10' style='width:95%;'>".$content."</textarea>
				 <input type='hidden' id='post_id_edit'   value='".$post_id."'>
				 <input type='button' class='recall-button updatesteptwo' style='float:right;' value='".__("Update",'rcl')."'>
			 </div>";
		}
		echo json_encode($log);
		exit;
	}
	function step_two_redactor_post(){
		global $user_ID,$wpdb;
		if(!$user_ID) exit;

		$post_id = intval($_POST['post_id']);
		$post_title = sanitize_text_field($_POST['post_title']);
		$post_content = esc_textarea($_POST['post_content']);

		$post_array = array();
		$post_array['post_title'] = $post_title;
		$post_array['post_content'] = $post_content;

		$post_array = apply_filters('rcl_pre_edit_post',$post_array);

		$result = $wpdb->update(
			$wpdb->prefix.'posts',
			$post_array,
			array('ID'=>$post_id)
		);

		if($result){
			$log['post_id']=$post_id;
			$log['post_title']=$post_title;
			$log['post_content']=$post_content;
			$log['otvet']=100;
		}
		echo json_encode($log);
		exit;
	}
}
$Rcl_Public = new Rcl_Public();