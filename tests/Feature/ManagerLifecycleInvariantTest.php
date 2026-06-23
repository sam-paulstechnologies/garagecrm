<?php

namespace Tests\Feature;

use App\Models\Client\Lead;
use App\Models\Client\Opportunity;
use App\Models\Job\Booking;
use App\Models\Job\Invoice;
use App\Models\Job\Job;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ManagerLifecycleInvariantTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;
    private User $admin;
    private int $companyId;
    private int $clientId;
    private int $vehicleId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareSchema();

        $this->companyId = (int) DB::table('companies')->insertGetId([
            'name' => 'Lifecycle Garage',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->manager = User::create([
            'name' => 'Manager User',
            'email' => 'manager@example.test',
            'password' => 'password',
            'role' => 'manager',
            'company_id' => $this->companyId,
            'status' => true,
            'must_change_password' => false,
        ]);

        $this->admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin-invariant@example.test',
            'password' => 'password',
            'role' => 'admin',
            'company_id' => $this->companyId,
            'status' => true,
            'must_change_password' => false,
        ]);

        $this->clientId = (int) DB::table('clients')->insertGetId([
            'company_id' => $this->companyId,
            'name' => 'Lifecycle Client',
            'phone' => '971500000001',
            'phone_norm' => '971500000001',
            'whatsapp' => '971500000001',
            'email' => 'client@example.test',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->vehicleId = (int) DB::table('vehicles')->insertGetId([
            'company_id' => $this->companyId,
            'client_id' => $this->clientId,
            'plate_number' => 'DXB I 100',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_manager_dashboard_renders_shared_theme_controls_without_broken_links(): void
    {
        $response = $this->actingAs($this->manager)
            ->get(route('manager.dashboard'));

        $response
            ->assertOk()
            ->assertSee('Manager Dashboard')
            ->assertSee('sayaraforce_theme', false)
            ->assertSee('data-sf-theme-toggle', false)
            ->assertSee('managerMobileNav', false)
            ->assertSee(route('manager.settings.index'), false)
            ->assertDontSee('manager/profile', false);

        if (! Route::has('manager.calendar.index')) {
            $response->assertDontSee('manager/calendar', false);
        }
    }

    public function test_manager_qualifying_lead_creates_or_reuses_opportunity(): void
    {
        $lead = $this->lead();

        $this->actingAs($this->manager)
            ->patch(route('manager.leads.status', $lead), [
                'status' => Lead::STATUS_QUALIFIED,
            ])
            ->assertRedirect();

        $this->assertSame(Lead::STATUS_QUALIFIED, $lead->fresh()->status);
        $this->assertSame(1, Opportunity::where('company_id', $this->companyId)->where('lead_id', $lead->id)->count());

        $this->actingAs($this->manager)
            ->patch(route('manager.leads.status', $lead->fresh()), [
                'status' => Lead::STATUS_QUALIFIED,
            ])
            ->assertRedirect();

        $this->assertSame(1, Opportunity::where('company_id', $this->companyId)->where('lead_id', $lead->id)->count());
    }

    public function test_manager_cannot_set_legacy_lead_statuses(): void
    {
        $lead = $this->lead();

        $this->actingAs($this->manager)
            ->from(route('manager.leads.index'))
            ->patch(route('manager.leads.status', $lead), [
                'status' => 'converted',
            ])
            ->assertRedirect(route('manager.leads.index'))
            ->assertSessionHasErrors('status');

        $this->assertSame(Lead::STATUS_NEW, $lead->fresh()->status);
        $this->assertSame(0, Opportunity::where('lead_id', $lead->id)->count());
    }

    public function test_manager_lead_hold_and_disqualified_validation_is_enforced(): void
    {
        $lead = $this->lead();

        $this->actingAs($this->manager)
            ->from(route('manager.leads.index'))
            ->patch(route('manager.leads.status', $lead), [
                'status' => Lead::STATUS_HOLD,
            ])
            ->assertRedirect(route('manager.leads.index'))
            ->assertSessionHasErrors('status_sub_status');

        $this->actingAs($this->manager)
            ->patch(route('manager.leads.status', $lead), [
                'status' => Lead::STATUS_HOLD,
                'status_sub_status' => 'call_back_requested',
                'follow_up_at' => now()->addDay()->format('Y-m-d H:i:s'),
            ])
            ->assertRedirect();

        $this->assertSame(Lead::STATUS_HOLD, $lead->fresh()->status);

        $this->actingAs($this->manager)
            ->from(route('manager.leads.index'))
            ->patch(route('manager.leads.status', $lead->fresh()), [
                'status' => Lead::STATUS_DISQUALIFIED,
            ])
            ->assertRedirect(route('manager.leads.index'))
            ->assertSessionHasErrors('status_sub_status');
    }

    public function test_manager_booking_confirmed_creates_or_reuses_booking(): void
    {
        $opportunity = $this->opportunity();

        $payload = [
            'stage' => Opportunity::STAGE_BOOKING_CONFIRMED,
            'service_type' => 'General Service',
            'booking_date' => now()->addDay()->toDateString(),
            'booking_slot' => 'morning',
        ];

        $this->actingAs($this->manager)
            ->patch(route('manager.opportunities.stage', $opportunity), $payload)
            ->assertRedirect();

        $this->assertSame(Opportunity::STAGE_BOOKING_CONFIRMED, $opportunity->fresh()->stage);
        $this->assertSame(1, Booking::where('company_id', $this->companyId)->where('opportunity_id', $opportunity->id)->count());

        $this->actingAs($this->manager)
            ->patch(route('manager.opportunities.stage', $opportunity->fresh()), $payload)
            ->assertRedirect();

        $this->assertSame(1, Booking::where('company_id', $this->companyId)->where('opportunity_id', $opportunity->id)->count());
    }

    public function test_manager_cannot_set_legacy_opportunity_stage_or_close_lost_without_reason(): void
    {
        $opportunity = $this->opportunity();

        $this->actingAs($this->manager)
            ->from(route('manager.opportunities.index'))
            ->patch(route('manager.opportunities.stage', $opportunity), [
                'stage' => 'closed_won',
            ])
            ->assertRedirect(route('manager.opportunities.index'))
            ->assertSessionHasErrors('stage');

        $this->actingAs($this->manager)
            ->from(route('manager.opportunities.index'))
            ->patch(route('manager.opportunities.stage', $opportunity), [
                'stage' => Opportunity::STAGE_CLOSED_LOST,
            ])
            ->assertRedirect(route('manager.opportunities.index'))
            ->assertSessionHasErrors('stage_sub_status');
    }

    public function test_manager_generic_job_status_cannot_complete_without_invoice(): void
    {
        $job = $this->job();

        $this->actingAs($this->manager)
            ->from(route('manager.jobs.show', $job))
            ->patch(route('manager.jobs.status', $job), [
                'status' => 'completed',
            ])
            ->assertRedirect(route('manager.jobs.show', $job))
            ->assertSessionHasErrors('status');

        $this->assertSame('pending', $job->fresh()->status);
        $this->assertSame(0, Invoice::where('job_id', $job->id)->count());
    }

    public function test_manager_complete_with_invoice_completes_job_and_reuses_invoice(): void
    {
        $job = $this->job();

        $payload = [
            'invoice_number' => 'INV-MGR-001',
            'invoice_amount' => 250,
            'invoice_notes' => 'Manager completion',
        ];

        $this->actingAs($this->manager)
            ->patch(route('manager.jobs.complete-with-invoice', $job), $payload)
            ->assertRedirect();

        $this->assertSame('completed', $job->fresh()->status);
        $this->assertSame(1, Invoice::where('job_id', $job->id)->count());
        $this->assertSame('pending', Invoice::where('job_id', $job->id)->first()->status);

        $this->actingAs($this->manager)
            ->patch(route('manager.jobs.complete-with-invoice', $job->fresh()), array_merge($payload, [
                'invoice_amount' => 300,
            ]))
            ->assertRedirect();

        $this->assertSame(1, Invoice::where('job_id', $job->id)->count());
        $this->assertSame('300.00', (string) Invoice::where('job_id', $job->id)->first()->amount);
    }

    public function test_admin_cannot_create_completed_job_without_invoice_data(): void
    {
        $this->actingAs($this->admin)
            ->from(route('admin.jobs.create'))
            ->post(route('admin.jobs.store'), [
                'client_id' => $this->clientId,
                'description' => 'Completed too early',
                'status' => 'completed',
            ])
            ->assertRedirect(route('admin.jobs.create'))
            ->assertSessionHasErrors(['invoice_number', 'invoice_amount']);
    }

    public function test_admin_can_create_completed_job_with_invoice_data(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.jobs.store'), [
                'client_id' => $this->clientId,
                'description' => 'Completed with invoice',
                'status' => 'completed',
                'invoice_number' => 'INV-ADM-001',
                'invoice_amount' => 500,
            ])
            ->assertRedirect();

        $job = Job::where('description', 'Completed with invoice')->first();

        $this->assertNotNull($job);
        $this->assertSame('completed', $job->status);
        $this->assertSame(1, Invoice::where('job_id', $job->id)->count());
        $this->assertSame('paid', Invoice::where('job_id', $job->id)->first()->status);
        $this->assertSame('500.00', (string) Invoice::where('job_id', $job->id)->first()->amount);
    }

    public function test_manager_mark_unpaid_writes_pending_status(): void
    {
        $job = $this->job(['status' => 'completed']);
        $invoice = Invoice::create([
            'company_id' => $this->companyId,
            'client_id' => $this->clientId,
            'job_id' => $job->id,
            'source' => 'generated',
            'amount' => 100,
            'status' => 'paid',
            'number' => 'INV-PAID',
            'invoice_date' => now()->toDateString(),
            'currency' => 'AED',
            'due_date' => now()->toDateString(),
        ]);

        $this->actingAs($this->manager)
            ->patch(route('manager.invoices.mark-unpaid', $invoice->id))
            ->assertRedirect();

        $this->assertSame('pending', $invoice->fresh()->status);
    }

    public function test_manager_can_mark_booking_as_rescheduling_required_with_replacement_slot(): void
    {
        $booking = $this->booking([
            'status' => Booking::STATUS_SCHEDULED,
            'booking_date' => now()->addDay()->toDateString(),
            'slot' => 'morning',
        ]);

        $this->actingAs($this->manager)
            ->from(route('manager.bookings.show', $booking))
            ->patch(route('manager.bookings.reschedule', $booking), [
                'booking_date' => now()->addDays(2)->toDateString(),
                'slot' => 'afternoon',
                'reschedule_reason' => 'Customer requested a later slot.',
            ])
            ->assertRedirect(route('manager.bookings.show', $booking));

        $fresh = $booking->fresh();

        $this->assertSame(Booking::STATUS_RESCHEDULE_REQUIRED, $fresh->status);
        $this->assertSame('Customer requested a later slot.', $fresh->reschedule_reason);
        $this->assertSame('afternoon', $fresh->slot);
        $this->assertNotNull($fresh->reschedule_requested_at);
        $this->assertSame(0, Job::where('booking_id', $booking->id)->count());
    }

    public function test_manager_reschedule_requires_reason_date_and_capacity(): void
    {
        $targetDate = now()->addDays(3)->toDateString();
        $blocking = $this->booking([
            'status' => Booking::STATUS_SCHEDULED,
            'booking_date' => $targetDate,
            'slot' => 'full_day',
        ]);
        $booking = $this->booking([
            'status' => Booking::STATUS_PENDING,
            'booking_date' => now()->addDay()->toDateString(),
            'slot' => 'morning',
        ]);

        $this->actingAs($this->manager)
            ->from(route('manager.bookings.show', $booking))
            ->patch(route('manager.bookings.reschedule', $booking), [
                'booking_date' => $targetDate,
                'slot' => 'morning',
            ])
            ->assertRedirect(route('manager.bookings.show', $booking))
            ->assertSessionHasErrors('reschedule_reason');

        $this->actingAs($this->manager)
            ->from(route('manager.bookings.show', $booking))
            ->patch(route('manager.bookings.reschedule', $booking), [
                'booking_date' => $targetDate,
                'slot' => 'morning',
                'reschedule_reason' => 'Need a different time.',
            ])
            ->assertRedirect(route('manager.bookings.show', $booking))
            ->assertSessionHasErrors('slot');

        $this->assertSame(Booking::STATUS_PENDING, $booking->fresh()->status);
        $this->assertSame(Booking::STATUS_SCHEDULED, $blocking->fresh()->status);
    }

    public function test_manager_cannot_reschedule_cross_company_or_converted_booking(): void
    {
        $converted = $this->booking(['status' => Booking::STATUS_CONVERTED_TO_JOB]);

        $this->actingAs($this->manager)
            ->from(route('manager.bookings.show', $converted))
            ->patch(route('manager.bookings.reschedule', $converted), [
                'booking_date' => now()->addDay()->toDateString(),
                'slot' => 'morning',
                'reschedule_reason' => 'Try to move completed journey.',
            ])
            ->assertRedirect(route('manager.bookings.show', $converted))
            ->assertSessionHasErrors('booking');

        $otherCompanyId = (int) DB::table('companies')->insertGetId([
            'name' => 'Other Garage',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $crossCompanyBooking = Booking::create([
            'company_id' => $otherCompanyId,
            'client_id' => $this->clientId,
            'vehicle_id' => $this->vehicleId,
            'booking_date' => now()->addDay()->toDateString(),
            'slot' => 'morning',
            'status' => Booking::STATUS_PENDING,
            'is_archived' => false,
        ]);

        $this->actingAs($this->manager)
            ->patch(route('manager.bookings.reschedule', $crossCompanyBooking->id), [
                'booking_date' => now()->addDays(2)->toDateString(),
                'slot' => 'morning',
                'reschedule_reason' => 'Cross company attempt.',
            ])
            ->assertNotFound();
    }

    public function test_manager_opportunity_index_shows_closed_lost_and_no_legacy_stage_choices(): void
    {
        $opportunity = $this->opportunity([
            'title' => 'Closed Lost Manager Visible',
            'stage' => Opportunity::STAGE_CLOSED_LOST,
            'status' => 'lost',
        ]);

        $response = $this->actingAs($this->manager)
            ->get(route('manager.opportunities.index', ['stage' => Opportunity::STAGE_CLOSED_LOST]))
            ->assertOk()
            ->assertSee('Closed Lost Manager Visible')
            ->assertSee('Closed Lost');

        $response->assertDontSee('value="collecting_details"', false);
        $response->assertDontSee('value="closed_won"', false);
        $response->assertDontSee('Collecting Details');
        $response->assertDontSee('Closed Won');

        $this->assertSame(Opportunity::STAGE_CLOSED_LOST, $opportunity->fresh()->stage);
    }

    public function test_manager_job_status_ui_only_renders_generic_active_statuses(): void
    {
        $job = $this->job();

        $this->actingAs($this->manager)
            ->get(route('manager.jobs.show', $job))
            ->assertOk()
            ->assertSee('Complete the job using the invoice completion action.')
            ->assertSee('value="pending"', false)
            ->assertSee('value="in_progress"', false)
            ->assertDontSee('value="completed"', false)
            ->assertDontSee('value="cancelled"', false);

        $this->actingAs($this->manager)
            ->get(route('manager.jobs.index'))
            ->assertOk()
            ->assertSee('Pending')
            ->assertSee('In Progress')
            ->assertSee('Completed')
            ->assertDontSee('Cancelled');
    }

    private function lead(array $overrides = []): Lead
    {
        return Lead::withoutEvents(fn () => Lead::create(array_merge([
            'company_id' => $this->companyId,
            'client_id' => $this->clientId,
            'name' => 'Lifecycle Lead',
            'phone' => '971500000002',
            'email' => 'lead@example.test',
            'status' => Lead::STATUS_NEW,
            'source' => 'website',
            'service_type' => 'General Service',
            'notes' => 'Lifecycle test lead',
            'is_active' => true,
        ], $overrides)));
    }

    private function opportunity(array $overrides = []): Opportunity
    {
        return Opportunity::create(array_merge([
            'company_id' => $this->companyId,
            'client_id' => $this->clientId,
            'vehicle_id' => $this->vehicleId,
            'title' => 'Lifecycle Opportunity',
            'service_type' => 'General Service',
            'stage' => Opportunity::STAGE_APPOINTMENT,
            'priority' => 'medium',
            'is_converted' => false,
            'is_archived' => false,
        ], $overrides));
    }

    private function job(array $overrides = []): Job
    {
        return Job::create(array_merge([
            'company_id' => $this->companyId,
            'client_id' => $this->clientId,
            'description' => 'Lifecycle job',
            'status' => 'pending',
            'is_archived' => false,
        ], $overrides));
    }

    private function booking(array $overrides = []): Booking
    {
        return Booking::create(array_merge([
            'company_id' => $this->companyId,
            'client_id' => $this->clientId,
            'vehicle_id' => $this->vehicleId,
            'name' => 'Lifecycle booking',
            'service_type' => 'General Service',
            'booking_date' => now()->addDay()->toDateString(),
            'slot' => 'morning',
            'status' => Booking::STATUS_PENDING,
            'is_archived' => false,
        ], $overrides));
    }

    private function prepareSchema(): void
    {
        $this->ensureUserColumns();

        Schema::dropIfExists('invoices');
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
            $table->string('source')->nullable();
            $table->string('preferred_channel')->nullable();
            $table->string('status')->nullable();
            $table->boolean('is_archived')->default(false);
            $table->timestamps();
        });

        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('client_id')->nullable();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('email_norm')->nullable();
            $table->string('phone')->nullable();
            $table->string('phone_norm')->nullable();
            $table->string('status')->default('new');
            $table->string('status_sub_status')->nullable();
            $table->text('status_reason')->nullable();
            $table->dateTime('follow_up_at')->nullable();
            $table->string('source')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->dateTime('last_contacted_at')->nullable();
            $table->string('preferred_channel')->nullable();
            $table->string('service_type')->nullable();
            $table->unsignedBigInteger('vehicle_make_id')->nullable();
            $table->unsignedBigInteger('vehicle_model_id')->nullable();
            $table->string('other_make')->nullable();
            $table->string('other_model')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('score')->nullable();
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
            $table->unsignedBigInteger('client_id')->nullable();
            $table->unsignedBigInteger('lead_id')->nullable();
            $table->unsignedBigInteger('vehicle_id')->nullable();
            $table->unsignedBigInteger('vehicle_make_id')->nullable();
            $table->unsignedBigInteger('vehicle_model_id')->nullable();
            $table->string('other_make')->nullable();
            $table->string('other_model')->nullable();
            $table->string('title')->nullable();
            $table->string('service_type')->nullable();
            $table->string('source')->nullable();
            $table->string('stage')->nullable()->default('new');
            $table->string('status')->nullable();
            $table->string('priority')->nullable()->default('medium');
            $table->decimal('value', 12, 2)->default(0);
            $table->date('expected_close_date')->nullable();
            $table->date('next_follow_up')->nullable();
            $table->boolean('is_converted')->default(false);
            $table->boolean('is_archived')->default(false);
            $table->string('close_reason')->nullable();
            $table->string('lost_reason')->nullable();
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('won_at')->nullable();
            $table->timestamp('lost_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('client_id')->nullable();
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
            $table->unsignedBigInteger('booking_id')->nullable();
            $table->unsignedBigInteger('lead_id')->nullable();
            $table->unsignedBigInteger('opportunity_id')->nullable();
            $table->unsignedBigInteger('client_id');
            $table->string('job_code')->nullable();
            $table->timestamp('start_time')->nullable();
            $table->timestamp('end_time')->nullable();
            $table->text('description')->nullable();
            $table->text('work_summary')->nullable();
            $table->text('issues_found')->nullable();
            $table->text('parts_used')->nullable();
            $table->integer('total_time_minutes')->nullable();
            $table->boolean('is_archived')->default(false);
            $table->string('status')->nullable()->default('pending');
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('client_id')->nullable();
            $table->unsignedBigInteger('job_id')->nullable();
            $table->unsignedBigInteger('booking_id')->nullable();
            $table->unsignedBigInteger('opportunity_id')->nullable();
            $table->unsignedBigInteger('lead_id')->nullable();
            $table->string('source')->default('generated');
            $table->string('file_path')->nullable();
            $table->string('url')->nullable();
            $table->string('file_type')->nullable();
            $table->string('mime')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->string('hash')->nullable();
            $table->integer('version')->nullable();
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->text('extracted_text')->nullable();
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('status')->default('pending');
            $table->boolean('is_primary')->default(false);
            $table->string('number')->nullable();
            $table->date('invoice_date')->nullable();
            $table->string('currency')->nullable();
            $table->date('due_date')->nullable();
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
