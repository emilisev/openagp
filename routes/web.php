<?php

use App\Http\Controllers\ProfileController;
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
Route::view('/', 'web.welcome')->name('welcome');
Route::any('/agp', [AgpController::class, 'view'])->name('agp');
Route::any('/timeInRange', [AgpController::class, 'view'])->name('timeInRange');
Route::any('/treatment', [AgpController::class, 'view'])->name('treatment');


Route::any('/daily/notes/{notes}', [AgpController::class, 'view'])->name('daily');
Route::any('/daily', [AgpController::class, 'view'])->name('daily');
Route::post('daily', [AgpController::class, 'postToGet'])->name('daily');

Route::any('/weekly', [AgpController::class, 'view'])->name('weekly');
Route::any('/monthly', [AgpController::class, 'view'])->name('monthly');
Route::any('/ratio', [AgpController::class, 'view'])->name('ratio');


Route::any('/user', [UserController::class, 'view'])->name('user');
Route::any('/logout', [UserController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});


Route::fallback(function() { return view('web.welcome');});
//require __DIR__.'/auth.php';
