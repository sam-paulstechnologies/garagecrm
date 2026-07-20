<?php

namespace App\Http\Controllers\SuperAdmin\Marketing;

use App\Http\Controllers\Controller;
use App\Models\PlatformMarketing\PlatformMarketingConversation;

class ConversationController extends Controller
{
    public function index()
    {
        return view('super_admin.marketing.conversations.index', [
            'conversations' => PlatformMarketingConversation::with('prospect')->latest('last_message_at')->paginate(20),
        ]);
    }

    public function show(PlatformMarketingConversation $conversation)
    {
        return view('super_admin.marketing.conversations.show', [
            'conversation' => $conversation->load(['prospect', 'messages']),
        ]);
    }

    public function pauseAi(PlatformMarketingConversation $conversation)
    {
        $conversation->forceFill(['ai_enabled' => false])->save();

        return back()->with('success', 'AI paused for this conversation.');
    }

    public function resumeAi(PlatformMarketingConversation $conversation)
    {
        $conversation->forceFill(['ai_enabled' => true, 'human_takeover' => false])->save();

        return back()->with('success', 'AI resumed.');
    }

    public function takeover(PlatformMarketingConversation $conversation)
    {
        $conversation->forceFill(['human_takeover' => true, 'ai_enabled' => false])->save();

        return back()->with('success', 'Conversation moved to human takeover.');
    }
}
