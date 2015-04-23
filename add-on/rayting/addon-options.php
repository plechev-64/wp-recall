<?php

add_filter('admin_options_wprecall','get_admin_rayt_sistem_content');
function get_admin_rayt_sistem_content($content){

    $opt = new Rcl_Options(__FILE__);
        
    $content .= $opt->options(
        __('Настройки рейтинга'),array(
        $opt->option_block(
            array(
                $opt->title(__('Общие настройки')),
                
                $opt->label(__('Вид рейтинга для записей')),
                $opt->option('select',array(
                    'name'=>'type_rayt_post',
                    'options'=>array(__('Плюс/минус'),__('Мне нравится'))
                )),

                $opt->label(__('Вид рейтинга для комментариев')),
                $opt->option('select',array(
                    'name'=>'type_rayt_comment',
                    'options'=>array(__('Плюс/минус'),__('Мне нравится'))
                ))
            )
        ),
        $opt->option_block(
            array(
                $opt->title(__('Рейтинг публикаций')),
                
                $opt->option('select',array(
                    'name'=>'rayt_post_recall',
                    'options'=>array(__('Отключено'),__('Включено'))
                )),

                $opt->label(__('Баллы за рейтинг публикаций')),
                $opt->option('number',array('name'=>'count_rayt_post')),
                $opt->notice(__('установите сколько баллов к рейтингу будет начисляться за положительный голос или сколько баллов будет отниматься от рейтинга за отрицательный голос')),
                
                $opt->label(__('Влияние рейтинга постов на общий рейтинг')),
                $opt->option('select',array(
                    'name'=>'rayt_post',
                    'options'=>array(__('Нет'),__('Да'))
                ))
            )
        ),
        $opt->option_block(
            array(
                $opt->title(__('Рейтинг комментариев')),
                
                $opt->option('select',array(
                    'name'=>'rayt_comment_recall',
                    'options'=>array(__('Отключено'),__('Включено'))
                )),

                $opt->label(__('Баллы за рейтинг комментария')),
                $opt->option('number',array('name'=>'count_rayt_comment')),
                $opt->notice(__('установите сколько баллов к рейтингу будет начисляться за положительный голос или сколько баллов будет отниматься от рейтинга за отрицательный голос')),
                
                $opt->label(__('Влияние рейтинга комментариев на общий рейтинг')),
                $opt->option('select',array(
                    'name'=>'rayt_comment',
                    'options'=>array(__('Нет'),__('Да'))
                ))
            )
        ),
        $opt->option_block(
            array(
                $opt->label(__('Позволять обходить модерацию публикаций при достижении рейтинга')),
                $opt->option('number',array('name'=>'nomoder_rayt')),
                $opt->notice(__('укажите уровень рейтинга при котором пользователь получит возможность делать публикации без модерации'))
            )
        )
    ));

    return $content;
}
