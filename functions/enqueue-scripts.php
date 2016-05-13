<?php

function rcl_pageform_scripts(){
    wp_enqueue_script( 'jquery' );
    wp_enqueue_script( 'rcl-page-form', RCL_URL.'js/page_form.js', array('rcl-primary-scripts'));
}

function rcl_floatform_scripts(){
    wp_enqueue_script( 'jquery' );
    wp_enqueue_script( 'rcl-float-form', RCL_URL.'js/float_form.js', array('rcl-primary-scripts'));
}

function rcl_sortable_scripts(){
    wp_enqueue_script( 'jquery' );
    wp_enqueue_script('jquery-ui-sortable');
}

function rcl_resizable_scripts(){
    wp_enqueue_script( 'jquery' );
    wp_enqueue_script('jquery-ui-resizable');
}

function rcl_datepicker_scripts(){
    wp_enqueue_style( 'jquery-ui-datepicker', RCL_URL.'js/datepicker/style.css' );
    wp_enqueue_script( 'jquery' );
    wp_enqueue_script('jquery-ui-core');
    wp_enqueue_script('jquery-ui-datepicker');
    wp_enqueue_script( 'custom-datepicker', RCL_URL.'js/datepicker/datepicker-init.js', array('jquery-ui-datepicker') );
}

function rcl_bxslider_scripts(){
    wp_enqueue_style( 'bx-slider', RCL_URL.'js/jquery.bxslider/jquery.bxslider.css' );
    wp_enqueue_script( 'jquery' );
    wp_enqueue_script( 'bx-slider', RCL_URL.'js/jquery.bxslider/jquery.bxslider.min.js' );
    wp_enqueue_script( 'custom-bx-slider', RCL_URL.'js/slider.js', array('bx-slider','rcl-header-scripts'));
}

function rcl_dialog_scripts(){
    wp_enqueue_script( 'jquery' );
    wp_enqueue_script( 'jquery-ui-dialog' );
    wp_enqueue_style('wp-jquery-ui-dialog');
}

function rcl_webcam_scripts(){
    wp_enqueue_script( 'jquery' );
    wp_enqueue_script( 'say-cheese', RCL_URL.'js/say-cheese/say-cheese.js', array(), VER_RCL,true );
}

function rcl_fileupload_scripts(){
    wp_enqueue_script( 'jquery' );
    wp_enqueue_script( 'jquery-ui-widget', RCL_URL.'js/fileupload/js/vendor/jquery.ui.widget.js', array(), VER_RCL,true );

    //перенесено из blueimp.github.io/JavaScript-Load-Image/js/load-image.all.min.js
    wp_enqueue_script( 'load-image', RCL_URL.'js/fileupload/js/load-image.all.min.js', array(), VER_RCL,true );
    //перенесено из blueimp.github.io/JavaScript-Canvas-to-Blob/js/canvas-to-blob.min.js
    wp_enqueue_script( 'canvas-to-blob', RCL_URL.'js/fileupload/js/canvas-to-blob.min.js', array(), VER_RCL,true );

    wp_enqueue_script( 'jquery-iframe-transport', RCL_URL.'js/fileupload/js/jquery.iframe-transport.js', array(), VER_RCL,true );
    wp_enqueue_script( 'jquery-fileupload', RCL_URL.'js/fileupload/js/jquery.fileupload.js', array(), VER_RCL,true );
    wp_enqueue_script( 'jquery-fileupload-process', RCL_URL.'js/fileupload/js/jquery.fileupload-process.js', array(), VER_RCL,true );
    wp_enqueue_script( 'jquery-fileupload-image', RCL_URL.'js/fileupload/js/jquery.fileupload-image.js', array(), VER_RCL,true );
    
    rcl_fileapi_scripts();
}

function rcl_crop_scripts(){
    wp_enqueue_script( 'jquery' );
    wp_enqueue_style( 'jcrop-master-css', RCL_URL.'js/jcrop.master/css/jquery.Jcrop.min.css' );
    wp_enqueue_script( 'jcrop-master', RCL_URL.'js/jcrop.master/js/jquery.Jcrop.min.js', array(), VER_RCL,true );
}

function rcl_rangyinputs_scripts(){
    if(defined( 'DOING_AJAX' ) && DOING_AJAX){
        return '<script type="text/javascript" src="'.RCL_URL.'js/rangyinputs.js"></script>';
    }else{
        wp_enqueue_script( 'jquery' );
        wp_enqueue_script( 'rangyinputs', RCL_URL.'js/rangyinputs.js' );
    }
}

function rcl_primary_scripts(){
    wp_enqueue_script( 'jquery' );
    wp_enqueue_script( 'rcl-primary-scripts', RCL_URL.'js/recall.js', array(), VER_RCL );
    if(!file_exists(RCL_UPLOAD_PATH.'scripts/header-scripts.js')){
        rcl_update_scripts;
    }
    wp_enqueue_script( 'rcl-header-scripts', RCL_UPLOAD_URL.'scripts/header-scripts.js', array('rcl-primary-scripts'), VER_RCL );
}

function rcl_font_awesome_style(){
    if( wp_style_is( 'font-awesome' ) ) wp_deregister_style('font-awesome');
    wp_enqueue_style( 'font-awesome', RCL_URL.'css/font-awesome/css/font-awesome.min.css', array(), '4.5.0' );
}

function rcl_plugin_style(){
    global $rcl_options;
    if(isset($rcl_options['minify_css'])&&$rcl_options['minify_css']==1){
        wp_enqueue_style( 'rcl-style', RCL_UPLOAD_URL.'css/minify.css' );
    }else{
        $css_ar = array('style','recbar','regform','slider','users');
        foreach($css_ar as $name){
            wp_enqueue_style( 'style_'.$name, RCL_URL.'css/'.$name.'.css' );           
        }
    }
}

function rcl_theme_style(){
    global $rcl_options;
    if(isset($rcl_options['color_theme'])&&$rcl_options['color_theme']){
        $dirs   = array(RCL_PATH.'css/themes',RCL_TAKEPATH.'themes');
        foreach($dirs as $dir){
            if(!file_exists($dir.'/'.$rcl_options['color_theme'].'.css')) continue;
            wp_enqueue_style( 'rcl-theme', rcl_path_to_url($dir.'/'.$rcl_options['color_theme'].'.css') );
            break;
        }
    }
}

add_action('login_enqueue_scripts','rcl_enqueue_wp_form_scripts',1);
function rcl_enqueue_wp_form_scripts(){
    wp_enqueue_script( 'jquery' );
    //wp_enqueue_style( 'rcl-form', RCL_URL.'css/regform.css' );
    echo '<link rel="stylesheet" id="rcl-form-css" href="'.RCL_URL.'css/regform.css" type="text/css" media="all">'
            . '<script type="text/javascript" src="'.RCL_URL.'js/recall.js"></script>';
}

function rcl_frontend_scripts(){
	global $rcl_options,$user_LK,$user_ID,$post;
	if(!isset($rcl_options['font_icons']))  $rcl_options['font_icons']=1;
        
        if($user_LK){
            rcl_dialog_scripts();
            rcl_fileupload_scripts();
        }

	rcl_font_awesome_style();

	rcl_plugin_style();
        
	rcl_theme_style();

	rcl_primary_scripts();
        
        $local = array(
            'save' => __('Save','wp-recall'),
            'close' =>__('Close','wp-recall'),
            'wait' => __('Please wait','wp-recall'),
            'preview' => __('Preview','wp-recall'),
            'error' => __('Error','wp-recall'),
            'loading' => __('Loading','wp-recall')
        );
        
        if($rcl_options['difficulty_parole']&&(!$user_ID||$user_LK==$user_ID)){
            $local['pass0'] = __('Very weak','wp-recall');
            $local['pass1'] = __('Weak','wp-recall');
            $local['pass2'] = __('Worse than average','wp-recall');
            $local['pass3'] = __('Average','wp-recall');
            $local['pass4'] = __('Reliable','wp-recall');
            $local['pass5'] = __('Strong','wp-recall');
        }
        
        $data = array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'wpurl' => get_bloginfo('wpurl'),
            'rcl_url' => RCL_URL,
            'user_ID' => $user_ID,
            'nonce' => wp_create_nonce( 'rcl-post-nonce' ),
            'local' => apply_filters('rcl_js_localize',$local)
        );
        
        $data['post_ID'] = (isset($post->ID)&&$post->ID)? $post->ID: 0;
        $data['mobile'] = (wp_is_mobile())? 1: 0;
        
        wp_localize_script( 'jquery', 'Rcl',$data);

}

function rcl_admin_scrips(){
    wp_enqueue_style( 'rcl-admin-style', RCL_URL.'rcl-admin/admin.css' );
    wp_enqueue_style( 'wp-color-picker' ); 
    wp_enqueue_script( 'jquery' );
    wp_enqueue_script( 'rcl-admin-scripts', RCL_URL.'rcl-admin/admin.js', array('wp-color-picker'), VER_RCL );
}

function rcl_fileapi_scripts() {
    if(file_exists(RCL_UPLOAD_PATH.'scripts/footer-scripts.js')){
        wp_enqueue_script( 'jquery' );
        wp_enqueue_script( 'rcl-footer-scripts', RCL_UPLOAD_URL.'scripts/footer-scripts.js', array(), VER_RCL, true );
    }
}

