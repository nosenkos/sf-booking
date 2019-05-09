<?php
/**
 * Created by PhpStorm.
 * User: sergeynosenko
 * Date: 24.09.2018
 * Time: 4:59
 */

namespace SnowfallBooking\Base;

use SnowfallBooking\Base\BaseController;


class CSVExport extends BaseController
{
    /**
     * Constructor
     */
    /**
     * Constructor
     */

    public $course;


    public function register()
    {
        if (isset($_POST['export']) && $_POST['export'] == 'sbp-member-download-list-csv') {
            $csv = $this->generate_csv();

            $date = date('m/d/Y h:i:s', time());
            $filename = "exprot_csv_ulvhall_booking_" . $date;

            header("Pragma: public");
            header("Expires: 0");
            header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
            header("Cache-Control: private", false);
            header("Content-Type: application/octet-stream");
            header("Content-Disposition: attachment; filename=\"" . $filename . ".csv\";");
            header("Content-Transfer-Encoding: binary");

            echo $csv;
            exit;
        }
    }

    /**
     * Converting data to CSV
     */
    public function generate_csv()
    {
        $csv_output = '';
        $datas = [];
        $members = get_posts(array('post_type' => 'booking_list', 'numberposts' => -1, 'post_status' => array('publish', 'draft')));
        foreach ($members as $member) {
            $first_name = get_post_meta($member->ID, $this->data_prefix . 'first_name', true);
            $first_name = isset($first_name) ? $first_name : '';

            $last_name = get_post_meta($member->ID, $this->data_prefix . 'last_name', true);
            $last_name = isset($last_name) ? $last_name : '';

            $phone = get_post_meta($member->ID, $this->data_prefix . 'phone', true);
            $phone = isset($phone) ? $phone : '';

            $email = get_post_meta($member->ID, $this->data_prefix . 'email', true);
            $email = isset($email) ? $email : '';

            $booking_status = get_post_meta($member->ID, $this->data_prefix . 'status', true);

            $selected_dates = get_post_meta($member->ID, $this->data_prefix . 'selected_dates', true);
            if (isset($selected_dates)):
                $selected_dates = explode(',', $selected_dates);
                if (count($selected_dates) > 1) {
                    $selected_dates = $selected_dates[0] . ' — ' . array_pop($selected_dates);
                } else {
                    $selected_dates = $selected_dates[0];
                }
            else:
                $selected_dates = '';
            endif;

            $booking_type = get_post_meta($member->ID, $this->data_prefix . 'booking_type', true);

            $amount_guests = get_post_meta($member->ID, $this->data_prefix . 'amount_guests', true);

            $addons = get_post_meta($member->ID, $this->data_prefix . 'addons', true);

            $addons_text = "";

            if (isset($addons) && !empty($addons)) {
               $i = 0;
                foreach ($addons as $key => $val) {
                    $comma = ($i == 0) ? "" : " | ";
                    $addon = $this->getAddon($key);
                    $addons_text .= $comma . $addon['addon_name'];
                    $i += 1;
                }
            }

            $pay_status = get_post_meta($member->ID, $this->data_prefix . 'pay_status', true);

            $total_price = get_post_meta($member->ID, $this->data_prefix . 'total_price', true);
            $total_price = isset($total_price) && !empty($total_price) ? $total_price : '0';

            $date = get_post($member->ID)->post_date_gmt;

            $datas[] = array(
                'First Name' => $first_name,
                'Last Name' => $last_name,
                'Phone' => $phone,
                'Email' => $email,
                'Booking Status' => $this->getBookingCleanStatus($booking_status),
                'Selected Dates' => $selected_dates,
                'Booking Type' => $this->getBookingType($booking_type),
                'Amount of guests' => $amount_guests,
                'Add-ons' => $addons_text,
                'Pay Status' => $this->getPayStatus($pay_status),
                'Total Price' => $total_price,
                'Registration Date' => $date,
            );

        }

        $heading = false;
        if (!empty($datas))
            foreach ($datas as $row) {
                if (!$heading) {
                    // display field/column names as a first row
                    $csv_output .= implode(",", array_keys($row)) . "\n";
                    $heading = true;
                }
                $csv_output .= implode(",", array_values($row)) . "\n";
            }

        return $csv_output;
    }
}