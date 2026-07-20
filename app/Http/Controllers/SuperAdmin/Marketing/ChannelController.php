<?php

namespace App\Http\Controllers\SuperAdmin\Marketing;

use App\Http\Controllers\Controller;
use App\Models\PlatformMarketing\PlatformMarketingChannel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class ChannelController extends Controller
{
    public function index()
    {
        return view('super_admin.marketing.channel.index', [
            'channel' => PlatformMarketingChannel::first(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'display_phone_number' => ['nullable', 'string', 'max:40'],
            'phone_number_id' => ['required', 'string', 'max:80'],
            'waba_id' => ['nullable', 'string', 'max:80'],
            'meta_business_id' => ['nullable', 'string', 'max:80'],
            'access_token' => ['nullable', 'string'],
            'verify_token' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $channel = PlatformMarketingChannel::query()->firstOrNew(['phone_number_id' => $validated['phone_number_id']]);

        $channel->fill([
            'name' => $validated['name'],
            'display_phone_number' => $validated['display_phone_number'] ?? '+971527427692',
            'phone_number_id' => $validated['phone_number_id'],
            'waba_id' => $validated['waba_id'] ?? null,
            'meta_business_id' => $validated['meta_business_id'] ?? null,
            'connection_status' => filled($validated['access_token'] ?? null) || filled($channel->access_token) ? 'connected' : 'not_connected',
            'webhook_health' => 'configured',
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ]);

        if (filled($validated['access_token'] ?? null)) {
            $channel->access_token = Crypt::encryptString($validated['access_token']);
        }

        if (filled($validated['verify_token'] ?? null)) {
            $channel->verify_token = Crypt::encryptString($validated['verify_token']);
        }

        $channel->save();

        return back()->with('success', 'Platform WhatsApp channel saved. Raw credentials are not displayed.');
    }
}
