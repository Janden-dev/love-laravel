<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    protected $fillable = ['from_user_id', 'to_user_id', 'question', 'answer', 'answered_at'];

    protected function casts(): array
    {
        return [
            'answered_at' => 'datetime',
        ];
    }

    public function fromUser()
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    public function toUser()
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('from_user_id', $userId)->orWhere('to_user_id', $userId);
    }

    public function scopeAskedTo($query, $userId)
    {
        return $query->where('to_user_id', $userId);
    }

    public function scopeAskedBy($query, $userId)
    {
        return $query->where('from_user_id', $userId);
    }
}
