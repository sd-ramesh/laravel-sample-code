<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Validator;
use Auth, Response, Exception, Session;
use App\User;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */	 
    protected $maxAttempts = 3; // default is 5
    protected $decayMinutes = 1; // default is 1
    // protected $redirectTo;
    public function redirectTo()
    {           
        return redirect()->route('admindashboard');
        return $next($request);
    } 
    

    // protected $redirectTo = '/';

    public function showLoginForm(Request $request)
    {
     
        if(auth::check())
        {
            $role_name = Auth::user()->getRoleNames()->first();
           
            if($role_name == 'User')
            {
                return redirect()->route('userdashboard');
            } elseif ($role_name == 'Administrator'){
                return redirect()->route('admindashboard');
            }
            else
            {
                Auth::logout();
                return redirect()->route('login')->with("status", "error")->with('message', Config::get('constants.ERROR.WRONG_CREDENTIAL'));
            }
        }
        else
        {
            $register = '';
            if(!session()->has('url.intended'))
            {
                session(['url.intended' => url()->previous()]);
            }
            if($request->has('register'))
            {
                $register = Config::get('constants.SUCCESS.ACCOUNT_CREATED');
            } 
            
            return redirect()->route('home');
        }
    }

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    public function login(Request $request)
    { 
        
        $this->validate($request, [
            'email' => 'required|email:rfc,dns',
            'password' => 'required',
        ]);
        $credentials = array_merge($request->only($this->username(), 'password'));
        $authSuccess = Auth::attempt($credentials);

        if($authSuccess && Auth::user()->status != '1') {
            Auth::logout();
            $responseArray = [];
            $responseArray['api_response'] = 'error';
            $responseArray['status_code'] = 200;
            $responseArray['message'] = Config::get('constants.ERROR.ACCOUNT_ISSUE');
            $responseArray['data'] = [];    
            return response()->json($responseArray);
            
        }
        else
        {
            if(auth::check()){
                $responseArray = [];
                $responseArray['api_response'] = 'Success';
                $responseArray['status_code'] = 200;
                $responseArray['message'] = 'Login Success';
                $responseArray['data'] = [route('userdashboard')];    
                return response()->json($responseArray);
            }
            else{
                $responseArray = [];
                $responseArray['api_response'] = 'error';
                $responseArray['status_code'] = 200;
                $responseArray['message'] = Config::get('constants.ERROR.WRONG_CREDENTIAL');
                $responseArray['data'] = [];    
                return response()->json($responseArray);
            }
        }
    }

    protected function credentials(Request $request)
    {
       return array_merge($request->only($this->username(), 'password'), ['status' => 1, 'verified' => 1]);
    }
}
