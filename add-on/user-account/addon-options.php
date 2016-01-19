<?php

add_filter('admin_options_rmag','rcl_user_account_options',10);
function rcl_user_account_options($content){

        global $rcl_options;
	$rcl_options = get_option('primary-rmag-options');

        include_once RCL_PATH.'functions/rcl_options.php';

        $opt = new Rcl_Options(rcl_key_addon(pathinfo(__FILE__)));

        $pay_options = array( __('Not used','wp-recall') );

        $content .= '<span class="title-option active">'.__('Payment systems','wp-recall').'</span>
	<div id="options-'.rcl_key_addon(pathinfo(__FILE__)).'" style="display:block" class="wrap-recall-options">';

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

        $content .= $opt->option_block(
            array(
                $opt->title(__('Payment','wp-recall')),

                $opt->label(__('Type of payment','wp-recall')),
                $opt->option('select',array(
                    'name'=>'type_order_payment',
                    'options'=>array(
						__('Funds from the personal account user','wp-recall'),
                        __('Directly through the payment system','wp-recall'),
                        __('To offer both options','wp-recall')
                    )
                )),
                $opt->notice(__('If the connection to the payment aggregator not in use, it is possible to set only "Funds from the personal account user"!','wp-recall')),

                $opt->title(__('The connection to payment aggregator','wp-recall')),
                $opt->label(__('Used type of connection','wp-recall')),
                $opt->option('select',array(
                    'name'=>'connect_sale',
                    'parent'=>true,
                    'options'=>apply_filters('rcl_pay_option',$pay_options)
                )),

                apply_filters('rcl_pay_child_option',$pay_options_child)

            )
        );

        $content .= $opt->option_block(
            array(
                $opt->title(__('Service page payment systems','wp-recall')),
                $opt->notice('<p>1. Создайте на своем сайте четыре страницы:</p>
                - пустую для success<br>
                - пустую для result<br>
                - одну с текстом о неудачной оплате (fail)<br>
                - одну с текстом об удачной оплате<br>
                Название и URL созданных страниц могут быть произвольными.<br>
                <p>2. Укажите здесь какие страницы и для чего вы создали. </p>
                <p>3. В настройках своего аккаунта платежной системы укажите URL страницы для fail, success и result</p>'),

                $opt->label(__('Page RESULT','wp-recall')),
                wp_dropdown_pages( array(
                        'selected'   => $rcl_options['page_result_pay'],
                        'name'       => 'global[page_result_pay]',
                        'show_option_none' => __('Not selected','wp-recall'),
                        'echo'             => 0 )
                ),
                $opt->notice(__('For WalletOne: URL script','wp-recall')),
                $opt->notice(__('For Interkassa: URL of interaction','wp-recall')),
                $opt->notice(__('For Yandex.Cash: checkURL and avisoURL','wp-recall')),

                $opt->label(__('Page SUCCESS','wp-recall')),
                wp_dropdown_pages( array(
                        'selected'   => $rcl_options['page_success_pay'],
                        'name'       => 'global[page_success_pay]',
                        'show_option_none' => __('Not selected','wp-recall'),
                        'echo'             => 0 )
                ),
                $opt->notice(__('For WalletOne: not use','wp-recall')),
                $opt->notice(__('For Interkassa: not use','wp-recall')),

                $opt->label(__('Page FAIL','wp-recall')),
                wp_dropdown_pages( array(
                        'selected'   => $rcl_options['page_fail_pay'],
                        'name'       => 'global[page_fail_pay]',
                        'show_option_none' => __('Not selected','wp-recall'),
                        'echo'             => 0 )
                ),

                $opt->label(__('The successful payment page','wp-recall')),
                wp_dropdown_pages( array(
                        'selected'   => $rcl_options['page_successfully_pay'],
                        'name'       => 'global[page_successfully_pay]',
                        'show_option_none' => __('Not selected','wp-recall'),
                        'echo'             => 0 )
                )
            )
        );

        $content .= '</div>';

	return $content;
}

