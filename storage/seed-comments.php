<?php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Modules\ShopProduct\Models\ShopEntry;
use App\Modules\ShopProduct\Models\ShopProduct;

$productId = (int) ($argv[1] ?? 2457);
$product = ShopProduct::find($productId);
if (! $product) {
    echo "PRODUCT NOT FOUND: $productId\n";
    exit(1);
}
$productName = $product->name ?? '';
$productSku = $product->sku ?? '';

$samples = [
    [
        'type' => 'review',
        'author' => 'Nguyễn Văn A',
        'email' => 'nguyenvana@example.com',
        'rating' => 5,
        'content' => 'Sản phẩm chất lượng, giao nhanh, đóng gói cẩn thận. Rất hài lòng!',
        'verified' => true,
        'reply' => 'Cảm ơn anh/chị đã đánh giá tích cực. YukiMart luôn nỗ lực phục vụ tốt hơn!',
        'reply_date' => now()->format('d/m/Y'),
    ],
    [
        'type' => 'review',
        'author' => 'Trần Thị B',
        'email' => 'tranthib@example.com',
        'rating' => 4,
        'content' => 'Hàng đúng mô tả, hộp hơi móp nhẹ nhưng sản phẩm bên trong nguyên vẹn.',
        'verified' => true,
        'reply' => '',
    ],
    [
        'type' => 'question',
        'author' => 'Lê Văn C',
        'email' => 'levanc@example.com',
        'content' => 'Sản phẩm này có dùng được cho người tiểu đường không ạ?',
        'verified' => false,
        'reply' => 'Chào anh, sản phẩm phù hợp dùng kèm chế độ ăn kiểm soát đường. Anh nên tham khảo bác sĩ trước khi dùng.',
        'reply_date' => now()->format('d/m/Y'),
    ],
];

$created = [];
foreach ($samples as $s) {
    $payload = array_merge($s, [
        'product_id' => $productId,
        'product_name' => $productName,
        'product_sku' => $productSku,
        'status' => 'approved',
        'likes' => random_int(0, 10),
        'date' => now()->format('d/m/Y'),
    ]);

    $entry = ShopEntry::create([
        'entity' => 'product-comments',
        'data' => $payload,
        'search_text' => mb_substr(implode(' ', array_filter(array_map(
            fn ($v) => is_scalar($v) ? (string) $v : '',
            $payload
        ))), 0, 4000),
    ]);
    $created[] = $entry->id;
}

echo "PRODUCT: #$productId — $productName\n";
echo 'CREATED COMMENT IDS: '.implode(', ', $created)."\n";
