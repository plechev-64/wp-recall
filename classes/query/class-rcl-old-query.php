<?php

/**
 * Description of Rcl_Query
 *
 * @author Андрей
 */
class Rcl_Old_Query {

	public $args		 = array();
	public $return_as	 = false;
	public $cache		 = false;

	function set_query( $args = false ) {

		$this->args = wp_unslash( esc_sql( $args ) );

		if ( ! $this->query['table'] ) {

			if ( isset( $this->args['table'] ) ) {
				$this->query['table'] = $this->args['table'];
			}
		}

		//получаем устаревшие указания кол-ва значений на странице
		//и приводим к number
		if ( isset( $this->args['per_page'] ) ) {
			$this->args['number'] = $this->args['per_page'];
		} else if ( isset( $this->args['inpage'] ) ) {
			$this->args['number'] = $this->args['inpage'];
		} else if ( isset( $this->args['in_page'] ) ) {
			$this->args['number'] = $this->args['in_page'];
		}

		if ( isset( $this->args['fields'] ) ) {

			$this->set_fields( $this->args['fields'] );
		} else {

			if ( ! $this->query['select'] ) {
				$this->query['select'][] = $this->query['table']['as'] . '.*';
			}
		}

		if ( isset( $this->args['distinct'] ) ) {
			$this->query['select'][0] = 'DISTINCT ' . $this->query['select'][0];
		}

		if ( $this->query['table']['cols'] ) {

			if ( isset( $this->args['include'] ) && $this->args['include'] ) {

				$this->query['where'][] = $this->query['table']['as'] . "." . $this->query['table']['cols'][0] . " IN (" . $this->get_string_in( $this->args['include'] ) . ")";
			}

			if ( isset( $this->args['exclude'] ) && $this->args['exclude'] ) {

				$this->query['where'][] = $this->query['table']['as'] . "." . $this->query['table']['cols'][0] . " NOT IN (" . $this->get_string_in( $this->args['exclude'] ) . ")";
			}

			foreach ( $this->query['table']['cols'] as $col_name ) {

				if ( isset( $this->args[$col_name] ) && $this->args[$col_name] != '' ) {

					if ( $this->args[$col_name] === 'is_null' ) {
						$this->query['where'][] = $this->query['table']['as'] . ".$col_name IS NULL";
					} else {
						$this->query['where'][] = $this->query['table']['as'] . ".$col_name = '" . $this->args[$col_name] . "'";
					}
				}

				if ( isset( $this->args[$col_name . '__is'] ) && $this->args[$col_name . '__is'] ) {

					$this->query['where'][] = $this->query['table']['as'] . ".$col_name IS " . $this->args[$col_name . '__is'];
				}

				if ( isset( $this->args[$col_name . '__in'] ) && ($this->args[$col_name . '__in'] || $this->args[$col_name . '__in'] === 0) ) {

					$this->query['where'][] = $this->query['table']['as'] . ".$col_name IN (" . $this->get_string_in( $this->args[$col_name . '__in'] ) . ")";
				}

				if ( isset( $this->args[$col_name . '__not_in'] ) && ($this->args[$col_name . '__not_in'] || $this->args[$col_name . '__not_in'] === 0) ) {

					$this->query['where'][] = $this->query['table']['as'] . ".$col_name NOT IN (" . $this->get_string_in( $this->args[$col_name . '__not_in'] ) . ")";
				}

				if ( isset( $this->args[$col_name . '__from'] ) && ($this->args[$col_name . '__from'] || $this->args[$col_name . '__from'] != '') ) {

					$colName = is_numeric( $this->args[$col_name . '__from'] ) ? "CAST(" . $this->query['table']['as'] . ".$col_name AS DECIMAL)" : $this->query['table']['as'] . "." . $col_name;

					$this->query['where'][] = $colName . " >= '" . $this->args[$col_name . '__from'] . "'";
				}

				if ( isset( $this->args[$col_name . '__to'] ) && ($this->args[$col_name . '__to'] || $this->args[$col_name . '__to'] != '') ) {

					$colName = is_numeric( $this->args[$col_name . '__to'] ) ? "CAST(" . $this->query['table']['as'] . ".$col_name AS DECIMAL)" : $this->query['table']['as'] . "." . $col_name;

					$this->query['where'][] = $colName . " <= '" . $this->args[$col_name . '__to'] . "'";
				}

				if ( isset( $this->args[$col_name . '__like'] ) && ($this->args[$col_name . '__like'] || $this->args[$col_name . '__like'] === 0) ) {

					$this->query['where'][] = $this->query['table']['as'] . ".$col_name LIKE '%" . $this->args[$col_name . '__like'] . "%'";
				}

				if ( isset( $this->args[$col_name . '__between'] ) && $this->args[$col_name . '__between'] && is_array( $this->args[$col_name . '__between'] ) ) {

					$this->query['where'][] = "(" . $this->query['table']['as'] . '.' . $col_name . " BETWEEN IFNULL(" . $this->args[$col_name . '__between'][0] . ", 0) AND '" . $this->args[$col_name . '__between'][1] . "')";
				}
			}

			if ( isset( $this->args['date_query'] ) ) {

				$this->set_date_query( $this->args['date_query'] );
			}

			if ( isset( $this->args['join_query'] ) && $this->args['join_query'] ) {
				$this->set_join_query( $this->args['join_query'] );
			}

			if ( isset( $this->args['union_query'] ) ) {

				$this->set_union_query( $this->args['union_query'] );
			}
		}

		$preOrderBy = $this->query['table']['as'] . '.';
		if ( isset( $this->query['union'] ) ) {
			$preOrderBy = '';
		}

		if ( isset( $this->args['orderby'] ) ) {

			if ( $this->args['orderby'] == 'rand' ) {
				$this->query['orderby']	 = $preOrderBy . $this->query['table']['cols'][0];
				$this->query['order']	 = 'RAND()';
			} else if ( is_array( $this->args['orderby'] ) ) {
				foreach ( $this->args['orderby'] as $orderby => $order ) {
					$this->query['orderby'][$preOrderBy . $orderby] = $order;
				}
			} else {
				$this->query['orderby']	 = $preOrderBy . $this->args['orderby'];
				$this->query['order']	 = (isset( $this->args['order'] ) && $this->args['order']) ? $this->args['order'] : 'DESC';
			}
		} else if ( isset( $this->args['orderby_as_decimal'] ) ) {

			$this->query['orderby']	 = 'CAST(' . $preOrderBy . $this->args['orderby_as_decimal'] . ' AS DECIMAL)';
			$this->query['order']	 = (isset( $this->args['order'] ) && $this->args['order']) ? $this->args['order'] : 'DESC';
		} else if ( isset( $this->args['order'] ) ) {

			$this->query['order'] = $this->args['order'];
		} else {

			$this->query['orderby']	 = $preOrderBy . $this->query['table']['cols'][0];
			$this->query['order']	 = 'DESC';
		}

		if ( isset( $this->args['number'] ) )
			$this->query['number'] = $this->args['number'];

		if ( isset( $this->args['offset'] ) )
			$this->query['offset'] = $this->args['offset'];

		if ( isset( $this->args['groupby'] ) )
			$this->query['groupby'] = $this->args['groupby'];

		if ( isset( $this->args['having'] ) )
			$this->query['having'] = $this->args['having'];

		if ( isset( $this->args['return_as'] ) )
			$this->query['return_as'] = $this->args['return_as'];

		if ( isset( $this->args['unserialize'] ) && $this->args['unserialize'] ) {
			$this->serialize = array( $this->args['unserialize'] );
		}

		if ( isset( $this->args['return_as'] ) && $this->args['return_as'] ) {
			$this->return_as = $this->args['return_as'];
		}

		if ( isset( $this->args['cache'] ) && $this->args['cache'] ) {
			$this->cache = true;
		}
	}

	function set_fields( $fields ) {

		if ( ! $fields )
			return false;

		foreach ( $fields as $key => $name ) {

			$tableas = $this->query['table']['as'];

			if ( $key === 'custom_query' ) {

				foreach ( $name as $n ) {
					if ( ! $n )
						continue;
					$this->query['select'][] = $n;
				}

				continue;
			}

			if ( is_string( $name ) && ! in_array( $name, $this->query['table']['cols'] ) ) {
				continue;
			}

			if ( is_int( $key ) && is_string( $name ) ) {
				$this->query['select'][] = $tableas . '.' . $name;
				continue;
			}

			if ( is_string( $key ) && is_string( $name ) ) {
				$this->query['select'][] = $tableas . '.' . $name . ' AS ' . $key;
				continue;
			}

			if ( is_string( $key ) && is_array( $name ) ) {

				$fieldname	 = $key;
				$as			 = $key;

				if ( isset( $name['as'] ) ) {
					$as = $name['as'];
				}

				if ( isset( $name['ifnull'] ) ) {
					$select = 'IFNULL(' . $tableas . '.' . $fieldname . ', ' . $name['ifnull'] . ') ' . $as;
				} else {
					$select = $tableas . '.' . $fieldname . ' AS ' . $as;
				}


				$this->query['select'][] = $select;
				continue;
			}
		}
	}

	function set_date_query( $date_query ) {

		foreach ( $date_query as $date ) {

			if ( ! isset( $date['column'] ) )
				continue;

			if ( ! isset( $date['compare'] ) )
				$date['compare'] = '=';

			if ( $date['compare'] == '=' ) {

				$datetime = array();

				if ( isset( $date['year'] ) )
					$this->query['where'][] = "YEAR(" . $this->query['table']['as'] . "." . $date['column'] . ") = '" . $date['year'] . "'";

				if ( isset( $date['month'] ) )
					$this->query['where'][] = "MONTH(" . $this->query['table']['as'] . "." . $date['column'] . ") = '" . $date['month'] . "'";

				if ( isset( $date['day'] ) )
					$this->query['where'][] = "DAY(" . $this->query['table']['as'] . "." . $date['column'] . ") = '" . $date['day'] . "'";

				if ( isset( $date['last'] ) ) {

					$this->query['where'][] = $this->query['table']['as'] . "." . $date['column'] . " >= DATE_SUB(NOW(), INTERVAL " . $date['last'] . ")";
				}

				if ( isset( $date['older'] ) ) {

					$this->query['where'][] = $this->query['table']['as'] . "." . $date['column'] . " < DATE_SUB(NOW(), INTERVAL " . $date['older'] . ")";
				}
			} else if ( $date['compare'] == 'BETWEEN' ) {

				if ( ! isset( $date['value'] ) || ! $date['value'] || ! $date['value'][0] && ! $date['value'][1] )
					continue;

				if ( ! $date['value'][1] ) {
					$date['value'][1] = current_time( 'mysql' );
				}

				$this->query['where'][] = "(" . $this->query['table']['as'] . "." . $date['column'] . " BETWEEN CAST('" . $date['value'][0] . "' AS DATE) AND CAST('" . $date['value'][1] . "' AS DATE))";
			} else {

				if ( isset( $date['interval'] ) ) {
					$this->query['where'][] = "DATE_SUB(NOW(), INTERVAL " . $date['last'] . ") " . $date['compare'] . " " . $this->query['table']['as'] . "." . $date['column'];
				} else {
					$this->query['where'][] = $this->query['table']['as'] . "." . $date['column'] . " " . $date['compare'] . " '" . $date['value'] . "'";
				}
			}
		}
	}

	function set_union_query( $unions ) {

		foreach ( $unions as $union ) {

			$unionTable = $union['table'];

			if ( ! $unionTable )
				continue;

			$unionQuery = new Rcl_Query( $union['table'] );

			$unionQuery->set_query( $union );

			unset( $unionQuery->query['orderby'] );
			unset( $unionQuery->query['number'] );

			$this->query['union'][] = $unionQuery->query;
		}
	}

	function set_join_query( $joins ) {

		foreach ( $joins as $k => $join ) {

			$joinTable = $join['table'];

			if ( ! $joinTable )
				continue;

			if ( isset( $join['postfix'] ) ) {
				$joinTable['as'] .= $join['postfix'];
			}

			$joinOn = false;
			foreach ( $this->query['table']['cols'] as $col_name ) {

				if ( isset( $join['on_' . $col_name] ) ) {
					$joinOn = $col_name;
					break;
				}
			}

			if ( ! $joinOn )
				continue;

			$joinType = (isset( $join['join'] )) ? $join['join'] : 'INNER';

			$this->query['join'][] = $joinType . " JOIN " . $joinTable['name'] . " AS " . $joinTable['as'] . " ON " . $this->query['table']['as'] . "." . $joinOn . " = " . $joinTable['as'] . "." . $join['on_' . $joinOn];

			$joinQuery = new Rcl_Query( $join['table'] );

			$joinQuery->set_query( $join );

			$this->query['select']	 = array_merge( $this->query['select'], $joinQuery->query['select'] );
			$this->query['where']	 = array_merge( $this->query['where'], $joinQuery->query['where'] );
			$this->query['join']	 = array_merge( $this->query['join'], $joinQuery->query['join'] );
		}
	}

	function get_var( $args ) {

		$this->set_query( $args );

		$result = $this->get_data( 'get_var' );

		$this->reset_query();

		return $result;
	}

	function get_results( $args ) {

		$this->set_query( $args );

		$result = $this->get_data( 'get_results' );

		$this->reset_query();

		if ( isset( $this->args['get_walker'] ) && $this->args['get_walker'] ) {
			return new Rcl_Walker( $result );
		}

		return $result;
	}

	function get_row( $args ) {

		$this->set_query( $args );

		$result = $this->get_data( 'get_row' );

		$this->reset_query();

		return $result;
	}

	function get_col( $args ) {

		$this->set_query( $args );

		$result = $this->get_data( 'get_col' );

		$this->reset_query();

		return $result;
	}

	function count( $args = false, $field_name = false ) {

		$result = $this->get_operator( 'COUNT', $args, $field_name );

		if ( ! $result )
			$result = 0;

		return $result;
	}

	function max( $field_name, $args = false ) {

		return $this->get_operator( 'MAX', $args, $field_name );
	}

	function min( $field_name, $args = false ) {

		return $this->get_operator( 'MIN', $args, $field_name );
	}

	function sum( $args = false, $field_name = false ) {

		$result = $this->get_operator( 'SUM', $args, $field_name );

		if ( ! $result )
			$result = 0;

		return $result;
	}

	function get_operator( $operator, $args = false, $field_name = false ) {

		global $wpdb;

		if ( $args )
			$this->set_query( $args );

		$field_name = ($field_name) ? $field_name : $this->query['table']['cols'][0];

		$query = $this->get_query();

		if ( isset( $query['union'] ) ) {

			unset( $query['select'] );
			unset( $query['offset'] );
			unset( $query['orderby'] );
			unset( $query['order'] );
			unset( $query['number'] );
			unset( $query['having'] );

			$query['select'] = array( $operator . '(' . $query['table']['as'] . '.' . $field_name . ') as total' );

			foreach ( $query['union'] as $k => $union ) {
				unset( $query['union'][$k]['select'] );
				unset( $query['union'][$k]['offset'] );
				unset( $query['union'][$k]['orderby'] );
				unset( $query['union'][$k]['order'] );
				unset( $query['union'][$k]['number'] );
				unset( $query['union'][$k]['having'] );
				$query['union'][$k]['select'] = array( $operator . '(' . $query['union'][$k]['table']['as'] . '.' . $field_name . ') as total' );
			}

			$sql = 'SELECT SUM(total) FROM (' . $this->get_sql( $query ) . ') x';
		} else {
			unset( $query['select'] );
			unset( $query['offset'] );
			unset( $query['orderby'] );
			unset( $query['order'] );
			unset( $query['number'] );
			unset( $query['having'] );

			$query['select'] = array( $operator . '(' . $query['table']['as'] . '.' . $field_name . ')' );

			$sql = $this->get_sql( $query );
		}

		if ( isset( $query['groupby'] ) && $query['groupby'] )
			$result	 = $wpdb->query( $sql );
		else
			$result	 = $wpdb->get_var( $sql );

		//$this->reset_query();

		return $result;
	}

}
