<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('postal_codes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('country_id')->constrained()->restrictOnDelete();
            $table->foreignId('admin_area_id')->constrained()->restrictOnDelete();
            $table->string('postal_code', 20)->index();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->timestampsTz();

            // Prevents duplicate rows for the same area + postal code combination.
            // The (country_id, postal_code) prefix also makes "find by code" lookups index-friendly.
            $table->unique(
                ['country_id', 'admin_area_id', 'postal_code'],
                'postal_codes_country_area_code_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('postal_codes');
    }
};
