<?php
add_action( 'widgets_init', 'rcl_widget_usercount' );
function rcl_widget_usercount() {
	register_widget( 'Rcl_Widget_user_count' );
}

class Rcl_Widget_user_count extends WP_Widget {
	function __construct() {
		$widget_ops	 = array( 'classname' => 'widget-user-count', 'description' => __( 'Personal user account', 'wp-recall' ) );
		$control_ops = array( 'width' => 300, 'height' => 350, 'id_base' => 'widget-user-count' );
		parent::__construct( 'widget-user-count', __( 'Personal account', 'wp-recall' ), $widget_ops, $control_ops );
	}

	function widget( $args, $instance ) {

		extract( $args );

		global $user_ID;

		if ( $user_ID ) {
			echo $before_widget;
			if ( $instance['title'] )
				echo $before_title . $instance['title'] . $after_title;
			echo rcl_get_html_usercount();
			echo $after_widget;
		}
	}

	//Update the widget
	function update( $new_instance, $old_instance ) {
		$instance			 = $old_instance;
		//Strip tags from title and name to remove HTML
		$instance['title']	 = strip_tags( $new_instance['title'] );
		return $instance;
	}

	function form( $instance ) {
		//Set up some default widget settings.
		$defaults	 = array( 'title' => __( 'Personal account', 'wp-recall' ) );
		$instance	 = wp_parse_args( ( array ) $instance, $defaults );
		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title', 'wp-recall' ); ?></label>
			<input id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo $instance['title']; ?>" style="width:100%;" />
		</p>
		<?php
	}

}
