<form class="rcl-cart-form" data-product="<?php echo $data->product_id ?>" method="post">

    <?php if($data->output['old_price'])
        echo $data->old_price_box(); ?>

    <?php if($data->output['price'])
        echo $data->price_box(); ?>

    <?php if($data->output['variations'])
        echo $data->variations_box($data->product_id); ?>
    
    <?php do_action('rcl_cart_button_form',$data); ?>

    <?php if($data->output['quantity'])
        echo $data->quantity_selector_box(); ?>

    <?php if($data->output['cart_button'])
        echo $data->cart_button(); ?>
    
    <?php do_action('rcl_cart_button_form_bottom',$data); ?>

    <input type="hidden" name="cart[product_id]" value="<?php echo $data->product_id ?>">
        
</form>

