<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Diary extends Model
{
    protected $fillable = ['entry_date', 'mood', 'text'];

    protected function casts(): array
    {
        return [
            'entry_date' => 'date',
        ];
    }
}
