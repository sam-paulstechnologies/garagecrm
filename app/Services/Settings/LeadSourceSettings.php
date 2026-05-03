<?php

namespace App\Services\Settings;

class LeadSourceSettings
{
    public function save(array $data): void
    {
        foreach ($data as $key => $value) {
            settings()->set("leads.{$key}", $value);
        }
    }
}
