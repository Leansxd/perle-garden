<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Daire tiplerine ait medya: fotoğraflar, 3D render'lar, kat planları.
 * Daire tipine ayrıca slug (sayfa adresi), kapak, 3D model (.glb) ve
 * 360° sanal tur bağlantısı eklenir.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('unit_types', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique()->after('name');
            $table->string('cover_image')->nullable()->after('plan_image');
            $table->string('model_3d')->nullable()->after('cover_image');      // .glb dosya yolu
            $table->string('tour_url')->nullable()->after('model_3d');         // 360° tur (Matterport, Kuula vb.)
            $table->string('video_url')->nullable()->after('tour_url');        // YouTube / mp4
            $table->boolean('is_published')->default(true)->after('video_url');
        });

        Schema::create('unit_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_type_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 16)->default('photo'); // photo | render | plan
            $table->string('path');
            $table->string('caption')->nullable();
            $table->json('translations')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['unit_type_id', 'kind', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unit_media');
        Schema::table('unit_types', function (Blueprint $table) {
            $table->dropColumn(['slug', 'cover_image', 'model_3d', 'tour_url', 'video_url', 'is_published']);
        });
    }
};
