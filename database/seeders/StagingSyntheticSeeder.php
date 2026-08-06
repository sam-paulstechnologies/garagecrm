<?php

namespace Database\Seeders;

use App\Models\Client\Client;
use App\Models\Client\Lead;
use App\Models\Conversation;
use App\Models\System\Company;
use App\Models\User;
use App\Models\Vehicle\Vehicle;
use App\Support\Staging\StagingSafety;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class StagingSyntheticSeeder extends Seeder
{
    public function run(): void
    {
        app(StagingSafety::class)->assertRuntimeIsolated();
        $this->assertSchemaReady();

        $credentials = $this->credentials();

        DB::transaction(function () use ($credentials): void {
            $tenantA = Company::query()->updateOrCreate(
                ['email' => 'tenant-a@staging.sayaraforce.test'],
                ['name' => 'Synthetic Garage Alpha', 'status' => 'active']
            );
            $tenantB = Company::query()->updateOrCreate(
                ['email' => 'tenant-b@staging.sayaraforce.test'],
                ['name' => 'Synthetic Garage Beta', 'status' => 'active']
            );

            $garageA = $this->garage($tenantA->id, 'Synthetic Alpha Main');
            $garageB = $this->garage($tenantB->id, 'Synthetic Beta Main');

            User::query()->updateOrCreate(['email' => $credentials['platform_email']], [
                'name' => 'Staging Platform Administrator',
                'password' => Hash::make($credentials['platform_password']),
                'role' => 'super_admin',
                'status' => true,
                'must_change_password' => true,
                'company_id' => null,
                'garage_id' => null,
                'email_verified_at' => now(),
            ]);

            $adminA = $this->user($credentials['garage_email'], $credentials['garage_password'], 'Synthetic Garage Administrator', 'admin', $tenantA->id, $garageA);
            $this->user($credentials['employee_email'], $credentials['employee_password'], 'Synthetic Garage Employee', 'manager', $tenantA->id, $garageA);
            $this->user($credentials['tenant_b_email'], $credentials['tenant_b_password'], 'Synthetic Beta Administrator', 'admin', $tenantB->id, $garageB);

            $this->syntheticTenantRecords($tenantA->id, $adminA->id, 'alpha', '971500000101');
            $this->syntheticTenantRecords($tenantB->id, null, 'beta', '971500000202');
        });

        $this->command?->info('Synthetic staging tenants and users seeded.');
        $this->command?->line('Passwords are stored only in staging Key Vault-backed app settings.');
        $this->command?->line('Retrieve them through the approved Key Vault access process; they are not printed here.');
    }

    private function credentials(): array
    {
        $values = [
            'platform_email' => env('STAGING_PLATFORM_ADMIN_EMAIL', 'platform-admin@staging.sayaraforce.test'),
            'platform_password' => env('STAGING_PLATFORM_ADMIN_PASSWORD'),
            'garage_email' => env('STAGING_GARAGE_ADMIN_EMAIL', 'garage-admin@staging.sayaraforce.test'),
            'garage_password' => env('STAGING_GARAGE_ADMIN_PASSWORD'),
            'employee_email' => env('STAGING_EMPLOYEE_EMAIL', 'employee@staging.sayaraforce.test'),
            'employee_password' => env('STAGING_EMPLOYEE_PASSWORD'),
            'tenant_b_email' => env('STAGING_TENANT_B_ADMIN_EMAIL', 'tenant-b-admin@staging.sayaraforce.test'),
            'tenant_b_password' => env('STAGING_TENANT_B_ADMIN_PASSWORD'),
        ];

        foreach ($values as $key => $value) {
            if (str_ends_with($key, '_password') && strlen((string) $value) < 20) {
                throw new \RuntimeException('Staging seed refused: a Key Vault-backed initial password is missing or too short.');
            }
        }

        return array_map('strval', $values);
    }

    private function assertSchemaReady(): void
    {
        $required = [
            'companies' => ['id', 'email', 'status'],
            'garages' => ['id', 'company_id', 'name'],
            'users' => ['company_id', 'garage_id', 'role', 'status', 'must_change_password'],
            'clients' => ['id', 'company_id', 'name', 'phone', 'email', 'phone_norm', 'email_norm'],
            'vehicles' => ['company_id', 'client_id', 'plate_number'],
            'leads' => ['company_id', 'client_id', 'name', 'phone', 'status'],
            'conversations' => ['company_id', 'client_id', 'lead_id', 'customer_phone'],
        ];

        foreach ($required as $table => $columns) {
            if (! Schema::hasTable($table)) {
                throw new \RuntimeException("Staging seed refused: approved schema baseline is missing {$table}.");
            }
            foreach ($columns as $column) {
                if (! Schema::hasColumn($table, $column)) {
                    throw new \RuntimeException("Staging seed refused: approved schema baseline is missing {$table}.{$column}.");
                }
            }
        }
    }

    private function garage(int $companyId, string $name): int
    {
        DB::table('garages')->updateOrInsert(
            ['company_id' => $companyId, 'name' => $name],
            ['phone' => null, 'updated_at' => now(), 'created_at' => now()]
        );

        return (int) DB::table('garages')->where('company_id', $companyId)->where('name', $name)->value('id');
    }

    private function user(string $email, string $password, string $name, string $role, int $companyId, int $garageId): User
    {
        return User::query()->updateOrCreate(['email' => $email], [
            'name' => $name,
            'password' => Hash::make($password),
            'role' => $role,
            'status' => true,
            'must_change_password' => true,
            'company_id' => $companyId,
            'garage_id' => $garageId,
            'email_verified_at' => now(),
        ]);
    }

    private function syntheticTenantRecords(int $companyId, ?int $assigneeId, string $key, string $phone): void
    {
        $client = Client::query()->updateOrCreate(
            ['company_id' => $companyId, 'email' => "customer-{$key}@example.test"],
            ['name' => "Synthetic Customer {$key}", 'phone' => $phone, 'source' => 'staging_seed', 'status' => 'active']
        );
        Vehicle::query()->updateOrCreate(
            ['company_id' => $companyId, 'plate_number' => strtoupper("STG-{$key}")],
            ['client_id' => $client->id, 'year' => 2024, 'color' => 'Orange']
        );
        $lead = Lead::query()->updateOrCreate(
            ['company_id' => $companyId, 'email' => "lead-{$key}@example.test"],
            ['client_id' => $client->id, 'name' => "Synthetic Lead {$key}", 'phone' => $phone, 'status' => 'new', 'source' => 'staging_seed', 'assigned_to' => $assigneeId]
        );
        Conversation::query()->updateOrCreate(
            ['company_id' => $companyId, 'lead_id' => $lead->id],
            ['client_id' => $client->id, 'customer_name' => $client->name, 'customer_phone' => $phone, 'subject' => 'Synthetic staging conversation', 'unread_count' => 0, 'is_whatsapp_linked' => false]
        );
    }
}
