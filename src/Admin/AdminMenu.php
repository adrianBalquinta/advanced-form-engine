<?php
declare(strict_types=1);

namespace AFE\Admin;

use AFE\Admin\FormListTable;
use AFE\Admin\FormEditorController;

class AdminMenu {
    public function register(): void {
        add_menu_page(
            __('Advanced Forms', 'advanced-form-engine'),
            __('Advanced Forms', 'advanced-form-engine'),
            'manage_options',
            'afe_forms',
            [$this, 'renderFormsPage'],
            'dashicons-feedback',
            58
        );

        add_submenu_page(
            'afe_forms',
            __('Settings', 'advanced-form-engine'),
            __('Settings', 'advanced-form-engine'),
            'manage_options',
            'afe_settings',
            [$this, 'renderSettingsPage']
        );
    }

    public function renderFormsPage(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to access this page.', 'advanced-form-engine'));
        }

        $action = isset($_GET['action']) ? sanitize_key((string) $_GET['action']) : '';
        $formId = isset($_GET['form']) ? (int) $_GET['form'] : 0;

        // Route to Add/Edit screen
        if ($action === 'new' || ($action === 'edit' && $formId > 0)) {
            $editor = new FormEditorController();
            $editor->render($formId);
            return;
        }

        // If we get here, show the list table
        $list_table = new FormListTable();
        $list_table->prepare_items();

        $add_new_url = add_query_arg(
            [
                'page'   => 'afe_forms',
                'action' => 'new',
            ],
            admin_url('admin.php')
        );
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">
                <?php esc_html_e('Advanced Forms', 'advanced-form-engine'); ?>
            </h1>

            <a href="<?php echo esc_url($add_new_url); ?>" class="page-title-action">
                <?php esc_html_e('Add New', 'advanced-form-engine'); ?>
            </a>

            <?php if (isset($_GET['updated'])) : ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php esc_html_e('Form saved.', 'advanced-form-engine'); ?></p>
                </div>
            <?php endif; ?>

            <hr class="wp-header-end" />

            <form method="post">
                <?php
                $list_table->display();
                ?>
            </form>
        </div>
        <?php
    }

    public function renderSettingsPage(): void {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Advanced Form Engine Settings', 'advanced-form-engine'); ?></h1>
            <p><?php esc_html_e('Here you will configure Slack, Mailgun, SendGrid, and Webhook integrations.', 'advanced-form-engine'); ?></p>
        </div>
        <?php
    }
}
