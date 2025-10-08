<?php

namespace App\Services\Settings;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Validates the settings payload for Meta, Twilio and System groups.
 * Keep rules light so tenants can save partial configs and test later.
 */
class SettingsValidator
{
    /**
     * @param  array  $input  flat array like:
     *  [
     *    'meta.access_token' => 'EAAB...',
     *    'meta.page_id'      => '123',
     *    'meta.app_id'       => '456',
     *    'meta.form_ids'     => '["123","456"]' OR '123,456',
     *    'twilio.account_sid'   => 'ACxxxx',
     *    'twilio.auth_token'    => 'xxxx',
     *    'twilio.whatsapp_from' => 'whatsapp:+1415...',
     *    'system.timezone'             => 'Asia/Dubai',
     *    'system.default_country_code' => '+971',
     *    'system.notification_email'   => 'ops@example.com',
     *  ]
     * @return array validated (same keys)
     * @throws ValidationException
     */
    public static function validate(array $input): array
    {
        $rules = [
            // Meta
            'meta.access_token' => ['nullable','string','max:4096'],
            'meta.page_id'      => ['nullable','string','max:255'],
            'meta.app_id'       => ['nullable','string','max:255'],
            'meta.form_ids'     => ['nullable','string','max:4096'], // CSV or JSON text; parsed in controller

            // Twilio
            'twilio.account_sid'   => ['nullable','string','max:255'],
            'twilio.auth_token'    => ['nullable','string','max:255'],
            'twilio.whatsapp_from' => ['nullable','string','max:255'],

            // System
            'system.timezone'             => ['nullable','string','max:255'],
            'system.default_country_code' => ['nullable','string','max:10'],
            'system.notification_email'   => ['nullable','email','max:255'],
        ];

        $validator = Validator::make($input, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }
}
