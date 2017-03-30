<?php

/**
 * Description of Rcl_Product_Variations
 *
 * @author Андрей
 */
class Rcl_Product_Variations {
    
    public $variations = array();
    public $product_id;
    public $product_variations = array();
    
    function __construct($args = false){
        
        $this->variations = apply_filters('rcl_variations', $this->get_variations());
        
        if(isset($args['product_id'])){
            $this->init_product_variations($args['product_id']);
        }
        
    }
    
    function init_product_variations($product_id){

        $this->product_variations = $this->get_product_variations($product_id);
        $this->product_id = $product_id;
        
    }
    
    function get_variations($product_id = false){
        
        $Vars = get_option('rcl_fields_products-variations');
        
        if(!$product_id)
            return $Vars;

    }
    
    function get_variation($slug){
        
        if(!$this->variations) return false;
        
        foreach($this->variations as $variation){
            
            if($variation['slug'] == $slug)
                return $variation;
            
        }
        
        return false;
        
    }
    
    function get_variation_title($slug){
        
        if(!$this->variations) return false;
        
        foreach($this->variations as $variation){
            
            if($variation['slug'] == $slug)
                return $variation['title'];
            
        }
        
        return false;
        
    }
    
    function get_product_variations($product_id = false){
        
        if(!$this->variations) return false;
        
        if(!$product_id || $product_id == $this->product_id)
            return $this->product_variations;
        
        $productVars = apply_filters('rcl_product_variations', get_post_meta($product_id, 'product-variations', 1), $product_id);
        
        if(!$productVars) return false;
        
        $varsData = array();

        foreach($this->variations as $variation){
            
            foreach($productVars as $prVar){
                
                if($variation['slug'] == $prVar['slug'])
                    $varsData[] = $prVar;
                
            }

        }

        return $varsData;
        
    }
    
    function product_exist_variation($varSlug, $product_id = false){
        
        $productVars = $this->get_product_variations($product_id);
        
        if(!$productVars) return false;
        
        foreach($productVars as $var){
            
            if($var['slug'] == $varSlug)
                return true;
            
        }
        
        return false;
        
    }
    
    function get_product_variation_value($varSlug, $varValue, $product_id = false){
        
        $productVars = $this->get_product_variations($product_id);
        
        if(!$productVars) return false;
        
        foreach($productVars as $var){
            
            if($var['slug'] == $varSlug){
                
                foreach($var['values'] as $value){
                    
                    if($value['name'] == $varValue)
                            return $value;
                    
                }
                
            }

        }
        
        return false;
        
    }
   
    
}
