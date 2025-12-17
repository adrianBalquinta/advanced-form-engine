<?php
declare(strict_types=1);

namespace AFE\Settings;

class Settings
{
    private const OPTION_KEY = 'afe_settings';

    /**
     * @return array<string,string>
     */
    public function all(): array
    {
        $raw = get_option(self::OPTION_KEY, []);
        return is_array($raw) ? $raw : [];
    }

    public function get(string $key, string $default = ''): string
    {
        $all = $this->all();
        return isset($all[$key]) ? (string) $all[$key] : $default;
    }

    public function update(array $values): void
    {
        update_option(self::OPTION_KEY, $values, false);
    }
}
