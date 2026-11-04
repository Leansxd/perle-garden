<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unit_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');                 // 1+1, 2+1 Dubleks ...
            $table->string('label')->nullable();    // "Bahçeli", "Çatı katı" gibi kısa etiket
            $table->unsignedSmallInteger('unit_count');
            $table->string('sizes');                // "58 / 63 / 67 m²"
            $table->string('description', 500)->nullable();
            $table->json('rooms')->nullable();      // ["Salon + mutfak 32 m²", ...]
            $table->string('plan_image')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unit_types');
    }
};
