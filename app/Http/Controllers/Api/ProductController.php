<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "Products", description: "Product management endpoints")]
class ProductController extends Controller
{
    #[OA\Get(
        path: "/api/products",
        summary: "Get all products",
        description: "Returns a list of all products with optional pagination",
        tags: ["Products"],
        parameters: [
            new OA\Parameter(name: "page", in: "query", required: false, schema: new OA\Schema(type: "integer", default: 1)),
            new OA\Parameter(name: "per_page", in: "query", required: false, schema: new OA\Schema(type: "integer", default: 15)),
            new OA\Parameter(name: "search", in: "query", required: false, schema: new OA\Schema(type: "string")),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Successful operation",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "data", type: "array", items: new OA\Items(ref: "#/components/schemas/Product")),
                        new OA\Property(property: "message", type: "string", example: "Products retrieved successfully"),
                    ]
                )
            ),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        // TODO: Implement product listing with pagination
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

        return response()->json([
            'success' => true,
            'data' => $products,
            'message' => 'Products retrieved successfully',
        ]);
    }

    #[OA\Get(
        path: "/api/products/{id}",
        summary: "Get product by ID",
        description: "Returns a single product",
        tags: ["Products"],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Successful operation",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "data", ref: "#/components/schemas/Product"),
                    ]
                )
            ),
            new OA\Response(response: 404, description: "Product not found"),
        ]
    )]
    public function show(int $id): JsonResponse
    {
        // TODO: Implement get product by ID
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $id,
                'name' => 'YukiMart Sample Product',
                'slug' => 'yukimart-sample-product',
                'price' => 99000,
                'description' => 'Sản phẩm mẫu YukiMart',
                'image' => 'https://via.placeholder.com/300',
                'category' => 'electronics',
                'stock' => 50,
                'created_at' => now()->toISOString(),
            ],
        ]);
    }

    #[OA\Post(
        path: "/api/products",
        summary: "Create a new product",
        description: "Create a new product in the store",
        tags: ["Products"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["name", "price"],
                properties: [
                    new OA\Property(property: "name", type: "string", example: "New Product"),
                    new OA\Property(property: "price", type: "number", example: 150000),
                    new OA\Property(property: "description", type: "string", example: "Product description"),
                    new OA\Property(property: "category", type: "string", example: "electronics"),
                    new OA\Property(property: "stock", type: "integer", example: 100),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Product created successfully"),
            new OA\Response(response: 422, description: "Validation error"),
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        // TODO: Implement product creation with validation
        return response()->json([
            'success' => true,
            'data' => array_merge(['id' => 2], $request->all()),
            'message' => 'Product created successfully',
        ], 201);
    }

    #[OA\Put(
        path: "/api/products/{id}",
        summary: "Update a product",
        description: "Update an existing product",
        tags: ["Products"],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "name", type: "string"),
                    new OA\Property(property: "price", type: "number"),
                    new OA\Property(property: "description", type: "string"),
                    new OA\Property(property: "stock", type: "integer"),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Product updated successfully"),
            new OA\Response(response: 404, description: "Product not found"),
        ]
    )]
    public function update(Request $request, int $id): JsonResponse
    {
        // TODO: Implement product update
        return response()->json([
            'success' => true,
            'data' => array_merge(['id' => $id], $request->all()),
            'message' => 'Product updated successfully',
        ]);
    }

    #[OA\Delete(
        path: "/api/products/{id}",
        summary: "Delete a product",
        description: "Delete a product by ID",
        tags: ["Products"],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
        ],
        responses: [
            new OA\Response(response: 200, description: "Product deleted successfully"),
            new OA\Response(response: 404, description: "Product not found"),
        ]
    )]
    public function destroy(int $id): JsonResponse
    {
        // TODO: Implement product deletion
        return response()->json([
            'success' => true,
            'message' => 'Product deleted successfully',
        ]);
    }
}
