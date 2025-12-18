<?php
declare(strict_types=1);

namespace AFE\Admin;

use AFE\Submissions\SubmissionRepository;

class SubmissionExportController
{
    private SubmissionRepository $repo;

    public function __construct()
    {
        $this->repo = new SubmissionRepository();
    }

    public function exportCsv(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to export submissions.', 'advanced-form-engine'));
        }

        $formId = isset($_GET['form']) ? (int) $_GET['form'] : 0;
        $search = isset($_GET['s']) ? sanitize_text_field((string) $_GET['s']) : '';

        $rows = $this->repo->list([
            'form_id' => $formId,
            'search' => $search,
            'limit' => 5000, // safety cap for v1
            'offset' => 0,
        ]);

        // Collect all keys used across submissions so CSV has stable columns
        $allKeys = [];
        $decoded = [];

        foreach ($rows as $row) {
            $data = json_decode((string) ($row['data'] ?? '{}'), true);
            if (!is_array($data)) {
                $data = [];
            }
            $decoded[] = [$row, $data];
            foreach (array_keys($data) as $k) {
                $allKeys[$k] = true;
            }
        }

        $fieldKeys = array_keys($allKeys);

        $filename = 'afe-submissions-' . gmdate('Y-m-d-His') . '.csv';

        nocache_headers();
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename=' . $filename);

        $out = fopen('php://output', 'w');

        // Excel-friendly UTF-8 BOM
        fwrite($out, "\xEF\xBB\xBF");

        // Header row
        $header = array_merge(['submission_id', 'form_id', 'created_at', 'ip_address'], $fieldKeys);
        fputcsv($out, $header);

        foreach ($decoded as [$row, $data]) {
            $line = [
                (string) $row['id'],
                (string) $row['form_id'],
                (string) $row['created_at'],
                (string) ($row['ip_address'] ?? ''),
            ];

            foreach ($fieldKeys as $key) {
                $line[] = isset($data[$key]) ? (string) $data[$key] : '';
            }

            fputcsv($out, $line);
        }

        fclose($out);
        exit;
    }
}
