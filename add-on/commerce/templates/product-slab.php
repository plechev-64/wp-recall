<?php
/*Шаблон для отображения содержимого шорткода [productlist] с указанием атрибута type='slab',
а также при выводе рекомендуемых товаров	
Данный шаблон можно разместить в папке используемого шаблона /wp-content/wp-recall/templates/ и он будет подключаться оттуда*/

global $post;

$attrWidth = (isset($width) && is_numeric($width))? 'style="width:'.$width.'px;"': '';
$imagesize = (isset($width))? array($width,$width): 'thumbnail'; ?>
<div class="product" <?php echo $attrWidth; ?> id="product-<?php the_ID(); ?>">
    <a class="product-thumbnail" href="<?php the_permalink(); ?>">
        <?php the_post_thumbnail($imagesize,array('alt'=>$post->post_title)); ?>
    </a>
    <div class="product-content">
        <a class="product-title" href="<?php the_permalink(); ?>">
            <?php the_title(); ?>
        </a>
        <div class="product-metas">
            
            <div class="product-meta">
                <i class="fa fa-info rcl-icon"></i>
                <span class="meta-content-box">
                    <span class="meta-content"><?php rcl_product_excerpt($post->ID); ?></span>
                </span>
            </div>

            <?php echo rcl_get_product_terms($post->ID); ?>
            
        </div>

        <?php echo rcl_get_cart_box($post->ID, 
                    array(
                        'variations' => false,
                        'quantity' => false,
                    )); ?>
    </div>
</div>
