<?php
/**
 * Created by PhpStorm.
 * User: sergeynosenko
 * Date: 26.09.2018
 * Time: 0:30
 */

namespace SnowfallBooking\Base;

use SnowfallBooking\Base\BaseController;


class i18nController extends BaseController
{

    /**
     * Load the plugin text domain for translation.
     *
     * @since    1.0.0
     */
    public function register()
    {

        add_action('plugins_loaded', array($this, 'load_plugin_textdomain'));
        add_action('after_setup_theme', array($this, 'load_theme_textdomain'));

    }

    public function load_plugin_textdomain()
    {
        load_plugin_textdomain(
            SBP,
            false,
            '/' . dirname(plugin_basename(__FILE__), 3) . '/languages/'
        );
    }

    public function load_theme_textdomain()
    {
        load_theme_textdomain(
            SBP,
            $this->plugin_path . 'languages'
        );
    }

}