<?php

use App\Http\Controllers\APIBitrixController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\APIController;
use App\Http\Controllers\APIOnlineController;
use App\Http\Controllers\BitrixHookController;
use App\Http\Controllers\Front\Calling\ReportController;
use App\Http\Controllers\PortalController;
use App\Http\Controllers\ReactAppController;
use App\Models\Price;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use PhpParser\Node\Stmt\Return_;

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


//FONTEND CALLINGS...........................................................................

//для фронта звонки
// при инициализации фронта из компании запрашивает привязанный по компании-сотруднику 

Route::post('/smart/item', function (Request $request) {

    $companyId = $request['companyId'];
    $userId = $request['userId'];

    $domain = $request['domain'];


    $controller = new APIBitrixController();
    return $controller->getSmartItemCallingFront(
        $domain,
        $companyId,
        $userId,
    );
});


// при инициализации фронта из лида запрашивает привязанный по лиду-сотруднику
Route::post('smart/item/fromlead', function (Request $request) {



    $leadId = $request->input('leadId');
    $userId = $request->input('userId');
    $domain = $request->input('domain');



    $controller = new APIBitrixController();
    return $controller->getSmartItemCallingFrontFromLead(
        $domain,
        $leadId,
        $userId,
    );
});

//создание теплого звонка из
Route::post('/task/warm', function (Request $request) {

    //from cold
    // https://april-hook.ru/api/task?
    // company_id={{companyId}}&
    // deadline={{Запланировать звонок}}&
    // responsible={{Ответственный}}&
    // created={{Постановщик ХО}}&
    // name={{Обзвон}}&
    // crm={{ID}}
    //smart
    //placement
    $comment = null;
    $smart = null;
    $sale = null;
    $isOneMore = $request['isOneMore'];
    $companyId = null;
    $leadId = null;
    $type = null;
    if (isset($request['type'])) {
        $type = $request['type'];
    }
    if (isset($request['placement'])) {
        if (isset($request['placement']['placement']) && isset($request['placement']['options']['ID'])) {
            $placementType = $request['placement']['placement'];
            $currentEntityId = $request['placement']['options']['ID'];

            if (strpos($placementType, "LEAD") !== false) {
                $leadId = $currentEntityId;
            } else if (strpos($placementType, "COMPANY") !== false) {

                $companyId = $currentEntityId;
            }
        }
    }

    $created = $request['created'];
    $responsible = $request['responsible'];

    // Log::info('LOG', $request->all());

    $partsCreated = explode("_", $created);
    $partsResponsible = explode("_", $responsible);
    $createdId = $partsCreated[1];
    $responsibleId = $partsResponsible[1];


    $auth = $request['auth'];
    $domain = $auth['domain'];
    $companyId = $request['company_id'];

    $deadline = $request['deadline'];

    $name = $request['name'];
    //only from front calling
    if (
        isset($request['comment'])
        && isset($request['smart'])
        && isset($request['sale'])
    ) {
        $comment = $request['comment'];
        $smart = $request['smart'];
        $sale = $request['sale'];
    }

    Log::info('TEST', ['req' => $request->all()]);
    $controller = new APIBitrixController();
    return $controller->createTask(
        $type,
        $domain,
        $companyId,
        $leadId,
        $createdId,
        $responsibleId,
        $deadline,
        $name,
        $comment,
        // $crm,
        $smart,
        $sale,
        $isOneMore
    );
});

// презентация проведена
Route::post('/presentation/done', function (Request $request) {


    $company = null;
    $smart = null;
    $placement = null;
    $companyId  = null;
    $isUnplannedPresentation = false;
    $responsibleId = null;
    // $created = $request['created'];
    // $responsible = $request['responsible'];

    // // Log::info('LOG', $request->all());

    // $partsCreated = explode("_", $created);
    // $partsResponsible = explode("_", $responsible);
    // $createdId = $partsCreated[1];
    // $responsibleId = $partsResponsible[1];
    // Log::channel('telegram')->error('APRIL_HOOK', [
    //     'presentation Done' => $request->body(),

    // ]);
    if (isset($request['auth'])) {
        $auth = $request['auth'];
        if (isset($request['auth'])) {
            $domain = $auth['domain'];
        }
    }

    if (isset($request['company_id'])) {
        $companyId = $request['company_id'];
    }


    if (isset($request['placement'])) {
        $placement = $request['placement'];
    }
    if (isset($request['smart'])) {
        $smart = $request['smart'];
    }
    if (isset($request['company'])) {
        $company = $request['company'];
    }
    if (isset($request['isUnplannedPresentation'])) {
        $isUnplannedPresentation = $request['isUnplannedPresentation'];
    }

    if (isset($request['responsibleId'])) {
        $responsible = $request['responsibleId'];
        $partsResponsible = explode("_", $responsible);
        $responsibleId = $partsResponsible[1];
    }
    // Log::channel('telegram')->error('APRIL_HOOK', [
    // 'done' => [
    //         'domain' => $domain,
    //         'companyId' => $companyId,
    //         'placement' => $placement,
    //         'smart' => $smart,
    //         'smart' => $smart,
    //     ]
    // ]);

    $controller = new APIBitrixController();
    return $controller->presentationDone(
        $domain,
        $companyId,
        $responsibleId,
        $placement,
        $company,
        $smart,
        $isUnplannedPresentation,
    );
});


//Отказ
Route::post('/task/fail', function (Request $request) {



    $smart = null;

    $responsible = $request['responsible'];
    $partsResponsible = explode("_", $responsible);

    $responsibleId = $partsResponsible[1];


    $auth = $request['auth'];
    $domain = $auth['domain'];
    $companyId = $request['company_id'];




    if (isset($request['smart'])) {

        $smart = $request['smart'];
    }


    $controller = new APIBitrixController();
    return $controller->failTask(
        $domain,
        $companyId,
        $responsibleId,
        $smart,

    );
});


// ..........................................................................................



// ............................... FULL CALING FRONT

Route::post('/full', function (Request $request) {
    return ReportController::eventReport($request);
});


Route::post('/activity/test', function (Request $request) {
    Log::info('', [
        'request' => $request->all()
    ]);
    Log::channel('telegram')->info('', [
        'request' => $request->all()
    ]);
});


Route::post('/pres/count', function (Request $request) {
    return ReportController::getPresCounts($request);
});

// новй холодный звонка из Откуда Угодно
Route::post('cold', function (Request $request) {

    //from anywhere
    // https: //april-hook.ru/api/cold?
    // created={=Template:Parameter2}&
    // responsible={=Template:Parameter3}&
    // deadline={=Template:Parameter1}&
    // name={=Template:Parameter4}&
    // entity_id={{ID}}
    // &entity_type=smart | company | lead
    // isOlyDeal ??
    // Log::info('APRIL_HOOK ', ['cold' => 'yo']);
    // Log::info('APRIL_HOOK cold', ['data' => $request->all()]);
    $controller = new BitrixHookController();
    return $controller->getColdCall(
        $request
    );
});







// Бизнес процесс битрикс ............................................
//........................................ Инициация холодного обзвона 
//.........................................Из Лидов Компаний Смарт-процессов
//.........................................Во время инициализации холодного звонка должен проверяться существует или нет смарт
//.........................................Передвигать смарт в ХО
// инициализация холодного звонка из Лид
Route::post('/coldlead/smart/init', function (Request $request) {

    //from cold
    // https://april-hook.ru/api/task?
    // company_id={{companyId}}&
    // deadline={{Запланировать звонок}}&
    // responsible={{Ответственный}}&
    // created={{Постановщик ХО}}&
    // name={{Обзвон}}&
    // crm={{ID}} || null


    //from company
    // https: //april-hook.ru/api/cold/smart/init?
    // created={=Template:Parameter2}&
    // responsible={=Template:Parameter3}&
    // deadline={=Template:Parameter1}&
    // name={=Template:Parameter4}&
    // id={{ID}}
    // &company_id={{Компания}}

    Log::info('COLD FROM LEAD', ['log' => 'from bitrix']);
    Log::info('COLD FROM LEAD', ['log' => $request->all()]);
    $comment = null;
    $smart = null;
    $sale = null;
    $createdId =  null;
    $smartId =  null;
    $responsibleId = null;
    // lidId UF_CRM_7_1697129037
    // lidIds UF_CRM_7_1697129081
    // Log::channel('telegram')->error('APRIL_HOOK', [

    //     'coldlead/smart/init' => $request->all()

    // ]);
    try {
        if (isset($request['created'])) {
            $created = $request['created'];
            $partsCreated = explode("_", $created);
            if (isset($partsCreated[1])) {
                $createdId = $partsCreated[1];
            }
        }

        if (isset($request['smart_id'])) {
            $smartId = $request['smart_id'];
        }

        if (isset($request['responsible'])) {
            $responsible = $request['responsible'];
            $partsResponsible = explode("_", $responsible);
            if (isset($partsResponsible[1])) {
                $responsibleId = $partsResponsible[1];
            } else {
                Log::channel('telegram')->error('APRIL_HOOK', [

                    'coldlead/smart/init' => 'no responsiblie',
                    ' $responsible' =>  $responsible
                    // 'btrx response' => $response['error_description']

                ]);
            }
        }





        $auth = $request['auth'];
        $domain = $auth['domain'];
        $companyId = null;
        // if (isset($request['company_id'])) {

        //     $companyId = $request['company_id'];
        // }

        $leadId = $request['lead_id'];
        $deadline = $request['deadline'];
        // $crm = $request['crm'];
        $name = $request['name'];
        //only from front calling
        // if (
        //     isset($request['comment'])
        //     && isset($request['smart'])
        //     && isset($request['smart'])
        // ) {
        //     $comment = $request['comment'];
        //     $smart = $request['smart'];
        //     $sale = $request['sale'];
        // }
        Log::info('COLD FROM LEAD', ['log' => $leadId]);

        $controller = new APIBitrixController();
        return $controller->initialCold(
            // $type,
            $domain,
            $companyId,
            $leadId,
            $createdId,
            $responsibleId,
            $deadline,
            $name,
            $smartId,
            // $comment,
            // $crm,
            // $smart,
            // $sale
        );
    } catch (\Throwable $th) {
        $errorMessages =  [
            'message'   => $th->getMessage(),
            'file'      => $th->getFile(),
            'line'      => $th->getLine(),
            'trace'     => $th->getTraceAsString(),
        ];
        Log::error('ROUTE ERROR COLD: Exception caught',  $errorMessages);
        Log::info('error COLD', ['error' => $th->getMessage()]);
    }
});

// инициализация холодного звонка из Компании
Route::post('/cold/smart/init', function (Request $request) {

    //from cold
    // https://april-hook.ru/api/task?
    // company_id={{companyId}}&
    // deadline={{Запланировать звонок}}&
    // responsible={{Ответственный}}&
    // created={{Постановщик ХО}}&
    // name={{Обзвон}}&
    // crm={{ID}} || null


    //from company
    // https: //april-hook.ru/api/cold/smart/init?
    // created={=Template:Parameter2}&
    // responsible={=Template:Parameter3}&
    // deadline={=Template:Parameter1}&
    // name={=Template:Parameter4}&
    // id={{ID}}
    // &company_id={{Компания}}

    $comment = null;
    $smart = null;
    $sale = null;
    $createdId =  null;
    $smartId =  null;
    $leadId = null;
    try {
        //     Log::channel('telegram')->error('APRIL_HOOK', [

        //         'deadline' => $request['deadline'],
        //         // 'название обзвона' => $name,
        //         // 'companyId' => $companyId,
        //         // 'domain' => $domain,
        //         // 'responsibleId' => $responsibleId,
        //         // 'btrx response' => $response['error_description']

        // ]);

        if (isset($request['created'])) {
            $created = $request['created'];
            $partsCreated = explode("_", $created);
            $createdId = $partsCreated[1];
        }

        if (isset($request['smart_id'])) {
            $smartId = $request['smart_id'];
        }


        $responsible = $request['responsible'];
        $partsResponsible = explode("_", $responsible);

        $responsibleId = $partsResponsible[1];


        $auth = $request['auth'];
        $domain = $auth['domain'];
        $companyId = $request['company_id'];

        if (isset($request['lead_id'])) {
            $leadId = $request['lead_id'];
        }

        $deadline = $request['deadline'];
        // $crm = $request['crm'];
        if (isset($request['name'])) {
            $name = $request['name'];
        }

        // $name = $request['name'];
        //only from front calling
        // if (
        //     isset($request['comment'])
        //     && isset($request['smart'])
        //     && isset($request['smart'])
        // ) {
        //     $comment = $request['comment'];
        //     $smart = $request['smart'];
        //     $sale = $request['sale'];
        // }

        Log::channel('telegram')->error('APRIL_HOOK domain', ['domain' => $domain]);

        $controller = new APIBitrixController();
        return $controller->initialCold(
            // $type,
            $domain,
            $companyId,
            $leadId,
            $createdId,
            $responsibleId,
            $deadline,
            $name,
            $smartId,
            // $comment,
            // $crm,
            // $smart,
            // $sale
        );
    } catch (\Throwable $th) {
        $errorMessages =  [
            'message'   => $th->getMessage(),
            'file'      => $th->getFile(),
            'line'      => $th->getLine(),
            'trace'     => $th->getTraceAsString(),
        ];
        Log::error('ROUTE ERROR COLD: Exception caught',  $errorMessages);
        Log::info('error COLD', ['error' => $th->getMessage()]);
    }
});



//test activity hook
// Route::post('/activity', function (Request $request) {
//     $requestData = $request->all();
//     $domain = $requestData['auth']['domain'];
//     $activityId = $requestData['data']['FIELDS'];
//     $portal = PortalController::getPortal($domain);

//     $portal = $portal['data'];

//     $smart = $portal['bitrixSmart'];

//     $method = 'crm.activity.get';
//     // $method = 'crm.enum.activitytype';


//     $webhookRestKey = $portal['C_REST_WEB_HOOK_URL'];
//     $hook = 'https://' . $domain  . '/' . $webhookRestKey. '/'. $method;
//     $smartFieldsResponse = Http::get($hook, $activityId);
//     $responseData = APIBitrixController::getBitrixRespone($smartFieldsResponse, 'activityTest');


//     Log::channel('telegram')->error('APRIL_HOOK', [
//         'activity' => [
//             'responseData' => $responseData,

//         ]
//     ]);
// });


//создание задачи ХО hook из Битрикс смарт-процессов когда карточка процесса 
//передвинута предыдущими хуками в категори ХЗ Запланироватьзвонок

Route::post('/task', function (Request $request) {


    // создает задачу холодного обзвона
    // вызывается из смарт-процесса
    // у смарт процесса может быть Компания, Лид, Сделка

    //from cold
    // https://april-hook.ru/api/task?
    // company_id={{companyId}}&          может быть null
    // deadline={{Дата холодного обзвона}}&
    // responsible={{Ответственный}}&
    // created=user_107&
    // name={{Название Холодного обзвона}}&
    // crm={{ID}}&  -- id элемента смарт процесса из которого пришел вызов
    // lid_id={{Лид}}                      может быть null


    $comment = null;
    $smart = null;
    $sale = null;

    $createdId = null;
    $responsibleId = null;
    $type = null;

    $companyId = null;
    $leadId = null;


    if (isset($request['type'])) {
        $type = $request['type'];
    }

    if (isset($request['created'])) {

        $created = $request['created'];
        $partsCreated = explode("_", $created);
        $createdId = $partsCreated[1];
    }

    if (isset($request['responsible'])) {

        $responsible = $request['responsible'];
        $partsResponsible = explode("_", $responsible);
        $responsibleId = $partsResponsible[1];
    }




    // Log::info('LOG', $request->all());





    $auth = $request['auth'];
    $domain = $auth['domain'];

    if (!empty($request['company_id'])) {
        $companyId = $request['company_id'];
    }
    if (!empty($request['lid_id'])) {
        $leadId = $request['lid_id'];
    }

    $deadline = $request['deadline'];
    $crm = $request['crm'];
    $name = $request['name'];
    //only from front calling
    // if (
    //     isset($request['comment'])
    //     && isset($request['smart'])
    //     && isset($request['smart'])
    // ) {
    //     $comment = $request['comment'];
    //     $smart = $request['smart'];
    //     $sale = $request['sale'];
    // }
    Log::channel('telegram')->error('APRIL_HOOK', [
        'cold task create api' => [
            'domain' => $domain,
            'deadline' => $deadline,
            'name' => $name,
            'companyId' => $companyId,
            'crm' => $crm,
        ]
    ]);

    $controller = new BitrixHookController();
    return $controller->createColdTask(
        $type,
        $domain,
        $companyId,
        $leadId,
        $createdId,
        $responsibleId,
        $deadline,
        $name,
        // $comment,
        $crm,
        // $smart,
        // $sale
    );
});

//....................................................................



//BITRIX EVENTS........................................................
//завершение лида
Route::post('listener/lead/complete', function (Request $request) {

    //from cold
    // https://april-hook.ru/api/task?
    $companyId = null;
    $leadId = null;
    $responsibleId  = null;
    $domain = null;
    if (isset($request['auth'])) {
        if (isset($request['auth']['domain'])) {
            $domain = $request['auth']['domain'];
        }
    }


    if (isset($request['company_id'])) {
        //при соединении к существующей или созданиии новой компании
        //полюбому будет company_id
        $companyId = $request['company_id'];
    }
    if (isset($request['lead_id'])) {
        $leadId = $request['lead_id'];
    }

    if (isset($request['responsible'])) {

        $responsible = $request['responsible'];
        $partsResponsible = explode("_", $responsible);
        if (!empty($partsResponsible)) {
            $responsibleId = $partsResponsible[1];
        }
    }
    //Создание новой компании из лида или присоеднинение к существующей


    return  BitrixHookController::leadComplete($domain, $companyId, $leadId, $responsibleId);
});

// .......................................................................




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






//testing

Route::post('/lists', function (Request $request) {

    $domain = env('APRIL_BITRIX_DOMAIN');
    $secret = env('APRIL_WEB_HOOK');
    $restVersion = env('APRIL_BITRIX_REST_VERSION');

    // Log::info('Environment Variables', [
    //     'BITRIX_DOMAIN' => $domain,
    //     'BITRIX_REST_VERSION' => $restVersion,
    //     'WEB_HOOK' => $secret
    // ]);


    try {

        $listsfields = Http::get('https://' . $domain . '/rest/' . $restVersion . '/' . $secret . '/lists.field.get.json', [
            'IBLOCK_TYPE_ID' => 'lists',
            'IBLOCK_ID' => '86',

        ]);
        // Log::info('listsfields ', ['listsfields ' => $listsfields['result']]);
        $response = Http::get('https://' . $domain . '/rest/' . $restVersion . '/' . $secret . '/lists.element.get.json', [
            'IBLOCK_TYPE_ID' => 'lists',
            'IBLOCK_ID' => '86',
            'FILTER' => [
                '>=DATE_CREATE' => '01.01.2023 00:00:00',
                '<=DATE_CREATE' => '01.01.2024 23:59:59',
            ]


        ]);
        // Log::info('response ', ['response ' => $response]);

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

Route::post('/smart/categories', function (Request $request) {



    // entityTypeId - id смарт процесса как сущности
    // domain for get keys

    // companyId	UF_CRM_6_1697099643
    $document_id = $request['document_id'];
    $ownerType = $request['ownerType'];   // L C D T9c
    $auth = $request['auth'];
    $domain = $auth['domain'];

    // Log::info('REQUEST', $request->all());
    // Log::info('domain', ['domain' => $domain]);


    return APIBitrixController::getSmartStages($domain);
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
            // Log::info('COMPANY ', ['getCompany ' => $getCompany]);
            // Log::info('COMPANY ', ['company_id ' => $company_id]);


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
            // Log::info('SMART ', ['trySmart ' => $responsetrySmart]);
            if ($responsetrySmart) {
                if ($responsetrySmart['result']) {
                    if ($responsetrySmart['result']['items']) {
                        // Log::info('SMART ', ['ITEMS ' => $responsetrySmart['result']['items']]);
                        // Log::info('SMART ', ['TOTAL ' => $responsetrySmart['result']['total']]);
                        if ($responsetrySmart['result']['items'][0]) {
                            $smart = $responsetrySmart['result']['items'][0];
                            // Log::info('SMART ', ['trySmart ' => $smart]);
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


Route::post('/install/smart/', function (Request $request) {

    $auth = $request['auth'];
    $domain = $auth['domain'];


    return APIBitrixController::installSmart($domain);
});


Route::post('/bitrix/method', function (Request $request) {

    $domain = $request->domain;
    $method = $request->method;
    $data = $request->bxData;




    try {
        $portal = PortalController::getPortal($domain);
        $portal = $portal['data'];
        $portal = $portal;
        $webhookRestKey = $portal['C_REST_WEB_HOOK_URL'];
        $hook = 'https://' . $domain  . '/' . $webhookRestKey;
        $url  = $hook . '/' . $method;

        return Http::get($url, $data);


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



Route::post('/test/', function (Request $request) {
    // $data = $request->all();
    // Log::channel('telegram')->error('APRIL_HOOK CALL TEST', [
    //     'call' => $request
    // ]);


    return APIOnlineController::getSuccess(['result' => true]);
});





// Route::post('/update/smart/', function (Request $request) {

//     $auth = $request['auth'];
//     $domain = $auth['domain'];


//     $created = $request['created'];
//     $responsible = $request['responsible'];

//     Log::info('LOG', $request->all());

//     $partsCreated = explode("_", $created);
//     $partsResponsible = explode("_", $responsible);
//     $createdId = $partsCreated[1];
//     $responsibleId = $partsResponsible[1];


//     $auth = $request['auth'];
//     $domain = $auth['domain'];
//     $crm = $request['document_id'][2];
//     // $companyId = $request['company_id'];

//     $deadline = $request['deadline'];
//     $name = $request['name'];
//     $currentSmartId = $request['id'];
//     $logData = [
//         'crm' => $crm,
//         'currentSmartId' => $currentSmartId,
//         'domain' => $domain,
//         'deadline' => $deadline,
//         'createdId' => $createdId,
//         'responsibleId' => $responsibleId,
//         'name' => $name,
//     ];

//     // Log::info('REQUEST', $request->all());
//     Log::info('REQUEST DATA', $logData);

//     try {


//         // APIBitrixController::getSmartStages($domain);

//         //portal and keys
//         $portal = PortalController::getPortal($domain);
//         Log::info('portal', ['portal' => $portal]);
//         $portal = $portal['data'];
//         Log::info('portalData', ['portal' => $portal]);

//         //base hook
//         $webhookRestKey = $portal['C_REST_WEB_HOOK_URL'];
//         $hook = 'https://' . $domain  . '/' . $webhookRestKey;



//         //user and time
//         $createdTime = Carbon::createFromFormat('d.m.Y H:i:s', $deadline, 'Asia/Novosibirsk');
//         $methodGetUser = '/user.get.json';
//         $url = $hook . $methodGetUser;
//         $userData = [
//             'id' => $createdId
//         ];
//         $userResponse =  $responseData = Http::get($url, $userData);
//         Log::info('RESPONSIBLE', ['userResponse' => $userResponse]);
//         // if ($userResponse && $userResponse['result'] && $userResponse['result'][0]) {
//         //     $userTimeZone =  $userResponse['result'][0]['TIME_ZONE'];
//         //     if ($userTimeZone) {
//         //         $createdTime = Carbon::createFromFormat('d.m.Y H:i:s', $deadline, $userTimeZone);
//         //     }
//         // }

//         // $nowDate = now();
//         // $novosibirskTime = Carbon::createFromFormat('d.m.Y H:i:s', $deadline, 'Asia/Novosibirsk');
//         $moscowTime = $createdTime->setTimezone('Europe/Moscow');
//         $moscowTime = $moscowTime->format('Y-m-d H:i:s');
//         Log::info('novosibirskTime', ['novosibirskTime' => $createdTime]);
//         Log::info('moscowTime', ['moscowTime' => $moscowTime]);


//         //smart update

//         //get smart
//         //  $methodSmartUpdate = '/crm.item.update.json';
//         // $methodSmartGet = '/crm.item.get.json';
//         // $url = $hook . $methodSmartGet;
//         // $smartGetData =  [
//         //     'id' => $currentSmartId,
//         //     'entityTypeId' => env('BITRIX_SMART_MAIN_ID'),
//         // 'fields' => [
//         //     'TITLE' => 'Холодный обзвон  ' . $name . '  ' . $deadline,
//         //     'RESPONSIBLE_ID' => $responsibleId,
//         //     'GROUP_ID' => env('BITRIX_CALLING_GROUP_ID'),
//         //     'CHANGED_BY' => $createdId, //- постановщик;
//         //     'CREATED_BY' => $createdId, //- постановщик;
//         //     'CREATED_DATE' => $nowDate, // - дата создания;
//         //     'DEADLINE' => $moscowTime, //- крайний срок;
//         //     'UF_CRM_TASK' => ['T9c_' . $crm],
//         //     'ALLOW_CHANGE_DEADLINE' => 'N',
//         //     'DESCRIPTION' => $description
//         // ]
//         // ];

//         // $responseGetData = Http::get($url, $smartGetData);
//         // Log::info('responseGetData', ['responseGetData' => $responseGetData]);

//         //update smart
//         //  $methodSmartUpdate = '/crm.item.update.json';
//         $methodSmartUpdate = '/crm.item.update.json';
//         $url = $hook . $methodSmartUpdate;
//         $smartUpdateData =  [
//             'id' => $currentSmartId,
//             'entityTypeId' => env('BITRIX_SMART_MAIN_ID'),
//             'fields' => [
//                 // 'TITLE' => 'Холодный обзвон  ' . $name . '  ' . $deadline,
//                 'assignedById' => $responsibleId,
//                 // 'GROUP_ID' => env('BITRIX_CALLING_GROUP_ID'),
//                 // 'CHANGED_BY' => $createdId, //- постановщик;
//                 // 'CREATED_BY' => $createdId, //- постановщик;
//                 // 'CREATED_DATE' => $nowDate, // - дата создания;
//                 // 'DEADLINE' => $moscowTime, //- крайний срок;
//                 // 'UF_CRM_TASK' => ['T9c_' . $crm],
//                 // 'ALLOW_CHANGE_DEADLINE' => 'N',
//                 // 'DESCRIPTION' => $description
//                 "ufCrm_1696580389" => $deadline,
//                 "ufCrm_6_1702453779" => $createdId,
//                 "ufCrm_6_1702652862" => $responsibleId,
//                 "ufCrm_6_1700645937" => $name,

//                 "stageId" => 'DT156_14:NEW',


//             ]
//         ];

//         $responseData = Http::get($url, $smartUpdateData);
//         Log::info('responseData', ['responseData' => $responseData]);
//     } catch (\Throwable $th) {
//         Log::error('ERROR: Exception caught', [
//             'message'   => $th->getMessage(),
//             'file'      => $th->getFile(),
//             'line'      => $th->getLine(),
//             'trace'     => $th->getTraceAsString(),
//         ]);
//         return APIOnlineController::getResponse(1, $th->getMessage(), null);
//     }
// });






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

























































// Route::post('/taskevent', function (Request $request) {
//     //     http://portal.bitrix24.com/rest/placement.bind/?access_token=sode3flffcmv500fuagrprhllx3soi72
//     // 	&PLACEMENT=CRM_CONTACT_LIST_MENU
//     // 	&HANDLER=http%3A%2F%2Fwww.applicationhost.com%2Fplacement%2F
//     // 	&TITLE=Тестовое приложение
//     // HTTP/1.1 200 OK
//     // {
//     // 	"result": true
//     // }
//     $actionUrl = '/placement.bind.json';
//     $domain = $request['auth']['domain'];
//     $portal = PortalController::getPortal($domain);
//     Log::info('portal', ['portal' => $portal]);

//     Log::info('taskevent', ['request' => $request->all()]);
//     try {

//         $webhookRestKey = $portal['data']['C_REST_WEB_HOOK_URL'];
//         $hook = 'https://' . $domain  . '/' . $webhookRestKey;

//         $url = $hook . $actionUrl;
//         $data = [
//             'PLACEMENT'=>'TASK_VIEW_SIDEBAR',
//             'HANDLER'=>'https://april-server/test/placement.php',
//             'LANG_ALL' => [
//                 'en' => [
//                     'TITLE' => 'Get Offer app',
//                     'DESCRIPTION' => 'App Helps Garant employees prepare commercial documents and collect sales funnel statistics',
//                     'GROUP_NAME' => 'Garant',
//                 ],
//                 'ru' => [
//                     'TITLE' => 'КП Гарант',
//                     'DESCRIPTION' => 'Приложение помогает сотрудникам Гарант составлять коммерческие документы и собирать статистику воронки продаж',
//                     'GROUP_NAME' => 'группа',
//                 ],
//             ],
//         ];




//         Log::info('taskevent', ['request' => $request->all()]);
//     } catch (\Throwable $th) {
//         Log::info('taskevent', ['request' => $request->all()]);
//         return APIOnlineController::getError(

//             'error callings ' . $th->getMessage(),
//             [
//                 // 'result' => $resultCallings,

//                 'error callings ' . $th->getMessage(),
//             ]
//         );
//     }
// });
