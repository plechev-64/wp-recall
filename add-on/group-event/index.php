<?php
if (!is_admin()) add_action('wp_enqueue_scripts', 'output_style_event_group');
function output_style_event_group(){
	global $rcl_options;	
	if($rcl_options['minify_css']==1) return;
	wp_enqueue_style( 'event_group_style', plugins_url('style.css', __FILE__) );	
}
if (is_admin()) add_filter('csspath_array_rcl','minify_css_event_group');
function minify_css_event_group($array){
	global $rcl_options;	
	if($rcl_options['minify_css']!=1) return;
	$path = pathinfo(__FILE__);
	$array[] = $path['dirname'].'/style.css';
	return $array;
}

function shortcode_event_group_rcl( $atts ) {
	global $user_ID,$rcl_options,$group_id,$options_gr;
	
	$hour_round = 4;
	$date_now = gmdate("Y-m-d H:i:s");
	$unix_date_now = strtotime($date_now);
	
	$date_future = gmdate("Y-m-d H:i:s", mktime($options_gr['event']['hour']-$hour_round, $options_gr['event']['min'], 0, $options_gr['event']['month'], $options_gr['event']['day'], $options_gr['event']['year']));

	$unix_date_future = strtotime($date_future);
	
	$timeout .= "
	<script type='text/javascript'>
		var ev_dateNow='".$unix_date_now."';
		var html_end; 
		var dateFuture = '".$unix_date_future."';
	</script>";
	$timeout .= "<div id='count-group-event'>";
	if($options_gr['event']['title']) $timeout .= "<h3>".$options_gr['event']['title']."</h3>";
	$timeout .= "<div id='countbox'></div>
	<div id='end_text'></div>";
	$timeout .= "</div>";
	$timeout .= '<script type="text/javascript" src="'.RCL_URL.'add-on/group-event/js/counter.js"></script>';	
   return $timeout;
}
add_shortcode('event-group', 'shortcode_event_group_rcl');

function get_option_event_group_rcl($cnt){
	global $options_gr;
	$cnt .=  '<p><input type="checkbox" '.checked($options_gr['event']['active'],1,false).' name="event[active]" value="1"> - Установить дату и время до события<br> 
				Заголовок счетчика: <input type="text" size="20" name="event[title]" value="'.$options_gr['event']['title'].'"><br>';
				$cnt.='<select name="event[min]">';
					for($a=0;$a<=60;$a++){$cnt.='<option '.selected($options_gr['event']['min'],$a,false).' value="'.$a.'">'.$a.'</option>'; }
				$cnt.='</select> минута<br>';
				$cnt.='<select name="event[hour]">';
					for($a=0;$a<=24;$a++){$cnt.='<option '.selected($options_gr['event']['hour'],$a,false).' value="'.$a.'">'.$a.'</option>'; }
				$cnt.='</select> час<br>';
				$cnt.='<select name="event[day]">';
					for($a=0;$a<=31;$a++){$cnt.='<option '.selected($options_gr['event']['day'],$a,false).' value="'.$a.'">'.$a.'</option>'; }
				$cnt.='</select> число<br>';
				$cnt.='<select name="event[month]">';
					for($a=0;$a<=12;$a++){$cnt.='<option '.selected($options_gr['event']['month'],$a,false).' value="'.$a.'">'.$a.'</option>'; }
				$cnt.='</select> месяц<br>';
				$cnt.='<select name="event[year]">';
					$y = date('Y');
					$fy = $y+1;
					for($a=$y;$a<=$fy;$a++){$cnt.='<option '.selected($options_gr['event']['year'],$a,false).' value="'.$a.'">'.$a.'</option>'; }
				$cnt.='</select> год';
				$cnt.='</p>';
	return $cnt;
}
add_filter('options_group_rcl','get_option_event_group_rcl');

function get_event_group_rcl($cnt){
	global $options_gr;
	if($options_gr['event']['active']==1) $cnt .= do_shortcode('[event-group]');
	return $cnt;
}
add_filter('content_group_rcl','get_event_group_rcl');
?>