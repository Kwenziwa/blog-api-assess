<?php
use Carbon\Carbon;
use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;


    if (! function_exists('validEmail')) {

         function validEmail($email) {
            return !!User::where('email', $email)->first();
         }
    }


    if(! function_exists('verification_email')){

        function verification_email($email, $name, $user_id,$subject ){
            $verification_code = str_random(30); //Generate verification code
            DB::table('user_verifications')->insert(['user_id'=>$user_id,'token'=>$verification_code]);
            
            Mail::send('emails.verify', ['name' => $name, 'verification_code' => $verification_code],
                function($mail) use ($email, $name, $subject){
                    $mail->from(getenv('FROM_EMAIL_ADDRESS'), "no-reply@api.com");
                    $mail->to($email, $name);
                    $mail->subject($subject);
            });
        }
    }

    if(! function_exists('password_reset_email')){

        function password_reset_email($email, $name, $user_id,$subject ){
            $verification_code = str_random(30); //Generate verification code
            DB::table('user_verifications')->insert(['user_id'=>$user_id,'token'=>$verification_code]);


            Mail::send('emails.rest', ['name' => $name, 'verification_code' => $verification_code],
                function($mail) use ($email, $name, $subject){
                    $mail->from(getenv('FROM_EMAIL_ADDRESS'), "no-reply@api.com");
                    $mail->to($email, $name);
                    $mail->subject($subject);
            });
        }
    }




