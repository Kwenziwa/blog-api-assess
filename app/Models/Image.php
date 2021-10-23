<?php

namespace App\Models;

use App\Models\Post;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Image extends Model
{
    use HasFactory;

    protected $fillable = ['post_id','path'];
    public $timestamps = true;

    protected $hidden = [
        'updated_at',
        'created_at'
    ];

    public function post()
    {
        return $this->belongsTo(Post::class, 'post_id');
    }

}
