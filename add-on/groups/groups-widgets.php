<?php
include_once 'classes/rcl-group-widget.php';

add_action('init','rcl_group_add_primary_widget');
function rcl_group_add_primary_widget(){
    rcl_group_register_widget('Group_Primary_Widget');
}

class Group_Primary_Widget extends Rcl_Group_Widget {

    function Group_Primary_Widget() {
        parent::__construct( array(
            'widget_id'=>'group-primary-widget',
            'widget_place'=>'sidebar',
            'widget_title'=>__('Control panel','wp-recall')
            )
        );
    }

    function options($instance){

        $defaults = array('title' => __('Control panel','wp-recall'));
        $instance = wp_parse_args( (array) $instance, $defaults );

        echo '<label>'.__('Title','wp-recall').'</label>'
                . '<input type="text" name="'.$this->field_name('title').'" value="'.$instance['title'].'">';

    }

    function widget($args) {
        extract( $args );

        global $rcl_group,$user_ID;

        if(!$user_ID||rcl_is_group_can('admin')) return false;

        if($rcl_group->current_user=='banned') return false;

        if(rcl_is_group_can('reader')){

            echo $before;

                echo '<form method="post">'
                   . '<input type="submit" class="recall-button" name="group-submit" value="'.__('Leave group','wp-recall').'">'
                    . '<input type="hidden" name="group-action" value="leave">'
                    . wp_nonce_field( 'group-action-' . $user_ID,'_wpnonce',true,false )
               . '</form>';

            echo $after;

        }else{

            if(rcl_get_group_option($rcl_group->term_id,'can_register')){

                echo $before;

                if($rcl_group->group_status=='open'){
                    echo '<form method="post">'
                        . '<input type="submit" class="recall-button" name="group-submit" value="'.__('Join group','wp-recall').'">'
                        . '<input type="hidden" name="group-action" value="join">'
                        . wp_nonce_field( 'group-action-' . $user_ID,'_wpnonce',true,false )
                    . '</form>';
                }

                if($rcl_group->group_status=='closed'){

                    $requests = rcl_get_group_option($rcl_group->term_id,'requests_group_access');

                    if($requests&&false!==array_search($user_ID, $requests)){

                        echo '<h3 class="title-widget">'.__('The request for access sent','wp-recall').'</h3>';

                    }else{

                        echo '<form method="post">'
                            . '<input type="submit" class="recall-button" name="group-submit" value="'.__('Apply for membership','wp-recall').'">'
                            . '<input type="hidden" name="group-action" value="ask">'
                            . wp_nonce_field( 'group-action-' . $user_ID,'_wpnonce',true,false )
                        . '</form>';

                    }
                }

                echo $after;

            }
        }


    }

}

add_action('init','rcl_group_add_users_widget');
function rcl_group_add_users_widget(){
    rcl_group_register_widget('Group_Users_Widget');
}

class Group_Users_Widget extends Rcl_Group_Widget {

    function Group_Users_Widget() {
        parent::__construct( array(
            'widget_id'=>'group-users-widget',
            'widget_place'=>'sidebar',
            'widget_title'=>__('Users','wp-recall')
            )
        );
    }

    function widget($args,$instance) {

        global $rcl_group,$user_ID;

        extract( $args );

        $user_count = (isset($instance['count']))? $instance['count']: 12;
        $template = (isset($instance['template']))? $instance['template']: 'mini';

        echo $before;
        echo rcl_group_users($user_count,$template);
        echo rcl_get_group_link('rcl_get_group_users',__('All users','wp-recall'));

        echo $after;
    }

    function options($instance){

        $defaults = array('title' => __('Users','wp-recall'),'count' => 12,'template' => 'mini');
        $instance = wp_parse_args( (array) $instance, $defaults );

        echo '<label>'.__('Title','wp-recall').'</label>'
                . '<input type="text" name="'.$this->field_name('title').'" value="'.$instance['title'].'">';
        echo '<label>'.__('Amount','wp-recall').'</label>'
                . '<input type="number" name="'.$this->field_name('count').'" value="'.$instance['count'].'">';
        echo '<label>'.__('Template','wp-recall').'</label>'
                . '<select name="'.$this->field_name('template').'">'
                . '<option value="mini" '.selected('mini',$instance['template'],false).'>Mini</option>'
                . '<option value="avatars" '.selected('avatars',$instance['template'],false).'>Avatars</option>'
                . '<option value="rows" '.selected('rows',$instance['template'],false).'>Rows</option>'
                . '</select>';
    }

}

add_action('init','rcl_group_add_publicform_widget');
function rcl_group_add_publicform_widget(){
    rcl_group_register_widget('Group_PublicForm_Widget');
}

class Group_PublicForm_Widget extends Rcl_Group_Widget {

    function Group_PublicForm_Widget() {
        parent::__construct( array(
            'widget_id'=>'group-public-form-widget',
            'widget_title'=>__('Form of the publication','wp-recall'),
            'widget_place'=>'content',
            'widget_type'=>'hidden'
            )
        );
    }

    function widget($args,$instance) {

       if(!rcl_is_group_can('author')) return false;

        extract( $args );

        global $rcl_group;

        $typeform = (isset($instance['type_form']))? $instance['type_form']: 0;

        echo $before;

        echo do_shortcode('[public-form post_type="post-group" type_editor="'.$typeform.'" group_id="'.$rcl_group->term_id.'"]');

        echo $after;
    }

    function options($instance){

        $defaults = array('title' => __('Form of the publication','wp-recall'), 'type_form' => 0);
        $instance = wp_parse_args( (array) $instance, $defaults );

        echo '<label>'.__('Title','wp-recall').'</label>'
                . '<input type="text" name="'.$this->field_name('title').'" value="'.$instance['title'].'">';
        echo '<label>'.__('Type form','wp-recall').'</label>'
                . '<select name="'.$this->field_name('type_form').'">'
                . '<option value="0" '.selected(0,$instance['type_form'],false).'>WP-Recall</option>'
                . '<option value="1" '.selected(1,$instance['type_form'],false).'>WordPress</option>'
                . '</select>';

    }

}

add_action('init','rcl_group_add_categorylist_widget');
function rcl_group_add_categorylist_widget(){
    rcl_group_register_widget('Group_CategoryList_Widget');
}

class Group_CategoryList_Widget extends Rcl_Group_Widget {

    function Group_CategoryList_Widget() {
        parent::__construct( array(
            'widget_id'=>'group-category-list-widget',
            'widget_title'=>__('Categories Content Group','wp-recall'),
            'widget_place'=>'unuses'
            )
        );
    }

    function options($instance){

        $defaults = array('title' => __('Categories Content Group','wp-recall'));
        $instance = wp_parse_args( (array) $instance, $defaults );

        echo '<label>'.__('Title','wp-recall').'</label>'
                . '<input type="text" name="'.$this->field_name('title').'" value="'.$instance['title'].'">';

    }

    function widget($args) {

        extract( $args );

        global $rcl_group;

        $category = rcl_get_group_category_list();
        if(!$category) return false;

        echo $before;
        echo $category;
        echo $after;

    }

}

add_action('init','rcl_group_add_admins_widget');
function rcl_group_add_admins_widget(){
    rcl_group_register_widget('Group_Admins_Widget');
}

class Group_Admins_Widget extends Rcl_Group_Widget {

    function Group_Admins_Widget() {
        parent::__construct( array(
            'widget_id'=>'group-admins-widget',
            'widget_place'=>'sidebar',
            'widget_title'=>__('Management','wp-recall')
            )
        );
    }

    function widget($args,$instance) {

        global $rcl_group,$user_ID;

        extract( $args );

        $user_count = (isset($instance['count']))? $instance['count']: 12;
        $template = (isset($instance['template']))? $instance['template']: 'mini';

        echo $before;
        echo $this->get_group_administrators($user_count,$template);
        echo $after;
    }

    function add_admins_query($query){
        global $rcl_group;
        $query['join'][] = "LEFT JOIN ".RCL_PREF."groups_users AS groups_users ON users.ID=groups_users.user_id";
        $query['where'][] = "(groups_users.user_role IN ('admin','moderator') AND groups_users.group_id='$rcl_group->term_id') OR (users.ID='$rcl_group->admin_id')";
        $query['group'] = "users.ID";

        return $query;
    }

    function get_group_administrators($number,$template='mini'){
        global $rcl_group;
        if(!$rcl_group) return false;

        switch($template){
           case 'rows': $data = 'descriptions,rating_total,posts_count,comments_count,user_registered'; break;
           case 'avatars': $data = 'rating_total'; break;
           default: $data = '';
        }

        add_filter('rcl_users_query',array($this,'add_admins_query'));
        return rcl_get_userlist(array('number'=>$number,'template'=>$template,'data'=>$data));
    }

    function options($instance){

        $defaults = array('title' => __('Management','wp-recall'),'count' => 12,'template' => 'mini');
        $instance = wp_parse_args( (array) $instance, $defaults );

        echo '<label>'.__('Title','wp-recall').'</label>'
                . '<input type="text" name="'.$this->field_name('title').'" value="'.$instance['title'].'">';
        echo '<label>'.__('Template','wp-recall').'</label>'
                . '<select name="'.$this->field_name('template').'">'
                . '<option value="mini" '.selected('mini',$instance['template'],false).'>Mini</option>'
                . '<option value="avatars" '.selected('avatars',$instance['template'],false).'>Avatars</option>'
                . '<option value="rows" '.selected('rows',$instance['template'],false).'>Rows</option>'
                . '</select>';

    }

}

add_action('init','rcl_group_add_posts_widget');
function rcl_group_add_posts_widget(){
    rcl_group_register_widget('Group_Posts_Widget');
}

class Group_Posts_Widget extends Rcl_Group_Widget {

    function Group_Posts_Widget() {
        parent::__construct( array(
            'widget_id'=>'group-posts-widget',
            'widget_place'=>'unuses',
            'widget_title'=>__('Group posts','wp-recall')
            )
        );
    }

    function widget($args,$instance) {

        global $rcl_group,$user_ID;

        extract( $args );

        $defaults = array(
            'title' => __('Group posts','wp-recall'),
            'count' => 12,
            'excerpt' => 1,
            'thumbnail' => 1
        );

        $instance = wp_parse_args( (array) $instance, $defaults );

        echo $before;

        if(have_posts()){ ?>

            <?php while ( have_posts() ): the_post(); ?>
                <div class="post-group">
                    <h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a> <span class="post-date"><i class="fa fa-clock-o"></i><?php the_date(); ?></span></h3>
                    <?php if($instance['thumbnail']&&has_post_thumbnail()){ ?>
                        <div class="post-group-thumb"><?php the_post_thumbnail('thumbnail'); ?></div>
                    <?php } ?>
                    <?php if($instance['excerpt']){ ?>
                    <div class="post-group-content">

                        <?php the_excerpt(); ?>
                    </div>
                    <?php } ?>
                </div>
            <?php endwhile; ?>

            <nav class="pagination group">
                <?php if ( function_exists('wp_pagenavi') ): ?>
                    <?php wp_pagenavi(); ?>
                <?php else: ?>
                    <ul class="group">
                        <li class="prev left"><?php previous_posts_link(); ?></li>
                        <li class="next right"><?php next_posts_link(); ?></li>
                    </ul>
                <?php endif; ?>
            </nav>

        <?php }else{ ?>
            <p><?php _e("Publications don't have","rcl"); ?></p>
        <?php }

        echo $after;
    }

    function options($instance){

        $defaults = array(
            'title' => __('Group posts','wp-recall'),
            'count' => 12,
            'excerpt' => 1,
            'thumbnail' => 1
        );
        $instance = wp_parse_args( (array) $instance, $defaults );

        echo '<label>'.__('Title','wp-recall').'</label>'
                . '<input type="text" name="'.$this->field_name('title').'" value="'.$instance['title'].'">';
        echo '<label>'.__('The excerpt','wp-recall').'</label>'
                . '<select name="'.$this->field_name('excerpt').'">'
                . '<option value="0" '.selected(0,$instance['excerpt'],false).'>'.__('Do not display','wp-recall').'</option>'
                . '<option value="1" '.selected(1,$instance['excerpt'],false).'>'.__('Display','wp-recall').'</option>'
                . '</select>';
        echo '<label>'.__('Thumbnail','wp-recall').'</label>'
                . '<select name="'.$this->field_name('thumbnail').'">'
                . '<option value="0" '.selected(0,$instance['thumbnail'],false).'>'.__('Do not display','wp-recall').'</option>'
                . '<option value="1" '.selected(1,$instance['thumbnail'],false).'>'.__('Display','wp-recall').'</option>'
                . '</select>';

    }

}

