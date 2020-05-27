<?php

class Rcl_Query extends Rcl_Old_Query {

	public $table;
	public $serialize	 = array();
	public $query		 = array();

	function __construct( $table ) {
		$this->table = $table;
		$this->reset_query();
	}

	function reset_query() {
		$this->query = array(
			'table'		 => $this->table, //deprecated
			'select'	 => array(),
			'where'		 => array(),
			'join'		 => array(),
			'number'	 => 30,
			'offset'	 => 0,
			'orderby'	 => false,
			'order'		 => 'DESC',
			'having'	 => array(),
			'groupby'	 => false
		);
	}

	function get_table_data( $dataName ) {
		return isset( $this->table[$dataName] ) ? $this->table[$dataName] : false;
	}

	function get_colname( $colname ) {
		return $this->table['as'] . '.' . $colname;
	}

	function parse( $args = false ) {

		if ( ! $args )
			return $this;

		$args = wp_unslash( $args );

		if ( ! isset( $args['select'] ) && ! isset( $args['date'] ) ) {
			foreach ( $this->table['cols'] as $col_name ) {
				$this->query['select'][] = $this->table['as'] . '.' . $col_name;
			}
		}

		foreach ( $args as $operator => $data ) {

			switch ( $operator ) {
				case 'select': $this->select( $data );
					continue;
				case 'where': $this->where( $data );
					continue;
				case 'date':
					foreach ( $data as $dateData ) {
						if ( ! isset( $dateData['colname'] ) || ! $dateData['colname'] )
							break;
						if ( ! isset( $dateData['compare'] ) || ! $dateData['compare'] )
							$dateData['compare'] = '=';
						$this->date( $dateData['colname'], $dateData['compare'], $dateData['data'] );
					}
					continue;
				case 'join':
					foreach ( $data as $joinData ) {
						$this->join( $joinData[0], $joinData[1] );
					}
					continue;
				case 'number': $this->number( $data );
					continue;
				case 'offset': $this->offset( $data );
					continue;
				case 'orderby': $this->orderby( $data );
					continue;
				case 'order': $this->order( $data );
					continue;
				case 'groupby': $this->groupby( $data );
					continue;
				case 'cache': $this->set_cache( $data );
					continue;
			}

			$this->where( array( $operator => $data ) );
		}

		return $this;
	}

	function set_cache( $cache ) {
		$this->cache = $cache;
	}

	function get_operator_data( $operator, $field_name = false, $cache = false ) {
		global $wpdb;

		$field_name = ($field_name) ? $field_name : $this->table['cols'][0];

		$query = $this->get_query();

		$sql = $this->get_sql( array(
			'select'	 => array( $operator . '(' . $this->table['as'] . '.' . $field_name . ')' ),
			'join'		 => $query['join'],
			'where'		 => $query['where'],
			'groupby'	 => $query['groupby']
			) );

		if ( $cache ) {
			$cachekey	 = md5( $sql );
			$cache		 = wp_cache_get( $cachekey );
			if ( $cache )
				return $cache;
		}

		if ( isset( $query['groupby'] ) && $query['groupby'] )
			$result	 = $wpdb->query( $sql );
		else
			$result	 = $wpdb->get_var( $sql );

		if ( $cache )
			wp_cache_add( $cachekey, $result );

		return $result;
	}

	function set_operator_query( $operator, $data ) {

		$opers = explode( ' ', $operator );

		$operator = isset( $opers[1] ) ? $opers : $operator;

		foreach ( $data as $as_value => $col_name ) {
			$this->query['select'][] = $this->get_operator_string( $operator, $as_value, $col_name );
		}
	}

	function get_operator_string( $operator, $as_value, $col_name ) {

		if ( is_array( $operator ) ) {

			switch ( $operator[1] ) {
				case 'DISTINCT': return $operator[0] . '( ' . $operator[1] . ' ' . $this->table['as'] . '.' . $col_name . ')' . ( is_string( $as_value ) ? ' AS ' . $as_value : '');
			}
		} else {
			return $operator . '(' . $this->table['as'] . '.' . $col_name . ')' . ( is_string( $as_value ) ? ' AS ' . $as_value : '');
		}
	}

	function distinct( $select ) {

		foreach ( $select as $as_value => $data ) {
			if ( in_array( $data, $this->table['cols'] ) ) {
				$this->query['select'][] = 'DISTINCT ' . $this->table['as'] . '.' . $data . ( is_string( $as_value ) ? ' AS ' . $as_value : '');
			} else if ( in_array( $as_value, array( 'count' ) ) ) {
				$this->set_operator_query( strtoupper( $as_value ) . ' DISTINCT', $data );
			}
		}

		return $this;
	}

	function select( $select ) {

		if ( ! $select )
			return $this;

		if ( ! is_array( $select ) ) {
			if ( $select ) {
				$this->query['select'][] = $this->table['as'] . '.*';
			}
		} else {

			foreach ( $select as $as_value => $data ) {
				if ( in_array( $data, $this->table['cols'] ) ) {
					$this->query['select'][] = $this->table['as'] . '.' . $data . ( is_string( $as_value ) ? ' AS ' . $as_value : '');
				} else if ( in_array( $as_value, array( 'count', 'max', 'min', 'sum' ) ) ) {
					$this->set_operator_query( strtoupper( $as_value ), $data );
				} else if ( is_object( $data ) ) {
					$this->query['select'][] = '(' . $data->limit( 0 )->get_sql() . ') AS ' . $as_value;
				}
			}
		}

		return $this;
	}

	function date( $col_name, $compare, $props ) {

		if ( $compare == '=' ) {

			if ( isset( $props['year'] ) )
				$this->query['where'][] = "YEAR(" . $this->table['as'] . ".$col_name) = '" . $props['year'] . "'";

			if ( isset( $props['month'] ) )
				$this->query['where'][] = "MONTH(" . $this->table['as'] . ".$col_name) = '" . $props['month'] . "'";

			if ( isset( $props['day'] ) )
				$this->query['where'][] = "DAY(" . $this->table['as'] . ".$col_name) = '" . $props['day'] . "'";

			if ( isset( $props['last'] ) )
				$this->date( $col_name, '>=', array( 'interval' => $props['last'] ) );

			if ( isset( $props['older'] ) )
				$this->date( $col_name, '<', array( 'interval' => $props['older'] ) );
		} else if ( $compare == 'BETWEEN' ) {

			if ( $props ) {

				if ( ! $props[1] )
					$props[1] = current_time( 'mysql' );

				$this->query['where'][] = "(" . $this->table['as'] . ".$col_name BETWEEN CAST('" . $props[0] . "' AS DATE) AND CAST('" . $props[1] . "' AS DATE))";
			}
		} else {

			if ( is_array( $props ) && isset( $props['interval'] ) ) {
				$this->query['where'][] = $this->table['as'] . ".$col_name $compare DATE_SUB(NOW(), INTERVAL " . $props['interval'] . ")";
			} else {
				$this->query['where'][] = $this->table['as'] . ".$col_name $compare '$props'";
			}
		}

		return $this;
	}

	function where( $where ) {
		foreach ( $this->table['cols'] as $col_name ) {
			if ( isset( $where[$col_name] ) ) {

				$data = $where[$col_name];

				if ( $data === 'is_null' ) {
					$this->query['where'][] = $this->table['as'] . ".$col_name IS NULL";
				} else if ( strpos( $data, '.' ) !== false ) {
					$this->query['where'][] = $this->table['as'] . ".$col_name = $data";
				} else {
					$this->query['where'][] = $this->table['as'] . ".$col_name = " . (is_object( $data ) ? "(" . $data->limit( 0 )->get_sql() . ")" : "'" . $data . "'");
				}
			} else if ( isset( $where[$col_name . '__in'] ) && $where[$col_name . '__in'] ) {

				$data = $where[$col_name . '__in'];

				$this->query['where'][] = $this->table['as'] . ".$col_name IN (" . (is_object( $data ) ? $data->limit( 0 )->get_sql() : $this->get_string_in( $data ) ) . ")";
			} else if ( isset( $where[$col_name . '__not_in'] ) && $where[$col_name . '__not_in'] ) {

				$data = $where[$col_name . '__not_in'];

				$this->query['where'][] = $this->table['as'] . ".$col_name NOT IN (" . (is_object( $data ) ? $data->limit( 0 )->get_sql() : $this->get_string_in( $data ) ) . ")";
			} else if ( isset( $where[$col_name . '__between'] ) && $where[$col_name . '__between'] ) {

				$data = $where[$col_name . '__between'];

				$this->query['where'][] = "(" . $this->table['as'] . '.' . $col_name . " BETWEEN IFNULL(" . $data[0] . ", 0) AND '" . $data[1] . "')";
			} else if ( isset( $where[$col_name . '__like'] ) && $where[$col_name . '__like'] ) {

				$data = $where[$col_name . '__like'];

				$this->query['where'][] = $this->table['as'] . ".$col_name LIKE '%" . $data . "%'";
			} else if ( isset( $where[$col_name . '__to'] ) ) {

				$data = $where[$col_name . '__to'];

				$colName = is_numeric( $data ) ? "CAST(" . $this->table['as'] . ".$col_name AS DECIMAL)" : $this->table['as'] . "." . $col_name;

				$this->query['where'][] = $colName . " <= '" . $data . "'";
			} else if ( isset( $where[$col_name . '__from'] ) ) {

				$data = $where[$col_name . '__from'];

				$colName = is_numeric( $data ) ? "CAST(" . $this->table['as'] . ".$col_name AS DECIMAL)" : $this->table['as'] . "." . $col_name;

				$this->query['where'][] = $colName . " >= '" . $data . "'";
			} else if ( isset( $where[$col_name . '__is'] ) ) {

				$data = $where[$col_name . '__is'];

				$this->query['where'][] = $this->table['as'] . ".$col_name IS " . $data;
			}
		}
		return $this;
	}

	function select_string( $string ) {
		$this->query['select'][] = $string;
		return $this;
	}

	function where_string( $string ) {
		$this->query['where'][] = $string;
		return $this;
	}

	function having_string( $string ) {
		$this->query['having'][] = $string;
		return $this;
	}

	function orderby_string( $string ) {
		$this->query['orderby'][] = $string;
		return $this;
	}

	function get_string_in( $data ) {

		$vars = (is_array( $data )) ? $data : explode( ',', $data );

		$vars = array_map( 'trim', $vars );

		$array = array();
		foreach ( $vars as $var ) {
			if ( is_numeric( $var ) )
				$array[] = $var;
			else
				$array[] = "'$var'";
		}

		return implode( ',', $array );
	}

	function join( $joinProps, $joinQuery ) {

		$joinType = isset( $joinProps[2] ) ? $joinProps[2] : 'INNER';

		$this->query['join'][] = $joinType . " JOIN " . $joinQuery->table['name'] . " AS " . $joinQuery->table['as'] . " ON " . $this->table['as'] . "." . $joinProps[0] . " = " . $joinQuery->table['as'] . "." . $joinProps[1];

		if ( ! $this->query['select'] ) {
			foreach ( $this->table['cols'] as $col_name ) {
				$this->query['select'][] = $this->table['as'] . '.' . $col_name;
			}
		}

		if ( $joinQuery->query['select'] )
			$this->query['select']	 = array_merge( $this->query['select'], $joinQuery->query['select'] );
		if ( $joinQuery->query['where'] )
			$this->query['where']	 = array_merge( $this->query['where'], $joinQuery->query['where'] );
		if ( $joinQuery->query['join'] )
			$this->query['join']	 = array_merge( $this->query['join'], $joinQuery->query['join'] );

		$joinQuery->reset_query();

		return $this;
	}

	function limit( $number, $offset = 0 ) {
		$this->number( $number );
		$this->offset( $offset );
		return $this;
	}

	function number( $number ) {
		$this->query['number'] = $number;
		return $this;
	}

	function offset( $offset ) {
		$this->query['offset'] = $offset;
		return $this;
	}

	function groupby( $groupby ) {
		$this->query['groupby'] = $groupby;
		return $this;
	}

	function orderby( $orderby, $order = false, $currentAs = true ) {

		$as = $currentAs ? $this->table['as'] . '.' : '';

		if ( is_array( $orderby ) ) {
			foreach ( $orderby as $by => $order ) {
				$this->query['orderby'][$as . $by] = $order;
			}
		} else {
			$this->query['orderby'] = $as . $orderby;

			if ( $order )
				$this->order( $order );
		}

		return $this;
	}

	function order( $order ) {
		$this->query['order'] = $order;
		return $this;
	}

	function get_query() {
		return $this->query;
	}

	function get_sql( $query = false ) {

		$query = $query ? $query : $this->get_query();

		if ( ! isset( $query['select'] ) || ! $query['select'] ) {
			$query['select'][] = $this->table['as'] . '.*';
		}

		$sql[] = "SELECT " . implode( ', ', $query['select'] );

		$sql[] = "FROM " . $this->table['name'] . " AS " . $this->table['as'];

		if ( isset( $query['join'] ) && $query['join'] ) {
			$sql[] = implode( ' ', $query['join'] );
		}

		$where = array();

		if ( isset( $query['where'] ) && $query['where'] ) {
			$where[] = implode( ' AND ', $query['where'] );
		}

		if ( isset( $query['where_or'] ) && $query['where_or'] ) {

			if ( $query['where'] )
				$where_or[] = 'OR';

			$where_or[] = implode( ' OR ', $query['where_or'] );

			$where[] = implode( ' ', $where_or );
		}

		if ( $where )
			$sql[] = "WHERE " . implode( ' ', $where );

		if ( isset( $query['union'] ) ) { //support old union request
			foreach ( $query['union'] as $unionQuery ) {

				$sql[] = "UNION ALL";

				$Query = new Rcl_Query( $unionQuery['table'] );

				$sql[] = $Query->get_sql( $unionQuery );
			}
		}

		if ( isset( $query['groupby'] ) && $query['groupby'] )
			$sql[] = "GROUP BY " . $query['groupby'];

		if ( isset( $query['having'] ) && $query['having'] )
			$sql[] = "HAVING " . implode( ' AND ', $query['having'] );

		if ( isset( $query['orderby'] ) && $query['orderby'] ) {

			if ( is_array( $query['orderby'] ) ) {
				$orders = array();
				foreach ( $query['orderby'] as $orderby => $order ) {
					$orders[] = $orderby . " " . $order;
				}
				$sql[] = "ORDER BY " . implode( ",", $orders );
			} else {
				$sql[] = "ORDER BY " . $query['orderby'] . " " . $query['order'];
			}
		}

		if ( isset( $query['number'] ) && $query['number'] ) {

			if ( $query['number'] < 0 ) {
				$query['number'] = 0;
			}

			if ( isset( $query['offset'] ) && $query['offset'] ) {
				$sql[] = "LIMIT " . $query['offset'] . "," . $query['number'];
			} else if ( isset( $query['number'] ) && $query['number'] ) {
				$sql[] = "LIMIT " . $query['number'];
			}
		} else if ( isset( $query['offset'] ) && $query['offset'] ) {
			$sql[] = "OFFSET " . $query['offset'];
		}

		$sql = implode( ' ', $sql );

		return $sql;
	}

	function get_data( $method = 'get_results', $cache = false, $return_as = false ) {
		global $wpdb;

		if ( $this->return_as )
			$return_as = $this->return_as;

		if ( $this->cache )
			$cache = $this->cache;

		$query = $this->get_query();

		if ( $cache ) {
			$cachekey	 = json_encode( $query );
			$cache		 = wp_cache_get( $cachekey );
			if ( $cache )
				return $cache;
		}

		$sql = $this->get_sql( $query );

		$data = $return_as ? $wpdb->$method( $sql, $return_as ) : $wpdb->$method( $sql );

		$data = $this->maybe_unserialize( $data );

		$data = wp_unslash( $data );

		if ( $cache )
			wp_cache_add( $cachekey, $data );

		return $data;
	}

	function maybe_unserialize( $data ) {

		if ( ! $this->serialize )
			return $data;

		foreach ( $this->serialize as $colName ) {
			if ( is_array( $data ) ) {
				foreach ( $data as $k => $item ) {
					if ( is_object( $item ) ) {
						if ( isset( $item->$colName ) )
							$data[$k]->$colName = maybe_unserialize( $item->$colName );
					}
				}
			}else if ( is_object( $data ) ) {
				if ( isset( $data->$colName ) )
					$data->$colName = maybe_unserialize( $data->$colName );
			}
		}

		return $data;
	}

	function get_walker() {
		return new Rcl_Walker( $this->get_results() );
	}

	function get_var( $cache = false ) {
		if ( is_array( $cache ) )
			return parent::get_var( $cache );
		return $this->get_data( 'get_var', $cache );
	}

	function get_results( $cache = false, $return_as = false ) {
		if ( is_array( $cache ) )
			return parent::get_results( $cache );
		return $this->get_data( 'get_results', $cache, $return_as );
	}

	function get_row( $cache = false ) {
		if ( is_array( $cache ) )
			return parent::get_row( $cache );
		return $this->get_data( 'get_row', $cache );
	}

	function get_col( $cache = false ) {
		if ( is_array( $cache ) )
			return parent::get_col( $cache );
		return $this->get_data( 'get_col', $cache );
	}

	function get_count( $field_name = false, $cache = false ) {
		return ( ! $result = $this->get_operator_data( 'COUNT', $field_name, $cache ) ) ? 0 : $result;
	}

	function get_sum( $field_name = false, $cache = false ) {
		return ( ! $result = $this->get_operator_data( 'SUM', $field_name, $cache ) ) ? 0 : $result;
	}

	function get_max( $field_name = false, $cache = false ) {
		return $this->get_operator_data( 'MAX', $field_name, $cache );
	}

	function get_min( $field_name = false, $cache = false ) {
		return $this->get_operator_data( 'MIN', $field_name, $cache );
	}

}
