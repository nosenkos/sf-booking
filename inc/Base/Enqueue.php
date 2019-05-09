<?php
/**
 * @package  Snowfall Booking Plugin
 */

namespace SnowfallBooking\Base;

use SnowfallBooking\Base\BaseController;
use SnowfallBooking\Base\AddonController;

/**
 *
 */
class Enqueue extends BaseController
{
    public function register()
    {
        add_action('admin_enqueue_scripts', array($this, 'enqueue'));

        /********* Enqueue Scripts Front-End ***********/
        add_action('wp_enqueue_scripts', array($this, 'front_enqueue'));
    }

    function enqueue()
    {
        // enqueue all our scripts
        wp_enqueue_script('media-upload');
        wp_enqueue_media();
        global $pagenow;

        if ('admin.php' === $pagenow && isset($_GET['page']) && 'snowfall_booking_plugin' === $_GET['page'] ||
            'edit.php' === $pagenow && isset($_GET['post_type']) && 'booking_list' === $_GET['post_type'] ||
            'post.php' === $pagenow && isset($_GET['post']) && 'booking_list' === get_post_type($_GET['post'])) {
            wp_enqueue_style('bts-css', 'https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css');
            wp_enqueue_style('form-css', $this->plugin_url . 'assets/form.css');

            wp_enqueue_script('jquery', 'http://code.jquery.com/jquery-3.3.1.slim.min.js', array(), null, true);
            wp_enqueue_script('bts-js', 'https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.min.js', array(), null, true);
        }
        wp_enqueue_style('mypluginstyle', $this->plugin_url . 'assets/mystyle.css');

        wp_enqueue_script('mypluginscript', $this->plugin_url . 'assets/myscript.js');
        wp_localize_script('mypluginscript', 'wp_admin_data', array(
            'admin_ajax' => admin_url('admin-ajax.php')
        ));

        wp_enqueue_script('sbp-calendar', $this->plugin_url . 'assets/calendar.js', array(), null, true);
        wp_localize_script('sbp-calendar', 'wp_calendar', array(
            'calendarPrice' => get_option('sbp_calendar_price'),
            'bookingStatus' => $this->getAllBookingStatus(),
            'allAddons' => get_option('sbp_addons'),
            'vat' => isset(get_option('sbp_btn')['vat']) && !empty(get_option('sbp_btn')['vat']) ? get_option('sbp_btn')['vat']/100 : '0.12',
            'language' => get_user_locale()
        ));
    }

    function front_enqueue()
    {
        global $post;

        if (has_shortcode($post->post_content, 'snowfall_booking')) {
            // enqueue all our scripts
            wp_enqueue_style('bts-css', 'https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css');
            wp_enqueue_style('form-css', $this->plugin_url . 'assets/form.css');
            wp_enqueue_style('tn_new_btn_plugin', $this->plugin_url . 'assets/front-style.css');

            wp_enqueue_script('jquery', 'http://code.jquery.com/jquery-3.3.1.slim.min.js', array(), null, true);
            wp_enqueue_script('bts-js', 'https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.min.js', array(), null, true);

            wp_enqueue_script('sbp-calendar-front', $this->plugin_url . 'assets/calendar-front-end.js', array(), null, true);
            wp_localize_script('sbp-calendar-front', 'wp_calendar', array(
                'calendarPrice' => get_option('sbp_calendar_price'),
                'bookingStatus' => $this->getAllBookingStatus(),
                'allAddons' => get_option('sbp_addons'),
                'cancel' => __('Cancel', SBP),
                'submit' => __('Get a price estimate', SBP),
                'vat' => isset(get_option('sbp_btn')['vat']) && !empty(get_option('sbp_btn')['vat']) ? get_option('sbp_btn')['vat']/100 : '0.12',
                'language' => ICL_LANGUAGE_CODE
            ));
        }
    }
}