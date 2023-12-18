<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\APIController;
use Illuminate\Support\Carbon;
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

Route::post('/task', function (Request $request) {
    $data = $request->all();
    $document_id = $request['document_id'];
    $auth = $request['auth'];
    $company_id = $request['company_id'];
    $deadline = $request['deadline'];
    $created = $request['created'];
    $responsible = $request['responsible'];
    $name = $request['name'];
    $crm = $request['crm'];
    Log::info('LOG', $request->all());
    Log::info('DOC_ID', $document_id);
    Log::info('AUTH', $auth);
    Log::info('COMP_ID', ['company_id' => $company_id]);
    Log::info('deadline', ['date' => $deadline]);
    Log::info('CREATED_ID', ['created' => $created]);
    Log::info('TITLE', ['created' => $name]);
    Log::info('responsible', ['responsible' => $responsible]);
    Log::info('crm', ['crm' => $crm]);
    $partsCreated = explode("_", $created);
    $partsResponsible = explode("_", $responsible);
    // Извлечение ID (предполагается, что ID всегда находится после "user_")
    $createdId = $partsCreated[1];
    $responsibleId = $partsResponsible[1];
    $nowDate = now();
    $domain = env('BITRIX_DOMAIN');
    $secret = env('WEB_HOOK');
    $restVersion = env('BITRIX_REST_VERSION');

    Log::info('Environment Variables', [
        'BITRIX_DOMAIN' => $domain,
        'BITRIX_REST_VERSION' => $restVersion,
        'WEB_HOOK' => $secret
    ]);
    $novosibirskTime = Carbon::createFromFormat('d.m.Y H:i:s', $deadline, 'Asia/Novosibirsk');

    $moscowTime = $novosibirskTime->setTimezone('Europe/Moscow');
    $moscowTime = $moscowTime->format('Y-m-d H:i:s');
    Log::info('novosibirskTime', ['novosibirskTime' => $novosibirskTime]);
    Log::info('moscowTime', ['moscowTime' => $moscowTime]);
    try {


        $response = Http::get('https://' . $domain . '/rest/' . $restVersion . '/' . $secret . '/tasks.task.add.json', [
            'fields' => [
                'TITLE' => 'Холодный обзвон  ' . $name . '  ' . $deadline,
                'RESPONSIBLE_ID' => $responsibleId,
                'GROUP_ID' => env('BITRIX_CALLING_GROUP_ID'),
                'CHANGED_BY' => $createdId, //- постановщик;
                'CREATED_BY' => $createdId, //- постановщик;
                'CREATED_DATE' => $nowDate, // - дата создания;
                'DEADLINE' => $moscowTime, //- крайний срок;
                'UF_CRM_TASK' => ['T9c_' . $crm],
                'ALLOW_CHANGE_DEADLINE'=> 'N'
            ]
        ]);
        Log::info('response ', ['response ' => $response]);
        $getFields = Http::get('https://' . $domain . '/rest/' . $restVersion . '/' . $secret . '/tasks.task.getFields.json', [
        ]);
        Log::info('TASK_FIELDS ', ['fields ' => $getFields]);
        // Возвращаем ответ как ответ сервера Laravel
        return $response;
    } catch (\Throwable $th) {
        Log::error('Exception caught', [
            'message'   => $th->getMessage(),
            'file'      => $th->getFile(),
            'line'      => $th->getLine(),
            'trace'     => $th->getTraceAsString(),
        ]);
        return response([
            'result' => 'error',
            'message' => $th->getMessage()
        ]);
    }
});


Route::post('/smart', function (Request $request) {
 
    // companyId	UF_CRM_6_1697099643
    $document_id = $request['document_id'];
    $ownerType = $request['ownerType'];   // L C D T9c
    $auth = $request['auth'];
    $company_id = $request['company_id'];

    Log::info('AUTH', $auth);
    Log::info('COMP_ID', ['company_id' => $company_id]);
    // Log::info('deadline', ['date' => $deadline]);
    // Log::info('CREATED_ID', ['created' => $created]);
    // Log::info('TITLE', ['created' => $name]);
    // Log::info('responsible', ['responsible' => $responsible]);
    // $partsCreated = explode("_", $created);
    // $partsResponsible = explode("_", $responsible);
    // Извлечение ID (предполагается, что ID всегда находится после "user_")
    // $createdId = $partsCreated[1];
    // $responsibleId = $partsResponsible[1];
    $nowDate = now();
    $domain = env('BITRIX_DOMAIN');
    $secret = env('WEB_HOOK');
    $restVersion = env('BITRIX_REST_VERSION');

    Log::info('Environment Variables', [
        'BITRIX_DOMAIN' => $domain,
        'BITRIX_REST_VERSION' => $restVersion,
        'WEB_HOOK' => $secret
    ]);
    // $novosibirskTime = Carbon::createFromFormat('d.m.Y H:i:s', $deadline, 'Asia/Novosibirsk');

    // $moscowTime = $novosibirskTime->setTimezone('Europe/Moscow');
    // $moscowTime = $moscowTime->format('Y-m-d H:i:s');
    // Log::info('novosibirskTime', ['novosibirskTime' => $novosibirskTime]);
    // Log::info('moscowTime', ['moscowTime' => $moscowTime]);
    try {
        $getCompany = Http::get('https://' . $domain . '/rest/' . $restVersion . '/' . $secret . '/crm.company.get.json', [
            'ID' => $company_id,


        ]);
        Log::info('COMPANY ', ['getCompany ' => $getCompany]);
        $trySmart = Http::get('https://' . $domain . '/rest/' . $restVersion . '/' . $secret . '/crm.item.list.json', [
            'entityTypeId' => 9,
            'select' => ['*'],
            'filter' => ["!=ufCrm24_1616150749" => ""]

        ]);

        Log::info('SMART ', ['trySmart ' => $trySmart]);

        // $response = Http::get('https://' . $domain . '/rest/' . $restVersion . '/' . $secret . '/tasks.task.add.json', [
        //     'fields' => [
        //         // 'TITLE' => 'Холодный обзвон' . $name . ' ' . $deadline,
        //         // 'RESPONSIBLE_ID' => $responsibleId,
        //         'GROUP_ID' => env('BITRIX_CALLING_GROUP_ID'),
        //         // 'CREATED_BY' => $createdId, //- постановщик;
        //         'CREATED_DATE' => $nowDate, // - дата создания;
        //         // 'DEADLINE' => $moscowTime //- крайний срок;
        //     ]
        // ]);
        // Log::info('response ', ['response ' => $response]);
        // Возвращаем ответ как ответ сервера Laravel
        return $trySmart;
    } catch (\Throwable $th) {
        Log::error('Exception caught', [
            'message'   => $th->getMessage(),
            'file'      => $th->getFile(),
            'line'      => $th->getLine(),
            'trace'     => $th->getTraceAsString(),
        ]);
        return response([
            'result' => 'error',
            'message' => $th->getMessage()
        ]);
    }
});
