<?php

namespace App\Modules\Product\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

/**
 * Controller sản phẩm
 */
#[OA\Tag(name: "Sản phẩm", description: "Quản lý sản phẩm")]
class ProductController extends Controller
{
    #[OA\Get(path: "/api/products", summary: "Danh sách sản phẩm", tags: ["Sản phẩm"])]
    public function index(Request $request): JsonResponse
    {
        // TODO: Lấy từ DB + phân trang
        $products = [
            [
                'id' => 1,
                'name' => 'YukiMart Sample Product',
                'slug' => 'yukimart-sample-product',
                'price' => 99000,
                'description' => 'Sản phẩm mẫu YukiMart',
                'image' => 'https://via.placeholder.com/300',
                'category' => 'electronics',
                'stock' => 50,
                'created_at' => now()->toISOString(),
            ],
        ];

        return ApiResponse::success($products, 'Lấy danh sách sản phẩm thành công.');
    }

    #[OA\Get(path: "/api/products/{id}", summary: "Chi tiết sản phẩm", tags: ["Sản phẩm"])]
    public function show(int $id): JsonResponse
    {
        // TODO: Lấy từ DB
        return ApiResponse::success([
            'id' => $id,
            'name' => 'YukiMart Sample Product',
            'slug' => 'yukimart-sample-product',
            'price' => 99000,
            'description' => 'Sản phẩm mẫu YukiMart',
            'image' => 'https://via.placeholder.com/300',
            'category' => 'electronics',
            'stock' => 50,
            'created_at' => now()->toISOString(),
        ], 'Lấy sản phẩm thành công.');
    }

    #[OA\Post(path: "/api/products", summary: "Tạo sản phẩm", tags: ["Sản phẩm"])]
    public function store(Request $request): JsonResponse
    {
        // TODO: Validation + lưu DB
        $data = array_merge(['id' => 2], $request->all());
        return ApiResponse::created($data, 'Tạo sản phẩm thành công.');
    }

    #[OA\Put(path: "/api/products/{id}", summary: "Cập nhật sản phẩm", tags: ["Sản phẩm"])]
    public function update(Request $request, int $id): JsonResponse
    {
        // TODO: Validation + cập nhật DB
        $data = array_merge(['id' => $id], $request->all());
        return ApiResponse::success($data, 'Cập nhật sản phẩm thành công.');
    }

    #[OA\Delete(path: "/api/products/{id}", summary: "Xóa sản phẩm", tags: ["Sản phẩm"])]
    public function destroy(int $id): JsonResponse
    {
        // TODO: Xóa từ DB
        return ApiResponse::success(message: 'Xóa sản phẩm thành công.');
    }
}
