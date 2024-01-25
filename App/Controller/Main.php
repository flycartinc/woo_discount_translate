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

        wp_localize_script(WDRT_PLUGIN_SLUG . '-admin', 'wdrt_localize_data', array(
            'common_nonce' => wp_create_nonce('wdrt_common_nonce'),
            'ajax_url' => admin_url('admin-ajax.php'),
        ));

    }
    public function getAppDetails($addons){
        if (!is_array($addons)){
            return $addons;
        }
        $addons['woo_discount_translate'] = [
                'name' => 'Woo Discount Translate',
                'description' => '',
                'icon_url' => 'https://cdn.jsdelivr.net/gh/flycartinc/wdr-addons@master/icons/woo_discount_translate.png',
                'product_url' => '',
                'page_url' => admin_url('admin.php?page=woo-discount-translate'),
                'settings_url' => '',
                'is_external' => true,
                'plugin_file' => 'woo-discount-translate/woo-discount-translate.php',
            ];
        return $addons;
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
        if (class_exists('WDR\Core\Models\Custom\AdminRule')){
            $admin_rule = new \WDR\Core\Models\Custom\AdminRule();
            $table_name =$admin_rule::getTableName();
            $query = "SELECT * FROM {$table_name}";
            global $wpdb;
            $rules = $wpdb->get_results($query, ARRAY_A);
            $allowed_string = array('title','description');
            foreach ($rules as $rule){
                if(!is_object($rule) || !isset($rule->discount_type)){
                    continue;
                }
                $this->getBasicTranslation($rule,$allowed_string,$new_custom_strings);
            }
        }
    }
    protected function getBasicTranslation($object, $allowed_strings, &$new_custom_strings)
    {
        if (!is_object($object) || !is_array($allowed_strings)) {
            return new \stdClass();
        }
        foreach ($allowed_strings as $key) {
            if (!in_array($key, $new_custom_strings) && isset($object->$key) && !empty($object->$key)) {
                $new_custom_strings[] = $object->$key;
            }
        }
    }
    function addWPMLCustomString()
    {
        $result = array('success'=>false,'data'=>array());
        $input_helper =new \WDR\Core\Helpers\Input();
        $nonce = $input_helper::get('wdrt_nonce');
        if (!current_user_can('manage_woocommerce') || !wp_verify_nonce($nonce, 'wdrt_common_nonce')){
            $result['data']['message'] = __('Security check validation failed', 'woo-discount-translate');
            wp_send_json($result);
        }
        if (!has_action('wpml_register_single_string')) {
            $result['data']['message'] = __('WPML translation action not found', 'woo-discount-translate');
            wp_send_json($result);
        }
        $domains = apply_filters('wdrt_dynamic_string_domain', array('woo-discount-rules'));
        foreach ($domains as $domain) {
            $new_custom_strings = $this->getDynamicStrings($domain);
            if (!empty($new_custom_strings)) {
                foreach ($new_custom_strings as $key) {
                    do_action('wpml_register_single_string', $domain, md5($key), $key);
                }
            }
        }
        $result['success'] = true;
        $result['data']['message'] = __('Update WPML translation successfully', 'woo-discount-translate');
        wp_send_json($result);
    }

}