<?php
add_filter('widget_text', 'do_shortcode');

add_action( 'widgets_init', 'widget_new_author' );
function widget_new_author() {
	register_widget( 'Widget_new_author' );
}

class Widget_new_author extends WP_Widget {

    function __construct() {
        $widget_ops = array( 'classname' => 'rcl-new-users', 'description' => __('New users on website','wp-recall') );
        $control_ops = array( 'width' => 300, 'height' => 350, 'id_base' => 'rcl-new-users' );
        parent::__construct( 'rcl-new-users', 'RCL: '.__('New users','wp-recall'), $widget_ops, $control_ops );
    }

    function widget( $args, $instance ) {
        extract( $args );

        $title = apply_filters('widget_title', $instance['title'] );
        $count_user = $instance['count_user'];
        $all = $instance['page_all_users'];

        if ( !$count_user ) $count_user = 12;

        echo $before_widget;

        if ( $title ) echo $before_title . $title . $after_title;

        echo rcl_get_userlist(array(
                'template' => 'mini', 
                'number'=>$count_user, 
                'filters'=>false, 
                'id'=>'rcl-new-users',
                'orderby' => 'user_registered'
            ));

        if($all) echo '<p class="clear alignright"><a href="'.get_permalink($all).'">'.__('All users','wp-recall').'</a></p>';
        echo $after_widget;
    }

    function update( $new_instance, $old_instance ) {
        $instance = $old_instance;
        $instance['title'] = strip_tags( $new_instance['title'] );
        $instance['count_user'] = $new_instance['count_user'];
        $instance['page_all_users'] = $new_instance['page_all_users'];
        return $instance;
    }

    function form( $instance ) {
        $defaults = array( 'title' => __('New users','wp-recall'), 'count_user' => '12');
        $instance = wp_parse_args( (array) $instance, $defaults ); ?>
        <p>
                <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e('Title','wp-recall'); ?>:</label>
                <input id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo $instance['title']; ?>" style="width:100%;" />
        </p>
        <p>
                <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e('Number of displayed users','wp-recall'); ?>:</label>
                <input id="<?php echo $this->get_field_id( 'count_user' ); ?>" name="<?php echo $this->get_field_name( 'count_user' ); ?>" value="<?php echo $instance['count_user']; ?>" style="width:100%;" />
        </p>
        <?php
                $args = array(
                        'selected'   => isset($instance['page_all_users'])? $instance['page_all_users']: 0,
                        'name'       => $this->get_field_name( 'page_all_users' ),
                        'show_option_none' => __('Not selected','wp-recall'),
                        'echo'       => 0
                );
        ?>
        <p>
                <label><?php _e('All users page','wp-recall'); ?>:</label>
                <?php echo wp_dropdown_pages( $args ); ?>
        </p>
    <?php
    }
}

add_action( 'widgets_init', 'widget_online_users' );
function widget_online_users() {
	register_widget( 'Widget_online_users' );
}

class Widget_online_users extends WP_Widget {

	function __construct() {
		$widget_ops = array( 'classname' => 'rcl-online-users', 'description' => __('Online users output','wp-recall') );
		$control_ops = array( 'width' => 300, 'height' => 350, 'id_base' => 'rcl-online-users' );
		parent::__construct( 'rcl-online-users', 'RCL: '.__('Online users','wp-recall'), $widget_ops, $control_ops );
	}

	function widget( $args, $instance ) {
		extract( $args );

		$title = apply_filters('widget_title', $instance['title'] );
		$all = $instance['page_all_users'];

		echo $before_widget;

		if ( $title ) echo $before_title . $title . $after_title;

                echo rcl_get_userlist(array('template' => 'mini', 'number'=>10, 'orderby'=>'time_action', 'only'=>'action_users', 'filters'=>false, 'id'=>'rcl-online-users' ));

		if($all) echo '<p class="clear alignright"><a href="'.get_permalink($all).'">'.__('All users','wp-recall').'</a></p>';
		echo $after_widget;
	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags( $new_instance['title'] );
		$instance['page_all_users'] = $new_instance['page_all_users'];
		return $instance;
	}

	function form( $instance ) {
		$defaults = array( 'title' => __('Online','wp-recall'));
		$instance = wp_parse_args( (array) $instance, $defaults ); ?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e('Title','wp-recall'); ?>:</label>
			<input id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo $instance['title']; ?>" style="width:100%;" />
		</p>
		<?php
			$args = array(
				'selected'   => isset($instance['page_all_users'])? $instance['page_all_users']: '',
				'name'       => $this->get_field_name( 'page_all_users' ),
				'show_option_none' => __('Not selected','wp-recall'),
				'echo'       => 0
			);
		?>
		<p>
			<label><?php _e('All users page','wp-recall'); ?>:</label>
			<?php echo wp_dropdown_pages( $args ); ?>
		</p>
	<?php
	}
}

add_action( 'widgets_init', 'widget_author_profil' );
function widget_author_profil() {
	register_widget( 'Widget_author_profil' );
}

class Widget_author_profil extends WP_Widget {

	function __construct() {
		$widget_ops = array( 'classname' => 'rcl-primary-panel', 'description' => __('Block with main profile information','wp-recall') );
		$control_ops = array( 'width' => 300, 'height' => 350, 'id_base' => 'rcl-primary-panel' );
		parent::__construct( 'rcl-primary-panel', 'RCL: '.__('Control panel','wp-recall'), $widget_ops, $control_ops );
	}

	function widget( $args, $instance ) {
		extract( $args );

		$title = apply_filters('widget_title', $instance['title'] );

		echo $before_widget;
		if ( $title ) echo $before_title . $title . $after_title;
		echo rcl_get_authorize_form();
		echo $after_widget;
	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags( $new_instance['title'] );
		return $instance;
	}

	function form( $instance ) {
		$defaults = array( 'title' => __('Control panel','wp-recall'));
		$instance = wp_parse_args( (array) $instance, $defaults ); ?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e('Title','wp-recall'); ?>:</label>
			<input id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo $instance['title']; ?>" style="width:100%;" />
		</p>
	<?php
	}
}