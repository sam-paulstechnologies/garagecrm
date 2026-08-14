<?php

namespace App\Http\Controllers\Admin;

use App\Commercial\ResourceLimitService;
use App\Http\Controllers\Controller;
use App\Models\Garage\Garage;
use App\Models\System\Company;
use App\Models\User;
use App\Security\SecurityAudit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password as PasswordRule;

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

        return view('admin.users.create', [
            'companies' => Company::where('id', $companyId)->get(),
            'garages' => Garage::where('company_id', $companyId)->get(),
            'roles' => User::tenantRoles(),
        ]);
    }

    public function store(Request $request, ResourceLimitService $limits)
    {
        $companyId = auth()->user()->company_id;
        $company = Company::query()->findOrFail($companyId);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|max:20',
            'role' => ['required', Rule::in(User::tenantRoles())],
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
            'garage_id' => 'nullable|exists:garages,id',
            'status' => 'required|boolean',
        ]);

        $limits->assertCanCreateUser($company);

        $data['company_id'] = $companyId;

        if (! empty($data['garage_id'])) {
            $garageCompany = Garage::whereKey($data['garage_id'])->value('company_id');
            abort_if($garageCompany != $companyId, 422, 'Invalid garage selection');
        }

        User::create($data);

        return redirect()->route('admin.users.index')
            ->with('success', 'User created successfully.');
    }

    public function edit(User $user)
    {
        $this->authorizeCompany($user);

        return view('admin.users.edit', [
            'user' => $user,
            'companies' => Company::where('id', $user->company_id)->get(),
            'garages' => Garage::where('company_id', $user->company_id)->get(),
            'roles' => User::tenantRoles(),
        ]);
    }

    public function update(Request $request, User $user)
    {
        $this->authorizeCompany($user);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,'.$user->id,
            'phone' => 'nullable|string|max:20',
            'role' => ['required', Rule::in(User::tenantRoles())],
            'password' => ['nullable', 'confirmed', PasswordRule::defaults()],
            'garage_id' => 'nullable|exists:garages,id',
            'status' => 'required|boolean',
        ]);

        if (! empty($data['garage_id'])) {
            $garageCompany = Garage::whereKey($data['garage_id'])->value('company_id');
            abort_if($garageCompany != $user->company_id, 422, 'Invalid garage selection');
        }

        $this->assertNotLastAdmin($user, $data);

        if (empty($data['password'])) {
            unset($data['password']);
        }

        $user->update($data);

        return redirect()->route('admin.users.index')
            ->with('success', 'User updated successfully.');
    }

    public function destroy(User $user)
    {
        $this->authorizeCompany($user);
        $this->assertNotSelf($user);
        $this->assertNotLastAdmin($user, ['status' => 0]);

        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'User deleted successfully.');
    }

    public function toggleStatus(User $user)
    {
        $this->authorizeCompany($user);
        $this->assertNotSelf($user);

        $newStatus = ! $user->status;
        $this->assertNotLastAdmin($user, ['status' => $newStatus]);

        $user->update(['status' => $newStatus]);

        return back()->with('success', 'User status updated.');
    }

    public function resetPassword(User $user, SecurityAudit $audit)
    {
        $this->authorizeCompany($user);

        if (app()->environment('staging') && config('mail.default') === 'log') {
            $audit->record('user.password_reset_blocked_log_mail', auth()->user(), $user);

            return back()->with('warning', 'No reset token was generated because staging mail is log-only. Use an approved secure recovery channel.');
        }

        $status = Password::sendResetLink(['email' => $user->email]);
        $audit->record('user.password_reset_requested', auth()->user(), $user, [
            'delivery_accepted' => $status === Password::RESET_LINK_SENT,
        ]);

        return back()->with(
            $status === Password::RESET_LINK_SENT ? 'success' : 'warning',
            $status === Password::RESET_LINK_SENT
                ? 'A secure password-reset link was requested for the user.'
                : 'The password-reset request could not be delivered. No password was changed.'
        );
    }

    /* ---------------- HELPERS ---------------- */

    protected function authorizeCompany(User $user)
    {
        abort_if($user->company_id !== auth()->user()->company_id, 403);
    }

    protected function assertNotSelf(User $user)
    {
        abort_if($user->id === auth()->id(), 422, 'You cannot modify your own account.');
    }

    protected function assertNotLastAdmin(User $user, array $newData)
    {
        $demoting = isset($newData['role']) && $newData['role'] !== 'admin';
        $deactivating = isset($newData['status']) && ! $newData['status'];

        if (($demoting || $deactivating) && $user->role === 'admin' && $user->status) {
            $count = User::where('company_id', $user->company_id)
                ->where('role', 'admin')
                ->where('status', 1)
                ->where('id', '!=', $user->id)
                ->count();

            abort_if($count === 0, 422, 'At least one active admin is required.');
        }
    }
}
