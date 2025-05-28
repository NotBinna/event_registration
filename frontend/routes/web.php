<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('session/login-session');
});

Route::get('/login', function () {
    return view('session.login-session');
});

Route::get('/register', function () {
    return view('session.register');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->name('dashboard');
