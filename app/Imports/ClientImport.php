<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use App\Models\Client\Client;
use Illuminate\Support\Facades\Log;

class ClientImport implements ToCollection, WithHeadingRow
{
    protected $companyId;
    public $imported = 0;
    public $skipped = 0;
    public $total = 0;

    public function __construct($companyId)
    {
        $this->companyId = $companyId;
    }

    public function collection(Collection $rows)
    {
        $this->total = $rows->count();

        foreach ($rows as $index => $row) {
            $data = $row->toArray();

            // 🛠️ Cast numeric fields to string
            foreach (['phone', 'whatsapp', 'postal_code'] as $field) {
                if (isset($data[$field]) && is_numeric($data[$field])) {
                    $data[$field] = (string) $data[$field];
                }
            }

            Log::info("🔍 Processing Row {$index}", $data);

            // ✅ Duplicate check
            if (!empty($data['email']) && Client::where('email', $data['email'])->where('company_id', $this->companyId)->exists()) {
                $this->skipped++;
                Log::warning("⏭️ Skipping duplicate email: {$data['email']}");
                continue;
            }

            // ✅ Validate row
            $validated = Validator::make($data, [
                'name'              => 'required|string|max:255',
                'email'             => 'nullable|email|max:255',
                'phone'             => 'nullable|string|max:20',
                'whatsapp'          => 'nullable|string|max:20',
                'dob'               => 'nullable|date',
                'gender'            => 'nullable|in:male,female,other',
                'address'           => 'nullable|string|max:255',
                'city'              => 'nullable|string|max:100',
                'state'             => 'nullable|string|max:100',
                'postal_code'       => 'nullable|string|max:20',
                'country'           => 'nullable|string|max:100',
                'source'            => 'nullable|string|max:255',
                'status'            => 'nullable|string|max:100',
                'notes'             => 'nullable|string|max:1000',
                'is_vip'            => 'nullable|boolean',
                'preferred_channel' => 'nullable|string|max:50',
            ]);

            if ($validated->fails()) {
                $this->skipped++;
                Log::error("❌ Error on Row {$index}: " . json_encode($validated->errors()->all()));
                continue;
            }

            // ✅ Create client
            Client::create(array_merge(
                $validated->validated(),
                ['company_id' => $this->companyId]
            ));

            $this->imported++;
            Log::info("✅ Row {$index} imported successfully");
        }

        Log::info("🎉 Client import complete: {$this->imported} imported, {$this->skipped} skipped, {$this->total} total");
    }
}
