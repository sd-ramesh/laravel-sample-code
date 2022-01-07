<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Auth;

class SiteController extends Controller
{
    public function index() {
       return view('welcome');
    }

    public function logout(){
		Auth::logout();
        return redirect()->route('home');
    }
}
