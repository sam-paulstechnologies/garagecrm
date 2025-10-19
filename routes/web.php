<?php
// routes/web.php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Request;

use App\Http\Controllers\PasswordForceController;
use App\Http\Controllers\WhatsAppSendController;
use App\Services\TwilioWhatsApp;
use App\Mail\BrandedNotification;

use App\Http\Controllers\Webhooks\TwilioWhatsAppWebhookController;
use App\Http\Controllers\Webhooks\EmailInboundWebhookController;
use App\Http\Controllers\Webhooks\MetaWebhookController;
use App\Jobs\TestQueueJob;
use App\Jobs\FailingJob;


/*
|--------------------------------------------------------------------------
| Public / simple pages
|--------------------------------------------------------------------------
*/

// ▶ ROOT: send users to the app (dashboard if signed in, else login)
Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect('/login'); // or ->route('login') if named
});

// Public API test
Route::get('/test-connection', fn () =>
    response()->json(['message' => 'Garage CRM public API test working!'])
);

/*
|--------------------------------------------------------------------------
| Health (no DB)
|--------------------------------------------------------------------------
|
| Lightweight health endpoint for Azure App Service.
| Optional token check using HEALTH_CHECK_TOKEN (leave unset for public).
|
*/
Route::get('/healthz', function (Request $request) {
    $token = env('HEALTH_CHECK_TOKEN');
    if ($token && $request->header('X-Health-Token') !== $token) {
        abort(403);
    }
    return response('OK', 200);
});

/*
|--------------------------------------------------------------------------
| One-time Ops: clear caches after deploy
|--------------------------------------------------------------------------
| 1) Set OPS_TOKEN in Azure App Settings (long random string)
| 2) Hit /_ops/flush?t=YOUR_TOKEN once after deploy
| 3) REMOVE this route after use
*/
Route::get('/_ops/flush', function (Request $r) {
    $token = env('OPS_TOKEN');
    abort_unless($token && hash_equals($token, (string) $r->query('t')), 403);

    Artisan::call('optimize:clear');
    return nl2br(e(Artisan::output() ?: 'Caches cleared'));
});

/*
|--------------------------------------------------------------------------
| Admin-only DB counts (optional)
|--------------------------------------------------------------------------
*/
Route::get('/db-counts', function () {
    try {
        $counts = DB::selectOne("
            SELECT
              (SELECT COUNT(*) FROM users)     AS users,
              (SELECT COUNT(*) FROM clients)   AS clients,
              (SELECT COUNT(*) FROM leads)     AS leads,
              (SELECT COUNT(*) FROM bookings)  AS bookings
        ");
        return response()->json(['ok' => true, 'counts' => $counts]);
    } catch (\Throwable $e) {
        return response()->json(['ok' => false, 'error' => $e->getMessage()], 500);
    }
})->middleware('auth');

/*
|--------------------------------------------------------------------------
| Force Password Change (after login, BEFORE admin area)
|--------------------------------------------------------------------------
| These should NOT be under the /admin prefix.
*/
Route::middleware(['web', 'auth', 'active'])->group(function () {
    Route::get('password/force',  [PasswordForceController::class, 'edit'])->name('password.force.edit');
    Route::post('password/force', [PasswordForceController::class, 'update'])->name('password.force.update');
});

/*
|--------------------------------------------------------------------------
| Authenticated redirects
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {
        $user = Auth::user();
        return match ($user->role) {
            'admin'    => redirect()->route('admin.dashboard'),
            'mechanic' => redirect()->route('mechanic.dashboard'),
            'tenant'   => redirect()->route('tenant.dashboard'),
            default    => abort(403, 'Unauthorized'),
        };
    })->name('dashboard');

    Route::get('/home', function () {
        $user = Auth::user();
        return match ($user->role) {
            'admin'    => redirect()->route('admin.dashboard'),
            'mechanic' => redirect()->route('mechanic.dashboard'),
            'tenant'   => redirect()->route('tenant.dashboard'),
            default    => abort(403, 'Unauthorized'),
        };
    });
});

/*
|--------------------------------------------------------------------------
| Dev / utility endpoints (WhatsApp + Mail)
|--------------------------------------------------------------------------
*/
Route::post('/whatsapp/send', [WhatsAppSendController::class, 'send']);

Route::get('/test-role', fn () => 'You have access!')
    ->middleware(['auth', 'role:admin']);

Route::get('/wa-test', function (TwilioWhatsApp $wa) {
    return $wa->send('+971586934377', 'Hello from GarageCRM 👋');
});

/*
|--------------------------------------------------------------------------
| Email test & preview
|--------------------------------------------------------------------------
*/
Route::get('/test-mail', function () {
    try {
        $to = env('DEV_TEST_EMAIL', 'youraddress@example.com');

        $user = (object)['email' => $to, 'name' => 'Sam'];
        $lead = (object)['id' => 123];

        Mail::to($user->email)->send(new BrandedNotification([
            'subject'  => 'Lead Created',
            'title'    => 'Thanks for contacting us!',
            'greeting' => 'Hi '.$user->name.',',
            'lines'    => [
                'We have received your enquiry.',
                'Our team will reach out shortly.',
            ],
            'cta_text' => 'View Lead',
            'cta_url'  => url('/leads/'.$lead->id),
            'outro'    => 'You can reply to this email if you have questions.',
        ]));

        return '✅ Test email sent (sync). Check your destination inbox/log.';
    } catch (\Throwable $e) {
        return response(
            "❌ Mail send failed: ".$e->getMessage()."\n\nFile: ".$e->getFile().":".$e->getLine(),
            500
        );
    }
});

Route::get('/mail-debug', function () {
    return response()->json([
        'env_default'    => env('MAIL_MAILER'),
        'config_default' => config('mail.default'),
        'from'           => config('mail.from'),
        'mailer_smtp'    => config('mail.mailers.smtp'),
    ]);
});

// In-browser preview of the email HTML (no sending)
Route::get('/dev/mail-preview', function () {
    if (!app()->isLocal() && !app()->environment(['local', 'development'])) {
        abort(403);
    }
    return view('dev.mail-preview');
});

/*
|--------------------------------------------------------------------------
| Webhooks (Inbound)
|--------------------------------------------------------------------------
| Add signature verification later as needed.
*/


Route::post('/webhooks/email/inbound',   [EmailInboundWebhookController::class, 'handle'])
    ->name('webhooks.email.inbound');

// ✅ Meta Lead Ads webhooks (verify + receive)
Route::get('/webhooks/meta/leads',  [MetaWebhookController::class, 'verify']);
Route::post('/webhooks/meta/leads', [MetaWebhookController::class, 'handle'])->name('webhooks.meta.leads');

/*
|--------------------------------------------------------------------------
| Optional: quick redirect to admin follow-ups for signed-in users
|--------------------------------------------------------------------------
*/
Route::get('/my/followups', function () {
    return Auth::user()?->role === 'admin'
        ? redirect()->route('admin.communications.followups')
        : abort(403);
})->middleware('auth')->name('my.followups');

Route::get('/_phpinfo', fn () => phpinfo());

Route::get('/debug/dispatch-delayed', function () {
    $marker = now()->format('Ymd_His');
    TestQueueJob::dispatch('Sam (delayed)', $marker)->delay(now()->addSeconds(5));
    return "Dispatched delayed job. Watch the worker ~5s later.";
});

Route::get('/debug/dispatch-failing', function () {
    FailingJob::dispatch();
    return 'Dispatched FailingJob.';
});


/*
|--------------------------------------------------------------------------
| Module route files
|--------------------------------------------------------------------------
*/
require __DIR__.'/auth.php';
// DO NOT require admin.php here; RouteServiceProvider loads it with the admin stack.
// require __DIR__.'/admin.php';
require __DIR__.'/tenant.php';
require __DIR__.'/mechanic.php';
require __DIR__.'/whatsapp.php'; 
//require __DIR__.'/admin/queue.php';

