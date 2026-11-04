<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Anahtar/değer site ayarları (proje adı, hero metinleri, iletişim, WhatsApp vb.).
 *
 * Çok dilli değerler için anahtarın sonuna "@dil" eklenir:
 *   hero_title      → Türkçe (varsayılan)
 *   hero_title@en   → İngilizce
 *   hero_title@ru   → Rusça
 * Çevirisi olmayan anahtarlar varsayılan değere düşer.
 */
class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    /** @return array<string, string|null> */
    public static function map(?string $locale = null): array
    {
        $locale ??= app()->getLocale();

        return Cache::rememberForever("settings.map.$locale", function () use ($locale) {
            $all = static::query()->pluck('value', 'key')->all();
            $out = [];
            foreach ($all as $key => $value) {
                if (! str_contains($key, '@')) {
                    $out[$key] ??= $value;
                    continue;
                }
                [$base, $lang] = explode('@', $key, 2);
                if ($lang === $locale && $value !== null && $value !== '') {
                    $out[$base] = $value;
                }
            }
            // Dile özel değer, temel değerden önce gelmiş olabilir — tekrar uygula
            foreach ($all as $key => $value) {
                if (str_ends_with($key, "@$locale") && $value !== null && $value !== '') {
                    $out[substr($key, 0, -strlen("@$locale"))] = $value;
                }
            }

            return $out;
        });
    }

    public static function flush(): void
    {
        foreach (array_keys(config('site.locales')) as $l) {
            Cache::forget("settings.map.$l");
        }
    }

    protected static function booted(): void
    {
        static::saved(fn () => static::flush());
        static::deleted(fn () => static::flush());
    }
}
