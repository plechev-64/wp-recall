<?php

add_filter('admin_options_wprecall','rcl_admin_page_rating');
function rcl_admin_page_rating($content){
    global $rcl_rating_types;

    $opt = new Rcl_Options(__FILE__);

    $options = '';

    foreach($rcl_rating_types as $type=>$data){

            $more = false;

            $notice_temp = __('select a template output stories where','wp-recall').' <br>'
                . __('%USER% - name of the voted','wp-recall').', <br>'
                . __('%VALUE% - rated value','wp-recall').', <br>'
                . __('%DATE% - date of changing the rating','wp-recall').', <br>';
                if($type=='comment') $notice_temp .= __('%COMMENT% - link to comment','wp-recall').', <br>';
                if(isset($data['post_type'])) $notice_temp .= __('%POST% - link to publication','wp-recall');

		if(isset($data['style'])){
			$more .= $opt->label(__('Type of rating for','wp-recall').' '.$data['type_name']);
            $more .= $opt->option('select',array(
                    'name'=>'rating_type_'.$type,
                    'options'=>array(__('Plus/minus','wp-recall'),__('I like','wp-recall'))
                ));
		}

		if(isset($data['data_type'])){
			$more .= $opt->label(__('Overall rating '.$data['type_name'],'wp-recall'));
            $more .= $opt->option('select',array(
                    'name'=>'rating_overall_'.$type,
                    'options'=>array(__('Sum votes values','wp-recall'),__('Number of positive and negative votes','wp-recall'))
                ));
		}

		if(isset($data['limit_votes'])){
                        $more .= $opt->label(__('Limit of one vote per user','wp-recall'));
			$more .= $opt->label(__('Positive votes','wp-recall'));
            $more .= __('Number','wp-recall').': '.$opt->option('number',array('name'=>'rating_plus_limit_'.$type));
			$more .= ' '.__('Time','wp-recall').': '.$opt->option('number',array('name'=>'rating_plus_time_'.$type));
			$more .= $opt->label(__('Negative votes','wp-recall'));
            $more .= __('Number','wp-recall').': '.$opt->option('number',array('name'=>'rating_minus_limit_'.$type));
			$more .= ' '.__('Time','wp-recall').': '.$opt->option('number',array('name'=>'rating_minus_time_'.$type));
                        $more .= $opt->notice(__('Note: Time in seconds','wp-recall'));
		}

        $options .= $opt->option_block(
            array(
                $opt->title(__('The rating','wp-recall').' '.$data['type_name']),

                $opt->option('select',array(
                    'name'=>'rating_'.$type,
                    'options'=>array(__('Disabled','wp-recall'),__('Included','wp-recall'))
                )),

                $more,

                $opt->label(__('Points for ranking','wp-recall').' '.$data['type_name']),
                $opt->option('text',array('name'=>'rating_point_'.$type)),
                $opt->notice(__('set how many points the ranking will be awarded for a positive vote or how many points will be subtracted from the rating for a negative vote','wp-recall')),

                $opt->label(sprintf(__('The influence of rating %s on the overall rating','wp-recall'),$data['type_name'])),
                $opt->option('select',array(
                    'name'=>'rating_user_'.$type,
					'parent'=>true,
                    'options'=>array(__('No','wp-recall'),__('Yes','wp-recall'))
                )),
                $opt->child(
                    array(
                        'name'=>'rating_user_'.$type,
                        'value'=>1
                    ),
                    array(
                    $opt->label(__('Template output stories in the overall ranking','wp-recall')),
                    $opt->option('text',array('name'=>'rating_temp_'.$type,'default'=>'%USER% '.__('voted','wp-recall').': %VALUE%')),
                    $opt->notice($notice_temp)
                ))
            )
        );
    }

    $content .= $opt->options(
        __('Rating settings','wp-recall'),array(

        $options,

        $opt->option_block(
            array(
                $opt->label(__('Allow to bypass the moderation of publications at achievement rating','wp-recall')),
                $opt->option('number',array('name'=>'rating_no_moderation')),
                $opt->notice(__('specify the rating level at which the user will get the ability to post without moderation','wp-recall'))
            )
        ),

		$opt->option_block(
            array(
                $opt->label(__('View results','wp-recall')),
                $opt->option('select',array(
					'name'=>'rating_results_can',
					'default'=>0,
					'options'=>array(
						0=>__('All users','wp-recall'),
						1=>__('Participants and older','wp-recall'),
						2=>__('Authors and older','wp-recall'),
						7=>__('Editors and older','wp-recall'),
						10=>__('only Administrators','wp-recall')
					)
				)),
                $opt->notice(__('specify the user group which is allowed to view votes','wp-recall'))
            )
        ),

		$opt->option_block(
            array(
                $opt->label(__('Deleting your voice','wp-recall')),
                $opt->option('select',array(
					'name'=>'rating_delete_voice',
					'options'=>array(__('No','wp-recall'),__('Yes','wp-recall'))
				))
            )
        )
    ));

    return $content;
}
