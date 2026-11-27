<?php

use App\Http\Controllers\GmailAuthController;
use App\Http\Controllers\GoogleAuthController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/auth/google/redirect', [GoogleAuthController::class, 'redirect'])->middleware('throttle:10,1');
Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->middleware('throttle:20,1');
Route::get('/auth/gmail/redirect', [GmailAuthController::class, 'redirect'])->middleware('throttle:10,1');
Route::get('/auth/gmail/callback', [GmailAuthController::class, 'callback'])->middleware('throttle:20,1');
Route::get('/', function () {
    if (Auth::guard('web')->check()) {
        return redirect('/dashboard');
    }

    return view('home');
});
Route::view('/privacy-policy', 'privacy-policy')->name('privacy-policy');
Route::view('/{path}', 'app')->where('path', '^(?!api(?:/|$)).*');
