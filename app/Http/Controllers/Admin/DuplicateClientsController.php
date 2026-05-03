<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\Request;

class DuplicateClientsController extends Controller
{
    /**
     * Show potential duplicate clients
     * Duplicates are detected by:
     * - Same phone number
     * - Same email
     */
    public function index(Request $request)
    {
        $companyId = auth()->user()->company_id;

        // 🔁 Find duplicate phone numbers
        $duplicatePhones = Client::select('phone')
            ->where('company_id', $companyId)
            ->whereNotNull('phone')
            ->groupBy('phone')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('phone');

        // 🔁 Find duplicate emails
        $duplicateEmails = Client::select('email')
            ->where('company_id', $companyId)
            ->whereNotNull('email')
            ->groupBy('email')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('email');

        // 📦 Fetch full client records
        $clients = Client::where('company_id', $companyId)
            ->where(function ($q) use ($duplicatePhones, $duplicateEmails) {
                if ($duplicatePhones->isNotEmpty()) {
                    $q->whereIn('phone', $duplicatePhones);
                }

                if ($duplicateEmails->isNotEmpty()) {
                    $q->orWhereIn('email', $duplicateEmails);
                }
            })
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy(function ($client) {
                return $client->phone ?: $client->email;
            });

        return view('admin.clients.duplicates', [
            'groups' => $clients
        ]);
    }
}
