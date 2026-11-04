<?php

namespace App\Models;

use App\Models\Concerns\Translatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** İnşaat ilerleme aşaması. Çevrilebilir: title, note */
class ConstructionPhase extends Model
{
    use Translatable;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['percent' => 'integer'];
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }
}
