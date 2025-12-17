<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Conversation;

class ConversationPolicy
{
    /**
     * Any logged-in user with same company can see the inbox list.
     */
    public function viewAny(User $user): bool
    {
        return (bool) $user->company_id;
    }

    /**
     * Can view a specific conversation.
     */
    public function view(User $user, Conversation $conversation): bool
    {
        if (!$user->company_id) {
            return false;
        }

        // Basic tenant isolation
        if ((int) $conversation->company_id !== (int) $user->company_id) {
            return false;
        }

        // If you later add per-user routing, you can check participants here.
        return true;
    }

    /**
     * Can send messages into a conversation.
     */
    public function reply(User $user, Conversation $conversation): bool
    {
        return $this->view($user, $conversation);
    }
}
