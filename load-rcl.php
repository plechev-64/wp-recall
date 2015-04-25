<?php
define('SHORTINIT',true);

$path_parts = pathinfo(__FILE__);
preg_match_all("/(?<=)[A-z0-9\-\/\.\_\s\ё]*(?=wp\-content)/i", $path_parts['dirname'], $string_value);

require_once( $string_value[0][0].'/wp-load.php' );
require_once( $string_value[0][0].'/wp-includes/l10n.php' );

global $wpdb,$user_ID,$rcl_options,$action,$active_plugins,$active_addons,$locale;

$rcl_options = get_option('primary-rcl-options');
$active_plugins = serialize(get_option('active_plugins'));
$active_addons = get_option('active_addons_recall');
$locale = get_locale();

define('RCL_PATH', $path_parts['dirname'].'/');
define('RCL_PREF', $wpdb->prefix.'rcl_');

require_once('functions/replacement.php');

if(isset($_POST['action'])) $action = esc_sql($_POST['action']);
if(isset($_POST['user_ID'])) $user_ID = esc_sql($_POST['user_ID']);

load_theme_textdomain('rcl', RCL_PATH.'lang/');