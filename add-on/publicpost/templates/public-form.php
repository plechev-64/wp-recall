<?php global $formFields,$editpost,$user_ID;
	if($user_ID) rcl_sortable_scripts(); ?>
<script>
jQuery(function(){
	jQuery('.rcl-editor-content').sortable({ axis: 'y', containment: 'parent', handle: '.move-box', cursor: 'move' });
});
</script>
<?php if($formFields['title']): ?>
	<div class="rcl-form-field">
		<label><?php _e('Title','rcl'); ?> <span class="required">*</span>:</label>
		<input type="text" maxlength="150" required value="<?php rcl_publication_title(); ?>" name="post_title" id="post_title_input">
	</div>
<?php endif; ?>

<?php if($formFields['excerpt']): ?>
	<div class="rcl-form-field">
		<textarea name="post_excerpt" placeholder="Введите описание поста" ><?php rcl_publication_excerpt(); ?></textarea>
	</div>
<?php endif; ?>

<?php if($formFields['termlist']): ?>
	<div class="rcl-form-field">
		<?php rcl_publication_termlist(); ?>
	</div>
<?php endif; ?>

<?php if($formFields['editor']): ?>
	<div class="rcl-form-field">
		<?php rcl_publication_editor(); ?>
	</div>
<?php endif; ?>
<?php //print_r($formFields); ?>
<?php if($formFields['upload']): ?>
    <b><?php _e('Click on Priceline the image to add it to the content of the publication','rcl'); ?></b>
    <?php rcl_publication_upload(); ?>
<?php endif; ?>

<div class="rcl-form-field">
	<?php do_action('public_form'); ?>
</div>

<?php if($formFields['custom_fields']): ?> 
	<div class="rcl-form-field">
		<?php rcl_publication_custom_fields(); ?>
	</div>
<?php endif; ?>

               