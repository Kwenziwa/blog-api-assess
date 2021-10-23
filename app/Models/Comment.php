<?php

namespace App\Models;

use App\Models\Post;
use App\Models\User;
use App\Models\CommentLike;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Comment extends Model
{
    use HasFactory;

    public $timestamps = true;
    protected $appends = ['author', 'votes'];

    protected $fillable = [
        'user_id',
        'post_id',
        'body'
    ];

    protected $hidden = [
        'updated_at',
        'user_id',
    ];
    public function user()
    {
        $this->belongsTo(User::class,'user_id');
    }

   /**
     * Get the post that owns the comment.
     */
    public function post()
    {
        return $this->belongsTo(Post::class, 'post_id');
    }

    public function getAuthorAttribute()
    {
        $user = User::find($this->user_id, ['first_name', 'last_name']);
        return $user->first_name .' '. $user->last_name;
    }



    public function commentlikes(){
        return $this->hasMany(CommentLike::class,'post_id');
    }

    public function getVotesAttribute()
    {
        return [
            'upvoted' => CommentLike::where('like','=',1)->where('comment_id',$this->id)->count(),
            'downvoted' => CommentLike::where('like','=',0)->where('comment_id',$this->id)->count()
        ];
    }
}
