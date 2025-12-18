<?php
declare(strict_types=1);

namespace AFE\Forms;

final class FormConfig
{
    public int $version;

    /** @var FieldDefinition[] */
    public array $fields = [];

    /** @var array<int,array<string,mixed>> */
    public array $logic = [];

    /** @var array<string,mixed> */
    public array $integrations = [];

    /** @var array<int,string> */
    public array $errors = [];

    private const ALLOWED_TYPES = ['text', 'email', 'textarea', 'select', 'checkbox', 'radio', 'number',];

    public static function fromJson(string $json): self
    {
        $self = new self();
        $self->version = 1;

        $data = json_decode($json, true);

        if (!is_array($data)) {
            // If config is empty/invalid, fall back to a safe default config
            return $self->withDefaultFields();
        }

        $self->version = isset($data['version']) ? (int) $data['version'] : 1;
        $self->logic = (isset($data['logic']) && is_array($data['logic'])) ? $data['logic'] : [];
        $self->integrations = (isset($data['integrations']) && is_array($data['integrations'])) ? $data['integrations'] : [];

        $fields = (isset($data['fields']) && is_array($data['fields'])) ? $data['fields'] : [];
        $self->fields = self::parseFields($fields, $self->errors);

        if (empty($self->fields)) {
            $self = $self->withDefaultFields();
        }

        return $self;
    }

    public static function defaultJson(): string
    {
        $data = [
            'version' => 1,
            'fields' => [
                ['id' => 'name', 'type' => 'text', 'label' => 'Name', 'required' => true],
                ['id' => 'email', 'type' => 'email', 'label' => 'Email', 'required' => true],
                ['id' => 'message', 'type' => 'textarea', 'label' => 'Message', 'required' => false],
            ],
            'logic' => [],
            'integrations' => [
                'email' => ['enabled' => true, 'to' => ''],
                'slack' => ['enabled' => false, 'webhook_url' => ''],
                'webhook' => ['enabled' => false, 'url' => ''],
            ],
        ];

        return wp_json_encode($data);
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function toArray(): array
    {
        $fields = [];
        foreach ($this->fields as $f) {
            $fields[] = [
                'id'        => $f->id,
                'type'      => $f->type,
                'label'     => $f->label,
                'required'  => $f->required,
                'sensitive' => $f->sensitive,
                'options'   => $f->options,
            ];
        }

        return [
            'version'      => $this->version,
            'fields'       => $fields,
            'logic'        => $this->logic,
            'integrations' => $this->integrations,
        ];
    }

    public function toJson(): string
    {
        return wp_json_encode($this->toArray());
    }

    private function withDefaultFields(): self
    {
        $this->fields = [
            new FieldDefinition('name', 'text', 'Name', true),
            new FieldDefinition('email', 'email', 'Email', true),
            new FieldDefinition('message', 'textarea', 'Message', false),
        ];

        // Keep logic/integrations defaults if missing
        if (empty($this->integrations)) {
            $this->integrations = [
                'email' => ['enabled' => true, 'to' => ''],
                'slack' => ['enabled' => false, 'webhook_url' => ''],
                'webhook' => ['enabled' => false, 'url' => ''],
            ];
        }

        return $this;
    }

    /**
     * @param array<int,mixed> $rawFields
     * @param array<int,string> $errors
     * @return FieldDefinition[]
     */
    private static function parseFields(array $rawFields, array &$errors): array
    {
        $out = [];
        $seen = [];

        foreach ($rawFields as $idx => $raw) {
            if (!is_array($raw)) {
                $errors[] = "Field at index {$idx} is not an object.";
                continue;
            }

            $id = isset($raw['id']) ? sanitize_key((string) $raw['id']) : '';
            $type = isset($raw['type']) ? sanitize_key((string) $raw['type']) : 'text';
            $label = isset($raw['label']) ? sanitize_text_field((string) $raw['label']) : '';
            $required = !empty($raw['required']);
            $sensitive = !empty($raw['sensitive']);

            if ($id === '' || $label === '') {
                $errors[] = "Field at index {$idx} is missing id/label.";
                continue;
            }

            if (isset($seen[$id])) {
                $errors[] = "Duplicate field id '{$id}'.";
                continue;
            }
            $seen[$id] = true;

            if (!in_array($type, self::ALLOWED_TYPES, true)) {
                $errors[] = "Field '{$id}' has unsupported type '{$type}'. Falling back to text.";
                $type = 'text';
            }

            $options = [];
            if (isset($raw['options']) && is_array($raw['options'])) {
                foreach ($raw['options'] as $opt) {
                    if (!is_array($opt)) {
                        continue;
                    }
                    $optLabel = isset($opt['label']) ? sanitize_text_field((string) $opt['label']) : '';
                    $optValue = isset($opt['value']) ? sanitize_text_field((string) $opt['value']) : '';
                    if ($optLabel !== '' && $optValue !== '') {
                        $options[] = ['label' => $optLabel, 'value' => $optValue];
                    }
                }
            }

            $out[] = new FieldDefinition($id, $type, $label, $required, $sensitive, $options);
        }

        return $out;
    }
}
