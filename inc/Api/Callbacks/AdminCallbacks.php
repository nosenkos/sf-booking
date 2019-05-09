<?php 
/**
 * @package  Snowfall Booking Plugin
 */
namespace SnowfallBooking\Api\Callbacks;

use SnowfallBooking\Base\BaseController;

class AdminCallbacks extends BaseController
{
	public function adminDashboard()
	{
		return require_once( "$this->plugin_path/templates/price_calendar.php" );
	}

	public function adminAddons()
	{
		return require_once( "$this->plugin_path/templates/addons.php" );
	}

	public function shortcodePage()
	{
		return require_once( "$this->plugin_path/templates/shortcode.php" );
	}
}