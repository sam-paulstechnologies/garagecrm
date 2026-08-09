<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PlatformUserController extends SuperAdminController
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', Rule::in(User::platformRoles())],
            'password' => ['required', 'string', 'min:12'],
            'status' => ['required', 'boolean'],
        ]);

        $user = User::query()->create([
            ...$data,
            'company_id' => null,
            'garage_id' => null,
            'must_change_password' => true,
        ]);

        return response()->json(['id' => $user->id, 'role' => $user->role], 201);
    }

    public function update(Request $request, User $platformUser): JsonResponse
    {
        abort_unless($platformUser->company_id === null && $platformUser->isPlatformUser(), 404);

        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($platformUser->id)],
            'role' => ['sometimes', 'required', Rule::in(User::platformRoles())],
            'password' => ['sometimes', 'required', 'string', 'min:12'],
            'status' => ['sometimes', 'required', 'boolean'],
        ]);

        $platformUser->update($data);

        return response()->json(['id' => $platformUser->id, 'role' => $platformUser->role]);
    }
}
