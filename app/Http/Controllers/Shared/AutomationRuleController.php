<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Models\Shared\AutomationRule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class AutomationRuleController extends Controller
{
    protected function companyId(): int
    {
        $companyId = (int) (auth()->user()?->company_id ?? 0);

        abort_if(! $companyId, 403);

        return $companyId;
    }

    protected function requireTenantColumn(): void
    {
        abort_unless(
            Schema::hasTable('automation_rules') && Schema::hasColumn('automation_rules', 'company_id'),
            500,
            'Security configuration error: automation_rules.company_id is required.'
        );
    }

    public function index()
    {
        $this->requireTenantColumn();

        $companyId = $this->companyId();

        $automationRules = AutomationRule::query()
            ->where('company_id', $companyId)
            ->orderBy('name')
            ->get();

        return view('automation_rules.index', compact('automationRules'));
    }

    public function toggle($id)
    {
        $this->requireTenantColumn();

        $companyId = $this->companyId();

        $rule = AutomationRule::query()
            ->where('company_id', $companyId)
            ->where('id', $id)
            ->firstOrFail();

        $rule->is_active = ! (bool) $rule->is_active;
        $rule->save();

        return redirect()
            ->route('automation_rules.index')
            ->with('status', 'Rule status updated successfully!');
    }
}