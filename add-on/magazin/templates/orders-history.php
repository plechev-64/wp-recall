<?php
    /*Шаблон для отображения содержимого истории заказов пользователя*/
    /*Данный шаблон можно разместить в папке используемого шаблона /wp-content/wp-recall/templates/ и он будет подключаться оттуда*/
?>
<?php global $orders,$order,$user_ID; ?>
<div class="order-data">
    <table>
        <tr>
            <th><?php _e('Order number','wp-recall'); ?></th>
            <th><?php _e('Order date','wp-recall'); ?></th>
            <th><?php _e('Number of goods','wp-recall'); ?></th>
            <th><?php _e('Sum','wp-recall'); ?></th>
            <th><?php _e('Order status','wp-recall'); ?></th>
        </tr>
        <?php foreach($orders as $data){ rcl_setup_orderdata($data); ?>
            <tr>
                <td>
                    <a href="<?php echo rcl_format_url(get_author_posts_url($order->order_author),'orders'); ?>&order-id=<?php rcl_order_ID(); ?>">
                        <?php rcl_order_ID(); ?>
                    </a>
                </td>
                <td><?php rcl_order_date(); ?></td>
                <td><?php rcl_number_products(); ?></td>
                <td><?php rcl_order_price(); ?></td>
                <td><?php rcl_order_status(); ?></td>
            </tr>
        <?php } ?>
        <tr>
                <th colspan="5"></th>
        </tr>
    </table>
</div>
