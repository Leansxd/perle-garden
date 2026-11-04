<?php

namespace App\Models;

use App\Models\Concerns\Translatable;
use Illuminate\Database\Eloquent\Model;

/** Hero altındaki rakamlar. Çevrilebilir: label */
class Stat extends Model
{
    use Translatable;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['value' => 'integer'];
    }
}
