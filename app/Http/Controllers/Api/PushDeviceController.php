<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notifications\PushDevice;
use App\Notifications\PushDeviceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PushDeviceController extends Controller
{
    public function store(Request $request, PushDeviceService $devices): JsonResponse
    {
        $data = $request->validate([
            'provider' => 'required|string|max:32',
            'token' => 'required|string|max:4096',
            'platform' => 'nullable|string|max:32',
            'device_name' => 'nullable|string|max:255',
        ]);

        $device = $devices->register(
            $request->user(),
            $data['provider'],
            $data['token'],
            $data['platform'] ?? null,
            $data['device_name'] ?? null,
        );

        return response()->json(['id' => $device->id, 'status' => $device->status], 201);
    }

    public function destroy(Request $request, PushDevice $device, PushDeviceService $devices): JsonResponse
    {
        $devices->revoke($request->user(), $device);

        return response()->json(['status' => 'revoked']);
    }
}
