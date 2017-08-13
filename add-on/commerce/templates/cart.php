<?php
/*Шаблон для отображения содержимого шорткода basket - полной корзины пользователя*/
/*Данный шаблон можно разместить в папке используемого шаблона /wp-content/wp-recall/templates/ и он будет подключаться оттуда*/
?>

<?php global $post; $Cart = new Rcl_Cart(); ?>

<?php do_action('rcl_cart_before'); ?>

<table class="order-table rcl-form">
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
    <?php foreach($Cart->products as $k => $product): setup_postdata($post = get_post($product->product_id)); ?>
        <tr id="product-<?php the_ID(); ?>-<?php echo $k; ?>" class="product-box">
            <td class="column-product-name">
                <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                <p><?php rcl_product_excerpt($post->ID); ?></p>
                <?php rcl_product_variation_list($product->variations); ?>
            </td>
            <td class="column-product-price">
                <?php echo $product->product_price; ?> <?php echo rcl_get_primary_currency(1); ?>
            </td>
            <td class="column-product-amount">
                <div class="quantity-selector">
                    <a class="edit-amount add-product" onclick="rcl_cart_add_product(<?php echo $product->product_id; ?>,<?php echo $k; ?>);return false;" href="#">
                        <i class="fa fa-plus"></i>
                    </a>
                    <span class="product-amount">
                        <?php echo $product->product_amount; ?>
                    </span>
                    <a class="edit-amount remove-product" onclick="rcl_cart_remove_product(<?php echo $product->product_id; ?>,<?php echo $k; ?>);return false;" href="#">
                        <i class="fa fa-minus"></i>
                    </a>
                </div>
            </td>
            <td class="column-product-sumprice">
                <span class="product-sumprice">
                    <?php echo $product->product_price * $product->product_amount; ?>
                </span> 
                <?php echo rcl_get_primary_currency(1); ?>
            </td>
        </tr>
    <?php endforeach; wp_reset_postdata(); ?>
    <tr>
        <th colspan="2"><?php _e('Total','wp-recall'); ?></th>
        <th class="column-product-amount total-amount">
            <span class="rcl-order-amount">
                <?php echo $Cart->products_amount; ?>
            </span>
        </th>
        <th class="column-product-sumprice total-sumprice">
            <span class="rcl-order-price">
                <?php echo $Cart->order_price; ?>
            </span> 
            <?php echo rcl_get_primary_currency(1); ?>
        </th>
    </tr>
</table>

<?php do_action('rcl_cart'); ?>
