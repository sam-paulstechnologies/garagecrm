<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    public function index()
    {
        $companyId = (int) (auth()->user()?->company_id ?? 0);

        abort_if(! $companyId, 403);

        $stats = [
            'open_leads' => $this->countOpenLeads($companyId),
            'human_escalations' => $this->countHumanEscalations($companyId),
            'pending_bookings' => $this->countBookingsByStatus($companyId, ['pending']),
            'scheduled_bookings' => $this->countBookingsByStatus($companyId, ['scheduled']),
            'converted_bookings' => $this->countBookingsByStatus($companyId, ['converted_to_job']),
            'lost_bookings' => $this->countBookingsByStatus($companyId, ['lost']),
            'jobs_pending' => $this->countJobsByStatus($companyId, ['pending']),
            'jobs_in_progress' => $this->countJobsByStatus($companyId, ['in_progress', 'started', 'active']),
            'jobs_completed' => $this->countJobsByStatus($companyId, ['completed', 'done', 'closed']),
            'unread_messages' => $this->countUnreadMessages($companyId),
        ];

        $pendingBookings = $this->latestPendingBookings($companyId);
        $escalatedLeads = $this->latestEscalatedLeads($companyId);
        $activeJobs = $this->latestActiveJobs($companyId);

        return view('manager.dashboard', [
            'stats' => $stats,
            'pendingBookings' => $pendingBookings,
            'escalatedLeads' => $escalatedLeads,
            'activeJobs' => $activeJobs,
        ]);
    }

    protected function countOpenLeads(int $companyId): int
    {
        if (! Schema::hasTable('leads')) {
            return 0;
        }

        $query = DB::table('leads')
            ->where('company_id', $companyId);

        if (Schema::hasColumn('leads', 'is_active')) {
            $query->where('is_active', 1);
        }

        if (Schema::hasColumn('leads', 'status')) {
            $query->whereNotIn('status', [
                'disqualified',
                'closed',
                'converted',
                'lost',
            ]);
        }

        return (int) $query->count();
    }

    protected function countHumanEscalations(int $companyId): int
    {
        if (! Schema::hasTable('leads')) {
            return 0;
        }

        $query = DB::table('leads')
            ->where('company_id', $companyId);

        if (Schema::hasColumn('leads', 'conversation_state')) {
            $query->where('conversation_state', 'human');
        }

        if (Schema::hasColumn('leads', 'is_active')) {
            $query->where('is_active', 1);
        }

        return (int) $query->count();
    }

    protected function countBookingsByStatus(int $companyId, array $statuses): int
    {
        if (! Schema::hasTable('bookings')) {
            return 0;
        }

        $query = DB::table('bookings')
            ->where('company_id', $companyId);

        if (Schema::hasColumn('bookings', 'is_archived')) {
            $query->where('is_archived', 0);
        }

        if (Schema::hasColumn('bookings', 'status')) {
            $query->whereIn('status', $statuses);
        }

        return (int) $query->count();
    }

    protected function countJobsByStatus(int $companyId, array $statuses): int
    {
        if (! Schema::hasTable('jobs')) {
            return 0;
        }

        $query = DB::table('jobs')
            ->where('company_id', $companyId);

        if (Schema::hasColumn('jobs', 'is_archived')) {
            $query->where('is_archived', 0);
        }

        if (Schema::hasColumn('jobs', 'status')) {
            $query->whereIn('status', $statuses);
        }

        return (int) $query->count();
    }

    protected function countUnreadMessages(int $companyId): int
    {
        if (! Schema::hasTable('message_logs')) {
            return 0;
        }

        $query = DB::table('message_logs');

        if (Schema::hasColumn('message_logs', 'company_id')) {
            $query->where('company_id', $companyId);
        }

        if (Schema::hasColumn('message_logs', 'direction')) {
            $query->where('direction', 'in');
        }

        if (Schema::hasColumn('message_logs', 'read_at')) {
            $query->whereNull('read_at');
        }

        return (int) $query->count();
    }

    protected function latestPendingBookings(int $companyId)
    {
        if (! Schema::hasTable('bookings')) {
            return collect();
        }

        $query = DB::table('bookings')
            ->where('company_id', $companyId);

        if (Schema::hasColumn('bookings', 'is_archived')) {
            $query->where('is_archived', 0);
        }

        if (Schema::hasColumn('bookings', 'status')) {
            $query->whereIn('status', ['pending', 'scheduled']);
        }

        return $query
            ->orderByDesc('id')
            ->limit(8)
            ->get();
    }

    protected function latestEscalatedLeads(int $companyId)
    {
        if (! Schema::hasTable('leads')) {
            return collect();
        }

        $query = DB::table('leads')
            ->where('company_id', $companyId);

        if (Schema::hasColumn('leads', 'conversation_state')) {
            $query->where('conversation_state', 'human');
        }

        if (Schema::hasColumn('leads', 'is_active')) {
            $query->where('is_active', 1);
        }

        return $query
            ->orderByDesc('updated_at')
            ->limit(8)
            ->get();
    }

    protected function latestActiveJobs(int $companyId)
    {
        if (! Schema::hasTable('jobs')) {
            return collect();
        }

        $query = DB::table('jobs')
            ->where('company_id', $companyId);

        if (Schema::hasColumn('jobs', 'is_archived')) {
            $query->where('is_archived', 0);
        }

        if (Schema::hasColumn('jobs', 'status')) {
            $query->whereIn('status', ['pending', 'in_progress', 'started', 'active']);
        }

        return $query
            ->orderByDesc('id')
            ->limit(8)
            ->get();
    }
}