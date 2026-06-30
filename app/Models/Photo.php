<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Photo extends Model
{
    protected $fillable = ['filename', 'caption', 'taken_at'];

    protected function casts(): array
    {
        return [
            'taken_at' => 'date',
        ];
    }

    public function url(): string
    {
        return asset('storage/uploads/' . $this->filename);
    }
}
