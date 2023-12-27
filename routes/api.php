<?php

use App\Http\Controllers\APIBitrixController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\APIController;
use App\Http\Controllers\APIOnlineController;
use App\Http\Controllers\PortalController;
use App\Http\Controllers\ReactAppController;
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



//FRONTEND TESTING
Route::get('front', [App\Http\Controllers\HomeController::class, 'index']);



Route::post('/task', function (Request $request) {

    $created = $request['created'];
    $responsible = $request['responsible'];

    Log::info('LOG', $request->all());

    $partsCreated = explode("_", $created);
    $partsResponsible = explode("_", $responsible);

    $auth = $request['auth'];
    $domain = $auth['domain'];
    $companyId = $request['company_id'];
    $createdId = $partsCreated[1];
    $responsibleId = $partsResponsible[1];
    $deadline = $request['deadline'];
    $name = $request['name'];
    $crm = $request['crm'];

    return APIBitrixController::createTask($domain, $companyId, $createdId, $responsibleId, $deadline, $name, $crm);
});



Route::post('/taskfields', function (Request $request) {

    $domain = env('APRIL_BITRIX_DOMAIN');
    $secret = env('APRIL_WEB_HOOK');
    $restVersion = env('APRIL_BITRIX_REST_VERSION');

    // Log::info('Environment Variables', [
    //     'BITRIX_DOMAIN' => $domain,
    //     'BITRIX_REST_VERSION' => $restVersion,
    //     'WEB_HOOK' => $secret
    // ]);


    try {

        $getFields = Http::get('https://' . $domain . '/rest/' . $restVersion . '/' . $secret . '/tasks.task.getFields.json', []);
        Log::info('TASK_FIELDS ', ['fields ' => $getFields['result']]);

        return  response([
            'result' => ['task fields' => $getFields['result']],
            'message' => 'success'
        ]);
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

Route::post('/calling', function (Request $request) {

    $domain = env('APRIL_BITRIX_DOMAIN');
    $secret = env('APRIL_WEB_HOOK');
    $restVersion = env('APRIL_BITRIX_REST_VERSION');
    $durationTop = $request['durationTop'];
    Log::info('Environment Variables', [
        'BITRIX_DOMAIN' => $domain,
        'BITRIX_REST_VERSION' => $restVersion,
        'WEB_HOOK' => $secret
    ]);


    try {


        $response = Http::get('https://' . $domain . '/rest/' . $restVersion . '/' . $secret . '/voximplant.statistic.get.json', [
            "FILTER" => [
                ">CALL_DURATION" => 60,
                ">CALL_START_DATE" => "2023-12-01T00:00:00+00:00",
                // "<CALL_START_DATE" =>  "2024-08-23T00:00:00+00:00"
                // PORTAL_USER_ID
            ]
        ]);
        Log::info('response ', ['response ' => $response]);

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
Route::post('/lists', function (Request $request) {

    $domain = env('APRIL_BITRIX_DOMAIN');
    $secret = env('APRIL_WEB_HOOK');
    $restVersion = env('APRIL_BITRIX_REST_VERSION');

    Log::info('Environment Variables', [
        'BITRIX_DOMAIN' => $domain,
        'BITRIX_REST_VERSION' => $restVersion,
        'WEB_HOOK' => $secret
    ]);


    try {

        $listsfields = Http::get('https://' . $domain . '/rest/' . $restVersion . '/' . $secret . '/lists.field.get.json', [
            'IBLOCK_TYPE_ID' => 'lists',
            'IBLOCK_ID' => '86',

        ]);
        Log::info('listsfields ', ['listsfields ' => $listsfields['result']]);
        $response = Http::get('https://' . $domain . '/rest/' . $restVersion . '/' . $secret . '/lists.element.get.json', [
            'IBLOCK_TYPE_ID' => 'lists',
            'IBLOCK_ID' => '86',
            'FILTER' => [
                '>=DATE_CREATE' => '01.01.2023 00:00:00',
                '<=DATE_CREATE' => '01.01.2024 23:59:59',
            ]


        ]);
        Log::info('response ', ['response ' => $response]);

        return  response([
            'result' => ['response' => $response['result'], 'listsfields' => $listsfields['result']],
            'message' => 'success'
        ]);
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
    $smart = null;
    // $novosibirskTime = Carbon::createFromFormat('d.m.Y H:i:s', $deadline, 'Asia/Novosibirsk');

    // $moscowTime = $novosibirskTime->setTimezone('Europe/Moscow');
    // $moscowTime = $moscowTime->format('Y-m-d H:i:s');
    // Log::info('novosibirskTime', ['novosibirskTime' => $novosibirskTime]);
    // Log::info('moscowTime', ['moscowTime' => $moscowTime]);
    try {
        if ($company_id) {
            //COMPANY
            $getCompany = Http::get('https://' . $domain . '/rest/' . $restVersion . '/' . $secret . '/crm.company.get.json', [
                'ID' => $company_id,


            ]);
            Log::info('COMPANY ', ['getCompany ' => $getCompany]);
            Log::info('COMPANY ', ['company_id ' => $company_id]);


            //SMART STATUS
            $responseStatusSmart = Http::get('https://' . $domain . '/rest/' . $restVersion . '/' . $secret . '/crm.status.list.json', [
                'entityTypeId' => 156,

            ]);
            Log::debug('STATUS ', ['responseStatusSmart ' => $responseStatusSmart]);

            //SMART
            $responsetrySmart = Http::get('https://' . $domain . '/rest/' . $restVersion . '/' . $secret . '/crm.item.list.json', [
                'entityTypeId' => 156,
                'select' => ['*'],
                'filter' => [
                    "!=stageId" => ["DT132_17:SUCCESS", "DT132_17:FAIL"],
                    // '0' => [

                    //     "=ufCrm6_1697099643" => $company_id
                    // ]
                ]

            ]);
            Log::info('SMART ', ['trySmart ' => $responsetrySmart]);
            if ($responsetrySmart) {
                if ($responsetrySmart['result']) {
                    if ($responsetrySmart['result']['items']) {
                        Log::info('SMART ', ['ITEMS ' => $responsetrySmart['result']['items']]);
                        Log::info('SMART ', ['TOTAL ' => $responsetrySmart['result']['total']]);
                        if ($responsetrySmart['result']['items'][0]) {
                            $smart = $responsetrySmart['result']['items'][0];
                            Log::info('SMART ', ['trySmart ' => $smart]);
                        }
                    }
                }
            }

            if ($smart) {
                //update smart
            } else {
            }

            return $responsetrySmart;
        }
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




Route::post('/smart/categories', function (Request $request) {


    // entityTypeId - id смарт процесса как сущности
    // domain for get keys

    // companyId	UF_CRM_6_1697099643
    $document_id = $request['document_id'];
    $ownerType = $request['ownerType'];   // L C D T9c
    $domain = $request['domain'];   // L C D T9c

    Log::info('REQUEST', $request->all());
    Log::info('domain', ['domain' => $domain]);


    return APIBitrixController::getSmartStages($domain);
});
















Route::post('/placement', function (Request $request) {
    $controller = new ReactAppController;
    return $controller->index();
});

Route::get('/placement', function (Request $request) {
    $controller = new ReactAppController;
    return $controller->index();
});
