<?php

namespace WDRT\App;

use WDRT\App\Controller\Main;
use WDR\Core\Helpers\Input;

defined("ABSPATH") or die();

class Router
{

    /**
     * Init plugin by adding hooks.
     */
    static function init()
    {
        if (is_admin()) {
            remove_all_actions('admin_notices');
            add_action('wdr_addons_page', [Main::class, 'managePages'], 10, 1);
            add_action('admin_enqueue_scripts', [Main::class, 'adminScripts']);
            //loco translate
            add_filter('loco_extracted_template', array(Main::class, 'addCustomString'), 10, 2);
            //wpml
            add_action('wp_ajax_wdrt_add_dynamic_string', array(Main::class, 'addWPMLCustomString'));
        }
    }
}