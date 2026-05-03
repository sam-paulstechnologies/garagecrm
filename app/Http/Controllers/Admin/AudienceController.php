<?php

// app/Http/Controllers/Admin/AudienceController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Audience;
use App\Models\AudienceRule;
use App\Models\AudienceMembership;
use App\Models\Client\Client;
use App\Services\Audiences\AudienceResolver;
use Illuminate\Http\Request;

class AudienceController extends Controller
{
    public function index(Request $request)
    {
        $companyId = (int)(auth()->user()->company_id ?? 0);

        $audiences = Audience::query()
            ->where(function ($q) use ($companyId) {
                $q->whereNull('company_id')->orWhere('company_id', $companyId);
            })
            ->orderByDesc('is_system')
            ->orderBy('name')
            ->paginate(20);

        return view('admin.audiences.index', compact('audiences'));
    }

    public function create()
    {
        return view('admin.audiences.create');
    }

    public function store(Request $request)
    {
        $companyId = (int)(auth()->user()->company_id ?? 0);

        $data = $request->validate([
            'name' => ['required','string','max:255'],
            'description' => ['nullable','string'],
            'is_active' => ['nullable'],
            'rules_json' => ['nullable','string'],
        ]);

        $audience = Audience::query()->create([
            'company_id' => $companyId,
            'name' => $data['name'],
            'entity_type' => 'client',
            'description' => $data['description'] ?? null,
            'is_active' => (bool)($data['is_active'] ?? true),
            'is_system' => 0,
        ]);

        $rulesArr = null;
        if (!empty($data['rules_json'])) {
            $decoded = json_decode($data['rules_json'], true);
            if (is_array($decoded)) $rulesArr = $decoded;
        }

        AudienceRule::query()->create([
            'audience_id' => $audience->id,
            'rules_json' => $rulesArr,
        ]);

        return redirect()->route('admin.audiences.show', $audience->id)
            ->with('success', 'Audience created.');
    }

    public function show(Request $request, Audience $audience)
    {
        $companyId = (int)(auth()->user()->company_id ?? 0);
        $this->guardCompany($audience, $companyId);

        $rules = $audience->rules()->latest()->first();

        $members = AudienceMembership::query()
            ->where('audience_id', $audience->id)
            ->where('company_id', $companyId)
            ->with('client')
            ->orderByDesc('id')
            ->paginate(25);

        return view('admin.audiences.show', compact('audience','rules','members'));
    }

    public function edit(Audience $audience)
    {
        $companyId = (int)(auth()->user()->company_id ?? 0);
        $this->guardCompany($audience, $companyId);

        $rules = $audience->rules()->latest()->first();

        return view('admin.audiences.edit', compact('audience','rules'));
    }

    public function update(Request $request, Audience $audience)
    {
        $companyId = (int)(auth()->user()->company_id ?? 0);
        $this->guardCompany($audience, $companyId);

        if ($audience->is_system) {
            return back()->with('error', 'System audiences cannot be edited.');
        }

        $data = $request->validate([
            'name' => ['required','string','max:255'],
            'description' => ['nullable','string'],
            'is_active' => ['nullable'],
            'rules_json' => ['nullable','string'],
        ]);

        $audience->update([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'is_active' => (bool)($data['is_active'] ?? true),
        ]);

        $rulesArr = null;
        if (!empty($data['rules_json'])) {
            $decoded = json_decode($data['rules_json'], true);
            if (is_array($decoded)) $rulesArr = $decoded;
        }

        AudienceRule::query()->create([
            'audience_id' => $audience->id,
            'rules_json' => $rulesArr,
        ]);

        return redirect()->route('admin.audiences.show', $audience->id)
            ->with('success', 'Audience updated.');
    }

    public function destroy(Audience $audience)
    {
        $companyId = (int)(auth()->user()->company_id ?? 0);
        $this->guardCompany($audience, $companyId);

        if ($audience->is_system) {
            return back()->with('error', 'System audiences cannot be deleted.');
        }

        $audience->delete();

        return redirect()->route('admin.audiences.index')->with('success', 'Audience deleted.');
    }

    public function rebuild(Request $request, AudienceResolver $resolver)
    {
        $companyId = (int)(auth()->user()->company_id ?? 0);
        $resolver->rebuildForCompany($companyId);

        return redirect()->route('admin.audiences.index')->with('success', 'Audiences rebuilt.');
    }

    public function unassigned(Request $request)
    {
        $companyId = (int)(auth()->user()->company_id ?? 0);

        $inAny = \App\Models\AudienceMembership::query()
            ->where('company_id', $companyId)
            ->pluck('client_id')
            ->unique()
            ->all();

        $clients = Client::query()
            ->where('company_id', $companyId)
            ->whereNotIn('id', $inAny)
            ->orderByDesc('id')
            ->paginate(25);

        return view('admin.audiences.unassigned', compact('clients'));
    }

    private function guardCompany(Audience $audience, int $companyId): void
    {
        if ($audience->company_id !== null && (int)$audience->company_id !== $companyId) {
            abort(403);
        }
    }
}
 