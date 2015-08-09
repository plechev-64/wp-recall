<?php

add_filter('admin_options_wprecall','rcl_admin_page_rating');
function rcl_admin_page_rating($content){
    global $rcl_rating_types;

    $opt = new Rcl_Options(__FILE__);

    $options = '';

    foreach($rcl_rating_types as $type=>$data){

            $more = false;

            $notice_temp = __('select a template output stories where','rcl').' <br>'
                . __('%USER% - name of the voted','rcl').', <br>'
                . __('%VALUE% - rated value','rcl').', <br>'
                . __('%DATE% - date of changing the rating','rcl').', <br>';
                if($type=='comment') $notice_temp .= __('%COMMENT% - link to comment','rcl').', <br>';
                if(isset($data['post_type'])) $notice_temp .= __('%POST% - link to publication','rcl');

		if(isset($data['style'])){
			$more .= $opt->label(__('Type of rating for','rcl').' '.$data['type_name']);
            $more .= $opt->option('select',array(
                    'name'=>'rating_type_'.$type,
                    'options'=>array(__('Plus/minus','rcl'),__('I like','rcl'))
                ));
		}

		if(isset($data['data_type'])){
			$more .= $opt->label(__('Overall rating '.$data['type_name'],'rcl'));
            $more .= $opt->option('select',array(
                    'name'=>'rating_overall_'.$type,
                    'options'=>array(__('Sum votes values','rcl'),__('Number of positive and negative votes','rcl'))
                ));
		}

		if(isset($data['limit_votes'])){
                        $more .= $opt->label(__('Limit of one vote per user','rcl'));
			$more .= $opt->label(__('Positive votes','rcl'));
            $more .= __('Number','rcl').': '.$opt->option('number',array('name'=>'rating_plus_limit_'.$type));
			$more .= ' '.__('Time','rcl').': '.$opt->option('number',array('name'=>'rating_plus_time_'.$type));
			$more .= $opt->label(__('Negative votes','rcl'));
            $more .= __('Number','rcl').': '.$opt->option('number',array('name'=>'rating_minus_limit_'.$type));
			$more .= ' '.__('Time','rcl').': '.$opt->option('number',array('name'=>'rating_minus_time_'.$type));
                        $more .= $opt->notice(__('Note: Time in seconds','rcl'));
		}

        $options .= $opt->option_block(
            array(
                $opt->title(__('The rating','rcl').' '.$data['type_name']),

                $opt->option('select',array(
                    'name'=>'rating_'.$type,
                    'options'=>array(__('Disabled','rcl'),__('Included','rcl'))
                )),

                $more,

                $opt->label(__('Points for ranking','rcl').' '.$data['type_name']),
                $opt->option('text',array('name'=>'rating_point_'.$type)),
                $opt->notice(__('set how many points the ranking will be awarded for a positive vote or how many points will be subtracted from the rating for a negative vote','rcl')),

                $opt->label(sprintf(__('The influence of rating %s on the overall rating','rcl'),$data['type_name'])),
                $opt->option('select',array(
                    'name'=>'rating_user_'.$type,
					'parent'=>true,
                    'options'=>array(__('No','rcl'),__('Yes','rcl'))
                )),
                $opt->child(
                    array(
                        'name'=>'rating_user_'.$type,
                        'value'=>1
                    ),
                    array(
                    $opt->label(__('Template output stories in the overall ranking','rcl')),
                    $opt->option('text',array('name'=>'rating_temp_'.$type,'default'=>'%USER% '.__('voted','rcl').': %VALUE%')),
                    $opt->notice($notice_temp)
                ))
            )
        );
    }

    $content .= $opt->options(
        __('Rating settings','rcl'),array(

        $options,

        $opt->option_block(
            array(
                $opt->label(__('Allow to bypass the moderation of publications at achievement rating','rcl')),
                $opt->option('number',array('name'=>'rating_no_moderation')),
                $opt->notice(__('specify the rating level at which the user will get the ability to post without moderation','rcl'))
            )
        ),

		$opt->option_block(
            array(
                $opt->label(__('View results','rcl')),
                $opt->option('select',array(
					'name'=>'rating_results_can',
					'default'=>0,
					'options'=>array(
						0=>__('All users','rcl'),
						1=>__('Participants and older','rcl'),
						2=>__('Authors and older','rcl'),
						7=>__('Editors and older','rcl'),
						10=>__('only Administrators','rcl')
					)
				)),
                $opt->notice(__('specify the user group which is allowed to view votes','rcl'))
            )
        ),

		$opt->option_block(
            array(
                $opt->label(__('Deleting your voice','rcl')),
                $opt->option('select',array(
					'name'=>'rating_delete_voice',
					'options'=>array(__('No','rcl'),__('Yes','rcl'))
				))
            )
        )
    ));

    return $content;
}
