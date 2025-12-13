<?php
declare(strict_types=1);

namespace AFE\Forms;

class FormRepository
{
    private string $table;

    public function __construct()
    {
        global $wpdb;
        $this->table = $wpdb->prefix . 'afe_forms';
    }

    /**
     * Get all forms ordered by created_at DESC.
     *
     * @return FormEntity[]
     */
    public function all(): array
    {
        global $wpdb;

        $rows = $wpdb->get_results(
            "SELECT * FROM {$this->table} ORDER BY created_at DESC",
            ARRAY_A
        );

        if (!$rows) {
            return [];
        }

        $forms = [];
        foreach ($rows as $row) {
            $forms[] = FormEntity::fromRow($row);
        }

        return $forms;
    }

    /**
     * Find a single form by ID.
     */
    public function find(int $id): ?FormEntity
    {
        global $wpdb;

        $row = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$this->table} WHERE id = %d", $id),
            ARRAY_A
        );

        if (!$row) {
            return null;
        }

        return FormEntity::fromRow($row);
    }

    /**
     * Save (insert or update) a form.
     */
    public function save(FormEntity $form): int
    {
        global $wpdb;

        $now = current_time('mysql');

        if ($form->id === null) {
            // Insert
            $result = $wpdb->insert(
                $this->table,
                [
                    'title'      => $form->title,
                    'slug'       => $form->slug,
                    'config'     => $form->configJson,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                ['%s', '%s', '%s', '%s', '%s']
            );

            if ($result === false) {
                return 0;
            }

            $form->id = (int) $wpdb->insert_id;
            $form->createdAt = $now;
            $form->updatedAt = $now;

            return $form->id;
        }

        // Update
        $result = $wpdb->update(
            $this->table,
            [
                'title' => $form->title,
                'slug' => $form->slug,
                'config' => $form->configJson,
                'updated_at' => $now,
            ],
            ['id' => $form->id],
            ['%s', '%s', '%s', '%s'],
            ['%d']
        );

        if ($result === false) {
            return 0;
        }

        $form->updatedAt = $now;

        return $form->id;
    }

    /**
     * Delete a form by ID.
     */
    public function delete(int $id): bool
    {
        global $wpdb;

        $deleted = $wpdb->delete(
            $this->table,
            ['id' => $id],
            ['%d']
        );

        return $deleted !== false;
    }
}
