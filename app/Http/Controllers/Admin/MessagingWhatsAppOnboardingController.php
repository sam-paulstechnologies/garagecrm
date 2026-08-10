<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CompleteMessagingWhatsAppOnboardingRequest;
use App\Http\Requests\Admin\StartMessagingWhatsAppOnboardingRequest;
use App\Http\Requests\Admin\SaveMessagingWhatsAppNumberRequest;
use App\Messaging\Enums\ConnectionMode;
use App\Messaging\Exceptions\MessagingProvisioningException;
use App\Messaging\Models\MessagingConnection;
use App\Messaging\Models\MessagingNumberClaim;
use App\Messaging\WhatsApp\ConnectionHealthService;
use App\Messaging\WhatsApp\DisconnectService;
use App\Messaging\WhatsApp\EmbeddedSignupService;
use App\Messaging\WhatsApp\ProvisioningService;
use App\Messaging\WhatsApp\WhatsAppNumberClaimService;
use App\Models\System\Company;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MessagingWhatsAppOnboardingController extends Controller
{
    public function __construct(
        private readonly EmbeddedSignupService $signup,
        private readonly ProvisioningService $provisioning,
        private readonly ConnectionHealthService $health,
        private readonly DisconnectService $disconnects,
        private readonly WhatsAppNumberClaimService $numberClaims,
    ) {
    }

    public function index(Request $request): View
    {
        $company = $this->company($request);
        $connection = $this->signup->currentConnection($company);
        $claims = MessagingNumberClaim::query()
            ->where('company_id', $company->id)
            ->latest('id')
            ->get();

        return view('admin.messaging.whatsapp.index', [
            'company' => $company,
            'connection' => $connection,
            'phone' => $connection?->phoneNumbers->firstWhere('is_primary', true) ?? $connection?->phoneNumbers->first(),
            'checks' => $connection?->checks->keyBy('check_key') ?? collect(),
            'audits' => $connection?->audits ?? collect(),
            'numberClaims' => $claims,
            'numberClaim' => $claims->firstWhere('messaging_phone_number_id', null) ?? $claims->first(),
            'configurations' => [
                ConnectionMode::BusinessApp->value => $this->signup->configuration(ConnectionMode::BusinessApp->value),
                ConnectionMode::CloudApi->value => $this->signup->configuration(ConnectionMode::CloudApi->value),
            ],
        ]);
    }

    public function start(StartMessagingWhatsAppOnboardingRequest $request): JsonResponse
    {
        try {
            $result = $this->signup->start(
                $this->company($request),
                $request->user(),
                (string) $request->validated('connection_mode'),
                $request->boolean('consent_accepted'),
                $request->validated('number_claim_id'),
            );

            return response()->json(['ok' => true] + $result);
        } catch (MessagingProvisioningException $exception) {
            return response()->json(['ok' => false, 'message' => $exception->getMessage()], 422);
        }
    }

    public function storeNumber(SaveMessagingWhatsAppNumberRequest $request): RedirectResponse
    {
        $this->numberClaims->create($this->company($request), $request->user(), $request->validated());

        return back()->with('success', 'WhatsApp number added. Meta verification is still pending.');
    }

    public function updateNumber(
        SaveMessagingWhatsAppNumberRequest $request,
        MessagingNumberClaim $numberClaim,
    ): RedirectResponse {
        $this->numberClaims->update($this->company($request), $numberClaim, $request->validated());

        return back()->with('success', 'Pending WhatsApp number updated. Meta verification is still required.');
    }

    public function destroyNumber(Request $request, MessagingNumberClaim $numberClaim): RedirectResponse
    {
        $this->numberClaims->delete($this->company($request), $numberClaim);

        return back()->with('success', 'The unverified WhatsApp number was removed.');
    }

    public function complete(CompleteMessagingWhatsAppOnboardingRequest $request): JsonResponse
    {
        try {
            $result = $this->provisioning->complete($this->company($request), $request->user(), $request->validated());

            return response()->json([
                'ok' => true,
                'idempotent' => $result['idempotent'],
                'message' => $result['idempotent']
                    ? 'This WhatsApp connection was already completed.'
                    : 'WhatsApp is connected and ready for the SayaraForce inbox.',
                'status' => $result['connection']->status->value,
            ]);
        } catch (MessagingProvisioningException $exception) {
            return response()->json(['ok' => false, 'message' => $exception->getMessage(), 'reason' => $exception->reason], 422);
        }
    }

    public function health(Request $request): RedirectResponse|JsonResponse
    {
        $connection = $this->connection($request);
        $result = $this->health->run($connection);

        return $request->expectsJson()
            ? response()->json(['ok' => true, 'health' => $result])
            : back()->with($result['healthy'] ? 'success' : 'warning', $result['healthy']
                ? 'WhatsApp connection checks passed.'
                : 'WhatsApp is saved, but one or more checks need attention.');
    }

    public function retry(Request $request): RedirectResponse|JsonResponse
    {
        try {
            $result = $this->provisioning->retry($this->connection($request), $request->user());
            return $request->expectsJson()
                ? response()->json(['ok' => true, 'health' => $result])
                : back()->with('success', 'WhatsApp provisioning completed successfully.');
        } catch (MessagingProvisioningException $exception) {
            return $request->expectsJson()
                ? response()->json(['ok' => false, 'message' => $exception->getMessage()], 422)
                : back()->with('error', $exception->getMessage());
        }
    }

    public function disconnect(Request $request): RedirectResponse
    {
        $validated = $request->validate(['confirm_disconnect' => ['required', 'accepted']]);
        $this->disconnects->disconnect($this->connection($request), $request->user());

        return back()->with('success', 'SayaraForce stopped future WhatsApp sending. Existing CRM history was preserved and no Meta assets were deleted.');
    }

    private function company(Request $request): Company
    {
        $companyId = (int) ($request->user()?->company_id ?? 0);
        abort_if($companyId < 1 || $request->user()?->role !== 'admin', 403);

        return Company::query()->findOrFail($companyId);
    }

    private function connection(Request $request): MessagingConnection
    {
        $connection = $this->signup->currentConnection($this->company($request));
        abort_if(! $connection, 404);

        return $connection;
    }
}
