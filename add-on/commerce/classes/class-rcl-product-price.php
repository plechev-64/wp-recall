<?php

/**
 * Description of Rcl_Product_Price
 *
 * @author Андрей
 */
class Rcl_Product_Price {
    
    public $product_id;
    public $product_price;
    
    function __construct($product_id) {
        
        $this->product_id = $product_id;
        $this->product_price = get_post_meta($this->product_id,'price-products',1);
        
    }
    
    function get_price($vars = false){
        
        if($vars)
            $this->add_variation_price($vars);
        
        return $this->product_price;
        
    }
    
    function add_variation_price($vars){
        
        $Vars = new Rcl_Product_Variations(array('product_id'=>$this->product_id));
        
        //получаем все текущие вариации товара
        $productVars = $Vars->get_product_variations();
        
        if(!$productVars) return false;
        
        foreach($productVars as $productVar){
            
            if(!isset($vars[$productVar['slug']])) continue;
            
            $name = $vars[$productVar['slug']][1];
            
            foreach($productVar['values'] as $varValue){
                
                if(is_array($name)){
                    if(!in_array($varValue['name'],$name)) continue;
                }else{
                    if($varValue['name'] != $name) continue;
                }

                $this->product_price += $varValue['price'];
                
            }
            
        }
        
    }
    
}
