<?php

class Rcl_Notify{
    function __construct($text,$type){
        $this->type = $type;
        $this->text = $text;
        add_filter('notify_lk',array($this,'add_notify'));
    }
    function add_notify($text){
        $text .= '<div class="'.$this->type.'">'.$this->text.'</div>';
        return $text;
    }
}
