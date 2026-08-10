<?php

namespace App\Http\Controllers;

use App\Models\Notifications\NotificationIntent;
use Illuminate\Http\Request;

final class NotificationCenterController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        abort_unless($user?->company_id, 403);

        $intents = NotificationIntent::query()
            ->where('company_id', $user->company_id)
            ->where('user_id', $user->id)
            ->latest('id')
            ->paginate(30);

        return view('notifications.index', compact('intents'));
    }
}
