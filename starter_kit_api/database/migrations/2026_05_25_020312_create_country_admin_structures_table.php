<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('country_admin_structures', function (Blueprint $table): void {
            $table->foreignId('country_id')->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('depth');
            $table->unsignedSmallInteger('admin_level_id');

            $table->foreign('admin_level_id')
                ->references('id')
                ->on('admin_levels')
                ->restrictOnDelete();

            $table->primary(['country_id', 'depth']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('country_admin_structures');
    }
};
