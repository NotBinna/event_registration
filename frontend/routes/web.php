<?php

use Illuminate\Support\Facades\Route;

// Home & umum
Route::get('/', function () {
    return view('home');
})->name('home');

Route::get('/home', function () {
    return redirect('/');
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

Route::get('/finance/approvals', function () {
    return view('event.finance_approvals');
})->name('finance.approvals');

Route::get('/scan-ticket', function () {
    return view('event.scan_ticket');
})->name('scan-ticket');

Route::get('/manage-tickets', function () {
    return view('event.manage_tickets');
})->name('manage-tickets');

Route::get('/event', function () {
    return view('event.index');
})->name('event.index');

Route::get('/event/index', function () {
    return view('event.index');
})->name('event.index');

Route::get('/event/buy/{id}', function ($id) {
    return redirect()->to('/buy/'.$id);
})->name('event.redirect.buy');

Route::get('/buy/{id}', function ($id) {
    return view('event.buy', compact('id'));
})->name('event.buy');

Route::get('/my-events', function () {
    return view('event.my_events');
})->name('my-events');

Route::get('/payment-page', function () {
    return view('event.payment');
})->name('payment.page');