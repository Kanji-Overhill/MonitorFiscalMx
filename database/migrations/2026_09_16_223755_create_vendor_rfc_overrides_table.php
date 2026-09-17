<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // El SDK del widget no expone el RFC del proveedor (confirmado en
        // Developer Mode contra una cuenta real: ni ZFAPPS.get('contact') ni
        // la API de Zoho Books vía Connection lo devuelven de forma
        // utilizable desde el widget). El usuario lo captura una vez desde
        // el widget y el backend lo recuerda por proveedor.
        Schema::create('vendor_rfc_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->string('zoho_vendor_id');
            $table->string('rfc_normalized', 13);
            $table->timestamps();

            $table->unique(['organization_id', 'zoho_vendor_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_rfc_overrides');
    }
};
