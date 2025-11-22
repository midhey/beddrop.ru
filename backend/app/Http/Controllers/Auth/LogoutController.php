<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class LogoutController extends Controller
{
    public function __invoke()
    {
        Auth::guard('api')->logout();

        return response()->json(['message' => 'Вы успешно вышли из своего аккаунта']);
    }
}
