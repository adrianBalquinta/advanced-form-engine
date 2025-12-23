<?php
declare(strict_types=1);

namespace AFE\Admin;

use AFE\Forms\FormRepository;
use AFE\Forms\FormEntity;
use AFE\Forms\FormConfig;

class FormEditorController
{
    private FormRepository $repository;

    public function __construct()
    {
        $this->repository = new FormRepository();
    }

    /**
     * Render the Add/Edit form screen.
     */
    public function render(int $formId = 0): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to access this page.', 'advanced-form-engine'));
        }

        $isEdit = $formId > 0;
        $errors = [];

        // Load existing or new entity
        if ($isEdit) {
            $form = $this->repository->find($formId);
            if (!$form) {
                wp_die(esc_html__('Form not found.', 'advanced-form-engine'));
            }
        } else {
            $form = new FormEntity();

            // New forms start with a valid, versioned config schema
            $form->configJson = FormConfig::defaultJson();
        }

        // Handle POST (save)
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            check_admin_referer('afe_save_form', 'afe_form_nonce');

            $title = isset($_POST['title']) ? sanitize_text_field(wp_unslash($_POST['title'])) : '';
            $slug = isset($_POST['slug']) ? sanitize_title(wp_unslash($_POST['slug'])) : '';
            // $fieldIds = isset($_POST['field_id']) ? (array) $_POST['field_id'] : [];
            // $fieldLabels = isset($_POST['field_label']) ? (array) $_POST['field_label'] : [];
            // $fieldTypes = isset($_POST['field_type']) ? (array) $_POST['field_type'] : [];
            // $fieldRequired = isset($_POST['field_required']) ? (array) $_POST['field_required'] : [];


            if ($title === '') {
                $errors[] = __('Title is required.', 'advanced-form-engine');
            }

            // Auto-generate slug from title if empty
            if ($slug === '' && $title !== '') {
                $slug = sanitize_title($title);
            }

            if (empty($errors)) {
                $form->title = $title;
                $form->slug = $slug;
                /*// Ensure config is always a valid schema JSON
                if ($form->configJson === '' || $form->configJson === '{}' ) {
                    $form->configJson = FormConfig::defaultJson();
                }*/

                // // Build fields array from POST (small v1 builder)
                // //build and store config
                // $fields = [];
                // $seen = [];

                // for ($i = 0; $i < count($fieldLabels); $i++) {
                //     $rawId = $fieldIds[$i] ?? '';
                //     $rawLabel = $fieldLabels[$i] ?? '';
                //     $rawType = $fieldTypes[$i] ?? 'text';

                //     $id = sanitize_key(wp_unslash((string) $rawId));
                //     $label = sanitize_text_field(wp_unslash((string) $rawLabel));
                //     $type = sanitize_key(wp_unslash((string) $rawType));

                //     if ($label === '') {
                //         continue; // skip empty rows
                //     }

                //     // If ID missing, generate from label
                //     if ($id === '') {
                //         $id = sanitize_key($label);
                //     }

                //     // Prevent duplicate IDs
                //     if ($id === '' || isset($seen[$id])) {
                //         continue;
                //     }
                //     $seen[$id] = true;

                //     $allowedTypes = ['text', 'email', 'textarea'];
                //     if (!in_array($type, $allowedTypes, true)) {
                //         $type = 'text';
                //     }

                //     // Checkbox posts as field_required[id] = 1
                //     $required = isset($fieldRequired[$id]) && (string) $fieldRequired[$id] === '1';

                //     $fields[] = [
                //         'id' => $id,
                //         'type' => $type,
                //         'label' => $label,
                //         'required' => $required,
                //     ];
                // }

                // // If admin removed all fields, keep safe defaults
                // if (empty($fields)) {
                //     $form->configJson = FormConfig::defaultJson();
                // } else {
                //     $config = [
                //         'version' => 1,
                //         'fields' => $fields,
                //         'logic' => [],
                //         'integrations' => [
                //             'email' => ['enabled' => true, 'to' => ''],
                //             'slack' => ['enabled' => false, 'webhook_url' => ''],
                //             'webhook' => ['enabled' => false, 'url' => ''],
                //         ],
                //     ];

                //     $form->configJson = wp_json_encode($config);
                // }

                $form->configJson = $this->buildConfigJsonFromPost($form->configJson);

                $savedId = $this->repository->save($form);

                if ($savedId > 0) {
                    $redirect = add_query_arg(
                        [
                            'page' => 'afe_forms',
                            'updated' => 1,
                        ],
                        admin_url('admin.php')
                    );
                    wp_safe_redirect($redirect);
                    exit;
                } else {
                    $errors[] = __('Failed to save the form. Please try again.', 'advanced-form-engine');
                }
            } else {
                // Refill fields with user input on validation error
                $form->title = $title;
                $form->slug = $slug;
            }
        }

        $this->renderPage($form, $isEdit, $errors);
    }

    /**
     * Build and normalize configJson from the Fields UI POST arrays.
     * Falls back to existing config or default schema if POST is empty/invalid.
     */
    private function buildConfigJsonFromPost(string $existingConfigJson): string
    {
        // If no fields posted, keep existing config (or default if empty)
        if (!isset($_POST['field_label'])) {
            if ($existingConfigJson === '' || $existingConfigJson === '{}' ) {
                return \AFE\Forms\FormConfig::defaultJson();
            }
            return $existingConfigJson;
        }

        $fieldIds = isset($_POST['field_id']) ? (array) $_POST['field_id'] : [];
        $fieldLabels = isset($_POST['field_label']) ? (array) $_POST['field_label'] : [];
        $fieldTypes = isset($_POST['field_type']) ? (array) $_POST['field_type'] : [];
        /**
         * Required flags are keyed by field id: field_required[<field_id>] = 1
         * This avoids checkbox index drift.
         */
        $fieldRequired = isset($_POST['field_required']) ? (array) $_POST['field_required'] : [];

        $fields = [];
        $seen = [];

        $count = max(count($fieldLabels), count($fieldIds), count($fieldTypes));

        for ($i = 0; $i < $count; $i++) {
            $rawId = $fieldIds[$i] ?? '';
            $rawLabel = $fieldLabels[$i] ?? '';
            $rawType = $fieldTypes[$i] ?? 'text';

            $id = sanitize_key(wp_unslash((string) $rawId));
            $label = sanitize_text_field(wp_unslash((string) $rawLabel));
            $type = sanitize_key(wp_unslash((string) $rawType));

            // Skip empty rows
            if ($label === '') {
                continue;
            }

            // If ID is missing, generate it from label
            if ($id === '') {
                $id = sanitize_key($label);
            }

            // Ensure unique, non-empty IDs
            if ($id === '' || isset($seen[$id])) {
                continue;
            }
            $seen[$id] = true;

            $allowedTypes = ['text', 'email', 'textarea'];
            if (!in_array($type, $allowedTypes, true)) {
                $type = 'text';
            }

            // Index-safe required (preferred): field_required[<i>] = 1
            $required = isset($fieldRequired[$id]) && (string) $fieldRequired[$id] === '1';

            $fields[] = [
                'id' => $id,
                'type' => $type,
                'label' => $label,
                'required' => $required,
            ];
        }

        // If admin removed everything, keep a safe default config
        if (empty($fields)) {
            return FormConfig::defaultJson();
        }

        // Keep existing integrations/logic if present, otherwise seed defaults
        $existing = json_decode($existingConfigJson, true);
        $logic = (is_array($existing) && isset($existing['logic']) && is_array($existing['logic'])) ? $existing['logic'] : [];

        $integrations = (is_array($existing) && isset($existing['integrations']) && is_array($existing['integrations']))
            ? $existing['integrations']
            : [
                'email' => ['enabled' => true,  'to' => ''],
                'slack' => ['enabled' => false, 'webhook_url' => ''],
                'webhook' => ['enabled' => false, 'url' => ''],
            ];

        $config = [
            'version' => 1,
            'fields' => $fields,
            'logic' => $logic,
            'integrations' => $integrations,
        ];

        return wp_json_encode($config);
    }

    /**
     * Output the HTML for the editor page.
     *
     * @param FormEntity $form
     * @param bool $isEdit
     * @param string[] $errors
     */
    private function renderPage(FormEntity $form, bool $isEdit, array $errors): void
    {
        $title = $isEdit
            ? __('Edit Form', 'advanced-form-engine')
            : __('Add New Form', 'advanced-form-engine');

        $listUrl = add_query_arg(['page' => 'afe_forms'], admin_url('admin.php'));

        $configArr = json_decode((string) $form->configJson, true);
        $existingFields = (is_array($configArr) && isset($configArr['fields']) && is_array($configArr['fields']))
                        ? $configArr['fields']
                        : [];

        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">
                <?php echo esc_html($title); ?>
            </h1>

            <a href="<?php echo esc_url($listUrl); ?>" class="page-title-action">
                <?php esc_html_e('Back to list', 'advanced-form-engine'); ?>
            </a>

            <hr class="wp-header-end" />

            <?php if (!empty($errors)) : ?>
                <div class="notice notice-error">
                    <ul>
                        <?php foreach ($errors as $error) : ?>
                            <li><?php echo esc_html($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="post">
                <?php wp_nonce_field('afe_save_form', 'afe_form_nonce'); ?>

                <table class="form-table" role="presentation">
                    <tbody>
                    <tr>
                        <th scope="row">
                            <label for="afe-form-title">
                                <?php esc_html_e('Title', 'advanced-form-engine'); ?>
                            </label>
                        </th>
                        <td>
                            <input
                                name="title"
                                type="text"
                                id="afe-form-title"
                                value="<?php echo esc_attr($form->title); ?>"
                                class="regular-text"
                                required
                            />
                            <p class="description">
                                <?php esc_html_e('Internal name for this form (e.g. Contact Form, Quote Request).', 'advanced-form-engine'); ?>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="afe-form-slug">
                                <?php esc_html_e('Slug', 'advanced-form-engine'); ?>
                            </label>
                        </th>
                        <td>
                            <input
                                name="slug"
                                type="text"
                                id="afe-form-slug"
                                value="<?php echo esc_attr($form->slug); ?>"
                                class="regular-text"
                            />
                            <p class="description">
                                <?php esc_html_e('Optional. Used for identification. If left empty, it will be generated from the title.', 'advanced-form-engine'); ?>
                            </p>
                        </td>
                    </tr>
                    </tbody>
                </table>
                <h2><?php esc_html_e('Fields', 'advanced-form-engine'); ?></h2>

                <table class="widefat striped" id="afe-fields-table" style="max-width: 900px;">
                <thead>
                    <tr>
                    <th style="width:22%"><?php esc_html_e('Field ID', 'advanced-form-engine'); ?></th>
                    <th style="width:38%"><?php esc_html_e('Label', 'advanced-form-engine'); ?></th>
                    <th style="width:22%"><?php esc_html_e('Type', 'advanced-form-engine'); ?></th>
                    <th style="width:10%"><?php esc_html_e('Required', 'advanced-form-engine'); ?></th>
                    <th style="width:8%"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($existingFields)) : ?>
                    <?php foreach ($existingFields as $i => $f) : 
                        $fid = isset($f['id']) ? (string) $f['id'] : '';
                        $lbl = isset($f['label']) ? (string) $f['label'] : '';
                        $typ = isset($f['type']) ? (string) $f['type'] : 'text';
                        $req = !empty($f['required']);
                    ?>
                        <tr>
                        <td><input type="text" name="field_id[]" class="regular-text" value="<?php echo esc_attr($fid); ?>" /></td>
                        <td><input type="text" name="field_label[]" class="regular-text" value="<?php echo esc_attr($lbl); ?>" required /></td>
                        <td>
                            <select name="field_type[]">
                            <option value="text" <?php selected($typ, 'text'); ?>>text</option>
                            <option value="email" <?php selected($typ, 'email'); ?>>email</option>
                            <option value="textarea" <?php selected($typ, 'textarea'); ?>>textarea</option>
                            </select>
                        </td>
                        <td style="text-align:center;">
                            <input type="checkbox" class="afe-required" name="field_required[<?php echo esc_attr($fid); ?>]" value="1" <?php checked($req); ?> />
                        </td>
                        <td style="text-align:right;">
                            <button type="button" class="button afe-remove-field"><?php esc_html_e('Remove', 'advanced-form-engine'); ?></button>
                        </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php else : ?>
                    <!-- Start with one empty row -->
                    <tr>
                        <td><input type="text" name="field_id[]" class="regular-text" value="" /></td>
                        <td><input type="text" name="field_label[]" class="regular-text" value="" required /></td>
                        <td>
                        <select name="field_type[]">
                            <option value="text">text</option>
                            <option value="email">email</option>
                            <option value="textarea">textarea</option>
                        </select>
                        </td>
                        <td style="text-align:center;">
                        <input type="checkbox" class="afe-required" name="field_required[_new_0]" value="1" />
                        </td>
                        <td style="text-align:right;">
                        <button type="button" class="button afe-remove-field"><?php esc_html_e('Remove', 'advanced-form-engine'); ?></button>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
                </table>
                <p>
                <button type="button" class="button button-secondary" id="afe-add-field">
                    <?php esc_html_e('Add Field', 'advanced-form-engine'); ?>
                </button>
                </p>

                <script>
                (function(){
                    const table = document.getElementById('afe-fields-table');
                    const addBtn = document.getElementById('afe-add-field');
                    let newRowIndex = table.querySelectorAll('tbody tr').length;
                    // When the user types a Field ID, update the Required checkbox name to:
                    // field_required[<field_id>]
                    function wireRow(row) {
                        const idInput = row.querySelector('input[name="field_id[]"]');
                        const reqInput = row.querySelector('.afe-required');
                        if (!idInput || !reqInput) return;

                        const syncName = () => {
                        const raw = (idInput.value || '').trim();
                        // mirror your PHP sanitize_key-ish behavior (close enough for admin UI)
                        const id = raw.toLowerCase().replace(/[^a-z0-9_]+/g, '_');
                        reqInput.name = `field_required[${id !== '' ? id : `_new_${newRowIndex}`}]`;
                        };

                        idInput.addEventListener('input', syncName);
                        syncName(); // set initial name (important on page load)
                    }

                    if (!table || !addBtn) return;

                    // Wire existing rows rendered by PHP
                    table.querySelectorAll('tbody tr').forEach(wireRow);
                    // Remove row handler (keep at least one row)
                    table.addEventListener('click', function(e){
                        const btn = e.target.closest('.afe-remove-field');
                        if (!btn) return;
                        const row = btn.closest('tr');
                        if (!row) return;
                        const tbody = table.querySelector('tbody');
                        if (tbody && tbody.rows.length > 1) {
                            row.remove();
                            return;
                        } else {
                            // keep at least one row
                            row.querySelectorAll('input[type="text"]').forEach(i => i.value = '');
                            row.querySelectorAll('input[type="checkbox"]').forEach(i => i.checked = false);
                            const sel = row.querySelector('select');
                            if (sel) sel.value = 'text';
                        }
                    });

                    addBtn.addEventListener('click', function(){
                        const tbody = table.querySelector('tbody');
                        if (!tbody) return;

                        newRowIndex++;

                        const tr = document.createElement('tr');
                        tr.innerHTML = `
                            <td><input type="text" name="field_id[]" class="regular-text" value="" /></td>
                            <td><input type="text" name="field_label[]" class="regular-text" value="" required /></td>
                            <td>
                                <select name="field_type[]">
                                    <option value="text">text</option>
                                    <option value="email">email</option>
                                    <option value="textarea">textarea</option>
                                </select>
                            </td>
                            <td style="text-align:center;">
                                <input type="checkbox" class="afe-required" name="field_required[_new_${newRowIndex}]" value="1" />
                            </td>
                            <td style="text-align:right;">
                                <button type="button" class="button afe-remove-field">Remove</button>
                            </td>
                        `;

                        tbody.appendChild(tr);
                        wireRow(tr);
                    });
                })();
                </script>

                <?php submit_button($isEdit ? __('Update Form', 'advanced-form-engine') : __('Create Form', 'advanced-form-engine')); ?>
            </form>
        </div>
        <?php
    }
}
