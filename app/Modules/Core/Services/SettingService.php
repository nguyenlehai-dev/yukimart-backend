<?php

namespace App\Modules\Core\Services;

use App\Modules\Core\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SettingService
{
    /**
     * Lấy cấu hình công khai (is_public = true), nhóm theo group.
     */
    public function getPublic(): array
    {
        return Cache::remember(Setting::CACHE_KEY_PUBLIC, Setting::CACHE_TTL, function () {
            return Setting::where('is_public', true)
                ->orderBy('group')
                ->orderBy('sort_order')
                ->get()
                ->groupBy('group')
                ->map(fn ($items) => $items->pluck('value', 'key')->map(fn ($v, $k) => Setting::castValue(
                    $v,
                    $items->firstWhere('key', $k)?->type ?? 'string'
                ))->all())
                ->all();
        });
    }

    /**
     * Lấy toàn bộ cấu hình, nhóm theo group.
     */
    public function getAll(): array
    {
        return Cache::remember(Setting::CACHE_KEY_ALL, Setting::CACHE_TTL, function () {
            return Setting::orderBy('group')
                ->orderBy('sort_order')
                ->get()
                ->groupBy('group')
                ->map(fn ($items) => $items->pluck('value', 'key')->map(fn ($v, $k) => Setting::castValue(
                    $v,
                    $items->firstWhere('key', $k)?->type ?? 'string'
                ))->all())
                ->all();
        });
    }

    /**
     * Lấy giá trị một key. Nếu private và không có quyền thì trả null.
     */
    public function getByKey(string $key): ?array
    {
        $setting = Setting::where('key', $key)->first();

        if (! $setting) {
            return null;
        }

        return [
            'key' => $setting->key,
            'value' => Setting::castValue($setting->value, $setting->type),
            'group' => $setting->group,
            'label' => $setting->label,
            'type' => $setting->type,
        ];
    }

    /**
     * Cập nhật nhiều key. Chỉ cập nhật các key tồn tại trong DB.
     */
    public function update(array $data): array
    {
        $validKeys = Setting::pluck('id', 'key')->all();

        DB::transaction(function () use ($data, $validKeys) {
            foreach ($data as $key => $value) {
                if (! isset($validKeys[$key])) {
                    continue;
                }

                $setting = Setting::find($validKeys[$key]);
                if (! $setting) {
                    continue;
                }

                $setting->value = $this->stringifyValue($value, $setting->type);
                $setting->save();
            }
        });

        Setting::clearCache();

        return $this->getAll();
    }

    /**
     * Chuyển value sang string để lưu DB.
     */
    protected function stringifyValue(mixed $value, string $type): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($type === 'json') {
            return is_string($value) ? $value : json_encode($value);
        }

        if ($type === 'boolean') {
            return $value ? '1' : '0';
        }

        return (string) $value;
    }
}
