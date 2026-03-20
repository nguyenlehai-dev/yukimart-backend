<?php

namespace App\Modules\Product\Models\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "Product",
    title: "Sản phẩm",
    description: "Schema sản phẩm cho Swagger",
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "name", type: "string", example: "YukiMart Product"),
        new OA\Property(property: "slug", type: "string", example: "yukimart-product"),
        new OA\Property(property: "price", type: "number", format: "float", example: 99000),
        new OA\Property(property: "description", type: "string", example: "Mô tả sản phẩm"),
        new OA\Property(property: "image", type: "string", example: "https://via.placeholder.com/300"),
        new OA\Property(property: "category", type: "string", example: "electronics"),
        new OA\Property(property: "stock", type: "integer", example: 50),
        new OA\Property(property: "created_at", type: "string", format: "date-time"),
        new OA\Property(property: "updated_at", type: "string", format: "date-time"),
    ]
)]
class ProductSchema
{
    // Schema ảo cho tài liệu Swagger
}
