<?php

use Pecee\SimpleRouter\SimpleRouter as Route;
use App\Controllers\{
    AdminController,
    IndexController,
    BscController,
    CadunicoController,
    ExternalDatabaseAdminController,
    PpaAdminController,
    PpaController,
    };

// sascsa
// osc
// popweb
// cadunico

// Namespace principal 
Route::group(["namespace" => "App\Controllers"], function(){
    /** ADMIN */
    Route::get('/admin', [AdminController::class, 'login']);
    Route::post('/admin/login', [AdminController::class, 'authenticate']);
    Route::get('/admin/painel', [AdminController::class, 'panel']);
    Route::get('/admin/usuarios', [AdminController::class, 'users']);
    Route::get('/admin/usuarios/novo', [AdminController::class, 'createUser']);
    Route::post('/admin/usuarios/store', [AdminController::class, 'storeUser']);
    Route::get('/admin/usuarios/editar/{id}', [AdminController::class, 'editUser']);
    Route::post('/admin/usuarios/update/{id}', [AdminController::class, 'updateUser']);
    Route::post('/admin/usuarios/delete/{id}', [AdminController::class, 'deleteUser']);
    Route::post('/admin/logout', [AdminController::class, 'logout']);
    Route::get('/admin/bases-externas', [ExternalDatabaseAdminController::class, 'sources']);
    Route::get('/admin/bases-externas/nova', [ExternalDatabaseAdminController::class, 'createSource']);
    Route::post('/admin/bases-externas/store', [ExternalDatabaseAdminController::class, 'storeSource']);
    Route::get('/admin/bases-externas/editar/{id}', [ExternalDatabaseAdminController::class, 'editSource']);
    Route::post('/admin/bases-externas/update/{id}', [ExternalDatabaseAdminController::class, 'updateSource']);
    Route::post('/admin/bases-externas/delete/{id}', [ExternalDatabaseAdminController::class, 'deleteSource']);
    Route::post('/admin/bases-externas/testar/{id}', [ExternalDatabaseAdminController::class, 'testSource']);
    Route::get('/admin/bases-externas/consultas', [ExternalDatabaseAdminController::class, 'queries']);
    Route::get('/admin/bases-externas/consultas/nova', [ExternalDatabaseAdminController::class, 'createQuery']);
    Route::post('/admin/bases-externas/consultas/store', [ExternalDatabaseAdminController::class, 'storeQuery']);
    Route::get('/admin/bases-externas/consultas/editar/{id}', [ExternalDatabaseAdminController::class, 'editQuery']);
    Route::post('/admin/bases-externas/consultas/update/{id}', [ExternalDatabaseAdminController::class, 'updateQuery']);
    Route::post('/admin/bases-externas/consultas/delete/{id}', [ExternalDatabaseAdminController::class, 'deleteQuery']);
    Route::post('/admin/bases-externas/consultas/testar/{id}', [ExternalDatabaseAdminController::class, 'testQuery']);
    Route::get('/admin/ppa', [PpaAdminController::class, 'dashboard']);
    Route::get('/admin/ppa/indicadores', [PpaAdminController::class, 'indicators']);
    Route::get('/admin/ppa/indicadores/novo', [PpaAdminController::class, 'createIndicator']);
    Route::post('/admin/ppa/indicadores/store', [PpaAdminController::class, 'storeIndicator']);
    Route::get('/admin/ppa/indicadores/editar/{id}', [PpaAdminController::class, 'editIndicator']);
    Route::post('/admin/ppa/indicadores/update/{id}', [PpaAdminController::class, 'updateIndicator']);
    Route::post('/admin/ppa/indicadores/delete/{id}', [PpaAdminController::class, 'deleteIndicator']);
    Route::get('/admin/ppa/vinculos', [PpaAdminController::class, 'links']);
    Route::get('/admin/ppa/vinculos/novo', [PpaAdminController::class, 'createLink']);
    Route::post('/admin/ppa/vinculos/store', [PpaAdminController::class, 'storeLink']);
    Route::get('/admin/ppa/vinculos/editar/{id}', [PpaAdminController::class, 'editLink']);
    Route::post('/admin/ppa/vinculos/update/{id}', [PpaAdminController::class, 'updateLink']);
    Route::post('/admin/ppa/vinculos/delete/{id}', [PpaAdminController::class, 'deleteLink']);

    /**BSC */
    Route::get('/', [IndexController::class,'index']);
    Route::get("/bsc", [BscController::class,'index']);
    Route::get('/bsc/dashboard-data', 'BscController@dashboardData');
    Route::post('/bsc/filter/local', 'BscController@setLocalPeriod');
    Route::post('/bsc/filter/local/clear', 'BscController@clearLocalPeriod');
    Route::post('/bsc/filter/global', 'BscController@setGlobalPeriod');
    Route::get('/bsc/registros', 'BscController@records');

    Route::get('/bsc/create', 'BscController@create');
    Route::post('/bsc/store', 'BscController@store');

    Route::get('/bsc/show/{id}', 'BscController@show');
    Route::get('/bsc/edit/{id}', 'BscController@edit');
    Route::post('/bsc/update/{id}', 'BscController@update');

    Route::post('/bsc/delete/{id}', 'BscController@delete');

    /** CADUNICO */
    Route::get('/cadunico', [CadunicoController::class, 'index']);

    /** PPA */
    Route::get('/ppa', [PpaController::class, 'index']);
    Route::get('/ppa/{slug}/data', [PpaController::class, 'dashboardData']);
    Route::get('/ppa/{slug}', [PpaController::class, 'show']);
});
