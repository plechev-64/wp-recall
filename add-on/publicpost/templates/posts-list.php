<?php global $posts,$ratings; ?>

<table class="publics-table-rcl">

	<tr>
		<td><?php _e('Date','rcl'); ?></td>
		<td><?php _e('Title','rcl'); ?></td>
		<td><?php _e('Status','rcl'); ?></td>						
	</tr>
			
	<?php foreach($posts as $postdata){ ?>
		
		<?php foreach($postdata as $post){ setup_postdata($post); ?>
		
			<?php if($post->post_status=='pending') $status = '<span class="pending">'.__('on approval','rcl').'</span>';
			elseif($post->post_status=='trash') $status = '<span class="pending">'.__('deleted','rcl').'</span>';
			else $status = '<span class="publish">'.__('publish','rcl').'</span>'; ?>
			
			<tr>
			
				<td width="50"><?php echo mysql2date('d.m.y', $post->post_date); ?></td>
				
				<td>

				<?php echo ($post->post_status=='trash')? $post->post_title: '<a target="_blank" href="'.$post->guid.'">'.$post->post_title.'</a>'; ?>

				<?php if(function_exists('rcl_format_rating')) {
					$rtng = (isset($ratings[$post->ID]))? $ratings[$post->ID]: 0;
					echo rcl_rating_block(array('value'=>$rtng));
				} ?>
				<?php echo apply_filters('content_postslist',$content); ?>

				</td>
				<td><?php echo $status ?></td>
			
			</tr>
		<?php } ?>
		
	<?php } ?>
		
</table>
               