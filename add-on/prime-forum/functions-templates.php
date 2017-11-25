<?php

function pfm_get_template_content(){
    
    $theme = pfm_get_current_theme();
    
    if(!$theme) return false;
    
    global $PrimeQuery,$PrimeUser;
    
    rcl_dialog_scripts();
    
    $content = '<div id="prime-forum" class="'.pfm_class_forum().'">';
    
    $content .= pfm_get_primary_manager();
    
    $content .= rcl_get_include_template('pfm-header.php',$theme['path']);
    
    if(pfm_is_search()){
        
        $content .= rcl_get_include_template('pfm-search.php',$theme['path']);
        
    }if(pfm_is_author()){
        
        $content .= rcl_get_include_template('pfm-author.php',$theme['path']);
        
    }else if(pfm_is_home()){
        
        $content .= rcl_get_include_template('pfm-home.php',$theme['path']);
        
    }else if(pfm_is_group()){
        
        $content .= rcl_get_include_template('pfm-group.php',$theme['path']);
        
    }else if(pfm_is_forum()){
        
        $content .= rcl_get_include_template('pfm-forum.php',$theme['path']);
        
    }else if(pfm_is_topic()){
        
        $content .= rcl_get_include_template('pfm-topic.php',$theme['path']);
        
    }
    
    $content .= rcl_get_include_template('pfm-footer.php',$theme['path']);
    
    $content .= '</div>';
    
    if(pfm_is_topic() 
        && $PrimeUser->user_id 
        && pfm_is_can('post_create') 
        && $PrimeUser->is_can_posts($PrimeQuery->object->topic_id)){

        $args = array(
            'topic_id' => $PrimeQuery->object->topic_id,
            'forum_id' => $PrimeQuery->object->forum_id,
            'group_id' => $PrimeQuery->object->group_id,
            'start_beat' => current_time('mysql')
        );

        if($beatTime = pfm_get_option('beat-time', 30))
            $content .= '<script>rcl_add_beat("pfm_topic_beat",'.$beatTime.','.json_encode($args).');</script>';
        
    }
    
    return $content;
}

function pfm_class_forum(){
    global $user_ID,$PrimeQuery;
    
    $classes = array();
    
    if(pfm_is_search()){
        
        $classes[] = 'prime-search-page';
        
    }else if(pfm_is_home()){
        
        $classes[] = 'prime-home-page';
        
    }else if(pfm_is_group()){
        
        $classes[] = 'prime-group-page prime-group-'.$PrimeQuery->object->group_id;
        
    }else if(pfm_is_forum()){
        
        $classes[] = 'prime-forum-page prime-forum-'.$PrimeQuery->object->forum_id;
        
        if($PrimeQuery->object->forum_closed){
            $classes[] = 'forum-closed';
        }else{
            $classes[] = 'forum-opened';
        }
        
    }else if(pfm_is_topic()){
        
        $classes[] = 'prime-topic-page prime-topic-'.$PrimeQuery->object->topic_id;
        
        if($PrimeQuery->object->topic_closed){
            $classes[] = 'topic-closed';
        }else{
            $classes[] = 'topic-opened';
        }
        
        if($PrimeQuery->object->topic_fix){
            $classes[] = 'topic-fixed';
        }
        
    }
    
    if($user_ID){

        $classes[] = 'prime-user-view';
        
    }else{
        
        $classes[] = 'prime-guest-view';
        
    }
    
    $classes = apply_filters('pfm_classes', $classes);
    
    return implode(' ',$classes);
} 

function pfm_the_template($name){
    
    $theme = pfm_get_current_theme();
    
    rcl_include_template($name.'.php',$theme['path']);
}

function pfm_the_notices(){
    global $PrimeQuery;
    
    if($PrimeQuery->errors){
        
        foreach($PrimeQuery->errors as $type => $notices){
            foreach($notices as $notice){
                echo pfm_get_notice($notice, $type);
            }
        }
        
    }else{
        
        if($PrimeQuery->is_search){
            
            echo pfm_get_notice(__('Nothing found','wp-recall'));
            
        }else if($PrimeQuery->is_frontpage){
            
            echo pfm_get_notice(__('Groups were not found','wp-recall'));

        }else if($PrimeQuery->is_group){
            
            if(!$PrimeQuery->object){
                echo pfm_get_notice(__('Group not found','wp-recall'));
                return;
            }
            
            echo pfm_get_notice(__('Forums were not found','wp-recall'));
            
        }else if($PrimeQuery->is_forum){
            
            if(!$PrimeQuery->object){
                echo pfm_get_notice(__('Forum not found','wp-recall'));
                return;
            }
            
            echo pfm_get_notice(__('No topics were created yet','wp-recall'));
            
        }else if($PrimeQuery->is_topic){
            
            if(!$PrimeQuery->object){
                echo pfm_get_notice(__('Topic not found','wp-recall'));
                return;
            }
            
            echo pfm_get_notice(__('The topic does not contain any messages','wp-recall'));
            
        }
        
    }
    
    do_action('pfm_the_notices');
    
}

function pfm_get_notice($notice, $type = 'notice'){
    
    $content = '<div class="prime-notice">';    
        $content .= '<div class="notice-box type-'.$type.'">';
            $content .= $notice;
        $content .= '</div>';
    $content .= '</div>';
    
    return $content;
    
}

function pfm_the_visitors(){
    global $PrimeQuery;
    
    $visitors = pfm_get_visitors();

    $visits = array();
    
    if($visitors){
        foreach($visitors as $visitor){
            $visits[] = '<a href="'.get_author_posts_url($visitor->user_id).'">'.$visitor->display_name.'</a>';
        }
    }
    
    $content = '<div class="prime-visitors">';
    
        if($PrimeQuery->is_group){
            $content .= __('The group is viewed by','wp-recall');
        }else if($PrimeQuery->is_forum){
            $content .= __('The forum is viewed by','wp-recall');
        }else if($PrimeQuery->is_topic){
            $content .= __('The topic is viewed by','wp-recall');
        }else{
            $content .= __('Currently on the forum','wp-recall');
        }
        
        $content .= ': ';

        $content .= '<span class="visitors-list">';
        
        if($visits)
            $content .= implode(', ',$visits);
        else
            $content .= __('Nobody is here','wp-recall');
        
        $content .= '</span>';

    $content .= '</div>';
    
    echo $content;
    
}

function pfm_the_search_form(){ 
    global $PrimeQuery; ?>

    <form action="<?php echo pfm_get_home_url() ?>">
        <input name="fs" value="<?php echo ($PrimeQuery->vars['pfm-search'])? $PrimeQuery->vars['pfm-search']: 'Поиск по форуму'; ?>" onblur="if (this.value == '') {this.value = 'Поиск по форуму';}" onfocus="if (this.value == 'Поиск по форуму') {this.value = '';}" type="text">
        <?php if(pfm_is_search()): ?>
        
            <?php if($PrimeQuery->vars['pfm-group']): ?>
            
                <input type="hidden" name="pfm-group" value="<?php echo $PrimeQuery->vars['pfm-group']; ?>">

             <?php elseif($PrimeQuery->vars['pfm-forum']): ?>

                <input type="hidden" name="pfm-forum" value="<?php echo $PrimeQuery->vars['pfm-forum']; ?>">

             <?php endif; ?>
        
        <?php else: ?>
        
            <?php if($PrimeQuery->is_group && $PrimeQuery->object->group_id){ ?>
                
                <input type="hidden" name="pfm-group" value="<?php echo $PrimeQuery->object->group_id; ?>">
            
            <?php }else if($PrimeQuery->is_forum || $PrimeQuery->is_topic && $PrimeQuery->object->forum_id){ ?>
            
                <input type="hidden" name="pfm-forum" value="<?php echo $PrimeQuery->object->forum_id; ?>">
            
            <?php } ?>
        
        <?php endif; ?>
        <button id="search-image" class="prime-search-button" type="submit" value="">
            <i class="fa fa-search" aria-hidden="true"></i>
        </button>
    </form>

<?php }

function pfm_the_breadcrumbs(){
    global $PrimeQuery;
    
    $object = $PrimeQuery->object; ?>

    <div class="prime-breadcrumbs" typeof="BreadcrumbList" vocab="https://schema.org/">
        
        <?php if(pfm_is_home()): ?>
        
            <span property="itemListElement" typeof="ListItem">
                <span property="position" content="1"></span>
                <span property="name">Главная</span>
            </span>
            
        <?php else: ?>

            <?php $homeUrl = pfm_get_home_url(); ?>

            <span property="itemListElement" typeof="ListItem">
                <span property="position" content="1"></span>
                <a href="<?php echo $homeUrl; ?>" property="item" typeof="WebPage">
                    <span property="name">Главная</span>
                </a>
            </span>

            <?php if(pfm_is_search()): ?>
            
                <?php if($PrimeQuery->vars['pfm-group']): ?>
            
                    <span property="itemListElement" typeof="ListItem">
                        <span property="position" content="2"></span>
                        <a href="<?php echo pfm_get_group_permalink($PrimeQuery->vars['pfm-group']); ?>" property="item" typeof="WebPage">
                            <span property="name">
                                <?php  echo pfm_get_group_field($PrimeQuery->vars['pfm-group'],'group_name'); ?>
                            </span>
                        </a>
                    </span>
            
                 <?php elseif($PrimeQuery->vars['pfm-forum']): ?>
            
                    <span property="itemListElement" typeof="ListItem">
                        <span property="position" content="2"></span>
                        <a href="<?php echo pfm_get_forum_permalink($PrimeQuery->vars['pfm-forum']); ?>" property="item" typeof="WebPage">
                            <span property="name">
                                <?php  echo pfm_get_forum_field($PrimeQuery->vars['pfm-forum'],'forum_name'); ?>
                            </span>
                        </a>
                    </span>
            
                 <?php endif; ?>

                <span>
                    Поиск: <?php echo $PrimeQuery->vars['pfm-search'] ?>
                </span>

            <?php else: ?>
                
                <?php if($object && $object->group_id): ?>

                <?php if(pfm_is_group()): ?>

                    <span property="itemListElement" typeof="ListItem">
                        <span property="position" content="2"></span>
                        <span property="name">
                            <?php echo $object->group_name; ?>
                        </span>
                    </span>

                <?php else: ?>

                    <span property="itemListElement" typeof="ListItem">
                        <span property="position" content="2"></span>
                        <a href="<?php echo pfm_get_group_permalink($object->group_id); ?>" property="item" typeof="WebPage">
                            <span property="name">
                                <?php echo $object->group_name; ?>
                            </span>
                        </a>
                    </span>

                    <?php if(pfm_is_forum()): ?>
                    
                        <?php if($object->parent_id): ?>
                    
                            <span property="itemListElement" typeof="ListItem">
                                <span property="position" content="3"></span>
                                <a href="<?php echo pfm_get_forum_permalink($object->parent_id); ?>" property="item" typeof="WebPage">
                                    <span property="name">
                                        <?php echo pfm_get_forum_field($object->parent_id,'forum_name'); ?>
                                    </span>
                                </a>
                            </span>
                    
                        <?php endif; ?>

                        <span property="itemListElement" typeof="ListItem">
                            <span property="position" content="3"></span>
                            <span property="name">
                                <?php echo $object->forum_name; ?>
                            </span>
                        </span>

                    <?php else: ?>
        
                        <?php if($object->parent_id): ?>
                    
                            <span property="itemListElement" typeof="ListItem">
                                <span property="position" content="3"></span>
                                <a href="<?php echo pfm_get_forum_permalink($object->parent_id); ?>" property="item" typeof="WebPage">
                                    <span property="name">
                                        <?php echo pfm_get_forum_field($object->parent_id,'forum_name'); ?>
                                    </span>
                                </a>
                            </span>
                    
                        <?php endif; ?>

                        <span property="itemListElement" typeof="ListItem">
                            <span property="position" content="3"></span>
                            <a href="<?php echo pfm_get_forum_permalink($object->forum_id); ?>" property="item" typeof="WebPage">
                                <span property="name">
                                    <?php echo $object->forum_name; ?>
                                </span>
                            </a>
                        </span>

                        <?php if(pfm_is_topic()): ?>

                            <span property="itemListElement" typeof="ListItem">
                                <span property="position" content="4"></span>
                                <span property="name">
                                    <?php echo $object->topic_name; ?>
                                </span>
                            </span>

                        <?php else: ?>

                            <span property="itemListElement" typeof="ListItem">
                                <span property="position" content="4"></span>
                                <a href="<?php echo pfm_get_topic_permalink($object->topic_id); ?>" property="item" typeof="WebPage">
                                    <span property="name">
                                        <?php echo $object->topic_name; ?>
                                    </span>
                                </a>
                            </span>

                        <?php endif; ?>

                    <?php endif; ?>

                <?php endif; ?>
                            
                <?php endif; ?>
                        
            <?php endif; ?>
            
        <?php endif; ?>

    </div>

    <?php
}

function pfm_get_icon($icon_class = 'fa-folder'){
    global $PrimeGroup,$PrimeForum,$PrimeTopic;
    
    $defaultIcon = '<i class="fa '.$icon_class.'" aria-hidden="true"></i>';
    
    if($PrimeGroup && !$PrimeForum){
        return apply_filters('pfm_group_icon', $defaultIcon);
    }
    
    if($PrimeGroup && $PrimeForum || $PrimeForum->parent_id){
        return apply_filters('pfm_forum_icon', $defaultIcon);
    }
    
    if($PrimeForum && $PrimeTopic){
        return apply_filters('pfm_topic_icon', $defaultIcon);
    }
    
    return apply_filters('pfm_icon', $defaultIcon);
    
}

function pfm_the_icon($icon_class = 'fa-folder'){
    echo pfm_get_icon($icon_class);
}

function pfm_page_navi($args = array()){
    
    $Navi = new PrimePageNavi($args);
        
    echo $Navi->pagenavi();
 
}

