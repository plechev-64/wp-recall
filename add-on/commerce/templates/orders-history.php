<?php
    /*Шаблон для отображения содержимого истории заказов пользователя*/
    /*Данный шаблон можно разместить в папке используемого шаблона /wp-content/wp-recall/templates/ и он будет подключаться оттуда*/
?>
<?php global $rcl_orders; ?>
<div class="order-data rcl-form">
    <table>
        <tr>
            <th><?php _e('Order number','wp-recall'); ?></th>
            <th><?php _e('Order date','wp-recall'); ?></th>
            <th><?php _e('Number of goods','wp-recall'); ?></th>
            <th><?php _e('Sum','wp-recall'); ?></th>
            <th><?php _e('Order status','wp-recall'); ?></th>
        </tr>
        <?php foreach($rcl_orders as $order){ ?>
            <tr>
                <td>
                    <a href="<?php echo rcl_get_tab_permalink($order->user_id,'orders'); ?>&order-id=<?php echo $order->order_id; ?>">
                        <?php echo $order->order_id; ?>
                    </a>
                </td>
                <td><?php echo $order->order_date; ?></td>
                <td><?php echo $order->products_amount; ?></td>
                <td><?php echo $order->order_price.' '.rcl_get_primary_currency(1); ?></td>
                <td><?php echo rcl_get_status_name_order($order->order_status); ?></td>
            </tr>
        <?php } ?>
        <tr>
            <th colspan="5"></th>
        </tr>
    </table>
</div>
