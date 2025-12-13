<?php
declare(strict_types=1);

namespace AFE\Frontend;

use AFE\Forms\FormRepository;
use AFE\Forms\FormEntity;

class Renderer
{
    private FormRepository $repository;

    public function __construct()
    {
        $this->repository = new FormRepository();
    }

    /**
     * Render a form by ID as HTML.
     */
    public function renderForm(int $formId): string
    {
        $form = $this->repository->find($formId);

        if (!$form instanceof FormEntity || $form->id === null) {
            return '<p>' . esc_html__('Form not found.', 'advanced-form-engine') . '</p>';
        }

        // In the future $form->configJson. For now, a default simple form:
        $fields = $this->getFieldsFromConfig($form->configJson);

        ob_start();?>
        
        <form method="post"
            class="afe-form card p-4 mt-4"
            data-afe-form-id="<?php echo esc_attr((string) $form->id); ?>">

            <?php wp_nonce_field('afe_submit_form', 'afe_form_nonce'); ?>
            <input type="hidden" name="afe_form_id" value="<?php echo esc_attr((string) $form->id); ?>" />

            <?php foreach ($fields as $field) : ?>
                <div class="mb-3 afe-field afe-field-<?php echo esc_attr($field['name']); ?>">
                    <label class="form-label">
                        <?php echo esc_html($field['label']); ?>
                        <?php if (!empty($field['required'])) : ?>
                            <span class="text-danger">*</span>
                        <?php endif; ?>
                    </label>

                    <?php if ($field['type'] === 'textarea') : ?>
                        <textarea
                            name="<?php echo esc_attr($field['name']); ?>"
                            rows="4"
                            class="form-control"
                        ></textarea>
                    <?php else : ?>
                        <input
                            type="<?php echo esc_attr($field['type']); ?>"
                            name="<?php echo esc_attr($field['name']); ?>"
                            class="form-control"
                        />
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

            <div class="afe-actions">
                <button type="submit" class="btn btn-primary">
                    <?php esc_html_e('Send', 'advanced-form-engine'); ?>
                </button>
            </div>
        </form>
        <?php

        return (string) ob_get_clean();
    }

    /**
     * For now, return a simple default config.
     * Later decode $configJson and build dynamic fields.
     *
     * @param string $configJson
     * @return array<int,array<string,mixed>>
     */
    private function getFieldsFromConfig(string $configJson): array
    {
        $config = json_decode($configJson, true);

        if (is_array($config) && isset($config['fields']) && is_array($config['fields'])) {
            // Very basic validation; Improve this when we build the editor.
            return $config['fields'];
        }

        // Default "MVP" fields
        return [
            [
                'name' => 'name',
                'label' => __('Name', 'advanced-form-engine'),
                'type' => 'text',
                'required' => true,
            ],
            [
                'name' => 'email',
                'label' => __('Email', 'advanced-form-engine'),
                'type' => 'email',
                'required' => true,
            ],
            [
                'name' => 'message',
                'label' => __('Message', 'advanced-form-engine'),
                'type' => 'textarea',
                'required' => false,
            ],
        ];
    }
}
