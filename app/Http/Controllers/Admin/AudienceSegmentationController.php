<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AudienceSegmentation;
use App\Models\CompanyAudienceSegmentationSetting;
use App\Services\AudienceSegmentation\AudienceSegmentationAudienceService;
use Illuminate\Http\Request;

class AudienceSegmentationController extends Controller
{
    public function __construct(
        protected AudienceSegmentationAudienceService $audienceService
    ) {
    }

    protected function companyId(): int
    {
        $companyId = (int) (auth()->user()?->company_id ?? 0);

        abort_if(! $companyId, 403, 'Company not found for this user.');

        return $companyId;
    }

    public function index()
    {
        $companyId = $this->companyId();

        $segmentations = AudienceSegmentation::query()
            ->with([
                'companySettings' => function ($query) use ($companyId) {
                    $query->where('company_id', $companyId);
                },
            ])
            ->where('is_system_defined', 1)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(function (AudienceSegmentation $segmentation) use ($companyId) {
                $setting = $segmentation->companySettings->first();

                if (! $setting) {
                    $setting = CompanyAudienceSegmentationSetting::create([
                        'company_id' => $companyId,
                        'audience_segmentation_id' => $segmentation->id,
                        'is_enabled' => (bool) $segmentation->default_enabled,
                    ]);
                }

                $audience = $this->audienceService->getAudienceForSegment(
                    $segmentation->key,
                    $companyId
                );

                $segmentation->company_is_enabled = (bool) $setting->is_enabled;
                $segmentation->audience_count = $audience->count();
                $segmentation->audience_preview = $audience->take(10)->values();

                return $segmentation;
            });

        return view('admin.settings.audience-segmentations.index', compact('segmentations'));
    }

    public function toggle(Request $request, AudienceSegmentation $audienceSegmentation)
    {
        $companyId = $this->companyId();

        $validated = $request->validate([
            'is_enabled' => ['required', 'boolean'],
        ]);

        $setting = CompanyAudienceSegmentationSetting::updateOrCreate(
            [
                'company_id' => $companyId,
                'audience_segmentation_id' => $audienceSegmentation->id,
            ],
            [
                'is_enabled' => (bool) $validated['is_enabled'],
            ]
        );

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $setting->is_enabled
                    ? 'Audience segmentation enabled.'
                    : 'Audience segmentation disabled.',
                'is_enabled' => (bool) $setting->is_enabled,
            ]);
        }

        return back()->with(
            'success',
            $setting->is_enabled
                ? 'Audience segmentation enabled.'
                : 'Audience segmentation disabled.'
        );
    }
}