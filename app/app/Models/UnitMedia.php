<?php

namespace App\Models;

use App\Models\Concerns\Translatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Daire tipi medyası. kind: photo (fotoğraf) | render (3D görsel) | plan (kat planı). Çevrilebilir: caption */
class UnitMedia extends Model
{
    use Translatable;

    public const KINDS = ['photo', 'render', 'plan'];

    protected $table = 'unit_media';

    protected $guarded = ['id'];

    public function unitType(): BelongsTo
    {
        return $this->belongsTo(UnitType::class);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    /** Herkese açık URL (storage/ ya da public/media/ yolları). */
    public function url(): string
    {
        return media_url($this->path);
    }
}
