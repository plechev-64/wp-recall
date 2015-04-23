<?php global $formFields,$editpost; ?>

<?php if($formFields['title']): ?>
    <label><?php _e('Заголовок'); ?> <span class="required">*</span>:</label>
    <input type="text" maxlength="150" required value="<?php the_public_title(); ?>" name="post_title" id="post_title_input">
<?php endif; ?>

<?php if($formFields['termlist']): ?>   
    <?php the_public_termlist(); ?>
<?php endif; ?>

<?php if($formFields['editor']): ?>
    <label><?php _e('Содержимое публикации'); ?></label>
    <?php the_public_editor(); ?>
<?php endif; ?>

<?php if($formFields['upload']): ?>
    <b><?php _e('Нажмите на прикреленное изображение, чтобы добавить его в контент публикации'); ?></b>
    <?php the_public_upload(); ?>
<?php endif; ?>

<?php do_action('public_form'); ?>

<?php if($formFields['custom_fields']): ?> 
    <?php the_public_custom_fields(); ?>
<?php endif; ?>

               