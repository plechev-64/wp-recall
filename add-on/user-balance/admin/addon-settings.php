<?php

add_filter('admin_options_rmag','rcl_user_account_options',10);
function rcl_user_account_options($content){

        global $rcl_options;
	$rcl_options = get_option('primary-rmag-options');

        require_once RCL_PATH.'classes/class-rcl-options.php';

        $opt = new Rcl_Options(__FILE__);

        $pay_options = array( __('Not used','wp-recall') );

        $content .= '<span id="title-user-balance" data-addon="user-balance" data-url="'.admin_url('admin.php?page='.$_GET['page'].'&rcl-addon-options=user-balance').'" class="title-option active">'
                . '<span class="wp-menu-image dashicons-before dashicons-admin-generic"></span> '
                . __('Payment systems','wp-recall')
                .'</span>
	<div id="options-user-balance" style="display:block" class="wrap-recall-options">';

        $content .= $opt->option_block(
            array(
                $opt->title('Валюта сайта'),
                    $opt->label('Основная валюта'),
                    $opt->option('select',array(
                    'name'=>'primary_cur',
                    'options'=>rcl_get_currency()
                    )
                )
            )
        );
        
        $init_gateway = true;
        $pay_options_child = apply_filters('rcl_pay_child_option','');
        if(!$pay_options_child){
            $init_gateway = false;
            $pay_options_child = '<p style="color:red;">Похоже ни одного подключения не настроено. Скачайте <a href="https://codeseller.ru/product_tag/platezhnye-sistemy/" target="_blank">одно из доступных дополнений</a> для подключения к платежному агрегатору и настройте его</p>';
        }
        
        $payment_opt = array( __('Payment from user’s personal account','wp-recall') );
        
        if($init_gateway){
            $payment_opt[] = __('Payment through payment systems','wp-recall');
            $payment_opt[] = __('Offer both options','wp-recall');
        }

        $content .= $opt->option_block(
            array(
                $opt->title(__('Payment','wp-recall')),

                $opt->label(__('Type of payment','wp-recall')),
                $opt->option('select',array(
                    'name'=>'type_order_payment',
                    'options'=>$payment_opt
                )),
                $opt->notice(__('If the connection to the payment aggregator not used, apply "Funds from user personal account"!','wp-recall')),

                $opt->title(__('The connection to payment aggregator','wp-recall')),
                $opt->label(__('Applied connection type','wp-recall')),
                $opt->option('select',array(
                    'name'=>'connect_sale',
                    'parent'=>true,
                    'options'=>apply_filters('rcl_pay_option',$pay_options)
                )),

                $pay_options_child

            )
        );
        
        if($init_gateway){
            $content .= $opt->option_block(
                array(
                    $opt->title(__('Service page of payment systems','wp-recall')),
                    $opt->notice('<p>1. Создайте на своем сайте четыре страницы:</p>
                    - пустую для success<br>
                    - пустую для result<br>
                    - одну с текстом о неудачной оплате (fail)<br>
                    - одну с текстом об удачной оплате<br>
                    Название и URL созданных страниц могут быть произвольными.<br>
                    <p>2. Укажите здесь какие страницы и для чего вы создали. </p>
                    <p>3. В настройках своего аккаунта платежной системы укажите URL страницы для fail, success и result</p>'),

                    $opt->label(__('RESULT Page','wp-recall')),
                    wp_dropdown_pages( array(
                            'selected'   => isset($rcl_options['page_result_pay'])? $rcl_options['page_result_pay']: '',
                            'name'       => 'global[page_result_pay]',
                            'show_option_none' => __('Not selected','wp-recall'),
                            'echo'             => 0 )
                    ),
                    
                    $opt->label(__('SUCCESS Page','wp-recall')),
                    wp_dropdown_pages( array(
                            'selected'   => isset($rcl_options['page_success_pay'])? $rcl_options['page_success_pay']: '',
                            'name'       => 'global[page_success_pay]',
                            'show_option_none' => __('Not selected','wp-recall'),
                            'echo'             => 0 )
                    ),
                    
                    $opt->label(__('FAIL Page','wp-recall')),
                    wp_dropdown_pages( array(
                            'selected'   => isset($rcl_options['page_fail_pay'])? $rcl_options['page_fail_pay']: '',
                            'name'       => 'global[page_fail_pay]',
                            'show_option_none' => __('Not selected','wp-recall'),
                            'echo'             => 0 )
                    ),
                    
                    $opt->label(__('Successful payment page','wp-recall')),
                    wp_dropdown_pages( array(
                            'selected'   => isset($rcl_options['page_successfully_pay'])? $rcl_options['page_successfully_pay']: '',
                            'name'       => 'global[page_successfully_pay]',
                            'show_option_none' => __('Not selected','wp-recall'),
                            'echo'             => 0 )
                    )
                )
            );
        }

        $content .= '</div>';

	return $content;
}

