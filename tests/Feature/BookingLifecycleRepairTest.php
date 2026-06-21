<?php

namespace Tests\Feature;

use App\Models\Client\Opportunity;
use App\Models\Job\Booking;
use App\Models\Job\Job;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BookingLifecycleRepairTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private int $companyId;
    private int $clientId;
    private int $leadId;
    private int $opportunityId;
    private int $vehicleId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareSchema();

        $this->companyId = (int) DB::table('companies')->insertGetId([
            'name' => 'Test Garage',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.test',
            'password' => 'password',
            'role' => 'admin',
            'company_id' => $this->companyId,
            'status' => true,
            'must_change_password' => false,
        ]);

        $this->clientId = (int) DB::table('clients')->insertGetId([
            'company_id' => $this->companyId,
            'name' => 'Booking Client',
            'phone' => null,
            'email' => 'client@example.test',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->leadId = (int) DB::table('leads')->insertGetId([
            'company_id' => $this->companyId,
            'client_id' => $this->clientId,
            'name' => 'Booking Lead',
            'status' => 'qualified',
            'service_type' => 'General Service',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->vehicleId = (int) DB::table('vehicles')->insertGetId([
            'company_id' => $this->companyId,
            'client_id' => $this->clientId,
            'plate_number' => 'DXB T 100',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->opportunityId = (int) DB::table('opportunities')->insertGetId([
            'company_id' => $this->companyId,
            'client_id' => $this->clientId,
            'lead_id' => $this->leadId,
            'vehicle_id' => $this->vehicleId,
            'title' => 'Booking Opportunity',
            'service_type' => 'General Service',
            'stage' => Opportunity::STAGE_APPOINTMENT,
            'priority' => 'medium',
            'is_converted' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_admin_converting_booking_creates_job_and_redirects_to_job(): void
    {
        $booking = $this->booking(['status' => Booking::STATUS_SCHEDULED]);

        $response = $this->actingAs($this->admin)->put(route('admin.bookings.update', $booking), $this->payload([
            'status' => Booking::STATUS_CONVERTED_TO_JOB,
        ]));

        $job = Job::where('booking_id', $booking->id)->first();

        $this->assertNotNull($job);
        $response->assertRedirect(route('admin.jobs.show', $job));
        $this->assertSame(Booking::STATUS_CONVERTED_TO_JOB, $booking->fresh()->status);
        $this->assertSame(Opportunity::STAGE_BOOKING_CONFIRMED, Opportunity::find($this->opportunityId)->stage);
    }

    public function test_admin_repeated_conversion_reuses_existing_job(): void
    {
        $booking = $this->booking(['status' => Booking::STATUS_SCHEDULED]);

        $this->actingAs($this->admin)->put(route('admin.bookings.update', $booking), $this->payload([
            'status' => Booking::STATUS_CONVERTED_TO_JOB,
        ]));

        $firstJobId = Job::where('booking_id', $booking->id)->value('id');

        $this->actingAs($this->admin)->put(route('admin.bookings.update', $booking->fresh()), $this->payload([
            'status' => Booking::STATUS_CONVERTED_TO_JOB,
        ]));

        $this->assertSame(1, Job::where('booking_id', $booking->id)->count());
        $this->assertSame($firstJobId, Job::where('booking_id', $booking->id)->value('id'));
    }

    public function test_api_transition_to_converted_to_job_creates_or_reuses_job(): void
    {
        $booking = $this->booking(['status' => Booking::STATUS_SCHEDULED]);

        Sanctum::actingAs($this->admin);

        $this->postJson(route('api.bookings.transition', $booking), [
            'to' => Booking::STATUS_CONVERTED_TO_JOB,
        ])->assertOk()
            ->assertJsonPath('status', Booking::STATUS_CONVERTED_TO_JOB)
            ->assertJsonStructure(['job_id', 'job_url']);

        $firstJobId = Job::where('booking_id', $booking->id)->value('id');

        $this->postJson(route('api.bookings.transition', $booking), [
            'to' => Booking::STATUS_CONVERTED_TO_JOB,
        ])->assertOk()
            ->assertJsonPath('job_id', $firstJobId);

        $this->assertSame(1, Job::where('booking_id', $booking->id)->count());
    }

    public function test_lost_requires_reason_and_does_not_create_job(): void
    {
        $booking = $this->booking(['status' => Booking::STATUS_SCHEDULED]);

        $this->actingAs($this->admin)->from(route('admin.bookings.edit', $booking))
            ->put(route('admin.bookings.update', $booking), $this->payload([
                'status' => Booking::STATUS_LOST,
                'lost_reason' => null,
            ]))
            ->assertRedirect(route('admin.bookings.edit', $booking));

        $this->actingAs($this->admin)->put(route('admin.bookings.update', $booking), $this->payload([
            'status' => Booking::STATUS_LOST,
            'lost_reason' => Booking::LOST_REASON_NO_SHOW,
        ]))->assertRedirect(route('admin.bookings.index'));

        $this->assertSame(Booking::STATUS_LOST, $booking->fresh()->status);
        $this->assertSame(Booking::LOST_REASON_NO_SHOW, $booking->fresh()->lost_reason);
        $this->assertSame(0, Job::where('booking_id', $booking->id)->count());
    }

    public function test_simple_statuses_do_not_create_job(): void
    {
        $booking = $this->booking(['status' => Booking::STATUS_PENDING]);

        $this->actingAs($this->admin)->put(route('admin.bookings.update', $booking), $this->payload([
            'status' => Booking::STATUS_PENDING,
        ]))->assertRedirect(route('admin.bookings.index'));

        $this->actingAs($this->admin)->put(route('admin.bookings.update', $booking->fresh()), $this->payload([
            'status' => Booking::STATUS_SCHEDULED,
        ]))->assertRedirect(route('admin.bookings.index'));

        $this->assertSame(0, Job::where('booking_id', $booking->id)->count());
    }

    public function test_reschedule_required_requires_reason_and_does_not_create_job(): void
    {
        $booking = $this->booking(['status' => Booking::STATUS_SCHEDULED]);

        $this->actingAs($this->admin)
            ->from(route('admin.bookings.edit', $booking))
            ->put(route('admin.bookings.update', $booking), $this->payload([
                'status' => Booking::STATUS_RESCHEDULE_REQUIRED,
                'reschedule_reason' => null,
            ]))
            ->assertRedirect(route('admin.bookings.edit', $booking));

        $this->actingAs($this->admin)
            ->put(route('admin.bookings.update', $booking), $this->payload([
                'status' => Booking::STATUS_RESCHEDULE_REQUIRED,
                'reschedule_reason' => 'Customer requested another day.',
            ]))
            ->assertRedirect(route('admin.bookings.index'));

        $fresh = $booking->fresh();

        $this->assertSame(Booking::STATUS_RESCHEDULE_REQUIRED, $fresh->status);
        $this->assertSame('Customer requested another day.', $fresh->reschedule_reason);
        $this->assertNotNull($fresh->reschedule_requested_at);
        $this->assertSame(0, Job::where('booking_id', $booking->id)->count());
    }

    public function test_booking_priority_rejects_urgent(): void
    {
        $booking = $this->booking(['status' => Booking::STATUS_PENDING]);

        $this->actingAs($this->admin)->from(route('admin.bookings.edit', $booking))
            ->put(route('admin.bookings.update', $booking), $this->payload([
                'priority' => 'urgent',
            ]))
            ->assertRedirect(route('admin.bookings.edit', $booking));

        $this->assertNotSame('urgent', $booking->fresh()->priority);
    }

    public function test_legacy_opportunity_stages_normalize_to_current_stages(): void
    {
        $this->assertSame(Opportunity::STAGE_BOOKING_CONFIRMED, Opportunity::normalizeStage('closed_won'));
        $this->assertSame(Opportunity::STAGE_ATTEMPTING_CONTACT, Opportunity::normalizeStage('collecting_details'));
    }

    private function booking(array $overrides = []): Booking
    {
        $id = DB::table('bookings')->insertGetId(array_merge([
            'company_id' => $this->companyId,
            'client_id' => $this->clientId,
            'vehicle_id' => $this->vehicleId,
            'opportunity_id' => $this->opportunityId,
            'lead_id' => $this->leadId,
            'name' => 'Booking Under Test',
            'service_type' => 'General Service',
            'booking_date' => now()->addDay()->toDateString(),
            'slot' => 'morning',
            'priority' => 'medium',
            'expected_duration' => 60,
            'status' => Booking::STATUS_PENDING,
            'is_archived' => false,
            'pickup_required' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides));

        return Booking::findOrFail($id);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'opportunity_id' => $this->opportunityId,
            'vehicle_id' => $this->vehicleId,
            'name' => 'Booking Under Test',
            'service_type' => 'General Service',
            'booking_date' => now()->addDay()->toDateString(),
            'slot' => 'morning',
            'assigned_to' => null,
            'pickup_required' => '0',
            'priority' => 'medium',
            'expected_duration' => 60,
            'expected_close_date' => now()->addDays(2)->toDateString(),
            'notes' => 'Test booking notes',
            'status' => Booking::STATUS_SCHEDULED,
            'lost_reason' => null,
            'reschedule_reason' => null,
        ], $overrides);
    }

    private function prepareSchema(): void
    {
        $this->ensureUserColumns();

        Schema::dropIfExists('jobs');
        Schema::dropIfExists('bookings');
        Schema::dropIfExists('opportunities');
        Schema::dropIfExists('leads');
        Schema::dropIfExists('vehicles');
        Schema::dropIfExists('clients');

        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('phone_norm')->nullable();
            $table->string('whatsapp')->nullable();
            $table->string('email')->nullable();
            $table->string('email_norm')->nullable();
            $table->boolean('is_archived')->default(false);
            $table->timestamps();
        });

        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('client_id')->nullable();
            $table->string('name')->nullable();
            $table->string('phone')->nullable();
            $table->string('status')->default('new');
            $table->string('service_type')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('client_id')->nullable();
            $table->unsignedBigInteger('make_id')->nullable();
            $table->unsignedBigInteger('model_id')->nullable();
            $table->string('plate_number')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('opportunities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('client_id');
            $table->unsignedBigInteger('lead_id')->nullable();
            $table->unsignedBigInteger('vehicle_id')->nullable();
            $table->string('title')->nullable();
            $table->string('service_type')->nullable();
            $table->string('stage')->nullable()->default('new');
            $table->string('priority')->nullable()->default('medium');
            $table->decimal('value', 12, 2)->default(0);
            $table->date('expected_close_date')->nullable();
            $table->date('next_follow_up')->nullable();
            $table->boolean('is_converted')->default(false);
            $table->boolean('is_archived')->default(false);
            $table->string('close_reason')->nullable();
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('client_id');
            $table->unsignedBigInteger('vehicle_id')->nullable();
            $table->unsignedBigInteger('opportunity_id')->nullable();
            $table->unsignedBigInteger('lead_id')->nullable();
            $table->string('name')->nullable();
            $table->string('service_type')->nullable();
            $table->date('booking_date')->nullable();
            $table->string('slot')->default('morning');
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->boolean('pickup_required')->default(false);
            $table->string('pickup_address')->nullable();
            $table->string('pickup_contact_number')->nullable();
            $table->string('priority')->nullable()->default('medium');
            $table->integer('expected_duration')->nullable();
            $table->date('expected_close_date')->nullable();
            $table->string('status')->default('pending');
            $table->string('lost_reason')->nullable();
            $table->text('reschedule_reason')->nullable();
            $table->timestamp('reschedule_requested_at')->nullable();
            $table->boolean('is_archived')->default(false);
            $table->text('notes')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('state_changed_at')->nullable();
            $table->unsignedBigInteger('state_changed_by')->nullable();
            $table->timestamp('reminder_sent_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('jobs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('booking_id')->unique('unique_job_per_booking');
            $table->unsignedBigInteger('lead_id')->nullable();
            $table->unsignedBigInteger('opportunity_id')->nullable();
            $table->unsignedBigInteger('client_id');
            $table->string('job_code')->nullable();
            $table->timestamp('start_time')->nullable();
            $table->text('description')->nullable();
            $table->string('status')->nullable()->default('pending');
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->boolean('is_archived')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    private function ensureUserColumns(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'company_id')) {
                $table->unsignedBigInteger('company_id')->nullable();
            }

            if (! Schema::hasColumn('users', 'garage_id')) {
                $table->unsignedBigInteger('garage_id')->nullable();
            }

            if (! Schema::hasColumn('users', 'role')) {
                $table->string('role')->nullable();
            }

            if (! Schema::hasColumn('users', 'status')) {
                $table->boolean('status')->default(true);
            }

            if (! Schema::hasColumn('users', 'must_change_password')) {
                $table->boolean('must_change_password')->default(false);
            }
        });
    }
}
