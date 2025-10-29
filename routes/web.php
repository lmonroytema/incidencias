<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    // Redirigimos a la nueva UI PHP (sin Blade)
    return redirect('/ui/report.php');
});

// URLs amigables hacia la UI estática en public/ui
Route::get('/reportar', fn() => redirect('/ui/report.php'));
Route::get('/consultor/login', fn() => redirect('/ui/login.php'));
Route::get('/consultor/incidencias', fn() => redirect('/ui/dashboard.php'));
