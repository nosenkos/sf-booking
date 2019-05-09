<?php
/**
 * @package  Snowfall Booking Plugin
 */

namespace SnowfallBooking\Base;

use Braintree\Exception;
use SnowfallBooking\Api\SettingsApi;
use SnowfallBooking\Base\BaseController;
use SnowfallBooking\Api\Callbacks\BookingListCallbacks;
use SnowfallBooking\Base\MailController;
use SnowfallBooking\Base\AddonController;

/**
 *
 */
class BookingListController extends BaseController
{
    public $settings;

    public $callbacks;

    public $subpages = array();

    public function register()
    {
        $this->settings = new SettingsApi();

        $this->callbacks = new BookingListCallbacks();

        $this->setDownloadPage();

        global $show_meta;
        $show_meta = true;

        add_action('old_booking', array($this, 'do_old_booking'));

        add_action('init', array($this, 'booking_list_cpt'));
        add_action('manage_posts_extra_tablenav', array($this, 'wpa_admin_filter'), 10, 1);
        add_action('admin_notices', array($this, 'wpa_admin_notice'), 10, 1);

        add_filter('views_edit-booking_list', array($this, 'subsubsub_booking_list'));
        add_filter('display_post_states', array($this, 'custom_display_post_states'), 10, 2);
        add_filter('parse_query', array($this, 'subsubsub_paid_booking_list'), 10, 1);


        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_meta_box'));
        add_action('manage_booking_list_posts_columns', array($this, 'set_custom_columns'));
        add_action('manage_booking_list_posts_custom_column', array($this, 'set_custom_columns_data'), 10, 2);

        add_filter('manage_edit-booking_list_sortable_columns', array($this, 'set_custom_columns_sortable'));
        add_filter('request', array($this, 'booking_list_column_orderby'));

        $this->settings->addSubMenuPages($this->subpages)->register();

        add_action('wp_ajax_submit_booking', array($this, 'submit_booking'));
        add_action('wp_ajax_nopriv_submit_booking', array($this, 'submit_booking'));
    }

    public function submit_booking()
    {
        if (!DOING_AJAX || !check_ajax_referer('sbp-nonce', 'nonce')) {
            return $this->return_json('error');
        }

        $status = sanitize_text_field($_POST['status']);
        $check_in = sanitize_text_field($_POST['check_in']);
        $check_out = sanitize_text_field($_POST['check_out']);
        $selected_dates = sanitize_text_field($_POST['selected_dates']);
        $first_name = sanitize_text_field($_POST['first_name']);
        $last_name = sanitize_text_field($_POST['last_name']);
        $phone = sanitize_text_field($_POST['phone']);
        $email = sanitize_email($_POST['email']);
        $booking_type = sanitize_text_field($_POST['booking_type']);
        $amount_guests = sanitize_text_field($_POST['amount_guests']);
        $total_price = sanitize_text_field($_POST['total_price']);
        $old_price = sanitize_text_field($_POST['old_price']);
        $old_calendar_price = sanitize_text_field($_POST['old_calendar_price']);
        $pay_status = sanitize_text_field($_POST['pay_status']);
        $booked_unix_timestamp = sanitize_text_field($_POST['booked_unix_timestamp']);
        $addons = $_POST['addons'];

        $args = array(
            'post_title' => $first_name . ' ' . $last_name,
            'post_author' => 1,
            'post_status' => 'publish',
            'post_type' => 'booking_list',
            'meta_input' => array(
                $this->data_prefix . 'status' => $status,
                $this->data_prefix . 'check_in' => $check_in,
                $this->data_prefix . 'check_out' => $check_out,
                $this->data_prefix . 'first_name' => $first_name,
                $this->data_prefix . 'selected_dates' => $selected_dates,
                $this->data_prefix . 'last_name' => $last_name,
                $this->data_prefix . 'phone' => $phone,
                $this->data_prefix . 'email' => $email,
                $this->data_prefix . 'booking_type' => $booking_type,
                $this->data_prefix . 'amount_guests' => $amount_guests,
                $this->data_prefix . 'total_price' => $total_price,
                $this->data_prefix . 'old_price' => $old_price,
                $this->data_prefix . 'old_calendar_price' => $old_calendar_price,
                $this->data_prefix . 'pay_status' => $pay_status,
                $this->data_prefix . 'booked_unix_timestamp' => $booked_unix_timestamp,
                $this->data_prefix . 'addons' => $addons
            )
        );

        $postID = wp_insert_post($args);

        if ($postID) {
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

    public function setDownloadPage()
    {
        $this->subpages = array(
            array(
                'parent_slug' => 'snowfall_booking_plugin',
                'page_title' => __('Download', SBP),
                'menu_title' => __('Download', SBP),
                'capability' => 'manage_options',
                'menu_slug' => 'sbp-booking-download-list',
                'callback' => array($this->callbacks, 'downloadPage')
            )
        );
    }

    public function booking_list_cpt()
    {
        $labels = array(
            'name' => __('Booking List', SBP),
            'singular_name' => __('Booking List', SBP)
        );

        $args = array(
            'labels' => $labels,
            'public' => true,
            'has_archive' => false,
            'menu_icon' => 'dashicons-testimonial',
            'exclude_from_search' => true,
            'publicly_queryable' => false,
            'supports' => array('title', 'comments'),
            'show_in_menu' => 'snowfall_booking_plugin',
            'menu_position' => 10,
            'capabilities' => array(
                'create_posts' => 'do_not_allow', // false < WP 4.5, credit @Ewout
            ),
            'map_meta_cap' => true,
        );

        register_post_type('booking_list', $args);
    }

    function wpa_admin_filter($which)
    {
        $screen = get_current_screen();
        if ('booking_list' == $screen->post_type
            && 'edit' == $screen->base && $which == 'top') {
            ?>
            <button type="button" class="sbp-registration" data-toggle="modal"
                    data-target="#sbp-form"><?php echo __('+ New Booking', SBP); ?></button>
            <?php
        }
    }

    function wpa_admin_notice()
    {
        $screen = get_current_screen();
        if ('booking_list' == $screen->post_type
            && 'edit' == $screen->base) {
            $screen = get_current_screen();
            if ('booking_list' == $screen->post_type
                && 'edit' == $screen->base) {
                require_once(plugin_dir_path(dirname(__FILE__, 2)) . 'templates/contact-form.php');
            }
        }
    }

    public function add_meta_boxes()
    {
        add_meta_box(
            'booking_info',
            __('Booking Information', SBP),
            array($this, 'render_member_box'),
            'booking_list',
            'normal',
            'high'
        );
    }

    public function render_member_box($post)
    {
        wp_nonce_field('sbp_booking', 'sbp_booking_nonce');

        $first_name = get_post_meta($post->ID, $this->data_prefix . 'first_name', true);
        $first_name = isset($first_name) ? $first_name : '';

        $last_name = get_post_meta($post->ID, $this->data_prefix . 'last_name', true);
        $last_name = isset($last_name) ? $last_name : '';

        $phone = get_post_meta($post->ID, $this->data_prefix . 'phone', true);
        $phone = isset($phone) ? $phone : '';

        $email = get_post_meta($post->ID, $this->data_prefix . 'email', true);
        $email = isset($email) ? $email : '';

        $check_in = get_post_meta($post->ID, $this->data_prefix . 'check_in', true);

        $check_out = get_post_meta($post->ID, $this->data_prefix . 'check_out', true);

        $booking_status = get_post_meta($post->ID, $this->data_prefix . 'status', true);

        $selected_dates = get_post_meta($post->ID, $this->data_prefix . 'selected_dates', true);

        $booking_type = get_post_meta($post->ID, $this->data_prefix . 'booking_type', true);

        $amount_guests = get_post_meta($post->ID, $this->data_prefix . 'amount_guests', true);

        $addons = get_post_meta($post->ID, $this->data_prefix . 'addons', true);

        $pay_status = get_post_meta($post->ID, $this->data_prefix . 'pay_status', true);

        $total_price = get_post_meta($post->ID, $this->data_prefix . 'total_price', true);
        $total_price = isset($total_price) && !empty($total_price) ? $total_price : '0';

        $old_price = get_post_meta($post->ID, $this->data_prefix . 'old_price', true);
        $old_price = isset($old_price) && !empty($old_price) ? $old_price : '0';

        $old_calendar_price = get_post_meta($post->ID, $this->data_prefix . 'old_calendar_price', true);
        $old_calendar_price = isset($old_calendar_price) && !empty($old_calendar_price) ? $old_calendar_price : '0';


        $booked_unix_timestamp = get_post_meta($post->ID, $this->data_prefix . 'booked_unix_timestamp', true);
        $booked_unix_timestamp = isset($booked_unix_timestamp) && !empty($booked_unix_timestamp) ? $booked_unix_timestamp : '';

        ?>
        <p>
            <label class="meta-label"
                   for="sbp_booking_list_calendar"><?php echo __('Calendar', SBP); ?></label>
            <input type="text" id="sbp_booking_list_calendar" name="sbp_booking_list_calendar" class="widefat"
                   value="<?php echo esc_attr($check_in); ?>">
            <input type="hidden" id="sbp_booking_list_check_out" name="sbp_booking_list_check_out" class="widefat"
                   value="<?php echo esc_attr($check_out); ?>">
            <input type="hidden" id="sbp_booking_list_selected_dates" name="sbp_booking_list_selected_dates"
                   class="widefat"
                   value="<?php echo esc_attr($selected_dates); ?>">
            <small class="field-msg error hidden"
                   data-error="invalidCheckIn"><?php echo __('Selected dates should be with price', SBP); ?></small>
            <small class="field-msg error hidden"
                   data-error="invalidCheckInBooked"><?php echo __('Selected dates are booked!', SBP); ?></small>
        </p>
        <div class="ui-toggle can-toggle vat">
            <span><?=__('EXCL.', SBP);?></span>
            <input type="checkbox" id="vat-status" <?php echo ($booking_type == 3) ? 'checked' : ''; ?>>
            <label for="vat-status">
                <div></div>
            </label>
            <span><?=__('INCL.', SBP);?></span>
        </div>

        <hr>

        <p>
            <label class="meta-label"
                   for="sbp_booking_list_status"><?php echo __('Status', SBP); ?></label>
            <select name="sbp_booking_list_status" id="sbp_booking_list_status" class="form-control widefat">
                <option value="2" <?= ($booking_status == 2) ? 'selected="selected"' : ''; ?>><span
                            class="booked"><?php echo __('Booked', SBP); ?></span></option>
                <option value="3" <?= ($booking_status == 3) ? 'selected="selected"' : ''; ?>><span
                            class="pre-booked"><?php echo __('Pre-booked', SBP); ?></span>
                </option>
                <option value="4" <?= ($booking_status == 4) ? 'selected="selected"' : ''; ?>><span
                            class="blocked"><?php echo __('Blocked', SBP); ?></span></option>
            </select>
        </p>

        <hr>

        <div class="form-row">
            <div class="col-md-6">
                <p>
                    <label class="meta-label"
                           for="sbp_booking_list_first_name"><?php echo __('First Name', SBP); ?></label>
                    <input type="text" id="sbp_booking_list_first_name" name="sbp_booking_list_first_name"
                           class="widefat"
                           value="<?php echo esc_attr($first_name); ?>">
                </p>
            </div>
            <div class="col-md-6">
                <p>
                    <label class="meta-label"
                           for="sbp_booking_list_last_name"><?php echo __('Last Name', SBP); ?></label>
                    <input type="text" id="sbp_booking_list_last_name" name="sbp_booking_list_last_name" class="widefat"
                           value="<?php echo esc_attr($last_name); ?>">
                </p>
            </div>
            <div class="col-md-6">
                <p>
                    <label class="meta-label"
                           for="sbp_booking_list_email"><?php echo __('Email', SBP); ?></label>
                    <input type="email" id="sbp_booking_list_email" name="sbp_booking_list_email" class="widefat"
                           value="<?php echo esc_attr($email); ?>">
                </p>
            </div>
            <div class="col-md-6">
                <p>
                    <label class="meta-label"
                           for="sbp_booking_list_phone"><?php echo __('Phone number', SBP); ?></label>
                    <input type="text" id="sbp_booking_list_phone" name="sbp_booking_list_phone" class="widefat"
                           value="<?php echo esc_attr($phone); ?>">
                </p>
            </div>
        </div>

        <hr>

        <div class="form-row">
            <div class="col-md-6">
                <p>
                    <label class="meta-label"
                           for="booking_type"><?php echo __('Booking type', SBP); ?></label>
                    <select name="booking_type" id="booking_type" class="form-control widefat">
                        <option value="3" <?= ($booking_type == 3) ? 'selected="selected"' : ''; ?>><?php echo __('Private', SBP); ?></option>
                        <option value="1" <?= ($booking_type == 1) ? 'selected="selected"' : ''; ?>><?php echo __('Corporate', SBP); ?></option>
                    </select>
                </p>
            </div>
            <div class="col-md-6">
                <p>
                    <label class="meta-label"
                           for="amount_guests"><?php echo __('Amount of the guests', SBP); ?></label>
                    <input type="number" id="amount_guests" name="amount_guests" class="widefat"
                           value="<?php echo esc_attr($amount_guests); ?>">
                </p>
            </div>
        </div>

        <hr>

        <?php
        $options = get_option('sbp_addons');
        if (isset($options)) { ?>
            <div class="field-container form-group">
                <label class="meta-label"><?php echo __('Add-ons', SBP); ?></label>
                <div class="form-row addons-container">
                    <?php
                    foreach ($options as $option) {
                        if (isset($option[$this->getBookingTypeSlug($booking_type)]) && $option[$this->getBookingTypeSlug($booking_type)] == 1) {
                            ?>
                            <div class="form-group col-md-6">
                                <div class="ui-toggle">
                                    <span><?= $option['addon_name']; ?></span>
                                    <input type="checkbox" id="addon-<?= $option['addon_id']; ?>"
                                           name="addons[<?= $option['addon_id']; ?>]" value="1" class=""
                                           data-id="<?= $option['addon_id']; ?>" <?= (isset($addons[$option['addon_id']]) && $addons[$option['addon_id']] == 1 ? 'checked="checked"' : ''); ?>>
                                    <label for="addon-<?= $option['addon_id']; ?>">
                                        <div></div>
                                    </label>
                                </div>
                                <?php
                                if (isset($option['short_info']) && !empty($option['short_info']) && $option['short_info'] != '') {
                                    ?>
                                    <div class="small-text">
                                        <?php
                                        echo $option['short_info'];
                                        ?>
                                    </div>
                                    <?php
                                }
                                ?>
                            </div>

                            <?php
                        }
                    } ?>
                </div>
            </div>

            <hr>
            <?php
        }
        ?>


        <div class="form-row">
            <div class="col-md-6">
                <div class="form-group">
                    <label class=" meta-label" for="total_price"><?php echo __('Price', SBP); ?></label>
                    <p id="total_price_view"><?= number_format($total_price, 0, ',', ' '); ?> SEK</p>
                </div>
            </div>
            <div class="col-md-6">
                <p>
                    <label class="meta-label"
                           for="sbp_booking_list_pay_status"><?php echo __('Status', SBP); ?></label>
                    <select name="sbp_booking_list_pay_status" id="sbp_booking_list_pay_status"
                            class="form-control widefat">
                        <option value="1" <?= ($pay_status == 1) ? 'selected="selected"' : ''; ?>><?php echo __('Not paid', SBP); ?>
                        </option>
                        <option value="2" <?= ($pay_status == 2) ? 'selected="selected"' : ''; ?>><?php echo __('Paid', SBP); ?>
                        </option>
                    </select>
                </p>
            </div>
        </div>

        <div class="hidden">
            <input type="hidden" name="total_price" id="total_price" value="<?= $total_price; ?>">
            <input type="hidden" name="old_price" id="old_price" value="<?= $old_price; ?>">
            <input type="hidden" name="old_calendar_price" id="old_calendar_price" value="<?= $old_calendar_price; ?>">
            <input type="hidden" name="booked_unix_timestamp" id="booked_unix_timestamp"
                   value="<?= $booked_unix_timestamp; ?>">
        </div>
        <?php
    }

    public function save_meta_box($post_id)
    {
        if (!isset($_POST['sbp_booking_nonce'])) {
            return $post_id;
        }

        $nonce = $_POST['sbp_booking_nonce'];
        if (!wp_verify_nonce($nonce, 'sbp_booking')) {
            return $post_id;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return $post_id;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return $post_id;
        }


        update_post_meta($post_id, $this->data_prefix . 'first_name', sanitize_text_field($_POST['sbp_booking_list_first_name']));

        update_post_meta($post_id, $this->data_prefix . 'last_name', sanitize_text_field($_POST['sbp_booking_list_last_name']));

        update_post_meta($post_id, $this->data_prefix . 'phone', sanitize_text_field($_POST['sbp_booking_list_phone']));

        update_post_meta($post_id, $this->data_prefix . 'email', sanitize_email($_POST['sbp_booking_list_email']));

        update_post_meta($post_id, $this->data_prefix . 'status', sanitize_text_field($_POST['sbp_booking_list_status']));

        update_post_meta($post_id, $this->data_prefix . 'check_in', sanitize_text_field($_POST['sbp_booking_list_calendar']));

        update_post_meta($post_id, $this->data_prefix . 'check_out', sanitize_text_field($_POST['sbp_booking_list_check_out']));

        update_post_meta($post_id, $this->data_prefix . 'selected_dates', sanitize_text_field($_POST['sbp_booking_list_selected_dates']));

        update_post_meta($post_id, $this->data_prefix . 'booking_type', sanitize_text_field($_POST['booking_type']));

        update_post_meta($post_id, $this->data_prefix . 'amount_guests', sanitize_text_field($_POST['amount_guests']));

        update_post_meta($post_id, $this->data_prefix . 'addons', $_POST['addons']);

        update_post_meta($post_id, $this->data_prefix . 'pay_status', sanitize_text_field($_POST['sbp_booking_list_pay_status']));

        update_post_meta($post_id, $this->data_prefix . 'total_price', sanitize_text_field($_POST['total_price']));

        update_post_meta($post_id, $this->data_prefix . 'old_price', sanitize_text_field($_POST['old_price']));

        update_post_meta($post_id, $this->data_prefix . 'old_calendar_price', sanitize_text_field($_POST['old_calendar_price']));

        update_post_meta($post_id, $this->data_prefix . 'booked_unix_timestamp', sanitize_text_field($_POST['booked_unix_timestamp']));
    }

    public function set_custom_columns($columns)
    {
        $date = $columns['date'];
        $comments = $columns['comments'];
        unset($columns['title']);
        unset($columns['date']);
        unset($columns['comments']);

        $columns['title'] = 'Contact name';
        $columns[$this->data_prefix . 'booking_date'] = __('Booking Date', SBP);
        $columns[$this->data_prefix . 'booking_type'] = __('Booking type', SBP);
        $columns[$this->data_prefix . 'status'] = __('Status', SBP);
        $columns[$this->data_prefix . 'addons'] = __('Add-ons', SBP);
        $columns[$this->data_prefix . 'total_price'] = __('Total estimated price', SBP);
        $columns['date'] = $date;
        $columns['comments'] = $comments;

        return $columns;
    }

    public function set_custom_columns_data($column, $post_id)
    {
        $selected_dates = get_post_meta($post_id, $this->data_prefix . 'selected_dates', true);
        if (isset($selected_dates)):
            $selected_dates = explode(',', $selected_dates);
            if (count($selected_dates) > 1) {
                $selected_dates = $selected_dates[0] . ' — ' . array_pop($selected_dates);
            } else {
                $selected_dates = $selected_dates[0];
            }
        else:
            $selected_dates = '-';
        endif;

        $booking_status = get_post_meta($post_id, $this->data_prefix . 'status', true);
        $booking_status = isset($booking_status) ? $booking_status : '-';

        $booking_type = get_post_meta($post_id, $this->data_prefix . 'booking_type', true);
        $booking_type = isset($booking_type) ? $booking_type : '-';

        $addons = get_post_meta($post_id, $this->data_prefix . 'addons', true);
        $addons_text = "";

        if (isset($addons) && !empty($addons)) {
            $addons_text .= '<ul>';
            foreach ($addons as $key => $val) {
                $addon = $this->getAddon($key);
                if ($addon) {
                    $addons_text .= "<li>" . $addon['addon_name'] . "</li>";
                }
            }
            $addons_text .= '</ul>';
        }

        $total_price = get_post_meta($post_id, $this->data_prefix . 'total_price', true);
        $total_price = isset($total_price) ? number_format($total_price, 0, ',', ' ') : '-';

        switch ($column) {
            case $this->data_prefix . 'booking_type':
                echo $this->getBookingType($booking_type);
                break;

            case $this->data_prefix . 'status':
                echo $this->getBookingStatus($booking_status);
                break;

            case $this->data_prefix . 'booking_date':
                echo $selected_dates;
                break;

            case $this->data_prefix . 'addons':
                echo $addons_text;
                break;

            case $this->data_prefix . 'total_price':
                echo $total_price . ' SEK';
                break;
        }
    }

    public function set_custom_columns_sortable($columns)
    {

        $columns[$this->data_prefix . 'booking_date'] = $this->data_prefix . 'booking_date';

        return $columns;
    }

    public function booking_list_column_orderby($vars)
    {
        if (isset($vars['orderby']) && $this->data_prefix . 'booking_date' == $vars['orderby']) {
            $vars = array_merge($vars, array(
                'meta_key' => $this->data_prefix . 'booked_unix_timestamp',
                'orderby' => 'meta_value_num'
            ));
        }

        return $vars;
    }

    public function subsubsub_booking_list($views)
    {
        global $wp_query, $show_meta;
        unset($views['all']);
        unset($views['publish']);
        unset($views['draft']);
        unset($views['trash']);

        $types = array(
            array('status' => NULL),
            array('status' => 'publish'),
            array('status' => 'pre_booked'),
            array('status' => 'draft'),
            array('status' => 'paid'),
            array('status' => 'not_paid'),
            array('status' => 'trash')
        );
        foreach ($types as $type) {
            if ($type['status'] == 'paid' || $type['status'] == 'not_paid') {
                $query = array(
                    'post_type' => 'booking_list',
                    'post_status' => array('publish', 'draft'),
                    'meta_query' => array(
                        array(
                            'key' => $this->data_prefix . 'pay_status',
                            'value' => ($type['status'] == 'paid') ? '2' : '1',
                            'compare' => '='
                        )
                    )
                );
            } elseif ($type['status'] == 'pre_booked') {
                $query = array(
                    'post_type' => 'booking_list',
                    'post_status' => array('publish'),
                    'meta_query' => array(
                        array(
                            'key' => $this->data_prefix . 'status',
                            'value' => '3',
                            'compare' => '='
                        )
                    )
                );
            } else {
                $query = array(
                    'post_type' => 'booking_list',
                    'post_status' => $type['status'],
                    'meta_query' => array()
                );
            }

            $show_meta = false;

            $result = new \WP_Query($query);

            if ($type['status'] == 'paid'):
                $class = (isset($_GET['pay_status']) && $_GET['pay_status'] == '2') ? ' class="current"' : '';
                $views['paid'] = sprintf('<a href="%s" ' . $class . '>' . __('Paid', SBP) . ' <span class="count">(%d)</span></a>',
                    admin_url('edit.php?post_status=paid&pay_status=2&post_type=booking_list'),
                    $result->found_posts);
            elseif ($type['status'] == 'not_paid'):
                $class = (isset($_GET['pay_status']) && $_GET['pay_status'] == '1') ? ' class="current"' : '';
                $views['not_paid'] = sprintf('<a href="%s" ' . $class . '>' . __('Not Paid', SBP) . ' <span class="count">(%d)</span></a>',
                    admin_url('edit.php?post_status=not_paid&pay_status=1&post_type=booking_list'),
                    $result->found_posts);
            elseif ($type['status'] == NULL):
                $class = (!isset($wp_query->query_vars['post_status']) && !isset($_GET['pay_status'])) ? ' class="current"' : '';
                $views['all'] = sprintf('<a href="%s" %s>' . __('All', SBP) . ' <span class="count">(%d)</span></a>',
                    admin_url('edit.php?post_type=booking_list'),
                    $class,
                    $result->found_posts);
            elseif ($type['status'] == 'publish'):
                $class = (isset($wp_query->query_vars['post_status']) && $wp_query->query_vars['post_status'] == 'publish' && !isset($_GET['booking_status'])) ? ' class="current"' : '';
                $views['publish'] = sprintf('<a href="%s" ' . $class . '>' . __('Future', SBP) . ' <span class="count">(%d)</span></a>',
                    admin_url('edit.php?post_status=publish&post_type=booking_list'),
                    $result->found_posts);
            elseif ($type['status'] == 'draft'):
                $class = (isset($wp_query->query_vars['post_status']) && $wp_query->query_vars['post_status'] == 'draft') ? ' class="current"' : '';
                $views['draft'] = sprintf('<a href="%s" ' . $class . '>' . __('Past', SBP) . ((sizeof($result->posts) > 1) ? "s" : "") . ' <span class="count">(%d)</span></a>',
                    admin_url('edit.php?post_status=draft&post_type=booking_list'),
                    $result->found_posts);
            elseif ($type['status'] == 'trash'):
                $class = (isset($wp_query->query_vars['post_status']) && $wp_query->query_vars['post_status'] == 'trash') ? ' class="current"' : '';
                $views['trash'] = sprintf('<a href="%s" ' . $class . '>' . __('Trash', SBP) . ' <span class="count">(%d)</span></a>',
                    admin_url('edit.php?post_status=trash&post_type=booking_list'),
                    $result->found_posts);
            elseif ($type['status'] == 'pre_booked'):
                $class = (isset($_GET['booking_status']) && $_GET['booking_status'] == '3') ? ' class="current"' : '';
                $views['pre_booked'] = sprintf('<a href="%s" ' . $class . '>' . __('Pre-Booked', SBP) . ' <span class="count">(%d)</span></a>',
                    admin_url('edit.php?post_status=publish&booking_status=3&post_type=booking_list'),
                    $result->found_posts);
            endif;
        }
        return $views;
    }

    public function subsubsub_paid_booking_list($query)
    {
        global $pagenow, $show_meta;

        if ('edit.php' != $pagenow || !$query->is_admin)
            return $query;

        if (isset($_GET['pay_status']) && $_GET['pay_status'] != '' && $show_meta && isset($_GET['post_type']) && $_GET['post_type'] == 'booking_list') {
            $query->set('meta_key', $this->data_prefix . 'pay_status');
            $query->set('meta_query', array(
                    array(
                        'key' => $this->data_prefix . 'pay_status',
                        'value' => $_GET['pay_status']),
                    'compare' => '='
                )
            );
        } elseif (isset($_GET['booking_status']) && $_GET['booking_status'] != '' && $show_meta && isset($_GET['post_type']) && $_GET['post_type'] == 'booking_list') {
            $query->set('meta_key', $this->data_prefix . 'status');
            $query->set('meta_query', array(
                    array(
                        'key' => $this->data_prefix . 'status',
                        'value' => $_GET['booking_status']),
                    'compare' => '='
                )
            );
        }
    }

    public function custom_display_post_states($post_states, $post)
    {
        if ($post->post_type == "booking_list" && $post->post_status == "draft") {
            unset($post_states['draft']);
            $post_states[] = __('Past', SBP);
        }

        return $post_states;
    }

    public function do_old_booking()
    {
        $args = array(
            'post_type' => 'booking_list',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'meta_query' => array(
                array(
                    'key' => $this->data_prefix . 'booked_unix_timestamp',
                    'value' => mktime(0, 0, 0),
                    'compare' => '<'
                )
            )
        );
        $old_booking = new \WP_Query($args);

        if ($old_booking->have_posts()) {
            while ($old_booking->have_posts()) {
                $old_booking->the_post();
                wp_update_post(array(
                    'ID' => get_the_ID(),
                    'post_status' => 'draft'
                ));
            }
            wp_reset_postdata();
        }
    }
}