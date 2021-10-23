<?php

namespace App\Transformers;

use App\Models\Post;
use App\Models\User;
use League\Fractal\TransformerAbstract;

class UserTransformer extends TransformerAbstract
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
    public function transform(User $user)
    {
        return [
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'username' => $user->username,
            'email' => $user->email,
            'is_verified' => $user->is_verified,
            'image' => $user->image,
            'registered_at' => $user->created_at->diffForHumans(),
            'posts'=> ['total_posts' => $user->posts->count()],
        ];
    }
}
