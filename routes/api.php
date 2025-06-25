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

// Rutas para crear usuarios del sistema tipo Asistencial y Pacientes   |
Route::group([
    'prefix' => 'u1V2w3-X4y5Z6-A7b8C9-D0e1F2-G3h4I5',
    'middleware' => ['auth:api']
],function($router) {
    // Route::get("get/info/patient",[StaffController::class,"getPatient"]);
    Route::post("staffs/create/patient",[StaffController::class,"createPatient"]);
    Route::post("staffs/user/validation",[StaffController::class,"userValidation"]);

    Route::post("staffs/create/assistant",[StaffController::class,"createAssistant"]);
    Route::get("staffs/get/assistant/from-specialties",[StaffController::class,"getAssistantFromSpecialties"]);
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
    Route::get("documents/company/APB",[LegalDocumentController::class,"getDocumentsCompanyApb"]);
});

// Rutas para empresa
Route::group([
    'prefix' => 'a1B2c3-D4e5F6-G7h8I9-J0k1L2-M3n4O5',
    'middleware' => ['auth:api']
],function($router) {
    Route::get("economic/activity",[CompanyController::class,"getEconomicActivity"]);
    Route::get("economic/activity/ciuu/codes",[CompanyController::class,"getCodesOfActivity"]);
    Route::post("create/company",[CompanyController::class,"createCompany"]);

});

// Rutas para países - prefijos
Route::group([
    'prefix' => 'b7C8d9-E0f1G2-H3i4J5-K6l7M8-N9o0P1',
    'middleware' => ['auth:api']
],function($router) {
    Route::get("country/codes",[CountryController::class,"getCountriesCodes"]);
});

// Rutas para crear pacientes y asignarles cursos
Route::group([
    'prefix' => 'c1D2e3-F4g5H6-I7j8K9-L0m1N2-O3p4Q5',
    'middleware' => ['auth:api']
],function($router) {
    Route::get("siau/trained",[SiauController::class,"Trained"]);
    Route::post("siau/create/users",[SiauController::class,"createUser"]);
    Route::get("siau/table/users",[SiauController::class,"getUsers"]);
    Route::post("siau/delete/users",[SiauController::class,"deleteUser"]);
    Route::post("siau/edit/users",[SiauController::class,"editUser"]);
    Route::get("siau/get/agreements/patient",[SiauController::class,"getAgreementsPatient"]);

    Route::post("siau/create/users/block",[BlockUsers::class,"createUserBlock"]);

    Route::post("siau/send/mail",[SiauController::class,'sendMail'])
    ->middleware('throttle:10,1'); // 10 requests por minuto
});

Route::group([
    'prefix' => 'e8F7g6-H5i4J3-K2l1M0-N9o8P7-Q6r5S4',
    'middleware' => ['auth:api']
],function($router) {
    Route::get("get/medical/from-specialties",[SiauController::class,"getMedicalFromSpecialties"]);
    Route::get("get/medical/nameFromUser",[SiauController::class,"getMedicalName"]);
    Route::get("get/medical/information",[StaffController::class,"getMedicalInformation"]);
    Route::post("siau/create/profesional/user",[StaffController::class,"createMedicalUser"]);
    Route::post("siau/update/profesional/info",[StaffController::class,"updateMedicalInfo"]);
});

// Rutas para crear especialidades médicas
Route::group([
    'prefix' => 'f9G8h7-I6j5K4-L3m2N1-O0p9Q8-R7s6T5',
    'middleware' => ['auth:api']
],function($router) {
    Route::get("specialties",[SpecialtyController::class,"getSpecialties"]);
    Route::post("create/specialties",[SpecialtyController::class,"createSpecialty"]);
    Route::post("delete/specialties",[SpecialtyController::class,"deleteSpecialty"]);
    Route::post("update/specialties",[SpecialtyController::class,"updateSpecialty"]);

    Route::post("create/subspecialty",[SpecialtyController::class,"createSubspecialty"]);
    Route::post("delete/subspecialty",[SpecialtyController::class,"deleteSubspecialty"]);
});

// Rutas para crear APB y asignarles Convenios
Route::group([
    'prefix' => 'a2B3c4-D5e6F7-G8h9I0-J1k2L3-M4n5O6',
    'middleware' => ['auth:api']
],function($router) {
    Route::get("get/apb",[APBController::class,"getApb"]);
    Route::post("create/apb",[APBController::class,"createApb"]);
    Route::post("delete/apb",[APBController::class,"deleteApb"]);
    Route::post("update/apb",[APBController::class,"updateApb"]);
    Route::post("create/agreement",[APBController::class,"createAgreement"]);
    Route::post("delete/agreement",[APBController::class,"deleteAgreement"]);
    Route::get("get/agreements",[APBController::class,"getAgreements"]);
    Route::get("get/agreements/fromAPB",[APBController::class,"getAgreementFromAPB"]);

    Route::get("get/apb/fromAgreementUser",[APBController::class,"getApbFromAgreement"]);
});

//Ruta para las peticiones sql
Route::group([
    'prefix' => 'g1H2i3-J4k5L6-M7n8O9-P0q1R2-S3t4U5',
    'middleware' => ['auth:api']
],function($router) {
    Route::get("sql/request",[SiauController::class,"sqlRequest"]);
    Route::get("validate/admin/password",[SiauController::class,"validateAdminPassword"]);
});

// Rutas para ver y crear cursos
Route::group([
    'prefix' => 'd4E5f6-G7h8I9-J0k1L2-M3n4O5-P6q7R8',
    'middleware' => ['auth:api']
],function($router) {
    Route::post("siau/create/course",[SiauController::class,"createCourse"]);
    Route::get("siau/table/courses",[SiauController::class,"getCourses"]);
    Route::post("siau/delete/courseFromUser",[SiauController::class,"deleteCourseFromUser"]);
    Route::post("siau/update/course",[SiauController::class,"updateCourse"]);
    Route::post("siau/disable/enable/course",[SiauController::class,"disableEnableCourse"]);
    Route::get("siau/reasons",[SiauController::class,"getReasons"]);
    Route::post("siau/update/reasons",[SiauController::class,"updateCheckReasons"]);
    Route::post("siau/update/name/reasons",[SiauController::class,"updateReasons"]);
    Route::post("siau/delete/reasons",[SiauController::class,"deleteReason"]);
    Route::post("siau/store/reasons",[SiauController::class,"storeReasons"]);
});

// Rutas para crear cuestionario, preguntas y respuestas y asignarlas a un curso
Route::group([
    'prefix' => 'h1I2j3-K4l5M6-N7o8P9-Q0r1S2-T3u4V5',
    'middleware' => ['auth:api']
],function($router) {
    Route::post("siau/create/questionnaire",[SiauController::class,"createQuestionnaire"]);
    Route::post("siau/activate/questionnaire",[SiauController::class,"activateQuestionnaire"]);
    Route::get("siau/table/questionnaire",[SiauController::class,"getQuestionnaires"]);
    Route::post("siau/update/questionnaire",[SiauController::class,"updateQuestionnaire"]);

    Route::post("siau/create/question",[SiauController::class,"createQuestion"]);
    Route::get("siau/table/questions",[SiauController::class,"getQuestion"]);
    Route::post("siau/delete/question",[SiauController::class,"deleteQuestion"]);
    Route::post("siau/update/question",[SiauController::class,"updateQuestion"]);

    Route::post("siau/create/answers",[SiauController::class,"createAnswers"]);
    Route::get("siau/table/answers",[SiauController::class,"getAnswers"]);
    Route::post("siau/select/answers",[SiauController::class,"selectAnswers"]);
    Route::post("siau/delete/answers",[SiauController::class,"deleteAnswers"]);

    Route::post("siau/add/visualHelp",[SiauController::class,"addVisualHelp"]);
});

// Rutas para crear, modificar o inhabilitar un concepto de servicio:
Route::group([
    'prefix' => 'y2Z3x4-A5B6C7-D8E9F0-G1H2I3-J4K5L6',
    'middleware' => ['auth:api']
],function($router) {
    Route::get("get/concept/services",[ServiceController::class,"getConceptServices"]);
    Route::post("update/concept/services",[ServiceController::class,"updateConceptService"]);
    Route::post("create/concept/services",[ServiceController::class,"createConceptService"]);
    Route::post("delete/concept/services",[ServiceController::class,"deleteConceptService"]);

    Route::get("get/services",[ServiceController::class,"getServices"]);
    Route::post("create/services",[ServiceController::class,"createService"]);
    Route::post("update/services",[ServiceController::class,"updateService"]);
    Route::post("delete/services",[ServiceController::class,"deleteService"]);

    Route::get("get/services/from-concept",[ServiceController::class,"getServicesFromConcept"]);
});

// Rutas para asignar archivos a un curso
Route::group([
    'prefix' => 'x1Y2z3-A4B5C6-D7E8F9-G0H1I2-J3K4L5',
    'middleware' => ['auth:api']
],function($router) {
    Route::post("siau/create/file",[SiauController::class,"assignFile"]);
    Route::get("siau/table/files",[SiauController::class,"getFiles"]);
    Route::post("siau/delete/file",[SiauController::class,"deleteFile"]);
});

// Rutas para crear sucursales
Route::group([
    'prefix' => 'u3V4w5-X6y7Z8-A9b0C1-D2e3F4-G5h6I7',
    'middleware' => ['auth:api']
],function($router) {
    Route::get("siau/table/branch",[SiauController::class,"getBranches"]);
    Route::get("siau/get/active/branch",[SiauController::class,"getActiveBranches"]);
    Route::post("siau/create/branch",[SiauController::class,"createBranch"]);
    Route::post("siau/update/branch",[SiauController::class,"updateBranch"]);
    Route::post("siau/deactivate/branch",[SiauController::class,"deactivateBranch"]);
    Route::post("siau/activate/branch",[SiauController::class,"activateBranch"]);
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

// ========================================================>>
// Rutas libres de autenticación
// ========================================================>>

// ---> documentos legales
Route::group([
    'prefix' => 'p1Q2r3-S4t5U6-V7w8X9-Y0z1A2-B3c4D5',
    'middleware' => 'throttle:60,1', // 60 requests por minuto
], function () {
    Route::get("users/documents", [NotAuth_DocumentController::class, "getDocuments_NotAuth"]);
});
// ---> validación de ingreso de capacitados a dashboard
Route::group([
    'prefix' => 'f3G4h5-I6j7K8-L9m0N1-O2p3Q4-R5s6T7',
],function($router) {
    Route::get("login/trained",[NotAuth_ValidationController::class,"loginTrained_NotAuth"]);
});

// ---> Actualizar cronometro
Route::group([
    'prefix' => 'e2A1b3-C4d5E6-F7g8H9-I0j1K2-L3m4N5',
],function($router) {
    Route::post("update/timer",[NotAuth_ValidationController::class,"updateTimer_NotAuth"]);
});

// ---> traer y responder cursos
Route::group([
    'prefix' => 'f3G4h5-I6j7K8-L9m0N1-O2p3Q4-R5s6T7',
],function($router) {
    Route::get("get/courses",[NotAuth_ValidationController::class,'getCourseExam_NotAuth'])
    ->middleware('throttle:30,1'); // 30 req/min por IP

    Route::get("get/reasons",[NotAuth_ValidationController::class,'getReasons'])
    ->middleware('throttle:30,1'); // 30 req/min por IP

    Route::get("get/files/from/user", [NotAuth_ValidationController::class, "getFiles_NotAuth"])
    ->middleware('throttle:30,1'); // 30 req/min por IP

    Route::post("course/answers",[NotAuth_ValidationController::class,'courseAnswers_NotAuth'])
    ->middleware('throttle:10,1'); // 10 req/min por IP
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
