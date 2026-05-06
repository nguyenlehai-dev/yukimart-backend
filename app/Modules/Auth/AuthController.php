<?php

namespace App\Modules\Auth;

use App\Http\Controllers\Controller;
use App\Modules\Auth\Requests\ForgotPasswordRequest;
use App\Modules\Auth\Requests\LoginRequest;
use App\Modules\Auth\Requests\RegisterRequest;
use App\Modules\Auth\Requests\ResetPasswordRequest;
use App\Modules\Auth\Requests\SwitchOrganizationRequest;
use App\Modules\Auth\Notifications\VerifyEmailNotification;
use App\Modules\Auth\Services\AuthService;
use App\Modules\Auth\Services\CaslAbilityConverter;
use App\Modules\Auth\Services\RecaptchaService;
use App\Modules\Core\Models\User;
use Illuminate\Support\Facades\URL;
use App\Modules\Core\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * @group Auth
 *
 * Xác thực: đăng nhập, đăng xuất, quên mật khẩu, đặt lại mật khẩu
 */
class AuthController extends Controller
{
    public function __construct(
        private AuthService $authService,
        private RecaptchaService $recaptcha,
    ) {}

    /**
     * Đăng nhập
     *
     * Trả về access_token, thông tin user và danh sách organization user có thể truy cập.
     *
     * @unauthenticated
     *
     * @bodyParam email string required Email hoặc tên đăng nhập (user_name). Example: admin@example.com
     * @bodyParam password string required Mật khẩu. Example: password
     *
     * @response 200 {"success": true, "message": "Đăng nhập thành công.", "data": {"access_token": "1|xxx...", "token_type": "Bearer", "user": {"id": 1, "name": "Admin"}, "available_organizations": [{"id": 2, "name": "Sở Nội vụ"}], "current_organization_id": 2, "roles": ["admin"], "permissions": ["users.index", "users.store"], "abilities": [{"action": "index", "subject": "User"}, {"action": "store", "subject": "User"}]}}
     */
    public function login(LoginRequest $request)
    {
        if (! $this->recaptcha->verify($request->input('recaptcha_token'), 'login')) {
            return $this->forbidden('Xác thực bảo mật thất bại. Vui lòng thử lại.');
        }
        $result = $this->authService->login($request->email, $request->password);

        if (! $result['ok']) {
            if ($result['type'] === 'unauthorized') {
                return $this->unauthorized($result['message']);
            }

            return $this->forbidden($result['message']);
        }

        return $this->success($result['data'], 'Đăng nhập thành công.');
    }

    /**
     * Đăng ký tài khoản khách hàng.
     *
     * Tài khoản mới không có quyền quản trị shop. Frontend dùng token này cho khu vực tài khoản khách hàng.
     *
     * @unauthenticated
     */
    public function register(RegisterRequest $request)
    {
        if (! $this->recaptcha->verify($request->input('recaptcha_token'), 'register')) {
            return $this->forbidden('Xác thực bảo mật thất bại. Vui lòng thử lại.');
        }
        $data = $this->authService->register($request->validated());

        return $this->success($data, 'Đăng ký thành công.', 201);
    }

    /**
     * Lấy thông tin user đăng nhập hiện tại kèm roles và permissions của tổ chức đang chọn.
     *
     * Dùng để Vue Casl khởi tạo ability khi refresh trang. Cần header X-Organization-Id (middleware set.permissions.team đã đặt ngữ cảnh).
     *
     * @response 200 {"success": true, "data": {"user": {"id": 1, "name": "Admin"}, "roles": ["admin"], "permissions": ["users.index", "users.store"], "abilities": [{"action": "index", "subject": "User"}, {"action": "store", "subject": "User"}]}}
     */
    public function me(Request $request)
    {
        $user = $request->user();
        // getAllPermissions() = direct + từ vai trò
        $permissions = $user->getAllPermissions()->pluck('name')->values()->unique()->all();

        return $this->success([
            'user' => (new UserResource($user))->resolve(),
            'roles' => $user->getRoleNames()->values()->all(),
            'permissions' => $permissions,
            'abilities' => CaslAbilityConverter::toCaslAbilities($permissions),
        ]);
    }

    /**
     * Đăng xuất
     *
     * Hủy token hiện tại.
     *
     * @response 200 {"success": true, "message": "Đã đăng xuất"}
     */
    public function logout(Request $request)
    {
        $this->authService->logout($request->user());

        return $this->success(null, 'Đã đăng xuất');
    }

    public function updateAvatar(Request $request)
    {
        $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $user = $request->user();
        $user->clearMediaCollection('avatar');

        $file = $request->file('avatar');
        $extension = $file->getClientOriginalExtension() ?: 'jpg';
        $user->addMedia($file)
            ->usingName('avatar-'.$user->id)
            ->usingFileName('avatar-'.$user->id.'-'.Str::random(8).'.'.$extension)
            ->toMediaCollection('avatar', config('media-library.disk_name', 'public'));

        return $this->success([
            'user' => (new UserResource($user->fresh()))->resolve(),
        ], 'Đã cập nhật ảnh đại diện.');
    }

    /**
     * Chuyển tổ chức làm việc
     *
     * Chọn organization để frontend gắn vào header `X-Organization-Id` cho các request tiếp theo.
     *
     * @bodyParam organization_id integer required ID tổ chức muốn chuyển. Example: 2
     *
     * @response 200 {"success": true, "message": "Đã chuyển tổ chức làm việc.", "data": {"current_organization_id": 2, "current_organization": {"id": 2, "name": "Sở Nội vụ"}, "roles": ["admin"], "permissions": ["users.index", "users.store"], "abilities": [{"action": "index", "subject": "User"}, {"action": "store", "subject": "User"}]}}
     */
    /**
     * Xác thực email qua signed URL (FE chuyển query về đây).
     *
     * @unauthenticated
     */
    public function verifyEmail(Request $request, int $id, string $hash)
    {
        if (! $request->hasValidSignature()) {
            return $this->error('Link xác thực không hợp lệ hoặc đã hết hạn', 400, null, 'INVALID_SIGNATURE');
        }
        $user = User::find($id);
        if (! $user) {
            return $this->notFound('Không tìm thấy tài khoản');
        }
        if (! hash_equals(sha1($user->email), $hash)) {
            return $this->error('Link xác thực không khớp', 400, null, 'HASH_MISMATCH');
        }
        if ($user->email_verified_at === null) {
            $user->email_verified_at = now();
            $user->save();
        }
        return $this->success(['email_verified_at' => $user->email_verified_at?->toIso8601String()], 'Đã xác thực email');
    }

    /**
     * Gửi lại email xác thực cho user đang đăng nhập.
     */
    public function resendVerification(Request $request)
    {
        $user = $request->user();
        if (! $user) {
            return $this->unauthorized();
        }
        if ($user->email_verified_at !== null) {
            return $this->success(null, 'Email đã được xác thực trước đó');
        }
        $user->notify(new VerifyEmailNotification());
        return $this->success(null, 'Đã gửi email xác thực — kiểm tra hộp thư.');
    }

    public function switchOrganization(SwitchOrganizationRequest $request)
    {
        $result = $this->authService->switchOrganization($request->user(), (int) $request->organization_id);

        if (! $result['ok']) {
            return $this->forbidden($result['message']);
        }

        return $this->success($result['data'], 'Đã chuyển tổ chức làm việc.');
    }

    /**
     * Quên mật khẩu
     *
     * Gửi link đặt lại mật khẩu qua email.
     *
     * @unauthenticated
     *
     * @bodyParam email string required Email tài khoản. Example: user@example.com
     *
     * @response 200 {"success": true, "message": "Link reset đã được gửi vào Email"}
     */
    public function forgotPassword(ForgotPasswordRequest $request)
    {
        $ok = $this->authService->forgotPassword($request->email);

        return $ok
            ? $this->success(null, 'Link reset đã được gửi vào Email')
            : $this->error('Không thể gửi mail', 400);
    }

    /**
     * Đặt lại mật khẩu
     *
     * Đặt mật khẩu mới dùng token từ email reset.
     *
     * @unauthenticated
     *
     * @bodyParam email string required Email tài khoản. Example: user@example.com
     * @bodyParam password string required Mật khẩu mới (tối thiểu 6 ký tự, có xác nhận). Example: newpassword123
     * @bodyParam password_confirmation string required Xác nhận mật khẩu.
     * @bodyParam token string required Token từ email reset.
     *
     * @response 200 {"success": true, "message": "Mật khẩu đã được đặt lại"}
     */
    public function resetPassword(ResetPasswordRequest $request)
    {
        $ok = $this->authService->resetPassword($request->email, $request->password, $request->token);

        return $ok
            ? $this->success(null, 'Mật khẩu đã được đặt lại')
            : $this->error('Không thể đặt lại mật khẩu', 400);
    }
}
