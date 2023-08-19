<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

//nodeMCU
Route::get('sensor/data', "iotC@data");
Route::get('sensor/logs', "iotC@logs");
Route::post('sensor/data/kirim', "iotC@post");
Route::get('coba', 'iotC@coba');


//android
Route::post('login', "iotC@login");
Route::get('kendali/{token_sensor}/data', "iotC@dataKendali");
Route::post('kendali/{token_sensor}/sensor/{ket}', "iotC@kendali");
Route::get('kendali/{token_sensor}/status', "iotC@status");
Route::post('kendali/{token_sensor}/status', "iotC@kendaliStatus");
Route::get('kendali/{token_sensor}/pengaturan', "iotC@pengaturan");
Route::post('kendali/{token_sensor}/pengaturan', "iotC@editPengaturan");
