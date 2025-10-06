<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\CustomLoginController;
use App\Http\Controllers\AttendanceController;

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

Route::get('/', function () {
    return view('welcome');
});

Route::post('/login', [CustomLoginController::class, 'store'])->name('login');

Route::get('/admin/login', function () {
    return view('admin.login');
})->name('admin.login');
Route::post('/admin/login', [CustomLoginController::class, 'store'])->name('admin.login.post');

Route::middleware(['auth'])->group(function () {
    Route::get('/attendance', [AttendanceController::class, 'record'])->name('attendance');
    Route::post('/attendance', [AttendanceController::class, 'store'])->name('attendance.store');
});