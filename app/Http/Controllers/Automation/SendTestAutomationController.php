<?php

namespace App\Http\Controllers\Automation;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SendTestAutomationController extends Controller
{
    // If your route uses invokable syntax
    public function __invoke(Request $request)
    {
        return $this->handle($request);
    }

    // If your route specifies a method
    public function handle(Request $request)
    {
        return response()->json([
            'ok' => true,
            'message' => 'SendTestAutomationController stub responding.',
        ], Response::HTTP_OK);
    }
}
