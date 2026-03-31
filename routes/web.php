<?php

use Illuminate\Support\Facades\Route;
//use App\Http\Controllers\CustomAuthController;

// Route::get('/', function () {
//     return view('welcome');
// });

// Route::middleware(['islogedin'])->group(function () {
//     Route::get('/login', [CustomAuthController::class, 'login'])->name('login');
//     Route::get('/registration', [CustomAuthController::class, 'registration'])->name('registration');
//     Route::post('/register-user', [CustomAuthController::class, 'registerUser'])->name('register-user');
//     Route::post('/login-user', [CustomAuthController::class, 'loginUser'])->name('login-user');
// });

// Route::middleware(['authcheck'])->group(function () {
//     Route::get('/dashboard', [CustomAuthController::class, 'dashboard'])->name('dashboard');
//     Route::get('/logout', [CustomAuthController::class, 'logout'])->name('logout');
// });