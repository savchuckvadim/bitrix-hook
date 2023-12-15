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
    $data = $request->all();
    $document_id = $request['document_id'];
    $auth = $request['auth'];
    $company_id = $request['company_id'];
    $deadline = $request['deadline'];
    $created = $request['created'];
    $responsible = $request['responsible'];
    Log::info('LOG', $request->all());
    Log::info('DOC_ID', $document_id);
    Log::info('AUTH', $auth);
    Log::info('COMP_ID', ['company_id' => $company_id]);
    Log::info('deadline', ['date' => $deadline]);
    Log::info('CREATED_ID', ['created' => $created]);

    Log::info('responsible', ['responsible' => $responsible]);

    $nowDate = now();
    
    try {


        $response = Http::get('https://' . env('BITRIX_DOMAIN') . '/rest/' . env('BITRIX_REST_VERSION') . '/' . env('WEB_HOOK') . '/tasks.task.add.json', [
            'fields' => [
                'TITLE' => 'task for test', 
                'RESPONSIBLE_ID' => $responsible,
                'GROUP_ID' => env('BITRIX_CALLING_GROUP_ID'),
                'CREATED_BY' => $created, //- постановщик;
                'CREATED_DATE' => $nowDate, // - дата создания;
                'DEADLINE' => $deadline //- крайний срок;
            ]
        ]);
        Log::info('response ', ['response ' => $response ]);
        // Возвращаем ответ как ответ сервера Laravel
        return $response;
    } catch (\Throwable $th) {
        return response(['result' => 'error']);
    }
});
