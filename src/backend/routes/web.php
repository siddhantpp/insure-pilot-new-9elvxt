<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

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

// Display the welcome page with introduction to the Documents View feature
Route::get('/', function () {
    return view('welcome');
})->name('home');

// Display the main Documents View interface for authenticated users
Route::get('/documents', function () {
    return view('documents');
})->middleware('auth')->name('documents');

// Authentication routes
// Display the login form for unauthenticated users
Route::get('/login', 'Auth\LoginController@showLoginForm')->middleware('guest')->name('login');
// Handle login form submission
Route::post('/login', 'Auth\LoginController@login')->middleware('guest');
// Handle user logout request
Route::post('/logout', 'Auth\LoginController@logout')->middleware('auth')->name('logout');

// Password reset routes
// Display the password reset request form
Route::get('/password/reset', 'Auth\ForgotPasswordController@showLinkRequestForm')->middleware('guest')->name('password.request');
// Send password reset link to user's email
Route::post('/password/email', 'Auth\ForgotPasswordController@sendResetLinkEmail')->middleware('guest')->name('password.email');
// Display the password reset form with token
Route::get('/password/reset/{token}', 'Auth\ResetPasswordController@showResetForm')->middleware('guest')->name('password.reset');
// Handle password reset form submission
Route::post('/password/reset', 'Auth\ResetPasswordController@reset')->middleware('guest')->name('password.update');