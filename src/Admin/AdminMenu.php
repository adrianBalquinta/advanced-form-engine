<?php
declare(strict_types=1);

namespace AFE\Admin;

use AFE\Admin\FormListTable;
use AFE\Admin\FormEditorController;
use AFE\Admin\SubmissionListTable;
use AFE\Admin\SubmissionViewController;


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

        add_submenu_page(
            'afe_forms',
            __('Submissions', 'advanced-form-engine'),
            __('Submissions', 'advanced-form-engine'),
            'manage_options',
            'afe_submissions',
            [$this, 'renderSubmissionsPage']
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
        
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to access this page.', 'advanced-form-engine'));
        }

        $settings = new \AFE\Settings\Settings();
        $saved = false;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            check_admin_referer('afe_save_settings', 'afe_settings_nonce');

            $values = [
                'slack_webhook_url'  => isset($_POST['slack_webhook_url']) ? esc_url_raw(wp_unslash((string) $_POST['slack_webhook_url'])) : '',
                'custom_webhook_url' => isset($_POST['custom_webhook_url']) ? esc_url_raw(wp_unslash((string) $_POST['custom_webhook_url'])) : '',
                'notify_email'       => isset($_POST['notify_email']) ? sanitize_email(wp_unslash((string) $_POST['notify_email'])) : '',
            ];

            $settings->update($values);
            $saved = true;
        }

        $slack  = $settings->get('slack_webhook_url');
        $hook   = $settings->get('custom_webhook_url');
        $email  = $settings->get('notify_email');
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Advanced Form Engine Settings', 'advanced-form-engine'); ?></h1>

            <?php if ($saved) : ?>
                <div class="notice notice-success is-dismissible"><p><?php esc_html_e('Settings saved.', 'advanced-form-engine'); ?></p></div>
            <?php endif; ?>

            <form method="post">
                <?php wp_nonce_field('afe_save_settings', 'afe_settings_nonce'); ?>

                <table class="form-table" role="presentation">
                    <tbody>
                    <tr>
                        <th scope="row"><label for="slack_webhook_url"><?php esc_html_e('Slack Incoming Webhook URL', 'advanced-form-engine'); ?></label></th>
                        <td>
                            <input type="url" class="regular-text" id="slack_webhook_url" name="slack_webhook_url" value="<?php echo esc_attr($slack); ?>" />
                            <p class="description"><?php esc_html_e('If set, a Slack message will be sent on each submission.', 'advanced-form-engine'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><label for="custom_webhook_url"><?php esc_html_e('Custom Webhook URL', 'advanced-form-engine'); ?></label></th>
                        <td>
                            <input type="url" class="regular-text" id="custom_webhook_url" name="custom_webhook_url" value="<?php echo esc_attr($hook); ?>" />
                            <p class="description"><?php esc_html_e('If set, a JSON POST will be sent on each submission.', 'advanced-form-engine'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><label for="notify_email"><?php esc_html_e('Notification Email', 'advanced-form-engine'); ?></label></th>
                        <td>
                            <input type="email" class="regular-text" id="notify_email" name="notify_email" value="<?php echo esc_attr($email); ?>" />
                            <p class="description"><?php esc_html_e('If set, an email will be sent on each submission.', 'advanced-form-engine'); ?></p>
                        </td>
                    </tr>
                    </tbody>
                </table>

                <?php submit_button(__('Save Settings', 'advanced-form-engine')); ?>
            </form>
        </div>
        <?php
    }

    public function renderSubmissionsPage(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to access this page.', 'advanced-form-engine'));
        }

        $action = isset($_GET['action']) ? sanitize_key((string) $_GET['action']) : '';
        $submissionId = isset($_GET['submission']) ? (int) $_GET['submission'] : 0;

        // View screen
        if ($action === 'view' && $submissionId > 0) {
            $controller = new SubmissionViewController();
            $controller->render($submissionId);
            return;
        }

        // Export CSV (uses current filters/search)
        if ($action === 'export') {
            $controller = new \AFE\Admin\SubmissionExportController();
            $controller->exportCsv();
            return;
        }

        $table = new SubmissionListTable();
        $table->prepare_items();

        $exportUrl = add_query_arg(
            [
                'page'   => 'afe_submissions',
                'action' => 'export',
                // preserve filters
                'form'   => isset($_GET['form']) ? (int) $_GET['form'] : 0,
                's'      => isset($_GET['s']) ? sanitize_text_field((string) $_GET['s']) : '',
            ],
            admin_url('admin.php')
        );
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php esc_html_e('Submissions', 'advanced-form-engine'); ?></h1>

            <a href="<?php echo esc_url($exportUrl); ?>" class="page-title-action">
                <?php esc_html_e('Export CSV', 'advanced-form-engine'); ?>
            </a>

            <hr class="wp-header-end" />

            <form method="get">
                <input type="hidden" name="page" value="afe_submissions" />
                <?php $table->search_box(__('Search', 'advanced-form-engine'), 'afe-submissions-search'); ?>
                <?php $table->display(); ?>
            </form>
        </div>
        <?php
    }



}
