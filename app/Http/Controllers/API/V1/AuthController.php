<?php

namespace App\Http\Controllers\API\V1;

use Closure;
use JWTAuth;
use Exception;
use Carbon\Carbon;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Requests\AvaterRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Transformers\UserTransformer;
use App\Http\Requests\PasswordRequest;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\UserUpdateRequest;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\UserRegisterRequest;
use Tymon\JWTAuth\Exceptions\JWTException;
use App\Http\Requests\PasswordChangeRequest;
use Symfony\Component\HttpFoundation\Response;


class AuthController extends Controller
{
    public $token = true;


    /**
	 * User Register
	 *
	 * @param Post method request inputs
	 *
	 * @return Response Json
	 */
    public function register(UserRegisterRequest $request)
    {

        // Insert Into User Table Using Model
        $user = new User();
        $user->first_name = $request->first_name;
        $user->last_name = $request->last_name;
        $user->username = $request->username;
        $user->image = $this->avater_upload($request);;
        $user->email = $request->email;
        $user->password = bcrypt($request->password);
        $user->save();

        // Send Verification Email , function location : app/Helpers/helpers.php
        verification_email($request->email, $request->first_name.' '.$request->last_name, $user->id,'Please verify your email address.');

        return response()->json([
            'status_code' => '1',
            'status_message' => 'Thanks for signing up! Please check your email to complete your registration.',
        ], Response::HTTP_OK);
    }

    /**
     * API Verify User
     *
     * @param Request $request
     *
     * @return Response Json
     */
    public function verifyUser($verification_code)
    {
        $check = DB::table('user_verifications')->where('token',$verification_code)->first();

        if(!is_null($check)){

            $user = User::find($check->id);
            $user->is_verified = 1;
            $user->email_verified_at = Carbon::now();
            $user->save();

            if($user->is_verified == 1){

                $status_message = "Good News, your account  has been verified..";
            }

            DB::table('user_verifications')->where('token',$verification_code)->delete();
            $status_message = "You have successfully verified your email address.";

        } else{

            $status_message = "This verification  token is invalid, please try again later.";
        }

        return view('other.verification', compact('status_message'));
    }

    /**
	 * User Login
	 *
	 * @param Post method request inputs
	 *
	 * @return Response Json
	 */

    public function login(Request $request)
    {

        $jwt_token = null;

        if (filter_var($request->email, FILTER_VALIDATE_EMAIL)) {

            $input = $request->only('email', 'password');

            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'password' => 'required|unique:posts|max:255',
            ]);

          } else {

            $input = $request->only('username', 'password');

            $validator = Validator::make($request->all(), [
                'username' => 'required',
                'password' => 'required',
            ]);

          }

          if ($validator->fails()) {

            return response()->json([
                'status_code' => '0',
                'status_message' => $validator->errors()
            ]);
        }



        try {
            // attempt to verify the credentials and create a token for the user
            if (!$jwt_token = JWTAuth::attempt($input)) {
                return response()->json([
                    'status_code' => '0',
                    'status_message' => 'Invalid Credentials, Please try agian',
                ], Response::HTTP_UNAUTHORIZED);
            }
        } catch (JWTException $e) {
            // something went wrong whilst attempting to encode the token
            return response()->json([
                'status_code' => '0',
                'status_message' => 'Failed to login, please try again.'
            ]);
        }

        $user = JWTAuth::user();

        $user_transformed_details = fractal()->item($user)->transformWith(new UserTransformer);

        if($user->is_verified == 0 ){

            // Send Verification Email , function location : app/Helpers/helpers.php
             verification_email($user->email, $user->first_name.' '.$user->last_name, $user->id,'Please verify your email address.');

            return response()->json([
                'status_code' => '0',
                'status_message' => 'Please verify, your account. Check your email to complete your registration.'
            ]);
        }

        return response()->json([
            'status_code' => '1',
            'status_message' => "User logged in successfully",
            'token' => $jwt_token,
            'user' => $user_transformed_details,
        ]);
    }

    /**
	 * User Details
	 *
	 * @param Get method request inputs
	 *
	 * @return Response Json
	 */

    public function getAuthenticatedUser()
    {
		$user = User::where('id', JWTAuth::parseToken()->authenticate()->id)->first();
        return response()->json([
            'status_code' => '1',
            'status_message' => "Profile Details Success",
            'user'    => $user,
        ]);
    }

    /**
	 * User Profile Update
	 *
	 * @param Post method request inputs
	 *
	 * @return Response Json
	 */

    public function profile_update(UserUpdateRequest $request)
    {
        $user_details = JWTAuth::parseToken()->authenticate();

        $user = User::find($user_details->id);
        $user->first_name = $request->first_name;
        $user->last_name = $request->last_name;
        $user->username = $request->username;
        $user->email = $request->email;
        $user->save();

        return response()->json([
            'status_code' => '1',
            'status_message' => "User details successfully updated",
        ]);

    }

    /**
	 * User Profile Picture Update
	 *
	 * @param Post method request inputs
	 *
	 * @return Response Json
	 */

    public function profile_avater_update(AvaterRequest $request)
    {
        $user_details = JWTAuth::parseToken()->authenticate();


        if($request->hasFile('image')){

            $extension = $request->image->extension();
            $image_url = $request->image->storeAs('/public/images/users/avater/', $user_details->username.".".$extension);
            $image_url = Storage::url($image_url);

        }else{
            $image_url = '/public/images/default.png';
        }

        $user = User::find($user_details->id);
        $user->image = url($image_url);
        $user->save();

        return response()->json([
            'status_code' => '1',
            'status_message' => "User avater successfully updated",
        ]);

    }


    public function change_password(PasswordChangeRequest $request){

        $user_details = JWTAuth::parseToken()->authenticate();

        if(Hash::check($request->old_password, $user_details->password)) {

            $user = User::find($user_details->id);
            $user->password = bcrypt($request->new_password);
            $user->save();

            return response()->json([
                'status_code' => '1',
                'status_message' => "User password successfully updated",
            ]);

        } else {
            // They don't match
            return response()->json([
                'status_code' => '0',
                'status_message' => "The current password you entered doesn't match  with the current password the system expects for the account you're attempting to access",
            ]);
        }

    }

    /**
	 * User Logout
	 *
	 * @param Post method request inputs
	 *
	 * @return Response Json
	 */

    public function logout(Request $request)
    {
        $this->validate($request, [
            'token' => 'required'
        ]);

        try {
            JWTAuth::invalidate($request->token);

            return response()->json([
                'status_code' => '1',
                'status_message' => "User logged out successfully",
            ]);
        } catch (JWTException $exception) {
            return response()->json([
                'status_code' => '0',
                'status_message' => "Sorry, the user cannot be logged out",
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    /**
	 * Avater Upload
	 *
	 * @param Get method request inputs
	 *
	 * @return String image_url
	 */

    public  function avater_upload(Request $request){

        if($request->hasFile('image')){

            $extension = $request->image->extension();
            $image_url = $request->image->storeAs('/public/images/users/avater/', $request->username.".".$extension);
            $image_url = Storage::url($image_url);

        }else{
            $image_url = '/public/images/default.png';
        }

        return url($image_url);
    }


    /**
	 * User Rest Password
	 *
	 * @param Get method request inputs
	 *
	 * @return Response Json
	 */

    public function sendPasswordResetEmail1(Request $request){
        // If email does not exist
        if(validEmail($request->email)) {
            return response()->json([
                'message' => 'Email does not exist.'
            ], Response::HTTP_NOT_FOUND);
        } else {
            // If email exists
            $this->sendMail($request->email);
            return response()->json([
                'message' => 'Check your inbox, we have sent a link to reset email.'
            ], Response::HTTP_OK);
        }
    }



    /**
	 *  Send Verification Email
	 *
	 * @param Get method request inputs
	 *
	 * @return Response Json
	 */
    public function recover_password(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);


        if ($validator->fails()) {
            return response()->json([
                'status_code' => '0',
                'status_message'=> "The email field is required",
            ]);
        }

        $user = User::where('email', $request->email)->first();
        if (!$user) {

            return response()->json([
                'status_code' => '0',
                'status_message'=> "Your email address was not found.",
            ]);
        }

        try {

           // Send Verification Email , function location : app/Helpers/helpers.php
           password_reset_email($user->email, $user->first_name.' '.$user->last_name, $user->id,'Password Reset');

        } catch (\Exception $e) {
            //Return with error
            $error_message = $e->getMessage();
            return response()->json([
                'status_code' => '0',
                'status_message' => $error_message
            ]);
        }

        return response()->json([
            'status_code' => '0',
            'status_message'=> 'A reset email has been sent! Please check your email.'
        ]);
    }

    /**
	 *  Form To change passsword
	 *
	 * @param Get method request inputs
	 *
	 * @return WebForm
	 */
    public function reset_password($verification_code){


        $token = $verification_code;
        $check = DB::table('user_verifications')->where('token',$verification_code)->first();

        if(!is_null($check)){

            $status_message = null;
            $status = true;

        } else{

            $status_message = "This verification  token is invalid, please try again later.";
            $status = false;

        }

        $data = array( 'status','status_message','token' );

        return view('auth.password_form')->with('status', $status)->with('status_message', $status_message)->with('token', $token);

    }

    /**
	 *  Form To change passsword
	 *
	 * @param Get method request inputs
	 *
	 * @return WebForm
	 */
    public  function update_password(PasswordRequest $request)
    {

        $check = DB::table('user_verifications')->where('token',$request->verification_code)->first();

        if(!is_null($check)){

            $user = User::find($check->user_id);
            $user->password = bcrypt($request->password);
            $user->save();

            DB::table('user_verifications')->where('token',$request->verification_code)->delete();
            $status_message = "You have successfully updated your password.";
            $status = false;

        } else{

            $status_message = "Failed to update your password, please try again.";
            $status = true;

        }

        return view('auth.password_form')->with('status', $status)->with('status_message', $status_message)->with('token', $request->verification_code);

    }

}
