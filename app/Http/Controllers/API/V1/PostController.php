<?php

namespace App\Http\Controllers\API\V1;

use JWTAuth;
use App\Models\Like;
use App\Models\Post;
use App\Models\User;
use App\Models\Image;
use App\Models\Comment;
use App\Models\CommentLike;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\File;
use App\Transformers\PostTransformer;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\CreatePostRequest;
use App\Http\Requests\PostCommentRequest;
use Illuminate\Support\Facades\Validator;

class PostController extends Controller
{

    /**
     *
     * A user should be able to create posts
     */
    public function CreatePost(CreatePostRequest $request){

        $body_request = Validator::make($request->all(), [
            'body' => 'required',
        ]);

        $image_request = Validator::make($request->all(), [
            'image' => 'required',
        ]);


        if ($body_request->fails() && $image_request->fails()) {

            return response()->json([
                'status_code' => '0',
                'status_message' => 'Failed to post, Image or Body is required.'
            ]);
        }

        $post = new Post();
        $post->user_id = JWTAuth::parseToken()->authenticate()->id;
        $post->title = $request->title;
        if(!empty($request->body)){
            $post->body = $request->body;
        }
        $post->save();

        // Moving Images
        if($request->hasfile('image'))
        {

            $this->upload_image($request, $post->id);
        }

        return response()->json([
            'status_code' => '1',
            'status_message' => 'Post successfully created'
        ]);

    }

    /**
     *
     * A user should be able to update posts they created.
     */
    public function updatePost(CreatePostRequest $request){

        $body_request = Validator::make($request->all(), [
            'body' => 'required',
        ]);

        $image_request = Validator::make($request->all(), [
            'image' => 'required|image:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $post_id_request = Validator::make($request->all(), [
            'post_id' => 'required|integer',
        ]);

        if ($body_request->fails() && $image_request->fails()) {

            return response()->json([
                'status_code' => '0',
                'status_message' => 'Failed to post, Image or Body is required.'
            ]);
        }

        if ($post_id_request->fails()) {

            return response()->json([
                'status_code' => '0',
                'status_message' => 'Failed to post, Image or Body is required.'
            ]);
        }


        $post = Post::find($request->post_id);

        if(!empty($post)){

            $post->title = $request->title;
            if(!empty($request->body)){
                $post->body = $request->body;
            }
            $post->save();

            if($request->hasfile('image'))
            {
                File::cleanDirectory('public/images/posts/'. $request->post_id);
                Image::where('post_id', $request->post_id)->delete();
                $this->upload_image($request, $post->id);
            }

            return response()->json([
                'status_code' => '1',
                'status_message' => 'Post successfully updated.'
            ]);
        }

        return response()->json([
            'status_code' => '0',
            'status_message' => 'Post was not found.'
        ]);
    }

    /**
     *
     * A user should be able to upload images for the posts they created.
     */

    public function upload_image(Request $request, $post_id){



        $user_details = JWTAuth::parseToken()->authenticate();

        $images =  [];

        if($request->hasfile('image'))
         {
            foreach($request->file('image') as $key => $file)
            {
                $extension = $file->extension();
                $filename = $post_id . '-' . date('Y-m-d-H_i_s') . '.' . $file->extension();
                $path = $file->storeAs('public/images/posts/'. $post_id,$filename  );
                $images[$key]['post_id'] = $post_id;
                $images[$key]['path'] = url($path);
            }
         }
        Image::insert($images);
    }


    /**
     *
     * Users should be able to comment on posts.
     */
    public function CommentPost(PostCommentRequest $request){

        $user = Post::find($request->post_id);

        if(!$user){

            return response()->json([
                'status_code' => '0',
                'status_message' => 'Invalid post id, Please try agian'
            ]);
        }

        $comment = new Comment();
        $comment->user_id = JWTAuth::parseToken()->authenticate()->id;
        $comment->post_id = $request->post_id;
        $comment->body = $request->comment;
        $comment->save();

        return response()->json([
            'status_code' => '1',
            'status_message' => 'User commented successfully'
        ]);
    }

    /**
     *
     * A user should be able to query all the posts.
     */
    public function getAllPosts(){

        $post = Post::with('images')->with('comments')->get();

        return response()->json([
            'status_code' => '1',
            'status_message' => $post
        ]);
    }


    /**
     *
     * A user should be able to query all the posts that they have created.
     */
    public function getMyPosts(){

        $user = JWTAuth::parseToken()->authenticate();
        $post = Post::where('user_id', $user->id)->with('images')->with('comments')->get();

        return response()->json([
            'status_code' => '1',
            'status_message' => $post
        ]);
    }


    /**
     *
     * Viewing any post should show you all the comments for that post
     * as well as how many people upvoted or downvoted the post.
     */
    public function getPost(Request $request){

        $validator = Validator::make($request->all(), [
            'post_id' => 'required|integer',
        ]);

        if ($validator->fails()) {

            return response()->json([
                'status_code' => '0',
                'status_message' => 'Invalid post id.'
            ]);
        }


        $post = Post::find($request->post_id);
        $posts = fractal()->item($post)->transformWith(new PostTransformer);

        return response()->json([
            'status_code' => '1',
            'status_message' => 'Post Details successfully',
            'posts' => $posts
        ]);
    }



     /**
     *
     * Users should be able to upvote (like) or downvote (dislike) those posts.
     */
    public function upvotedDownvoted(Request $request){

        $user_details = JWTAuth::parseToken()->authenticate();

        $validator = Validator::make($request->all(), [
            'post_id' => 'required|integer',
            'like' => 'required|integer|between:0,2',
        ]);

        if ($validator->fails()) {

            return response()->json([
                'status_code' => '0',
                'status_message' => $validator->messages()
            ]);
        }

        $liked = Like::where('user_id', $user_details->id)->where('post_id', $request->post_id)->first();


        if($request->like != 2){
            if ($liked !== null) {

                $update = Like::find($liked->id);
                $update->like = $request->like;
                $update->save();

            } else {
                $liked = Like::create([
                'like' => request('like'),
                'user_id' => $user_details->id,
                'post_id' => request('post_id'),
                ]);
            }

            $status_message = 'Voted successfully';

        }else{

            $status_message = 'Vote successfully removed';

        }

        return response()->json([
            'status_code' => '1',
            'status_message' => $status_message
        ]);

    }


    /**
     *
     * A user should be able to those posts.
     */
    public function searchPost(Request $request){

        $alidator = Validator::make($request->all(), [
            'username' => 'required',
        ]);

        if ($alidator->fails()) {

            return response()->json([
                'status_code' => '0',
                'status_message' => 'Username field is required.'
            ]);
        }

        $user = User::where('username',$request->username)->first();

        if(!empty($user)){

            $posts = Post::where('user_id', $user->id)->with('images')->with('comments')->get();
            return response()->json([
                'status_code' => '1',
                'status_message' => $posts
            ]);

        }

        return response()->json([
            'status_code' => '0',
            'status_message' => 'Username not found'
        ]);

    }


    /**
     *
     * A user should be able to delete posts.
     */
    public function deletePost(Request $request){

        $validator = Validator::make($request->all(), [
            'post_id' => 'required|integer',
        ]);

        if ($validator->fails()) {

            return response()->json([
                'status_code' => '0',
                'status_message' => 'Invalid post id.'
            ]);
        }


        $post = Post::find($request->post_id);

        if(!empty($post) && $post->user_id == JWTAuth::parseToken()->authenticate()->id){

            Comment::where('post_id', $request->post_id)->delete();
            Like::where('post_id', $request->post_id)->delete();
            Image::where('post_id', $request->post_id)->delete();
            Post::where('id',$post->id)->delete();
            Storage::deleteDirectory('public/images/posts/'. $request->post_id);

            return response()->json([
                'status_code' => '1',
                'status_message' => 'Post successfully updated.'
            ]);
        }

        return response()->json([
            'status_code' => '0',
            'status_message' => 'Post was not found.'
        ]);

    }


    /**
     *
     * A User should be able to upvote or downvote comments.
     */
    public function upvotedDownvotedComment(Request $request){

        $user_details = JWTAuth::parseToken()->authenticate();

        $validator = Validator::make($request->all(), [
            'comment_id' => 'required|integer',
            'like' => 'required|integer|between:0,2',
        ]);

        if ($validator->fails()) {

            return response()->json([
                'status_code' => '0',
                'status_message' => $validator->messages()
            ]);
        }

        $CommentLike = CommentLike::where('user_id', $user_details->id)->where('comment_id', $request->comment_id)->first();


        if($request->comment_id != 2){
            if (!empty($CommentLike)) {

                $update = CommentLike::find($CommentLike->id);
                $update->like = $request->like;
                $update->save();

            } else {
                $commentLike = CommentLike::create([
                'like' => request('like'),
                'user_id' => $user_details->id,
                'comment_id' => request('comment_id'),
                ]);
            }

            $status_message = 'Voted successfully';

        }else{

            $status_message = 'Vote successfully removed';

        }

        return response()->json([
            'status_code' => '1',
            'status_message' => $status_message
        ]);

    }


    /**
     *
     * A user should be able to query all the posts that they have upvoted or downvoted.
     */
    public function postUserVodted(){

       $posts_array = Like::where('user_id' ,JWTAuth::parseToken()->authenticate()->id)->pluck('post_id')->all(); //returns array
       $posts = Post::where('user_id', $posts_array)->with('images')->with('comments')->get();

        return response()->json([
            'status_code' => '1',
            'status_message' => $posts
        ]);

    }

}
