<?php

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

if (! function_exists('media_url')) {
    /**
     * Medya yolunu herkese açık URL'ye çevirir.
     *  - "/media/..." ya da "http..." → olduğu gibi (public/ altındaki sabit dosyalar)
     *  - "uploads/..."               → admin panelinden yüklenen dosya (storage/app/public)
     */
    function media_url(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }
        if (Str::startsWith($path, ['/', 'http://', 'https://'])) {
            return $path;
        }

        return Storage::disk('public')->url($path);
    }
}
