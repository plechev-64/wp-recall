<?
/*Шаблон для отображения динамичного содержимого содержимого шорткода minibasket*/
/*Данный шаблон можно разместить в папке используемого шаблона /wp-content/wp-recall/templates/ и он будет подключаться оттуда*/
?>
<?php global $CartData; ?>
<div>
    <?php _e('Total number of goods','wp-recall'); ?>: <span class="cart-numbers"><?php echo $CartData->numberproducts; ?></span> шт.
</div>
<div>
    <?php _e('Total amount','wp-recall'); ?>: <span class="cart-summa"><?php echo rcl_add_primary_currency_price($CartData->cart_price); ?></span>
</div>
<a href="<?php echo get_permalink($CartData->cart_url); ?>"><?php _e('Go to cart','wp-recall'); ?></a>
