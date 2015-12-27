<?php
global $rcl_options;
unset($rcl_options['user_public_access_recall']);
unset($rcl_options['id_parent_category']);
unset($rcl_options['media_downloader_recall']);
unset($rcl_options['moderation_public_post']);
unset($rcl_options['info_author_recall']);
update_option('rcl_global_options',$rcl_options);
?>