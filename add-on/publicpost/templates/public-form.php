<?php global $formFields,$editpost; ?>

<?php if($formFields['title']): ?>
	<div class="rcl-form-field">
		<label><?php _e('Title','wp-recall'); ?> <span class="required">*</span>:</label>
		<input type="text" maxlength="150" required value="<?php rcl_publication_title(); ?>" name="post_title" id="post_title_input">
	</div>
<?php endif; ?>

<?php //if($formFields['termlist']): ?>
	<div class="rcl-form-field">
		<?php rcl_publication_termlist(); ?>
	</div>
<?php //endif; ?>

<?php if(isset($formFields['excerpt'])&&$formFields['excerpt']): ?>
	<div class="rcl-form-field">
            <textarea name="post_excerpt" required placeholder="<?php _e('Enter a brief description of the publication','wp-recall') ?>" ><?php rcl_publication_excerpt(); ?></textarea>
	</div>
<?php endif; ?>

<?php if($formFields['editor']): ?>
	<div class="rcl-form-field">
		<?php rcl_publication_editor(); ?>
	</div>
<?php endif; ?>

<?php if($formFields['upload']): ?>
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

