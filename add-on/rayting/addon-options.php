<?php

add_filter('admin_options_wprecall','get_admin_rayt_sistem_content');
function get_admin_rayt_sistem_content($content){

    $opt = new Rcl_Options(__FILE__);
        
    $content .= $opt->options(
        'Настройки рейтинга',array(
        $opt->option_block(
            array(
                $opt->title('Общие настройки'),
                
                $opt->label('Вид рейтинга для записей'),
                $opt->option('select',array(
                    'name'=>'type_rayt_post',
                    'options'=>array('Плюс/минус','Мне нравится')
                )),

                $opt->label('Вид рейтинга для комментариев'),
                $opt->option('select',array(
                    'name'=>'type_rayt_comment',
                    'options'=>array('Плюс/минус','Мне нравится')
                ))
            )
        ),
        $opt->option_block(
            array(
                $opt->title('Рейтинг публикаций'),
                
                $opt->option('select',array(
                    'name'=>'rayt_post_recall',
                    'options'=>array('Отключено','Включено')
                )),

                $opt->label('Баллы за рейтинг публикаций'),
                $opt->option('text',array('name'=>'count_rayt_post')),
                $opt->notice('установите сколько баллов к рейтингу будет начисляться за положительный голос или сколько баллов будет отниматься от рейтинга за отрицательный голос'),
                
                $opt->label('Влияние рейтинга постов на общий рейтинг'),
                $opt->option('select',array(
                    'name'=>'rayt_post',
                    'options'=>array('Нет','Да')
                ))
            )
        ),
        $opt->option_block(
            array(
                $opt->title('Рейтинг комментариев'),
                
                $opt->option('select',array(
                    'name'=>'rayt_comment_recall',
                    'options'=>array('Отключено','Включено')
                )),

                $opt->label('Баллы за рейтинг комментария'),
                $opt->option('text',array('name'=>'count_rayt_comment')),
                $opt->notice('установите сколько баллов к рейтингу будет начисляться за положительный голос или сколько баллов будет отниматься от рейтинга за отрицательный голос'),
                
                $opt->label('Влияние рейтинга комментариев на общий рейтинг'),
                $opt->option('select',array(
                    'name'=>'rayt_comment',
                    'options'=>array('Нет','Да')
                ))
            )
        ),
        $opt->option_block(
            array(
                $opt->label('Позволять обходить модерацию публикаций при достижении рейтинга'),
                $opt->option('text',array('name'=>'nomoder_rayt')),
                $opt->notice('укажите уровень рейтинга при котором пользователь получит возможность делать публикации без модерации')
            )
        )
    ));

    return $content;
}
