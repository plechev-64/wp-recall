<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


/**
 * Description of class-rcl-file-uploader
 *
 * @author Андрей
 */

add_action( 'wp_enqueue_scripts', 'rcl_uploader_scripts', 10);
function rcl_uploader_scripts(){
    wp_enqueue_style( 'rcl-uploader-style', plugins_url( 'assets/css/uploader.css', dirname( __FILE__ )) );
    wp_enqueue_script( 'rcl-uploader-scripts', plugins_url( 'assets/js/uploader.js', dirname( __FILE__ ) ), array('jquery'), USP_VERSION );
}

class Rcl_Uploader {

    public $uploader_id = '';
    public $required = false;
    public $action = 'rcl_upload';
    public $temp_media = false;
    public $input_attach = false;
    public $auto_upload = true;
    public $user_id = 0;
    public $post_parent = 0;
    public $input_name = 'rcl-upload';
    public $dropzone = false;
    public $max_files = 10;
    public $max_size = 512;
    public $min_width = false;
    public $min_height = false;
    public $resize = array();
    public $file_types = array('jpg','png','jpeg');
    public $multiple = false;
    public $crop = false;
    public $image_sizes = true;
    public $files = array();

    protected $accept = array('image/*');

    function __construct($uploader_id, $args = false){

        if(!isset($args['user_id'])){

            global $user_ID;

            $args['user_id'] = $user_ID;

        }

        $this->uploader_id = $uploader_id;

        if($args)
            $this->init_properties($args);

        if(!is_array($this->file_types)){
            $this->file_types = array_map('trim', explode(',', $this->file_types));
        }

        if(!$this->file_types)
            $this->file_types = array('jpg','png','jpeg');

        if($this->resize && !is_array($this->resize)){
            $this->resize = array_map('trim', explode(',', $this->resize));
        }

        $this->accept = $this->get_accept();

        $this->init_scripts();

    }

    function init_scripts(){

        rcl_fileupload_scripts();

        if($this->crop){
            rcl_dialog_scripts();
            rcl_crop_scripts();
        }

    }

    function init_properties($args){

        $properties = get_class_vars(get_class($this));

        foreach ($args as $name => $value){
            //if(isset($args[$name])){

                //$value = $args[$name];

                if(is_array($value)){

                    foreach($value as $k => $v){
                        if(is_object($v))
                            $value[$k] = (array)$v;
                    }
                    $value = (array)$value;

                }else if(is_object($value)){
                    $value = (array)$value;
                }

                $this->$name = $value;
            //}
        }

    }

    function get_uploader($args = false){

        $defaults = array(
            'allowed_types' => true
        );

        $args = wp_parse_args( $args, $defaults );

        $content = '<div id="rcl-uploader-'.$this->uploader_id.'" class="rcl-uploader">';

            if($this->dropzone)
                $content .= $this->get_dropzone();

            $content .= '<div class="rcl-uploader-button-box">';

            $content .= $this->get_button($args);

            if($args['allowed_types'])
                $content .= '<small class="notice">'.__('Allowed extensions','rcl-public').': '.implode(', ', $this->file_types).'</small>';

            $content .= '</div>';

        $content .= '</div>';

        return $content;

    }

    function get_input(){
        return '<input id="rcl-uploader-input-'.$this->uploader_id.'" class="uploader-input" data-uploader_id="'.$this->uploader_id.'" name="'.$this->input_name.'[]" type="file" accept="'.implode(', ', $this->accept).'" '.($this->multiple? 'multiple': '').'>'
            . '<script>rcl_init_uploader('.json_encode($this).');</script>';
    }

    function get_button($args){

        $defaults = array(
            'button_label' => __('Загрузить файл','rcl-public'),
            'button_icon' => 'fa-upload',
            'button_type' => 'simple'
        );

        $args = wp_parse_args( $args, $defaults );

        $bttnArgs = array(
            'icon' => $args['button_icon'],
            'type' => $args['button_type'],
            'label' => $args['button_label'],
            'class' => array('rcl-uploader-button', 'rcl-uploader-button-'.$this->uploader_id),
            'content' => $this->get_input()
        );

        return rcl_get_button($bttnArgs);

    }

    function get_dropzone(){

        $content = '<div id="rcl-dropzone-'.$this->uploader_id.'" class="rcl-dropzone">
                <div class="dropzone-upload-area">
                    '.__('Add files to the download queue','rcl-public').'
                </div>
            </div>';

        return $content;

    }

    private function get_mime_type_by_ext($file_ext){

        if(!$file_ext) return false;

        $mimes = get_allowed_mime_types();

        foreach ($mimes as $type => $mime) {
            if (strpos($type, $file_ext) !== false) {
                return $mime;
            }
        }

        return false;
    }

    private function get_accept(){

        if(!$this->file_types) return false;

        $accept = array();

        foreach($this->file_types as $type){
            if(!$type) continue;
            $accept[] = $this->get_mime_type_by_ext($type);
        }

        return $accept;
    }

    function get_gallery($imagIds = false, $getTemps = false){

        if(!$imagIds && $getTemps){

            $Query = new Rcl_Temp_Media();

            $imagIds = $Query->get_col(array(
                'fields' => array(
                    'media_id'
                ),
                'uploader_id' => $this->uploader_id,
                'user_id' => $this->user_id? $this->user_id: 0,
                'session_id' => $this->user_id? '': $_COOKIE['PHPSESSID'],
            ));

        }

        $content = '<div id="rcl-upload-gallery-'.$this->uploader_id.'" class="rcl-upload-gallery">';

        if($imagIds){
            foreach($imagIds as $imagId){
                $content .= $this->gallery_attachment($imagId);
            }
        }

        $content .= '</div>';

        return $content;

    }

    function gallery_attachment($attach_id){

        if(!get_post_type($attach_id)) return false;

        if(wp_attachment_is_image($attach_id)){

            $image = wp_get_attachment_image( $attach_id, 'thumbnail');

        }else{

            $image = wp_get_attachment_image( $attach_id, array(100, 100), true);

        }

        if(!$image) return false;

        $content = '<div class="gallery-attachment gallery-attachment-'.$attach_id.'" id="gallery-'.$this->uploader_id.'-attachment-'.$attach_id.'">';

            $content .= $this->get_attachment_manager($attach_id);

            $content .= $image;

            if($this->input_attach)
                $content .= '<input type="hidden" name="'.$this->input_attach.'[]" value="'.$attach_id.'">';

        $content .= '</div>';

        return $content;

    }

    function get_attachment_manager($attach_id){

        $manager_items = apply_filters('rcl_uploader_manager_items', array(
            array(
                'icon' => 'fa-trash',
                'title' => __('Удалить файл', 'wp-recall'),
                'onclick' => 'rcl_delete_attachment('.$attach_id.','.$this->post_parent.',this);return false;'
            )
        ), $attach_id, $this);

        if(!$manager_items) return false;

        $content .= '<div class="attachment-manager">';

            foreach($manager_items as $item){
                $item['type'] = 'simple';
                $content .= rcl_get_button($item);

            }

        $content .= '</div>';

        return $content;

    }

    function upload(){

        rcl_verify_ajax_nonce();

        if(!$_FILES[$this->input_name]){
            return false;
        }

        require_once( ABSPATH . 'wp-admin/includes/image.php' );

        $files = array();
        foreach($_FILES[$this->input_name] as $nameProp => $values){
            foreach($values as $k => $value){
                $files[$k][$nameProp] = $value;
            }
        }

        $uploads = array();
        foreach($files as $file){

            $filetype = wp_check_filetype_and_ext( $file['tmp_name'], $file['name'] );

            if (!in_array($filetype['ext'], $this->file_types)){
                wp_send_json(array('error'=>__('Forbidden file extension. Allowed:','wp-recall').' '.implode(', ',$this->file_types)));
            }

            $image = wp_handle_upload( $file, array('test_form' => FALSE) );

            if($image['file']){

                $this->setup_image_sizes($image['file']);

                if($this->crop){

                    $this->crop_image($image['file']);

                }

                if($this->resize){

                    $this->resize_image($image['file']);

                }

                $attachment = array(
                    'post_mime_type' => $image['type'],
                    'post_title' => preg_replace('/\.[^.]+$/', '', basename($image['file'])),
                    'post_content' => '',
                    'guid' => $image['url'],
                    'post_parent' => $this->post_parent,
                    'post_author' => $this->user_id,
                    'post_status' => 'inherit'
                );

                if(!$this->user_id){
                    $attachment['post_content'] = $_COOKIE['PHPSESSID'];
                }

                $attach_id = wp_insert_attachment( $attachment, $image['file'], $this->post_parent );

                $attach_data = wp_generate_attachment_metadata( $attach_id, $image['file'] );

                wp_update_attachment_metadata( $attach_id, $attach_data );

                if($this->temp_media){
                    rcl_add_temp_media(array(
                        'media_id' => $attach_id,
                        'uploader_id' => $this->uploader_id
                    ));
                }

                $uploads[] = array(
                    'id' => $attach_id,
                    'html' => $this->gallery_attachment($attach_id)
                );

            }

        }

        do_action('rcl_upload', $uploads, $this);

        return $uploads;

    }

    function setup_image_sizes($image_src){

        if(!$this->image_sizes || is_array($this->image_sizes)){

            $thumbSizes = wp_get_additional_image_sizes();

            foreach($thumbSizes as $thumbName => $sizes){
                remove_image_size( $thumbName );
            }

            if(is_array($this->image_sizes)){

                list($width,$height) = getimagesize($image_src);

                foreach($this->image_sizes as $k => $thumbData){

                    $thumbData = wp_parse_args($thumbData, array(
                        'width' => $width,
                        'height' => $height,
                        'crop' => 1
                    ));

                    add_image_size( $k.'-'.current_time('mysql'), $thumbData['width'], $thumbData['height'], $thumbData['crop'] );
                }

            }

        }

    }

    function crop_image($image_src){

        list($width,$height) = getimagesize($image_src);

        $crop = $_POST['crop_data'];
        $size = $_POST['image_size'];

        if(!$crop) return false;

        list($crop_x, $crop_y, $crop_w ,$crop_h) =  explode(',', $crop);
        list($viewWidth, $viewHeight) =  explode(',',$size);

        $cf = 1;
        if($viewWidth < $width){
            $cf = $width/$viewWidth;
        }

        $crop_x *= $cf;
        $crop_y *= $cf;
        $crop_w *= $cf;
        $crop_h *= $cf;


        $image = wp_get_image_editor( $image_src );

        if ( ! is_wp_error( $image ) ) {
            $image->crop($crop_x, $crop_y, $crop_w, $crop_h);
            $image->save( $image_src );
        }

    }

    function resize_image($image_src){

        if(!$this->resize) return false;

        $image = wp_get_image_editor( $image_src );

        if ( ! is_wp_error( $image ) ) {
            $image->resize($this->resize[0], $this->resize[1], false);
            $image->save( $image_src );
        }

    }

}
