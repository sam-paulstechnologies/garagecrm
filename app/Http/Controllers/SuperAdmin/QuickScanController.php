<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Models\QuickScan\QuickScanWorkspace;
use App\QuickScan\QuickScanAccess;
use App\QuickScan\QuickScanPurge;
use App\QuickScan\QuickScanSyntheticFixture;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class QuickScanController extends SuperAdminController
{
    public function __construct(private readonly QuickScanAccess $access) {}

    public function index(): View
    {
        abort_unless(config('quick_scan.enabled'), 404);
        $scans = QuickScanWorkspace::query()->latest()->paginate(25);

        return view('super_admin.quick_scans.index', compact('scans'));
    }

    public function create(): View
    {
        abort_unless(config('quick_scan.enabled'), 404);

        return view('super_admin.quick_scans.create');
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(config('quick_scan.enabled'), 404);
        $data = $request->validate([
            'garage_name' => ['required', 'string', 'min:2', 'max:191'],
            'garage_contact_name' => ['required', 'string', 'min:2', 'max:191'],
            'garage_phone' => ['required', 'string', 'max:30'],
            'garage_email' => ['nullable', 'email', 'max:191'],
            'source' => ['nullable', 'string', 'max:80'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'follow_up_at' => ['nullable', 'date'],
        ]);
        $created = $this->access->create($data, $request->user());

        return redirect()->route('super-admin.quick-scans.show', $created['scan'])
            ->with('success', 'Quick Scan created. The secure link expires automatically.');
    }

    public function show(QuickScanWorkspace $quickScan): View
    {
        abort_unless(config('quick_scan.enabled'), 404);

        return view('super_admin.quick_scans.show', [
            'scan' => $quickScan->loadCount('candidates'),
            'scanUrl' => $this->access->url($quickScan),
        ]);
    }

    public function qr(QuickScanWorkspace $quickScan): Response
    {
        abort_unless(config('quick_scan.enabled'), 404);
        $renderer = new ImageRenderer(new RendererStyle(320, 2), new SvgImageBackEnd);
        $svg = (new Writer($renderer))->writeString($this->access->url($quickScan));

        return response($svg, 200, [
            'Content-Type' => 'image/svg+xml', 'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function revoke(Request $request, QuickScanWorkspace $quickScan): RedirectResponse
    {
        $this->access->revoke($quickScan, $request->user());

        return back()->with('success', 'The Quick Scan link was revoked.');
    }

    public function regenerate(Request $request, QuickScanWorkspace $quickScan): RedirectResponse
    {
        $this->access->regenerate($quickScan, $request->user());

        return back()->with('success', 'A new secure Quick Scan link was generated.');
    }

    public function followUp(Request $request, QuickScanWorkspace $quickScan): RedirectResponse
    {
        $data = $request->validate(['follow_up_at' => ['nullable', 'date'], 'outcome' => ['nullable', 'string', 'max:40']]);
        $quickScan->forceFill($data)->save();

        return back()->with('success', 'Sales follow-up updated.');
    }

    public function purge(QuickScanWorkspace $quickScan, QuickScanPurge $purge): RedirectResponse
    {
        $purge->purge($quickScan, force: true);

        return redirect()->route('super-admin.quick-scans.index')->with('success', 'Customer-level Quick Scan data was purged.');
    }

    public function synthetic(Request $request, QuickScanSyntheticFixture $fixture): RedirectResponse
    {
        abort_unless(app()->environment(['local', 'testing', 'staging']), 404);
        $created = $fixture->create($request->user(), 500);

        return redirect()->route('super-admin.quick-scans.show', $created['scan'])
            ->with('success', 'A deterministic 500-contact synthetic Quick Scan is report-ready. No provider or AI API was called.');
    }
}
