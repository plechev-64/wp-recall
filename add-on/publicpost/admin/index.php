<?php

require_once 'settings.php';

add_action('admin_init','rcl_public_admin_scripts');
function rcl_public_admin_scripts(){
    
    wp_enqueue_script( 'jquery' );
    wp_enqueue_script( 'rcl_public_admin_scripts', rcl_addon_url('admin/assets/scripts.js', __FILE__) );
    wp_enqueue_style( 'rcl_public_admin_style', rcl_addon_url('admin/assets/style.css', __FILE__) );

}

add_action('admin_menu', 'rcl_admin_page_publicform',30);
function rcl_admin_page_publicform(){
    add_submenu_page( 'manage-wprecall', __('Form of publication','wp-recall'), __('Form of publication','wp-recall'), 'manage_options', 'manage-public-form', 'rcl_public_form_manager');
}

function rcl_public_form_manager(){
    global $wpdb;
    
    $post_type = (isset($_GET['post-type']))? $_GET['post-type']: 'post';

    rcl_sortable_scripts();

    $publicFields = new Rcl_Public_Form_Fields(array('post_type'=>$post_type));
    
    $content = '<h2>'.__('Управление формами публикации','wp-recall').'</h2>';
    
    $content .= $publicFields->form_navi();
    
    $content .= $publicFields->active_fields_box();

    $content .= $publicFields->inactive_fields_box();

    /*$users_fields = '<h2>'.__('Arbitrary fields of  publication','wp-recall').'</h2>
    <small>'.__('Use shortcode for publication form','wp-recall').' [public-form]</small><br>
    <small>'.__('You can create a different set of custom fields for different forms','wp-recall').'.<br>
    Чтобы вывести определенный набор полей через шорткод следует указать идентификатор формы, например, [public-form id="2"]</small><br>
    <small>Форма публикации уже содержит обязательные поля для заголовка записи, контента, ее категории и указания метки.</small><br>

    '.$f_edit->edit_form(array(
        $f_edit->option('textarea',array(
            'name'=>'notice',
            'label'=>__('field description','wp-recall')
        )),
        $f_edit->option('select',array(
            'name'=>'required',
            'notice'=>__('required field','wp-recall'),
            'value'=>array(__('No','wp-recall'),__('Yes','wp-recall'))
        ))
    )).'
        
    <p>Чтобы вывести все данные занесенные в созданные произвольные поля формы публикации внутри опубликованной записи можно воспользоваться функцией<br />
    <b>rcl_get_custom_post_meta($post_id)</b><br />
    Разместите ее внутри цикла и передайте ей идентификатор записи первым аргументом<br />
    Также можно вывести каждое произвольное поле в отдельности через функцию<br />
    <b>get_post_meta($post_id,$slug,1)</b><br />
    где<br />
    $post_id - идентификатор записи<br />
    $slug - ярлык произвольного поля формы</p>';*/
    
    echo $content;
}

add_action('admin_init', 'custom_fields_editor_post_rcl', 1);
function custom_fields_editor_post_rcl() {
    add_meta_box( 'custom_fields_editor_post', __('Arbitrary fields of  publication','wp-recall'), 'custom_fields_list_posteditor_rcl', 'post', 'normal', 'high'  );
}

function custom_fields_list_posteditor_rcl($post){    
    echo rcl_get_custom_fields_edit_box($post->ID); ?>
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