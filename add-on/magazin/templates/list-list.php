<?
	/*Шаблон для отображения содержимого шорткода productslist с указанием атрибута type='list'*/
	/*Данный шаблон можно разместить в папке используемого шаблона /recall/templates/ и он будет подключаться оттуда*/
?>
<?php global $post; ?>
<div class="prodlist">
    
    <div class="prod-single list-list">
        <?php if ( has_post_thumbnail()) : ?>
                <div class="thumb-prod" width="110">
                        <?php the_post_thumbnail( array(100,100) ); ?>
                </div>
        <?php endif; ?>

        <div class="product-content">

            <?php echo get_price($post->ID); ?>

            <a href="<?php the_permalink(); ?>">
                    <h3 class="title-prod"><?php the_title(); ?></h3>
            </a>

            <div class="product-excerpt">
                    <?php the_product_excerpt(); ?>
            </div>


            <?php echo get_button_cart_rcl($post->ID); ?>

        </div>

    </div>
	
</div>