<?php
/**
 * Plugin Name:         Woo Discount Rules - Multi-Lingual Compatibility - Dynamic Strings
 * Plugin URI:          https://www.flycart.org
 * Description:         This add-on used to translate dynamic string for Woo Discount Rules and related add-on.
 * Version:             1.0.0
 * Requires at least:   5.3
 * Requires PHP:        5.6
 * Author:              Flycart
 * Author URI:          https://www.flycart.org
 * Slug:                woo-discount-translate
 * Text Domain:         woo-discount-translate
 * Domain path:         /i18n/languages/
 * License:             GPL v3 or later
 * License URI:         https://www.gnu.org/licenses/gpl-3.0.html
 * WC requires at least: 4.3
 * WC tested up to:     8.0
 */

defined("ABSPATH") or die();

if (!function_exists('isWoocommerceActive')) {
    function isWoocommerceActive(){
        $active_plugins = apply_filters('active_plugins', get_option('active_plugins', array()));
        if (is_multisite()) {
            $active_plugins = array_merge($active_plugins, get_site_option('active_sitewide_plugins', array()));
        }
        return in_array('woocommerce/woocommerce.php', $active_plugins, false) || array_key_exists('woocommerce/woocommerce.php', $active_plugins);
    }
}
if (!function_exists('isDiscountRulesActive')) {
    function isDiscountRulesActive(){
        $active_plugins = apply_filters('active_plugins', get_option('active_plugins', array()));
        if (is_multisite()) {
            $active_plugins = array_merge($active_plugins, get_site_option('active_sitewide_plugins', array()));
        }
        return in_array('woo-discount-rules-pro/woo-discount-rules-pro.php', $active_plugins, false) || in_array('woo-discount-rules/woo-discount-rules.php', $active_plugins, false);
    }
}

if (!isWoocommerceActive() || !isDiscountRulesActive()) return;
defined('WDRT_PLUGIN_NAME') or define('WDRT_PLUGIN_NAME', 'Woo Discount Rules - Multi-Lingual Compatibility - Dynamic Strings');
defined('WDRT_PLUGIN_VERSION') or define('WDRT_PLUGIN_VERSION', '1.0.0');
defined('WDRT_PLUGIN_SLUG') or define('WDRT_PLUGIN_SLUG', 'woo-discount-translate');
defined('WDRT_PLUGIN_URL') or define('WDRT_PLUGIN_URL', plugin_dir_url(__FILE__));
defined('WDRT_PLUGIN_PATH') or define('WDRT_PLUGIN_PATH', __DIR__ . '/');

/*add_action('before_woocommerce_init', function () {
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
    }
});*/
if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
    return;
}
require __DIR__ . '/vendor/autoload.php';

if (class_exists(\WDRT\App\Router::class)){
    /*$updateChecker = \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
        'https://github.com/woo-discount-translate/woo-discount-translate',
        __FILE__,
        'woo-discount-translate'
    );*/
//    $updateChecker->getVcsApi()->enableReleaseAssets();
    $plugin = new \WDRT\App\Router();
    if (method_exists($plugin, 'init')) $plugin->init();
}

