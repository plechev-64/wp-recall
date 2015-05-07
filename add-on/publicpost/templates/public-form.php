<?php global $formFields,$editpost; ?>

<?php if($formFields['title']): ?>
    <label><?php _e('Title','rcl'); ?> <span class="required">*</span>:</label>
    <input type="text" maxlength="150" required value="<?php rcl_publication_title(); ?>" name="post_title" id="post_title_input">
<?php endif; ?>

<?php if($formFields['termlist']): ?>   
    <?php rcl_publication_termlist(); ?>
<?php endif; ?>

<?php if($formFields['editor']): ?>
    <label><?php _e('The content of the post','rcl'); ?></label>
    <?php rcl_publication_editor(); ?>
<?php endif; ?>

<?php if($formFields['upload']): ?>
    <b><?php _e('Click on Priceline the image to add it to the content of the publication','rcl'); ?></b>
    <?php rcl_publication_upload(); ?>
<?php endif; ?>

<?php do_action('public_form'); ?>

<?php if($formFields['custom_fields']): ?> 
    <?php rcl_publication_custom_fields(); ?>
<?php endif; ?>

               