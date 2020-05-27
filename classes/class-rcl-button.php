<?php

/**
 * Description of class-rcl-button
 *
 * @author Андрей
 */
class Rcl_Button {

	public $id;
	public $onclick;
	public $href		 = 'javascript:void(0);';
	public $class		 = array();
	public $type		 = 'primary'; // clear, simple, primary
	public $style;
	public $icon; // for example: fa-car
	public $icon_align	 = 'left'; // left or right position
	public $icon_mask;  // 1 - is mask on icon
	public $label;   // text on button
	public $title;   // title attribute
	public $counter; // number
	public $content;
	public $avatar;  // avatar button
	public $avatar_circle; // round avatar
	public $data;
	public $submit;
	public $status;  // state of the button: loading, disabled, active
	public $size		 = 'standart';   // standart, medium, large, big
	public $attr;
	public $attrs;
	public $fullwidth;  // 1 - is fullwidth button
	public $inset;

	function __construct( $args ) {

		if ( ! isset( $args['title'] ) && isset( $args['label'] ) )
			$args['title'] = $args['label'];

		$this->init_properties( $args );

		$this->setup_class();
		$this->setup_attrs();
	}

	function init_properties( $args ) {

		$properties = get_class_vars( get_class( $this ) );

		foreach ( $properties as $name => $val ) {
			if ( isset( $args[$name] ) & ! empty( $args[$name] ) )
				$this->$name = $args[$name];
		}
	}

	function setup_attrs() {

		$this->attrs['href']	 = $this->href;
		$this->attrs['title']	 = $this->title;
		$this->attrs['onclick']	 = $this->onclick;
		$this->attrs['style']	 = $this->style;
		$this->attrs['id']		 = $this->id;
		$this->attrs['class']	 = is_array( $this->class ) ? implode( ' ', $this->class ) : $this->class;

		if ( $this->submit && ! $this->onclick ) {
			$this->attrs['onclick'] = 'rcl_submit_form(this);return false;';
		}

		if ( $this->data ) {

			foreach ( $this->data as $k => $value ) {
				if ( ! $value )
					continue;
				$this->attrs['data-' . $k] = $value;
			}
		}
	}

	function setup_class() {

		if ( $this->class && ! is_array( $this->class ) )
			$this->class	 = array( 'rcl-bttn', $this->class );
		else
			$this->class[]	 = 'rcl-bttn';

		if ( $this->icon ) {

			if ( $this->icon_align == 'right' && $this->label ) {

				if ( ! $this->counter ) {
					// кнопка из текста и только иконки справа
					$this->class[] = 'rcl-bttn__mod-text-rico';
				} else if ( $this->counter && ! $this->avatar ) {
					// текст иконка справа и счетчик
					$this->class[] = 'rcl-bttn__mod-text-rico-count';
				}
			} else if ( ! $this->counter && ! $this->avatar && ! $this->label ) {
				// только иконка
				$this->class[] = 'rcl-bttn__mod-only-icon';
			}

			if ( $this->icon_mask )
				$this->class[] = 'rcl-bttn__ico-mask';
		}

		$this->class[] = 'rcl-bttn__type-' . $this->type;

		if ( $this->size )
			$this->class[] = 'rcl-bttn__size-' . $this->size;

		if ( $this->status )
			$this->class[] = 'rcl-bttn__' . $this->status;

		if ( $this->fullwidth )
			$this->class[] = 'rcl-bttn__fullwidth';

		if ( $this->inset )
			$this->class[] = 'rcl-bttn__inset';

		if ( $this->avatar_circle )
			$this->class[] = 'rcl-bttn__ava_circle';
	}

	function parse_attrs() {

		$attrs = array();
		foreach ( $this->attrs as $name => $value ) {
			if ( ! $value )
				continue;
			$attrs[] = $name . '=\'' . $value . '\'';
		}

		if ( $this->attr ) //поддержка старого указания произвольных атрибутов
			$attrs[] = $this->attr;

		return implode( ' ', $attrs );
	}

	function get_icon() {
		return sprintf( '<i class="rcl-bttn__ico rcl-bttn__ico-%1$s rcli %2$s"></i>', $this->icon_align, $this->icon );
		//return sprintf('<svg class="rcl-bttn__ico rcl-bttn__ico-%1$s rcli %2$s"><use xlink:href="#%2$s"></use></svg>', $this->icon_align, $this->icon);
	}

	function get_avatar() {
		return sprintf( '<i class="rcl-bttn__ava">%s</i>', $this->avatar );
	}

	function get_label() {
		return sprintf( '<span class="rcl-bttn__text">%s</span>', $this->label );
	}

	function get_counter() {
		return sprintf( '<span class="rcl-bttn__count">%s</span>', $this->counter );
	}

	function get_custom_content() {
		return $this->content;
	}

	function get_button() {

		$content = sprintf( '<a %s>', $this->parse_attrs() );

		if ( $this->icon && $this->icon_align == 'left' )
			$content .= $this->get_icon();

		if ( $this->avatar )
			$content .= $this->get_avatar();

		if ( $this->label )
			$content .= $this->get_label();

		if ( $this->icon && $this->icon_align == 'right' )
			$content .= $this->get_icon();

		if ( $this->counter )
			$content .= $this->get_counter();

		if ( $this->content )
			$content .= $this->get_custom_content();

		$content .= '</a>';

		return $content;
	}

}
