<?php

namespace App\Models;

use App\Models\Concerns\Translatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Daire tipi (1+1, 2+1 Dubleks ...).
 * Çevrilebilir: label, description, rooms
 * Medya: photos / renders / plans (unit_media), model_3d (.glb), tour_url (360°)
 */
class UnitType extends Model
{
    use Translatable;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['rooms' => 'array', 'unit_count' => 'integer', 'is_published' => 'boolean'];
    }

    protected static function booted(): void
    {
        // Slug boşsa ad + etiketten üret: "2+1 Dubleks" → "2-1-dubleks"
        static::saving(function (UnitType $unit) {
            if (blank($unit->slug)) {
                $base = Str::slug(str_replace('+', '-', trim($unit->name.' '.$unit->label))) ?: 'daire';
                $slug = $base;
                $i = 2;
                while (static::where('slug', $slug)->whereKeyNot($unit->id)->exists()) {
                    $slug = $base.'-'.$i++;
                }
                $unit->slug = $slug;
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function media(): HasMany
    {
        return $this->hasMany(UnitMedia::class)->ordered();
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    public function title(): string
    {
        return trim($this->name.' '.($this->t('label') ?? ''));
    }

    /** Ana sayfa kartı için veri. */
    public function toCard(): array
    {
        return $this->localized(['label', 'description', 'rooms'], ['id', 'slug', 'name', 'unit_count', 'sizes'])
            + [
                'plan_image' => media_url($this->plan_image ?? $this->media->firstWhere('kind', 'plan')?->path),
                'cover_image' => media_url($this->cover_image ?? $this->media->firstWhere('kind', 'render')?->path ?? $this->media->firstWhere('kind', 'photo')?->path),
            ];
    }

    /** Detay sayfası için tam veri. */
    public function toDetail(): array
    {
        $group = fn (string $kind) => $this->media->where('kind', $kind)->values()->map(fn (UnitMedia $m) => [
            'id' => $m->id,
            'url' => $m->url(),
            'caption' => $m->t('caption'),
        ]);

        return $this->toCard() + [
            'photos' => $group('photo'),
            'renders' => $group('render'),
            'plans' => $group('plan'),
            'model_3d' => media_url($this->model_3d),
            'tour_url' => $this->tour_url,
            'video_url' => $this->video_url,
        ];
    }
}
