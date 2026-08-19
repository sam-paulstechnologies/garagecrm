<?php

namespace App\Http\Controllers\Auth;

use App\Commercial\ProductEventRecorder;
use App\Commercial\ProductEvents;
use App\Commercial\SubscriptionManager;
use App\Http\Controllers\Controller;
use App\Models\Garage\Garage;
use App\Models\System\Company;
use App\Models\User;
use App\QuickScan\QuickScanAccess;
use App\QuickScan\QuickScanConversion;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(Request $request, ProductEventRecorder $events, QuickScanAccess $quickScanAccess): View
    {
        $events->recordSafely(
            ProductEvents::REGISTRATION_STARTED,
            dedupeKey: 'registration-started:'.hash('sha256', $request->session()->getId()),
        );

        // M34 / Fable final: the Quick Scan bearer token is handed off ONLY via
        // the single-use server-side session (kept out of the URL, browser
        // history, referrer and analytics). The legacy `?quick_scan=<token>`
        // query fallback has been removed; a crafted query token is ignored and
        // simply binds no scan.
        $handoffToken = $request->session()->pull(\App\Http\Controllers\QuickScanController::HANDOFF_SESSION_KEY);

        $quickScan = null;
        $quickScanToken = null;
        if (filled($handoffToken)) {
            $quickScan = $quickScanAccess->resolve((string) $handoffToken);
            abort_unless($quickScan->status === 'accepted' && ! $quickScan->converted_company_id, 404);
            $quickScanToken = (string) $handoffToken;
        }

        return view('auth.register', [
            'quickScan' => $quickScan,
            'quickScanToken' => $quickScanToken,
        ]);
    }

    /**
     * Handle an incoming registration request.
     */
    public function store(
        Request $request,
        SubscriptionManager $subscriptions,
        ProductEventRecorder $events,
        QuickScanAccess $quickScanAccess,
        QuickScanConversion $quickScanConversion,
    ): RedirectResponse {
        $request->merge([
            'email' => Str::lower(trim((string) $request->input('email'))),
            'garage_name' => trim((string) $request->input('garage_name')),
            'name' => trim((string) $request->input('name')),
            'phone' => trim((string) $request->input('phone')),
            'address' => trim((string) $request->input('address')),
        ]);

        $validated = $request->validate([
            'garage_name' => ['required', 'string', 'min:2', 'max:191'],
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email:rfc', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:30', 'regex:/^\+?[0-9][0-9\s().-]{6,29}$/'],
            'address' => ['required', 'string', 'min:5', 'max:255'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'terms' => ['accepted'],
            'quick_scan_token' => ['nullable', 'string', 'size:64'],
        ]);

        $quickScan = filled($validated['quick_scan_token'] ?? null)
            ? $quickScanAccess->resolve((string) $validated['quick_scan_token'])
            : null;
        if ($quickScan) {
            abort_unless($quickScan->status === 'accepted' && ! $quickScan->converted_company_id, 422);
        }

        try {
            $user = DB::transaction(function () use ($validated, $subscriptions, $quickScan, $quickScanConversion): User {
                $company = Company::query()->create([
                    'name' => $validated['garage_name'],
                    'email' => $validated['email'],
                    'phone' => $validated['phone'],
                    'address' => $validated['address'],
                    'status' => 'active',
                ]);

                $garage = Garage::query()->create([
                    'company_id' => $company->id,
                    'name' => $validated['garage_name'],
                    'phone' => $validated['phone'],
                ]);

                $subscriptions->assignFree($company);

                $user = User::query()->create([
                    'company_id' => $company->id,
                    'garage_id' => $garage->id,
                    'name' => $validated['name'],
                    'email' => $validated['email'],
                    'phone' => $validated['phone'],
                    'role' => 'admin',
                    'status' => true,
                    'must_change_password' => false,
                    'password' => $validated['password'],
                ]);

                if ($quickScan) {
                    $quickScanConversion->bindToCompany($quickScan, $company);
                }

                return $user;
            }, 3);
        } catch (QueryException $exception) {
            if (in_array((string) $exception->getCode(), ['23000', '23505'], true)) {
                throw ValidationException::withMessages([
                    'email' => 'An account already exists for this email address.',
                ]);
            }

            throw $exception;
        }

        Auth::login($user);
        $request->session()->regenerate();
        $events->recordSafely(
            ProductEvents::REGISTRATION_COMPLETED,
            $user->company,
            $user,
            ['plan_code' => 'free'],
            'registration-completed:'.$user->company_id,
        );

        return redirect()
            ->route('admin.messaging.whatsapp.index')
            ->with('success', 'Your garage workspace is ready. Connect a test WhatsApp account when you are ready.');
    }
}
