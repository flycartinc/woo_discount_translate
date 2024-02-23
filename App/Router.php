<?php

namespace WDRT\App;
use WDRT\App\Controller\Main;
use WDR\Core\Helpers\Input;

defined("ABSPATH") or die();
class Router
{
    private static $main;
    function init(){
        self::$main = empty(self::$main) ? new Main() : self::$main;
        if (is_admin()){
            $view = (string)Input::get('addon', '');
            if ($view != 'woo_discount_translate'){
                return;
            }
            remove_all_actions('admin_notices');
            add_action('wdr_addons_page',[self::$main,'managePages']);
            add_action('admin_enqueue_scripts',[self::$main,'adminScripts'],100);

            //loco translate
            if (self::$main->isPluginActive('loco-translate/loco.php')){
                add_filter('loco_extracted_template', array(self::$main, 'addCustomString'), 10, 2);
            }
            //wpml
            if (self::$main->isPluginActive('wpml-string-translation/plugin.php')) {
                add_action('wp_ajax_wdrt_add_dynamic_string', array(self::$main, 'addWPMLCustomString'));
            }
        }
    }
}