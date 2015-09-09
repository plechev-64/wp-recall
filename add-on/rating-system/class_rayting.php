<?php
class Rcl_Rating{

	function __construct(){

	}

	function get_values($args){
		global $wpdb;

		$table = RCL_PREF."rating_values";

		$fields = "*";

		if(isset($args['fields'])){
			$fields = implode(',',$args['fields']);
		}

		if(isset($args['rating_type'])){
			$types = explode(',',$args['rating_type']);
			$where[] = "rating_type IN ('".implode("','",$types)."')";
		}

		if(isset($args['days'])){
			$where[] = "rating_date > '".current_time('mysql')."' - INTERVAL ".$args['days']." DAY";
		}

		if($where) $query = "WHERE ".implode(' AND ',$where);

		if(isset($args['group_by'])&&$args['group_by']){
			$query .= " GROUP BY ".$args['group_by'];
			$fields = $args['group_by'].",SUM(rating_value) as rating_total";
		}

		if(isset($args['order'])){

			$query .= " ORDER BY";

			if($args['order']=='rating_value') $query .= " CAST(".$args['order']." AS DECIMAL) ";
			else $query .= " ".$args['order']." ";

			if(isset($args['order_by'])){
				$query .= $args['order_by'];
			}else{
				$query .= "DESC";
			}
		}

		if(isset($args['number'])&&$args['number']){
			$offset = (isset($args['offset']))? $args['offset']: 0;
			$query .= " LIMIT $offset,".$args['number'];
		}

		$query = "SELECT $fields FROM $table $query";

		return $wpdb->get_results($query);
	}

}