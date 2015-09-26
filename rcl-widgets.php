<?php
add_filter('widget_text', 'do_shortcode');

add_action( 'widgets_init', 'widget_new_author' );
function widget_new_author() {
	register_widget( 'Widget_new_author' );
}

class Widget_new_author extends WP_Widget {

	function Widget_new_author() {
		$widget_ops = array( 'classname' => 'widget-new-author', 'description' => __('New users on the website','rcl') );
		$control_ops = array( 'width' => 300, 'height' => 350, 'id_base' => 'widget-new-author' );
		parent::__construct( 'widget-new-author', __('New users','rcl'), $widget_ops, $control_ops );
	}

	function widget( $args, $instance ) {
		extract( $args );

		$title = apply_filters('widget_title', $instance['title'] );
		$count_user = $instance['count_user'];
		$all = $instance['page_all_users'];

		if ( !$count_user ) $count_user = 12;

		echo $before_widget;

		if ( $title ) echo $before_title . $title . $after_title;

		echo do_shortcode('[userlist type="mini" filter="0"  widget="1" limit="'.$count_user.'" search="no"]');
		if($all) echo '<p class="clear alignright"><a href="'.get_permalink($all).'">'.__('All users','rcl').'</a></p>';
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
		$defaults = array( 'title' => __('New users','rcl'), 'count_user' => '12');
		$instance = wp_parse_args( (array) $instance, $defaults ); ?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e('Title','rcl'); ?>:</label>
			<input id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo $instance['title']; ?>" style="width:100%;" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e('The number of displayed users','rcl'); ?>:</label>
			<input id="<?php echo $this->get_field_id( 'count_user' ); ?>" name="<?php echo $this->get_field_name( 'count_user' ); ?>" value="<?php echo $instance['count_user']; ?>" style="width:100%;" />
		</p>
		<?php
			$args = array(
				'selected'   => $instance['page_all_users'],
				'name'       => $this->get_field_name( 'page_all_users' ),
				'show_option_none' => __('Not selected','rcl'),
				'echo'       => 0
			);
		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'page_all_users' ); ?>"><?php _e('Page all users','rcl'); ?>:</label>
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

	function Widget_online_users() {
		$widget_ops = array( 'classname' => 'widget_online_users', 'description' => __('Conclusion the users in the network','rcl') );
		$control_ops = array( 'width' => 300, 'height' => 350, 'id_base' => 'online-users-widget' );
		parent::__construct( 'online-users-widget', __('Users on the network','rcl'), $widget_ops, $control_ops );
	}

	function widget( $args, $instance ) {
		extract( $args );

		$title = apply_filters('widget_title', $instance['title'] );
		$all = $instance['page_all_users'];

		echo $before_widget;

		if ( $title ) echo $before_title . $title . $after_title;

		echo do_shortcode('[userlist type="mini" limit="10" onlyaction="1" widget="1" orderby="action" search="no"]');
		if($all) echo '<p class="clear alignright"><a href="'.get_permalink($all).'">'.__('All users','rcl').'</a></p>';
		echo $after_widget;
	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags( $new_instance['title'] );
		$instance['page_all_users'] = $new_instance['page_all_users'];
		return $instance;
	}

	function form( $instance ) {
		$defaults = array( 'title' => __('Right now','rcl'));
		$instance = wp_parse_args( (array) $instance, $defaults ); ?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e('Title','rcl'); ?>:</label>
			<input id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo $instance['title']; ?>" style="width:100%;" />
		</p>
		<?php
			$args = array(
				'selected'   => $instance['page_all_users'],
				'name'       => $this->get_field_name( 'page_all_users' ),
				'show_option_none' => __('Not selected','rcl'),
				'echo'       => 0
			);
		?>
		<p>
			<label for="<?php echo $instance['page_all_users']; ?>"><?php _e('Page all users','rcl'); ?>:</label>
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

	function Widget_author_profil() {
		$widget_ops = array( 'classname' => 'widget_author_profil', 'description' => __('The block with the main profile information','rcl') );
		$control_ops = array( 'width' => 300, 'height' => 350, 'id_base' => 'widget-author-profil' );
		parent::__construct( 'widget-author-profil', __('Control panel','rcl'), $widget_ops, $control_ops );
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
		$defaults = array( 'title' => __('Control panel','rcl'));
		$instance = wp_parse_args( (array) $instance, $defaults ); ?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e('Title','rcl'); ?>:</label>
			<input id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo $instance['title']; ?>" style="width:100%;" />
		</p>
	<?php
	}
}