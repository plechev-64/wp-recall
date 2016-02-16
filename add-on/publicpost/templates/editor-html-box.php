<?php global $rcl_box; ?>
<div class="rcl-content-box">
	<div class="field-icons">
		<a href="#" title="удалить" onclick="return confirm('<?php _e('Are you sare?','wp-recall')?>')? rcl_delete_editor_box(this): false;" class="rcl-icon"><i class="fa fa-times"></i></a>
		<span class="rcl-icon move-box" title="<?php _e('move','wp-recall') ?>"><i class="fa fa-arrows"></i></span>
	</div>	
	<textarea name="post_content[][html]" placeholder="HTML"><?php echo $rcl_box['content']; ?></textarea>
</div>

               