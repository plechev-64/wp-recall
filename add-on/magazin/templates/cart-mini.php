<?php
    /*Шаблон для отображения содержимого шорткода minibasket - малой корзины пользователя*/
    /*Данный шаблон можно разместить в папке используемого шаблона /wp-content/wp-recall/templates/ и он будет подключаться оттуда*/
?>
<?php global $CartData; ?>
<div class="rcl-mini-cart">
    <div class="cart-icon">
            <i class="fa fa-shopping-cart"></i>
    </div>
    <div><?php _e('In your cart','wp-recall'); ?>:</div>

    <?php if($CartData->numberproducts): ?>

            <?php rcl_include_template('cart-mini-content.php',__FILE__); ?>

    <?php else: ?>

            <div class="empty-basket" style="text-align:center;"><?php _e('While empty','wp-recall'); ?></div>

    <?php endif; ?>
</div>
