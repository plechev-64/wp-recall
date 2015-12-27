<?php
global $rcl_options;
unset($rcl_options['delete_user_account']);
update_option('rcl_global_options',$rcl_options);
?>