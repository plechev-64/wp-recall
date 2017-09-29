<?php

class PrimeManager extends Rcl_Custom_Fields_Manager{
    
    public $forum_groups;
    public $forums;
    public $group_id;
    public $current_group;
    
    function __construct(){

        rcl_sortable_scripts();
        
        $this->forum_groups = pfm_get_groups(array(
            'order' => 'ASC',
            'orderby' => 'group_seq'
        ));
        
        $this->group_id = (isset($_GET['group-id']))? intval($_GET['group-id']): 0;
        
        if($this->forum_groups && !$this->group_id){
            $this->group_id = $this->forum_groups[0]->group_id;
        }
        
        if($this->group_id){
            
            $this->forums = pfm_get_forums(array(
                'order' => 'ASC',
                'orderby' => 'forum_seq',
                'group_id' => $this->group_id,
                'number' => -1
            ));
            
            $this->current_group = pfm_get_group($this->group_id);
            
        }
        
    }
    
    function get_form_group(){
        
        $fields = $this->get_options_group();
        
        $content = $this->get_form_box($fields,'group_create',__('Create group','wp-recall'));
        
        return $content;

    }
    
    function get_form_forum(){
        
        $fields = $this->get_options_forum();
        
        if(!$fields) return false;
        
        $content = $this->get_form_box($fields,'forum_create',__('Create forum','wp-recall'));
        
        return $content;

    }
    
    function get_form_box($fields,$action,$submit){
        
        $content = '<div class="manager-form">';
            $content .= '<form method="post">';

                foreach($fields as $field){

                    //$value = isset($adsOptions[$option['slug']])? $adsOptions[$option['slug']]: false;

                    $required = (isset($field['required']) && $field['required'] == 1)? '<span class="required">*</span>': '';

                    $content .= '<div id="field-'.$field['slug'].'" class="form-field rcl-custom-field">';

                        if(isset($field['title'])){
                            $content .= '<label>';
                            $content .= $this->get_title($field).' '.$required;
                            $content .= '</label>';
                        }

                        $content .= $this->get_input($field);

                    $content .= '</div>';
                }

                $content .= '<div class="form-field fields-submit">';
                    $content .= '<input type="submit" class="button-primary" value="'.$submit.'">';
                $content .= '</div>';
                $content .= '<input type="hidden" name="pfm-data[action]" value="'.$action.'">';
                $content .= wp_nonce_field('pfm-action','_wpnonce',true,false);
            $content .= '</form>';
        $content .= '</div>';
        
        return $content;
        
    }
    
    function get_options_group($group = false){
        
        $options = array(
            array(
                'type' => 'text',
                'slug' => 'group_name',
                'title' => __('Name of the group of forums','wp-recall'),
                'required' => 1
            ),
            array(
                'type' => 'text',
                'slug' => 'group_slug',
                'title' => __('Slug of the group','wp-recall')
            ),
            array(
                'type' => 'textarea',
                'slug' => 'group_desc',
                'title' => __('Description of the group','wp-recall')
            )
        );
        
        $options = apply_filters('pfm_options_group', $options, $group);
        
        return $options;
    }
    
    function get_options_forum($forum = false){
        
        if(!$this->forum_groups) return false;
        
        $groups = array('' => __('Select the group forum','wp-recall'));
        
        foreach($this->forum_groups as $group){
            $groups[$group->group_id] = $group->group_name;
        }

        $options = array(
            array(
                'type' => 'select',
                'slug' => 'group_id',
                'title' => __('Forum group','wp-recall'),
                'required' => 1,
                'default' => $this->group_id,
                'values' => $groups
            ),
            array(
                'type' => 'text',
                'slug' => 'forum_name',
                'title' => __('Name of the forum','wp-recall'),
                'required' => 1
            ),
            array(
                'type' => 'text',
                'slug' => 'forum_slug',
                'title' => __('Slug of the forum','wp-recall')
            ),
            array(
                'type' => 'select',
                'slug' => 'forum_closed',
                'title' => __('Forum status','wp-recall'),
                'values' => array(
                    __('Open forum','wp-recall'),
                    __('Closed forum','wp-recall')
                ),
                'notice' => __('It is impossible to publish new topics and messages in a closed forum','wp-recall')
            ),
            array(
                'type' => 'textarea',
                'slug' => 'forum_desc',
                'title' => __('Description of the forum','wp-recall')
            )
        );
        
        $options = apply_filters('pfm_options_forum', $options, $forum);
        
        return $options;
        
    }
    
    function get_manager_groups(){
        
        $content = '<div class="manager-box manage-groups rcl-custom-fields-box">';
        
        $content .= '<h3>'.__('Manage groups','wp-recall').'</h3>';

        $content .= $this->get_groups_list();    

        $content .= $this->get_form_group();
        
        $content .= '</div>';
        
        return $content;
        
    }
    
    function get_groups_list(){

        if(!$this->forum_groups) 
            return '<p>'.__('No groups are created yet','wp-recall').'</p>';

        $content = '<div class="groups-list">';

            foreach($this->forum_groups as $group){

                $this->fields[] = array(
                    'type' => 'groups',
                    'type_id' => 'group_id',
                    'slug' => $group->group_id,
                    'group_name' => $group->group_name,
                    'title' => $group->group_name,
                    'group_slug' => $group->group_slug,
                    'group_desc' => $group->group_desc,
                    'options-field' => $this->get_options_group($group)
                );

            }

            $content .= '<div id="pfm-groups-list">';
                $content .= '<ul class="rcl-sortable-fields">';
                    $content .= $this->loop();
                $content .= '</ul>';
            $content .= '</div>';

            $content .= $this->sortable_script('groups');

        $content .= '</div>';
        
        return $content;
        
    }

    function get_forums_list(){
        
        if(!$this->forums) 
            return '<p>'.__('Forums were not created yet','wp-recall').'</p>';

        $groups = array();
        foreach($this->forum_groups as $group){
            $groups[$group->group_id] = $group->group_name;
        }

        $content = '<div class="forums-list">';
        
            $content .= '<p>'.__('Group forums','wp-recall').' "'.$this->current_group->group_name.'"</p>';

            foreach($this->forums as $forum){
                
                $this->fields[] = array(
                    'type' => 'forums',
                    'type_id' => 'forum_id',
                    'slug' => $forum->forum_id,
                    'title' => $forum->forum_name,
                    'forum_name' => $forum->forum_name,
                    'forum_desc' => $forum->forum_desc,
                    'forum_slug' => $forum->forum_slug,
                    'forum_closed' => $forum->forum_closed,
                    'group_id' => $forum->group_id,
                    'parent_id' => $forum->parent_id,
                    'options-field' => $this->get_options_forum($forum)
                );

            }
            
            $content .= '<div id="pfm-forums-list">';
                $content .= '<ul class="rcl-sortable-fields">';
                    $content .= $this->loop($this->get_children_fields(0));
                $content .= '</ul>';
            $content .= '</div>';

            $content .= $this->sortable_script('forums');

        $content .= '</div>';
        
        return $content;
        
    }
    
    function get_children_fields($parent_id){
        
        $childrens = array();
        foreach($this->fields as $field){
            if($field['parent_id'] != $parent_id) continue;
            $childrens[] = $field;
        }

        return $childrens;
        
    }
    
    function get_manager_forums(){
        
        $this->fields = array();
        
        $content = '<div class="manager-box manage-forums rcl-custom-fields-box">';
        
        $content .= '<h3>'.__('Manage forums','wp-recall').'</h3>';
        
        $content .= $this->get_forums_list();    

        $content .= $this->get_form_forum();

        $content .= '</div>';
        
        return $content;
    }
    
    function get_manager(){

        $content = '<div id="prime-forum-manager">';
        
        $content .= $this->get_manager_groups();
            
        $content .= $this->get_manager_forums();
        
        $content .= '</div>';
        
        return $content;

    }
    
    function get_input_option($option, $value = false){
        
        $value = (isset($this->field[$option['slug']]))? $this->field[$option['slug']]: $value;

        $option['name'] = $option['slug'];
        
        return $this->get_input($option, $value);
        
    }
    
    function field($args){
        
        $this->field = $args;
        
        $this->status = true;

        $classes = array('rcl-custom-field');
        
        if($this->field['type'] == 'groups' && $this->group_id == $this->field['slug']){
            $classes[] = 'active-group';
        }
  
        if(isset($this->field['class']))
            $classes[] = $this->field['class'];
        
        $title = ($this->field['type'] == 'groups')? $this->field['slug'].': '.$this->field['title']: $this->field['title'];

        $content = '<li id="field-'.$this->field['slug'].'" data-parent="'.$this->field['parent_id'].'" data-slug="'.$this->field['slug'].'" data-type="'.$this->field['type'].'" class="'.implode(' ',$classes).'">
            <div class="field-header">
                <span class="field-type type-'.$this->field['type'].'"></span>
                <span class="field-title">'.$title.'</span>                           
                <span class="field-controls">
                    <a class="field-trash field-control" href="#" title="'.__('Delete','wp-recall').'" onclick="pfm_delete_manager_item(this); return false;"></a>
                    <a class="field-edit field-control" href="#" title="'.__('Edit','wp-recall').'"></a>';
        
                if($this->field['type'] == 'groups')
                    $content .= '<a class="get-forums field-control" href="'.admin_url('admin.php?page=pfm-forums&group-id='.$this->field['slug']).'" title="'.__('Get forums','wp-recall').'"></a>';
                
                $content .= '</span>
            </div>
            <div class="field-settings">';

                $content .= '<form method="post">';

                    $content .= '<div class="options-custom-field">';
                    $content .= $this->get_options();
                    $content .= '</div>';

                    $content .= '<div class="form-buttons">';
                    $content .= '<input type="submit" class="button-primary" value="'.__('Save changes','wp-recall').'">';
                    $content .= '<input type="hidden" name="'.$this->field['type_id'].'" value="'.$this->field['slug'].'">';
                    $content .= '</div>';

                $content .= '</form>';

            $content .= '</div>';
            
            if($this->field['type'] == 'forums'){
                $content .= '<ul class="rcl-sortable-fields children-box">';
                $content .= $this->loop($this->get_children_fields($this->field['slug']));
                $content .= '</ul>';
            }
                    
        $content .= '</li>';
                        
        $this->field = false;

        return $content;

    }
    
    function sortable_script($typeList){
        
        return '<script>
                jQuery(function(){
                    jQuery(".'.$typeList.'-list .rcl-sortable-fields").sortable({
                        handle: ".field-header",
                        cursor: "move",
                        /*containment: "parent",*/
                        connectWith: ".'.$typeList.'-list .rcl-sortable-fields",
                        placeholder: "ui-sortable-placeholder",
                        distance: 15,
                        start: function(ev, ui) {
                        
                            var field = jQuery(ui.item[0]);
                            
                            field.parents("#pfm-'.$typeList.'-list > ul").find(".rcl-custom-field").each(function(a,b){
                                if(field.attr("id") == jQuery(this).attr("id")) return;
                                jQuery(this).children(".children-box").addClass("must-receive");
                            });
                            
                            field.parent().addClass("list-receive");

                        },
                        stop: function(ev, ui) {
                            
                            var field = jQuery(ui.item[0]);
                            
                            field.parents("#pfm-'.$typeList.'-list > ul").find(".children-box").removeClass("must-receive");
                            
                            var parentUl = field.parent("ul");
                            
                            parentUl.removeClass("list-receive");
                            
                            var parentID = 0;
                            if(parentUl.hasClass("children-box")){
                                var parentID = parentUl.parent("li").data("slug");                               
                            }
                            
                            field.attr("data-parent",parentID);
                            
                            pfm_manager_save_sort("'.$typeList.'");
                                
                        }
                    });
                });
            </script>';
        
    }
    
}

