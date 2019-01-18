<?php

/**
 * Description of Rcl_Payments
 *
 * @author Андрей
 */

class Rcl_Payments extends Rcl_Query {
    
    function __construct() {       

        $table = array(
            'name' => RMAG_PREF ."pay_results",
            'as' => 'rcl_payments',
            'cols' => array(
                'ID',
                'payment_id',
                'user_id',
                'pay_amount',
                'time_action',
                'pay_system',
                'pay_type'
            )
        );
        
        parent::__construct($table);
        
    }
    
}
