<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->string('zoho_organization_id')->unique();
            $table->string('name')->nullable();
            $table->string('status')->default('active');
            $table->string('api_token')->nullable()->comment('Hashed bearer token used by the widget to authenticate requests for this organization.');
            $table->json('settings')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organizations');
    }
};
