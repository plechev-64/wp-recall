<?
	/*Шаблон для отображения динамичного содержимого содержимого шорткода minibasket*/
	/*Данный шаблон можно разместить в папке используемого шаблона /wp-recall/templates/ и он будет подключаться оттуда*/
?>
<?php global $CartData; ?>
<div>
	Всего товаров: <span class="cart-numbers"><?php echo $CartData->numberproducts; ?></span> шт.
</div>
<div>
	Общая сумма: <span class="cart-summa"><?php echo rcl_add_primary_currency_price($CartData->cart_price); ?></span>
</div>
<a href="<?php echo get_permalink($CartData->cart_url); ?>">Перейти в корзину</a>
