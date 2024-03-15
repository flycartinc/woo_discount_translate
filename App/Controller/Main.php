<?php

namespace WDRT\App\Controller;

use WDR\Core\Helpers\Input;
use WDR\Core\Helpers\Util;

defined("ABSPATH") or die();

class Main
{
    /**
     * Add addon menu page.
     *
     * @return void
     */
    public static function managePages($addon = '')
    {
        if ($addon != 'woo_discount_translate') return;
        Util::renderTemplate(WDRT_PLUGIN_PATH . 'App/Views/Admin/main.php', array(
            'is_wpml_translate_string_available' => (new \WDR\Core\Helpers\Plugin())::isActive('wpml-string-translation/plugin.php')
        ));
    }

    /**
     * Loading site scripts and styles.
     *
     * @return void
     */
    public static function adminScripts()
    {
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
        wp_enqueue_script(WDRT_PLUGIN_SLUG . '-admin', WDRT_PLUGIN_URL . 'Assets/Js/wdrt_admin' . $suffix . '.js', array('jquery'), WDRT_PLUGIN_VERSION . '&t=' . time());

        wp_localize_script(WDRT_PLUGIN_SLUG . '-admin', 'wdrt_localize_data', array(
            'common_nonce' => wp_create_nonce('wdrt_common_nonce'),
            'ajax_url' => admin_url('admin-ajax.php'),
        ));

    }

    /**
     * Add strings to loco translate.
     *
     * @param \Loco_gettext_Extraction $extraction Loco translate object.
     * @param string $domain Text domain.
     * @return void
     */
    function addCustomString(\Loco_gettext_Extraction $extraction, $domain)
    {
        $plugin = new \WDR\Core\Helpers\Plugin();
        if ($plugin::isActive('loco-translate/loco.php')) {
            return;
        }
        $new_custom_strings = $this->getDynamicStrings($domain);
        if (empty($new_custom_strings)) {
            return;
        }
        foreach ($new_custom_strings as $key) {
            $custom = new \Loco_gettext_String($key);
            $extraction->addString($custom, $domain);
        }
    }

    /**
     * Get discount rules dynamic strings.
     *
     * @param string $domain Text domain.
     * @return mixed
     */
    function getDynamicStrings($domain)
    {
        $new_custom_strings = array();
        $new_custom_strings = apply_filters('wdrt_dynamic_string_list', $new_custom_strings, $domain);
        if ('woo-discount-rules' === $domain) {
            $this->getRuleStrings($new_custom_strings);
            $this->getSettingsStrings($new_custom_strings);
        }
        return $new_custom_strings;
    }

    /**
     * Getting discount rules settings strings.
     *
     * @param array $new_custom_strings Custom strings.
     * @return void
     */
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

    /**
     * Getting rules strings.
     *
     * @param array $new_custom_strings Text domain.
     * @return void
     */
    function getRuleStrings(&$new_custom_strings)
    {
        if (!class_exists('WDR\Core\Models\Custom\AdminRule')) {
            return;
        }
        $admin_rule = new \WDR\Core\Models\Custom\AdminRule();
        $table_name = $admin_rule::getTableName();
        $query = "SELECT * FROM {$table_name}";
        global $wpdb;
        $rules = $wpdb->get_results($query);
        $allowed_string = array('title', 'description');
        foreach ($rules as $rule) {
            if (!is_object($rule)) {
                continue;
            }
            $this->getBasicTranslation($rule, $allowed_string, $new_custom_strings);
        }

    }

    /**
     * Add custom strings.
     *
     * @param object $object Rule object.
     * @param array $allowed_strings Strings list.
     * @param array $new_custom_strings Custom strings.
     * @return \stdClass|void
     */
    function getBasicTranslation($object, $allowed_strings, &$new_custom_strings)
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

    /**
     * Add stings to WPML.
     *
     * @return void
     */
    static function addWPMLCustomString()
    {
        $result = array('success' => false, 'data' => array());
        $plugin = new \WDR\Core\Helpers\Plugin();
        if (!$plugin::isActive('wpml-string-translation/plugin.php')) {
            $result['data']['message'] = __('WPML string translation plugin is not activated.', 'woo-discount-translate');
            wp_send_json($result);
        }
        $input_helper = new \WDR\Core\Helpers\Input();
        $nonce = $input_helper::get('wdrt_nonce');
        if (!current_user_can('manage_woocommerce') || !wp_verify_nonce($nonce, 'wdrt_common_nonce')) {
            $result['data']['message'] = __('Security check validation failed', 'woo-discount-translate');
            wp_send_json($result);
        }
        if (!has_action('wpml_register_single_string')) {
            $result['data']['message'] = __('WPML translation action not found', 'woo-discount-translate');
            wp_send_json($result);
        }
        $domains = apply_filters('wdrt_dynamic_string_domain', array('woo-discount-rules'));
        foreach ($domains as $domain) {
            $new_custom_strings = (new Main())->getDynamicStrings($domain);
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