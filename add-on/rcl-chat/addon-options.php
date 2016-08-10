<?php
add_filter('admin_options_wprecall','rcl_chat_options');
function rcl_chat_options($content){

    $opt = new Rcl_Options(__FILE__);

    $content .= $opt->options(
        __('IM settings','wp-recall'),
        array(
            $opt->option_block(
                array(

                    $opt->title(__('General settings','wp-recall')),

                    $opt->option('number',array(
                        'label'=>__('The delay between requests','wp-recall'),
                        'name'=>'delay',
                        'group'=>'chat',
                        'default'=>15,
                        'notice'=>__('In seconds. It is recommended to put at least 10 seconds','wp-recall'),
                    )),

                    $opt->option('number',array(
                        'label'=>__('User Downtime','wp-recall'),
                        'name'=>'inactivity',
                        'group'=>'chat',
                        'default'=>10,
                        'notice'=>__('In minuts. The time of user inactivity after which he ceases to receive new messages in chat','wp-recall'),
                    )),

                    $opt->option('number',array(
                        'label'=>__('The number of characters in the message','wp-recall'),
                        'name'=>'words',
                        'group'=>'chat',
                        'default'=>300
                    )),

                    $opt->option('number',array(
                        'label'=>__('Posts per page','wp-recall'),
                        'name'=>'in_page',
                        'group'=>'chat',
                        'default'=>50
                    )),
                    
                    $opt->option('select',array(
                        'label'=>__('Using OEMBED','wp-recall'),
                        'name'=>'oembed',
                        'group'=>'chat',
                        'parent'=>true,
                        'options'=>array(
                            __('No','wp-recall'),
                            __('Yes','wp-recall')),
                        'notice'=>__('Option is responsible for the incorporation by reference of media content, such as from Youtube or Twitter','wp-recall'),
                    )),

                    $opt->option('select',array(
                        'label'=>__('Attaching files','wp-recall'),
                        'name'=>'file_upload',
                        'group'=>'chat',
                        'parent'=>true,
                        'options'=>array(
                            __('No','wp-recall'),
                            __('Yes','wp-recall'))
                    )),
                    $opt->child(
                        array(
                            'name'=>'file_upload',
                            'value'=>1
                        ),
                        array(
                            $opt->option('text',array(
                                'label'=>__('Allowed file types','wp-recall'),
                                'name'=>'file_types',
                                'group'=>'chat',
                                'default'=>'jpeg, jpg, png, zip, mp3',
                                'notice'=>__('By default: jpeg, jpg, png, zip, mp3','wp-recall')
                            )),
                            $opt->option('number',array(
                                'label'=>__('The maximum file size','wp-recall'),
                                'name'=>'file_size',
                                'group'=>'chat',
                                'default'=>2
                            ))
                        )
                    )
                )
            ),
            $opt->option_block(
                array(

                    $opt->title(__('Personal chat','wp-recall')),

                    $opt->option('number',array(
                        'label'=>__('The number of messages in the conversation','wp-recall'),
                        'name'=>'messages_amount',
                        'group'=>'chat',
                        'default'=>100,
                        'notice'=>__('The maximum number of messages in the conversation between two users. Default: 100','wp-recall')
                    )),
                    $opt->option('select',array(
                        'label'=>__('The contacts bar','wp-recall'),
                        'name'=>'contact_panel',
                        'group'=>'chat',
                        'parent'=>true,
                        'options'=>array(
                            __('No','wp-recall'),
                            __('Yes','wp-recall'))
                    )),
                    $opt->child(
                        array(
                            'name'=>'contact_panel',
                            'value'=>1
                        ),
                        array(
                            $opt->option('select',array(
                                'label'=>__('The place of a conclusion','wp-recall'),
                                'name'=>'place_contact_panel',
                                'group'=>'chat',
                                'parent'=>true,
                                'options'=>array(
                                    __('Right','wp-recall'),
                                    __('Left','wp-recall'))
                            ))
                        )
                    )
                )
            )
        )
    );
    return $content;
}

