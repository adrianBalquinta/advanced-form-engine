<?php
declare(strict_types=1);

namespace AFE\Core;

class Assets {
    private string $pluginFile;

    public function __construct(string $pluginFile) {
        $this->pluginFile = $pluginFile;
    }

    public function enqueueFrontend(): void {
        $url = plugin_dir_url($this->pluginFile);
        $path = plugin_dir_path($this->pluginFile);

        wp_enqueue_style('afe-frontend', $url . 'assets/frontend/css/frontend.css', [],
            file_exists($path . 'assets/frontend/css/frontend.css') ? filemtime($path . 'assets/frontend/css/frontend.css') : null
        );

        wp_enqueue_script('afe-frontend', $url . 'assets/frontend/js/frontend.js', ['jquery'],
            file_exists($path . 'assets/frontend/js/frontend.js') ? filemtime($path . 'assets/frontend/js/frontend.js') : null, true
        );
    }

    public function enqueueAdmin(string $hook): void {
        // Load only on plugin pages (refine later)
        $url  = plugin_dir_url($this->pluginFile);
        $path = plugin_dir_path($this->pluginFile);

        wp_enqueue_style('afe-admin', $url . 'assets/admin/css/admin.css', [],
            file_exists($path . 'assets/admin/css/admin.css') ? filemtime($path . 'assets/admin/css/admin.css') : null
        );

        wp_enqueue_script( 'afe-admin', $url . 'assets/admin/js/admin.js', ['wp-element', 'wp-components', 'wp-api-fetch', 'wp-i18n'],
            file_exists($path . 'assets/admin/js/admin.js') ? filemtime($path . 'assets/admin/js/admin.js') : null, true
        );
    }
}
