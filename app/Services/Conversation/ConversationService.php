<?php

namespace App\Services\Conversation;

use App\Models\Client\Lead;
use App\Models\Conversation;
use Illuminate\Support\Facades\Log;

class ConversationService
{
    /**
     * Resolve existing conversation or create a new one
     */
    public function resolve(int $companyId, Lead $lead): ?Conversation
    {
        try {

            // 🔥 Use normalized phone (fallback if missing)
            $digits = $lead->phone_norm ?? preg_replace('/\D+/', '', (string) $lead->phone);

            if (!$digits) {
                Log::warning('[ConversationService] Missing phone for lead', [
                    'lead_id' => $lead->id
                ]);
                return null;
            }

            /*
            |--------------------------------------------------------------------------
            | 🔍 Find existing conversation
            |--------------------------------------------------------------------------
            */

            $conversation = Conversation::where('company_id', $companyId)
                ->where('customer_phone', $digits)
                ->first();

            /*
            |--------------------------------------------------------------------------
            | 🔁 Update existing conversation
            |--------------------------------------------------------------------------
            */

            if ($conversation) {

                $conversation->update([
                    'latest_message_at'  => now(),
                    'last_message_at'    => now(),
                    'is_whatsapp_linked'=> 1,
                ]);

                Log::info('[ConversationService] Existing conversation reused', [
                    'conversation_id' => $conversation->id,
                    'lead_id'         => $lead->id
                ]);

                return $conversation;
            }

            /*
            |--------------------------------------------------------------------------
            | 🆕 Create new conversation
            |--------------------------------------------------------------------------
            */

            $conversation = Conversation::create([
                'company_id'         => $companyId,
                'lead_id'            => $lead->id,
                'client_id'          => $lead->client_id,
                'customer_name'      => $lead->name ?? 'Customer',
                'customer_phone'     => $digits,
                'subject'            => "Chat with " . ($lead->name ?? 'Customer'),
                'latest_message_at'  => now(),
                'last_message_at'    => now(),
                'is_whatsapp_linked' => 1,
                'unread_count'       => 0,
            ]);

            Log::info('[ConversationService] New conversation created', [
                'conversation_id' => $conversation->id,
                'lead_id'         => $lead->id
            ]);

            return $conversation;

        } catch (\Throwable $e) {

            Log::error('[ConversationService] Failed resolving conversation', [
                'lead_id' => $lead->id ?? null,
                'error'   => $e->getMessage()
            ]);

            return null;
        }
    }
}