<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

//$app->get('/', function () use ($app) {
//    return $app->version();
//});
$api = app('Dingo\Api\Routing\Router');
$api->version('v1', ['middleware' => ['cors']], function ($api) {
    $api->post('login', 'App\Http\Controllers\LoginController@login');

    $api->group(['middleware' => ['auth']], function ($api) {
        $api->post('get_tab', 'App\Http\Controllers\IndexController@getTab');
        $api->get('user_list', 'App\Http\Controllers\IndexController@getUserList');
        $api->post('add', 'App\Http\Controllers\IndexController@add');
        $api->post('del', 'App\Http\Controllers\IndexController@del');
        $api->post('total', 'App\Http\Controllers\IndexController@total');
    });

});
