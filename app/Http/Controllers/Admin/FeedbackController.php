<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Job\Job;
use App\Models\Client\Client;
use App\Models\Company\CompanySetting;
use App\Services\WhatsApp\SendWhatsAppMessage;

class FeedbackController extends Controller
{
    public function store(Request $r)
    {
        $data = $r->validate([
            'job_id'    => 'nullable|integer',
            'client_id' => 'nullable|integer',
            'rating'    => 'required|integer|min:1|max:5',
            'comments'  => 'nullable|string',
        ]);

        $client = null;
        $companyId = (int)(auth()->user()->company_id ?? 1);

        if (!empty($data['job_id'])) {
            $job = Job::with('client')->findOrFail($data['job_id']);
            $this->authorizeCompanyJob($job);
            $client = $job->client;
            $companyId = (int)($job->company_id ?? $companyId);
        } elseif (!empty($data['client_id'])) {
            $client = Client::findOrFail($data['client_id']);
            $this->authorizeCompanyClient($client);
            $companyId = (int)($client->company_id ?? $companyId);
        }

        // Optional persistence if you add a Feedback model/table
        if (class_exists(\App\Models\Feedback::class)) {
            \App\Models\Feedback::create([
                'company_id' => $companyId,
                'client_id'  => $client?->id,
                'job_id'     => $data['job_id'] ?? null,
                'rating'     => $data['rating'],
                'comments'   => $data['comments'] ?? null,
            ]);
        }

        if ($client && $client->phone_norm && $data['rating'] >= 4) {
            $set = CompanySetting::where('company_id', $companyId)->first();
            (new SendWhatsAppMessage())->fireEvent(
                $companyId,
                'feedback.positive.review',
                $client->phone_norm,
                [
                    'name'        => $client->name,
                    'review_link' => $set->google_review_link ?? 'https://google.com',
                ]
            );
        }

        return back()->with('success', 'Feedback recorded.');
    }

    protected function authorizeCompanyJob(Job $job): void
    {
        abort_if((int)$job->company_id !== (int)(auth()->user()->company_id ?? 0), 403);
    }

    protected function authorizeCompanyClient(Client $client): void
    {
        abort_if((int)$client->company_id !== (int)(auth()->user()->company_id ?? 0), 403);
    }
}
