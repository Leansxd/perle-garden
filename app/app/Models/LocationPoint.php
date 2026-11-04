<?php

namespace App\Models;

use App\Models\Concerns\Translatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** Konum bölümündeki "yer — mesafe" satırları. Çevrilebilir: name */
class LocationPoint extends Model
{
    use Translatable;

    protected $guarded = ['id'];

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }
}
