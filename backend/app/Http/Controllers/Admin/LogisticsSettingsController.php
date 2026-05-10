<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Logistics\LogisticsSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LogisticsSettingsController extends Controller
{
    public function index(LogisticsSettingsService $settings): JsonResponse
    {
        $this->ensureAdmin();

        return response()->json([
            'groups' => $settings->editableSettings(),
        ]);
    }

    public function update(Request $request, LogisticsSettingsService $settings): JsonResponse
    {
        $this->ensureAdmin();

        $data = $request->validate([
            'settings' => ['required', 'array'],
            'settings.*' => ['nullable'],
        ]);

        $settings->update($data['settings']);

        return response()->json([
            'groups' => $settings->editableSettings(),
        ]);
    }

    private function ensureAdmin(): void
    {
        if (!request()->user()?->is_admin) {
            abort(403, 'Доступно только администратору.');
        }
    }
}
