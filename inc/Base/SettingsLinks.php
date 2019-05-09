<?php
/**
 * @package  Snowfall Booking Plugin
 */

namespace SnowfallBooking\Base;

use SnowfallBooking\Base\BaseController;

class SettingsLinks extends BaseController
{
    public function register()
    {
        add_filter("plugin_action_links_$this->plugin", array($this, 'settings_link'));
    }

    public function settings_link($links)
    {
        $settings_link = '<a href="admin.php?page=snowfall_booking_plugin">' . __('Settings', SBP) . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }
}