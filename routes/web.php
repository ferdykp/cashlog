<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

Route::get('/auth/google/redirect', [App\Http\Controllers\GoogleAuthController::class, 'redirect'])->middleware('throttle:10,1');
Route::get('/auth/google/callback', [App\Http\Controllers\GoogleAuthController::class, 'callback'])->middleware('throttle:20,1');
Route::get('/', function () {
    if (Auth::guard('web')->check()) {
        return redirect('/dashboard');
    }

    return view('home');
});
Route::view('/privacy-policy', 'privacy-policy')->name('privacy-policy');
Route::view('/{path}', 'app')->where('path', '^(?!api(?:/|$)).*');
