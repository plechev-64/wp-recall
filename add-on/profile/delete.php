<?php
global $rcl_options;
unset($rcl_options['delete_user_account']);
update_option('primary-rcl-options',$rcl_options);
?>