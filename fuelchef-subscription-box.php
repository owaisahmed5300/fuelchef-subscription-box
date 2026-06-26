<?php
/**
 * Plugin Name:          FuelChef Subscription Box
 * Plugin URI:           https://wptechnix.com/
 * Description:          A WooCommerce subscription box plugin for meal delivery services.
 * Version:              __VERSION__
 * Requires at least:    6.0
 * Requires PHP:         8.1
 * Author:               WPTechnix
 * Author URI:           https://wptechnix.com/
 * Text Domain:          fuelchef-subscription-box
 * License:              GPL v2 or later
 * Requires Plugins:     woocommerce
 *
 * @package FuelChefSubsBox
 */

declare(strict_types=1);

use FuelChefSubsBox\Plugin;

defined('ABSPATH') || exit();

define('FUELCHEF_SUBSCRIPTION_BOX_VERSION', '__VERSION__');
define('FUELCHEF_SUBSCRIPTION_BOX_FILE', __FILE__);
define('FUELCHEF_SUBSCRIPTION_BOX_DIR', trailingslashit(plugin_dir_path(__FILE__)));
define('FUELCHEF_SUBSCRIPTION_BOX_URL', trailingslashit(plugin_dir_url(__FILE__)));

$fuelchef_autoloader_paths = [
    FUELCHEF_SUBSCRIPTION_BOX_DIR . 'vendor-prefixed/scoper-autoload.php',
    FUELCHEF_SUBSCRIPTION_BOX_DIR . 'vendor/autoload.php',
];

$fuelchef_autoload_path = null;

foreach ($fuelchef_autoloader_paths as $fuelchef_path) {
    if (file_exists($fuelchef_path)) {
        $fuelchef_autoload_path = $fuelchef_path;
        break;
    }
}

if (null === $fuelchef_autoload_path) {
    add_action('admin_notices', static function (): void {
        if (!current_user_can('activate_plugins')) {
            return;
        }
        echo '<div class="notice notice-error"><p>';
        echo esc_html__('FuelChef Subscription Box plugin composer autoloader missing', 'fuelchef-subscription-box');
        echo '</p></div>';
    });

    return;
}

require_once $fuelchef_autoload_path;

Plugin::getInstance();
