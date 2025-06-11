<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('session/login-session');
});

Route::get('/login', function () {
    return view('session.login-session');
})->name('login');

Route::get('/register', function () {
    return view('session.register');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->name('dashboard');

Route::get('/admin/users', function () {
    return view('admin.index');
})->name('admin.users');

Route::get('/event/index', function () {
    return view('event.index');
})->name('event.index');