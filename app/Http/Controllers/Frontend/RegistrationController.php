<?php
namespace App\Http\Controllers\Frontend;

use Illuminate\Support\Facades\{Validator, Config, File};
use App\{User, UserDetails, PasswordReset};
use App\Http\Controllers\Controller;
use App\Traits\AutoResponderTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Auth, Image, Crypt; 
use Carbon\Carbon;

class RegistrationController extends Controller
{
    use AutoResponderTrait;
    
    public function __construct()
    {
        $this->middleware('guest')
            ->except('logout');
    }
 
    /*
    Method Name:    registerUser
    Developer:      Shine Dezign
    Created Date:   2022-01-07 (yyyy-mm-dd)
    Purpose:        Form to register new user
    Params:         
    */ 
    
    public function registerUser(Request $request)
    {
        if ($request->isMethod('get'))
        {
            return view('auth.registration_form');
        }
        else
        { 
            $validator = Validator::make($request->all() , [
                'first_name' => 'required|string|max:20', 
                'last_name' => 'required|string|max:20', 
                'email' => 'required|email|unique:users',
                'password' => 'required_with:password_confirmation|string|confirmed'],['captcha.captcha' => 'Captcha is not matched', 'captcha.required' => 'Captcha field is required']);
            if ($validator->fails() && $request->ajax())
            { 
                return response()
                    ->json(["error" => true, "errors" => $validator->getMessageBag()
                    ->toArray() ], 422); 
            }
            try
            {
                $userData = [
                    'first_name' => $request->first_name, 
                    'last_name' => $request->last_name,
                    'email' => $request->email,
                    'password' => bcrypt($request->password) ,
                    'social_type' => 'Website',
                    'social_id' => 0,
                    'status' => 0,
                ];
                $user = User::create($userData);
                
                $userDetail = [
                    'user_id' => $user->id, 
                ]; 
                $create_status = UserDetails::create($userDetail);
                $user->assignRole('User');

                /*Send Verification Link*/
                $passwordReset = PasswordReset::updateOrCreate(['email' => $request->email], ['email' => $request->email, 'token' => Str::random(12) ]);

                $logtoken = Str::random(12);
                $link = route('verifyEmail', $passwordReset->token);
                $template = $this->get_template_by_name('VERIFY_EMAIL');
                $string_to_replace = [
                    '{{$name}}',
                    '{{$token}}',
                    '{{$logToken}}'
                ];
                $string_replace_with = [
                    $request->first_name . ' ' . $request->last_name,
                    $link,
                    $logtoken
                ];
                $newval = str_replace($string_to_replace, $string_replace_with, $template->template);
                $logId = $this->email_log_create($request->email, $template->id, 'VERIFY_EMAIL', $logtoken);

                $result = $this->send_mail($request->email, $template->subject, $newval);
                if ($result)
                {
                    $this->email_log_update($logId);
                }
                /*End of Send Email Verification Link*/
                if($create_status)
                {
                    $responseArray = [];
                    $responseArray['api_response'] = 'Success';
                    $responseArray['status_code'] = 200;
                    $responseArray['message'] = Config::get('constants.SUCCESS.ACCOUNT_CREATED');
                    $responseArray['data'] = [];    
                    return response()->json($responseArray);
                }
                else{ 
                    $responseArray = [];
                    $responseArray['api_response'] = 'error';
                    $responseArray['status_code'] = 200;
                    $responseArray['message'] = Config::get('constants.ERROR.OOPS_ERROR');
                    $responseArray['data'] = [];    
                    return response()->json($responseArray);
                }

            }
            catch(\Exception $e)
            {
                $responseArray = [];
                $responseArray['api_response'] = 'error';
                $responseArray['status_code'] = 200;
                $responseArray['message'] = $e->getMessage();
                $responseArray['data'] = [];    
                return response()->json($responseArray);
            }
        }

    }
    // /* End Method registerUser */

    
    /*
    Method Name:    verifyEmail
    Developer:      Shine Dezign
    Created Date:   2022-01-07 (yyyy-mm-dd)
    Purpose:        For user email Verification
    Params:         
    */ 
 
    public function verifyEmail($token)
    {
        $passwordReset = PasswordReset::where('token', $token)->first(); 
        if (!$passwordReset)
        {
            if (auth::check())
            {

                $role_name = Auth::user()->getRoleNames()
                    ->first();
                switch ($role_name)
                { 
                    case "User": 
                        return redirect()
                            ->route('userdashboard')
                            ->with('status', 'error')
                            ->with('message', Config::get('constants.ERROR.TOKEN_INVALID'));
                    break;
                    default:
                        return redirect()
                            ->route('/')
                            ->with('status', 'error')
                            ->with('message', Config::get('constants.ERROR.TOKEN_INVALID'));
                }
            }
            else
            {
                return redirect()
                    ->route("login")
                    ->with('status', 'error')
                    ->with('message', Config::get('constants.ERROR.TOKEN_INVALID'));
            }
        }
        else
        {
            $users = User::where('email', $passwordReset->email)
                ->update(['status' => 1]); 
            $passwordReset->delete();
            if (auth::check())
            {
                $role_name = Auth::user()->getRoleNames()
                    ->first();
                switch ($role_name)
                {
                    case "User":
                        return redirect()
                            ->route('userdashboard')
                            ->with('status', 'success')
                            ->with('message', Config::get('constants.SUCCESS.WELCOME'));
                    break;
                    default:
                        return redirect()
                            ->route('/')
                            ->with('status', 'success')
                            ->with('message', Config::get('constants.SUCCESS.WELCOME'));
                }
            }
            else
            {
                return redirect()
                    ->route("login")
                    ->with('status', 'success')
                    ->with('message', Config::get('constants.SUCCESS.WELCOME_LOGIN'));
            }

        }
    } 
    
    // /* End Verification email Function */
    
    public function refreshCaptcha()
    {
        return response()->json(['captcha'=> captcha_img()]);
    }
}

