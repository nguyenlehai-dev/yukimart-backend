<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "Authentication", description: "Auth endpoints")]
class AuthController extends Controller
{
    #[OA\Post(
        path: "/api/auth/login",
        summary: "User login",
        description: "Authenticate user and return token",
        tags: ["Authentication"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["email", "password"],
                properties: [
                    new OA\Property(property: "email", type: "string", format: "email", example: "user@yukimart.com"),
                    new OA\Property(property: "password", type: "string", format: "password", example: "password123"),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Login successful",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "token", type: "string", example: "eyJ0eXAiOiJKV1Q..."),
                        new OA\Property(property: "user", type: "object"),
                    ]
                )
            ),
            new OA\Response(response: 401, description: "Invalid credentials"),
        ]
    )]
    public function login(Request $request): JsonResponse
    {
        // TODO: Implement actual authentication
        return response()->json([
            'success' => true,
            'token' => 'sample-jwt-token',
            'user' => [
                'id' => 1,
                'name' => 'YukiMart User',
                'email' => $request->email,
            ],
        ]);
    }

    #[OA\Post(
        path: "/api/auth/register",
        summary: "User registration",
        description: "Register a new user account",
        tags: ["Authentication"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["name", "email", "password", "password_confirmation"],
                properties: [
                    new OA\Property(property: "name", type: "string", example: "Nguyen Van A"),
                    new OA\Property(property: "email", type: "string", format: "email", example: "user@yukimart.com"),
                    new OA\Property(property: "password", type: "string", format: "password", example: "password123"),
                    new OA\Property(property: "password_confirmation", type: "string", format: "password", example: "password123"),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "User registered successfully"),
            new OA\Response(response: 422, description: "Validation error"),
        ]
    )]
    public function register(Request $request): JsonResponse
    {
        // TODO: Implement actual registration
        return response()->json([
            'success' => true,
            'message' => 'User registered successfully',
            'user' => [
                'id' => 1,
                'name' => $request->name,
                'email' => $request->email,
            ],
        ], 201);
    }

    #[OA\Post(
        path: "/api/auth/logout",
        summary: "User logout",
        tags: ["Authentication"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(response: 200, description: "Logout successful"),
        ]
    )]
    public function logout(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully',
        ]);
    }
}
