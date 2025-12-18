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
                'ip_address' => null,    //$ip,
                'user_agent' => null,    //$ua,
                'created_at' => $now,
            ],
            ['%d', '%s', '%s', '%s', '%s']
        );

        if ($inserted === false) {
            return 0;
        }

        return (int) $wpdb->insert_id;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function list(array $args = []): array
    {
        global $wpdb;

        $defaults = [
            'form_id' => 0,
            'search' => '',
            'limit' => 20,
            'offset' => 0,
            'order_by' => 'created_at',
            'order' => 'DESC',
        ];
        $args = array_merge($defaults, $args);

        $where = 'WHERE 1=1';
        $params = [];

        if (!empty($args['form_id'])) {
            $where .= ' AND form_id = %d';
            $params[] = (int) $args['form_id'];
        }

        if (!empty($args['search'])) {
            // search inside JSON string (simple v1)
            $where .= ' AND data LIKE %s';
            $params[] = '%' . $wpdb->esc_like((string) $args['search']) . '%';
        }

        $orderBy = in_array($args['order_by'], ['created_at', 'id', 'form_id'], true) ? $args['order_by'] : 'created_at';
        $order = strtoupper((string) $args['order']) === 'ASC' ? 'ASC' : 'DESC';

        $sql = "SELECT id, form_id, data, ip_address, created_at
                FROM {$this->table}
                {$where}
                ORDER BY {$orderBy} {$order}
                LIMIT %d OFFSET %d";

        $params[] = (int) $args['limit'];
        $params[] = (int) $args['offset'];

        if (!empty($params)) {
            $sql = $wpdb->prepare($sql, ...$params);
        }

        $rows = $wpdb->get_results($sql, ARRAY_A);
        return $rows ?: [];
    }

    public function count(array $args = []): int
    {
        global $wpdb;

        $defaults = [
            'form_id' => 0,
            'search'  => '',
        ];
        $args = array_merge($defaults, $args);

        $where = 'WHERE 1=1';
        $params = [];

        if (!empty($args['form_id'])) {
            $where .= ' AND form_id = %d';
            $params[] = (int) $args['form_id'];
        }

        if (!empty($args['search'])) {
            $where .= ' AND data LIKE %s';
            $params[] = '%' . $wpdb->esc_like((string) $args['search']) . '%';
        }

        $sql = "SELECT COUNT(*) FROM {$this->table} {$where}";
        if (!empty($params)) {
            $sql = $wpdb->prepare($sql, ...$params);
        }

        return (int) $wpdb->get_var($sql);
    }

    public function find(int $id): ?array
    {
        global $wpdb;

        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->table} WHERE id = %d", $id),ARRAY_A);

        return $row ?: null;
    }

}
