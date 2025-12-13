<?php
declare(strict_types=1);

namespace AFE\Forms;

class FormEntity
{
    public ?int $id = null;
    public string $title = '';
    public string $slug = '';
    public string $configJson = '{}';
    public string $createdAt = '';
    public string $updatedAt = '';

    /**
     * Hydrate from DB row (associative array).
     *
     * @param array<string,mixed> $row
     */
    public static function fromRow(array $row): self
    {
        $entity = new self();

        $entity->id = isset($row['id']) ? (int) $row['id'] : null;
        $entity->title = (string) ($row['title'] ?? '');
        $entity->slug = (string) ($row['slug'] ?? '');
        $entity->configJson = (string) ($row['config'] ?? '{}');
        $entity->createdAt = (string) ($row['created_at'] ?? '');
        $entity->updatedAt = (string) ($row['updated_at'] ?? '');

        return $entity;
    }

    /**
     * Convert to array for list table display, etc.
     *
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'shortcode' => $this->id ? sprintf('[afe_form id="%d"]', $this->id) : '',
            'created_at' => $this->createdAt,
        ];
    }
}
