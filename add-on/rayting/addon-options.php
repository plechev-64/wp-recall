<?php

add_filter('admin_options_wprecall','rcl_admin_page_rating');
function rcl_admin_page_rating($content){

    $opt = new Rcl_Options(__FILE__);

    $content .= $opt->options(
        __('Rating settings','rcl'),array(
        $opt->option_block(
            array(
                $opt->title(__('General settings','rcl')),

                $opt->label(__('Type of rating for records','rcl')),
                $opt->option('select',array(
                    'name'=>'type_rayt_post',
                    'options'=>array(__('Plus/minus','rcl'),__('I like','rcl'))
                )),

                $opt->label(__('Type of rating comments','rcl')),
                $opt->option('select',array(
                    'name'=>'type_rayt_comment',
                    'options'=>array(__('Plus/minus','rcl'),__('I like','rcl'))
                )),

                $opt->label(__('Output rating to archive page','rcl')),
                $opt->option('select',array(
                    'name'=>'output_rating_archive',
                    'options'=>array(__('No','rcl'),__('Yes','rcl'))
                ))
            )
        ),
        $opt->option_block(
            array(
                $opt->title(__('The rating publications','rcl')),

                $opt->option('select',array(
                    'name'=>'rayt_post_recall',
                    'options'=>array(__('Disabled','rcl'),__('Included','rcl'))
                )),

                $opt->label(__('Points for ranking publications','rcl')),
                $opt->option('number',array('name'=>'count_rayt_post')),
                $opt->notice(__('set how many points the ranking will be awarded for a positive vote or how many points will be subtracted from the rating for a negative vote','rcl')),

                $opt->label(__('The influence of rating posts on the overall rating','rcl')),
                $opt->option('select',array(
                    'name'=>'rayt_post',
                    'options'=>array(__('No','rcl'),__('Yes','rcl'))
                ))
            )
        ),
        $opt->option_block(
            array(
                $opt->title(__('Rating review','rcl')),

                $opt->option('select',array(
                    'name'=>'rayt_comment_recall',
                    'options'=>array(__('Disabled','rcl'),__('Included','rcl'))
                )),

                $opt->label(__('Points for comment rating','rcl')),
                $opt->option('number',array('name'=>'count_rayt_comment')),
                $opt->notice(__('set how many points the ranking will be awarded for a positive vote or how many points will be subtracted from the rating for a negative vote','rcl')),

                $opt->label(__('The influence of the rating review on the overall rating','rcl')),
                $opt->option('select',array(
                    'name'=>'rayt_comment',
                    'options'=>array(__('No','rcl'),__('Yes','rcl'))
                ))
            )
        ),
        $opt->option_block(
            array(
                $opt->label(__('Allow to bypass the moderation of publications at achievement rating','rcl')),
                $opt->option('number',array('name'=>'nomoder_rayt')),
                $opt->notice(__('specify the rating level at which the user will get the ability to post without moderation','rcl'))
            )
        )
    ));

    return $content;
}
