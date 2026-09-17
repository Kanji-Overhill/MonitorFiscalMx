<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->string('zoho_vendor_id')->nullable();
            $table->string('rfc_normalized', 13);
            $table->string('result_status');
            $table->boolean('matched')->default(false);
            $table->json('result_snapshot');
            $table->timestamp('checked_at');
            $table->timestamps();

            $table->index(['organization_id', 'zoho_vendor_id']);
            $table->index(['organization_id', 'rfc_normalized']);
            $table->index('checked_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_checks');
    }
};
