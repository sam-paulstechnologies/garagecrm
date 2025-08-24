<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\System\Company;
use App\Models\System\Garage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        $companyId = auth()->user()->company_id;

        $users = User::with(['company', 'garage'])
            ->where('company_id', $companyId)
            ->orderBy('name')
            ->paginate(20);

        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        $companyId = auth()->user()->company_id;

        // Limit dropdowns to current company (adjust if you have superadmin)
        $companies = Company::where('id', $companyId)->get();
        $garages   = Garage::where('company_id', $companyId)->get();
        $roles     = User::ROLES;

        return view('admin.users.create', compact('companies', 'garages', 'roles'));
    }

    public function store(Request $request)
    {
        $companyId = auth()->user()->company_id;

        $data = $request->validate([
            'name'       => ['required','string','max:255'],
            'email'      => ['required','email','unique:users,email'],
            'phone'      => ['nullable','string','max:20'],
            'role'       => ['required', Rule::in(User::ROLES)],
            'password'   => ['required','string','min:8','confirmed'],
            'company_id' => ['nullable','exists:companies,id'],
            'garage_id'  => ['nullable','exists:garages,id'],
            'status'     => ['required'], // 1 or 0
        ]);

        // Default to current company if not provided
        $data['company_id'] = $data['company_id'] ?? $companyId;

        // Ensure selected garage belongs to selected/current company
        if (!empty($data['garage_id'])) {
            $garageCompany = Garage::whereKey($data['garage_id'])->value('company_id');
            abort_if((int)$garageCompany !== (int)$data['company_id'], 422, 'Garage does not belong to selected company.');
        }

        // Normalize status; DO NOT pre-hash password (mutator handles it)
        $data['status'] = $this->normalizeStatus($data['status']);

        $user = User::create($data);

        return redirect()->route('admin.users.index')->with('success', 'User created successfully.');
    }

    public function edit(User $user)
    {
        $this->authorizeCompany($user);

        $companyId = auth()->user()->company_id;
        $companies = Company::where('id', $companyId)->get();
        $garages   = Garage::where('company_id', $companyId)->get();
        $roles     = User::ROLES;

        return view('admin.users.edit', compact('user', 'companies', 'garages', 'roles'));
    }

    public function update(Request $request, User $user)
    {
        $this->authorizeCompany($user);

        $data = $request->validate([
            'name'       => ['required','string','max:255'],
            'email'      => ['required','email','unique:users,email,' . $user->id],
            'phone'      => ['nullable','string','max:20'],
            'role'       => ['required', Rule::in(User::ROLES)],
            'password'   => ['nullable','string','min:8','confirmed'],
            'company_id' => ['nullable','exists:companies,id'],
            'garage_id'  => ['nullable','exists:garages,id'],
            'status'     => ['required'],
        ]);

        // Keep user within same company unless you explicitly allow cross-company moves
        $data['company_id'] = $data['company_id'] ?? $user->company_id;
        abort_if((int)$data['company_id'] !== (int)$user->company_id, 403, 'Cannot change company for this user.');

        if (!empty($data['garage_id'])) {
            $garageCompany = Garage::whereKey($data['garage_id'])->value('company_id');
            abort_if((int)$garageCompany !== (int)$data['company_id'], 422, 'Garage does not belong to selected company.');
        }

        $data['status'] = $this->normalizeStatus($data['status']);

        // Protect: cannot demote or deactivate the last active admin of the company
        $this->assertNotLastAdminOnChange($user, $data);

        // If password present, set plain text to trigger model mutator (no Hash::make here)
        if (empty($data['password'])) {
            unset($data['password']);
        }

        $user->update($data);

        return redirect()->route('admin.users.index')->with('success', 'User updated successfully.');
    }

    public function destroy(User $user)
    {
        $this->authorizeCompany($user);
        $this->assertNotSelf($user);
        $this->assertNotLastAdminOnChange($user, ['status' => 0]); // deleting = effectively deactivate

        $user->delete();

        return redirect()->route('admin.users.index')->with('success', 'User deleted successfully.');
    }

    // --- Extra admin actions ---

    public function toggleStatus(User $user)
    {
        $this->authorizeCompany($user);
        $this->assertNotSelf($user);

        $new = $this->normalizeStatus(!$this->normalizeStatus($user->status));
        $this->assertNotLastAdminOnChange($user, ['status' => $new]);

        $user->update(['status' => $new]);

        return back()->with('success', 'User status updated.');
    }

    public function resetPassword(User $user)
    {
        $this->authorizeCompany($user);

        $temp = Str::random(12);

        // Set plain temp to trigger mutator; do NOT Hash::make() here
        $user->password = $temp;

        if (Schema::hasColumn('users', 'must_change_password')) {
            $user->must_change_password = true;
        }

        $user->save();

        // For production, send an email; for now, show once in flash
        return back()->with('success', "Temporary password set: {$temp}");
    }

    // --- Helpers ---

    protected function authorizeCompany(User $user): void
    {
        abort_if($user->company_id !== auth()->user()->company_id, 403);
    }

    protected function normalizeStatus($value): int
    {
        // Accept 'active'/'inactive', 1/0, '1'/'0', 'true'/'false'
        if (is_string($value)) {
            $v = strtolower(trim($value));
            if (in_array($v, ['active', '1', 'true', 'yes'], true)) return 1;
            if (in_array($v, ['inactive', '0', 'false', 'no'], true)) return 0;
        }
        return (int)(bool)$value;
    }

    protected function assertNotSelf(User $user): void
    {
        abort_if($user->id === auth()->id(), 422, 'You cannot modify or delete your own account in this way.');
    }

    protected function assertNotLastAdminOnChange(User $user, array $newData): void
    {
        $becomingNonAdmin = array_key_exists('role', $newData) && $newData['role'] !== 'admin';
        $deactivating     = array_key_exists('status', $newData) && (int)$newData['status'] !== 1;

        if (($becomingNonAdmin || $deactivating) && $user->role === 'admin' && (int)$user->status === 1) {
            $count = User::where('company_id', $user->company_id)
                ->where('role', 'admin')
                ->where('status', 1)
                ->where('id', '!=', $user->id)
                ->count();

            abort_if($count === 0, 422, 'At least one active admin is required.');
        }
    }
}
