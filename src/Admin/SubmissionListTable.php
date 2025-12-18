<?php
declare(strict_types=1);

namespace AFE\Admin;

use AFE\Submissions\SubmissionRepository;
use AFE\Forms\FormRepository;

if (!class_exists('\WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class SubmissionListTable extends \WP_List_Table
{
    private SubmissionRepository $repo;
    private FormRepository $forms;
    private array $formTitleCache = [];


    public function __construct()
    {
        parent::__construct([
            'singular' => 'afe_submission',
            'plural' => 'afe_submissions',
            'ajax' => false,
        ]);

        $this->repo = new SubmissionRepository();
        $this->forms = new FormRepository();
    }

    public function get_columns(): array
    {
        return [
            'id' => __('ID', 'advanced-form-engine'),
            'form_id' => __('Form', 'advanced-form-engine'),
            'summary' => __('Summary', 'advanced-form-engine'),
            'ip_address' => __('IP', 'advanced-form-engine'),
            'created_at' => __('Date', 'advanced-form-engine'),
        ];
    }

    protected function get_sortable_columns(): array
    {
        return [
            'id' => ['id', false],
            'form_id' => ['form_id', false],
            'created_at' => ['created_at', true],
        ];
    }

    protected function extra_tablenav($which): void
    {
        if ($which !== 'top') {
            return;
        }

        $selectedForm = isset($_GET['form']) ? (int) $_GET['form'] : 0;
        $forms = $this->forms->all();

        echo '<div class="alignleft actions">';
        echo '<label class="screen-reader-text" for="afe-form-filter">' . esc_html__('Filter by form', 'advanced-form-engine') . '</label>';
        echo '<select name="form" id="afe-form-filter">';
        echo '<option value="0">' . esc_html__('All Forms', 'advanced-form-engine') . '</option>';

        foreach ($forms as $form) {
            $id = (int) $form->id;
            $title = (string) $form->title;
            printf('<option value="%d" %s>%s</option>',$id,
                selected($selectedForm, $id, false),
                esc_html($title)
            );
        }

        echo '</select>';
        submit_button(__('Filter'), 'secondary', false, false, ['id' => 'post-query-submit']);
        echo '</div>';
    }

    public function prepare_items(): void
    {
        $perPage = 20;
        $paged = isset($_GET['paged']) ? max(1, (int) $_GET['paged']) : 1;
        $offset = ($paged - 1) * $perPage;

        $search = isset($_GET['s']) ? sanitize_text_field((string) $_GET['s']) : '';
        $formId = isset($_GET['form']) ? (int) $_GET['form'] : 0;

        $orderby = isset($_GET['orderby']) ? sanitize_key((string) $_GET['orderby']) : 'created_at';
        $order = isset($_GET['order']) ? sanitize_key((string) $_GET['order']) : 'DESC';

        $this->_column_headers = [$this->get_columns(), [], $this->get_sortable_columns()];

        $total = $this->repo->count([
            'form_id' => $formId,
            'search'  => $search,
        ]);

        $rows = $this->repo->list([
            'form_id' => $formId,
            'search' => $search,
            'limit' => $perPage,
            'offset' => $offset,
            'order_by' => $orderby,
            'order' => $order,
        ]);

        $this->items = $rows;

        $this->set_pagination_args([
            'total_items' => $total,
            'per_page' => $perPage,
            'total_pages' => (int) ceil($total / $perPage),
        ]);
    }

    public function column_id(array $item): string
    {
        $id = (int) $item['id'];

        $viewUrl = add_query_arg(
            [
                'page' => 'afe_submissions',
                'action' => 'view',
                'submission' => $id,
            ],
            admin_url('admin.php')
        );

        $actions = [
            'view' => sprintf('<a href="%s">%s</a>', esc_url($viewUrl), esc_html__('View', 'advanced-form-engine')),
        ];

        return sprintf('%d %s', $id, $this->row_actions($actions));
    }

    public function column_form_id(array $item): string
    {
        $formId = (int) $item['form_id'];

        if (!isset($this->formTitleCache[$formId])) {
            $form = $this->forms->find($formId);

            // Store both title + existence in cache
            $this->formTitleCache[$formId] = $form
                ? (string) $form->title
                : '';
        }

        $title = $this->formTitleCache[$formId];

        if ($title === '') {
            // Form missing/deleted
            return esc_html(sprintf(__('Form #%d', 'advanced-form-engine'), $formId));
        }
        
        // Link to the existing Form Editor route in your AdminMenu::renderFormsPage()
        $editUrl = add_query_arg(
            [
                'page'   => 'afe_forms',
                'action' => 'edit',
                'form'   => $formId,
            ],
            admin_url('admin.php')
        );

        return sprintf(
            '<a href="%s">%s</a>',
            esc_url($editUrl),
            esc_html($title)
        );
    }

    public function column_summary(array $item): string
    {
        $data = json_decode((string) ($item['data'] ?? '{}'), true);
        if (!is_array($data)) {
            return '';
        }

        $name = isset($data['name']) ? (string) $data['name'] : '';
        $email = isset($data['email']) ? (string) $data['email'] : '';

        $parts = array_filter([$name, $email]);
        return esc_html(implode(' — ', $parts));
    }

    public function column_default($item, $column_name): string
    {
        $value = $item[$column_name] ?? '';
        return esc_html((string) $value);
    }

    public function no_items(): void
    {
        esc_html_e('No submissions found.', 'advanced-form-engine');
    }
}
