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
        
        $this->group_id = (isset($_GET['group-id']))? $_GET['group-id']: 0;
        
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
        
        $fields = $this->get_fields_form_group();
        
        $content = $this->get_form_box($fields,'group_create',__('Создать группу'));
        
        return $content;

    }
    
    function get_form_forum(){
        
        $fields = $this->get_fields_form_forum();
        
        if(!$fields) return false;
        
        $content = $this->get_form_box($fields,'forum_create',__('Создать форум'));
        
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
    
    function get_fields_form_group(){
        
        $fields = array(
            array(
                'type' => 'text',
                'slug' => 'group-title',
                'name' => 'pfm-data[group_name]',
                'title' => __('Название группы форумов'),
                'required' => 1
            ),
            array(
                'type' => 'text',
                'slug' => 'group-slug',
                'name' => 'pfm-data[group_slug]',
                'title' => __('Slug группы')
            ),
            array(
                'type' => 'textarea',
                'slug' => 'group-desc',
                'name' => 'pfm-data[group_desc]',
                'title' => __('Описание группы')
            )
        );
        
        return $fields;
    }
    
    function get_fields_form_forum(){
        
        if(!$this->forum_groups) return false;
        
        $groups = array('' => 'Выберите группу форума');
        
        foreach($this->forum_groups as $group){
            $groups[$group->group_id] = $group->group_name;
        }

        $fields = array(
            array(
                'type' => 'select',
                'slug' => 'forum-group',
                'name' => 'pfm-data[group_id]',
                'title' => __('Группа форума'),
                'required' => 1,
                'default' => $this->group_id,
                'values' => $groups
            ),
            array(
                'type' => 'text',
                'slug' => 'forum-name',
                'name' => 'pfm-data[forum_name]',
                'title' => __('Название форума'),
                'required' => 1
            ),
            array(
                'type' => 'text',
                'slug' => 'forum-slug',
                'name' => 'pfm-data[forum_slug]',
                'title' => __('Slug форума')
            ),
            array(
                'type' => 'textarea',
                'slug' => 'forum-desc',
                'name' => 'pfm-data[forum_desc]',
                'title' => __('Описание форума')
            )
        );
        
        return $fields;
        
    }
    
    function get_manager_groups(){
        
        $content = '<div class="manager-box manage-groups rcl-custom-fields-box">';
        
        $content .= '<h3>'.__('Управление группами').'</h3>';

        $content .= $this->get_groups_list();    

        $content .= $this->get_form_group();
        
        $content .= '</div>';
        
        return $content;
        
    }
    
    function get_groups_list(){

        if(!$this->forum_groups) 
            return '<p>'.__('Группы пока не созданы.').'</p>';

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
                    'options-field' => array(
                        array(
                            'type' => 'text',
                            'slug' => 'group_name',
                            'title' => __('Название группы')
                        ),
                        array(
                            'type' => 'text',
                            'slug' => 'group_slug',
                            'title' => __('Slug группы')
                        ),
                        array(
                            'type' => 'textarea',
                            'slug' => 'group_desc',
                            'title' => __('Описание группы')
                        )
                    )
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
            return '<p>'.__('Форумы пока не создавались.').'</p>';

        $groups = array();
        foreach($this->forum_groups as $group){
            $groups[$group->group_id] = $group->group_name;
        }

        $content = '<div class="forums-list">';
        
            $content .= '<p>Форумы группы "'.$this->current_group->group_name.'"</p>';

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
                    'options-field' => array(
                        array(
                            'type' => 'select',
                            'slug' => 'group_id',
                            'title' => __('Группа форума'),
                            'values' => $groups
                        ),
                        array(
                            'type' => 'text',
                            'slug' => 'forum_name',
                            'title' => __('Название форума')
                        ),
                        array(
                            'type' => 'text',
                            'slug' => 'forum_slug',
                            'title' => __('Slug форума')
                        ),
                        array(
                            'type' => 'select',
                            'slug' => 'forum_closed',
                            'title' => __('Статус форума'),
                            'values' => array(
                                __('Открытый форум'),
                                __('Закрытый форум')
                            ),
                            'notice' => __('В закрытом форуме невозможна публикация новых топиков и сообщений')
                        ),
                        array(
                            'type' => 'textarea',
                            'slug' => 'forum_desc',
                            'title' => __('Описание форума')
                        )
                    )
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
        
        $content .= '<h3>'.__('Управление форумами').'</h3>';
        
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

        $option['name'] = 'options['.$option['slug'].']';
        
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
                    $content .= '<a class="get-forums field-control" href="'.admin_url('admin.php?page=pfm-forums&group-id='.$this->field['slug']).'" title="'.__('Получить форумы').'"></a>';
                
                $content .= '</span>
            </div>
            <div class="field-settings">';

                $content .= '<form method="post">';

                    $content .= '<div class="options-custom-field">';
                    $content .= $this->get_options();
                    $content .= '</div>';

                    $content .= '<div class="form-buttons">';
                    $content .= '<input type="submit" class="button-primary" value="'.__('Сохранить изменения').'">';
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
                            
                            var fields = new Array;
                            field.parents(".'.$typeList.'-list ul").find(".rcl-custom-field").each(function(a,b){
                                fields[a] = {
                                    "id": jQuery(this).attr("data-slug"),
                                    "parent": jQuery(this).attr("data-parent")
                                }
                            });
                            
                            var box = jQuery("#prime-forum-manager");
    
                            rcl_preloader_show(box);
                            
                            var dataString = "action=pfm_ajax_update_sort_'.$typeList.'&sort=" + JSON.stringify(fields);

                            jQuery.ajax({
                                type: "POST", data: dataString, dataType: "json", url: ajaxurl,
                                success: function(result){

                                    rcl_preloader_hide();

                                    if(result["error"]){
                                        rcl_notice(result["error"],"error",10000);
                                        return false;
                                    }

                                    rcl_notice(result["success"],"success",10000);

                                }
                            });
                        }
                    });
                });
            </script>';
        
    }
    
}
