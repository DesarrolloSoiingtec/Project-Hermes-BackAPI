<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Rol\RoleController;
use App\Http\Controllers\Staff\StaffController;
use App\Http\Controllers\Others\LegalDocumentController;
use App\Http\Controllers\Others\CompanyController;
use App\Http\Controllers\Others\CountryController;
use App\Http\Controllers\Others\ServiceController;
use App\Http\Controllers\Others\SpecialtyController;
use App\Http\Controllers\Siau\SiauController;
use App\Http\Controllers\Siau\BlockUsers;
use App\Http\Controllers\APB\APBController;
use App\Http\Controllers\unauthenticated\NotAuth_DocumentController;
use App\Http\Controllers\unauthenticated\NotAuth_ValidationController;
use App\Http\Controllers\Graphics\GraphicsController;
use App\Http\Controllers\Maileroo\MailerooController;
use App\Http\Controllers\Maileroo\RecipientController;
use App\Http\Controllers\Maileroo\SendMailController;

// ========================================================>>
// Rutas Maileroo
// ========================================================>>

Route::group([
    'prefix' => 'fc849aae-4304-40d5-9744-28b08023961b',
], function () {
    Route::post('webhook/email-events', [MailerooController::class, 'newEvent']);
});

Route::group([
    'prefix' => '4e975d0e-1021-45bf-acc8-bd5499a2afaf',
], function () {
    Route::get('recipients/get/table', [RecipientController::class, 'getRecipientsTable']);
    Route::get('dashboard/data', [RecipientController::class, 'getDashboardData']);
});

Route::group([
    'prefix' => '09c87545-e7b9-4358-9110-18865b02e5db',
], function () {
    Route::post('send/mail', [SendMailController::class, 'sendMail']);
});


// ========================================================>>
// Rutas Aplicativo
// ========================================================>>

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

//Ruta para las peticiones sql
Route::group([
    'prefix' => 'g1H2i3-J4k5L6-M7n8O9-P0q1R2-S3t4U5',
    'middleware' => ['auth:api']
],function($router) {
    Route::get("sql/request",[SiauController::class,"sqlRequest"]);
    Route::get("validate/admin/password",[SiauController::class,"validateAdminPassword"]);
});

// Rutas para gestionar la sección de gráficas
Route::group([
    'prefix' => 'bd2159e5-244e-466e-8a20-a1b1c941eecf',
    'middleware' => ['auth:api']
],function($router) {
    Route::get("siau/get/grap/info",[GraphicsController::class,"getGrapInfo"]);
    Route::get("siau/get/reasons/grap/info",[GraphicsController::class,"getReasonsGrap"]);
    Route::get("siau/age/grap",[GraphicsController::class,"getAgeGrap"]);
    Route::get("siau/get/users/forAgreement",[GraphicsController::class,"getUsersForAgreement"]);
    Route::get("siau/get/apb/agreement/grap/info",[GraphicsController::class,"getAPBandAgreement"]);
    Route::get("siau/table/grap/info",[GraphicsController::class,"getGrapTable"]);
    Route::get("siau/table/detail/grap",[GraphicsController::class,"getDetailGrap"]);
});

// Rutas para perfil
Route::group([
    'prefix' => 'f06ac5c5-6c45-480d-9f0b-fbc7cb8c8d93',
    'middleware' => ['auth:api']
],function($router) {
    Route::get("get/profile",[StaffController::class,"getProfile"]);
    Route::post("update/profile",[StaffController::class,"updateProfile"]);
    Route::post("update/profile/credentials",[StaffController::class,"updateProfileCredentials"]);
});

// ---> Ruta para validar si la API se está ejecutando correctamente
Route::group([
    'prefix' => 'check-api', // Ruta base para comprobar la API
], function () {
    // Ruta para verificar si la API está activa
    Route::get('status', function () {
        return response()->json([
            'status' => 'API is up and running', // Mensaje para indicar que la API está activa
            'timestamp' => now(),
        ]);
    });
});
