<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MeResource;
use Illuminate\Http\Request;

final class MeController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user()->loadMissing(['company', 'garage']);

        return response()
            ->json([
                'ok'   => true,
                'user' => (new MeResource($user))->resolve(),
            ])
            ->header('Cache-Control', 'no-store');
    }
}
