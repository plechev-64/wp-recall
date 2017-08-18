<?php 
$screen = get_current_screen(); ?>
<h2><?php _e('Dashboard WP-Recall','wp-recall'); ?></h2>
<div id="dashboard-widgets" class="metabox-holder">
    <div id="postbox-container-1" class="postbox-container">
    <?php do_meta_boxes( $screen->id, 'normal', '' ); ?>
    </div>
    <div id="postbox-container-2" class="postbox-container">
    <?php do_meta_boxes( $screen->id, 'side', '' ); ?>
    </div>
    <div id="postbox-container-3" class="postbox-container">
    <?php do_meta_boxes( $screen->id, 'column3', '' ); ?>
    </div>
    <div id="postbox-container-4" class="postbox-container">
    <?php do_meta_boxes( $screen->id, 'column4', '' ); ?>
    </div>
</div>
<?php
    wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false );
    wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false );