<?php

use Pecee\SimpleRouter\SimpleRouter as Route;
use App\Controllers\{
    AdminController,
    IndexController,
    BscController,
    CadunicoController,
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
});
