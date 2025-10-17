<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\CustomLoginController;
use App\Http\Controllers\Auth\LogoutController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\CorrectionController;
use App\Http\Controllers\Admin\AttendanceController as AdminAttendanceController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\CorrectionApprovalController;

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

Route::post('/logout', [LogoutController::class, 'logout'])->name('logout');

// メール認証関連
Route::get('/email/verify', fn () => view('verify-email'))
    ->middleware('auth')
    ->name('verification.notice');

Route::get('email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
    $request->fulfill();
    return redirect('/first-login');
})->middleware(['auth', 'signed'])->name('verification.verify');

Route::post('email/verification-notification', function (Request $request) {
    $request->user()->sendEmailVerificationNotification();
    return back()->with('message', '確認メールを再送しました。');
})->middleware(['auth', 'throttle:6,1'])->name('verification.send');

Route::middleware(['auth', 'role:user'])->group(function () {
    // 初回ログイン
    Route::get('/first-login', function () {
        $user = auth()->user();

        if ($user->hasVerifiedEmail()) {
            return redirect('/attendance');
        }

        return view('verify-email', compact('user'));
    })->name('first-login');

    Route::middleware(['verified'])->group(function () {
        Route::get('/attendance', [AttendanceController::class, 'record'])->name('attendance');
        Route::post('/attendance', [AttendanceController::class, 'store'])->name('attendance.store');

        Route::get('/attendance/list', [AttendanceController::class, 'index'])->name('attendance.index');

        Route::get('/attendance/detail/{id}', [AttendanceController::class, 'show'])->name('attendance.show');
        Route::post('/attendance/detail/{id}', [CorrectionController::class, 'store'])->name('attendance.correction');

        Route::get('/stamp_correction_request/list', [CorrectionController::class, 'index'])->name('requests.index');
    });
});

Route::middleware(['auth', 'admin', 'role:admin'])->prefix('admin')->group(function () {
    Route::get('/attendances', [AdminAttendanceController::class, 'today'])->name('admin.attendances.today');

    Route::get('/attendances/{id}', [AdminAttendanceController::class, 'show'])->name('admin.attendances.show');
    Route::post('/attendances/{id}', [AdminAttendanceController::class, 'store'])->name('admin.attendances.store');

    Route::get('/users', [UserController::class, 'index'])->name('admin.staffs.index');

    Route::get('/users/{user}/attendances', [UserController::class, 'show'])->name('admin.staffs.attendances');
    Route::get('/users/{user}/attendances/export', [UserController::class, 'export'])->name('admin.staffs.attendances.export');

    Route::get('requests', [CorrectionApprovalController::class, 'index'])->name('admin.requests.index');

    Route::get('/requests/{id}', [CorrectionApprovalController::class, 'show'])->name('admin.corrections.show');
    Route::post('/requests/{id}', [CorrectionApprovalController::class, 'approve'])->name('admin.corrections.approved');
});