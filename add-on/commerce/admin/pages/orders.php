<?php

global $Rcl_History_Orders;

$Rcl_History_Orders->prepare_items();

echo '</pre><div class="wrap"><h2>'.__('Order history','wp-recall').'</h2>';

    echo rcl_get_chart_orders($Rcl_History_Orders->items); ?>
    <form method="get">
        <?php $currentStatus = (isset($_GET['sts']))? $_GET['sts']: 0;
        $sts = rcl_order_statuses(); ?>
        <select name="sts" id="filter-by-status">
            <option<?php selected( $currentStatus, 0 ); ?> value="0"><?php _e( 'All statuses', 'wp-recall' ); ?></option>
            <?php foreach ( $sts as $id=>$name ) {
                    printf( "<option %s value='%s'>%s</option>\n",
                        selected( $id, $currentStatus, false ),
                        $id,
                        $name
                    );
            } ?>
        </select>
        <span class="rcl-datepicker-box">
            <input type="text" name="date-start" id="orders-date-start" onclick="rcl_show_datepicker(this);" class="rcl-datepicker" value="<?php echo (isset($_GET['date-start']))? $_GET['date-start']: ''; ?>">
        </span>
        <span class="date-separator">-</span>
        <span class="rcl-datepicker-box">
            <input type="text" name="date-end" id="orders-date-end" onclick="rcl_show_datepicker(this);" class="rcl-datepicker" value="<?php echo (isset($_GET['date-end']))? $_GET['date-end']: ''; ?>">
        </span>
        <input type="hidden" name="page" value="manage-rmag">
        <?php submit_button( __( 'Filter', 'wp-recall' ), 'button', '', false, array('id' => 'search-submit') ); ?>
    </form>
    <form method="post">
        <input type="hidden" name="page" value="manage-rmag">
        <?php
        $Rcl_History_Orders->search_box( __( 'Search', 'wp-recall' ), 'search_id' );
        $Rcl_History_Orders->display(); ?>
    </form>
</div>