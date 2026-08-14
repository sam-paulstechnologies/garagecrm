<?php

namespace App\Console\Commands;

use App\Models\System\Company;
use App\Security\SecurityAudit;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class EncryptLegacyProviderTokens extends Command
{
    protected $signature = 'security:encrypt-legacy-provider-tokens {--apply : Persist the reviewed encryption changes}';

    protected $description = 'Detect and encrypt legacy plaintext provider tokens without exposing their values';

    public function handle(SecurityAudit $audit): int
    {
        if (! app()->environment(['local', 'testing', 'staging'])) {
            $this->error('Refused: this command requires an explicitly approved non-production environment.');

            return self::FAILURE;
        }

        $apply = (bool) $this->option('apply');
        $legacy = 0;
        $alreadyEncrypted = 0;
        $hashRepairs = 0;
        $fields = [
            ['table' => 'meta_pages', 'column' => 'page_access_token'],
            ['table' => 'companies', 'column' => 'meta_access_token'],
            ['table' => 'companies', 'column' => 'meta_verify_token'],
        ];

        foreach ($fields as $field) {
            $table = $field['table'];
            $column = $field['column'];
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
                continue;
            }

            DB::table($table)
                ->select(['id', $column])
                ->whereNotNull($column)
                ->where($column, '!=', '')
                ->orderBy('id')
                ->chunkById(100, function ($rows) use ($apply, $table, $column, &$legacy, &$alreadyEncrypted): void {
                    foreach ($rows as $row) {
                        $value = (string) $row->{$column};
                        try {
                            Crypt::decryptString($value);
                            $alreadyEncrypted++;

                            continue;
                        } catch (Throwable) {
                            $legacy++;
                        }

                        if ($apply) {
                            DB::table($table)
                                ->where('id', $row->id)
                                ->update([$column => Crypt::encryptString($value)]);
                        }
                    }
                });
        }

        if (Schema::hasTable('companies')
            && Schema::hasColumn('companies', 'meta_verify_token')
            && Schema::hasColumn('companies', 'meta_verify_token_hash')) {
            DB::table('companies')
                ->select(['id', 'meta_verify_token', 'meta_verify_token_hash'])
                ->whereNotNull('meta_verify_token')
                ->where('meta_verify_token', '!=', '')
                ->orderBy('id')
                ->chunkById(100, function ($rows) use ($apply, &$hashRepairs): void {
                    foreach ($rows as $row) {
                        $stored = (string) $row->meta_verify_token;
                        try {
                            $plaintext = Crypt::decryptString($stored);
                        } catch (Throwable) {
                            $plaintext = $stored;
                        }
                        $expected = Company::metaVerifyTokenHash($plaintext);
                        if (hash_equals($expected, (string) ($row->meta_verify_token_hash ?? ''))) {
                            continue;
                        }
                        $hashRepairs++;
                        if ($apply) {
                            DB::table('companies')->where('id', $row->id)->update([
                                'meta_verify_token_hash' => $expected,
                            ]);
                        }
                    }
                });
        }

        if (Schema::hasTable('company_settings')) {
            DB::table('company_settings')
                ->select(['id', 'value'])
                ->where('is_encrypted', true)
                ->whereNotNull('value')
                ->where('value', '!=', '')
                ->orderBy('id')
                ->chunkById(100, function ($rows) use ($apply, &$legacy, &$alreadyEncrypted): void {
                    foreach ($rows as $row) {
                        $value = (string) $row->value;
                        try {
                            Crypt::decryptString($value);
                            $alreadyEncrypted++;

                            continue;
                        } catch (Throwable) {
                            $legacy++;
                        }

                        if ($apply) {
                            DB::table('company_settings')->where('id', $row->id)->update([
                                'value' => Crypt::encryptString($value),
                            ]);
                        }
                    }
                });
        }

        if ($apply && ($legacy > 0 || $hashRepairs > 0) && Schema::hasTable('security_audit_logs')) {
            $audit->record('provider_tokens.encrypted_at_rest', null, context: [
                'record_count' => $legacy,
                'verification_hash_repairs' => $hashRepairs,
                'source' => 'controlled_security_command',
            ]);
        }

        $this->info(sprintf(
            '%s: %d legacy token record(s); %d already encrypted; %d verification hash repair(s). No token values were displayed.',
            $apply ? 'Applied' : 'Dry run',
            $legacy,
            $alreadyEncrypted,
            $hashRepairs,
        ));

        return self::SUCCESS;
    }
}
