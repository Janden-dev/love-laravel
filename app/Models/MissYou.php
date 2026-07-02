<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MissYou extends Model
{
    protected $table = 'miss_you';

    protected $fillable = ['user_id', 'date', 'count'];

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
