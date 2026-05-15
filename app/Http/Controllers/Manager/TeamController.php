<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class TeamController extends Controller
{
    protected function companyId(): int
    {
        $companyId = (int) (auth()->user()?->company_id ?? 0);

        abort_if(! $companyId, 403);

        return $companyId;
    }

    public function index(Request $request)
    {
        $companyId = $this->companyId();

        $q = trim((string) $request->get('q', ''));
        $role = trim((string) $request->get('role', ''));

        $team = User::query()
            ->where('company_id', $companyId)
            ->when(Schema::hasColumn('users', 'is_active'), function ($query) {
                $query->where('is_active', 1);
            })
            ->when(Schema::hasColumn('users', 'status'), function ($query) {
                $query->whereIn('status', ['active', 'Active', 1]);
            })
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    foreach ([
                        'name',
                        'email',
                        'phone',
                        'mobile',
                        'phone_number',
                        'designation',
                        'department',
                        'role',
                    ] as $column) {
                        if (Schema::hasColumn('users', $column)) {
                            $sub->orWhere($column, 'like', '%' . $q . '%');
                        }
                    }
                });
            })
            ->when($role !== '', function ($query) use ($role) {
                if (Schema::hasColumn('users', 'role')) {
                    $query->where('role', $role);
                } elseif (Schema::hasColumn('users', 'role_name')) {
                    $query->where('role_name', $role);
                }
            })
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $roles = $this->roles($companyId);

        return view('manager.team.index', compact('team', 'roles', 'q', 'role'));
    }

    public function show(User $user)
    {
        $this->authorizeUser($user);

        return view('manager.team.show', [
            'member' => $user,
        ]);
    }

    protected function authorizeUser(User $user): void
    {
        abort_if((int) $user->company_id !== $this->companyId(), 403);
    }

    protected function roles(int $companyId)
    {
        if (Schema::hasColumn('users', 'role')) {
            return User::query()
                ->where('company_id', $companyId)
                ->whereNotNull('role')
                ->select('role')
                ->distinct()
                ->orderBy('role')
                ->pluck('role');
        }

        if (Schema::hasColumn('users', 'role_name')) {
            return User::query()
                ->where('company_id', $companyId)
                ->whereNotNull('role_name')
                ->select('role_name')
                ->distinct()
                ->orderBy('role_name')
                ->pluck('role_name');
        }

        return collect();
    }
}