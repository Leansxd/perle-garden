<?php

namespace App\Http\Controllers\Admin\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Admin denetleyicileri için ortak yardımcılar:
 *  - Çok dilli alanlar: formda {tr, en, ru} nesnesi olarak gelir/gider
 *  - Dosya yükleme: storage/app/public/uploads/... (public/storage ile yayınlanır)
 */
trait ManagesContent
{
    protected array $extraLocales = ['en', 'ru'];

    /** Modeli forma uygun hale getirir: çevrilebilir alanlar {tr,en,ru} olur. */
    protected function toForm(Model $model, array $translatable, array $plain = []): array
    {
        $out = ['id' => $model->getKey()];
        foreach ($plain as $f) {
            $out[$f] = $model->getAttribute($f);
        }
        foreach ($translatable as $f) {
            $out[$f] = ['tr' => $model->getAttribute($f)];
            foreach ($this->extraLocales as $l) {
                $out[$f][$l] = $model->translations[$l][$f] ?? null;
            }
        }

        return $out;
    }

    /**
     * Formdan gelen {tr,en,ru} alanları; asıl sütun (tr) + translations JSON'a ayırır.
     * Liste alanları (ör. rooms) [{tr,en,ru}, ...] biçiminde gelir.
     */
    protected function fromForm(array $data, array $translatable, array $listFields = [], ?array $existing = null): array
    {
        $translations = $existing ?? [];
        foreach ($translatable as $f) {
            if (! array_key_exists($f, $data)) {
                continue;
            }
            $value = $data[$f];
            if (in_array($f, $listFields, true)) {
                $items = collect($value ?? [])->filter(fn ($i) => filled($i['tr'] ?? null));
                $data[$f] = $items->pluck('tr')->values()->all();
                foreach ($this->extraLocales as $l) {
                    $translations[$l][$f] = $items->map(fn ($i) => filled($i[$l] ?? null) ? $i[$l] : $i['tr'])->values()->all();
                }
            } else {
                $data[$f] = $value['tr'] ?? null;
                foreach ($this->extraLocales as $l) {
                    $translations[$l][$f] = filled($value[$l] ?? null) ? $value[$l] : null;
                }
            }
        }
        $data['translations'] = $translations;

        return $data;
    }

    /** Çevrilebilir alan için doğrulama kuralları üretir. */
    protected function transRules(string $field, bool $required = false, int $max = 500): array
    {
        return [
            "$field" => ['array'],
            "$field.tr" => [$required ? 'required' : 'nullable', 'string', "max:$max"],
            "$field.en" => ['nullable', 'string', "max:$max"],
            "$field.ru" => ['nullable', 'string', "max:$max"],
        ];
    }

    /** Dosyayı public diske kaydeder, benzersiz adla. */
    protected function storeUpload(UploadedFile $file, string $dir): string
    {
        $ext = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'bin');
        $name = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) ?: 'dosya';
        $name = Str::limit($name, 40, '').'-'.Str::lower(Str::random(6)).'.'.$ext;

        return $file->storeAs("uploads/$dir", $name, 'public');
    }

    /** Sadece admin panelinden yüklenmiş dosyaları siler (public/media'daki sabit dosyalara dokunmaz). */
    protected function deleteUpload(?string $path): void
    {
        if ($path && Str::startsWith($path, 'uploads/')) {
            Storage::disk('public')->delete($path);
        }
    }
}
