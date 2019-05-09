<?php
/**
 * Created by PhpStorm.
 * User: sergeynosenko
 * Date: 24.09.2018
 * Time: 4:56
 */

namespace SnowfallBooking\Api\Callbacks;

use SnowfallBooking\Base\BaseController;

class BookingListCallbacks extends BaseController
{
    public function downloadPage()
    {
        return require_once( "$this->plugin_path/templates/member-download.php" );
    }
}