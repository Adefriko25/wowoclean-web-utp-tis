<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class AuthController extends Controller
{
    #[OA\Post(
        path: "/api/v1/login",
        summary: "Endpoint Login",
        tags: ["Auth"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "email", type: "string", example: "admin@wowoclean.com"),
                    new OA\Property(property: "password", type: "string", example: "password")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Berhasil login & dapat token"),
            new OA\Response(response: 401, description: "Unauthorized")
        ]
    )]
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (!$token = auth('api')->attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return response()->json([
            'token' => $token,
            'user'  => auth('api')->user()
        ]);
    }

    #[OA\Get(
        path: "/api/v1/profile",
        summary: "Ambil data user yang sedang login",
        security: [["bearerAuth" => []]],
        tags: ["Auth"],
        responses: [
            new OA\Response(response: 200, description: "Data profile berhasil diambil"),
            new OA\Response(response: 401, description: "Unauthorized")
        ]
    )]
    public function profile()
    {
        return response()->json(auth('api')->user());
    }

    #[OA\Post(
        path: "/api/v1/logout",
        summary: "Logout & invalidate token",
        security: [["bearerAuth" => []]],
        tags: ["Auth"],
        responses: [
            new OA\Response(response: 200, description: "Berhasil logout"),
            new OA\Response(response: 401, description: "Unauthorized")
        ]
    )]
    public function logout()
    {
        auth('api')->logout();
        return response()->json(['message' => 'Berhasil logout']);
    }
}