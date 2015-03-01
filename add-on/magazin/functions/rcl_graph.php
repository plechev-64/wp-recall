<?php

class Rcl_Graph {
    function __construct(){
        
    }
    function get_graph($data){
        $graph = '<div class="graph">';
        foreach($data as $dt=>$val){
            $graph = '<div class="col"><span class="dt">'.$dt.'</span><span class="val">'.$val.'</span></div>';
        }
        $graph .= '</div>';
    }
}
