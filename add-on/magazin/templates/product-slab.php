<?php
/*
Шаблон для отображения содержимого шорткода [productlist] с указанием атрибута type='slab',
а также при выводе рекомендуемых товаров	
Данный шаблон можно разместить в папке используемого шаблона /wp-content/wp-recall/templates/ и он будет подключаться оттуда
*/

global $post,$productlist; 
$width = (isset($productlist['width']))? 'style="width:'.$productlist['width'].'px;"': '';
$imagesize = ($width)? array($width,$width): 'thumbnail'; ?>
<div class="product" <?php echo $width; ?> id="product-<?php the_ID(); ?>" itemscope itemtype="http://schema.org/Product">
    <a class="product-thumbnail" href="<?php the_permalink(); ?>">
            <?php the_post_thumbnail($imagesize,array('alt'=>$post->post_title,'itemprop'=>'image')); ?>
    </a>
    <div class="product-content">
            <a class="product-title" href="<?php the_permalink(); ?>" itemprop="name">
                <?php the_title(); ?>
            </a>
            <div class="product-meta">
                <?php rcl_product_excerpt(); ?>
                <?php echo rcl_get_product_category($post->ID); ?>
            </div>
            <?php echo rcl_get_cart_button($post->ID); ?>
    </div>
</div>
