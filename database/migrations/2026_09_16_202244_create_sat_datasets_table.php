<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sat_datasets', function (Blueprint $table) {
            $table->id();
            $table->string('type')->default('69b');
            $table->string('source_url');
            $table->string('source_filename')->nullable();
            $table->date('source_updated_at')->nullable();
            $table->timestamp('downloaded_at')->nullable();
            $table->string('checksum')->nullable();
            $table->string('status')->default('pending');
            $table->unsignedInteger('record_count')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['type', 'status']);
            $table->index('checksum');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sat_datasets');
    }
};
