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
    $createdId = $partsCreated[1];
    $responsibleId = $partsResponsible[1];


    $auth = $request['auth'];
    $domain = $auth['domain'];
    $companyId = $request['company_id'];

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
    Log::info('calling ', ['request ' => $request->all()]);
    $domain = $request['domain'];
    $filters = $request['filters'];
    $callStartDateFrom = $filters['callStartDateFrom'];
    $callStartDateTo = $filters['callStartDateTo'];
    $secret = env('APRIL_WEB_HOOK');
    $restVersion = env('APRIL_BITRIX_REST_VERSION');
    $durationTop = $request['durationTop'];

    return APIBitrixController::getCalling($domain, $callStartDateFrom, $callStartDateTo);
    // Log::info('Environment Variables', [
    //     'BITRIX_DOMAIN' => $domain,
    //     'BITRIX_REST_VERSION' => $restVersion,
    //     'WEB_HOOK' => $secret
    // ]);


    // try {


    //     $response = Http::get('https://' . $domain . '/rest/' . $restVersion . '/' . $secret . '/voximplant.statistic.get.json', [
    //         "FILTER" => [
    //             ">CALL_DURATION" => 60,
    //             ">CALL_START_DATE" => $callStartDateFrom,
    //             "<CALL_START_DATE" =>  $callStartDateTo
    //             // PORTAL_USER_ID
    //         ]
    //     ]);
    //     Log::info('response ', ['response ' => $response]);

    //     // Возвращаем ответ как ответ сервера Laravel
    //     return $response;
    // } catch (\Throwable $th) {
    //     Log::error('Exception caught', [
    //         'message'   => $th->getMessage(),
    //         'file'      => $th->getFile(),
    //         'line'      => $th->getLine(),
    //         'trace'     => $th->getTraceAsString(),
    //     ]);
    //     return response([
    //         'result' => 'error',
    //         'message' => $th->getMessage()
    //     ]);
    // }
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
    $auth = $request['auth'];
    $domain = $auth['domain'];

    Log::info('REQUEST', $request->all());
    Log::info('domain', ['domain' => $domain]);


    return APIBitrixController::getSmartStages($domain);
});

Route::post('/install/smart/', function (Request $request) {

    $auth = $request['auth'];
    $domain = $auth['domain'];


    return APIBitrixController::installSmart($domain);
});




Route::post('/update/smart/', function (Request $request) {

    $auth = $request['auth'];
    $domain = $auth['domain'];


    $created = $request['created'];
    $responsible = $request['responsible'];

    Log::info('LOG', $request->all());

    $partsCreated = explode("_", $created);
    $partsResponsible = explode("_", $responsible);
    $createdId = $partsCreated[1];
    $responsibleId = $partsResponsible[1];


    $auth = $request['auth'];
    $domain = $auth['domain'];
    $crm = $request['document_id'][2];
    // $companyId = $request['company_id'];

    $deadline = $request['deadline'];
    $name = $request['name'];
    $currentSmartId = $request['id'];
    $logData = [
        'crm' => $crm,
        'currentSmartId' => $currentSmartId,
        'domain' => $domain,
        'deadline' => $deadline,
        'createdId' => $createdId,
        'responsibleId' => $responsibleId,
        'name' => $name,
    ];

    // Log::info('REQUEST', $request->all());
    Log::info('REQUEST DATA', $logData);

    try {


        // APIBitrixController::getSmartStages($domain);

        //portal and keys
        $portal = PortalController::getPortal($domain);

        $portal = $portal['data'];
        Log::info('portalData', ['portal' => $portal]);

        //base hook
        $webhookRestKey = $portal['C_REST_WEB_HOOK_URL'];
        $hook = 'https://' . $domain  . '/' . $webhookRestKey;



        //user and time
        $createdTime = Carbon::createFromFormat('d.m.Y H:i:s', $deadline, 'Asia/Novosibirsk');
        $methodGetUser = '/user.get.json';
        $url = $hook . $methodGetUser;
        $userData = [
            'id' => $createdId
        ];
        $userResponse =  $responseData = Http::get($url, $userData);
        Log::info('RESPONSIBLE', ['userResponse' => $userResponse]);
        // if ($userResponse && $userResponse['result'] && $userResponse['result'][0]) {
        //     $userTimeZone =  $userResponse['result'][0]['TIME_ZONE'];
        //     if ($userTimeZone) {
        //         $createdTime = Carbon::createFromFormat('d.m.Y H:i:s', $deadline, $userTimeZone);
        //     }
        // }

        // $nowDate = now();
        // $novosibirskTime = Carbon::createFromFormat('d.m.Y H:i:s', $deadline, 'Asia/Novosibirsk');
        $moscowTime = $createdTime->setTimezone('Europe/Moscow');
        $moscowTime = $moscowTime->format('Y-m-d H:i:s');
        Log::info('novosibirskTime', ['novosibirskTime' => $createdTime]);
        Log::info('moscowTime', ['moscowTime' => $moscowTime]);


        //smart update

        //get smart
        //  $methodSmartUpdate = '/crm.item.update.json';
        // $methodSmartGet = '/crm.item.get.json';
        // $url = $hook . $methodSmartGet;
        // $smartGetData =  [
        //     'id' => $currentSmartId,
        //     'entityTypeId' => env('BITRIX_SMART_MAIN_ID'),
        // 'fields' => [
        //     'TITLE' => 'Холодный обзвон  ' . $name . '  ' . $deadline,
        //     'RESPONSIBLE_ID' => $responsibleId,
        //     'GROUP_ID' => env('BITRIX_CALLING_GROUP_ID'),
        //     'CHANGED_BY' => $createdId, //- постановщик;
        //     'CREATED_BY' => $createdId, //- постановщик;
        //     'CREATED_DATE' => $nowDate, // - дата создания;
        //     'DEADLINE' => $moscowTime, //- крайний срок;
        //     'UF_CRM_TASK' => ['T9c_' . $crm],
        //     'ALLOW_CHANGE_DEADLINE' => 'N',
        //     'DESCRIPTION' => $description
        // ]
        // ];

        // $responseGetData = Http::get($url, $smartGetData);
        // Log::info('responseGetData', ['responseGetData' => $responseGetData]);

        //update smart
        //  $methodSmartUpdate = '/crm.item.update.json';
        $methodSmartUpdate = '/crm.item.update.json';
        $url = $hook . $methodSmartUpdate;
        $smartUpdateData =  [
            'id' => $currentSmartId,
            'entityTypeId' => env('BITRIX_SMART_MAIN_ID'),
            'fields' => [
                // 'TITLE' => 'Холодный обзвон  ' . $name . '  ' . $deadline,
                'assignedById' => $responsibleId,
                // 'GROUP_ID' => env('BITRIX_CALLING_GROUP_ID'),
                // 'CHANGED_BY' => $createdId, //- постановщик;
                // 'CREATED_BY' => $createdId, //- постановщик;
                // 'CREATED_DATE' => $nowDate, // - дата создания;
                // 'DEADLINE' => $moscowTime, //- крайний срок;
                // 'UF_CRM_TASK' => ['T9c_' . $crm],
                // 'ALLOW_CHANGE_DEADLINE' => 'N',
                // 'DESCRIPTION' => $description
                "ufCrm_1696580389" => $deadline,
                "ufCrm_6_1702453779" => $createdId,
                "ufCrm_6_1702652862" => $responsibleId,
                "ufCrm_6_1700645937" => $name,

                "stageId" => 'DT156_14:NEW',


            ]
        ];

        $responseData = Http::get($url, $smartUpdateData);
        Log::info('responseData', ['responseData' => $responseData]);
    } catch (\Throwable $th) {
        Log::error('ERROR: Exception caught', [
            'message'   => $th->getMessage(),
            'file'      => $th->getFile(),
            'line'      => $th->getLine(),
            'trace'     => $th->getTraceAsString(),
        ]);
        return APIOnlineController::getResponse(1, $th->getMessage(), null);
    }
});






Route::post('/get/report/', function (Request $request) {

    $auth = $request['auth'];
    $domain = $auth['domain'];


    return APIBitrixController::installSmart($domain);
});






Route::post('/placement', function (Request $request) {
    $controller = new ReactAppController;
    return $controller->index();
});

Route::get('/placement', function (Request $request) {
    $controller = new ReactAppController;
    return $controller->index();
});




Route::post('/bind', function (Request $request) {
    //     http://portal.bitrix24.com/rest/placement.bind/?access_token=sode3flffcmv500fuagrprhllx3soi72
    // 	&PLACEMENT=CRM_CONTACT_LIST_MENU
    // 	&HANDLER=http%3A%2F%2Fwww.applicationhost.com%2Fplacement%2F
    // 	&TITLE=Тестовое приложение
    // HTTP/1.1 200 OK
    // {
    // 	"result": true
    // }
    // $portal = PortalController::getPortal($domain);
    //
    // $resultCallings = [];
    try {
        //CATEGORIES
        // $webhookRestKey = $portal['data']['C_REST_WEB_HOOK_URL'];
        // $hook = 'https://' . $domain  . '/' . $webhookRestKey;
        // $actionUrl = '/voximplant.statistic.get.json';
        // $url = $hook . $actionUrl;
        Log::info('bind', ['bind' => $request->all()]);
    } catch (\Throwable $th) {
        return APIOnlineController::getError(

            'error callings ' . $th->getMessage(),
            [
                // 'result' => $resultCallings,

                'error callings ' . $th->getMessage(),
            ]
        );
    }
});


Route::post('/taskevent', function (Request $request) {
    //     http://portal.bitrix24.com/rest/placement.bind/?access_token=sode3flffcmv500fuagrprhllx3soi72
    // 	&PLACEMENT=CRM_CONTACT_LIST_MENU
    // 	&HANDLER=http%3A%2F%2Fwww.applicationhost.com%2Fplacement%2F
    // 	&TITLE=Тестовое приложение
    // HTTP/1.1 200 OK
    // {
    // 	"result": true
    // }
    $actionUrl = '/placement.bind.json';
    $domain = $request['auth']['domain'];
    $member_id = $request['auth']['member_id'];
    $application_token = $request['auth']['application_token'];

    $portal = PortalController::getPortal($domain);


    $hook = 'https://' . $domain . '/' . $member_id . '/' . $application_token . $actionUrl;
    $handler = 'https://april-server/test/placement.php';

    Log::info('taskevent', ['request' => $request->all()]);
    try {

        $webhookRestKey = $portal['data']['C_REST_WEB_HOOK_URL'];
        // $hook = 'https://' . $domain  . '/' . $webhookRestKey;

        // $url = $hook . $actionUrl;
        $data = [
            'PLACEMENT' => 'CRM_ACTIVITY_LIST_MENU',
            'HANDLER' => 'https://april-server/test/placement.php',
            'LANG_ALL' => [
                'en' => [
                    'TITLE' => 'Get Offer app',
                    'DESCRIPTION' => 'App helps Garant employees prepare commercial documents and collect sales funnel statistics',
                    'GROUP_NAME' => 'Garant',
                ],
                'ru' => [
                    'TITLE' => 'КП Гарант',
                    'DESCRIPTION' => 'Приложение помогает сотрудникам Гарант составлять коммерческие документы и собирать статистику воронки продаж',
                    'GROUP_NAME' => 'Гарант',
                ],
            ],
        ];
        // $headers = [
        //     'Authorization' => 'Basic ' . base64_encode($member_id . ':' . $application_token),
        // ];
        $response = Http::get($hook, $data);
        Log::info('taskevent', ['response' => $response]);
        return $response;
    } catch (\Throwable $th) {
        Log::info('taskevent', ['request' => $request->all()]);
        return APIOnlineController::getError(

            'error callings ' . $th->getMessage(),
            [
                // 'result' => $resultCallings,

                'error callings ' . $th->getMessage(),
            ]
        );
    }
});
