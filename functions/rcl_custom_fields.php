<?php

class Rcl_Custom_Fields{

    public $value;
    public $slug;
    public $required;

    function __construct(){

    }

    function get_title($field){
        if($field['type']=='agree') return __('Agreement','rcl');
        return $field['title'];
    }

    function get_input($field,$value=false){


        $this->value = $value;
        $this->slug = $field['slug'];
        $this->required = ($field['requared']==1)? 'required': '';

        if(!$field['type']) return false;

        if($field['type']=='date') rcl_datepicker_scripts();

        $callback = 'get_type_'.$field['type'];

        return $this->$callback($field);

    }

    function get_type_text($field){
        return '<input type="text" '.$this->required.' name="'.$this->slug.'" id="'.$this->slug.'" maxlength="50" value="'.$this->value.'"/>';
    }

    function get_type_tel($field){
        return '<input type="tel" '.$this->required.' name="'.$this->slug.'" id="'.$this->slug.'" maxlength="50" value="'.$this->value.'"/>';
    }

    function get_type_email($field){
        return '<input type="email" '.$this->required.' name="'.$this->slug.'" id="'.$this->slug.'" maxlength="50" value="'.$this->value.'"/>';
    }

    function get_type_url($field){
        return '<input type="url" '.$this->required.' name="'.$this->slug.'" id="'.$this->slug.'" maxlength="50" value="'.$this->value.'"/>';
    }

    function get_type_date($field){
        return '<input type="text" '.$this->required.' class="datepicker" name="'.$this->slug.'" id="'.$this->slug.'" value="'.$this->value.'"/>';
    }

    function get_type_time($field){
        return '<input type="time" '.$this->required.' name="'.$this->slug.'" id="'.$this->slug.'" maxlength="50" value="'.$this->value.'"/>';
    }

    function get_type_number($field){
        return '<input type="number" '.$this->required.' name="'.$this->slug.'" id="'.$this->slug.'" maxlength="50" value="'.$this->value.'"/>';
    }

    function get_type_textarea($field){
        return '<textarea name="'.$this->slug.'" '.$this->required.' id="'.$this->slug.'" rows="5" cols="50">'.$this->value.'</textarea>';
    }

    function get_type_agree($field){
        return '<input type="checkbox" '.checked($this->value,1,false).' '.$this->required.' name="'.$this->slug.'" id="'.$this->slug.'" value="1"/> '
                . '<a href="'.$field['field_select'].'">'.$field['title'].'</a>';
    }

    function get_type_select($field){
        $fields = explode('#',$field['field_select']);
        $count_field = count($fields);
        $field_select = '';
        for($a=0;$a<$count_field;$a++){
                $field_select .='<option '.selected($this->value,$fields[$a],false).' value="'.trim($fields[$a]).'">'.$fields[$a].'</option>';
        }
        return '<select '.$this->required.' name="'.$this->slug.'" id="'.$this->slug.'">
        '.$field_select.'
        </select>';
    }

    function get_type_checkbox($field){
        $chek = explode('#',$field['field_select']);
        $count_field = count($chek);
        $input = '';
        $class = ($this->required) ? 'class="requared-checkbox"':'';
        for($a=0;$a<$count_field;$a++){
            $sl = '';
            if($this->value){
                foreach($this->value as $meta){
                    if($chek[$a]!=$meta) continue;
                    $sl = 'checked=checked';
                    break;
                }
            }
            $input .='<label class="block-label" for="'.$this->slug.'_'.$a.'">'
                    . '<input '.$this->required.' '.$sl.' id="'.$this->slug.'_'.$a.'" type="checkbox" '.$class.' name="'.$this->slug.'[]" value="'.trim($chek[$a]).'"> ';
            $input .= (!isset($field['before']))? '': $field['before'];
            $input .= $chek[$a]
                    .'</label>';
            $input .= (!isset($field['after']))? '': $field['after'];
        }
        return $input;
    }

    function get_type_radio($field){
        $radio = explode('#',$field['field_select']);
        $count_field = count($radio);
        $input = '';
        for($a=0;$a<$count_field;$a++){
            $input .='<label class="block-label" for="'.$this->slug.'_'.$a.'">'
                    . '<input '.$this->required.' '.checked($this->value,$radio[$a],false).' type="radio" '.checked($a,0,false).' id="'.$this->slug.'_'.$a.'" name="'.$this->slug.'" value="'.trim($radio[$a]).'"> '
                    .$radio[$a]
                    .'</label>';
        }
        return $input;
    }

    function get_field_value($field,$value=false){
        $show = '';
        if($field['type']=='text'&&$value)
                $show = ' <span>'.esc_textarea($value).'</span>';
        if($field['type']=='tel'&&$value)
                $show = ' <span>'.esc_textarea($value).'</span>';
        if($field['type']=='email'&&$value)
                $show = ' <span><a rel="nofolow" target="_blank" href="mailto:'.$value.'">'.esc_textarea($value).'</a></span>';
        if($field['type']=='url'&&$value)
                $show = ' <span><a rel="nofolow" target="_blank" href="'.$value.'">'.esc_textarea($value).'</a></span>';
        if($field['type']=='time'&&$value)
                $show = ' <span>'.esc_textarea($value).'</span>';
        if($field['type']=='date'&&$value)
                $show = ' <span>'.esc_textarea($value).'</span>';
        if($field['type']=='number'&&$value)
                $show = ' <span>'.esc_textarea($value).'</span>';
        if($field['type']=='select'&&$value||$field['type']=='radio'&&$value)
                $show = ' <span>'.esc_textarea($value).'</span>';
        if($field['type']=='checkbox'){
                $chek_field = '';
                if(is_array($value)) $chek_field = implode(', ',$value);
                if($chek_field)
                    $show = $chek_field;
        }
        if($field['type']=='textarea'&&$value)
                $show = '<p>'.nl2br(esc_textarea($value));

        if($show) $show = '<p><b>'.$field['title'].':</b> '.$show.'</p>';

        return $show;
    }

    function register_user_metas($user_id){

        $get_fields = get_option( 'custom_profile_field' );

        if(!$get_fields) return false;

	$_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);

        foreach((array)$get_fields as $custom_field){
            $slug = $custom_field['slug'];
            if($custom_field['type']=='checkbox'){
                $select = explode('#',$custom_field['field_select']);
                $count_field = count($select);
                if(isset($_POST[$slug])){
                    foreach($_POST[$slug] as $val){
                        for($a=0;$a<$count_field;$a++){
                            if(trim($select[$a])==$val){
                                $vals[] = $val;
                            }
                        }
                    }

                    if($vals) update_usermeta($user_id, $slug, $vals);
                }

            }else{
                if($_POST[$slug]) update_usermeta($user_id, $slug, $_POST[$slug]);
            }
        }

    }

}

function rcl_get_custom_fields($post_id,$posttype=false,$id_form=false){

    if($post_id){
            $post = get_post($post_id);
            $posttype = $post->post_type;
        }

    switch($posttype){
        case 'post':
                if(isset($post)) $id_form = get_post_meta($post->ID,'publicform-id',1);
                if(!$id_form) $id_form = 1;
                $id_field = 'custom_public_fields_'.$id_form;
        break;
        case 'products': $id_field = 'custom_saleform_fields'; break;
        default: $id_field = 'custom_fields_'.$posttype;
    }

    return get_option($id_field);
}

function rcl_get_custom_post_meta($post_id){

    $get_fields = rcl_get_custom_fields($post_id);

    if($get_fields){

        $cf = new Rcl_Custom_Fields();
        foreach((array)$get_fields as $custom_field){
            $p_meta = get_post_meta($post_id,$custom_field['slug'],true);
            $show_custom_field .= $cf->get_field_value($custom_field,$p_meta);
        }

        return $show_custom_field;
    }
}

function rcl_get_post_meta($content){
    global $post,$rcl_options;
    if(!isset($rcl_options['pm_rcl'])||!$rcl_options['pm_rcl'])return $content;
    $pm = rcl_get_custom_post_meta($post->ID);
    if(!$rcl_options['pm_place']) $content .= $pm;
    else $content = $pm.$content;
    return $content;
}
if(!is_admin()) add_filter('the_content','rcl_get_post_meta');

