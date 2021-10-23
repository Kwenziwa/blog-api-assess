<?php

namespace App\Models;

use App\Models\Like;
use App\Models\User;
use App\Models\Image;
use App\Models\Comment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Post extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'title', 'body'];
    public $timestamps = true;

    protected $appends = ['author','votes'];

    protected $hidden = [
        'updated_at',
        'user_id'
    ];


    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function comments()
    {
        return $this->hasMany(Comment::class , 'post_id');

    }

    public function images()
    {
        return $this->hasMany(Image::class, 'post_id');
    }

    public function likes(){
        return $this->hasMany(Like::class,'post_id');
    }

    public function getAuthorAttribute()
    {
        $user = User::find($this->user_id, ['first_name', 'last_name']);
        return $user->first_name .' '. $user->last_name;
    }

    public function getVotesAttribute()
    {
        return [
            'upvoted' => Like::where('like','=',1)->where('post_id',$this->id)->count(),
            'downvoted' => Like::where('like','=',0)->where('post_id',$this->id)->count()
        ];
    }





}
