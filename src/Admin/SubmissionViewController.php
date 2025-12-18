<?php
declare(strict_types=1);

namespace AFE\Admin;

use AFE\Submissions\SubmissionRepository;

class SubmissionViewController
{
    private SubmissionRepository $repo;

    public function __construct()
    {
        $this->repo = new SubmissionRepository();
    }

    public function render(int $submissionId): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to access this page.', 'advanced-form-engine'));
        }

        $row = $this->repo->find($submissionId);
        if (!$row) {
            wp_die(esc_html__('Submission not found.', 'advanced-form-engine'));
        }

        $backUrl = add_query_arg(['page' => 'afe_submissions'], admin_url('admin.php'));
        $data = json_decode((string) ($row['data'] ?? '{}'), true);
        if (!is_array($data)) {
            $data = [];
        }
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php esc_html_e('View Submission', 'advanced-form-engine'); ?></h1>
            <a href="<?php echo esc_url($backUrl); ?>" class="page-title-action"><?php esc_html_e('Back to list', 'advanced-form-engine'); ?></a>
            <hr class="wp-header-end" />

            <table class="widefat striped">
                <tbody>
                <tr><th><?php esc_html_e('Submission ID', 'advanced-form-engine'); ?></th><td><?php echo esc_html((string) $row['id']); ?></td></tr>
                <tr><th><?php esc_html_e('Form ID', 'advanced-form-engine'); ?></th><td><?php echo esc_html((string) $row['form_id']); ?></td></tr>
                <tr><th><?php esc_html_e('Date', 'advanced-form-engine'); ?></th><td><?php echo esc_html((string) $row['created_at']); ?></td></tr>
                <tr><th><?php esc_html_e('IP Address', 'advanced-form-engine'); ?></th><td><?php echo esc_html((string) ($row['ip_address'] ?? '')); ?></td></tr>
                </tbody>
            </table>

            <h2 style="margin-top: 18px;"><?php esc_html_e('Data', 'advanced-form-engine'); ?></h2>
            <pre style="background:#fff;border:1px solid #dcdcde;padding:12px;overflow:auto;"><?php echo esc_html(wp_json_encode($data, JSON_PRETTY_PRINT)); ?></pre>
        </div>
        <?php
    }
}
