<?php

/**
 * Description of Rcl_Payments
 *
 * @author Андрей
 */

class Rcl_Payments extends Rcl_Query {
    
    public $table_as = 'rcl_payments';
    public $table_cols = array(
                'ID',
                'payment_id',
                'user_id',
                'pay_amount',
                'time_action',
                'pay_system',
                'pay_type'
            );
    
    function __construct() {       
        $this->table = RMAG_PREF.'pay_results';
    }
    
}
