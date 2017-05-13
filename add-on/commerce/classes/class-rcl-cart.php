<?php

class Rcl_Cart {
    
    public $products = array();
    public $order_price = 0;
    public $products_amount = 0;
    public $cart_url = '#';

    function __construct($args = false) {
        global $rmag_options;
        
        if(isset($rmag_options['basket_page_rmag']))
            $this->cart_url = get_permalink($rmag_options['basket_page_rmag']);
        
        $this->products = (isset($args['cart_products']) && $args['cart_products'])? $args['cart_products']: $this->get_cookie();
        
        $this->init_cart_data();

    }
    
    function get_cookie(){
        
        if(!isset($_COOKIE['rcl_cart'])) 
            return array();
        
        return json_decode(wp_unslash($_COOKIE['rcl_cart']));
    }
    
    function init_cart_data(){
    
        if($this->products){
            
            foreach($this->products as $product){

                $this->products_amount += $product->product_amount;
                $this->order_price += $product->product_amount * $product->product_price;

            }
            
        }
        
    }
    
    function get_product($product_id){
        
        $key = $this->search_product($product_id);
        
        if($key !== false){
            
            return $this->products[$key];
            
        }

        return false;
        
    }
    
    function add_product($product_id,$args){
        
        $qls = (isset($args['quantity']) && $args['quantity'])? $args['quantity']: 1;
        $vars = (isset($args['variations']) && $args['variations'])? $this->add_variations_title($product_id,$args['variations']): false;
        
        $productPrice = new Rcl_Product_Price($product_id);
        
        $product_price = $productPrice->get_price($vars);
        
        if(!$product_price)
            $product_price = 0;

        $key = $this->search_product($product_id,$vars);
        
        if($key !== false){

            $this->products[$key]->product_amount += $qls;
            
        }else{

            $this->products[] = array(
                'product_id' => $product_id,
                'product_price' => $product_price,
                'product_amount' => $qls,
                'variations' => $vars
            );
            
        }

        $this->products_amount += $qls;
        $this->order_price += $product_price;
        
        $this->update_cart();
        
        return true;

    }

    function remove_product($product_id){
        
        $key = $this->search_product($product_id);
        
        if($key === false) return false;
        
        $amount = $this->products[$key]->product_amount;
        $product_price = $this->products[$key]->product_price;
        
        if(!$amount) return false;
        
        $this->products_amount --;
        $this->order_price -= $product_price;
        
        if($amount > 1){
            
            $this->products[$key]->product_amount -= 1;
            
        }else{
            
            unset($this->products[$key]);
            
        }
        
        $this->update_cart();
        
        return true;
        
    }
    
    function search_product($product_id, $vars = false){
        
        if(!$this->products) return false;
        
        $Vars = new Rcl_Product_Variations(array('product_id'=>$product_id));

        $productVars = $Vars->get_product_variations();
        
        if($productVars)
            $varsHash = md5(json_encode($vars));
        
        foreach($this->products as $key => $product){

            if($product->product_id == $product_id){

                if(!$productVars)
                    return $key;
                
               if(!$vars && !$product->variations)
                   return $key;
                
                $productHash = md5(json_encode((array)$product->variations));
                
                if($productHash == $varsHash) 
                    return $key;

            }

        }
        
        return false;
        
    }
    
    function add_variations_title($product_id,$vars){
        
        $Vars = new Rcl_Product_Variations(array('product_id'=>$product_id));
        
        //получаем все текущие вариации товара
        $productVars = $Vars->get_product_variations();

        $newVars = array();
        foreach($productVars as $var){

            if(!isset($vars[$var['slug']])) continue;
            
            $newVars[$var['slug']] = array(
                $Vars->get_variation_title($var['slug']), //заголовок вариации
                $vars[$var['slug']] //значение
            );
            
        }
        
        return $newVars;
        
    }
    
    function reset_cart(){
        
        $this->products = array();
        
        $this->update_cart();
        
    }
    
    function update_cart(){       
        setcookie('rcl_cart',json_encode($this->products),time(),'/');
    }
 
}
