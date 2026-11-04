<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('construction_phases', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->unsignedTinyInteger('percent')->default(0);   // 0–100
            $table->string('note')->nullable();                   // "Tamamlandı · Mart 2026"
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('construction_phases');
    }
};
