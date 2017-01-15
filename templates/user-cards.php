<?php global $rcl_user,$rcl_users_set; 
// если есть вызов в data атрибута comments_count
$uc_count = '';
    if(in_array('comments_count', $rcl_users_set->data)){
        $uc_count .= '<div class="u_card_half">Комментариев<br/><span>';
        $uc_count .= $rcl_user->comments_count;
        if(!isset($rcl_user->comments_count)){
            $uc_count .= '0';
        }
        $uc_count .= '</span></div>';
    }
// если есть вызов в data атрибута posts_count
$up_count = '';
    if(in_array('posts_count', $rcl_users_set->data)){
        $up_count .= '<div class="u_card_half">Публикаций<br/><span>';
        $up_count .= $rcl_user->posts_count;
        if(!isset($rcl_user->posts_count)){
            $up_count .= '0';
        }
        $up_count .= '</span></div>';
    }
    
    $style = (isset($rcl_users_set->width))? 'style="width:'.$rcl_users_set->width.'px"': '';
    
?>
<div class="user-single" <?php echo $style; ?> data-user-id="<?php echo $rcl_user->ID; ?>">
    <div class="u_card_top">
        <?php rcl_user_rayting(); ?>
        <?php rcl_user_action(2); ?>
        <div class="thumb-user">
            <a title="Перейти в кабинет пользователя" href="<?php rcl_user_url(); ?>">
                <?php rcl_user_avatar(200); ?>
            </a>
        </div>
        <div class="u_card_name">
            <?php rcl_user_name(); ?>
        </div>
    </div>
    <div class="u_card_bottom">
        <?php
            echo $uc_count;
            echo $up_count;
        ?>
    </div>
</div>