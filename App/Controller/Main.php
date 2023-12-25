<?php

namespace WDRT\App\Controller;

use WDR\Core\Helpers\Input;
use WDR\Core\Helpers\Template;

defined("ABSPATH") or die();
class Main
{
    protected static $active_plugin_list = array();

    function adminMenu()
    {
        if (current_user_can('manage_woocommerce')) {
            add_menu_page(__('Woo Discount Translate', 'woo-discount-translate'), __('Woo Discount Translate', 'woo-discount-translate'), 'manage_woocommerce', WDRT_PLUGIN_SLUG, [$this,'managePages'], 'dashicons-megaphone', 57);
        }
    }
    public function managePages()
    {
        $view = (string)Input::get('view', 'wdr-translate');
        $params = array(
            'current_view' => $view,
            'is_wpml_translate_string_available' => $this->isPluginActive('wpml-string-translation/plugin.php')
        );
        $path = WDRT_PLUGIN_PATH . 'App/Views/Admin/main.php';
        $template = new Template();
        $template->setData($path, $params)->display();
    }
    public function adminScripts(){
        if (!current_user_can('manage_woocommerce')) {
            return;
        }
        /*$suffix = '.min';
        if (defined('SCRIPT_DEBUG')) {
            $suffix = SCRIPT_DEBUG ? '' : '.min';
        }*/
        $suffix = '';
        remove_all_actions('admin_notices');

        wp_enqueue_style(WDR_PLUGIN_SLUG . '-alertify', WDR_PLUGIN_URL . 'assets/Admin/Css/alertify' . $suffix . '.css', array(), WDR_PLUGIN_VERSION . '&t=' . time());
        wp_enqueue_script(WDR_PLUGIN_SLUG . '-alertify', WDR_PLUGIN_URL . 'assets/Admin/Js/alertify' . $suffix . '.js', array('jquery'), WDR_PLUGIN_VERSION . '&t=' . time());

        wp_enqueue_style(WDRT_PLUGIN_SLUG . '-admin', WDRT_PLUGIN_URL . 'Assets/Css/wdrt_admin.css', array(), WDRT_PLUGIN_VERSION . '&t=' . time());
        wp_enqueue_script(WDRT_PLUGIN_SLUG . '-admin', WDRT_PLUGIN_URL . 'Assets/Js/wdrt_admin'.$suffix.'.js', array('jquery'), WDRT_PLUGIN_VERSION . '&t=' . time());

    }

    protected function getActivePlugins()
    {
        if (empty(self::$active_plugin_list)) {
            $active_plugins = apply_filters('active_plugins', get_option('active_plugins', array()));
            if (is_multisite()) {
                $active_plugins = array_merge($active_plugins, get_site_option('active_sitewide_plugins', array()));
            }
            self::$active_plugin_list = $active_plugins;
        }
        return self::$active_plugin_list;
    }

    public function isPluginActive($plugin = '')
    {
        if (empty($plugin) || !is_string($plugin)) {
            return false;
        }
        $active_plugins = $this->getActivePlugins();
        if (in_array($plugin, $active_plugins, false) || array_key_exists($plugin, $active_plugins)) {
            return true;
        }
        return false;
    }

    function addCustomString(\Loco_gettext_Extraction $extraction, $domain)
    {
        $new_custom_strings = $this->getDynamicStrings($domain);
        if (!empty($new_custom_strings)) {
            foreach ($new_custom_strings as $key) {
                $custom = new \Loco_gettext_String($key);
                $extraction->addString($custom, $domain);
            }
        }
    }

    function getDynamicStrings($domain)
    {
        $new_custom_strings = array();
        $new_custom_strings = apply_filters('wdrt_dynamic_string_list', $new_custom_strings, $domain);
        if ('woo-discount-rules' === $domain) {
            // campaign label
            $this->getRuleStrings($new_custom_strings);
            $this->getSettingsStrings($new_custom_strings);
        }
        return $new_custom_strings;
    }

    function getSettingsStrings(&$new_custom_strings)
    {
        $options = get_option('wdr_settings');
        if (!is_array($options)) return;

        $allowed_strings = array('you_saved_text', 'table_title_column_name', 'table_discount_column_name', 'table_range_column_name',
            'free_shipping_title', 'discount_label_for_combined_discounts', 'apply_cart_discount_as', 'show_strikeout_when',
        );
        foreach ($allowed_strings as $key) {
            if (isset($options[$key]) && !empty($options[$key])) {
                $new_custom_strings[] = $options[$key];
            }
        }

    }

    function getRuleStrings(&$new_custom_strings)
    {

    }

    function addWPMLCustomString()
    {

    }

}