<?php

namespace App\Console\Commands;

use App\Models\PlatformMarketing\PlatformMarketingChannel;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Crypt;

class ConfigurePlatformMarketingChannel extends Command
{
    protected $signature = 'platform-marketing:configure-channel
        {--name=PaulsTechnologies LLC}
        {--display-phone=+971527427692}
        {--phone-number-id=1070868312780019}
        {--waba-id=}
        {--business-id=}
        {--access-token=}
        {--verify-token=}
        {--active : Mark the platform channel active}';

    protected $description = 'Configure the isolated platform marketing WhatsApp channel without changing tenant company credentials.';

    public function handle(): int
    {
        $phoneNumberId = trim((string) $this->option('phone-number-id'));

        $channel = PlatformMarketingChannel::query()->firstOrNew(['phone_number_id' => $phoneNumberId]);

        $channel->fill([
            'name' => (string) $this->option('name'),
            'display_phone_number' => (string) $this->option('display-phone'),
            'phone_number_id' => $phoneNumberId,
            'waba_id' => $this->option('waba-id') ?: null,
            'meta_business_id' => $this->option('business-id') ?: null,
            'connection_status' => $this->option('access-token') || $channel->access_token ? 'connected' : 'not_connected',
            'webhook_health' => 'configured',
            'is_active' => (bool) $this->option('active'),
        ]);

        if ($this->option('access-token')) {
            $channel->access_token = Crypt::encryptString((string) $this->option('access-token'));
        }

        if ($this->option('verify-token')) {
            $channel->verify_token = Crypt::encryptString((string) $this->option('verify-token'));
        }

        $channel->save();

        $this->info('Platform marketing channel configured.');
        $this->line('Display number: '.$channel->display_phone_number);
        $this->line('Phone Number ID: '.$channel->masked_phone_number_id);
        $this->line('Status: '.$channel->connection_status);

        return self::SUCCESS;
    }
}
