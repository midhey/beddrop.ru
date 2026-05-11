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
        return response()->json([
            'groups' => $settings->editableSettings(),
        ]);
    }

    public function update(Request $request, LogisticsSettingsService $settings): JsonResponse
    {
        $data = $request->validate([
            'settings' => ['required', 'array'],
            'settings.*' => ['nullable'],
        ]);

        $settings->update($data['settings']);

        return response()->json([
            'groups' => $settings->editableSettings(),
        ]);
    }
}
