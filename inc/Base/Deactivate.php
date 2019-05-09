<?php
/**
 * @package  Snowfall Booking Plugin
 */
namespace SnowfallBooking\Base;

class Deactivate
{
	public static function deactivate() {
		flush_rewrite_rules();

        wp_clear_scheduled_hook('old_booking');
        wp_clear_scheduled_hook('remove_pdf_files');
	}
}