<?php

class Rcl_Field{

    static function setup($args){

        if(is_admin()){
            rcl_font_awesome_style();
        }

        if(in_array($args['type'], array(
            'text', 'url', 'email', 'hidden', 'password', 'time'
        ))){
            return new Rcl_Field_Text($args);
        }else
        if($args['type'] == 'textarea'){
            return new Rcl_Field_TextArea($args);
        }else
        if($args['type'] == 'number'){
            return new Rcl_Field_Number($args);
        }else
        if($args['type'] == 'tel'){
            return new Rcl_Field_Tel($args);
        }else
        if($args['type'] == 'agree'){
            return new Rcl_Field_Agree($args);
        }else
        if($args['type'] == 'checkbox'){
            return new Rcl_Field_Checkbox($args);
        }else
        if($args['type'] == 'color'){
            return new Rcl_Field_Color($args);
        }else
        if($args['type'] == 'custom'){
            return new Rcl_Field_Custom($args);
        }else
        if($args['type'] == 'dynamic'){
            return new Rcl_Field_Dynamic($args);
        }else
        if($args['type'] == 'editor'){
            return new Rcl_Field_Editor($args);
        }else
        if($args['type'] == 'file'){
            return new Rcl_Field_File($args);
        }else
        if($args['type'] == 'multiselect'){
            return new Rcl_Field_MultiSelect($args);
        }else
        if($args['type'] == 'radio'){
            return new Rcl_Field_Radio($args);
        }else
        if($args['type'] == 'range'){
            return new Rcl_Field_Range($args);
        }else
        if($args['type'] == 'runner'){
            return new Rcl_Field_Runner($args);
        }else
        if($args['type'] == 'select'){
            return new Rcl_Field_Select($args);
        }else
        if($args['type'] == 'date'){
            return new Rcl_Field_Date($args);
        }else
        if($args['type'] == 'uploader'){
            return new Rcl_Field_Uploader($args);
        }

    }

}
