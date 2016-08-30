<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


/**
 * Description of class-rcl-sub-tabs
 *
 * @author Андрей
 */
class Rcl_Sub_Tabs {
    
    public $subtabs;
    public $active_tab;
    public $parent_id;
    public $callback;
    
    function __construct($subtabs,$parent_id = false){
        $this->subtabs = $subtabs;
        $this->parent_id = $parent_id;
        $this->active_tab = (isset($_GET['subtab']))? $_GET['subtab']: $this->subtabs[0]['id'];
    }
    
    function get_sub_content(){
        $content = $this->get_submenu();
        $content .= $this->get_subtab();
        return $content;
    }
    
    function get_submenu(){
        global $user_LK;

        $content = '<div class="rcl-subtab-menu">';

        foreach($this->subtabs as $key=>$tab){

            if($this->active_tab==$tab['id']){
                $this->callback = $tab['callback'];
            }

            $classes = ($this->active_tab==$tab['id'])? array('active','rcl-subtab-button'): array('rcl-subtab-button');

            $button_args = array('class'=>implode(' ',$classes));

            if(isset($tab['icon'])){
                $button_args['icon'] = $tab['icon'];
            }

            $content .= rcl_get_button($tab['name'],$this->url_string($tab['id']),$button_args);

        }

        $content .= '</div>';
        
        return $content;
        
    }
    
    function get_subtab(){

        $content .= '<div class="rcl-subtab-content">';
        
        $args = (isset($this->callback['args']))? $this->callback['args']: array();

        $content .= call_user_func_array($this->callback['name'],$args);

        $content .= '</div>';

        return $content;

    }

    function url_string($subtab_id){
        global $user_LK;
        
        $tab_id = (isset($_GET['tab']))? $_GET['tab']: '';
        
        $url = (isset($_POST['tab_url']))? $_POST['tab_url']: rcl_format_url(get_author_posts_url($user_LK),$tab_id);

        $url .= '&subtab='.$subtab_id;
        
        return $url;
    }
}
