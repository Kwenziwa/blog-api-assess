<?php

namespace App\Transformers;

use App\Models\Like;
use App\Models\Post;
use League\Fractal\TransformerAbstract;

class PostTransformer extends TransformerAbstract
{
    /**
     * List of resources to automatically include
     *
     * @var array
     */
    protected $defaultIncludes = [
        //
    ];

    /**
     * List of resources possible to include
     *
     * @var array
     */
    protected $availableIncludes = [
        //
    ];

    /**
     * A Fractal transformer.
     *
     * @return array
     */
    public function transform(Post $post)
    {
        return [
            'post_id' => $post->id,
            'created_by' => $post->user->first_name.' '. $post->user->last_name,
            'created_at' => $post->created_at->diffForHumans(),
            'title' => $post->title,
            'body' => $post->body,
            'votes' => [
                'upvoted' => Like::where('like','=',1)->where('post_id',$post->id)->count(),
                'downvoted' => Like::where('like','=',0)->where('post_id',$post->id)->count()
            ],
            'image' => $post->images,
            'comments' => $post->comments,
        ];
    }
}
