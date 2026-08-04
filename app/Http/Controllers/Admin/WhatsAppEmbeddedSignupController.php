<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\WhatsAppOnboardingException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CompleteWhatsAppEmbeddedSignupRequest;
use App\Models\System\Company;
use App\Models\WhatsApp\WhatsAppConnectionAudit;
use App\Services\WhatsApp\MetaEmbeddedSignupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Throwable;

class WhatsAppEmbeddedSignupController extends Controller
{
    public function __construct(
        private readonly MetaEmbeddedSignupService $embeddedSignupService
    ) {
    }

    public function index(Request $request): View
    {
        $company = $this->resolveCompany($request);
        $connectionMode = $this->embeddedSignupService->normalizeConnectionMode(
            $request->query('mode', MetaEmbeddedSignupService::MODE_BUSINESS_APP)
        );
        $configuration = $this->embeddedSignupService->signupConfiguration($connectionMode);
        $state = $this->embeddedSignupService->createState(
            (int) $company->id,
            (int) $request->user()->id,
            $connectionMode
        );

        return view('admin.whatsapp.connect', [
            'company' => $company,
            'state' => $state,
            'connectionMode' => $connectionMode,
            'signupConfiguration' => $configuration,
            'signupConfigurations' => [
                MetaEmbeddedSignupService::MODE_BUSINESS_APP => $this->embeddedSignupService
                    ->signupConfiguration(MetaEmbeddedSignupService::MODE_BUSINESS_APP),
                MetaEmbeddedSignupService::MODE_CLOUD_API => $this->embeddedSignupService
                    ->signupConfiguration(MetaEmbeddedSignupService::MODE_CLOUD_API),
            ],
            'status' => $this->embeddedSignupService->connectionStatus($company),
            'recentAudits' => Schema::hasTable('whatsapp_connection_audits')
                ? WhatsAppConnectionAudit::query()
                    ->where('company_id', $company->id)
                    ->latest('occurred_at')
                    ->limit(8)
                    ->get()
                : collect(),
        ]);
    }

    public function status(Request $request): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'status' => $this->embeddedSignupService->connectionStatus($this->resolveCompany($request)),
        ]);
    }

    public function callback(CompleteWhatsAppEmbeddedSignupRequest $request): JsonResponse|RedirectResponse
    {
        $company = $this->resolveCompany($request);

        try {
            $result = $this->embeddedSignupService->complete(
                $company,
                (int) $request->user()->id,
                $request->validated()
            );

            $message = $result['idempotent']
                ? 'This WhatsApp connection was already completed.'
                : 'WhatsApp connected and webhook subscription confirmed.';

            if ($request->expectsJson()) {
                return response()->json([
                    'ok' => true,
                    'message' => $message,
                    'warnings' => $result['warnings'],
                    'status' => $result['status'],
                ]);
            }

            return redirect()->route('admin.whatsapp.connect')->with('success', $message);
        } catch (WhatsAppOnboardingException $exception) {
            Log::warning('[SF-WA Connect] Embedded Signup completion rejected', [
                'company_id' => $company->id,
                'user_id' => $request->user()->id,
                'reason' => $exception->reason,
            ]);

            return $this->failureResponse($request, $exception->getMessage(), 422);
        } catch (Throwable $exception) {
            Log::error('[SF-WA Connect] Embedded Signup completion failed safely', [
                'company_id' => $company->id,
                'user_id' => $request->user()->id,
                'exception' => $exception::class,
            ]);

            return $this->failureResponse(
                $request,
                'WhatsApp connection could not be completed. Restart Embedded Signup and try again.',
                500
            );
        }
    }

    public function diagnostics(Request $request): RedirectResponse|JsonResponse
    {
        $company = $this->resolveCompany($request);

        try {
            $diagnostics = $this->embeddedSignupService->diagnostics($company, (int) $request->user()->id);

            if ($request->expectsJson()) {
                return response()->json(['ok' => true, 'diagnostics' => $diagnostics]);
            }

            return back()->with('success', 'WhatsApp diagnostics completed.');
        } catch (WhatsAppOnboardingException $exception) {
            return $this->failureResponse($request, $exception->getMessage(), 422);
        }
    }

    public function requestContactSync(Request $request): RedirectResponse|JsonResponse
    {
        return $this->requestSync($request, 'smb_app_state_sync');
    }

    public function requestHistorySync(Request $request): RedirectResponse|JsonResponse
    {
        return $this->requestSync($request, 'history');
    }

    public function disconnect(Request $request): RedirectResponse|JsonResponse
    {
        $company = $this->embeddedSignupService->disconnectCompany(
            $this->resolveCompany($request),
            (int) $request->user()->id
        );

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => 'The SayaraForce WhatsApp connection was disabled locally.',
                'status' => $this->embeddedSignupService->connectionStatus($company),
            ]);
        }

        return redirect()->route('admin.whatsapp.connect')
            ->with('success', 'The SayaraForce WhatsApp connection was disabled locally.');
    }

    private function requestSync(Request $request, string $syncType): RedirectResponse|JsonResponse
    {
        $company = $this->resolveCompany($request);

        try {
            $result = $this->embeddedSignupService->requestSync(
                $company,
                $syncType,
                (int) $request->user()->id
            );

            $message = $syncType === 'history'
                ? 'Meta accepted the approved chat-history sync request.'
                : 'Meta accepted the contact sync request.';

            return $request->expectsJson()
                ? response()->json(['ok' => true, 'message' => $message, 'result' => $result])
                : back()->with('success', $message);
        } catch (WhatsAppOnboardingException $exception) {
            return $this->failureResponse($request, $exception->getMessage(), 422);
        }
    }

    private function resolveCompany(Request $request): Company
    {
        $companyId = (int) ($request->user()?->company_id ?? 0);

        abort_if($companyId < 1, 403, 'No company is attached to this user.');

        return Company::query()->findOrFail($companyId);
    }

    private function failureResponse(Request $request, string $message, int $status): JsonResponse|RedirectResponse
    {
        return $request->expectsJson()
            ? response()->json(['ok' => false, 'message' => $message], $status)
            : redirect()->route('admin.whatsapp.connect')->with('error', $message);
    }
}
