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
    public $parent_tab;
    public $callback;
    
    function __construct($subtabs,$parent_id = false){
        
        $this->subtabs = $subtabs;
        $this->parent_id = $parent_id;
        $this->parent_tab = rcl_get_tab($parent_id);
        
        if(isset($_GET['subtab'])){
            
            foreach($subtabs as $k=>$subtab){

                if($_GET['subtab']==$subtab['id']){
                    $this->active_tab = $subtab['id'];
                }

            }
            
        }
        
        if(!$this->active_tab){
            
           $this->active_tab = $this->subtabs[0]['id'];
            
        }

    }
    
    function get_sub_content($master_id){
        $content = $this->get_submenu($master_id);
        $content .= $this->get_subtab($master_id);
        return $content;
    }
    
    function get_submenu($master_id){
        
        $content = '<div class="rcl-subtab-menu">';

        foreach($this->subtabs as $key=>$tab){
            
            if(!$tab['name']) continue;

            $classes = ($this->active_tab == $tab['id'])? array('active','rcl-subtab-button'): array('rcl-subtab-button');
            
            if(isset($this->parent_tab['supports'])){
                if(in_array('ajax',$this->parent_tab['supports'])){
                    $classes[] = 'rcl-ajax';
                }
            }

            $button_args = array('class' => implode(' ',$classes));

            if(isset($tab['icon'])){
                $button_args['icon'] = $tab['icon'];
            }
            
            $button_args['attr'] = 'data-post='.rcl_encode_post(array(
                                            'tab_id'=>$this->parent_id,
                                            'subtab_id'=>$tab['id'],
                                            'master_id'=>$master_id
                                        ));

            $content .= rcl_get_button($tab['name'], $this->url_string($master_id,$tab['id']), $button_args);

        }

        $content .= '</div>';
        
        return $content;
        
    }
    
    function get_subtab($master_id){
        
        foreach($this->subtabs as $key=>$tab){
            if($this->active_tab==$tab['id']){
                $this->callback = (isset($tab['callback']))? $tab['callback']: false;
            }
        }

        $content = '<div id="subtab-'.$this->active_tab.'" class="rcl-subtab-content">';
        
        if($this->callback){
            
            if(isset($this->callback['args'])){
                $args = $this->callback['args'];
            }else{
                $args = array($master_id);
            }

            $funcContent = call_user_func_array($this->callback['name'],$args);
            
            if(!$funcContent){
                rcl_add_log(
                    'get_subtab: '.__('Failed to load tab content','wp-recall'),
                    $this->callback
                );
            }
            
            $content .= $funcContent;
            
        }

        $content .= '</div>';

        return $content;

    }

    function url_string($master_id,$subtab_id){
        
        $url = rcl_format_url(get_author_posts_url($master_id),$this->parent_id,$subtab_id);
        
        return $url;
    }
}
