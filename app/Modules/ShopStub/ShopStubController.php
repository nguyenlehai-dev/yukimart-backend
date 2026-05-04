<?php

namespace App\Modules\ShopStub;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

/**
 * Stub controller cho module ShopAdmin (đã bị xóa khi reset DB).
 *
 * Mục đích: Frontend đang gọi nhiều endpoint /api/shop/* (products, categories, sections,
 * brands, news, promotions, customers, orders, inventory). Trong khi chờ user import lại
 * sản phẩm bằng Excel và dựng lại module ShopAdmin thật, stub này trả về dữ liệu rỗng
 * cho GET và 501 cho mutation để FE render được "empty state" thay vì lỗi 404.
 *
 * Khi module thật được khôi phục: xóa thư mục này và xóa route group trong routes/api.php.
 */
class ShopStubController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        if ($request->isMethod('GET')) {
            return response()->json([
                'success' => true,
                'message' => 'Module ShopAdmin chưa được khôi phục — trả về dữ liệu trống.',
                'data' => [],
                'meta' => [
                    'total' => 0,
                    'perPage' => 20,
                    'currentPage' => 1,
                    'lastPage' => 1,
                ],
                'stats' => [
                    'total' => 0,
                    'active' => 0,
                    'draft' => 0,
                    'outOfStock' => 0,
                ],
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Module ShopAdmin chưa được khôi phục. Vui lòng import sản phẩm bằng Excel sau khi module được dựng lại.',
        ], 501);
    }
}
