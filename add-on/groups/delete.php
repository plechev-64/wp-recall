<?php
global $rcl_options;
unset($rcl_options['public_group_access_recall']);
update_option('primary-rcl-options',$rcl_options);
?>