<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\System\Company;
use App\Services\WhatsApp\MetaEmbeddedSignupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;
use Throwable;

class WhatsAppEmbeddedSignupController extends Controller
{
    public function __construct(
        protected MetaEmbeddedSignupService $embeddedSignupService
    ) {
    }

    public function index(Request $request): View
    {
        $company = $this->resolveCompany($request);

        $state = $this->embeddedSignupService->createState(
            (int) $company->id,
            $request->user()?->id
        );

        return view('admin.whatsapp.connect', [
            'company' => $company,
            'state' => $state,
            'status' => $this->embeddedSignupService->connectionStatus($company),
            'metaAppId' => config('services.meta.app_id')
                ?: config('services.meta_leads.app_id')
                ?: env('META_APP_ID'),
            'graphVersion' => config('services.meta.api_version')
                ?: config('services.whatsapp.meta.api_version')
                ?: 'v21.0',
        ]);
    }

    public function status(Request $request): JsonResponse
    {
        $company = $this->resolveCompany($request);

        return response()->json([
            'ok' => true,
            'status' => $this->embeddedSignupService->connectionStatus($company),
        ]);
    }

    public function callback(Request $request): RedirectResponse|JsonResponse
    {
        $company = $this->resolveCompany($request);

        $validated = $request->validate([
            'code' => ['nullable', 'string'],
            'state' => ['nullable', 'string'],
            'business_id' => ['nullable', 'string'],
            'waba_id' => ['nullable', 'string'],
            'phone_number_id' => ['nullable', 'string'],
            'display_phone_number' => ['nullable', 'string'],
        ]);

        $state = $validated['state'] ?? '';

        try {
            $accessToken = null;
            $tokenPayload = [];

            if (filled($validated['code'] ?? null)) {
                $tokenPayload = $this->embeddedSignupService
                    ->exchangeCodeForAccessToken($validated['code']);

                $accessToken = $tokenPayload['access_token'] ?? null;
            }

            if (blank($accessToken)) {
                throw new \RuntimeException('Meta did not return access token.');
            }

            $wabaId = $validated['waba_id'] ?? null;
            $phoneNumberId = $validated['phone_number_id'] ?? null;
            $displayPhoneNumber = $validated['display_phone_number'] ?? null;

            if (filled($wabaId) && blank($phoneNumberId)) {
                $numbers = $this->embeddedSignupService->fetchPhoneNumbers($wabaId, $accessToken);

                if (! empty($numbers[0]['id'])) {
                    $phoneNumberId = $numbers[0]['id'];
                    $displayPhoneNumber = $numbers[0]['display_phone_number'] ?? $displayPhoneNumber;
                }
            }

            if (filled($phoneNumberId) && blank($displayPhoneNumber)) {
                $phoneNumber = $this->embeddedSignupService->fetchPhoneNumber($phoneNumberId, $accessToken);
                $displayPhoneNumber = $phoneNumber['display_phone_number'] ?? null;
            }

            $company = $this->embeddedSignupService->saveConnectionToCompany(
                company: $company,
                accessToken: $accessToken,
                wabaId: $wabaId,
                phoneNumberId: $phoneNumberId,
                businessId: $validated['business_id'] ?? null,
                displayPhoneNumber: $displayPhoneNumber,
                metaPayload: $tokenPayload
            );

            if (filled($state)) {
                $this->embeddedSignupService->markSessionCompleted($state, $company, [
                    'business_id' => $validated['business_id'] ?? null,
                    'waba_id' => $wabaId,
                    'phone_number_id' => $phoneNumberId,
                    'display_phone_number' => $displayPhoneNumber,
                    'token_response' => $this->safeTokenPayload($tokenPayload),
                ]);
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'ok' => true,
                    'message' => 'WhatsApp connected successfully.',
                    'status' => $this->embeddedSignupService->connectionStatus($company),
                ]);
            }

            return $this->redirectToConnect()
                ->with('success', 'WhatsApp connected successfully.');
        } catch (Throwable $e) {
            logger()->error('[SF-WA Connect] Embedded signup callback failed', [
                'company_id' => $company->id ?? null,
                'state' => $state,
                'error' => $e->getMessage(),
            ]);

            if (filled($state)) {
                $this->embeddedSignupService->markSessionFailed($state, $e->getMessage(), [
                    'request' => $request->except(['code']),
                ]);
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'ok' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return $this->redirectToConnect()
                ->with('error', 'WhatsApp connection failed: '.$e->getMessage());
        }
    }

    public function disconnect(Request $request): RedirectResponse|JsonResponse
    {
        $company = $this->resolveCompany($request);

        try {
            $company = $this->embeddedSignupService->disconnectCompany($company);

            if ($request->expectsJson()) {
                return response()->json([
                    'ok' => true,
                    'message' => 'WhatsApp disconnected successfully.',
                    'status' => $this->embeddedSignupService->connectionStatus($company),
                ]);
            }

            return $this->redirectToConnect()
                ->with('success', 'WhatsApp disconnected successfully.');
        } catch (Throwable $e) {
            logger()->error('[SF-WA Connect] Disconnect failed', [
                'company_id' => $company->id ?? null,
                'error' => $e->getMessage(),
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'ok' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return $this->redirectToConnect()
                ->with('error', 'Unable to disconnect WhatsApp: '.$e->getMessage());
        }
    }

    protected function resolveCompany(Request $request): Company
    {
        $user = $request->user();

        abort_if(! $user, 403);

        $companyId = $user->company_id ?? $user->company?->id ?? null;

        abort_if(! $companyId, 403, 'No company is attached to this user.');

        return Company::query()->findOrFail($companyId);
    }

    protected function redirectToConnect(): RedirectResponse
    {
        if (Route::has('admin.whatsapp.connect')) {
            return redirect()->route('admin.whatsapp.connect');
        }

        return redirect('/admin/whatsapp/connect');
    }

    protected function safeTokenPayload(array $payload): array
    {
        if (isset($payload['access_token'])) {
            $payload['access_token'] = '[hidden]';
        }

        return $payload;
    }
}