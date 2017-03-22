<?php

class Rcl_Edit_Terms_List{

    public $cats;
    public $new_cat = array();

    function get_terms_list($cats,$post_cat){
        $this->cats = $cats;
        $this->new_cat = $post_cat;
        $cnt = count($post_cat);
        for($a=0;$a<$cnt;$a++){
            foreach((array)$cats as $cat){
                if($cat->term_id!=$post_cat[$a]) continue;
                if($cat->parent==0) continue;
                $this->new_cat = $this->get_parents($cat->term_id);
            }
        }
        return $this->new_cat;
    }
	
    function get_parents($term_id){
        foreach($this->cats as $cat){
            if($cat->term_id!=$term_id) continue;
            if($cat->parent==0) continue;
            $this->new_cat[] = $cat->parent;
            $this->new_cat = $this->get_parents($cat->parent);
        }
        return $this->new_cat;
    }
}
