<?php
declare(strict_types=1);

namespace AFE\Core;

/**
 * This class prepares DB tables
 */
class Activator {
    public static function activate(): void {
        global $wpdb;

        $charset = $wpdb->get_charset_collate();
        $forms = $wpdb->prefix . 'afe_forms';
        $submissions = $wpdb->prefix . 'afe_submissions';

        $sql = "
        CREATE TABLE {$forms} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            title VARCHAR(255) NOT NULL,
            slug VARCHAR(255) NOT NULL,
            config LONGTEXT NOT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug)
        ) {$charset};

        CREATE TABLE {$submissions} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            form_id BIGINT UNSIGNED NOT NULL,
            data LONGTEXT NOT NULL,
            ip_address VARCHAR(45) NULL,
            user_agent TEXT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY form_id (form_id)
        ) {$charset};
        ";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);

        // Seed sample forms for dev if table is empty.
        $count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$forms}");
        if ($count === 0) {
            $now = current_time('mysql');

            $wpdb->insert(
                $forms,
                [
                    'title' => 'Contact Form',
                    'slug' => 'contact-form',
                    'config' => '{}',
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                ['%s', '%s', '%s', '%s', '%s']
            );

            $wpdb->insert(
                $forms,
                [
                    'title' => 'Quote Request',
                    'slug' => 'quote-request',
                    'config' => '{}',
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                ['%s', '%s', '%s', '%s', '%s']
            );
        }
    }
}
