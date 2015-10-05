<?php
include_once('classes.php');
include_once('fast-editor.php');
include_once('upload-file.php');
include_once 'addon-options.php';

rcl_enqueue_style('publics',__FILE__);

//if (!session_id()) { session_start(); }

if (!is_admin()):
	add_action('wp_enqueue_scripts','rcl_publics_scripts');
endif;

function rcl_publics_scripts(){
	global $rcl_options;
	//if($rcl_options['media_downloader_recall']!=1) return false;
	wp_enqueue_script( 'jquery' );
	wp_enqueue_script( 'rcl_publics_scripts', rcl_addon_url('js/scripts.js', __FILE__) );
}

function rcl_autocomplete_scripts(){
	wp_enqueue_style( 'rcl_autocomplete_scripts', rcl_addon_url('js/magicsuggest/magicsuggest-min.css', __FILE__) );
	wp_enqueue_script( 'jquery' );
	wp_enqueue_script( 'rcl_autocomplete_scripts', rcl_addon_url('js/magicsuggest/magicsuggest-min.js', __FILE__) );
}

if (!is_admin()):
	add_filter('the_content','rcl_post_gallery',10);
endif;

if (!is_admin()||defined('DOING_AJAX'))add_filter('the_content','rcl_author_info',100);

add_action('admin_menu', 'rcl_admin_page_publicform',30);
function rcl_admin_page_publicform(){
	add_submenu_page( 'manage-wprecall', __('Form of publication','rcl'), __('Form of publication','rcl'), 'manage_options', 'manage-public-form', 'rcl_manage_publicform');
}

add_filter('taxonomy_public_form_rcl','rcl_taxonomy_public_post');
function rcl_taxonomy_public_post($tax){
    if (!isset($tax['post'])) $tax['post'] = 'category';
    return $tax;
}

//add_filter('after_public_form_rcl','rcl_saveform_data_script',10,2);
function rcl_saveform_data_script($content,$data){
    $idform = 'form-'.$data->post_type.'-';
    $idform .= ($data->post_id)? $data->post_id : 0;
    $content .= '<script type="text/javascript" src="'.rcl_addon_url('js/sisyphus.min.js',__FILE__).'"></script>'
            . '<script>jQuery( function() { jQuery( "#'.$idform.'" ).sisyphus({timeout:10}) } );</script>';
    return $content;
}

add_action('init','rcl_add_postlist_posts');
function rcl_add_postlist_posts(){
    rcl_postlist('rcl','post',__('Records','rcl'),array('order'=>30));
}

add_action('init','rcl_init_publics_block');
function rcl_init_publics_block(){
	global $rcl_options;
	if($rcl_options['publics_block_rcl']==1){
                $view = 0;
                if($rcl_options['view_publics_block_rcl']) $view = $rcl_options['view_publics_block_rcl'];
                rcl_tab('publics','rcl_tab_publics',__('Posts','rcl'),array('public'=>$view,'class'=>'fa-list','order'=>50));
	}
	if($rcl_options['output_public_form_rcl']==1){
                rcl_tab('postform','rcl_tab_postform',__('Publication','rcl'),array('class'=>'fa-pencil','order'=>60,'path'=>__FILE__));
	}
}

add_filter('pre_update_postdata_rcl','rcl_update_postdata_excerpt');
function rcl_update_postdata_excerpt($postdata){
	if(!isset($_POST['post_excerpt'])) return $postdata;
	$postdata['post_excerpt'] = sanitize_text_field($_POST['post_excerpt']);
	return $postdata;
}

add_filter('pre_update_postdata_rcl','rcl_update_postdata_tags');
function rcl_update_postdata_tags($postdata){

	if(!isset($_POST['post_tags'])&&!isset($_POST['tags'])) return $postdata;

	$tags = array();

	if($_POST['post_tags']){
		$posttags = $_POST['post_tags'];
		$tags = explode(',',$posttags);
		$tags = array_map('trim', $tags);
	}

	if($_POST['tags']){
		$tags = array_merge ( $tags, $_POST['tags'] );
	}

	if($tags) $postdata['tags_input'] = $tags;

	return $postdata;
}

function rcl_tab_postform($author_lk){
	global $user_ID,$rcl_options;
	if($user_ID!=$author_lk) return false;
        $id_form = 1;
        if(isset($rcl_options['form-lk'])&&$rcl_options['form-lk']) $id_form = $rcl_options['form-lk'];
	return do_shortcode('[public-form id="'.$id_form.'"]');
}

function rcl_ajax_tab_publics($array_tabs){
    $array_tabs['publics']='rcl_tab_publics';
    return $array_tabs;
}
add_filter('ajax_tabs_rcl','rcl_ajax_tab_publics');

function rcl_tab_publics($author_lk){
	global $user_ID;
        $p_button='';
	$p_button = apply_filters('posts_button_rcl',$p_button,$author_lk);
	$posts_block = '<div class="rcl-menu">'.$p_button.'</div>';
        $p_block='';
	$p_block = apply_filters('posts_block_rcl',$p_block,$author_lk);
	$posts_block .= $p_block;

	return $posts_block;
}

function rcl_get_postlist_page(){
	global $wpdb;

	$type = sanitize_text_field($_POST['type']);
	$start = intval($_POST['start']);
	$author_lk = intval($_POST['id_user']);

	$start .= ',';

	//$edit_url = rcl_format_url(get_permalink($rcl_options['public_form_page_rcl']));

	$posts = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".$wpdb->prefix."posts WHERE post_author='%d' AND post_type='%s' AND post_status NOT IN ('draft','auto-draft') ORDER BY post_date DESC LIMIT $start 20",$author_lk,$type));

		$rayting = false;
		if(function_exists('rcl_get_rating_block')){
                        $b=0;
			foreach((array)$posts as $p){if(++$b>1) $p_list .= ',';$p_list .= $p->ID;}
			$rayt_p = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".RCL_PREF."total_rayting_posts WHERE post_id IN ($p_list)",$p_list));
			foreach((array)$rayt_p as $r){$rayt[$r->post_id] = $r->total;}
			$rayting = true;
		}

		$posts_block .='<table class="publics-table-rcl">
		<tr>
			<td>'.__('Date','rcl').'</td><td>'.__('Title','rcl').'</td><td>'.__('Status','rcl').'</td>';
			//if($user_ID==$author_lk) $posts_block .= '<td>Ред.</td>';
			$posts_block .= '</tr>';
		foreach((array)$posts as $post){
			if($post->post_status=='pending') $status = '<span class="pending">'.__('on approval','rcl').'</span>';
			elseif($post->post_status=='trash') $status = '<span class="pending">'.__('deleted','rcl').'</span>';
			else $status = '<span class="publish">'.__('publish','rcl').'</span>';
			$posts_block .= '<tr>
			<td>'.mysql2date('d-m-Y', $post->post_date).'</td><td><a target="_blank" href="'.$post->guid.'">'.$post->post_title.'</a>';
			if($rayting) $posts_block .= ' '.rcl_get_rating_block($rayt[$post->ID]);
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
add_action('wp_ajax_rcl_get_postlist_page', 'rcl_get_postlist_page');
add_action('wp_ajax_nopriv_rcl_get_postlist_page', 'rcl_get_postlist_page');

function rcl_manage_publicform(){
	global $wpdb;

    rcl_sortable_scripts();

	$form = (isset($_GET['form'])) ? $_GET['form']: false;

	if(isset($_POST['delete-form'])&&wp_verify_nonce( $_POST['_wpnonce'], 'update-public-fields' )){
            $id_form = intval($_POST['id-form']);
            $_GET['status'] = 'old';
            $wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->prefix."options WHERE option_name LIKE 'custom_public_fields_%d'",$id_form));
	}

	if(!$form){
		$option_name = $wpdb->get_var("SELECT option_name FROM ".$wpdb->prefix."options WHERE option_name LIKE 'custom_public_fields%'");
		if($option_name) $form = preg_replace("/[a-z_]+/", '', $option_name);
		else $form = 1;
	}

        include_once RCL_PATH.'functions/rcl_editfields.php';
        $f_edit = new Rcl_EditFields('post',array('id'=>$form,'custom-slug'=>1,'terms'=>1));

	if($f_edit->verify()){
            $_GET['status'] = 'old';
            $fields = $f_edit->update_fields();
	}

	$custom_public_form_data = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."options WHERE option_name LIKE 'custom_public_fields%'");

	if($custom_public_form_data){
		$form_navi = '<h3>'.__('Available forms','rcl').'</h3><div class="form-navi">';
		foreach((array)$custom_public_form_data as $form_data){
			$id_form = preg_replace("/[a-z_]+/", '', $form_data->option_name);
			if($form==$id_form) $class = 'button-primary';
			else $class = 'button-secondary';
			$form_navi .= '<input class="'.$class.'" type="button" onClick="document.location=\''.admin_url('admin.php?page=manage-public-form&form='.$id_form).'\';" value="ID:'.$id_form.'" name="public-form-'.$id_form.'">';
		}
		if(!isset($_GET['status'])||$_GET['status']!='new') $form_navi .= '<input class="button-secondary" type="button" onClick="document.location=\''.admin_url('admin.php?page=manage-public-form&form='.++$id_form.'&status=new').'\';" value="'.__('To add another form').'" name="public-form-'.$id_form.'">';
		$form_navi .= '</div>

		<h3>'.__('Form ID','rcl').':'.$form.' </h3>';
		if(!isset($_GET['status'])||$_GET['status']!='new') $form_navi .= '<form method="post" action="">
			'.wp_nonce_field('update-public-fields','_wpnonce',true,false).'
			<input class="button-primary" type="submit" value="'.__('To remove all fields','rcl').'" onClick="return confirm(\''.__('Вы уверены?','rcl').'\');" name="delete-form">
			<input type="hidden" value="'.$form.'" name="id-form">
		</form>';
	}else{
		$form = 1;
		$form_navi = '<h3>'.__('Form ID','rcl').':'.$form.' </h3>';
	}

	$users_fields = '<h2>'.__('Arbitrary form fields publishing','rcl').'</h2>
	<small>Для размещения формы публикации используем шорткод [public-form]</small><br>
        <small>Можно создавать разный набор произвольных полей для разных форм.<br>
        Чтобы вывести определенный набор полей через шорткод следует указать идентификатор формы, например, [public-form id="2"]</small><br>
	<small>Форма публикации уже содержит обязательные поля для заголовка записи, контента, ее категории и указания метки.</small><br>
	'.$form_navi.'
	'.$f_edit->edit_form(array(
            $f_edit->option('select',array(
                'name'=>'requared',
                'notice'=>__('required field','rcl'),
                'value'=>array(__('No','rcl'),__('Yes','rcl'))
            ))
        )).'
	<p>Чтобы вывести все данные занесенные в созданные произвольные поля формы публикации внутри опубликованной записи можно воспользоваться функцией<br />
	<b>rcl_get_custom_post_meta($post_id)</b><br />
	Разместите ее внутри цикла и передайте ей идентификатор записи первым аргументом<br />
	Также можно вывести каждое произвольное поле в отдельности через функцию<br />
	<b>get_post_meta($post_id,$slug,1)</b><br />
	где<br />
	$post_id - идентификатор записи<br />
	$slug - ярлык произвольного поля формы</p>';
	echo $users_fields;
}

//формируем галерею записи
function rcl_post_gallery($content){
	global $post;
	if(get_post_meta($post->ID, 'recall_slider', 1)!=1||!is_single()||$post->post_type=='products') return $content;
	$gallery = do_shortcode('[gallery-rcl post_id="'.$post->ID.'"]');
	return $gallery.$content;
}

function rcl_get_like_tags(){
	global $wpdb;

	if(!$_POST['query']){
		echo 1;
		exit;
	};
	$query = $_POST['query'];

	$terms = get_terms( 'post_tag', array('hide_empty'=>false,'name__like'=>$query) );

	$tags = array();
	foreach($terms as $key=>$term){
		$tags[$key]['id'] = $term->name;
		$tags[$key]['name'] = $term->name;
	}

	echo json_encode($tags);
    exit;
}
add_action('wp_ajax_rcl_get_like_tags','rcl_get_like_tags');
add_action('wp_ajax_nopriv_rcl_get_like_tags','rcl_get_like_tags');

add_shortcode('gallery-rcl','rcl_shortcode_gallery');
function rcl_shortcode_gallery($atts, $content = null){
    global $post;

    rcl_bxslider_scripts();

    extract(shortcode_atts(array(
            'post_id' => false
    ),
    $atts));

    $post_id = $post->ID;

    $args = array(
            'post_parent' => $post_id,
            'post_type'   => 'attachment',
            'numberposts' => -1,
            'post_status' => 'any',
            'post_mime_type'=> 'image'
    );
    $childrens = get_children($args);

    if( $childrens ){
            $gallery = '<ul class="rcl-gallery">';
            foreach((array) $childrens as $children ){
                    $large = wp_get_attachment_image_src( $children->ID, 'large' );
                    $gallery .= '<li><a class="fancybox" href="'.$large[0].'"><img src="'.$large[0].'"></a></li>';
                    $thumbs[] = $large[0];
            }
            $gallery .= '</ul>';

            if(count($thumbs)>1){
                    $gallery .= '<div id="bx-pager">';
                            foreach($thumbs as $k=>$src ){
                                    $gallery .= '<a data-slide-index="'.$k.'" href=""><img src="'.$src.'" /></a>';
                            }
                    $gallery .= '</div>';
            }
    }

    return $gallery;
}

//Выводим инфу об авторе записи в конце поста
function rcl_author_info($content){
	global $post,$rcl_options;
	if($rcl_options['info_author_recall']!=1) return $content;
	if(!is_single()) return $content;
	if($post->post_type=='page') return $content;
	$out = rcl_get_author_block();
        if($post->post_type=='task') return $out.$content;
	return $content.$out;
}

function rcl_get_basedir_image($path){
	$dir = explode('/',$path);
	$cnt = count($dir) - 2;
	for($a=0;$a<=$cnt;$a++){
		$base_path .= $dir[$a].'/';
	}
	return $base_path;
}

/*deprecated*/
function rcl_get_image_gallery($atts,$content=null){
	global $post;
	extract(shortcode_atts(array('id'=>'','size'=>'thumbnail'),$atts));
	if(!$id) return false;

	$upl_dir = wp_upload_dir();
	$meta = wp_get_attachment_metadata($id);

	if(!$meta) return false;

	$full = $upl_dir['baseurl'].'/'.$meta['file'];

	if($size=='full'){
		$img = '<img class="thumbnail full"  src="'.$full.'">';
	}else{

		$size_ar = explode(',',$size);
		if(isset($size_ar[1])){
			$img = get_the_post_thumbnail($post->ID,$size_ar);
		}else{
			$dir_img = rcl_get_basedir_image($meta['file']);
			$img = '<img class="thumbnail"  src="'.$upl_dir['baseurl'].'/'.$dir_img.'/'.$meta['sizes'][$size]['file'].'">';
		}

	}

	$image .= '<a href="'.$upl_dir['baseurl'].'/'.$meta['file'].'" rel="lightbox">';
	$image .= $img;
	$image .= '</a>';
	return $image;
}
add_shortcode('art','rcl_get_image_gallery');

function rcl_add_attachments_in_temps($id_post){
    global $user_ID;

    $temp_gal = get_user_meta($user_ID,'tempgallery',1);
    if($temp_gal){
            //$cnt = count($temp_gal);
            foreach((array)$temp_gal as $key=>$gal){
                    if($thumb[$gal['ID']]==1) add_post_meta($id_post, '_thumbnail_id', $gal['ID']);
                    wp_update_post( array('ID'=>$gal['ID'],'post_parent'=>$id_post) );
            }
            if($_POST['add-gallery-rcl']==1) add_post_meta($id_post, 'recall_slider', 1);
            delete_user_meta($user_ID,'tempgallery');

            if(!$thumb){
                $args = array(
                'post_parent' => $id_post,
                'post_type'   => 'attachment',
                'numberposts' => 1,
                'post_status' => 'any',
                'post_mime_type'=> 'image'
                );
                $child = get_children($args);
                if($child){ foreach($child as $ch){add_post_meta($id_post, '_thumbnail_id',$ch->ID);} }
            }
    }
    return $temp_gal;
}

function rcl_update_tempgallery($attach_id,$attach_url){
	global $user_ID;
	$temp_gal = get_user_meta($user_ID,'tempgallery',1);
	if(!$temp_gal||!is_array($temp_gal)) $temp_gal = array();
	$temp_gal[$attach_id]['ID'] = $attach_id;
	$temp_gal[$attach_id]['url'] = $attach_url;
	update_user_meta($user_ID,'tempgallery',$temp_gal);
	return $temp_gal;
}

function rcl_insert_attachment($attachment,$image,$id_post=false){
	$attach_id = wp_insert_attachment( $attachment, $image['file'], $id_post );
	$attach_data = wp_generate_attachment_metadata( $attach_id, $image['file'] );
	wp_update_attachment_metadata( $attach_id, $attach_data );

	if(!$id_post) rcl_update_tempgallery($attach_id,$image['url']);

	return rcl_get_html_attachment($attach_id,$attachment['post_mime_type']);
}

function rcl_get_html_attachment($attach_id,$mime_type){

    $editpost = $_GET['rcl-post-edit'];

	$mime = explode('/',$mime_type);

	$rt = "<li id='attachment-".$attach_id."'>
		".rcl_button_fast_delete_post($attach_id)."
		<label>
			".rcl_get_insert_image($attach_id,$mime[0]);
			if($mime[0]=='image') $rt .= "<span>
				<input type='checkbox' class='thumb-foto' ".checked(get_post_thumbnail_id( $editpost ),$attach_id,false)." id='thumb-".$attach_id."' name='thumb[".$attach_id."]' value='1'> - ".__('featured','rcl')."</span>";
		$rt .= "</label>
	</li>";
	return $rt;
}

function rcl_edit_post(){
    include_once 'rcl_editpost.php';
    $edit = new Rcl_EditPost();
}

function rcl_edit_post_activate ( ) {
  if ( isset( $_POST['edit-post-rcl'] )&&wp_verify_nonce( $_POST['_wpnonce'], 'edit-post-rcl' ) ) {
    add_action( 'wp', 'rcl_edit_post' );
  }
}
add_action('init', 'rcl_edit_post_activate');

function rcl_delete_post(){
	global $rcl_options,$user_ID;
	$post_id = wp_update_post( array('ID'=>intval($_POST['post-rcl']),'post_status'=>'trash'));
    do_action('after_delete_post_rcl',$post_id);
	wp_redirect(rcl_format_url(get_author_posts_url($user_ID)).'&public=deleted');
	exit;
}

function rcl_delete_post_activate ( ) {
  if ( isset( $_POST['delete-post-rcl'] )&&wp_verify_nonce( $_POST['_wpnonce'], 'delete-post-rcl' ) ) {
    add_action( 'wp', 'rcl_delete_post' );
  }
}
add_action('init', 'rcl_delete_post_activate');

add_action('wp','rcl_deleted_post_notice');
function rcl_deleted_post_notice(){
    if (isset($_GET['public'])&&$_GET['public']=='deleted') rcl_notice_text(__('The publication has been successfully removed!','rcl'),'warning');
}

add_action('after_delete_post_rcl','rcl_delete_notice_author_post');
function rcl_delete_notice_author_post($post_id){

	if(!$_POST['reason_content']) return false;

	$post = get_post($post_id);

	$subject = 'Ваша публикация удалена.';
	$textmail = '<h3>Публикация "'.$post->post_title.'" была удалена</h3>
	<p>Примечание модератора: '.$_POST['reason_content'].'</p>';
	rcl_mail(get_the_author_meta('user_email',$post->post_author),$subject,$textmail);
}

function rcl_publicform($atts, $content = null){
    include_once 'rcl_publicform.php';
    $form = new Rcl_PublicForm($atts);
    return $form->public_form();
}
add_shortcode('public-form','rcl_publicform');

add_action('admin_init', 'custom_fields_editor_post_rcl', 1);
function custom_fields_editor_post_rcl() {
    add_meta_box( 'custom_fields_editor_post', __('Arbitrary form fields publishing','rcl'), 'custom_fields_list_posteditor_rcl', 'post', 'normal', 'high'  );
}

function custom_fields_list_posteditor_rcl($post){
	echo rcl_get_list_custom_fields($post->ID); ?>
	<input type="hidden" name="custom_fields_nonce_rcl" value="<?php echo wp_create_nonce(__FILE__); ?>" />
	<?php
}

add_action('save_post', 'rcl_custom_fields_update', 0);
function rcl_custom_fields_update( $post_id ){
    if(!isset($_POST['custom_fields_nonce_rcl'])) return false;
    if ( !wp_verify_nonce($_POST['custom_fields_nonce_rcl'], __FILE__) ) return false;
	if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE  ) return false;
	if ( !current_user_can('edit_post', $post_id) ) return false;

	rcl_update_post_custom_fields($post_id);

	return $post_id;
}

function rcl_update_post_custom_fields($post_id,$id_form=false){

        require_once(ABSPATH . "wp-admin" . '/includes/image.php');
	require_once(ABSPATH . "wp-admin" . '/includes/file.php');
	require_once(ABSPATH . "wp-admin" . '/includes/media.php');

	$post = get_post($post_id);

	switch($post->post_type){
            case 'post':
                if(!$id_form){
                        $id_form = get_post_meta($post->ID,'publicform-id',1);
                        if(!$id_form) $id_form = 1;
                }
                $id_field = 'custom_public_fields_'.$id_form;
            break;
            case 'products': $id_field = 'custom_saleform_fields'; break;
            default: $id_field = 'custom_fields_'.$post->post_type;
	}

	$get_fields = get_option($id_field);

	if($get_fields){

            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);

            foreach((array)$get_fields as $custom_field){
                $slug = $custom_field['slug'];
                if($custom_field['type']=='checkbox'){
                    $select = explode('#',$custom_field['field_select']);
                    $count_field = count($select);
                    foreach($_POST[$slug] as $val){
                        for($a=0;$a<$count_field;$a++){
                            if($select[$a]==$val){
                                $vals[] = $val;
                            }
                        }
                    }
                    if($vals){
                        $res = update_post_meta($post_id, $slug, $vals);
                    }else{
                        delete_post_meta($post_id, $slug);
                    }

                }else if($custom_field['type']=='file'){

                    $attach_id = rcl_upload_meta_file($custom_field,$post->post_author,$post_id);
                    if($attach_id) update_post_meta($post_id, $slug, $attach_id);

                }else{

                    if($_POST[$slug]){
                        update_post_meta($post_id, $slug, $_POST[$slug]);
                    }else{
                        if(get_post_meta($post_id, $slug, 1)) delete_post_meta($post_id, $slug);
                    }

                }
            }
	}
}

function rcl_get_list_custom_fields($post_id,$posttype=false,$id_form=false){

	$get_fields = rcl_get_custom_fields($post_id,$posttype,$id_form);

	if(!$get_fields) return false;

        $public_fields = '';

        $data = array(
            'ID'=>$post_id,
            'post_type'=>$posttype,
            'form_id'=>$id_form
        );

        $cf = new Rcl_Custom_Fields();

	foreach((array)$get_fields as $key=>$custom_field){
                if($key==='options') continue;

                $custom_field = apply_filters('custom_field_public_form',$custom_field,$data);

                $star = ($custom_field['requared']==1)? ' <span class="required">*</span> ': '';
		$postmeta = ($post_id)? get_post_meta($post_id,$custom_field['slug'],1):'';

		$public_fields .= '<tr><th><label>'.$cf->get_title($custom_field).$star.':</label></th>';
		$public_fields .= '<td>'.$cf->get_input($custom_field,$postmeta).'</td>';
		$public_fields .= '</tr>';
	}

	if(isset($public_fields)){
            $public_fields = '<table>'.$public_fields.'</table>';
            return $public_fields;
        }else{
            return false;
        }

}

if(!is_admin()) add_filter('get_edit_post_link','rcl_edit_post_link',100,2);
function rcl_edit_post_link($admin_url, $post_id){
	global $user_ID,$rcl_options;
	get_currentuserinfo();

	if(!isset($rcl_options['front_editing'])) $rcl_options['front_editing'] = array(0);

	$access = (isset($rcl_options['consol_access_rcl'])&&$rcl_options['consol_access_rcl'])? $rcl_options['consol_access_rcl']: 7;
	$user_info = get_userdata($user_ID);

	if ( array_search($user_info->user_level, $rcl_options['front_editing'])!==false ||$user_info->user_level < $access ){
		$edit_url = rcl_format_url(get_permalink($rcl_options['public_form_page_rcl']));
		return $edit_url.'rcl-post-edit='.$post_id;
	}else{
		return $admin_url;
	}
}

function rcl_get_edit_post_button($content){
	global $post,$user_ID,$current_user,$rcl_options;
	if(is_tax('groups')||$post->post_type=='page') return $content;

	if(!current_user_can('edit_post', $post->ID)) return $content;

	get_currentuserinfo();
	$user_info = get_userdata($current_user->ID);

	if($post->post_author!=$user_ID){
		$author_info = get_userdata($post->post_author);
		if($user_info->user_level < $author_info->user_level) return $content;
	}

	if(!isset($rcl_options['front_editing'])) $rcl_options['front_editing'] = array(0);

	$access = (isset($rcl_options['consol_access_rcl'])&&$rcl_options['consol_access_rcl'])? $rcl_options['consol_access_rcl']: 7;

	if( false!==array_search($user_info->user_level, $rcl_options['front_editing']) || $user_info->user_level >= $access ) {

		if($post->post_type=='task'){
			if(get_post_meta($post->ID,'step_order',1)!=1) return $content;
		}

		if($user_info->user_level<10&&rcl_is_limit_editing($post->post_date)) return $content;

		$content = rcl_edit_post_button_html($post->ID).$content;
	}
	return $content;
}
add_filter('the_content','rcl_get_edit_post_button',999);
add_filter('the_excerpt','rcl_get_edit_post_button',999);

function rcl_is_limit_editing($post_date){
	global $rcl_options;

	$timelimit = (isset($rcl_options['time_editing'])&&$rcl_options['time_editing'])? $rcl_options['time_editing']: false;

	$timelimit = apply_filters('rcl_time_editing',$timelimit);

	if($timelimit){
		$hours = (strtotime(current_time('mysql')) - strtotime($post_date))/3600;
		if($hours>$timelimit) return true;
	}

	return false;
}

function rcl_edit_post_button_html($post_id){
    return '<p class="post-edit-button">'
        . '<a title="Редактировать" object-id="none" href="'. get_edit_post_link($post_id) .'">'
            . '<i class="fa fa-pencil-square-o"></i>'
        . '</a>'
    . '</p>';
}

function rcl_add_editor_box(){
	global $rcl_box;
	$rcl_box['id_box'] = (isset($_POST['idbox']))? $_POST['idbox']: rand(1,100000);
	$type = $_POST['type'];
	$log['content']=rcl_get_include_template("editor-$type-box.php",__FILE__);
	echo json_encode($log);
	exit;
}
add_action('wp_ajax_rcl_add_editor_box','rcl_add_editor_box');
add_action('wp_ajax_nopriv_rcl_add_editor_box','rcl_add_editor_box');

add_action('wp_ajax_rcl_preview_post','rcl_preview_post');
add_action('wp_ajax_nopriv_rcl_preview_post','rcl_preview_post');
function rcl_preview_post(){
	global $user_ID,$rcl_options;

	$log = array();

	$user_can = $rcl_options['user_public_access_recall'];

	if(!$user_can&&!$user_ID){

		$email_new_user = sanitize_email($_POST['email-user']);
		$name_new_user = $_POST['name-user'];

		if(!$email_new_user){
			$log['error'] = 'Введите свой e-mail!';
		}
		if(!$name_new_user){
			$log['error'] = 'Введите свое имя!';
		}

		$res_email = email_exists( $email_new_user );
		$res_login = username_exists($email_new_user);
		$correctemail = is_email($email_new_user);
		$valid = validate_username($email_new_user);

		if($res_login||$res_email||!$correctemail||!$valid){

			if(!$valid||!$correctemail){
				$log['error'] .= 'Вы ввели некорректный email!';
			}
			if($res_login||$res_email){
				$log['error'] .= 'Этот email уже используется!<br>'
						. 'Если это ваш email, то авторизуйтесь и опубликуйте свою запись';
			}
		}
	}

	//if(!$_POST['post_title']) $log['error'] = 'Заполните заголовок публикации';
	if(!$_POST['post_content']) $log['error'] = 'Добавьте содержимое публикации!';

	if($log['error']){
		echo json_encode($log);
		exit;
	}

	$post_content = '';

	if(is_array($_POST['post_content'])){
            foreach($_POST['post_content'] as $contents){
                foreach($contents as $type=>$content){
                    if($type=='text') $content = strip_tags($content);
                    if($type=='header') $content = sanitize_text_field($content);
                    if($type=='html') $content = str_replace('\'','"',$content);
                    $post_content .= "[rcl-box type='$type' content='$content']";
                }
            }
	}else{
		//$content = str_replace('\\"','',$_POST['post_content']);
		//$post_content = "[rcl-box type='html' content='$content']";
                $post_content = stripslashes_deep($_POST['post_content']);
	}

	$post_content = rcl_get_editor_content($post_content,'preview');

	$preview = '<div id="rcl-preview">';

	$preview .= '<h2>Предварительный просмотр</h2>
		<h3 class="title-post">'.$_POST['post_title'].'</h3>
		'.$post_content;

	$preview .= '<div class="rcl-notice-preview">
			<p>Если все в порядке - публикуйте! Если нет, то вы можете вернуться в редактор.</p>
			<div class="rcl-preview-buttons">
				<input type="button" class="recall-button" onclick="rcl_preview_close(this);" value="Редактировать">
				<input type="submit" class="recall-button" id="edit-post-rcl" value="'.__('To publish','rcl').'">
			</div>
		</div>';

	$preview .= '</div>';

	$log['content'] = $preview;
	echo json_encode($log);
	exit;
}

function rcl_get_editor_content($post_content,$type='editor'){
	global $rcl_box,$formFields;

	$formFields['upload'] = false;

	if($post_content){
		remove_filter('the_content','add_button_bmk_in_content',20);
		remove_filter('the_content','get_notifi_bkms',20);
		remove_filter('the_content','rcl_get_edit_post_button',999);
		$content = apply_filters('the_content',$post_content);

		if($type=='preview') return $content;

		if(isset($rcl_box)){

		}else{
			//return '<style>.rcl-public-editor{display:none}</style>'
			//.rcl_wp_editor(array('type_editor'=>3,'media_buttons'=>0),$post_content);
			//return rcl_box_shortcode(array('type'=>'html', 'content'=>str_replace('\'','"',$post_content)));
		}
		return $content;
	}else{
		return rcl_get_include_template('editor-text-box.php',__FILE__);
	}
}

function rcl_wp_editor($args=false,$content=false){
    global $rcl_options,$editpost,$formData,$user_ID;

    $media = (isset($args['media']))? $args['media']: true;
	$wp_editor = (isset($args['wp_editor']))? $args['wp_editor']: $formData->wp_editor;

    $tinymce = ($wp_editor==1||$wp_editor==3)? $tinymce = 1: 0;
    $quicktags = ($wp_editor==2||$wp_editor==3)? $quicktags = 1: 0;

    $data = array( 'wpautop' => 1
        ,'media_buttons' => 0
        ,'textarea_name' => 'post_content'
        ,'textarea_rows' => 20
        ,'tabindex' => null
        ,'editor_css' => ''
        ,'editor_class' => 'autosave'
        ,'teeny' => 0
        ,'dfw' => 0
        ,'tinymce' => $tinymce
        ,'quicktags' => $quicktags
    );

    if($media)
        if($user_ID) echo rcl_get_button(__('To add a media file','rcl'),'#',array('icon'=>'fa-folder-open','id'=>'get-media-rcl'));

	if(!$content) $content = (isset($editpost->post_content))? $editpost->post_content: '';

    wp_editor( $content, 'contentarea', $data );
}

add_shortcode('rcl-box','rcl_box_shortcode');
function rcl_box_shortcode($atts){
	global $rcl_box;

	$rcl_box = $atts;

	extract(shortcode_atts(array(
		'type' => 'text',
		'content' => ''
	),
	$atts));

	$html = '';

        $content = nl2br(strip_tags($content));

	if(isset($_GET['rcl-post-edit'])){

            switch($type){
                case 'text':
                        $rcl_box['content'] = strip_tags($content);
                break;
                /*case 'header':

                break;
                case 'image':

                break;
                case 'html':

                break;*/
            }

            $rcl_box['id_box'] = rand(1,100000);
            $html = rcl_get_edit_box($type);
	}else{

		switch($type){
			case 'text':
				$html = '<p>'.$content.'</p>';
			break;
			case 'header':
				$html = '<h3>'.$content.'</h3>';
			break;
			case 'image':
				$html = '<img class="aligncenter" src="'.$content.'">';
			break;
			case 'html':
				$html = $content;
			break;
		}

	}

	$rcl_box = false;

	return $html;
}

add_filter('get_the_excerpt','rcl_box_excerpt',10);
function rcl_box_excerpt($excerpt){
	global $post;
	if($post->post_content&&!$post->post_excerpt){
		$rcl_box = strpos($post->post_content, '[rcl-box');
		if($rcl_box!==false){
                    $excerpt = '<p>'.strip_tags(apply_filters('the_content',$post->post_content)).'</p>';
                    $excerpt = substr($excerpt, 0, 500);
                    $excerpt = preg_replace('@(.*)\s[^\s]*$@s', '\\1 ...', $excerpt);
		}
	}
	return $excerpt;
}

add_action('wp_ajax_rcl_upload_box','rcl_upload_box');
add_action('wp_ajax_nopriv_rcl_upload_box','rcl_upload_box');
function rcl_upload_box(){
	global $rcl_options,$user_ID;

	require_once(ABSPATH . "wp-admin" . '/includes/image.php');
	require_once(ABSPATH . "wp-admin" . '/includes/file.php');
	require_once(ABSPATH . "wp-admin" . '/includes/media.php');

	if($rcl_options['user_public_access_recall']&&!$user_ID) return false;

	$maxsize = (isset($rcl_options['max_sizes_attachment'])&&$rcl_options['max_sizes_attachment'])? explode(',',$rcl_options['max_sizes_attachment']): array(800,600);
	$files = array();

    //$valid_types = array("gif", "jpg", "png", "jpeg");

        if(isset($_POST['url_image'])){

		$url_image = $_POST['url_image'];
		$filename = basename($url_image);

		if($url_image){
			$img = @file_get_contents($url_image);
			if($img) file_put_contents($dir_path.$filename, $img);
			else{
                            $res['error'] = "Загрузка изображения не удалась!";
                            echo json_encode($res);
                            exit;
			}
		}

		$files[] = array(
			'tmp_name'=>$dir_path.$filename,
			'name' => $filename
		);

	}else{

            foreach($_FILES['editor_upload'] as $key=>$fls){
                    foreach($fls as $k=>$data){
                            $files[$k][$key] = $data;
                    }
            }

            $files = rcl_multisort_array($files, 'name', SORT_ASC);

        }

	$user_dir = ($user_ID)? $user_ID: $_COOKIE['PHPSESSID'];

	foreach($files as $k=>$file){

		$image = getimagesize($file['tmp_name']);

		$mime = explode('/',$image['mime']);

		/*if (!in_array($mime[1], $valid_types)){
			$res['error'] = "Недозволенное расширение файла. Используйте только: .gif, .png, .jpg";
			echo json_encode($res);
			exit;
		} */

		$dir_path = RCL_UPLOAD_PATH.'users-temp/';
		$dir_url = RCL_UPLOAD_URL.'users-temp/';
		if(!is_dir($dir_path)){
			mkdir($dir_path);
			chmod($dir_path, 0755);
		}

		$dir_path = RCL_UPLOAD_PATH.'users-temp/'.$user_dir.'/';
		$dir_url = RCL_UPLOAD_URL.'users-temp/'.$user_dir.'/';
		if(!is_dir($dir_path)){
			mkdir($dir_path);
			chmod($dir_path, 0755);
		}

		$filename = str_replace(array('`',']','[','\'',' '),'',basename($file['name']));

		$filepath = $dir_path.$filename;
		$fileurl = $dir_url.$filename;

		//if(stripos($mime[1],'gif')===false){
			if($image[0]>$maxsize[0]||$image[1]>$maxsize[1]){
				rcl_crop($file['tmp_name'],$maxsize[0],$maxsize[1],$filepath);
			}else{
				if(copy($file['tmp_name'], $dir_path.$filename)){
					unlink($file['tmp_name']);
				}
			}
			//$crop = 1;
			$html = '<img class="aligncenter" src='.$fileurl.'>';
		/*}else{
			$name = explode('.',$filename);
			$thumb_name = $name[0].'-thumb.'.$name[1];
			$crop->get_crop($file['tmp_name'],$image[0],$image[1],$dir_path.$thumb_name);
			if(copy($file['tmp_name'], $dir_path.$filename)){
				unlink($file['tmp_name']);
			}
			$thumb_url = $dir_url.$thumb_name;
			$crop = 0;
			$html = get_html_gif_image($thumb_url);
		}*/

		//if($crop) $html .= '<input type="button" class="get-crop-image recall-button" value="Обрезать" onclick="return rcl_crop(this);"/>';
		$html .= '<input type="hidden" name="post_content[][image]" value="'.$fileurl.'"/>';

		$res[$k]['content'] = $html;
		//$res[$k]['crop'] = $crop;

	}

	echo json_encode($res);
	exit;
}

//Прикрепление новой миниатюры к публикации из произвольного места на сервере
function rcl_add_thumbnail_post($post_id,$filepath){

	require_once(ABSPATH . "wp-admin" . '/includes/image.php');
	require_once(ABSPATH . "wp-admin" . '/includes/file.php');
	require_once(ABSPATH . "wp-admin" . '/includes/media.php');

	$filename = basename($filepath);
	$file = explode('.',$filename);
	$thumbpath = $filepath;

	//if($file[0]=='image'){
		$data = getimagesize($thumbpath);
		$mime = $data['mime'];
	//}else $mime = mime_content_type($thumbpath);

	$cont = file_get_contents($thumbpath);
	$image = wp_upload_bits( $filename, null, $cont );

	$attachment = array(
		'post_mime_type' => $mime,
		'post_title' => preg_replace('/\.[^.]+$/', '', basename($image['file'])),
		'post_content' => '',
		'guid' => $image['url'],
		'post_parent' => $post_id,
		'post_status' => 'inherit'
	);

	$attach_id = wp_insert_attachment( $attachment, $image['file'], $post_id );
	$attach_data = wp_generate_attachment_metadata( $attach_id, $image['file'] );
	wp_update_attachment_metadata( $attach_id, $attach_data );

	$oldthumb = get_post_meta($post_id, '_thumbnail_id',1);
	if($oldthumb) wp_delete_attachment($oldthumb);

	update_post_meta($post_id, '_thumbnail_id', $attach_id);
}

//удаляем папку с изображениями при удалении поста
add_action('delete_post','rcl_delete_tempdir_attachments');
function rcl_delete_tempdir_attachments($postid){
	$dir_path = RCL_UPLOAD_PATH.'post-media/'.$postid;
	rcl_remove_dir($dir_path);
}

function rcl_footer_publics_scripts($script){
	global $rcl_options;
	$maxsize_mb = (isset($rcl_options['public_gallery_weight'])&&$rcl_options['public_gallery_weight'])? $rcl_options['public_gallery_weight']: 2;
	$maxsize = $maxsize_mb*1024*1024;
	$cnt = (isset($rcl_options['count_image_gallery'])&&$rcl_options['count_image_gallery'])? $rcl_options['count_image_gallery']: 1;

	$script .= "
	rcl_add_dropzone('#rcl-public-dropzone');
	var post_id_edit = $('input[name=\"post-rcl\"]').val();
	$('#upload-public-form').fileupload({
		dataType: 'json',
		type: 'POST',
		url: wpurl+'wp-admin/admin-ajax.php',
		formData:{action:'rcl_imagepost_upload',post_id:post_id_edit},
		singleFileUploads:false,
		autoUpload:true,
		progressall: function (e, data) {
			/*var progress = parseInt(data.loaded / data.total * 100, 10);
			$('#upload-box-message .progress-bar').show().css('width',progress+'px');*/
		},
		change:function (e, data) {
			var error = 0;
                        /*rcl_preloader_show('#tab-postform');*/
                        rcl_preloader_show('.public_block form');
			$.each(data.files, function (index, file) {
				if(file['size']>".$maxsize."){
					rcl_notice('Превышен максимальный размер для файла '+file['name']+'! Макс. ".$maxsize_mb."MB','error');
                                        rcl_preloader_hide();
					error = 1;
				}
			});
			if(error) return false;
		},
		done: function (e, data) {
			$.each(data.result, function (index, file) {
				if(file['string']){
					$('#temp-files').append(file['string']);
				}
			});
                        rcl_preloader_hide();
		}
	});";
	return $script;
}
add_filter('file_footer_scripts_rcl','rcl_footer_publics_scripts');

function rcl_public_file_scripts($script){

	$ajaxdata = "type: 'POST', data: dataString, dataType: 'json', url: wpurl+'wp-admin/admin-ajax.php',";
	//$ajaxfile = "type: 'POST', data: dataString, dataType: 'json', url: rcl_url+'add-on/publicpost/ajax-request.php',";

	$script .= "

		jQuery('#rcl-delete-post .delete-toggle').click(function() {
			jQuery(this).next().toggle('fast');
			return false;
		});

		jQuery('form[name=\'public_post\'] input[name=\'edit-post-rcl\'],form[name=\'public_post\'] input[name=\'add_new_task\']').click(function(){
			var error=0;
			jQuery('form[name=\'public_post\']').find(':input').each(function() {
				for(var i=0;i<field.length;i++){
					if(jQuery(this).attr('name')==field[i]){
						if(jQuery(this).val()==''){
							jQuery(this).attr('style','border:1px solid red !important');
							error=1;
						}else{
							jQuery(this).attr('style','border:1px solid #E6E6E6 !important');
						}
					}
				}
			});
			if(error==0) return true;
			else return false;
		});
		jQuery('#rcl-popup').on('click','.rcl-navi.ajax-navi a',function(){
			var page = jQuery(this).text();
			var dataString = 'action=get_media&user_ID='+user_ID+'&page='+page;

			jQuery.ajax({
				".$ajaxdata."
				success: function(data){
					if(data['result']==100){
						jQuery('#rcl-overlay').fadeIn();
						jQuery('#rcl-popup').html(data['content']);
						var screen_top = jQuery(window).scrollTop();
						var popup_h = jQuery('#rcl-popup').height();
						var window_h = jQuery(window).height();
						screen_top = screen_top + 60;
						jQuery('#rcl-popup').css('top', screen_top+'px').delay(100).slideDown(400);
					}else{
						alert('Ошибка!');
					}
				}
			});
			return false;
		});
		jQuery('form #get-media-rcl').click(function(){
			var dataString = 'action=get_media&user_ID='+user_ID;

			jQuery.ajax({
				".$ajaxdata."
				success: function(data){
					if(data['result']==100){
						jQuery('#rcl-overlay').fadeIn();
						jQuery('#rcl-popup').html(data['content']);
						var screen_top = jQuery(window).scrollTop();
						var popup_h = jQuery('#rcl-popup').height();
						var window_h = jQuery(window).height();
						screen_top = screen_top + 60;
						jQuery('#rcl-popup').css('top', screen_top+'px').delay(100).slideDown(400);
					}else{
						alert('Ошибка!');
					}
				}
			});
			return false;
		});

		jQuery('#lk-content').on('click','#tab-publics .sec_block_button',function(){
			var btn = jQuery(this);
			get_page_content_rcl(btn,'posts_rcl_block');
			return false;
		});

	function get_page_content_rcl(btn,id_page_rcl){
			if(btn.hasClass('active'))return false;
			rcl_preloader_show('#tab-publics');
			var start = btn.attr('data');
			var type = btn.attr('type');
			var id_user = parseInt(jQuery('.wprecallblock').attr('id').replace(/\D+/g,''));
			jQuery('.'+id_page_rcl+' .sec_block_button').removeClass('active');
			btn.addClass('active');
			var dataString = 'action=rcl_posts_list&start='+start+'&type='+type+'&id_user='+id_user;
			jQuery.ajax({
				".$ajaxdata."
				success: function(data){
					if(data['recall']==100){
						jQuery('.'+id_page_rcl+' .publics-table-rcl').html(data['post_content']);
					} else {
						alert('Error');
					}
					rcl_preloader_hide();
				}
			});
			return false;
	}
		";
	return $script;
}
add_filter('file_scripts_rcl','rcl_public_file_scripts');
