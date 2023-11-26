<?php

use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AgpController;

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
Route::view('/', 'web.welcome');
Route::any('/agp', [AgpController::class, 'view'])->name('agp');
Route::any('/daily', [AgpController::class, 'view'])->name('daily');
Route::any('/weekly', [AgpController::class, 'view'])->name('weekly');
Route::any('/logout', [UserController::class, 'logout']);
