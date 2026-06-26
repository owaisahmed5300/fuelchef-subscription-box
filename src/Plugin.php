<?php
/**
 * Plugin bootstrap class.
 */

declare(strict_types=1);

namespace FuelChefSubsBox;

use Stripe\Stripe;

/**
 * Plugin bootstrap and service orchestrator.
 */
final class Plugin
{
    /**
     * Singleton instance of the plugin.
     */
    private static ?self $instance = null;

    /**
     * Private constructor
     */
    private function __construct() {}

    /**
     * Get instance of the plugin.
     *
     * @return self
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Registers activation and i18n hooks.
     */
    public function register(): void
    {
        register_activation_hook(FUELCHEF_SUBSCRIPTION_BOX_FILE, [$this, "onActivate"]);

        add_action("plugins_loaded", [$this, "boot"]);
    }

    /**
     * Initializes the plugin after WordPress has finished loading.
     */
    public function boot(): void
    {
        add_action("init", [$this, "loadTextdomain"]);
    }

    /**
     * Loads the plugin textdomain for i18n.
     */
    public function loadTextdomain(): void
    {
        load_plugin_textdomain(
            "fuelchef-subscription-box",
            false,
            dirname(plugin_basename(FUELCHEF_SUBSCRIPTION_BOX_FILE)) . "/languages",
        );
    }

    /**
     * Executes on plugin activation.
     */
    public function onActivate(): void
    {
        // @todo: add activation logic.
    }
}
