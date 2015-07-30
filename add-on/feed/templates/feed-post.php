<?php global $post,$active_addons; ?>

<div id="feed-post-<?php echo $post->ID; ?>" class="feed-post">

	<div class="feed-author-avatar">
		<a href="<?php echo get_author_posts_url($post->post_author); ?>"><?php echo get_avatar($post->post_author,50); ?></a>
	</div>
	
	<h3 class="feed-title"><a href="<?php echo get_permalink($post->ID); ?>"><?php echo $post->post_title; ?></a></h3><small><?php echo date('d.m.Y H:i', strtotime($post->post_date)); ?></small>

	<?php if( has_post_thumbnail($post->ID) ) {
			echo get_the_post_thumbnail( $post->ID, 'medium', 'class=aligncenter' );
	} ?>

	<?php if($active_addons['gallery-recall']&&$post->post_type=='attachment'){
		$src = wp_get_attachment_image_src($post->ID,'medium');
		echo '<a href="'.get_permalink($post->ID).'"><img class="aligncenter" src="' . $src[0] . '" alt="" /></a>';
	} ?>

	<?php if($post->post_type=='video'&&$active_addons['video-gallery']){
			$data = explode(':',$post->post_excerpt);
			$video = new Rcl_Video();
			$video->service = $data[0];
			$video->video_id = $data[1];
			$video->height = 300;
			$video->width = 450;
			echo '<div class="video-iframe aligncenter">'.$video->rcl_get_video_window().'</div>';
	} ?>

	<div class="feed-content"><?php echo apply_filters('the_excerpt',get_the_excerpt($post->ID)); ?></div>
	<div class="feed-comment"><?php  _e('Comments','rcl'); ?> (<?php echo get_comments_number( $post->ID ); ?>)</div>
	
</div>
           