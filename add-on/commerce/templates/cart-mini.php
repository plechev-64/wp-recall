<?php
    /*Шаблон для отображения содержимого шорткода minibasket - малой корзины пользователя*/
    /*Данный шаблон можно разместить в папке используемого шаблона /wp-content/wp-recall/templates/ и он будет подключаться оттуда*/
?>
<?php $Cart = new Rcl_Cart(); ?>
<div class="rcl-mini-cart <?php echo ($Cart->products_amount)? 'not-empty': 'empty-cart'; ?>">
    
    <div class="cart-icon">
        <i class="fa fa-shopping-cart"></i>
    </div>
    <div><?php _e('In your cart','wp-recall'); ?>:</div>
    <div class="cart-content">
        <span class="products-amount">
            <?php _e('Total number of goods','wp-recall'); ?>: <span class="rcl-order-amount"><?php echo $Cart->products_amount; ?></span> шт.
        </span>
        <span class="cart-price">
            <?php _e('Total amount','wp-recall'); ?>: <span class="rcl-order-price"><?php echo $Cart->order_price; ?></span>
        </span>
        <span class="cart-url">
            <a href="<?php echo $Cart->cart_url; ?>"><?php _e('Go to cart','wp-recall'); ?></a>
        </span>      
    </div>
    <div class="empty-notice"><?php _e('Empty','wp-recall'); ?></div>
</div>
