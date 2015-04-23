<?php global $rcl_user_URL,$rcl_options; ?>
<div id="recallbar">
    <ul class="right-recall-menu">           
            <?php recallbar_right_side(); ?>
    </ul>
    <?php if(is_user_logged_in()){ ?>
        <ul class="left-recall-menu">               
                <li><a href="<?php echo $rcl_user_URL ?>"><i class="fa fa-user"></i><?php _e('Личный кабинет'); ?></a></li>
                <li><?php echo wp_loginout('', 0); ?></li>
        </ul>
    <?php }else{ ?>
        <ul class="left-recall-menu">
    <?php if($rcl_options['login_form_recall']==1){	?>

        <?php $redirect_url = get_redirect_url_rcl(get_permalink($rcl_options['page_login_form_recall'])); ?>

        <li><a href="<?php echo $redirect_url; ?>form=register"><i class="fa fa-book"></i><?php _e('Регистрация'); ?></a></li>
        <li><a href="<?php echo $redirect_url; ?>form=sign"><i class="fa fa-signin"></i><?php _e('Войти'); ?></a></li>

    <?php }else if($rcl_options['login_form_recall']==2){ ?>

        <li><?php echo wp_register('', '', 0) ?></li>
        <li><?php echo wp_loginout('', 0) ?></li>

    <?php }else if($rcl_options['login_form_recall']==3){ ?>
        
        <li><a href="/"><?php _e('Главная'); ?></a></li>
        
    <?php }else if(!$rcl_options['login_form_recall']){ ?>
        
        <li><a href="#" class="reglink"><i class="fa fa-book"></i><?php _e('Регистрация'); ?></a></li>
        <li><a href="#" class="sign-button"><i class="fa fa-signin"></i><?php _e('Войти'); ?></a></li>
        
    <?php } ?>
        </ul>

    <?php } ?>
        <?php wp_nav_menu('fallback_cb=null&container_class=recallbar&link_before=<i class=\'fa fa-caret-right\'></i>&theme_location=recallbar'); ?>

    <?php if ( is_admin_bar_showing() ){ ?> 
           <style>#recallbar{margin-top:28px;}</style>
    <?php } ?>

   </div>
   <div id="favs" style="display:none"></div>
   <div id="add_bookmarks" style="display:none"></div>

