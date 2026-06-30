<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Anniversary extends Model
{
    protected $fillable = ['title', 'date', 'note'];

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }
}
