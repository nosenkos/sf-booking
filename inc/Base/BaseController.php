<?php
/**
 * @package  Snowfall Booking Plugin
 */

namespace SnowfallBooking\Base;

class BaseController
{
    public $plugin_path;

    public $plugin_url;

    public $plugin;

    public $upload_path;

    public $upload_url;

    public $admin_email;

    public $managers = array();

    public $data_prefix = 'sbp-';

    public function __construct()
    {
        if (!defined('SBP')) {
            define('SBP', 'snowfall_booking-plugin');
        }

        // Add Image Size for Logo
        add_image_size('email_logo', 440, 94, true);

//        //get admin email
        $this->admin_email = get_option('admin_email');

        $this->plugin_path = plugin_dir_path(dirname(__FILE__, 2));
        $this->plugin_url = plugin_dir_url(dirname(__FILE__, 2));
        $this->plugin = plugin_basename(dirname(__FILE__, 3)) . '/snowfall_booking-plugin.php';
        $this->upload_path = wp_upload_dir()['basedir'];
        $this->upload_url = wp_upload_dir()['baseurl'];
    }

    public function activated(string $key)
    {
        $option = get_option('tn_academy_plugin');

        return isset($option[$key]) ? $option[$key] : false;
    }

    public function getBookingType(string $type)
    {
        switch ($type) {
            case '1':
                return __("Corporate", SBP);
                break;

            case '3':
                return __("Private", SBP);
                break;
        }
    }

    public function getBookingTypeSlug(string $type)
    {
        switch ($type) {
            case '1':
                return "corporate";
                break;

            case '3':
                return "private";
                break;
        }
    }

    public function getBookingStatus(string $type)
    {
        switch ($type) {
            case '2':
                return '<span class="booked">'.__("Booked", SBP).'</span>';
                break;

            case '3':
                return '<span class="pre-booked">'.__("Pre-booked", SBP).'</span>';
                break;

            case '4':
                return '<span class="blocked">'.__("Blocked", SBP).'</span>';
                break;
        }
    }

    public function getBookingCleanStatus(string $type)
    {
        switch ($type) {
            case '2':
                return __("Booked", SBP);
                break;

            case '3':
                return __("Pre-booked", SBP);
                break;

            case '4':
                return __("Blocked", SBP);
                break;
        }
    }

    public function getPayStatus(string $type)
    {
        switch ($type) {
            case '1':
                return __('Not paid', SBP);
                break;

            case '2':
                return __('Paid', SBP);
                break;
        }
    }

    public function getAddon(string $id)
    {
        return isset(get_option('sbp_addons')[$id]) ? get_option('sbp_addons')[$id] : false;
    }

    public function getAllBookingStatus()
    {
        global $wpdb;

        $results = $wpdb->get_results(
            "
                    SELECT pm1.post_id, pm1.meta_value as sbp_status, pm2.meta_value as sbp_selected_dates
                    FROM $wpdb->postmeta as pm1
                    INNER JOIN $wpdb->postmeta as pm2
                    ON pm2.post_id = pm1.post_id
                    AND pm2.meta_key = 'sbp-selected_dates'
                    INNER JOIN $wpdb->posts as ps1 ON ps1.ID = pm1.post_id 
                    AND ps1.post_status = 'publish'
                    WHERE pm1.meta_key = 'sbp-status'
                    ", OBJECT);

        $booking_status = array(
            'booked' => '',
            'pre-booked' => '',
            'blocked' => ''
        );

        $sep_2 = "";
        $sep_3 = "";
        $sep_4 = "";
        foreach ($results as $result) {
            if ($result->sbp_status == 2) {
                $booking_status['booked'] .= $sep_2 . $result->sbp_selected_dates;
                $sep_2 = ",";
            } elseif ($result->sbp_status == 3) {
                $booking_status['pre-booked'] .= $sep_3 . $result->sbp_selected_dates;
                $sep_3 = ",";
            } elseif ($result->sbp_status == 4) {
                $booking_status['blocked'] .= $sep_4 . $result->sbp_selected_dates;
                $sep_4 = ",";
            }
        }

        return $booking_status;
    }

    function delete_directory($dirname) {
        $dir_handle = false;
        if (is_dir($dirname))
            $dir_handle = opendir($dirname);
        if (!$dir_handle)
            return;
        while($file = readdir($dir_handle)) {
            echo "<pre>";
            print_r($file);
            echo "</pre>";
            if ($file != "." && $file != "..") {
                if (!is_dir($dirname."/".$file)):
                    unlink($dirname."/".$file);
                else:
                    $this->delete_directory($dirname.'/'.$file);
                endif;
            }
        }
        closedir($dir_handle);
        rmdir($dirname);
    }
}