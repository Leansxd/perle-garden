<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactMessage extends Model
{
    protected $fillable = ['name', 'email', 'phone', 'unit_type', 'message', 'ip', 'locale', 'read_at'];

    protected function casts(): array
    {
        return ['read_at' => 'datetime'];
    }
}
