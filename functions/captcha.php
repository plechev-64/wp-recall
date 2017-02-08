<?php

function rcl_get_simple_captcha($args = false){
    
    if(!class_exists('ReallySimpleCaptcha')) return false;

    $captcha = new ReallySimpleCaptcha();
    
    $captcha->font_size = (isset($args['font_size']))? $args['font_size']: '16';
    $captcha->char_length = (isset($args['char_length']))? $args['char_length']: '4';
    $captcha->img_size = (isset($args['img_size']) && is_array($args['img_size']))? $args['img_size']: array( '72', '24' );

    $captcha->chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';   
    $captcha->fg = array( '0', '0', '0' );
    $captcha->bg = array( '255', '255', '255' );
    $captcha->font_char_width = '15';
    $captcha->img_type = 'png';
    $captcha->base = array( '6', '18' );

    $rcl_captcha_word = $captcha->generate_random_word();
    $rcl_captcha_prefix = mt_rand();
    $rcl_captcha_image_name = $captcha->generate_image($rcl_captcha_prefix, $rcl_captcha_word);
    $rcl_captcha_image_url =  plugins_url('really-simple-captcha/tmp/');
    $rcl_captcha_image_src = $rcl_captcha_image_url . $rcl_captcha_image_name;
    
    $result = array(
        'img_size' => $captcha->img_size,
        'char_length' => $captcha->char_length,
        'img_src' => $rcl_captcha_image_src,
        'prefix' => $rcl_captcha_prefix
    );
    
    return (object)$result;
    
}

function rcl_captcha_check_correct($code,$prefix){
    
    if(!class_exists('ReallySimpleCaptcha')) return true;
    
    $rcl_captcha = new ReallySimpleCaptcha();

    $rcl_captcha_prefix = sanitize_text_field($prefix);
    $rcl_captcha_code = sanitize_text_field($code);
    
    $rcl_captcha_correct = false;
    
    $rcl_captcha_correct = $rcl_captcha->check( $rcl_captcha_prefix, $rcl_captcha_code );
    
    $rcl_captcha->remove($rcl_captcha_prefix);
    $rcl_captcha->cleanup();
    
    return $rcl_captcha_correct;
}

add_filter('regform_fields_rcl','rcl_add_regform_captcha',999);
function rcl_add_regform_captcha($fields){

    $captcha = rcl_get_simple_captcha();
    
    if(!$captcha) return $fields;

    $fields .= '
      <div class="form-block-rcl">
        <label>'.__('Enter characters','wp-recall').' <span class="required">*</span></label>
        <img src="'.$captcha->img_src.'" alt="captcha" width="'.$captcha->img_size[0].'" height="'.$captcha->img_size[1].'" />
        <input id="rcl_captcha_code" required name="rcl_captcha_code" style="width: 160px;" size="'.$captcha->char_length.'" type="text" />
        <input id="rcl_captcha_prefix" name="rcl_captcha_prefix" type="hidden" value="'.$captcha->prefix.'" />
     </div>';

    return $fields;

}

add_action('rcl_registration_errors','rcl_check_register_captcha');
function rcl_check_register_captcha($errors){
    
    $rcl_captcha_correct = rcl_captcha_check_correct($_POST['rcl_captcha_code'],$_POST['rcl_captcha_prefix']);

    if ( ! $rcl_captcha_correct ) {
        $errors = new WP_Error();
        $errors->add( 'rcl_register_captcha', __('Incorrect CAPTCHA!','wp-recall') );
    }
    
    return $errors;
}

add_filter('rcl_public_form','rcl_add_public_form_captcha',100);
function rcl_add_public_form_captcha($form){
    global $user_ID;
    
    if($user_ID) return $form;
    
    $captcha = rcl_get_simple_captcha(array('img_size'=>array(72,29)));
    
    if(!$captcha) return $form;
    
    $form .= '
      <div class="form-block-rcl">
        <label>'.__('Enter characters','wp-recall').' <span class="required">*</span></label>
        <img src="'.$captcha->img_src.'" alt="captcha" width="'.$captcha->img_size[0].'" height="'.$captcha->img_size[1].'" />
        <input id="rcl_captcha_code" required name="rcl_captcha_code" style="width: 160px;" size="'.$captcha->char_length.'" type="text" />
        <input id="rcl_captcha_prefix" name="rcl_captcha_prefix" type="hidden" value="'.$captcha->prefix.'" />
     </div>';
    
    return $form;
}

add_action('init_update_post_rcl','rcl_check_public_form_captcha',10);
function rcl_check_public_form_captcha(){
    global $user_ID;
    
    if(!$user_ID && isset($_POST['rcl_captcha_prefix'])){
            
        $rcl_captcha_correct = rcl_captcha_check_correct($_POST['rcl_captcha_code'],$_POST['rcl_captcha_prefix']);

        if ( ! $rcl_captcha_correct ) {
            wp_die( __('Incorrect CAPTCHA!','wp-recall') );
        }

    }
}