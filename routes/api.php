<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::namespace('Api')
    ->prefix('v1')
    ->middleware('cors')
    ->group(function(){

        Route::middleware('api.guard')->group(function(){
            Route::post('users/login', 'UserController@login')->name('users.login');
            Route::post('users', 'UserController@store')->name('users.store');

            Route::middleware('api.refresh')->group(function(){
                Route::get('users', 'UserController@index')->name('users.index');

                Route::get('users/info', 'UserController@info')->name('users.info');

                Route::get('users/logout','UserController@logout')->name('users.logout');
                Route::get('users/{user}', 'UserController@show')->name('users.show');
            });
        });

        Route::middleware('admin.guard')->group(function(){
            Route::post('admins/login', 'AdminController@login')->name('admins.login');
            Route::post('admins', 'AdminController@store')->name('admins.store');

            Route::middleware('api.refresh')->group(function(){
                Route::get('admins', 'AdminController@index')->name('admins.index');
                Route::get('admins/info', 'AdminController@info')->name('admins.info');
                Route::get('admins/logout','AdminController@logout')->name('admins.logout');
                Route::get('admins/{admin}', 'AdminController@show')->name('admins.show');
            });
        });
});
