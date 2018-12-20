<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


/**
 * Description of class-rcl-fields
 *
 * @author Андрей
 */
class Rcl_Fields extends Rcl_Field{

    public $fields;
    public $structure = array();

    function __construct($fields = false, $structure = false) {

        if($structure){
            $this->structure = $structure;
        }

        if($fields){
            foreach($fields as $field){
                if(!isset($field['slug'])) continue;
                $this->fields[$field['slug']] = is_array($field)? parent::setup($field): $field;
            }
        }

        $this->setup_structure();

    }

    function setup_structure($force = false){

        if(!$this->structure || ($this->structure && !$this->fields) || $force){

            $fieldIds = array();

            if($this->fields){
                foreach($this->fields as $field_id => $field){
                    $fieldIds[] = $field_id;
                }
            }

            $this->structure = array(
                array(
                    'areas' => array(
                        array(
                            'fields' => $fieldIds
                        )
                    )
                )
            );

        }

    }

    function get_fields(){
        return $this->fields;
    }

    function add_field($field_id, $args){
        $this->fields[$field_id] = parent::setup($args);
    }

    function remove_field($field_id){
        unset($this->fields[$field_id]);
    }

    function isset_field($field_id){
        return isset($this->fields[$field_id]);
    }

    function get_field($field_id){
        return $this->isset_field($field_id)? $this->fields[$field_id]: false;
    }

    function set_field_prop($field_id, $propName, $propValue){

        $field = $this->get_field($field_id);

        $field->$propName = $propValue;

        $this->fields[$field_id]  = $field;

    }

    function isset_field_prop($field_id, $propName){

        $field = $this->get_field($field_id);

        if(!$field) return false;

        return isset($field->$propName);

    }

    function get_field_prop($field_id, $propName){

        if(!$this->isset_field_prop($field_id, $propName)) return false;

        $field = $this->get_field($field_id);

        return $field->$propName;

    }

    function exclude($fieldIds){

        if(!$this->fields) return false;

        $fields = array();
        foreach($this->fields as $field_id => $field){
            if(in_array($field_id, $fieldIds)) continue;
            $fields[$field_id] = $field;
        }

        $this->fields = $fields;

        return $this;

    }

    function search($filters){

        $fields = array();

        foreach($filters as $key => $value){
            $fields = $this->search_by($key, $value, $fields);
            if(!$fields) return false;
        }

        return $fields;

    }

    function search_by($key, $value, $fields = false){

        if(!$fields)
            $fields = $this->fields;

        $search = array();

        foreach($fields as $field_id => $field){

            if(!$field->isset_prop($key)) continue;

            if(is_array($value)){

                if(!in_array($field->get_prop($key), $value)) continue;

            }else{

                if($field->get_prop($key) != $value) continue;

            }

            $search[$field_id] = $field;

        }

        return $search;

    }

    function add_structure_field($group_id, $area_id, $fields){

        foreach($fields as $field_id => $args){
            $this->fields[$field_id] = $this::setup_field($field_id, $args);
            $this->structure[$group_id]['areas'][$area_id]['fields'][] = $field_id;
        }

    }

    function add_structure_group($group_id, $args = false){

        $this->structure[$group_id] = wp_parse_args($args, array(
            'title' => ''
        ));

    }

    function get_content(){

        $content = '';

        foreach($this->structure as $group_id => $group){
            $content .= $this->get_group($group);
        }

        if(!$content) return false;

        $content = '<div class="rcl-content preloader-parent">'.$content.'</div>';

        return $content;

    }

    function get_loop(){

        $content = '';

        foreach($this->structure as $group_id => $group){
            $content .= $this->get_group($group);
        }

        return $content;
    }

    function get_group($group){

        if(!isset($group['areas']) || !$group['areas']) return false;

        $groupContent = '';

        foreach($group['areas'] as $area){
            $groupContent .= $this->get_area($area);
        }

        if(!$groupContent) return false;

        $content = '<div class="rcl-content-group">';

        if($group['title'])
            $content .= '<div class="group-title">'.$group['title'].'</div>';

            $content .= '<div class="group-areas">';

            $content .= $groupContent;

            $content .= '</div>';

        $content .= '</div>';

        return $content;
    }

    function get_area($area){

        $areaContent = '';

        if(!isset($area['fields']) || !$area['fields']) return false;

        foreach($area['fields'] as $field_id){
            $areaContent .= $this->get_field_content($field_id);
        }

        if(!$areaContent) return false;

        $content = '<div class="rcl-content-area" style="width:'.(isset($area['width'])? $area['width']: 100).'%;">';
        $content .= $areaContent;
        $content .= '</div>';

        return $content;
    }

    function get_field_content($field_id){

        $field = $this->get_field($field_id);

        if(!$field->value) return false;

        $content .= $field->get_value_box($field->value);

        return $content;
    }

    function get_form($args = array()){

        $args = wp_parse_args($args, array(
            'form_id' => '',
            'unique_ids' => false,
            'action' => '',
            'method' => 'post',
            'submit' => __('Сохранить', 'wp-recall'),
            'nonce_name' => '_wpnonce',
            'nonce_key' => '',
            'onclick' => '',
        ));

        $content = '<div class="rcl-form preloader-parent">';

            $content .= '<form '.($args['form_id']? 'id="'.$args['form_id'].'"': '').' method="'.$args['method'].'" action="'.$args['action'].'">';

            $content .= $this->get_content_form($args);

            $content .= '<div class="submit-box">';

            $bttnArgs = array(
                'label' => $args['submit'],
                'icon' => 'fa-check-circle'
            );

            if($args['onclick']){
                $bttnArgs['onclick'] = $args['onclick'];
            }else{
                $bttnArgs['submit'] = 1;
            }

            $content .= rcl_get_button($bttnArgs);

            $content .= '</div>';

            if($args['nonce_key'])
                $content .= wp_nonce_field($args['nonce_key'],$args['nonce_name'],true,false);

            $content .= '</form>';

        $content .= '</div>';

        return $content;

    }

    function get_content_form($args = false){

        $content = '';

        foreach($this->structure as $group_id => $group){
            $content .= $this->get_group_form($group, $args);
        }

        if(!$content) return false;

        $content = '<div class="rcl-content preloader-parent">'.$content.'</div>';

        return $content;

    }

    function get_group_form($group, $args = false){

        if(!isset($group['areas']) || !$group['areas']) return false;

        $groupContent = '';

        foreach($group['areas'] as $area){
            $groupContent .= $this->get_area_form($area, $args);
        }

        if(!$groupContent) return false;

        $content = '<div class="rcl-content-group">';

        if($group['title'])
            $content .= '<div class="group-title">'.$group['title'].'</div>';

            $content .= '<div class="group-areas">';

            $content .= $groupContent;

            $content .= '</div>';

        $content .= '</div>';

        return $content;
    }

    function get_area_form($area, $args = false){

        $areaContent = '';

        if(!isset($area['fields']) || !$area['fields']) return false;

        foreach($area['fields'] as $field_id){
            $areaContent .= $this->get_field_form($field_id, $args);
        }

        if(!$areaContent) return false;

        $content = '<div class="rcl-content-area" style="width:'.(isset($area['width'])? $area['width']: 100).'%;">';
        $content .= $areaContent;
        $content .= '</div>';

        return $content;
    }

    function get_field_form($field_id, $args = false){

        $field = $this->get_field_input($field_id);

        if(!$field) return false;

        if(isset($args['unique_ids']))
            $field->set_prop('unique_id', true);

        $content = $field->get_field_box();

        return $content;
    }

}
