<?php
/**
 * Created by PhpStorm.
 * User: sergeynosenko
 * Date: 3/6/19
 * Time: 4:26 PM
 */

namespace SnowfallBooking\Base;

use SnowfallBooking\Base\BaseController;
use SnowfallBooking\Api\Callbacks\AdminCallbacks;
use SnowfallBooking\Api\SettingsApi;
use SnowfallBooking\Base\MailController;
use SnowfallBooking\Base\PDFController;

class ShortcodeController extends BaseController
{
    public $settings;

    public $callbacks;

    public $mail;

    public $pdf;

    public $subpages = array();

    public $btn;

    public function register()
    {
        $this->settings = new SettingsApi();

        $this->callbacks = new AdminCallbacks();

        $this->mail = new MailController();

        $this->pdf = new PDFController();

        add_action('remove_pdf_files', array($this, 'remove_pdf_files'));

        $this->btn = get_option('sbp_btn');

        $this->setShortCodePage();

        // [snowfall_booking]
        add_shortcode('snowfall_booking', array($this, 'snowfall_booking'));

        $this->settings->addSubMenuPages($this->subpages)->register();

        add_filter('wp_mail_from_name', array($this, 'sbp_mail_from_name'), 1, 1);
        add_filter('wp_mail_from', array($this, 'sbp_custom_wp_mail_from'), 1, 1);

        add_action('wp_ajax_sending_estimate', array($this, 'sending_estimate'));
        add_action('wp_ajax_nopriv_sending_estimate', array($this, 'sending_estimate'));
    }

    public function setShortCodePage()
    {
        $this->subpages = array(
            array(
                'parent_slug' => 'snowfall_booking_plugin',
                'page_title' => __('Shortcode', SBP),
                'menu_title' => __('Shortcode', SBP),
                'capability' => 'manage_options',
                'menu_slug' => 'sbp-booking-shortcode-page',
                'callback' => array($this->callbacks, 'shortcodePage')
            )
        );
    }

    public function snowfall_booking()
    {
        ob_start(); ?>
        <div class="row">
            <div class="col-lg-6">
                <input type="text" id="booking_calendar" name="booking_calendar"/>
                <div class="top_calendar">
                    <p class="choose_end_date">
                        <?=__('Need to select both start and stop date', SBP);?>
                    </p>
                    <a href="#" id="clear_selected_dates" class="btn-sbp btn-sbp-blue"><?=__('Clear selection', SBP);?></a>
                </div>
                <div class="bottom_calendar">
                    <div class="kind_booking">
                        <p class=""><span class="booked"></span> <?php _e('Booked', SBP); ?></p>
                        <p class=""><span class="pre-booked"></span> <?php _e('Pre-booked', SBP); ?></p>
                        <p class=""><span class="blocked"></span> <?php _e('Blocked', SBP); ?></p>
                    </div>
                    <div class="ui-toggle can-toggle vat">
                        <span><?= __('EXCL.', SBP); ?></span>
                        <input type="checkbox" id="vat-status" checked>
                        <label for="vat-status">
                            <div></div>
                        </label>
                        <span><?= __('INCL.', SBP); ?></span>
                    </div>
                </div>
                <div class="front_calendar_info">
                    <?php
                    if (isset($this->btn['front_calendar_info'])) {
                        echo wpautop($this->btn['front_calendar_info']);
                    }
                    ?>
                </div>
            </div>
            <div class="col-lg-6">
                <?php
                require_once(plugin_dir_path(dirname(__FILE__, 2)) . 'templates/contact-form-front-end.php');
                ?>
            </div>
        </div>
        <?php
        $html = ob_get_contents();
        ob_get_clean();

        return $html;
    }


    public function sending_estimate()
    {
        if (!DOING_AJAX || !check_ajax_referer('sbp-nonce-front', 'nonce')) {
            return $this->return_json('error');
        }

        $summary = sanitize_text_field($_POST['summary']);
        $selected_dates = sanitize_text_field($_POST['selected_dates']);
        $selected_arr = explode(',', $selected_dates);
        $check_in = $selected_arr[0];
        $check_out = $selected_arr[count($selected_arr) - 1];
        $first_name = sanitize_text_field($_POST['first_name']);
        $last_name = sanitize_text_field($_POST['last_name']);
        $phone = sanitize_text_field($_POST['phone']);
        $email = sanitize_email($_POST['email']);
        $booking_type = sanitize_text_field($_POST['booking_type']);
        $amount_guests = sanitize_text_field($_POST['amount_guests']);
        $total_price = sanitize_text_field($_POST['total_price']);
        $old_price = sanitize_text_field($_POST['old_price']);
        $old_calendar_price = sanitize_text_field($_POST['old_calendar_price']);
        $booked_unix_timestamp = sanitize_text_field($_POST['booked_unix_timestamp']);
        $addons = isset($_POST['addons']) ? $_POST['addons'] : array();

        $data = array(
            'booking_type' => $booking_type,
            'amount_guests' => $amount_guests,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'phone' => $phone,
            'email' => $email,
            'selected_dates' => $selected_dates,
            'total_price' => $total_price,
            'old_price' => $old_price,
            'addons' => $addons
        );

        if ($summary == 1) {
            try {
                $this->pdf->register($data);
            } catch (Exception $e) {
                error_log(print_r($e->getMessage(), true));
            }
            return $this->return_json('success', $this->pdf->output_pdf());
        } elseif ($summary == 2) {
            // With Pre_booking
//            $args = array(
//                'post_title' => $first_name . ' ' . $last_name,
//                'post_author' => 1,
//                'post_status' => 'publish',
//                'post_type' => 'booking_list',
//                'meta_input' => array(
//                    $this->data_prefix . 'status' => '3',
//                    $this->data_prefix . 'check_in' => $check_in,
//                    $this->data_prefix . 'check_out' => $check_out,
//                    $this->data_prefix . 'first_name' => $first_name,
//                    $this->data_prefix . 'selected_dates' => $selected_dates,
//                    $this->data_prefix . 'last_name' => $last_name,
//                    $this->data_prefix . 'phone' => $phone,
//                    $this->data_prefix . 'email' => $email,
//                    $this->data_prefix . 'booking_type' => $booking_type,
//                    $this->data_prefix . 'amount_guests' => $amount_guests,
//                    $this->data_prefix . 'total_price' => $total_price,
//                    $this->data_prefix . 'old_price' => $old_price,
//                    $this->data_prefix . 'old_calendar_price' => $old_calendar_price,
//                    $this->data_prefix . 'pay_status' => '1',
//                    $this->data_prefix . 'booked_unix_timestamp' => $booked_unix_timestamp,
//                    $this->data_prefix . 'addons' => $addons
//                )
//            );
//
//            $postID = wp_insert_post($args);
//
//            if ($postID) {
//                try {
//                    $this->mail->register($data);
//                } catch (Exception $e) {
//                    error_log(print_r($e->getMessage(), true));
//                }
//                return $this->return_json('success');
//            }

            // Without Pre-Booking
            try {
                $this->mail->register($data);
            } catch (Exception $e) {
                error_log(print_r($e->getMessage(), true));
            }
            return $this->return_json('success');
        }

        return $this->return_json('error');
    }

    public function return_json($status, $data = "")
    {
        $return = array(
            'status' => $status,
            'data' => $data,
            'bookingStatus' => $this->getAllBookingStatus()
        );
        wp_send_json($return);

        wp_die();
    }

    function sbp_mail_from_name($name)
    {
        return $name;
    }

    function sbp_custom_wp_mail_from($original_email_address)
    {
        //Make sure the email is from the same domain
        //as your website to avoid being marked as spam.
        return $original_email_address;
    }

    public function remove_pdf_files()
    {
        $this->delete_directory($this->upload_path . '/sbp/');
    }
}