<?php global $post,$posts,$ratings; ?>

<table class="publics-table-rcl rcl-form">

	<tr>
		<td><?php _e('Date','wp-recall'); ?></td>
		<td><?php _e('Title','wp-recall'); ?></td>
		<td><?php _e('Status','wp-recall'); ?></td>
	</tr>

	<?php foreach($posts as $postdata){ ?>

		<?php foreach($postdata as $post){ setup_postdata($post); ?>

			<?php if($post->post_status=='pending') $status = '<span class="status-pending">'.__('to be approved','wp-recall').'</span>';
			elseif($post->post_status=='trash') $status = '<span class="status-pending">'.__('deleted','wp-recall').'</span>';
                        elseif($post->post_status=='draft') $status = '<span class="status-draft">'.__('draft','wp-recall').'</span>';
			else $status = '<span class="status-publish">'.__('published','wp-recall').'</span>'; ?>

			<tr>

				<td width="50"><?php echo mysql2date('d.m.y', $post->post_date); ?></td>

				<td>

				<?php echo ($post->post_status=='trash')? $post->post_title: '<a target="_blank" href="'.$post->guid.'">'.$post->post_title.'</a>'; ?>

				<?php if(function_exists('rcl_format_rating')) {
					$rtng = (isset($ratings[$post->ID]))? $ratings[$post->ID]: 0;
					echo rcl_rating_block(array('value'=>$rtng));
				} ?>
				<?php $content = ''; echo apply_filters('content_postslist',$content); ?>

				</td>
				<td><?php echo $status ?></td>

			</tr>
		<?php } ?>

	<?php } ?>

</table>
