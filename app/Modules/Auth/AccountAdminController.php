<?php

namespace App\Modules\Auth;

use App\Http\Controllers\Controller;
use App\Modules\Core\Enums\UserStatusEnum;
use App\Modules\Core\Models\User;
use App\Modules\ShopProduct\Models\ShopEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Endpoint admin để thao tác trên khách hàng (entity customers) có sync với
 * bảng users — tắt khách hàng = khoá user + huỷ mọi token.
 */
class AccountAdminController extends Controller
{
    /**
     * Toggle/đổi trạng thái active của khách hàng. Khi tắt:
     * - data->active = false trên ShopEntry
     * - users.status = inactive (nếu có user_id)
     * - revoke toàn bộ Sanctum token của user → request kế tiếp middleware
     *   user.active sẽ trả 401, FE auto logout.
     */
    public function setCustomerActive(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'active' => ['required', 'boolean'],
        ]);

        $entry = ShopEntry::where('entity', 'customers')->find($id);
        if (! $entry) {
            return $this->notFound('Không tìm thấy khách hàng');
        }

        // Atomic: nếu update users.status fail giữa chừng, rollback luôn customer entry.
        $userLocked = DB::transaction(function () use ($entry, $validated) {
            $data = (array) $entry->data;
            $data['active'] = (bool) $validated['active'];
            $entry->update([
                'data' => $data,
                'search_text' => mb_substr(implode(' ', array_filter([
                    $data['name'] ?? null, $data['email'] ?? null, $data['phone'] ?? null,
                ])), 0, 4000),
            ]);

            $userId = isset($data['user_id']) ? (int) $data['user_id'] : 0;
            if ($userId <= 0) {
                return false;
            }

            $user = User::query()->whereKey($userId)->lockForUpdate()->first();
            // Tuyệt đối không khoá Super Admin.
            if (! $user || $user->hasRole('Super Admin')) {
                return false;
            }

            $user->status = $validated['active']
                ? UserStatusEnum::Active->value
                : UserStatusEnum::Inactive->value;
            $user->save();

            if (! $validated['active']) {
                $user->tokens()->delete();
                return true;
            }
            return false;
        });

        $data = (array) $entry->fresh()->data;

        return $this->success([
            'id' => $entry->id,
            'active' => (bool) ($data['active'] ?? false),
            'user_locked' => $userLocked,
        ], $validated['active'] ? 'Đã kích hoạt khách hàng' : 'Đã khoá tài khoản khách hàng');
    }
}
