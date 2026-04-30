<?php

use Pecee\SimpleRouter\SimpleRouter as Route;
use App\Controllers\{
    IndexController,
    BscController,
    };

// sascsa
// osc
// popweb
// cadunico

// Namespace principal 
Route::group(["namespace" => "App\Controllers"], function(){
    /**BSC */
    // Route::get('/', [IndexController::class,'index']);
    Route::get("/", [BscController::class,'index']);
    Route::get("/bsc", [BscController::class,'index']);
    Route::get('/bsc/registros', 'BscController@records');

    Route::get('/bsc/create', 'BscController@create');
    Route::post('/bsc/store', 'BscController@store');

    Route::get('/bsc/show/{id}', 'BscController@show');
    Route::get('/bsc/edit/{id}', 'BscController@edit');
    Route::post('/bsc/update/{id}', 'BscController@update');

    Route::post('/bsc/delete/{id}', 'BscController@delete');
});
