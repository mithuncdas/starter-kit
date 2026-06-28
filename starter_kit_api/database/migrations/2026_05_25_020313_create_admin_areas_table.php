<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_areas', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('country_id')->constrained()->restrictOnDelete();
            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('admin_areas')
                ->restrictOnDelete();
            $table->unsignedSmallInteger('admin_level_id');
            $table->foreign('admin_level_id')
                ->references('id')
                ->on('admin_levels')
                ->restrictOnDelete();
            $table->unsignedSmallInteger('depth');
            $table->string('code', 64);
            $table->string('name', 128);
            $table->string('short_name', 128)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('timezone', 64)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();

            $table->index(['country_id', 'depth']);
            $table->index('parent_id');
            $table->unique(
                ['country_id', 'admin_level_id', 'code'],
                'admin_areas_country_level_code_unique',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_areas');
    }
};
