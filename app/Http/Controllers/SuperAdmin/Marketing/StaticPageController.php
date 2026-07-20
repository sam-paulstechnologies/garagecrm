<?php

namespace App\Http\Controllers\SuperAdmin\Marketing;

use App\Http\Controllers\Controller;
use App\Models\PlatformMarketing\PlatformMarketingImport;
use App\Models\PlatformMarketing\PlatformMarketingOptOut;

class StaticPageController extends Controller
{
    public function imports()
    {
        return view('super_admin.marketing.static.imports', [
            'imports' => PlatformMarketingImport::latest()->paginate(20),
        ]);
    }

    public function templates()
    {
        return view('super_admin.marketing.static.templates');
    }

    public function settings()
    {
        return view('super_admin.marketing.static.settings');
    }

    public function suppressionList()
    {
        return view('super_admin.marketing.static.suppression-list', [
            'optOuts' => PlatformMarketingOptOut::latest('opted_out_at')->paginate(20),
        ]);
    }
}
