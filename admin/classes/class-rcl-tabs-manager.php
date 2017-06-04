<?php

class Rcl_Tabs_Manager extends Rcl_Custom_Fields_Manager{
    
    function __construct($areaType, $options = false) {
        
        parent::__construct($areaType, $options);

        $this->setup_tabs();

        add_filter('rcl_custom_field_options', array($this, 'edit_tab_options'), 10, 3);
        
    }
    
    function form_navi(){
        
        $areas = array(
            'area-menu'     => __('"Menu" area','wp-recall'),
            'area-actions'  => __('"Actions" area','wp-recall'),
            'area-counters' => __('"Counters" area','wp-recall')
        );

        $content = '<div class="rcl-custom-fields-navi">';
        
            $content .= '<ul class="rcl-types-list">';

            foreach ($areas  as $type => $name ) {
                
                $class = ($this->post_type == $type)? 'class="current-item"': '';
                
                $content .= '<li '.$class.'><a href="'.admin_url('admin.php?page=rcl-tabs-manager&area-type='.$type).'">'.$name.'</a></li>';
            }

            $content .= '</ul>';

        $content .= '</div>';
        
        return $content;
        
    }
    
    function active_fields_box(){

        $defaultOptions = array(
    
            array(
                'type' => 'text',
                'slug'=>'slug',
                'title'=>__('Tab ID','wp-recall'),
                'placeholder'=>__('Latin alphabet and numbers','wp-recall')
            ),

            array(
                'type' => 'text',
                'slug'=>'icon',
                'title'=>__('Icon class of  font-awesome','wp-recall'),
                'placeholder'=>__('Example , fa-user','wp-recall'),
                'notice'=>__('Source','wp-recall').' <a href="http://fontawesome.io/icons/" target="_blank">http://fontawesome.io/</a>'
            ),
            
            array(
                'type' => 'select',
                'slug'=>'public-tab',
                'title'=>__('Tab privacy','wp-recall'),
                'values'=>array(
                    __('Private','wp-recall'),
                    __('Public','wp-recall')
                )
            ),

            array(
                'type' => 'checkbox',
                'slug'=>'supports-tab',
                'title'=>__('Support of the functions','wp-recall'),
                'values'=>array(
                    'ajax' => __('ajax-loading','wp-recall'),
                    'cache' => __('caching','wp-recall'),
                    'dialog' => __('dialog box','wp-recall')
                )
            ),

            array(
                'type' => 'editor',
                'tinymce' => true,
                'slug'=>'content',
                'title'=>__('Content tab','wp-recall'),
                'notice'=>__('supported shortcodes and HTML-code','wp-recall')
            )

        );

        $content = $this->manager_form($defaultOptions);
        
        return $content;
        
    }
    
    function is_default_tab($slug){
        global $rcl_tabs;
        
        if(isset($rcl_tabs[$slug]) && !isset($rcl_tabs[$slug]['custom-tab']))
            return true;
        
        return false;
        
    }
    
    function setup_tabs(){
        
        $defaultTabs = $this->get_default_tabs();
        
        if($this->fields){
            
            foreach($this->fields as $k => $tab){

                if($this->is_default_tab($tab['slug'])){
                    $this->fields[$k]['delete'] = false;
                }else{
                    if(isset($tab['default-tab'])){
                        unset($this->fields[$k]);
                    }
                }
                
            }
            
            foreach($defaultTabs as $tab){
                if($this->exist_active_field($tab['slug']))continue;
                $this->fields[] = $tab;
            }
            
        }else{
            
            $this->fields = $defaultTabs;
            
        }
        
    }
    
    function get_default_tabs(){
        global $rcl_tabs;
        
        if(!$rcl_tabs) return false;
        
        $fields = array();
        
        foreach($rcl_tabs as $tab_id => $tab){
            
            if(isset($tab['custom-tab'])) continue;
             
            $tabArea = (isset($tab['output']))? $tab['output']: 'menu';
            
            if('area-'.$tabArea != $this->post_type) continue;
            
            $fields[] = array(
                'type-edit' => false,
                'slug' => $tab_id,
                'delete' => false,
                'default-tab' => true,
                'type' => 'custom',
                'title' => $tab['name'],
                'icon' => $tab['icon']
            );
            
        }
        
        return $fields;

    }
    
    function edit_tab_options($options, $field, $type){
        global $rcl_tabs;
        
        if(!isset($field['slug'])) return $options;

        if($this->is_default_tab($field['slug'])){
            
            foreach($options as $k => $option){
                
                if($option['slug'] == 'public-tab'){
                    unset($options[$k]);
                }
                
                if($option['slug'] == 'supports-tab'){
                    unset($options[$k]);
                }
                
                if($option['slug'] == 'content'){
                    unset($options[$k]);
                }
                
                if($option['slug'] == 'slug'){
                    unset($options[$k]);
                }
                
                if($option['slug'] == 'icon'){
                    $options[$k]['placeholder'] = $rcl_tabs[$field['slug']]['icon'];
                }
 
            }
            
            $options[] = array(
                'type' => 'hidden',
                'slug' => 'default-tab',
                'value' => 1
            );
            
        }
        
        return $options;
        
    }
    
}

