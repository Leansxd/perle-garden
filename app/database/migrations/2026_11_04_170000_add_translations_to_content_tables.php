<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Çok dilli içerik: asıl sütunlar Türkçe kalır, diğer diller
 * `translations` JSON sütununda tutulur:
 *   {"en": {"title": "..."}, "ru": {"title": "..."}}
 */
return new class extends Migration
{
    private array $tables = [
        'stats', 'highlights', 'unit_types', 'gallery_images',
        'amenities', 'construction_phases', 'location_points',
    ];

    public function up(): void
    {
        foreach ($this->tables as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->json('translations')->nullable();
            });
        }
        Schema::table('contact_messages', function (Blueprint $table) {
            $table->string('locale', 5)->default('tr');
        });
    }

    public function down(): void
    {
        foreach ($this->tables as $name) {
            Schema::table($name, fn (Blueprint $table) => $table->dropColumn('translations'));
        }
        Schema::table('contact_messages', fn (Blueprint $table) => $table->dropColumn('locale'));
    }
};
