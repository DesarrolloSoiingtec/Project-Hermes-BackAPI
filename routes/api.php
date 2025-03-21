<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Rol\RoleController;
use App\Http\Controllers\Staff\StaffController;
use App\Http\Controllers\Others\LegalDocumentController;
use App\Http\Controllers\Others\CompanyController;
use App\Http\Controllers\Others\CountryController;

// Rutas para Autenticación
Route::group([
    'prefix' => 'auth',
    // 'middleware' => ['auth:api']//,'permission:edit articles'
], function ($router) {
    Route::post('/register', [AuthController::class, 'register'])->name('register');
    Route::post('/login', [AuthController::class, 'login'])->name('login');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::post('/refresh', [AuthController::class, 'refresh'])->name('refresh');
    Route::post('/me', [AuthController::class, 'me'])->name('me');
});

// Rutas para Roles y Permisos
Route::group([
    'prefix' => 'aB3dE-F1Gh2-IjK3L-MnOp4-QrSt5',
    'middleware' => ['auth:api']
],function($router) {
    Route::resource("role",RoleController::class);

    Route::get("staffs/list",[StaffController::class,"index"]);
    Route::post("staffs/edit/info/{id}",[StaffController::class,"updateInfo"]);
    Route::post("staffs/edit/credentials/{id}",[StaffController::class,"updateCredentials"]);
    Route::post("staffs/create",[StaffController::class,"store"]);
    Route::delete("staffs". "/{id}",[StaffController::class,"destroy"]);
});

// Rutas para documentos legales
Route::group([
    'prefix' => 'zX7yW-V8uT6-SrQ9P-LmNo3-KjHg1',
    'middleware' => ['auth:api']
],function($router) {
    Route::get("documents",[LegalDocumentController::class,"getDocuments"]);
    Route::get("documents/company",[LegalDocumentController::class,"getDocumentsCompany"]);
});

// Rutas para empresa
Route::group([
    'prefix' => 'a1B2c3-D4e5F6-G7h8I9-J0k1L2-M3n4O5',
    'middleware' => ['auth:api']
],function($router) {
    Route::get("economic/activity",[CompanyController::class,"getEconomicActivity"]);
    Route::get("economic/activity/ciuu/codes",[CompanyController::class,"getCodesOfActivity"]);
});

// Rutas para paises - prefijos
Route::group([
    'prefix' => 'b7C8d9-E0f1G2-H3i4J5-K6l7M8-N9o0P1',
    'middleware' => ['auth:api']
],function($router) {
    Route::get("country/codes",[CountryController::class,"getCountriesCodes"]);
});
