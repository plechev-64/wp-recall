<?php global $rcl_box; ?>
<div class="rcl-content-box">
	<div class="field-icons">
		<a href="#" title="удалить" onclick="return rcl_delete_editor_box(this);" class="rcl-icon"><i class="fa fa-times"></i></a>
		<span class="rcl-icon move-box" title="переместить"><i class="fa fa-arrows"></i></span>						
	</div>
	<input type="text" name="post_content[][header]" value="<?php echo $rcl_box['content']; ?>" placeholder="Подзаголовок">
</div>

               