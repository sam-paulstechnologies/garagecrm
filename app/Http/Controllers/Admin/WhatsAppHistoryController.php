<?php

namespace App\Http\Controllers\Admin;

use App\Commercial\EntitlementService;
use App\Exceptions\WhatsAppOnboardingException;
use App\Http\Controllers\Controller;
use App\Jobs\AnalyzeWhatsAppHistoryCandidate;
use App\Jobs\ImportTrackedWhatsAppHistory;
use App\Models\System\Company;
use App\Models\WhatsApp\WhatsAppHistoryCandidate;
use App\Models\WhatsApp\WhatsAppHistoryImportBatch;
use App\Services\WhatsApp\History\HistoryQuota;
use App\Services\WhatsApp\History\HistoryReview;
use App\Services\WhatsApp\MetaEmbeddedSignupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class WhatsAppHistoryController extends Controller
{
    public function __construct(
        private readonly HistoryQuota $quota,
        private readonly HistoryReview $review,
        private readonly EntitlementService $entitlements,
        private readonly MetaEmbeddedSignupService $metaSignup,
    ) {}

    public function index(Request $request): View
    {
        $company = $this->company($request);
        $batch = WhatsAppHistoryImportBatch::query()
            ->where('company_id', $company->id)
            ->latest('id')
            ->first();
        $quota = $this->quota->summary($company->id);
        $intelligence = $this->entitlements->decide($company, 'whatsapp_history_intelligence');
        $candidates = $batch ? $this->candidatePage($request, $batch) : new LengthAwarePaginator([], 0, 25);
        $breakdown = $batch ? [
            'likely_customer' => $batch->candidates()->where('classification', 'likely_customer')->count(),
            'possible_colleague' => $batch->candidates()->where('classification', 'possible_colleague')->count(),
            'possible_personal' => $batch->candidates()->where('classification', 'possible_personal')->count(),
            'unknown' => $batch->candidates()->where('classification', 'unknown')->count(),
            'high_retention' => $batch->candidates()->where('retention_level', 'high')->count(),
        ] : [];

        return view('admin.messaging.whatsapp.history.index', compact(
            'company', 'batch', 'quota', 'intelligence', 'candidates', 'breakdown'
        ));
    }

    public function show(Request $request, WhatsAppHistoryCandidate $candidate): View
    {
        $company = $this->company($request);
        $candidate = $this->candidate($company, $candidate)->load(['batch', 'messages']);
        $hasUsage = $this->quota->hasUsage($candidate);
        $canPreview = $hasUsage || $candidate->intelligence_status === 'deterministic';

        return view('admin.messaging.whatsapp.history.show', [
            'company' => $company,
            'candidate' => $candidate,
            'canPreview' => $canPreview,
            'canTrack' => $hasUsage,
            'quota' => $this->quota->summary($company->id),
        ]);
    }

    public function requestSync(Request $request): RedirectResponse
    {
        $company = $this->company($request);
        try {
            $this->metaSignup->requestSync($company, 'history', (int) $request->user()->id);
        } catch (WhatsAppOnboardingException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Meta history synchronization was requested. Contacts will remain quarantined for review.');
    }

    public function analyse(Request $request, WhatsAppHistoryCandidate $candidate): RedirectResponse
    {
        $company = $this->company($request);
        $candidate = $this->candidate($company, $candidate);
        abort_if($candidate->review_decision === 'dont_track', 422);
        $candidate->forceFill(['intelligence_status' => 'queued', 'analysis_requested_at' => now()])->save();
        AnalyzeWhatsAppHistoryCandidate::dispatch($candidate->id);

        return back()->with('success', 'History intelligence was queued for this contact.');
    }

    public function bulkAnalyse(Request $request, WhatsAppHistoryImportBatch $batch): RedirectResponse
    {
        $company = $this->company($request);
        $batch = $this->batch($company, $batch);
        $ids = $request->validate(['candidates' => ['required', 'array', 'max:100'], 'candidates.*' => ['uuid']])['candidates'];
        $candidates = WhatsAppHistoryCandidate::query()
            ->where('company_id', $company->id)
            ->where('whatsapp_history_import_batch_id', $batch->id)
            ->whereIn('public_id', $ids)
            ->where('review_decision', '!=', 'dont_track')
            ->get();
        foreach ($candidates as $candidate) {
            $candidate->forceFill(['intelligence_status' => 'queued', 'analysis_requested_at' => now()])->save();
            AnalyzeWhatsAppHistoryCandidate::dispatch($candidate->id);
        }

        return back()->with('success', $candidates->count().' contact analyses were queued.');
    }

    public function decide(Request $request, WhatsAppHistoryCandidate $candidate): RedirectResponse
    {
        $company = $this->company($request);
        $candidate = $this->candidate($company, $candidate);
        $data = $request->validate([
            'decision' => ['required', Rule::in(['track', 'dont_track'])],
            'remove_imported_history' => ['nullable', 'boolean'],
        ]);
        if ($data['decision'] === 'track' && ! $this->quota->hasUsage($candidate)) {
            return back()->with('error', 'Analyse this contact before approving it for CRM tracking.');
        }
        $this->review->decide(
            $candidate,
            $request->user(),
            $data['decision'],
            $request->boolean('remove_imported_history'),
        );

        return back()->with('success', $data['decision'] === 'track'
            ? 'Contact approved for tracking. Confirm import when your review is complete.'
            : 'Future CRM tracking is suppressed for this contact.');
    }

    public function bulkDecide(Request $request, WhatsAppHistoryImportBatch $batch): RedirectResponse
    {
        $company = $this->company($request);
        $batch = $this->batch($company, $batch);
        $data = $request->validate([
            'candidates' => ['required', 'array', 'max:100'], 'candidates.*' => ['uuid'],
            'decision' => ['required', Rule::in(['track', 'dont_track'])],
        ]);
        $candidates = WhatsAppHistoryCandidate::query()
            ->where('company_id', $company->id)
            ->where('whatsapp_history_import_batch_id', $batch->id)
            ->whereIn('public_id', $data['candidates'])
            ->get();
        foreach ($candidates as $candidate) {
            if ($data['decision'] === 'track' && ! $this->quota->hasUsage($candidate)) {
                continue;
            }
            $this->review->decide($candidate, $request->user(), $data['decision']);
        }

        return back()->with('success', 'Selected contact decisions were saved.');
    }

    public function import(Request $request, WhatsAppHistoryImportBatch $batch): RedirectResponse
    {
        $company = $this->company($request);
        $batch = $this->batch($company, $batch);
        abort_unless($batch->candidates()->where('review_decision', 'track')->exists(), 422);
        ImportTrackedWhatsAppHistory::dispatch($batch->id);

        return back()->with('success', 'The approved history import is queued. Historical messages cannot trigger leads or automation.');
    }

    private function company(Request $request): Company
    {
        $companyId = (int) ($request->user()?->company_id ?? 0);
        abort_if($companyId < 1 || $request->user()?->role !== 'admin', 403);

        return Company::query()->findOrFail($companyId);
    }

    private function batch(Company $company, WhatsAppHistoryImportBatch $batch): WhatsAppHistoryImportBatch
    {
        abort_unless((int) $batch->company_id === (int) $company->id, 404);

        return $batch;
    }

    private function candidate(Company $company, WhatsAppHistoryCandidate $candidate): WhatsAppHistoryCandidate
    {
        abort_unless((int) $candidate->company_id === (int) $company->id, 404);

        return $candidate;
    }

    private function candidatePage(Request $request, WhatsAppHistoryImportBatch $batch): LengthAwarePaginator
    {
        $filter = (string) $request->query('filter', 'all');
        $query = WhatsAppHistoryCandidate::query()
            ->where('company_id', $batch->company_id)
            ->where('whatsapp_history_import_batch_id', $batch->id)
            ->when($filter === 'pending', fn ($q) => $q->where('review_decision', 'pending'))
            ->when($filter === 'likely_customer', fn ($q) => $q->where('classification', 'likely_customer'))
            ->when($filter === 'possible_personal', fn ($q) => $q->where('classification', 'possible_personal'))
            ->when($filter === 'possible_colleague', fn ($q) => $q->where('classification', 'possible_colleague'))
            ->when($filter === 'unknown', fn ($q) => $q->where('classification', 'unknown'))
            ->when($filter === 'high_retention', fn ($q) => $q->where('retention_level', 'high'))
            ->when($filter === 'medium_retention', fn ($q) => $q->where('retention_level', 'medium'))
            ->when($filter === 'track', fn ($q) => $q->where('review_decision', 'track'))
            ->when($filter === 'dont_track', fn ($q) => $q->where('review_decision', 'dont_track'))
            ->when($filter === 'locked', fn ($q) => $q->where('intelligence_status', 'locked'))
            ->latest('last_message_at')
            ->limit(1000)
            ->get();
        $search = mb_strtolower(trim((string) $request->query('q')));
        if ($search !== '') {
            $query = $query->filter(fn (WhatsAppHistoryCandidate $candidate): bool => str_contains(mb_strtolower((string) $candidate->display_name), $search)
                || str_contains((string) $candidate->phone_e164, $search));
        }
        $perPage = 25;
        $page = max(1, (int) $request->query('page', 1));
        $items = $query->slice(($page - 1) * $perPage, $perPage)->values();

        return new LengthAwarePaginator($items, $query->count(), $perPage, $page, [
            'path' => $request->url(), 'query' => $request->query(),
        ]);
    }
}
