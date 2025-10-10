<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 👇 Skip migration if the table already exists
        if (Schema::hasTable('job_documents')) {
            return;
        }

        Schema::create('job_documents', function (Blueprint $table) {
            $table->id();
            // Add columns here if you have them in your schema
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_documents');
    }
};
