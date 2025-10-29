<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\IncidentController;
use App\Http\Controllers\Api\ConsultantAuthController;
use App\Http\Controllers\Api\EmployeeLookupController;

Route::get('/employees/lookup', [EmployeeLookupController::class, 'lookup']);

Route::post('/incidencias', [IncidentController::class, 'store']);
Route::get('/incidencias', [IncidentController::class, 'index']);
Route::get('/incidencias/{id}', [IncidentController::class, 'show']);
// Eliminación desde UI de reporte: requiere dni_type y dni_number en el body
Route::delete('/incidencias/{id}', [IncidentController::class, 'destroy']);
Route::get('/attachments/{id}', [IncidentController::class, 'viewAttachment']);

Route::middleware('api.token')->group(function () {
    Route::put('/incidencias/{id}', [IncidentController::class, 'update']);
    Route::get('/incidencias/export/excel', [IncidentController::class, 'exportExcel']);

    Route::post('/logout', [ConsultantAuthController::class, 'logout']);
});

Route::post('/login', [ConsultantAuthController::class, 'login']);