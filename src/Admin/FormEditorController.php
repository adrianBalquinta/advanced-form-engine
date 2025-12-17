<?php
declare(strict_types=1);

namespace AFE\Admin;

use AFE\Forms\FormRepository;
use AFE\Forms\FormEntity;

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
        }

        // Handle POST (save)
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            check_admin_referer('afe_save_form', 'afe_form_nonce');

            $title = isset($_POST['title']) ? sanitize_text_field(wp_unslash($_POST['title'])) : '';
            $slug = isset($_POST['slug']) ? sanitize_title(wp_unslash($_POST['slug'])) : '';

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
                // Keep config as an empty JSON object until builder exists
                if ($form->configJson === '') {
                    $form->configJson = '{}';
                }

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

                <?php submit_button($isEdit ? __('Update Form', 'advanced-form-engine') : __('Create Form', 'advanced-form-engine')); ?>
            </form>
        </div>
        <?php
    }
}
