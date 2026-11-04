<?php

namespace App\Models;

use App\Models\Concerns\Translatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** Proje tanıtımındaki öne çıkan özellikler. Çevrilebilir: title, description */
class Highlight extends Model
{
    use Translatable;

    protected $guarded = ['id'];

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }
}
