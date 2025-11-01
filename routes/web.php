<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Request;

use App\Http\Middleware\VerifyCsrfToken;

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
| Root Redirect
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect('/login');
});

/*
|--------------------------------------------------------------------------
| Health Check
|--------------------------------------------------------------------------
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
| One-Time Cache Clear (Ops)
|--------------------------------------------------------------------------
*/
Route::get('/_ops/flush', function (Request $r) {
    $token = env('OPS_TOKEN');
    abort_unless($token && hash_equals($token, (string) $r->query('t')), 403);

    Artisan::call('optimize:clear');
    return nl2br(e(Artisan::output() ?: 'Caches cleared'));
});

/*
|--------------------------------------------------------------------------
| DB Counts (for quick admin metrics)
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
| Force Password Change
|--------------------------------------------------------------------------
*/
Route::middleware(['web', 'auth', 'active'])->group(function () {
    Route::get('password/force',  [PasswordForceController::class, 'edit'])->name('password.force.edit');
    Route::post('password/force', [PasswordForceController::class, 'update'])->name('password.force.update');
});

/*
|--------------------------------------------------------------------------
| Dashboard Redirects
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

    Route::get('/home', fn () => redirect()->route('dashboard'));
});

/*
|--------------------------------------------------------------------------
| WhatsApp & Mail Test Utilities
|--------------------------------------------------------------------------
*/
Route::post('/whatsapp/send', [WhatsAppSendController::class, 'send']);

Route::get('/wa-test', function (TwilioWhatsApp $wa) {
    return $wa->send('+971586934377', 'Hello from GarageCRM 👋');
});

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

        return '✅ Test email sent successfully!';
    } catch (\Throwable $e) {
        return response("❌ Mail failed: ".$e->getMessage(), 500);
    }
});

Route::get('/mail-debug', function () {
    return response()->json([
        'env_default'    => env('MAIL_MAILER'),
        'config_default' => config('mail.default'),
        'from'           => config('mail.from'),
        'mailer_smtp'    => config('mail.mailers.smtp'),
        'app_url'        => config('app.url'),
    ]);
});

/*
|--------------------------------------------------------------------------
| Twilio WhatsApp Webhooks (CSRF disabled)
|--------------------------------------------------------------------------
*/
Route::match(['GET','POST','HEAD'], '/webhooks/twilio/whatsapp',
    [TwilioWhatsAppWebhookController::class, 'handle']
)->withoutMiddleware(VerifyCsrfToken::class)
 ->name('webhooks.twilio.whatsapp');

Route::match(['GET','POST','HEAD'], '/webhooks/twilio/whatsapp/status',
    [TwilioWhatsAppWebhookController::class, 'status']
)->withoutMiddleware(VerifyCsrfToken::class)
 ->name('webhooks.twilio.whatsapp.status');

/* 👇 alias route name Twilio service expects */
Route::match(['GET','POST','HEAD'], '/webhooks/twilio/status',
    [TwilioWhatsAppWebhookController::class, 'status']
)->withoutMiddleware(VerifyCsrfToken::class)
 ->name('webhooks.twilio.status');

Route::get('/webhooks/twilio/ping', fn() => 'pong')->withoutMiddleware(VerifyCsrfToken::class);

/*
|--------------------------------------------------------------------------
| Email & Meta Webhooks
|--------------------------------------------------------------------------
*/
Route::post('/webhooks/email/inbound', [EmailInboundWebhookController::class, 'handle'])
    ->withoutMiddleware(VerifyCsrfToken::class)
    ->name('webhooks.email.inbound');

Route::get('/webhooks/meta/leads',  [MetaWebhookController::class, 'verify']);
Route::post('/webhooks/meta/leads', [MetaWebhookController::class, 'handle'])
    ->withoutMiddleware(VerifyCsrfToken::class)
    ->name('webhooks.meta.leads');

/*
|--------------------------------------------------------------------------
| Debug & Job Test Routes
|--------------------------------------------------------------------------
*/
Route::get('/debug/dispatch-delayed', function () {
    $marker = now()->format('Ymd_His');
    TestQueueJob::dispatch('Sam (delayed)', $marker)->delay(now()->addSeconds(5));
    return "Dispatched delayed job. Watch the worker ~5s later.";
});

Route::get('/debug/dispatch-failing', function () {
    FailingJob::dispatch();
    return 'Dispatched FailingJob.';
});

Route::get('/_phpinfo', fn () => phpinfo());

/*
|--------------------------------------------------------------------------
| Include other module route files
|--------------------------------------------------------------------------
*/
require __DIR__.'/auth.php';
require __DIR__.'/tenant.php';
require __DIR__.'/mechanic.php';
require __DIR__.'/whatsapp.php';

/*
|--------------------------------------------------------------------------
| Admin Feedback
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'active'])
    ->prefix('admin')
    ->as('admin.')
    ->group(function () {
        Route::get('feedback',        [\App\Http\Controllers\Admin\FeedbackController::class, 'index'])->name('feedback.index');
        Route::get('feedback/create', [\App\Http\Controllers\Admin\FeedbackController::class, 'create'])->name('feedback.create');
        Route::post('feedback',       [\App\Http\Controllers\Admin\FeedbackController::class, 'store'])->name('feedback.store');
    });
