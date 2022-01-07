<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/ 
Route::middleware('XssSanitizer')->group(function () {  
	
	Auth::routes();
	Route::namespace('Frontend')->group(function () {  
		Route::get('/', 'SiteController@index')->name('home');
		Route::get('logout', 'SiteController@logout')->name('logout'); 
		Route::post('/register', 'RegistrationController@registerUser')->name('register');
		Route::get('verify/email/{token}', 'RegistrationController@verifyEmail')->name('verifyEmail');
		Route::get('reset-password', 'UserController@password_reset')->name('password.reset'); 
		Route::post('reset-password-email', 'UserController@password_reset_link')->name('passwordreset');
		Route::get('reset-password/check/token/{token}', 'UserController@password_reset_token_check')->name('checktoken'); 
		Route::post('update-new-password', 'UserController@update_new_password')->name('userupdatenewpassword'); 
		Route::get('set-new-password', 'UserController@new_password_set')->name('usersetnewpassword');
		/***************** User's routes ******************/
		Route::prefix('user')->middleware('user', 'prevent-back-history')->group(function () {
			Route::get('/dashboard', 'UserController@index')->name('userdashboard');
			Route::get('/rating', 'UserController@getRatings')->name('user.rating');
			Route::get('/getreview/{id}', 'UserController@getReview');
			Route::match(['get', 'post'], '/profile/update', 'UserController@profileUpdate')->name('user.profile-update');
			Route::post('/picture/update', 'UserController@pictureUpdate')->name('user.picture-update');
			Route::post('/address/update', 'UserController@addressUpdate')->name('user.address-update');
			Route::get('/password/update', 'UserController@passwordUpdate')->name('user.password');
		});
	});
});
/*****************
******************
Admin panel routes
******************
******************/

Route::namespace('Admin')->prefix('admin')->group(function () {
	Route::match(['get', 'post'], '/', 'AdminDashboardController@login')->name('admin');
	Route::get('reset-password', 'AdminDashboardController@resetPassword')->name('resetpassword');
	Route::post('reset-password-email', 'AdminDashboardController@resetPasswordLink')->name('sendpasswordemail');
	Route::get('reset-password/check/token/{token}', 'AdminDashboardController@verifyResetPasswordToken')->name('tokencheck');
	Route::get('set-new-password', 'AdminDashboardController@setNewPassword')->name('setnewpassword');
	Route::post('update-new-password', 'AdminDashboardController@updateNewPassword')->name('updatenewpassword');
});
Route::namespace('Admin')->prefix('admin')->middleware('admin', 'prevent-back-history')->group(function () {

	Route::get('dashboard', 'AdminDashboardController@index')->name('admindashboard');
	Route::post('details/update', 'AdminDashboardController@updateDetails')->name('details.update');
	Route::post('password/update', 'AdminDashboardController@updatePassword')->name('password.update');

	//Manage user Routes
	Route::prefix('user')->name('user.')->group(function () {
		Route::get('list', 'UserController@getList')->name('list'); 
		Route::get('changeStatus', 'UserController@changeStatus');
		Route::get('add', 'UserController@createDetail')->name('add');
		Route::post('create', 'UserController@createDetail')->name('create');
		Route::get('edit/{id}', 'UserController@updateDetail')->name('edit');
		Route::post('update', 'UserController@updateDetail')->name('update');
		Route::get('user/changepassword/{id}', 'UserController@change_password')->name('changepassword');
		Route::post('user/updatepassword', 'UserController@update_password')->name('updatepassword');
		Route::get('delete/{id}', 'UserController@del_record')->name('delete');
		Route::get('restore/{id}', 'UserController@del_restore')->name('restore'); 
		Route::get('details/{id}', 'UserController@view_detail')->name('details');
	});
});



