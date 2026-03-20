<?php

namespace App\Helpers;

use Illuminate\Http\JsonResponse;

/**
 * Helper JSON response chuẩn cho toàn bộ API.
 *
 * Tất cả response đều có format:
 * {
 *   "success": true/false,
 *   "message": "...",
 *   "data": { ... },         // chỉ khi có data
 *   "error_code": "...",     // chỉ khi lỗi
 *   "errors": { ... }        // chỉ khi validation lỗi
 * }
 *
 * Dùng:
 *   ApiResponse::success($data, 'Thành công');
 *   ApiResponse::error('Lỗi gì đó', 'ERROR_CODE', 400);
 *   ApiResponse::unauthorized('Chưa đăng nhập');
 */
class ApiResponse
{
    /**
     * Response thành công — 200
     */
    public static function success(mixed $data = null, string $message = 'Thành công.'): JsonResponse
    {
        $response = [
            'success' => true,
            'message' => $message,
        ];

        if (!is_null($data)) {
            $response['data'] = $data;
        }

        return response()->json($response);
    }

    /**
     * Response tạo mới thành công — 201
     */
    public static function created(mixed $data = null, string $message = 'Tạo thành công.'): JsonResponse
    {
        $response = [
            'success' => true,
            'message' => $message,
        ];

        if (!is_null($data)) {
            $response['data'] = $data;
        }

        return response()->json($response, 201);
    }

    /**
     * Response lỗi chung
     */
    public static function error(string $message = 'Có lỗi xảy ra.', string $errorCode = 'ERROR', int $status = 400): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'error_code' => $errorCode,
        ], $status);
    }

    /**
     * Chưa đăng nhập / token không hợp lệ — 401
     */
    public static function unauthorized(string $message = 'Chưa đăng nhập.', string $errorCode = 'UNAUTHORIZED'): JsonResponse
    {
        return self::error($message, $errorCode, 401);
    }

    /**
     * Không có quyền — 403
     */
    public static function forbidden(string $message = 'Không có quyền truy cập.', string $errorCode = 'FORBIDDEN'): JsonResponse
    {
        return self::error($message, $errorCode, 403);
    }

    /**
     * Không tìm thấy — 404
     */
    public static function notFound(string $message = 'Không tìm thấy.', string $errorCode = 'NOT_FOUND'): JsonResponse
    {
        return self::error($message, $errorCode, 404);
    }

    /**
     * Lỗi validation — 422
     */
    public static function validation(array $errors, string $message = 'Dữ liệu không hợp lệ.'): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], 422);
    }

    /**
     * Quá nhiều request — 429
     */
    public static function tooMany(string $message = 'Quá nhiều yêu cầu. Vui lòng thử lại sau.'): JsonResponse
    {
        return self::error($message, 'TOO_MANY_REQUESTS', 429);
    }

    /**
     * Lỗi server — 500
     */
    public static function serverError(string $message = 'Lỗi hệ thống.', string $errorCode = 'SERVER_ERROR'): JsonResponse
    {
        return self::error($message, $errorCode, 500);
    }
}
