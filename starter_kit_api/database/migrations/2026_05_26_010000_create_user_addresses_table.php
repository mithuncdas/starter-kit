<?php

use App\Enums\UserAddressLabelEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_addresses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('admin_area_id')->constrained()->restrictOnDelete();
            $table->tinyInteger('label')->default(UserAddressLabelEnum::Home->value);
            $table->boolean('is_primary')->default(false);
            $table->string('address_line1')->nullable();
            $table->string('address_line2')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->text('notes')->nullable();
            $table->timestampsTz();

            $table->index(['user_id', 'is_primary']);
            $table->index('admin_area_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_addresses');
    }
};
