<?php

namespace App\Models;

use App\Models\Post;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Like extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'post_id',
        'like'
    ];

    protected $hidden = [
        'updated_at',
        'updated_at'
    ];

    public function post()
    {
        return $this->belongsTo(Post::class, 'post_id');
    }
}
