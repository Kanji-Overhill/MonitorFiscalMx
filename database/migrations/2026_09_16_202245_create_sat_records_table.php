<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sat_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dataset_id')->constrained('sat_datasets')->cascadeOnDelete();
            $table->string('rfc_normalized', 13);
            // Nombres de razón social del SAT pueden superar 255 caracteres
            // (denominaciones largas, texto combinado); se usa TEXT en vez
            // de adivinar un límite arbitrario.
            $table->text('business_name')->nullable();
            $table->string('classification');
            $table->text('official_document')->nullable();
            $table->date('publication_date')->nullable();
            $table->json('raw_data')->nullable();
            $table->timestamps();

            $table->index('rfc_normalized');
            $table->index('classification');
            $table->index(['dataset_id', 'rfc_normalized']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sat_records');
    }
};
