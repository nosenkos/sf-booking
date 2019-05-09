<?php
/**
 * @package  Snowfall Booking Plugin
 */
/*
Plugin Name: Snowfall Booking Plugin
Plugin URI: https://www.snowfall.se
Description: This is advanced booking plugin developed by Snowfall
Version: 1.0.0
Author: Serhii Nosenko
Author URI: https://www.linkedin.com/in/sergey-nosenko-4b8a37127/
License: GPLv2 or later
Text Domain: snowfall_booking-plugin
*/

// If this file is called firectly, abort!!!
defined( 'ABSPATH' ) or die( 'Hey, what are you doing here? You silly human!' );

// Require once the Composer Autoload
if ( file_exists( dirname( __FILE__ ) . '/vendor/autoload.php' ) ) {
	require_once dirname( __FILE__ ) . '/vendor/autoload.php';
}

/**
 * The code that runs during plugin activation
 */
function activate_snowfall_booking_plugin() {
	SnowfallBooking\Base\Activate::activate();
}
register_activation_hook( __FILE__, 'activate_snowfall_booking_plugin' );

/**
 * The code that runs during plugin deactivation
 */
function deactivate_snowfall_booking_plugin() {
	SnowfallBooking\Base\Deactivate::deactivate();
}
register_deactivation_hook( __FILE__, 'deactivate_snowfall_booking_plugin' );

/**
 * Initialize all the core classes of the plugin
 */
if ( class_exists( 'SnowfallBooking\\Init' ) ) {
	SnowfallBooking\Init::registerServices();
}