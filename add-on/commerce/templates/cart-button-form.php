<form class="rcl-cart-form" data-product="<?php echo $Cart_Button->product_id ?>" method="post">

    <?php if($Cart_Button->output['old_price'])
        echo $Cart_Button->old_price_box(); ?>

    <?php if($Cart_Button->output['price'])
        echo $Cart_Button->price_box(); ?>

    <?php if($Cart_Button->output['variations'])
        echo $Cart_Button->variations_box($Cart_Button->product_id); ?>
    
    <?php do_action('rcl_cart_button_form',$Cart_Button); ?>

    <?php if($Cart_Button->output['quantity'])
        echo $Cart_Button->quantity_selector_box(); ?>

    <?php if($Cart_Button->output['cart_button'])
        echo $Cart_Button->cart_button(); ?>
    
    <?php do_action('rcl_cart_button_form_bottom',$Cart_Button); ?>

    <input type="hidden" name="cart[product_id]" value="<?php echo $Cart_Button->product_id ?>">
        
</form>

