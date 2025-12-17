<?php
declare(strict_types=1);

namespace AFE\Core;

use AFE\Admin\AdminMenu;
use AFE\Frontend\Shortcode;
use AFE\Core\Assets;
use AFE\Core\EventDispatcher;
use AFE\Notifications\NotificationManager;
use AFE\Notifications\SlackWebhookNotifier;
use AFE\Notifications\WebhookNotifier;
use AFE\Notifications\EmailNotifier;
use AFE\Settings\Settings;

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

        //Submission event
        add_action('afe/submission_created', function (int $formId, int $submissionId, array $data) {

            // Get the internal event dispatcher from the DI container
            $events = $this->container->get(\AFE\Core\EventDispatcher::class);

            // Dispatch a domain-level event for observers (notifications, etc.)
            $events->dispatch('afe.submission.created',new \AFE\Events\SubmissionCreatedEvent($formId,$submissionId,$data));

        }, 10, 3);

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

        //Settings service
        $this->container->set(Settings::class, function () {
            return new Settings();
        });

        //Event Dispatcher
        $this->container->set(EventDispatcher::class, function () {
            return new EventDispatcher();
        });

        //Notification Manager
        $this->container->set(NotificationManager::class, function () {
            /** @var Settings $settings */
            $settings = $this->container->get(Settings::class);
            /**
             * Instantiate all notification strategies.
             *
             * Each notifier decides internally if it is enabled
             * (e.g. Slack only runs if a webhook URL is configured).
             */
            $notifiers = [
                new SlackWebhookNotifier($settings->get('slack_webhook_url')),
                new WebhookNotifier($settings->get('custom_webhook_url')),
                new EmailNotifier($settings->get('notify_email')),
            ];
            return new NotificationManager($notifiers);
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

        /** @var EventDispatcher $events */
        $events = $this->container->get(EventDispatcher::class);

        /** @var NotificationManager $manager */
        $manager = $this->container->get(NotificationManager::class);
        
        //Register observer for form submissions.
        $events->addListener('afe.submission.created',[$manager, 'onSubmissionCreated']);
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
