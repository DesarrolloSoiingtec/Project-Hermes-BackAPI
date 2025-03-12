<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Rol\RoleController;
use App\Http\Controllers\Staff\StaffController;

Route::group([
    // 'middleware' => 'api',
    'prefix' => 'auth',
    // 'middleware' => ['auth:api']//,'permission:edit articles'
], function ($router) {
    Route::post('/register', [AuthController::class, 'register'])->name('register');
    Route::post('/login', [AuthController::class, 'login'])->name('login');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');//->middleware('auth:api')
    Route::post('/refresh', [AuthController::class, 'refresh'])->name('refresh');//->middleware('auth:api')
    Route::post('/me', [AuthController::class, 'me'])->name('me');//->middleware('auth:api')
});

Route::group([
    'prefix' => 'aB3dE-F1Gh2-IjK3L-MnOp4-QrSt5',
    'middleware' => ['auth:api']
],function($router) {
    Route::resource("role",RoleController::class);
    Route::post("staffs/{id}",[StaffController::class,"update"]);
    // Route::resource("staffs",StaffController::class);
    Route::get("staffs",[StaffController::class,"index"]);
    Route::post("staffs",[StaffController::class,"store"]);
    Route::delete("staffs". "/{id}",[StaffController::class,"destroy"]);
});

