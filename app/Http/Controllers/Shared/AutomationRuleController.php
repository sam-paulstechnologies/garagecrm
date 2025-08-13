<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller; // ✅ Required to extend
use App\Models\Shared\AutomationRule;
use Illuminate\Http\Request;

class AutomationRuleController extends Controller
{
    public function index()
    {
        $automationRules = AutomationRule::all();
        return view('automation_rules.index', compact('automationRules'));
    }

    public function toggle($id)
    {
        $rule = AutomationRule::findOrFail($id);
        $rule->is_active = !$rule->is_active;
        $rule->save();

        return redirect()->route('automation_rules.index')->with('status', 'Rule status updated successfully!');
    }
}
