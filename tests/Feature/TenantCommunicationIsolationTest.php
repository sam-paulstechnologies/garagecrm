<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TenantCommunicationIsolationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('clients');
        Schema::dropIfExists('templates');

        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->timestamps();
        });

        Schema::create('templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('content');
            $table->string('type');
            $table->timestamps();
        });
    }

    public function test_tenant_cannot_send_communication_to_another_company_client(): void
    {
        $clientId = $this->insertClient(companyId: 2);
        $templateId = $this->insertTemplate();

        $response = $this
            ->actingAs($this->tenantUser(companyId: 1))
            ->post(route('tenant.communications.send'), [
                'client_id' => $clientId,
                'template_id' => $templateId,
                'schedule' => 'now',
            ]);

        $response->assertNotFound();
    }

    public function test_tenant_can_send_communication_to_own_company_client(): void
    {
        $clientId = $this->insertClient(companyId: 1);
        $templateId = $this->insertTemplate();

        $response = $this
            ->actingAs($this->tenantUser(companyId: 1))
            ->post(route('tenant.communications.send'), [
                'client_id' => $clientId,
                'template_id' => $templateId,
                'schedule' => 'now',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('tenant.communications.create'));
    }

    protected function tenantUser(int $companyId): User
    {
        $user = new User([
            'name' => 'Tenant User',
            'email' => 'tenant'.$companyId.'@example.test',
            'role' => 'tenant',
            'company_id' => $companyId,
            'status' => true,
            'must_change_password' => false,
        ]);

        $user->id = $companyId;
        $user->exists = true;

        return $user;
    }

    protected function insertClient(int $companyId): int
    {
        return (int) \DB::table('clients')->insertGetId([
            'company_id' => $companyId,
            'name' => 'Pilot Client',
            'phone' => '971500000000',
            'email' => 'client'.$companyId.'@example.test',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function insertTemplate(): int
    {
        return (int) \DB::table('templates')->insertGetId([
            'name' => 'Pilot Template',
            'content' => 'Hello',
            'type' => 'whatsapp',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
