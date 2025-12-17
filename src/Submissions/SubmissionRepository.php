<?php
declare(strict_types=1);

namespace AFE\Submissions;

class SubmissionRepository
{
    private string $table;

    public function __construct()
    {
        global $wpdb;
        $this->table = $wpdb->prefix . 'afe_submissions';
    }

    /**
     * Save a submission.
     *
     * @param int   $formId
     * @param array $data   Sanitized field data.
     * @return int Inserted submission ID or 0 on failure.
     */
    public function save(int $formId, array $data): int
    {
        global $wpdb;

        $now = current_time('mysql');
        //$ip  = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field((string) $_SERVER['REMOTE_ADDR']) : '';
        //$ua  = isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field((string) $_SERVER['HTTP_USER_AGENT']) : '';

        $inserted = $wpdb->insert(
            $this->table,
            [
                'form_id' => $formId,
                'data' => wp_json_encode($data),
                //'ip_address' => $ip,
                //'user_agent' => $ua,
                'created_at' => $now,
            ],
            ['%d', '%s', '%s', '%s', '%s']
        );

        if ($inserted === false) {
            return 0;
        }

        return (int) $wpdb->insert_id;
    }
}
