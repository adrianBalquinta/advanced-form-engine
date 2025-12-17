<?php
declare(strict_types=1);

namespace AFE\Admin;

use AFE\Forms\FormRepository;
use AFE\Forms\FormEntity;

if (!class_exists('\WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class FormListTable extends \WP_List_Table
{

    private FormRepository $repository;

    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct([
            'singular' => 'afe_form',
            'plural' => 'afe_forms',
            'ajax' => false,
        ]);

        $this->repository = new FormRepository();
    }

    /**
     * Define columns for the table.
     */
    public function get_columns(): array
    {
        return [
            'cb' => '<input type="checkbox" />',
            'title' => __('Title', 'advanced-form-engine'),
            'slug' => __('Slug', 'advanced-form-engine'),
            'shortcode' => __('Shortcode', 'advanced-form-engine'),
            'created_at' => __('Created', 'advanced-form-engine'),
        ];
    }

    /**
     * Default column output.
     *
     * @param array  $item
     * @param string $column_name
     */
    public function column_default($item, $column_name)
    {
        return isset($item[$column_name]) ? esc_html((string) $item[$column_name]) : '';
    }

    /**
     * Checkbox column.
     *
     * @param array $item
     */
    public function column_cb($item): string
    {
        return sprintf('<input type="checkbox" name="form_ids[]" value="%d" />',(int) $item['id']);
    }

    /**
     * Title column with row actions.
     *
     * @param array $item
     */
    public function column_title($item): string
    {
        $id = (int) $item['id'];
        $title = esc_html($item['title']);

        $edit_url = add_query_arg(
            [
                'page'   => 'afe_forms',
                'action' => 'edit',
                'form'   => $id,
            ],
            admin_url('admin.php')
        );

        $actions = [
            'edit' => sprintf('<a href="%s">%s</a>',esc_url($edit_url),
                esc_html__('Edit', 'advanced-form-engine')
            ),
            'duplicate' => '#', // Placeholder for now
            'delete' => '#', // Placeholder for now
        ];

        return sprintf('<strong><a href="%s">%s</a></strong> %s',
            esc_url($edit_url),
            $title,
            $this->row_actions($actions)
        );
    }

    /**
     * Prepare items.
     */
    public function prepare_items(): void
    {
        $columns = $this->get_columns();
        $hidden = [];
        $sortable = []; // Make columns sortable later.

        $this->_column_headers = [$columns, $hidden, $sortable];

        // Load forms from repository.
        $forms = $this->repository->all();

        $data = [];
        foreach ($forms as $form) {
            if ($form instanceof FormEntity) {
                $data[] = $form->toArray();
            }
        }

        $this->items = $data;
    }

    public function no_items(): void
    {
        esc_html_e('No forms found. Click "Add New" to create your first form.', 'advanced-form-engine');
    }

}
