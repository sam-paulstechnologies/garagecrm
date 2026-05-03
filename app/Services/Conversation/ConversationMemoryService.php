<?php

namespace App\Services\Conversation;

use App\Models\Client\Lead;

class ConversationMemoryService
{
    /**
     * Get full memory array
     */
    public function getMemory(Lead $lead): array
    {
        $memory = $lead->conversation_data ?? [];

        if (!is_array($memory)) {
            $memory = [];
        }

        return $memory;
    }

    /**
     * Get a specific memory key
     */
    public function get(Lead $lead, string $key, $default = null)
    {
        $memory = $this->getMemory($lead);

        return $memory[$key] ?? $default;
    }

    /**
     * Update memory (merge)
     */
    public function updateMemory(Lead $lead, array $data): void
    {
        $memory = $this->getMemory($lead);

        $lead->conversation_data = array_merge($memory, $data);

        $lead->save();
    }

    /**
     * Set single value
     */
    public function set(Lead $lead, string $key, $value): void
    {
        $memory = $this->getMemory($lead);

        $memory[$key] = $value;

        $lead->conversation_data = $memory;

        $lead->save();
    }

    /**
     * Remove a key
     */
    public function forget(Lead $lead, string $key): void
    {
        $memory = $this->getMemory($lead);

        if (array_key_exists($key, $memory)) {
            unset($memory[$key]);
        }

        $lead->conversation_data = $memory;

        $lead->save();
    }

    /**
     * Clear entire conversation memory
     */
    public function clearMemory(Lead $lead): void
    {
        $lead->conversation_data = [];

        $lead->save();
    }
}