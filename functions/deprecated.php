<?php
/*14.0.0*/
add_action('wp_head','rcl_head_js_data',1);
function rcl_head_js_data(){
    global $user_ID;
    $data = "<script>
	var user_ID = $user_ID;
	var wpurl = '".preg_quote(trailingslashit(get_bloginfo('wpurl')),'/:')."';
	var rcl_url = '".preg_quote(RCL_URL,'/:')."';
	</script>\n";
    echo $data;
}