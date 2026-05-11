<?php

namespace App\Services\Logistics;

use App\Models\LogisticsSetting;

class LogisticsSettingsService
{
    /**
     * @var array<string, mixed>|null
     */
    private ?array $settingsCache = null;

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        if ($this->settingsCache === null) {
            $this->settingsCache = LogisticsSetting::query()
                ->orderBy('sort_order')
                ->get()
                ->mapWithKeys(fn (LogisticsSetting $setting) => [
                    $setting->key => $this->castValue($setting->value, $setting->type),
                ])
                ->all();
        }

        return $this->settingsCache;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->all()[$key] ?? $default;
    }

    /**
     * @return array<string, mixed>
     */
    public function editableSettings(): array
    {
        return LogisticsSetting::query()
            ->where('is_admin_editable', true)
            ->orderBy('group')
            ->orderBy('sort_order')
            ->get()
            ->groupBy('group')
            ->map(fn ($settings) => $settings->values())
            ->all();
    }

    /**
     * @param array<string, mixed> $values
     */
    public function update(array $values): void
    {
        foreach ($values as $key => $value) {
            LogisticsSetting::query()
                ->where('key', $key)
                ->where('is_admin_editable', true)
                ->update(['value' => (string) $value]);
        }

        $this->settingsCache = null;
    }

    /**
     * @return array<string, mixed>
     */
    public function snapshot(): array
    {
        return $this->all();
    }

    private function castValue(?string $value, string $type): mixed
    {
        return match ($type) {
            'integer' => (int) $value,
            'decimal' => (float) $value,
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'json' => json_decode((string) $value, true),
            default => $value,
        };
    }
}
