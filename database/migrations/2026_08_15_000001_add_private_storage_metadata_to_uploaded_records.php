<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var array<string, string> */
    private array $tables = [
        'files' => 'file_path',
        'client_documents' => 'document_path',
        'job_documents' => 'path',
        'job_cards' => 'file_path',
        'invoices' => 'file_path',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table => $after) {
            if (Schema::hasTable($table) && ! Schema::hasColumn($table, 'storage_disk')) {
                Schema::table($table, function (Blueprint $blueprint) use ($after): void {
                    $blueprint->string('storage_disk', 64)->nullable()->after($after);
                });
            }
        }
    }

    public function down(): void
    {
        foreach (array_keys($this->tables) as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'storage_disk')) {
                Schema::table($table, function (Blueprint $blueprint): void {
                    $blueprint->dropColumn('storage_disk');
                });
            }
        }
    }
};
