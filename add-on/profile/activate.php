<?php
global $rcl_options;
if(!isset($rcl_options['rcl_user_profile'])) $rcl_options['rcl_user_profile']=1;
if(!isset($rcl_options['delete_user_account'])) $rcl_options['delete_user_account']='';
update_option('primary-rcl-options',$rcl_options);