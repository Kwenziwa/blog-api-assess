<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommentLike extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'comment_id',
        'like'
    ];

    protected $hidden = [
        'updated_at',
        'updated_at'
    ];

    public function post()
    {
        return $this->belongsTo(Comment::class, 'comment_id');
    }
}
