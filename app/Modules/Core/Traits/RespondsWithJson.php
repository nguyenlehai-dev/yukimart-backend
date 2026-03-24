<?php

namespace App\Modules\Core\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Validation\ValidationException;

/**
 * Trait chuẩn hóa response API JSON cho frontend.
 *
 * Cấu trúc success: { success: true, message?, data? }
 * Cấu trúc error:   { success: false, message, errors?, code? }
 */
trait RespondsWithJson
{
    /**
     * Trả về response thành công với dữ liệu thuần (stats, destroy, bulk, ...).
     */
    protected function success(
        mixed $data = null,
        ?string $message = null,
        int $statusCode = 200
    ): JsonResponse {
        $payload = array_filter([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], fn ($v) => $v !== null);

        return response()->json($payload, $statusCode);
    }

    /**
     * Trả về response thành công với JsonResource (show, store, update, changeStatus).
     */
    protected function successResource(
        JsonResource $resource,
        ?string $message = null,
        int $statusCode = 200
    ): JsonResponse {
        $additional = array_filter([
            'success' => true,
            'message' => $message,
        ], fn ($v) => $v !== null);

        return $resource->additional($additional)->response()->setStatusCode($statusCode);
    }

    /**
     * Trả về response thành công với ResourceCollection (index, tree).
     * Thêm success, message vào envelope của collection.
     */
    protected function successCollection(
        ResourceCollection $collection,
        ?string $message = null,
        int $statusCode = 200
    ): JsonResponse {
        $additional = array_filter([
            'success' => true,
            'message' => $message,
        ], fn ($v) => $v !== null);

        return $collection->additional($additional)->response()->setStatusCode($statusCode);
    }

    /**
     * Trả về response lỗi.
     */
    protected function error(
        string $message,
        int $statusCode = 400,
        ?array $errors = null,
        ?string $code = null
    ): JsonResponse {
        $payload = array_filter([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
            'code' => $code,
        ], fn ($v) => $v !== null);

        return response()->json($payload, $statusCode);
    }

    /**
     * Trả về lỗi validation (422).
     */
    protected function validationError(ValidationException $exception): JsonResponse
    {
        return $this->error(
            $exception->getMessage(),
            422,
            $exception->errors(),
            'VALIDATION_ERROR'
        );
    }

    /**
     * Trả về lỗi chưa xác thực (401).
     */
    protected function unauthorized(?string $message = null): JsonResponse
    {
        return $this->error(
            $message ?? 'Chưa xác thực',
            401,
            null,
            'UNAUTHORIZED'
        );
    }

    /**
     * Trả về lỗi không có quyền (403).
     */
    protected function forbidden(?string $message = null): JsonResponse
    {
        return $this->error(
            $message ?? 'Không có quyền truy cập',
            403,
            null,
            'FORBIDDEN'
        );
    }

    /**
     * Trả về không tìm thấy (404).
     */
    protected function notFound(?string $message = null): JsonResponse
    {
        return $this->error(
            $message ?? 'Không tìm thấy tài nguyên',
            404,
            null,
            'NOT_FOUND'
        );
    }

    /**
     * Trả về xung đột dữ liệu (409).
     */
    protected function conflict(?string $message = null): JsonResponse
    {
        return $this->error(
            $message ?? 'Dữ liệu đã tồn tại hoặc xung đột',
            409,
            null,
            'CONFLICT'
        );
    }

    /**
     * Trả về thành công không nội dung (204) – dùng cho DELETE đơn giản.
     * Một số frontend thích 200 + message, nên giữ success() với message.
     */
    protected function noContent(): JsonResponse
    {
        return response()->json(null, 204);
    }
}
