<?php
/**
 * @package  Snowfall Booking Plugin
 */

namespace SnowfallBooking\Base;

class Activate
{
    public static function activate()
    {
        flush_rewrite_rules();

        if (! wp_next_scheduled ( 'old_booking' )) {
            wp_schedule_event(mktime(0,0,0), 'daily', 'old_booking');
        }

        if (! wp_next_scheduled ( 'remove_pdf_files' )) {
            wp_schedule_event(mktime(0,0,0), 'daily', 'remove_pdf_files');
        }

        $default = array();

        $default_mail = array(
            'email_from' => '',
            'email_subject' => '',
            'email_logo' => ''
        );

        if (!get_option('sbp_addons')) {
            update_option('sbp_addons', $default);
        }

        if (!get_option('sbp_addon_last_id')) {
            update_option('sbp_addon_last_id', '0');
        }

        if (!get_option('sbp_calendar_price')) {
            update_option('sbp_calendar_price', $default);
        }

        if (!get_option('sbp_btn')) {
            update_option('sbp_btn', $default);
        }

        if (!get_option('sbp_mail')) {
            update_option('sbp_mail', $default_mail);
        }
    }
}