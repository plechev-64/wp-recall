<?php

add_filter('admin_options_rmag','rcl_user_account_options',10);
function rcl_user_account_options($content){

        global $rcl_options;
	$rcl_options = get_option('primary-rmag-options');

        include_once RCL_PATH.'functions/rcl_options.php';

        $opt = new Rcl_Options(rcl_key_addon(pathinfo(__FILE__)));

        $content .= '<span class="title-option active">'.__('Payment systems','rcl').'</span>
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
                $opt->title(__('Payment','rcl')),

                $opt->label(__('Type of payment','rcl')),
                $opt->option('select',array(
                    'name'=>'type_order_payment',
                    'options'=>array(
                        1=>__('Directly through the payment system','rcl'),
                        2=>__('To offer both options','rcl')
                    )
                )),
                $opt->notice(__('If the connection to the payment aggregator not in use, it is possible to set only "Funds from the personal account user"!','rcl')),

                $opt->title(__('The connection to payment aggregator','rcl')),
                $opt->label(__('Used type of connection','rcl')),
                $opt->option('select',array(
                    'name'=>'connect_sale',
                    'parent'=>true,
                    'options'=>array(
                        __('Not used','rcl'),
                        __('Robokassa','rcl'),
                        __('Interkassa','rcl'),
                        __('Yandex.Kassa','rcl').' ('.__('not tested','rcl').')',
                        __('WalletOne','rcl').' ('.__('not tested','rcl').')'
                    )
                )),
                $opt->child(
                    array(
                        'name'=>'connect_sale',
                        'value'=>1
                    ),
                    array(
                        $opt->title(__('Connection settings ROBOKASSA','rcl')),
                        $opt->label(__('The ID of the store','rcl')),
                        $opt->option('text',array('name'=>'robologin')),
                        $opt->label(__('1 Password','rcl')),
                        $opt->option('password',array('name'=>'onerobopass')),
                        $opt->label(__('2 Password','rcl')),
                        $opt->option('password',array('name'=>'tworobopass')),
                        $opt->label(__('The status of the account ROBOKASSA','rcl')),
                        $opt->option('select',array(
                            'name'=>'robotest',
                            'options'=>array(
                                __('Work','rcl'),
                                __('Test','rcl')
                            )
                        )),
                    )
                ),
                $opt->child(
                    array(
                        'name'=>'connect_sale',
                        'value'=>2
                    ),
                    array(
                        $opt->title(__('Connection settings Interkassa','rcl')),
                        $opt->label(__('Secret Key','rcl')),
                        $opt->option('password',array('name'=>'intersecretkey')),
                        $opt->label(__('Test Key','rcl')),
                        $opt->option('password',array('name'=>'intertestkey')),
                        $opt->label(__('The ID of the store','rcl')),
                        $opt->option('text',array('name'=>'interidshop')),
                        $opt->label(__('The status of the account Interkassa','rcl')),
                        $opt->option('select',array(
                            'name'=>'interkassatest',
                            'options'=>array(
                                __('Work','rcl'),
                                __('Test','rcl')
                            )
                        )),
                    )
                ),
                $opt->child(
                    array(
                        'name'=>'connect_sale',
                        'value'=>3
                    ),
                    array(
                        $opt->title(__('Connection settings Yandex.Kassa','rcl')),
                        $opt->label(__('ID cash','rcl')),
                        $opt->option('text',array('name'=>'shopid')),
                        $opt->label(__('The room showcases','rcl')),
                        $opt->option('text',array('name'=>'scid')),
                        $opt->label(__('The secret word','rcl')),
                        $opt->option('password',array('name'=>'secret_word')),
                    )
                ),
                $opt->child(
                    array(
                        'name'=>'connect_sale',
                        'value'=>4
                    ),
                    array(
                        $opt->title(__('Connection settings WalletOne','rcl')),
                        $opt->label(__('Merchant ID','rcl')),
                        $opt->option('text',array('name'=>'WO_MERCHANT_ID')),
                        $opt->label(__('The secret key','rcl')),
                        $opt->option('password',array('name'=>'WO_SECRET_KEY'))
                    )
                )
            )
        );

        $content .= $opt->option_block(
            array(
                $opt->title(__('Service page payment systems','rcl')),
                $opt->notice('<p>1. Создайте на своем сайте четыре страницы:</p>
                - пустую для success<br>
                - пустую для result<br>
                - одну с текстом о неудачной оплате (fail)<br>
                - одну с текстом об удачной оплате<br>
                Название и URL созданных страниц могут быть произвольными.<br>
                <p>2. Укажите здесь какие страницы и для чего вы создали. </p>
                <p>3. В настройках своего аккаунта платежной системы укажите URL страницы для fail, success и result</p>'),

                $opt->label(__('Page RESULT','rcl')),
                wp_dropdown_pages( array(
                        'selected'   => $rcl_options['page_result_pay'],
                        'name'       => 'page_result_pay',
                        'show_option_none' => __('Not selected','rcl'),
                        'echo'             => 0 )
                ),
                $opt->notice(__('For Interkassa: URL of interaction','rcl')),
                $opt->notice(__('For Yandex.Cash: checkURL and avisoURL','rcl')),

                $opt->label(__('Page SUCCESS','rcl')),
                wp_dropdown_pages( array(
                        'selected'   => $rcl_options['page_success_pay'],
                        'name'       => 'page_success_pay',
                        'show_option_none' => __('Not selected','rcl'),
                        'echo'             => 0 )
                ),
                $opt->notice(__('For Interkassa: successful payment URL','rcl')),

                $opt->label(__('Page FAIL','rcl')),
                wp_dropdown_pages( array(
                        'selected'   => $rcl_options['page_fail_pay'],
                        'name'       => 'page_fail_pay',
                        'show_option_none' => __('Not selected','rcl'),
                        'echo'             => 0 )
                ),

                $opt->label(__('The successful payment page','rcl')),
                wp_dropdown_pages( array(
                        'selected'   => $rcl_options['page_successfully_pay'],
                        'name'       => 'page_successfully_pay',
                        'show_option_none' => __('Not selected','rcl'),
                        'echo'             => 0 )
                )
            )
        );

        $content .= '</div>';

	return $content;
}

