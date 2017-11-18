<?php global $rcl_user_URL,$user_ID; ?>

<div id="recallbar">
    <div class="rcb_left">
        
        <?php $rcb_menu = wp_nav_menu(array( 'echo'=>false,'theme_location' => 'recallbar','container_class'=>'rcb_menu','fallback_cb' => '__return_empty_string')); ?>
        <?php if($rcb_menu): ?>
        <div class="rcb_left_menu"><!-- блок rcb_left_menu должен появляться только если есть пункты в меню -->
            <i class="fa fa-bars" aria-hidden="true"></i>
            <?php echo $rcb_menu; ?>	
        </div>
        <?php endif; ?>
        
        <div class="rcb_icon">
            <a href="/">
                <i class="fa fa-home" aria-hidden="true"></i>
                <div class="rcb_hiden"><span><?php _e('Homepage','wp-recall'); ?></span></div>
            </a>
        </div>

        <?php if(!is_user_logged_in()): $logIn = rcl_get_option('login_form_recall'); ?>
        
        <?php if($logIn == 1){
            $page_in_out = rcl_format_url(get_permalink(rcl_get_option('page_login_form_recall')));
            $urls = array(
                $page_in_out . 'action-rcl=login',
                $page_in_out . 'action-rcl=register'
            );
        }else if($logIn == 2){
            $urls = array(
                wp_login_url('/'),
                wp_registration_url()
            );
        }else if($logIn == 3){ // Форма в виджете
                
        }else{
            $urls = array('#','#');
        } ?>
        
        <?php if(isset($urls)){ ?>
        <div class="rcb_icon">
            <a href="<?php echo $urls[0]; ?>" class="rcl-login">
                <i class="fa fa-sign-in" aria-hidden="true"></i><span><?php _e('Entry','wp-recall'); ?></span>
                <div class="rcb_hiden"><span><?php _e('Entry','wp-recall'); ?></span></div>
            </a>
        </div>
            <?php if(rcl_is_register_open()): ?>
                <div class="rcb_icon">
                    <a href="<?php echo $urls[1]; ?>" class="rcl-register">
                        <i class="fa fa-book" aria-hidden="true"></i><span><?php _e('Register','wp-recall'); ?></span>
                        <div class="rcb_hiden"><span><?php _e('Register','wp-recall'); ?></span></div>
                    </a>
                </div>
            <?php endif; ?>
        <?php } ?>
        
        <?php endif; ?>
        
        <?php do_action('rcl_bar_left_icons'); ?>
        
    </div>

    <div class="rcb_right">
        <div class="rcb_icons">
            <?php do_action('rcl_bar_print_icons'); ?>
        </div>
        
        <?php if(is_user_logged_in()): ?>
        
        <div class="rcb_right_menu">
            <i class="fa fa-ellipsis-h" aria-hidden="true"></i>
            <a href="<?php echo $rcl_user_URL; ?>"><?php echo get_avatar($user_ID,36); ?></a>
            <div class="pr_sub_menu">
                <?php do_action('rcl_bar_print_menu'); ?>
                <div class="rcb_line"><a href="<?php echo wp_logout_url('/'); ?>"><i class="fa fa-sign-out" aria-hidden="true"></i><span><?php _e('Exit','wp-recall'); ?></span></a></div>
            </div>    
        </div>
        
        <?php endif; ?>
    </div>
</div>
