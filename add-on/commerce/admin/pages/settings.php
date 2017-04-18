<?php

function rcl_commerce_options(){
    global $rcl_options;
    
    $rcl_options = get_option('primary-rmag-options');

    require_once RCL_PATH.'classes/class-rcl-options.php';

    $opt = new Rcl_Options(__FILE__);

    $args = array(
        'selected'   => $rcl_options['basket_page_rmag'],
        'name'       => 'global[basket_page_rmag]',
        'show_option_none' => __('Not selected','wp-recall'),
        'echo'       => 0
    );
    
    $content = '<h2>'.__('Settings','wp-recall').' Recall Commerce</h2>';

    $content .= $opt->options(
        __('General settings','wp-recall'),array(
        $opt->option_block(
            array(
                $opt->title(__('General settings','wp-recall')),

                $opt->label(__('Email for notifications','wp-recall')),
                $opt->option('email',array('name'=>'admin_email_magazin_recall')),
                $opt->notice(__('If email is not specified, a notification will be sent to all users of the website with "Administrator" rights','wp-recall')),

            )
        ),
        $opt->option_block(
            array(
                $opt->title(__('Check-out','wp-recall')),

                $opt->label(__('Register at check-out','wp-recall')),
                $opt->option('select',array(
                    'name'=>'buyer_register',
                    'default'=>1,
                    'options'=>array(__('Disabled','wp-recall'),__('Enabled','wp-recall'))
                )),
                $opt->notice(__('If enabled, the user will be automatically registered on the site after successfull check-out','wp-recall'))
            )
        ),
        $opt->option_block(
            array(
                $opt->title(__('Cart','wp-recall')),
                $opt->label(__('Checkout page','wp-recall')),
                wp_dropdown_pages( $args ),
                $opt->notice(__('Specify the page with the shortcode [basket]','wp-recall')),
            )
        ),
         $opt->option_block(
            array(
                $opt->title(__('Similar or recommended goods','wp-recall')),

                $opt->label(__('Output order','wp-recall')),
                $opt->option('select',array(
                    'name'=>'sistem_related_products',
                    'options'=>array(__('Disabled','wp-recall'),__('Enabled','wp-recall'))
                )),

                $opt->label(__('Block title for featured products','wp-recall')),
                $opt->option('text',array('name'=>'title_related_products_recall')),

                $opt->label(__('Number of featured products','wp-recall')),
                $opt->option('number',array('name'=>'size_related_products'))
            )
        ),
         $opt->option_block(
            array(
                $opt->title(__('Currency and rates','wp-recall')),
                    $opt->label(__('Basis currency','wp-recall')),
                    $opt->option('select',array(
                    'name'=>'primary_cur',
                    'options'=>rcl_get_currency()
                )),
            )
        ))
    );
    
    return $content;
    
}
