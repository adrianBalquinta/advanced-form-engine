<?php
declare(strict_types=1);

namespace AFE\Core;

use AFE\Admin\AdminMenu;
use AFE\Frontend\Shortcode;
use AFE\Core\Assets;

class Plugin
{
    private string $pluginFile;
    private ServiceContainer $container;

    public function __construct(string $pluginFile)
    {
        $this->pluginFile = $pluginFile;
        $this->container = new ServiceContainer();
    }

    public function init(): void
    {
        $this->registerServices();

        add_action('init', [$this, 'onInit']);

        // Admin menu
        add_action('admin_menu', [$this, 'onAdminMenu']);

        // Assets
        add_action('admin_enqueue_scripts', [$this, 'onAdminEnqueueScripts']);
        add_action('wp_enqueue_scripts', [$this, 'onFrontendEnqueueScripts']);
    }

    /**
     * Register all core services in the container.
     */
    private function registerServices(): void
    {
        $file = $this->pluginFile;

        // Assets service
        $this->container->set(Assets::class, function () use ($file) {
            return new Assets($file);
        });

        // Admin menu service
        $this->container->set(AdminMenu::class, function () {
            return new AdminMenu();
        });

        // Shortcode service
        $this->container->set(Shortcode::class, function () {
            return new Shortcode();
        });
    }

    /**
     * General init hook.
     */
    public function onInit(): void
    {
        // Register frontend shortcode
        $this->container->get(Shortcode::class)->register();

        // Later: register REST routes, blocks, etc.
        // error_log('AFE Plugin onInit fired');
    }

    /**
     * Hook into admin_menu to register plugin pages.
     */
    public function onAdminMenu(): void
    {
        $this->container->get(AdminMenu::class)->register();
    }

    /**
     * Enqueue admin assets only when needed.
     *
     * @param string $hook
     */
    public function onAdminEnqueueScripts(string $hook): void
    {
        $this->container->get(Assets::class)->enqueueAdmin($hook);
    }

    /**
     * Enqueue frontend assets.
     */
    public function onFrontendEnqueueScripts(): void
    {
        $this->container->get(Assets::class)->enqueueFrontend();
    }
}
