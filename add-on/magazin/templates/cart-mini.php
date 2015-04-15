<?
	/*Шаблон для отображения содержимого шорткода minibasket - малой корзины пользователя*/
	/*Данный шаблон можно разместить в папке используемого шаблона /recall/templates/ и он будет подключаться оттуда*/
?>
<?php global $CartData; ?>
<div class="cart-icon">
	<i class="fa fa-shopping-cart"></i>
</div>
<div>В вашей корзине:</div>	

<?php if($CartData->numberproducts): ?>

	<?php include_template_rcl('cart-mini-content.php',__FILE__); ?>
	
<?php else: ?>

	<div class="empty-basket" style="text-align:center;">Пока пусто</div>
	
<?php endif; ?>
