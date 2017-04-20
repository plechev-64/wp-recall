<?php
    /*Шаблон для отображения содержимого отдельного заказа,
    также используется при формировании письма-уведомления
    о заказе и его содержимом на почту пользователя (поэтому есть указание bordercolor и border для тега table)*/
/*Данный шаблон можно разместить в папке используемого шаблона /wp-content/wp-recall/templates/ и он будет подключаться оттуда*/
?>
<?php global $rclOrder,$post; ?>

<?php do_action('rcl_order_before'); ?>

<table bordercolor="сссссс" border="1" cellpadding="5" class="order-table rcl-form">
    <tr>
        <th class="column-product-name">
            <?php _e('Product','wp-recall'); ?>
        </th>
        <th class="column-product-price">
            <?php _e('Price','wp-recall'); ?>
        </th>
        <th class="column-product-amount">
            <?php _e('Amount','wp-recall'); ?>
        </th>
        <th class="column-product-sumprice">
            <?php _e('Sum','wp-recall'); ?>
        </th>
    </tr>
    <?php foreach($rclOrder->products as $product): setup_postdata($post = get_post($product->product_id)); ?>
        <tr id="product-<?php the_ID(); ?>" class="product-box">
            <td class="column-product-name">
                <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                <p><?php rcl_product_excerpt($post->ID); ?></p>
                <?php rcl_product_variation_list($product->variations); ?>
            </td>
            <td class="column-product-price">
                <?php echo $product->product_price; ?> <?php echo rcl_get_primary_currency(0); ?>
            </td>
            <td class="column-product-amount">
                <span class="product-amount">
                    <?php echo $product->product_amount; ?>
                </span>
            </td>
            <td class="column-product-sumprice">
                <span class="product-sumprice">
                    <?php echo $product->product_price * $product->product_amount; ?>
                </span> 
                <?php echo rcl_get_primary_currency(0); ?>
            </td>
        </tr>
    <?php endforeach; wp_reset_postdata(); ?>
    <tr>
        <th colspan="2"><?php _e('Total','wp-recall'); ?></th>
        <th class="column-product-amount total-amount">
            <span class="rcl-order-amount">
                <?php echo $rclOrder->products_amount; ?>
            </span>
        </th>
        <th class="column-product-sumprice total-sumprice">
            <span class="rcl-order-price">
                <?php echo $rclOrder->order_price; ?>
            </span> 
            <?php echo rcl_get_primary_currency(0); ?>
        </th>
    </tr>
</table>

<?php do_action('rcl_order'); ?>