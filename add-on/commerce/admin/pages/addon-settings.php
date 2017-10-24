<?php

function rcl_commerce_options(){
    global $rcl_options;
    
    $rcl_options = get_option('primary-rmag-options');

    require_once RCL_PATH.'classes/class-rcl-options.php';

    $opt = new Rcl_Options(__FILE__);
    
    $content = '<h2>'.__('Settings','wp-recall').' Recall Commerce</h2>';

    $content .= $opt->options(
        __('General settings','wp-recall'),array(
        $opt->options_box(
            __('General settings','wp-recall'),    
            array(
                array(
                    'type' => 'email',
                    'title'=>__('Email for notifications','wp-recall'),
                    'slug'=>'admin_email_magazin_recall',
                    'notice'=>__('If email is not specified, a notification will be sent to all users of the website with "Administrator" rights','wp-recall')
                )
            )
        ),
        $opt->options_box(
            __('Currency and rates','wp-recall'),    
            array(
                array(
                    'type' => 'select',
                    'title'=>__('Basis currency','wp-recall'),
                    'slug'=>'primary_cur',
                    'values'=> rcl_get_currency()
                )
            )
        ),
        $opt->options_box(
            __('Cart','wp-recall'),    
            array(
                array(
                    'type' => 'custom',
                    'title'=>__('Checkout page','wp-recall'),
                    'slug'=>'checkout_page',
                    'content' => wp_dropdown_pages( array(
                        'selected'   => $rcl_options['basket_page_rmag'],
                        'name'       => 'global[basket_page_rmag]',
                        'show_option_none' => __('Not selected','wp-recall'),
                        'echo'       => 0
                    )),
                    'notice'=>__('Specify the page with the shortcode [basket]','wp-recall')
                )
            )
        ),
        $opt->options_box(
            __('Check-out','wp-recall'),    
            array(
                array(
                    'type' => 'select',
                    'title'=>__('Register at check-out','wp-recall'),
                    'slug'=>'buyer_register',
                    'values' => array(
                        __('Disabled','wp-recall'),
                        __('Enabled','wp-recall')
                    ),
                    'notice'=>__('If enabled, the user will be automatically registered on the site after successfull check-out','wp-recall')
                )
            )
        ),
        $opt->options_box(
            __('The output of the button "Add to cart"','wp-recall'),    
            array(
                array(
                    'type' => 'checkbox',
                    'title'=>__('On the product page','wp-recall'),
                    'slug'=>'cart_button_single_page',
                    'values'=> array(
                        'top' => __('On the description','wp-recall'),
                        'bottom' => __('Under the description','wp-recall')
                    ),
                    'default' => array('top','bottom')
                ),
                array(
                    'type' => 'select',
                    'title'=>__('On the archive page','wp-recall'),
                    'slug'=>'cart_button_archive_page',
                    'values'=> array(__('Disabled','wp-recall'),__('Enabled','wp-recall')),
                    'default' => 1
                )
            )
        ),
        $opt->options_box(
            __('Similar or recommended goods','wp-recall'),    
            array(
                array(
                    'type' => 'select',
                    'title'=>__('Output order','wp-recall'),
                    'slug'=>'sistem_related_products',
                    'values'=> array(__('Disabled','wp-recall'),__('Enabled','wp-recall')),
                    'childrens' => array(
                        1 => array(
                            array(
                                'type' => 'text',
                                'title'=>__('Block title for featured products','wp-recall'),
                                'slug'=>'title_related_products_recal'
                            ),
                            array(
                                'type' => 'number',
                                'title'=>__('Number of featured products','wp-recall'),
                                'slug'=>'size_related_products'
                            )
                        )
                    )
                )
            )
        ))
    );
    
    return $content;
    
}
