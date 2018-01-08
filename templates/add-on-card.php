<?php global $addon,$rcl_addons,$wprecall; ?>
<div class="addon-box plugin-card plugin-card-<?php echo $addon->slug; ?>">
    <div class="plugin-card-top">
        <div class="name column-name">
            <h3>
                <a href="<?php echo $addon->add_on_uri; ?>" class="thickbox">
                <?php echo $addon->name; ?>
                <img src="<?php echo $addon->thumbnail; ?>" class="plugin-icon" alt="">
                </a>
            </h3>
        </div>
        <div class="action-links">
            <ul class="plugin-action-buttons">
                <?php if(isset($rcl_addons[$addon->slug]) && $rcl_addons[$addon->slug]['need-update']): ?>
                    <li><a class="update-now button aria-button-if-js" data-addon="<?php echo $addon->slug; ?>" href="#" aria-label="Обновить сейчас" role="button" onclick='rcl_update_addon(<?php echo json_encode(array('slug'=>$addon->slug)); ?>,this);return false;'>Обновить</a></li>
                <?php elseif(isset($rcl_addons[$addon->slug])): ?>
                    <li><span class="button button-disabled" title="<?php _e('This add-on has already been installed','wp-recall') ?>"><?php _e('Installed','wp-recall') ?></span></li>
                <?php else: ?>
                    <li><a class="button" target="_blank" data-slug="<?php echo $addon->slug; ?>" href="<?php echo $addon->add_on_uri; ?>" aria-label="<?php _e('Go to page','wp-recall') ?> <?php echo $addon->name; ?> <?php echo $addon->version; ?>" data-name="<?php echo $addon->name; ?> <?php echo $addon->version; ?>"><?php _e('Go to','wp-recall') ?></a></li>
                <?php endif; ?>
                <li><a href="#" class="open-addon-details-modal" onclick='rcl_get_details_addon(<?php echo json_encode(array('slug'=>$addon->slug)); ?>,this);return false;' aria-label="Подробности о <?php echo $addon->name; ?>" data-title="<?php echo $addon->name; ?>">Детали</a></li>
        </div>
        <div class="desc column-description">
            <p><?php print_r($addon->description); ?></p>
            <p class="authors"> <cite><?php _e('Author','wp-recall') ?>: <a href="<?php echo $addon->author_uri; ?>" target="_blank" ><?php echo $addon->author; ?></a></cite></p>
        </div>
        <div class="addon-terms">
            <?php if(isset($addon->terms) && $addon->terms):
                
                foreach($addon->terms as $taxonomy => $terms){ $html = array(); ?>
                
                    <p><cite><?php echo ($taxonomy == 'prodcat')? __('Category','wp-recall'): __('Tags','wp-recall') ?>: 
            
                    <?php foreach($terms as $slug => $name){ 
                            
                            $html[] = '<a href="'.admin_url('admin.php?page=rcl-repository&type=tag&s='.$name).'">'.$name.'</a>';
                        
                     } ?>
                            
                    <?php echo implode(', ', $html); ?>
                            
                    </cite></p>
                    
                <?php }
            
            endif; ?>
        </div>           
    </div>
    <div class="plugin-card-bottom">
        <div class="vers column-rating">
            <?php wp_star_rating( array(
                    'rating'=>$addon->rating->value, 
                    'type'=>'rating', 
                    'number'=>$addon->rating->votes
                )); ?>
            <span class="num-ratings">(<?php echo $addon->rating->votes; ?>)</span>
        </div>
        <div class="column-updated">
            <strong><?php _e('Updated','wp-recall') ?>:</strong> <span title="<?php echo $addon->update; ?>">
                <?php echo human_time_diff(strtotime($addon->update),time() ).' '.__('ago','wp-recall'); ?>
            </span>
        </div>
        <div class="column-downloaded"><?php echo $addon->downloads; ?> <?php _e('downloads','wp-recall') ?></div>
        <div class="column-compatibility">
            <?php if(isset($addon->support_core)){ ?>
                <span class="compatibility-compatible"><strong><?php _e('Compatible','wp-recall') ?></strong> с WP-Recall <?php echo $addon->support_core; ?> и выше</span>
                <?php if(version_compare($addon->support_core, $wprecall->version) > 0){ ?>
                    <span class="compatibility-untested"><?php _e('Operation is not guaranteed with your version of WP-Recall','wp-recall') ?></span>
                <?php }  ?>
            <?php }  ?>
        </div>
    </div>
</div>