<?php

add_filter('admin_options_wprecall','rcl_admin_page_rating');
function rcl_admin_page_rating($content){
    global $rcl_rating_types;
    
    $post_types = get_post_types(array(
            'public'   => true,
            '_builtin' => false
        ), 'objects');

    $types = array('post');

    foreach ($post_types  as $post_type ) {
        $types[] = $post_type->name;
    }

    $opt = new Rcl_Options(__FILE__);

    $optionsContent = '';

    foreach($rcl_rating_types as $type=>$data){

        $more = false;
        $points = (isset($data['points']))? $data['points']: true;

        $notice_temp = __('select a template for history output where','wp-recall').' <br>'
        . __('%USER% - name of the user who voted','wp-recall').', <br>'
        . __('%VALUE% - rated value','wp-recall').', <br>'
        . __('%DATE% - date of changing the rating','wp-recall').', <br>';
        
        if($type=='comment') 
            $notice_temp .= __('%COMMENT% - link to comment','wp-recall').', <br>';
        
        if(isset($data['post_type'])) 
            $notice_temp .= __('%POST% - link to publication','wp-recall');
        
        $options = array();

        if(isset($data['style'])){
            
            $options[] = array(
                'child' => true,
                'type' => 'select',
                'slug' => 'rating_type_'.$type,
                'title' => __('Type of rating for','wp-recall').' '.$data['type_name'],
                'values' => array(
                    __('Plus/minus','wp-recall'),
                    __('I like','wp-recall'),
                    __('Stars','wp-recall')
                )
            );
            
            $options[] = array(
                'parent' => array('rating_type_'.$type => 2),
                'type' => 'runner',
                'slug' => 'rating_item_amount_'.$type,
                'title' => __('Количество звезд','wp-recall'),
                'value_min' => 1,
                'value_max' => 20,
                'default' => 5
            );
            
            if(in_array($type,$types)){
            
                $options[] = array(
                    'parent' => array('rating_type_'.$type => 2),
                    'type' => 'select',
                    'slug' => 'rating_shema_'.$type,
                    'title' => __('Разметка рейтинга','wp-recall'),
                    'values' => array(
                        __('Отключено','wp-recall'),
                        __('Включено','wp-recall')
                    ),
                    'notice' => __('Если включено, то на одиночных страницах вместе с рейтингом выводится разметка по стандарту <a href="http://schema.org" target="_blank">http://schema.org</a>','wp-recall')
                );
            
            }

        }

        if(isset($data['data_type'])){
            
            $options[] = array(
                'parent' => array('rating_type_'.$type => array(0,1)),
                'type' => 'select',
                'slug' => 'rating_overall_'.$type,
                'title' => __('Overall rating','wp-recall').' '.$data['type_name'],
                'values' => array(__('Sum of votes','wp-recall'),__('Number of positive and negative votes','wp-recall'))
            );

        }
        
        if($points){
            
            $options[] = array(
                'type' => 'text',
                'slug' => 'rating_point_'.$type,
                'title' => __('Points for ranking','wp-recall').' '.$data['type_name'],
                'notice' => __('set how many points will be awarded for a positive or negative vote for the publication','wp-recall')
            );
            
        }

        /*if(isset($data['limit_votes'])){
            $more .= $opt->label(__('Limit of one vote per user','wp-recall'));
            $more .= $opt->label(__('Positive votes','wp-recall'));
            $more .= __('Number','wp-recall').': '.$opt->option('number',array('name'=>'rating_plus_limit_'.$type));
            $more .= ' '.__('Time','wp-recall').': '.$opt->option('number',array('name'=>'rating_plus_time_'.$type));
            $more .= $opt->label(__('Negative votes','wp-recall'));
            $more .= __('Number','wp-recall').': '.$opt->option('number',array('name'=>'rating_minus_limit_'.$type));
            $more .= ' '.__('Time','wp-recall').': '.$opt->option('number',array('name'=>'rating_minus_time_'.$type));
            $more .= $opt->notice(__('Note: Time in seconds','wp-recall'));
        }*/
        
        $options[] = array(
            'type' => 'select',
            'slug' => 'rating_user_'.$type,
            'title' => sprintf(__('The influence of rating %s on the overall rating','wp-recall'),$data['type_name']),
            'values' => array(__('No','wp-recall'),__('Yes','wp-recall')),
            'childrens' => array(
                1 => array(
                    array(
                        'type' => 'text',
                        'slug' => 'rating_temp_'.$type,
                        'title' => __('Template of history output in the overall ranking','wp-recall'),
                        'default' => '%DATE% %USER% '.__('has voted','wp-recall').': %VALUE%',
                        'notice' => $notice_temp
                    )
                )
            )
        );

        $optionsContent .= $opt->options_box(
            __('Rating','wp-recall').' '.$data['type_name'],
            array(
                array(
                    'type' => 'select',
                    'slug' => 'rating_'.$type,
                    'values' => array(__('Disabled','wp-recall'),__('Enabled','wp-recall')),
                    'childrens' => array(
                        1 => $options
                    )
                )
            )
            
        );

    }

    $content .= $opt->options(
            
        __('Rating settings','wp-recall'),array(

        $optionsContent,
            
        $opt->extend(array(
            
            $opt->options_box(
                __('Extends options','wp-recall'),
                array(
                    array(
                        'type' => 'number',
                        'slug' => 'rating_no_moderation',
                        'title' => __('Influence of rationg on publication moderation','wp-recall'),
                        'notice' => __('specify the rating level at which the user will get the ability to post without moderation','wp-recall')
                    ),
                    array(
                        'type' => 'select',
                        'slug' => 'rating_results_can',
                        'title' => __('View results','wp-recall'),
                        'values' => array(
                            0   => __('All users','wp-recall'),
                            1   => __('Participants and higher','wp-recall'),
                            2   => __('Authors and higher','wp-recall'),
                            7   => __('Editors and higher','wp-recall'),
                            10  => __('only Administrators','wp-recall')
                        ),
                        'notice' => __('specify the user group which is allowed to view votes','wp-recall')
                    ),
                    array(
                        'type' => 'select',
                        'slug' => 'rating_delete_voice',
                        'title' => __('Delete your vote','wp-recall'),
                        'values' => array(__('No','wp-recall'),__('Yes','wp-recall'))
                    ),
                    array(
                        'type' => 'select',
                        'slug' => 'rating_custom',
                        'title' => __('Вкладка "Разное"','wp-recall'),
                        'values' => array(
                            __('Отключено','wp-recall'),
                            __('Включено','wp-recall')
                        ),
                        'notice' => __('Если включено, то в истории рейтинга будет формироваться дополнительная вкладка "Разное", где будут выводится все изменения через незарегистрированные типы рейтинга','wp-recall')
                    )
                )
            )
        ))
    ));

    return $content;
}
