<?php

namespace App\Modules\Auth\Services;

/**
 * Chuyển đổi permission Spatie (resource.action) sang định dạng CASL abilities.
 *
 * Mỗi permission Laravel tương ứng một đối tượng CASL riêng, không gộp chung.
 * Format: [{ "action": "index", "subject": "User" }, { "action": "show", "subject": "User" }, ...]
 */
class CaslAbilityConverter
{
    /**
     * Chuyển danh sách permission Spatie sang abilities theo chuẩn CASL.
     * Mỗi permission = 1 ability, giữ nguyên action gốc (index, show, store, ...).
     *
     * @param  array<string>  $permissions  Ví dụ: ["users.index", "users.show", "posts.store"]
     * @return array<array{action: string, subject: string}>
     */
    public static function toCaslAbilities(array $permissions): array
    {
        $abilities = [];

        foreach ($permissions as $permission) {
            if (! is_string($permission) || ! str_contains($permission, '.')) {
                continue;
            }

            [$resource, $action] = explode('.', $permission, 2);
            $subject = self::resourceToSubject($resource);

            $abilities[] = [
                'action' => $action,
                'subject' => $subject,
            ];
        }

        return $abilities;
    }

    protected static function resourceToSubject(string $resource): string
    {
        return collect(explode('-', $resource))
            ->map(fn (string $part) => ucfirst(strtolower($part)))
            ->implode('');
    }
}
