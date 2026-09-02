<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::view('/', 'auth.login');

Route::middleware('guest')->group(function () {
    Route::view('/login', 'auth.login')->name('login');

    Route::post('/login', function (Request $request) {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withErrors(['email' => 'Email atau password yang kamu masukkan belum tepat.'])
                ->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->intended('/dashboard');
    })->name('login.store');
});

Route::get('/dashboard', function () {
    return view('welcome');
})->middleware('auth')->name('dashboard');

Route::post('/logout', function (Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect()->route('login');
})->middleware('auth')->name('logout');
