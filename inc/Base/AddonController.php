<?php
/**
 * @package  Snowfall Booking Plugin
 */

namespace SnowfallBooking\Base;

use SnowfallBooking\Api\SettingsApi;
use SnowfallBooking\Base\BaseController;
use SnowfallBooking\Api\Callbacks\AddonCallbacks;
use SnowfallBooking\Api\Callbacks\AdminCallbacks;
use SnowfallBooking\Api\Callbacks\ManagerCallbacks;

/**
 *
 */
class AddonController extends BaseController
{
    public $settings;

    public $callbacks;

    public $addon_callbacks;

    public $callbacks_mngr;

    public $subpages = array();

    public $custom_post_types = array();

    public $prefix;

    public function register()
    {
        $this->settings = new SettingsApi();

        $this->callbacks = new AdminCallbacks();

        $this->addon_callbacks = new AddonCallbacks();

        $this->callbacks_mngr = new ManagerCallbacks();

        $this->setSubpages();

        $this->setSettings();

        $this->setSections();

        $this->setFields();

        $this->settings->addSubMenuPages($this->subpages)->register();

        add_action('wp_ajax_set_addons_order', array($this, 'set_addons_order'));
        add_action('wp_ajax_nopriv_set_addons_order', array($this, 'set_addons_order'));
    }

    public function set_addons_order()
    {
        $options = get_option('sbp_addons') ?: array();
        if (isset($_POST['array']) && !empty($_POST['array'])) {
            $arr = $_POST['array'];
            foreach ($arr as $key => $val) {
                $options[$key]['order'] = $val;
            }

            foreach ($options as $id => $value) {
                $order[$id] = $value['order'];
            }
            $keys = array_keys($options);
            array_multisort(
                $order, SORT_ASC, SORT_NUMERIC, $options, $keys
            );
            $results = array_combine($keys, $options);

            // do this for addonsSanitize() callbacks
            $results = array(
                'ajax_request' => $results
            );

            $status = update_option('sbp_addons', $results);

            if ($status) {
                $this->return_json('success', 'The addons order has been updated!');
            } else {
                $this->return_json('error', 'The addons order hasn\'t been updated!');
            }

        }
    }

    public function return_json($status, $data = "")
    {
        $return = array(
            'status' => $status,
            'data' => $data,
        );
        wp_send_json($return);

        wp_die();
    }

    public function setSubpages()
    {
        $this->subpages = array(
            array(
                'parent_slug' => 'snowfall_booking_plugin',
                'page_title' => __('Settings panel', SBP),
                'menu_title' => __('Settings panel', SBP),
                'capability' => 'manage_options',
                'menu_slug' => 'sbp_settings_panel',
                'callback' => array($this->callbacks, 'adminAddons')
            )
        );
    }

    public function setSettings()
    {
        $args = array(
            array(
                'option_group' => 'sbp_addons_settings',
                'option_name' => 'sbp_addons',
                'callback' => array($this->addon_callbacks, 'addonsSanitize')
            ),
            array(
                'option_group' => 'sbp_btn_settings',
                'option_name' => 'sbp_btn',
                'callback' => array($this->callbacks_mngr, 'btnSanitize')
            ),
            array(
                'option_group' => 'sbp_mail_settings',
                'option_name' => 'sbp_mail',
                'callback' => array($this->callbacks_mngr, 'mailSanitize')
            )
        );

        $this->settings->setSettings($args);
    }

    public function setSections()
    {
        $args = array(
            array(
                'id' => 'sbp_addons_index',
                'title' => __('Add-on Manager', SBP),
                'callback' => array($this->addon_callbacks, 'addonsSectionManager'),
                'page' => 'sbp_settings_panel'
            ),
            array(
                'id' => 'sbp_admin_btn',
                'title' => __('All info is send to', SBP),
                'callback' => array($this->callbacks_mngr, 'adminSectionBtnManager'),
                'page' => 'sbp_btn_panel'
            ),
            array(
                'id' => 'sbp_admin_mail',
                'title' => __('Mail Manager', SBP),
                'callback' => array($this->callbacks_mngr, 'adminSectionMailManager'),
                'page' => 'sbp_mail_panel'
            )
        );

        $this->settings->setSections($args);
    }

    public function setFields()
    {
        $args = array(
            array(
                'id' => 'addon_id',
                'title' => __('Add-on ID', SBP),
                'callback' => array($this->addon_callbacks, 'idField'),
                'page' => 'sbp_settings_panel',
                'section' => 'sbp_addons_index',
                'args' => array(
                    'option_name' => 'sbp_addons',
                    'label_for' => 'addon_id',
                    'placeholder' => '',
                    'class' => 'hidden'
                )
            ),
            array(
                'id' => 'addon_name',
                'title' => __('Name', SBP),
                'callback' => array($this->addon_callbacks, 'textField'),
                'page' => 'sbp_settings_panel',
                'section' => 'sbp_addons_index',
                'args' => array(
                    'option_name' => 'sbp_addons',
                    'label_for' => 'addon_name',
                    'placeholder' => ''
                )
            ),
            array(
                'id' => 'addon_price',
                'title' => __('Price', SBP),
                'callback' => array($this->addon_callbacks, 'numberField'),
                'page' => 'sbp_settings_panel',
                'section' => 'sbp_addons_index',
                'args' => array(
                    'option_name' => 'sbp_addons',
                    'label_for' => 'addon_price',
                    'placeholder' => '0'
                )
            ),
            array(
                'id' => 'corporate',
                'title' => __('Corporate', SBP),
                'callback' => array($this->addon_callbacks, 'checkboxField'),
                'page' => 'sbp_settings_panel',
                'section' => 'sbp_addons_index',
                'args' => array(
                    'option_name' => 'sbp_addons',
                    'label_for' => 'corporate',
                    'class' => 'ui-toggle'
                )
            ),
            array(
                'id' => 'private',
                'title' => __('Private', SBP),
                'callback' => array($this->addon_callbacks, 'checkboxField'),
                'page' => 'sbp_settings_panel',
                'section' => 'sbp_addons_index',
                'args' => array(
                    'option_name' => 'sbp_addons',
                    'label_for' => 'private',
                    'class' => 'ui-toggle'
                )
            ),
            array(
                'id' => 'per_guest',
                'title' => __('Per Guest', SBP),
                'callback' => array($this->addon_callbacks, 'checkboxField'),
                'page' => 'sbp_settings_panel',
                'section' => 'sbp_addons_index',
                'args' => array(
                    'option_name' => 'sbp_addons',
                    'label_for' => 'per_guest',
                    'class' => 'ui-toggle'
                )
            ),
            array(
                'id' => 'short_info',
                'title' => __('Short Information :', SBP),
                'callback' => array($this->addon_callbacks, 'wysiwygField'),
                'page' => 'sbp_settings_panel',
                'section' => 'sbp_addons_index',
                'args' => array(
                    'option_name' => 'sbp_addons',
                    'label_for' => 'short_info',
                    'class' => ''
                )
            ),
            array(
                'id' => 'order',
                'title' => __('Order :', SBP),
                'callback' => array($this->addon_callbacks, 'orderField'),
                'page' => 'sbp_settings_panel',
                'section' => 'sbp_addons_index',
                'args' => array(
                    'option_name' => 'sbp_addons',
                    'label_for' => 'order',
                    'class' => 'hidden'
                )
            )
        );

        // Add Fields to Button Settings
        $args[] = array(
            'id' => 'all_info_emails',
            'title' => __('Emails', SBP),
            'callback' => array($this->callbacks_mngr, 'textEmailField'),
            'page' => 'sbp_btn_panel',
            'section' => 'sbp_admin_btn',
            'args' => array(
                'option_name' => 'sbp_btn',
                'label_for' => 'all_info_emails',
                'class' => '',
                'placeholder' => __('Email', SBP)
            )
        );
        $args[] = array(
            'id' => 'vat',
            'title' => __('VAT', SBP),
            'callback' => array($this->callbacks_mngr, 'numberField'),
            'page' => 'sbp_btn_panel',
            'section' => 'sbp_admin_btn',
            'args' => array(
                'option_name' => 'sbp_btn',
                'label_for' => 'vat',
                'class' => '',
                'placeholder' => __('12', SBP),
                'currency' => __('%', SBP),
                'show_value' => true
            )
        );
        $args[] = array(
            'id' => 'front_calendar_info',
            'title' => __('Text Box Under Calendar :', SBP),
            'callback' => array($this->callbacks_mngr, 'wysiwygField'),
            'page' => 'sbp_btn_panel',
            'section' => 'sbp_admin_btn',
            'args' => array(
                'option_name' => 'sbp_btn',
                'label_for' => 'front_calendar_info',
                'class' => ''
            )
        );

        // Add Fields to Mail Settings
        $args[] = array(
            'id' => 'email_from',
            'title' => __('From:', SBP),
            'callback' => array($this->callbacks_mngr, 'textField'),
            'page' => 'sbp_mail_panel',
            'section' => 'sbp_admin_mail',
            'args' => array(
                'option_name' => 'sbp_mail',
                'label_for' => 'email_from',
                'class' => '',
                'placeholder' => __('Ulvhäll', SBP),
                'admin_email' => $this->admin_email
            )
        );
        $args[] = array(
            'id' => 'email_subject',
            'title' => __('Subject:', SBP),
            'callback' => array($this->callbacks_mngr, 'textField'),
            'page' => 'sbp_mail_panel',
            'section' => 'sbp_admin_mail',
            'args' => array(
                'option_name' => 'sbp_mail',
                'label_for' => 'email_subject',
                'class' => '',
                'placeholder' => __('Ulvhäll Booking, Date', SBP)
            )
        );
        $args[] = array(
            'id' => 'email_logo',
            'title' => __('Email Logo:', SBP),
            'callback' => array($this->callbacks_mngr, 'fileUpload'),
            'page' => 'sbp_mail_panel',
            'section' => 'sbp_admin_mail',
            'args' => array(
                'option_name' => 'sbp_mail',
                'label_for' => 'email_logo',
                'class' => ''
            )
        );
        $args[] = array(
            'id' => 'email_confirmation',
            'title' => __('Email Introductory Text :', SBP),
            'callback' => array($this->callbacks_mngr, 'wysiwygField'),
            'page' => 'sbp_mail_panel',
            'section' => 'sbp_admin_mail',
            'args' => array(
                'option_name' => 'sbp_mail',
                'label_for' => 'email_confirmation',
                'class' => ''
            )
        );

        $this->settings->setFields($args);
    }
}