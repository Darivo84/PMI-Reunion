<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/

Route::get('/', function () {
    return view('welcome');
});

// APPLICATION ROUTES
    Route::group(['prefix' => 'application'], function () {
        Route::post('/',  [
            'as' => 'app', 
            'uses' => 'appController@application'
        ]);                     
    });
        
    Route::get('/test',  [
            'as' => 'test', 
            'uses' => 'appController@test'
    ]); 
