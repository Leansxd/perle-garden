<?php

namespace App\Models\Concerns;

/**
 * Asıl sütunlar varsayılan dilde (tr) tutulur; diğer diller `translations`
 * JSON sütunundan okunur. Çeviri yoksa varsayılan değere düşer.
 *
 *   $model->t('title')            // aktif dil
 *   $model->localized(['title'])  // ['title' => ...] dizisi
 */
trait Translatable
{
    public function initializeTranslatable(): void
    {
        $this->mergeCasts(['translations' => 'array']);
    }

    public function t(string $field, ?string $locale = null): mixed
    {
        $locale ??= app()->getLocale();
        if ($locale !== config('site.default_locale', 'tr')) {
            $value = $this->translations[$locale][$field] ?? null;
            if ($value !== null && $value !== '') {
                return $value;
            }
        }

        return $this->getAttribute($field);
    }

    /** @param string[] $fields */
    public function localized(array $fields, array $plain = []): array
    {
        $out = [];
        foreach ($plain as $f) {
            $out[$f] = $this->getAttribute($f);
        }
        foreach ($fields as $f) {
            $out[$f] = $this->t($f);
        }

        return $out;
    }
}
