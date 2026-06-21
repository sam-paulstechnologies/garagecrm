<?php

namespace Tests\Feature;

use App\Models\Job\Booking;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminCalendarEventsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $assignedUser;
    private int $companyId;
    private int $otherCompanyId;
    private int $clientId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareSchema();

        $this->companyId = (int) DB::table('companies')->insertGetId([
            'name' => 'Calendar Garage',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->otherCompanyId = (int) DB::table('companies')->insertGetId([
            'name' => 'Other Garage',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->admin = User::create([
            'name' => 'Calendar Admin',
            'email' => 'calendar-admin@example.test',
            'password' => 'password',
            'role' => 'admin',
            'company_id' => $this->companyId,
            'status' => true,
            'must_change_password' => false,
        ]);

        $this->assignedUser = User::create([
            'name' => 'Assigned Calendar User',
            'email' => 'assigned-calendar@example.test',
            'password' => 'password',
            'role' => 'manager',
            'company_id' => $this->companyId,
            'status' => true,
            'must_change_password' => false,
        ]);

        $this->clientId = (int) DB::table('clients')->insertGetId([
            'company_id' => $this->companyId,
            'name' => 'Calendar Client',
            'phone' => '971500000100',
            'phone_norm' => '971500000100',
            'email' => 'calendar-client@example.test',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_admin_calendar_returns_booking_confirmation_events_only(): void
    {
        $date = now()->addDays(3)->toDateString();

        $pending = $this->booking(['booking_date' => $date, 'status' => Booking::STATUS_PENDING]);
        $scheduled = $this->booking(['booking_date' => $date, 'status' => Booking::STATUS_SCHEDULED]);
        $reschedule = $this->booking([
            'booking_date' => $date,
            'status' => Booking::STATUS_RESCHEDULE_REQUIRED,
            'reschedule_reason' => 'Customer requested another time.',
        ]);
        $this->booking(['booking_date' => $date, 'status' => Booking::STATUS_CONVERTED_TO_JOB]);
        $this->booking(['booking_date' => $date, 'status' => Booking::STATUS_LOST]);

        $events = $this->actingAs($this->admin)
            ->getJson(route('admin.calendar.events', [
                'start' => now()->startOfMonth()->toDateString(),
                'end' => now()->addMonth()->endOfMonth()->toDateString(),
            ]))
            ->assertOk()
            ->json();

        $ids = array_column($events, 'id');
        sort($ids);

        $expectedIds = [
            'booking:' . $pending->id,
            'booking:' . $scheduled->id,
            'booking:' . $reschedule->id,
        ];
        sort($expectedIds);

        $this->assertSame($expectedIds, $ids);

        $pendingEvent = collect($events)->firstWhere('id', 'booking:' . $pending->id);
        $this->assertSame('Manager Confirmation', $pendingEvent['status_label']);
        $this->assertSame('#f59e0b', $pendingEvent['backgroundColor']);
        $this->assertSame(route('admin.bookings.show', $pending), $pendingEvent['url']);
        $this->assertSame('booking', $pendingEvent['extendedProps']['type']);

        $scheduledEvent = collect($events)->firstWhere('id', 'booking:' . $scheduled->id);
        $this->assertSame('Booking Confirmed', $scheduledEvent['status_label']);
        $this->assertSame('#16a34a', $scheduledEvent['backgroundColor']);

        $rescheduleEvent = collect($events)->firstWhere('id', 'booking:' . $reschedule->id);
        $this->assertSame('Rescheduling Required', $rescheduleEvent['status_label']);
        $this->assertSame('#dc2626', $rescheduleEvent['backgroundColor']);
    }

    public function test_admin_calendar_filters_by_date_range_tenant_status_assignee_and_slot(): void
    {
        $inside = now()->addDays(2)->toDateString();
        $outside = now()->addMonths(2)->toDateString();

        $visible = $this->booking([
            'booking_date' => $inside,
            'status' => Booking::STATUS_RESCHEDULE_REQUIRED,
            'slot' => 'afternoon',
            'assigned_to' => $this->assignedUser->id,
            'reschedule_reason' => 'Slot no longer works.',
        ]);

        $this->booking([
            'booking_date' => $inside,
            'status' => Booking::STATUS_RESCHEDULE_REQUIRED,
            'slot' => 'morning',
            'assigned_to' => null,
            'reschedule_reason' => 'Wrong slot.',
        ]);

        $this->booking([
            'booking_date' => $outside,
            'status' => Booking::STATUS_RESCHEDULE_REQUIRED,
            'slot' => 'afternoon',
            'assigned_to' => $this->assignedUser->id,
            'reschedule_reason' => 'Outside range.',
        ]);

        DB::table('bookings')->insert([
            'company_id' => $this->otherCompanyId,
            'client_id' => null,
            'name' => 'Other Company Booking',
            'booking_date' => $inside,
            'slot' => 'afternoon',
            'status' => Booking::STATUS_RESCHEDULE_REQUIRED,
            'reschedule_reason' => 'Other tenant.',
            'priority' => 'medium',
            'pickup_required' => false,
            'is_archived' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $events = $this->actingAs($this->admin)
            ->getJson(route('admin.calendar.events', [
                'start' => now()->toDateString(),
                'end' => now()->addWeek()->toDateString(),
                'status' => Booking::STATUS_RESCHEDULE_REQUIRED,
                'assigned_user' => $this->assignedUser->id,
                'slot' => 'afternoon',
            ]))
            ->assertOk()
            ->json();

        $this->assertSame(['booking:' . $visible->id], array_column($events, 'id'));
        $this->assertSame('Afternoon', $events[0]['extendedProps']['slot_label']);
        $this->assertSame('Assigned Calendar User', $events[0]['extendedProps']['assigned_user']);
    }

    private function booking(array $overrides = []): Booking
    {
        $id = DB::table('bookings')->insertGetId(array_merge([
            'company_id' => $this->companyId,
            'client_id' => $this->clientId,
            'name' => 'Calendar Booking',
            'service_type' => 'General Service',
            'booking_date' => now()->addDay()->toDateString(),
            'slot' => 'morning',
            'priority' => 'medium',
            'expected_duration' => 120,
            'status' => Booking::STATUS_SCHEDULED,
            'pickup_required' => false,
            'is_archived' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides));

        return Booking::findOrFail($id);
    }

    private function prepareSchema(): void
    {
        $this->ensureUserColumns();

        Schema::dropIfExists('bookings');
        Schema::dropIfExists('clients');

        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->string('name')->nullable();
            $table->string('phone')->nullable();
            $table->string('phone_norm')->nullable();
            $table->string('email')->nullable();
            $table->timestamps();
        });

        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('client_id')->nullable();
            $table->string('name')->nullable();
            $table->string('service_type')->nullable();
            $table->date('booking_date')->nullable();
            $table->string('slot')->nullable();
            $table->string('priority')->nullable();
            $table->integer('expected_duration')->nullable();
            $table->string('status')->nullable();
            $table->string('lost_reason')->nullable();
            $table->text('reschedule_reason')->nullable();
            $table->timestamp('reschedule_requested_at')->nullable();
            $table->boolean('pickup_required')->default(false);
            $table->boolean('is_archived')->default(false);
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    private function ensureUserColumns(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'role')) {
                $table->string('role')->default('admin');
            }

            if (! Schema::hasColumn('users', 'company_id')) {
                $table->unsignedBigInteger('company_id')->nullable();
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
