<?php

namespace App\Models\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "Product",
    title: "Product",
    description: "Product model",
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
    // Virtual schema for Swagger documentation
}
