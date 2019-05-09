<?php 
/**
 * @package  Snowfall Booking Plugin
 */
namespace SnowfallBooking\Pages;

use SnowfallBooking\Api\SettingsApi;
use SnowfallBooking\Base\BaseController;
use SnowfallBooking\Api\Callbacks\AdminCallbacks;
use SnowfallBooking\Api\Callbacks\ManagerCallbacks;

class Dashboard extends BaseController
{
	public $settings;

	public $callbacks;

	public $callbacks_mngr;

	public $pages = array();

	public function register()
	{
		$this->settings = new SettingsApi();

		$this->callbacks = new AdminCallbacks();

		$this->callbacks_mngr = new ManagerCallbacks();

		$this->setPages();

		$this->setSettings();
		$this->setSections();
		$this->setFields();

		$this->settings->addPages( $this->pages )->withSubPage( __('Price Calendar', SBP) )->register();
	}

	public function setPages() 
	{
		$this->pages = array(
			array(
				'page_title' => __('Snowfall Booking Plugin', SBP),
				'menu_title' => __('Snowfall Booking', SBP),
				'capability' => 'manage_options', 
				'menu_slug' => 'snowfall_booking_plugin',
				'callback' => array( $this->callbacks, 'adminDashboard' ), 
				'icon_url' => 'dashicons-calendar-alt',
				'position' => 9
			)
		);
	}

	public function setSettings()
	{
		$args = array(
			array(
				'option_group' => 'sbp_calendar_price_settings',
				'option_name' => 'sbp_calendar_price',
				'callback' => array( $this->callbacks_mngr, 'textSanitize' )
			)
		);

		$this->settings->setSettings( $args );
	}

	public function setSections()
	{
		$args = array(
			array(
				'id' => 'sbp_calendar_price_admin',
				'title' => __('Price Calendar Manager', SBP),
				'callback' => array( $this->callbacks_mngr, 'adminSectionManager' ),
				'page' => 'sbp_calendar_price'
			)
		);

		$this->settings->setSections( $args );
	}

	public function setFields()
	{
		$args = array();

		 // Add Fields to Price Calendar
        $args[] = array(
            'id' => 'adminCalendar',
            'title' => __('Calendar', SBP),
            'callback' => array( $this->callbacks_mngr, 'calendarField' ),
            'page' => 'sbp_calendar_price',
            'section' => 'sbp_calendar_price_admin',
            'args' => array(
                'option_name' => 'sbp_calendar_price',
                'label_for' => 'adminCalendar',
                'class' => '',
                'placeholder' => ''
            )
        );

        $args[] = array(
            'id' => 'adminPrice',
            'title' => __('Price (EXCL. VAT)', SBP),
            'callback' => array( $this->callbacks_mngr, 'numberField' ),
            'page' => 'sbp_calendar_price',
            'section' => 'sbp_calendar_price_admin',
            'args' => array(
                'option_name' => 'sbp_calendar_price',
                'label_for' => 'adminPrice',
                'class' => '',
                'placeholder' => __('Price exclude VAT', SBP),
                'currency' => __('SEK', SBP)
            )
        );

		$this->settings->setFields( $args );
	}
}