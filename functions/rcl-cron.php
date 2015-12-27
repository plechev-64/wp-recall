<?php

add_action('wp', 'rcl_cron_activated');
function rcl_cron_activated() {
    if ( !wp_next_scheduled( 'rcl_cron_hourly_schedule' ) ) {
        $start_date = strtotime(current_time('mysql'));
        wp_schedule_event( $start_date, 'hourly', 'rcl_cron_hourly_schedule');
    }
    if ( !wp_next_scheduled( 'rcl_cron_twicedaily_schedule' ) ) {
        $start_date = strtotime(current_time('mysql'));
        wp_schedule_event( $start_date, 'twicedaily', 'rcl_cron_twicedaily_schedule');
    }
    if ( !wp_next_scheduled( 'rcl_cron_daily_schedule' ) ) {
        $start_date = strtotime(current_time('mysql'));
        wp_schedule_event( $start_date, 'daily', 'rcl_cron_daily_schedule');
    }
}

add_action('rcl_cron_hourly_schedule','rcl_cron_hourly');
function rcl_cron_hourly(){
    do_action('rcl_cron_hourly');
}
add_action('rcl_cron_twicedaily_schedule','rcl_cron_twicedaily');
function rcl_cron_twicedaily(){
    do_action('rcl_cron_twicedaily');
}
add_action('rcl_cron_daily_schedule','rcl_cron_daily');
function rcl_cron_daily(){
    do_action('rcl_cron_daily');
}

