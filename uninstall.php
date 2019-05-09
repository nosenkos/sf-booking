<?php

/**
 * Trigger this file on Plugin uninstall
 *
 * @package  Snowfall Booking Plugin
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	die;
}

// Clear Database stored data
$books = get_posts( array( 'post_type' => 'booking_list', 'numberposts' => -1, 'post_status' => 'any' ) );

foreach( $books as $book ) {
	wp_delete_post( $book->ID, true );
}

if (get_option('sbp_addons')) {
    delete_option('sbp_addons');
}

if (get_option('sbp_addon_last_id')) {
    delete_option('sbp_addon_last_id');
}

if (get_option('sbp_calendar_price')) {
    delete_option('sbp_calendar_price');
}

if (get_option('sbp_mail')) {
    delete_option('sbp_mail');
}

if (get_option('sbp_btn')) {
    delete_option('sbp_btn');
}