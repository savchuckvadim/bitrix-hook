<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\APIController;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/login', [APIController::class, 'login']);
Route::post('/register', [APIController::class, 'register']);
Route::post('/forget-password', [APIController::class, 'forget_pass']);
Route::post('/reset-password', [APIController::class, 'reset_pass']);

Route::post('/test', function (Request $request) {
   
    try {
        $data = $request->all();
        Log::info('Webhook received', $request->all());
        Log::info('Webhook', $data['company_id']);
        $response = Http::get('https://'.env('BITRIX_DOMAIN').'/rest/'.env('BITRIX_REST_VERSION').'/'.env('WEB_HOOK').'/crm.deal.add.json', [
            // Дополнительные параметры запроса, если необходимо
        ]);

        // Возвращаем ответ как ответ сервера Laravel
        return $response;
    } catch (\Throwable $th) {
        return response(['result' => 'error']);
    }
});
