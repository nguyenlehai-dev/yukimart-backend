<?php

namespace App\Modules\Auth\Services;

use App\Modules\Core\Enums\UserStatusEnum;
use App\Modules\Core\Models\Organization;
use App\Modules\Core\Models\User;
use App\Modules\Core\Resources\UserResource;
use App\Modules\ShopProduct\Models\ShopEntry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

class AuthService
{
    public function register(array $data): array
    {
        // Nếu config bật require email verification → tạo user chưa verify, gửi link.
        $requireVerify = (bool) config('auth.require_email_verification', false);
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'user_name' => $data['user_name'] ?? null,
            'password' => $data['password'],
            'status' => UserStatusEnum::Active->value,
            'email_verified_at' => $requireVerify ? null : now(),
        ]);

        if ($requireVerify) {
            try {
                $user->notify(new \App\Modules\Auth\Notifications\VerifyEmailNotification());
            } catch (\Throwable $e) {
                // Mail driver chưa cấu hình — log để dev biết, không fail register.
                \Log::warning('[register] sendVerifyEmail failed: '.$e->getMessage());
            }
        }

        $this->ensureCustomerEntry($user);

        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => (new UserResource($user))->resolve(),
            'available_organizations' => [],
            'current_organization_id' => null,
            'roles' => [],
            'permissions' => [],
            'abilities' => [],
        ];
    }

    public function login(string $login, string $password): array
    {
        $user = User::where('email', $login)
            ->orWhere('user_name', $login)
            ->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            return [
                'ok' => false,
                'type' => 'unauthorized',
                'message' => 'Thông tin đăng nhập không chính xác',
            ];
        }

        if ($user->status !== UserStatusEnum::Active->value) {
            return [
                'ok' => false,
                'type' => 'forbidden',
                'message' => 'Tài khoản của bạn đã bị khóa',
            ];
        }

        $this->ensureCustomerEntry($user);

        $token = $user->createToken('auth_token')->plainTextToken;
        $organizations = $this->getAccessibleOrganizations($user);
        $currentOrganization = $organizations[0] ?? null;
        $currentOrganizationId = $currentOrganization['id'] ?? null;
        $rolesAndPermissions = $this->getRolesAndPermissionsForOrganization($user, $currentOrganizationId);

        return [
            'ok' => true,
            'data' => [
                'access_token' => $token,
                'token_type' => 'Bearer',
                'user' => (new UserResource($user))->resolve(),
                'available_organizations' => $organizations,
                'current_organization_id' => $currentOrganizationId,
                'roles' => $rolesAndPermissions['roles'],
                'permissions' => $rolesAndPermissions['permissions'],
                'abilities' => $rolesAndPermissions['abilities'],
            ],
        ];
    }

    public function logout($user): void
    {
        $user->currentAccessToken()->delete();
    }

    public function forgotPassword(string $email): bool
    {
        return Password::sendResetLink(['email' => $email]) === Password::RESET_LINK_SENT;
    }

    public function resetPassword(string $email, string $password, string $token): bool
    {
        $status = Password::reset(
            ['email' => $email, 'password' => $password, 'token' => $token],
            function (User $user, string $newPassword) {
                $user->forceFill(['password' => Hash::make($newPassword)])->save();
            }
        );

        return $status === Password::PASSWORD_RESET;
    }

    public function switchOrganization(User $user, int $organizationId): array
    {
        $organization = Organization::query()
            ->whereKey($organizationId)
            ->where('status', 'active')
            ->first();

        if (! $organization) {
            return [
                'ok' => false,
                'type' => 'forbidden',
                'message' => 'Tổ chức không hợp lệ hoặc đã ngừng hoạt động.',
            ];
        }

        if (! $this->hasOrganizationAccess((int) $user->id, (int) $organization->id)) {
            return [
                'ok' => false,
                'type' => 'forbidden',
                'message' => 'Bạn không có quyền truy cập tổ chức đã chọn.',
            ];
        }

        $rolesAndPermissions = $this->getRolesAndPermissionsForOrganization($user, (int) $organization->id);

        return [
            'ok' => true,
            'data' => [
                'current_organization_id' => (int) $organization->id,
                'current_organization' => [
                    'id' => (int) $organization->id,
                    'name' => $organization->name,
                    'description' => $organization->description,
                ],
                'roles' => $rolesAndPermissions['roles'],
                'permissions' => $rolesAndPermissions['permissions'],
                'abilities' => $rolesAndPermissions['abilities'],
            ],
        ];
    }

    protected function getAccessibleOrganizations(User $user): array
    {
        $organizationIds = $this->getAccessibleOrganizationIds((int) $user->id);
        if (empty($organizationIds)) {
            return [];
        }

        return Organization::query()
            ->whereIn('id', $organizationIds)
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'description'])
            ->map(fn (Organization $organization) => [
                'id' => (int) $organization->id,
                'name' => $organization->name,
                'description' => $organization->description,
            ])
            ->values()
            ->all();
    }

    protected function getAccessibleOrganizationIds(int $userId): array
    {
        $tableNames = config('permission.table_names');
        $columnNames = config('permission.column_names');
        $modelMorphKey = $columnNames['model_morph_key'] ?? 'model_id';
        $teamForeignKey = $columnNames['team_foreign_key'] ?? 'organization_id';
        $modelType = \App\Modules\Core\Models\User::class;

        $roleOrgIds = DB::table($tableNames['model_has_roles'] ?? 'model_has_roles')
            ->where($modelMorphKey, $userId)
            ->where('model_type', $modelType)
            ->whereNotNull($teamForeignKey)
            ->pluck($teamForeignKey)
            ->map(fn ($id) => (int) $id)
            ->all();

        $permissionOrgIds = DB::table($tableNames['model_has_permissions'] ?? 'model_has_permissions')
            ->where($modelMorphKey, $userId)
            ->where('model_type', $modelType)
            ->whereNotNull($teamForeignKey)
            ->pluck($teamForeignKey)
            ->map(fn ($id) => (int) $id)
            ->all();

        return array_values(array_unique(array_merge($roleOrgIds, $permissionOrgIds)));
    }

    protected function hasOrganizationAccess(int $userId, int $organizationId): bool
    {
        return in_array($organizationId, $this->getAccessibleOrganizationIds($userId), true);
    }

    /**
     * Đảm bảo mỗi user khách hàng có 1 bản ghi tương ứng trong bảng customers
     * (shop_entries entity='customers') để admin CRM thấy. Lazy-create: nếu user
     * đã có entry thì bỏ qua. Gọi từ register() và login() để tự backfill cho
     * cả user cũ.
     */
    protected function ensureCustomerEntry(User $user): void
    {
        // Bỏ qua các user nội bộ (đã có role bất kỳ trên team nào) — họ là
        // staff/admin, không phải khách hàng. Spatie roles có team scoping nên
        // dùng raw query thay vì $user->roles()->exists() (phụ thuộc context).
        $tableNames = config('permission.table_names');
        $columnNames = config('permission.column_names');
        $morphKey = $columnNames['model_morph_key'] ?? 'model_id';
        $hasAnyRole = DB::table($tableNames['model_has_roles'] ?? 'model_has_roles')
            ->where($morphKey, $user->id)
            ->where('model_type', User::class)
            ->exists();
        if ($hasAnyRole) {
            return;
        }

        $exists = ShopEntry::where('entity', 'customers')
            ->where(function ($q) use ($user) {
                $q->where('data->user_id', (int) $user->id)
                    ->orWhere('data->user_id', (string) $user->id)
                    ->orWhere('data->email', $user->email);
            })
            ->exists();

        if ($exists) {
            return;
        }

        $joinedAt = $user->created_at?->format('d/m/Y') ?? now()->format('d/m/Y');
        $data = [
            'user_id' => (int) $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => '',
            'group_id' => 3, // Khách lẻ mặc định
            'orders_count' => 0,
            'total_spent' => 0,
            'joined_at' => $joinedAt,
            'active' => true,
        ];

        ShopEntry::create([
            'entity' => 'customers',
            'data' => $data,
            'search_text' => mb_substr(implode(' ', array_filter([
                $user->name, $user->email, $joinedAt,
            ])), 0, 4000),
        ]);
    }

    /**
     * Lấy danh sách vai trò và quyền hạn của user trong tổ chức, dùng cho Vue Casl.
     */
    protected function getRolesAndPermissionsForOrganization(User $user, ?int $organizationId): array
    {
        if ($organizationId === null) {
            return ['roles' => [], 'permissions' => [], 'abilities' => []];
        }

        setPermissionsTeamId($organizationId);
        $user->unsetRelation('roles');
        $user->unsetRelation('permissions');

        // getAllPermissions() = direct + từ vai trò; getPermissionNames() chỉ direct
        $permissions = $user->getAllPermissions()->pluck('name')->values()->unique()->all();

        return [
            'roles' => $user->getRoleNames()->values()->all(),
            'permissions' => $permissions,
            'abilities' => CaslAbilityConverter::toCaslAbilities($permissions),
        ];
    }
}
