<?php
add_filter('admin_options_wprecall','rcl_chat_options');
function rcl_chat_options($content){

    $opt = new Rcl_Options(__FILE__);

    $content .= $opt->options(
        __('IM settings','wp-recall'),
        array(
            $opt->options_box(
                __('General settings','wp-recall'),
                array(
                    array(
                        'type' => 'number',
                        'title'=>__('Delay between requests','wp-recall'),
                        'slug'=>'delay',
                        'group'=>'chat',
                        'default'=>15,
                        'notice'=>__('In seconds. It is recommended to choose at least 10 seconds','wp-recall'),
                    ),
                    array(
                        'type' => 'number',
                        'title'=>__('User Downtime','wp-recall'),
                        'slug'=>'inactivity',
                        'group'=>'chat',
                        'default'=>10,
                        'notice'=>__('"In minutes. The time of user inactivity after which he ceases to receive new messages in chat','wp-recall')
                    ),
                    array(
                        'type' => 'number',
                        'title'=>__('The number of characters in the message','wp-recall'),
                        'slug'=>'words',
                        'group'=>'chat',
                        'default'=>300
                    ),
                    array(
                        'type' => 'number',
                        'title'=>__('Posts per page','wp-recall'),
                        'slug'=>'in_page',
                        'group'=>'chat',
                        'default'=>50
                    ),
                    array(
                        'type' => 'select',
                        'title'=>__('Using OEMBED','wp-recall'),
                        'slug'=>'oembed',
                        'group'=>'chat',
                        'values'=>array(
                            __('No','wp-recall'),
                            __('Yes','wp-recall')),
                        'notice'=>__('Option is responsible for the incorporation of media content, such as from Youtube or Twitter from the link','wp-recall'),
                    ),
                    array(
                        'type' => 'select',
                        'title'=>__('Attaching files','wp-recall'),
                        'slug'=>'file_upload',
                        'group'=>'chat',
                        'child'=>true,
                        'values'=>array(
                            __('No','wp-recall'),
                            __('Yes','wp-recall')
                        ) 
                    ),
                    array(
                        'parent' => array( 'file_upload' => 1 ),
                        'type' => 'text',
                        'title'=>__('Allowed file types','wp-recall'),
                        'slug'=>'file_types',
                        'group'=>'chat',
                        'default'=>'jpeg, jpg, png, zip, mp3',
                        'notice'=>__('By default: jpeg, jpg, png, zip, mp3','wp-recall')
                    ),
                    array(
                        'parent' => array( 'file_upload' => 1 ),
                        'type' => 'runner',
                        'value_min' => 1,
                        'value_max' => 10,
                        'value_step' => 1,
                        'default' => 2,
                        'title'=>__('Maximum file size, MB','wp-recall'),
                        'slug'=>'file_size',
                        'group'=>'chat',
                        'default'=>2
                    )
                )
            ),
            $opt->options_box(
                __('Personal chat','wp-recall'),
                array(
                    array(
                        'type' => 'number',
                        'title'=>__('Number of messages in the conversation','wp-recall'),
                        'slug'=>'messages_amount',
                        'group'=>'chat',
                        'default'=>100,
                        'notice'=>__('The maximum number of messages in the conversation between two users. Default: 100','wp-recall')
                    ),
                    array(
                        'type' => 'select',
                        'slug'=>'messages_mail',
                        'title'=>__('Mail alert','wp-recall'),
                        'values'=>array(
                            __('Without the text of the message','wp-recall'),
                            __('Full text of the message','wp-recall'))
                    ),
                    array(
                        'type' => 'select',
                        'title'=>__('Contacts bar','wp-recall'),
                        'slug'=>'contact_panel',
                        'group'=>'chat',
                        'child'=>true,
                        'values'=>array(
                            __('No','wp-recall'),
                            __('Yes','wp-recall'))
                    ),
                    array(
                        'type' => 'select',
                        'parent' => array('contact_panel'=>1),
                        'title'=>__('Output location','wp-recall'),
                        'slug'=>'place_contact_panel',
                        'group'=>'chat',
                        'values'=>array(
                            __('Right','wp-recall'),
                            __('Left','wp-recall'))
                    )
                )
            )
        )
    );
    return $content;
}

