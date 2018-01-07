<?php

//Перечень действующих валют
function rcl_get_currency_list(){
    
    $rub = (is_admin())? 'p': '<i class="fa fa-rub"></i>';
    
    return array(
        'RUB' => array('руб',$rub,'<span class="ruble-symbol">P<span>–</span></span>'),
        'UAH' => array('гривен','грн','грн'),
        'KZT' => array('тенге','тнг','тнг'),
        'USD' => array('dollars','<i class="fa fa-usd"></i>','$'),
        'EUR' => array('euro','<i class="fa fa-eur"></i>','€'),
    );
}

function rcl_get_currency($cur=false,$type=0){
    
    $curs = rcl_get_currency_list();
    
    $curs = apply_filters('currency_list',$curs);
    
    if(!$cur){
        foreach($curs as $cur => $nms){
            $crs[$cur] = $cur;
        }
        return $crs;
    }

    if(!isset($curs[$cur][$type])) return false;
    
    return $curs[$cur][$type];
}

function rcl_type_currency_list($post_id){
    global $rmag_options;
    if($rmag_options['multi_cur']){
            $type = get_post_meta($post_id,'type_currency',1);
            $curs = array($rmag_options['primary_cur'],$rmag_options['secondary_cur']);
            $conts = '<select name="wprecall[type_currency]">';
            foreach($curs as $cur){
                    $conts .= '<option '.selected($type,$cur,false).' value="'.$cur.'">'.$cur.'</option>';
            }
            $conts .= '</select>';
    }else{
            $conts = $rmag_options['primary_cur'];
    }
    echo $conts;
}
function rcl_get_current_type_currency($post_id){
    global $rmag_options;
    if($rmag_options['multi_cur']){
            $type = get_post_meta($post_id,'type_currency',1);
            $curs = array($rmag_options['primary_cur'],$rmag_options['secondary_cur']);
            if($type==$curs[0]||$type==$curs[1]) $current = $type;
            else $current = $curs[0];
    }else{
            $current = $rmag_options['primary_cur'];
    }
    return $current;
}
function get_current_currency($post_id){
    $current = rcl_get_current_type_currency($post_id);
    return rcl_get_currency($current,1);
}
//Вывод основной валюты сайта
function rcl_get_primary_currency($type=0){
    global $rmag_options;
    $cur = (isset($rmag_options['primary_cur']))? $rmag_options['primary_cur']:'RUB';
    return rcl_get_currency($cur,$type);
}
function rcl_primary_currency($type=0){
    echo rcl_get_primary_currency($type);
}

