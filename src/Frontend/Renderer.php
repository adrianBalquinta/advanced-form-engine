<?php
declare(strict_types=1);

namespace AFE\Frontend;

use AFE\Forms\FormRepository;
use AFE\Forms\FormEntity;
use AFE\Submissions\SubmissionRepository;
use AFE\Core\EventDispatcher;
use AFE\Events\SubmissionCreatedEvent;

class Renderer
{
    private FormRepository $repository;
    private SubmissionRepository $submissions;

    public function __construct()
    {
        $this->repository = new FormRepository();
        $this->submissions = new SubmissionRepository();
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

        $fields = $this->getFieldsFromConfig($form->configJson);

        // Handle submission
        $status = $this->handleSubmission($form, $fields);

        ob_start();?>

        <?php if ($status['submitted']) : ?>
            <?php if ($status['success']) : ?>
                <div class="alert alert-success" role="alert">
                    <?php esc_html_e('Thank you! Your message has been sent.', 'advanced-form-engine'); ?>
                </div>
            <?php else : ?>
                <div class="alert alert-danger" role="alert">
                    <strong><?php esc_html_e('There were some problems with your submission:', 'advanced-form-engine'); ?></strong>
                    <ul class="mb-0">
                        <?php foreach ($status['errors'] as $error) : ?>
                            <li><?php echo esc_html($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <form method="post"
              class="afe-form card p-4 mt-4"
              data-afe-form-id="<?php echo esc_attr((string) $form->id); ?>">

            <?php wp_nonce_field('afe_submit_form', 'afe_form_nonce'); ?>
            <input type="hidden" name="afe_form_id" value="<?php echo esc_attr((string) $form->id); ?>" />

            <?php foreach ($fields as $field) : 
                $name  = $field['name'];
                $value = isset($status['values'][$name]) ? $status['values'][$name] : '';
                ?>
                <div class="mb-3 afe-field afe-field-<?php echo esc_attr($name); ?>">
                    <label class="form-label">
                        <?php echo esc_html($field['label']); ?>
                        <?php if (!empty($field['required'])) : ?>
                            <span class="text-danger">*</span>
                        <?php endif; ?>
                    </label>

                    <?php if ($field['type'] === 'textarea') : ?>
                        <textarea
                            name="<?php echo esc_attr($name); ?>"
                            rows="4"
                            class="form-control"
                        ><?php echo esc_textarea($value); ?></textarea>
                    <?php else : ?>
                        <input
                            type="<?php echo esc_attr($field['type']); ?>"
                            name="<?php echo esc_attr($name); ?>"
                            class="form-control"
                            value="<?php echo esc_attr($value); ?>"
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
     * Handle submission for this specific form, if POSTed.
     *
     * @param FormEntity $form
     * @param array<int,array<string,mixed>> $fields
     *
     * @return array{submitted:bool,success:bool,errors:array<int,string>,values:array<string,string>}
     */
    private function handleSubmission(FormEntity $form, array $fields): array
    {
        $result = [
            'submitted' => false,
            'success' => false,
            'errors' => [],
            'values' => [],
        ];

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $result;
        }

        // Only handle if this form was submitted
        $postedFormId = isset($_POST['afe_form_id']) ? (int) $_POST['afe_form_id'] : 0;
        if ($postedFormId !== (int) $form->id) {
            return $result;
        }

        $result['submitted'] = true;

        // Verify nonce
        if (!isset($_POST['afe_form_nonce']) ||
            !wp_verify_nonce(sanitize_text_field(wp_unslash((string) $_POST['afe_form_nonce'])), 'afe_submit_form')) {
                
                $result['errors'][] = __('Security check failed. Please try again.', 'advanced-form-engine');
                return $result;
        }

        // Collect & sanitize values
        $values = [];
        foreach ($fields as $field) {
            $name = $field['name'];

            $raw = isset($_POST[$name]) ? wp_unslash((string) $_POST[$name]) : '';

            if ($field['type'] === 'email') {
                $values[$name] = sanitize_email($raw);
            } else {
                // simple version, later will support rich text
                $values[$name] = sanitize_text_field($raw);
            }
        }
        $result['values'] = $values;

        // Validate required fields
        foreach ($fields as $field) {
            $name = $field['name'];
            $label = $field['label'];
            $required = !empty($field['required']);

            $value = $values[$name] ?? '';

            if ($required && $value === '') {
                $result['errors'][] = sprintf(
                    /* translators: %s is the field label. */
                    __('The %s field is required.', 'advanced-form-engine'), $label);
            }

            if ($field['type'] === 'email' && $value !== '' && !is_email($value)) {
                $result['errors'][] = sprintf(
                    /* translators: %s is the field label. */
                    __('The %s field must be a valid email address.', 'advanced-form-engine'), $label);
            }
        }

        // If there are validation errors, don't save
        if (!empty($result['errors'])) {
            return $result;
        }

        // Save to DB
        $submissionId = $this->submissions->save((int) $form->id, $values);

        do_action('afe/submission_created', (int) $form->id, (int) $submissionId, $values);

        if ($submissionId <= 0) {
            $result['errors'][] = __('An error occurred while saving your submission. Please try again later.', 'advanced-form-engine');
            return $result;
        }

        $result['success'] = true;

        // Optional: clear values after successful submission
        $result['values'] = [];

        return $result;
    }


    /**
     * First return a simple default config.
     * Later decode $configJson and build dynamic fields.
     *
     * @param string $configJson
     * @return array<int,array<string,mixed>>
     */
    private function getFieldsFromConfig(string $configJson): array
    {
        $config = json_decode($configJson, true);

        if (is_array($config) && isset($config['fields']) && is_array($config['fields'])) {
            // Very basic validation; Improve this when the editor is built .
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
