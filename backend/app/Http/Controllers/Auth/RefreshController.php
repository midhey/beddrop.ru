<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class RefreshController extends Controller
{
    public function __invoke(): JsonResponse
    {
        try {
            $newToken = auth('api')->refresh();

            return response()->json([
                'access_token' => $newToken,
                'token_type'   => 'bearer',
                'expires_in'   => auth('api')->factory()->getTTL() * 60,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Не удалось обновить токен',
            ], 401);
        }
    }
}
