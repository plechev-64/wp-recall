<?php

class Rcl_Pager {

	public $current		 = 1; //текущая страница
	public $pages		 = 0; //кол-во страниц
	public $diff		 = array( 4, 4 ); //диапазон вывода отображаемых страниц
	public $number		 = 30; //кол-во элементов на странице
	public $total		 = 0; //общее кол-во элементов
	public $id; //идентификатор навигации
	public $offset		 = 0; //отступ выборки элементов
	public $key			 = 'pagenum';
	public $onclick		 = false;
	public $page_args	 = array(
		'type' => 'simple'
	);

	function __construct( $args ) {

		$this->init_properties( $args );

		$this->set_current();

		$this->offset	 = ($this->current - 1) * $this->number;
		$this->pages	 = ceil( $this->total / $this->number );
	}

	function init_properties( $args ) {

		$properties = get_class_vars( get_class( $this ) );

		foreach ( $properties as $name => $val ) {
			if ( isset( $args[$name] ) & ! empty( $args[$name] ) )
				$this->$name = $args[$name];
		}
	}

	function set_current() {

		if ( isset( $_REQUEST[$this->key] ) && $_REQUEST[$this->key] )
			$this->current = $_REQUEST[$this->key];

		if ( $this->current == 0 )
			$this->current = 1;
	}

	function get_walker() {
		$walker = array();

		$walker['args']['number_left']	 = (($this->current - $this->diff[0]) <= 0) ? $this->current - 1 : $this->diff[0];
		$walker['args']['number_right']	 = (($this->current + $this->diff[1]) > $this->pages) ? $this->pages - $this->current : $this->diff[1];

		if ( $walker['args']['number_left'] ) {

			$start = $this->current - $walker['args']['number_left'];

			if ( $start > 1 ) {
				$walker['output'][]['page'] = 1;
			}

			if ( $start > 2 ) {
				$walker['output'][]['separator'] = '...';
			}


			for ( $num = $walker['args']['number_left']; $num > 0; $num -- ) {
				$walker['output'][]['page'] = $this->current - $num;
			}
		}

		$walker['output'][]['current'] = $this->current;

		if ( $walker['args']['number_right'] ) {
			for ( $num = 1; $num <= $walker['args']['number_right']; $num ++ ) {
				$walker['output'][]['page'] = $this->current + $num;
			}
		}

		$end = $this->pages - ($this->current + $walker['args']['number_right']);

		if ( $end > 1 )
			$walker['output'][]['separator'] = '...';

		if ( $end > 0 )
			$walker['output'][]['page'] = $this->pages;

		return $walker;
	}

	function get_url( $page_id ) {
		return add_query_arg( array( $this->key => $page_id ), (isset( $_POST['tab_url'] ) ? $_POST['tab_url'] : false ) );
	}

	function get_page_args( $page_id, $label = false ) {

		$args = array(
			'href'	 => $this->get_url( $page_id ),
			'label'	 => $label ? $label : $page_id,
			'data'	 => array(
				'page' => $page_id
			)
		);

		if ( $this->onclick ) {
			$args['onclick'] = 'return ' . $this->onclick . '(' . $page_id . ', this);';
		}

		return wp_parse_args( $args, $this->page_args );
	}

	function get_navi() {
		return $this->get_pager();
	}

	function get_pager( $typePager = 'numbers' ) {

		if ( ! $this->total || $this->pages == 1 )
			return false;

		$walker = $this->get_walker();

		$content = '<div class="rcl-pager">';

		$content .= '<div class="rcl-page-navi">';

		foreach ( $walker['output'] as $item ) {

			foreach ( $item as $type => $data ) {

				if ( $typePager == 'numbers' ) {

					if ( $type == 'page' ) {

						$html = rcl_get_button( $this->get_page_args( $data ) );
					} else if ( $type == 'current' ) {

						$html = '<span data-page="' . $data . '">' . $data . '</span>';
					} else {

						$html = '<span>' . $data . '</span>';
					}
				} else {

					if ( $type == 'page' ) {

						if ( $this->current + 1 == $data )
							$label	 = __( 'Вперед', 'wp-recall' );
						else if ( $this->current - 1 == $data )
							$label	 = __( 'Назад', 'wp-recall' );
						else
							continue;

						$html = rcl_get_button( $this->get_page_args( $data, $label ) );
					}else {
						continue;
					}
				}

				$content .= '<span class="pager-item type-' . $type . '">' . $html . '</span>';
			}
		}

		$content .= '</div>';

		$content .= '</div>';

		return $content;
	}

}
