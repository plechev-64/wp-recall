<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


/**
 * Description of class-rcl-custom-field-text
 *
 * @author Андрей
 */
class Rcl_Field_File extends Rcl_Field_Abstract{

    public $required;
    public $file_types;
    public $max_size;
    public $files;

    function __construct($args) {

        if(isset($args['ext-files']))
            $args['file_types'] = $args['ext-files'];

        if(isset($args['sizefile']))
            $args['max_size'] = $args['sizefile'];

        parent::__construct($args);
    }

    function get_options(){

        $options = array(
             array(
                'slug' => 'icon',
                'default' => 'fa-file',
                'placeholder' => 'fa-file',
                'type' => 'text',
                'title'=>__('Icon class of  font-awesome', 'wp-recall'),
                'notice'=>__('Source', 'wp-recall').' <a href="https://fontawesome.com/v4.7.0/icons/" target="_blank">http://fontawesome.io/</a>'
            ),
            array(
                'slug' => 'max_size',
                'default' => $this->max_size,
                'type' => 'runner',
                'unit' => 'Kb',
                'value_min' => 256,
                'value_max' => 5120,
                'value_step' => 256,
                'default' => 512,
                'title' => __('File size', 'wp-recall'),
                'notice' => __('maximum size of uploaded file, KbB (Default - 512)', 'wp-recall')
            ),
            array(
                'slug' => 'file_types',
                'default' => $this->file_types,
                'type' => 'text',
                'title' => __('Allowed file types', 'wp-recall'),
                'notice' => __('allowed types of files are divided by comma, for example: pdf, zip, jpg', 'wp-recall')
            )
        );

        return $options;

    }

    function get_input(){
        global $user_ID;
        $input = '';

        if(is_admin() && !rcl_is_ajax()){

            $post_id = (isset($_GET['post']))? intval($_GET['post']): false;
            $user_id = (isset($_GET['user_id']))? intval($_GET['user_id']): false;

            $url = admin_url('?meta='.$this->id.'&rcl-delete-file='.base64_encode($this->value));

            if($post_id){
                $url .= '&post_id='.$post_id;
            }else if($user_id){
                $url .= '&user_id='.$user_id;
            }else{
                $url .= '&user_id='.$user_ID;
            }

        }else{

            $url = get_bloginfo('wpurl').'/?meta='.$this->id.'&rcl-delete-file='.base64_encode($this->value);

        }

        if($this->value){

            $input .= $this->get_field_value();

            if(!$this->required)
                $input .= '<span class="delete-file-url"><a href="'.wp_nonce_url($url, 'user-'.$user_ID ).'"> <i class="rcli fa-times-circle-o"></i> '.__('delete', 'wp-recall').'</a></span>';

            $input = '<span class="file-manage-box">'.$input.'</span>';

        }

        $accTypes = false;
        $extTypes = $this->file_types? array_map('trim',explode(',',$this->file_types)): array();

        if($extTypes)
            $accTypes = rcl_get_mime_types($extTypes);

        $accept = ($accTypes)? 'accept="'.implode(',',$accTypes).'"': '';
        $required = (!$this->value)? $this->get_required(): '';

        $input .= '<span id="'.$this->id.'-content" class="file-field-upload">';
        $input .= '<span onclick="jQuery(\'#'.$this->input_id.'\').val(\'\');" class="file-input-recycle"><i class="rcli fa-recycle"></i></span>';
        $input .= '<input data-size="'.$this->max_size.'" '.($extTypes? 'data-ext="'.implode(',',$extTypes).'"': '').' type="file" '.$required.' '.$accept.' name="'.$this->input_name.'" '.$this->get_class().' id="'.$this->input_id.'" value=""/> ';

        $input .= '<br>';

        if($extTypes)
            $input .= '<span class="allowed-types">'.__('Allowed extensions', 'wp-recall').': '.$this->file_types.'</span>. ';

        $input .= __('Max size', 'wp-recall').': '.$this->max_size.'Kb';

        $input .= '<script type="text/javascript">rcl_init_field_file("'.$this->id.'");</script>';
        $input .= '</span>';

        $this->files[$this->id] = $this->max_size;

        return $input;
    }

    function get_value(){
        global $user_ID;

        if(!$this->value) return false;

        return '<a href="'.wp_nonce_url(get_bloginfo('wpurl').'/?rcl-download-file='.base64_encode($this->value), 'user-'.$user_ID ).'"><i class="rcli fa-upload" aria-hidden="true"></i> '.__('Upload the downloaded file','wp-recall').'</a>';

    }

}
