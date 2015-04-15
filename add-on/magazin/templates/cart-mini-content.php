<?
	/*Шаблон для отображения динамичного содержимого содержимого шорткода minibasket*/
	/*Данный шаблон можно разместить в папке используемого шаблона /recall/templates/ и он будет подключаться оттуда*/
?>
<?php global $CartData; ?>
<div>
	Всего товаров: <span class="cart-numbers"><?php echo $CartData->numberproducts; ?></span> шт.
</div>
<div>
	Общая сумма: <span class="cart-summa"><?php echo $CartData->cart_price; ?></span> руб.
</div>
<a href="<?php echo get_permalink($CartData->cart_url); ?>">Перейти в корзину</a>
