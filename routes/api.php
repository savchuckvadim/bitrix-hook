<?php

use App\Http\Controllers\APIBitrixController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\APIController;
use App\Http\Controllers\APIOnlineController;
use App\Http\Controllers\BitrixHookController;

use App\Http\Controllers\PortalController;
use App\Http\Controllers\ReactAppController;

use App\Services\BitrixGeneralService;
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

Route::get('/test-cors', function () {
   
    $headers = [
        'CORS-Middleware-Called' => 'true',
        'Origin' => request()->header('Origin'),
        'Headers' => response()->headers->all(),
    ];
    APIOnlineController::sendLog('CORS-Middleware-Called', $headers);

    return response()->json(['success' => true], 200)->withHeaders($headers);
});

// Route::options('{any}', function () {
//     APIOnlineController::sendLog('yo OPTIONS request received', []);

//     return response()->json([], 204, [
//         'CORS-Middleware-Called' => 'true',
//         'Access-Control-Allow-Origin' => '*',
//         'Access-Control-Allow-Methods' => 'GET, POST, PUT, DELETE, OPTIONS',
//         'Access-Control-Allow-Headers' => 'Content-Type, Authorization, X-Requested-With',
//         'Access-Control-Allow-Credentials' => 'true',
//     ]);
// })->where('any', '.*');

require __DIR__ . '/rate/rate.php';
require __DIR__ . '/full/full_event.php';
require __DIR__ . '/full/full_lead.php';
require __DIR__ . '/full/full_front.php';
require __DIR__ . '/alfa/alfa.php';
require __DIR__ . '/helper/helper_router.php';
require __DIR__ . '/yandex/routes.php';


Route::get('/test/', function () {

    $domain = 'april-dev.bitrix24.ru';
    $portal = PortalController::getPortal($domain);
    if (!empty($portal) && !empty($portal['data'])) {
        $result = $portal['data']['id'];
    } else {
        $result = $portal;
    }
    dd([
        'result' => $result,

    ]);

    return 'yo';
});


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



// // ............................... FULL EVENT CALING PRES FRONT
// Route::post('/full/initpres/success', function (Request $request) {
//     //     https://april-hook/api/full/initpres/success
//     //     ?commentOwner={{Комментарий к заявке Руководитель}}&commentTMC={{Комментарий к заявке ТМЦ}
//     // }&commentManager={{Комментарий к заявке Менеджер}}&deadline={{ОП Дата назначенной презентации}}
//     //     &name={{Название}}&ownerId=user_1&managerId={{ОП Кто назначен ответственным}}
//     //     &tmcId={{ТМЦ Кто назначил последнюю заявку на презентацию}}&tmcDealId={{ТМЦ Сделка}}
//     $comedata = $request->all();
//     Log::info('HOOK TST', [
//         'comedata' => $comedata,


//     ]);
//     Log::channel('telegram')->info('HOOK TST', [

//         '$comedata' => $comedata

//     ]);
//     //     должен сделать полный цикл flow 
//     // как будто назначили презентацию
//     // найти существующую сделку по компании и сотруднику base или создать
//     // для этого контроллер подготовит data на основе request чтобы далее засунуть просто в event service
//     $data = [];
//     try {
//         //code...
//         $tmcdealId = $comedata['tmcDealId'];
//         $companyId = $comedata['companyId'];

//         if (!empty($comedata['auth'])) {
//             if (!empty($comedata['auth']['domain'])) {
//                 $domain =  $comedata['auth']['domain'];
//             }
//         }
//         $data['domain'] =  $domain;




//         $comment = '';
//         if (!empty($comedata['commentTMC'])) {
//             $comment = 'ТМЦ:' . $comedata['commentTMC'];
//         }
//         if (!empty($comedata['commentOwner'])) {
//             $comment = 'Руководитель: ' . $comedata['commentOwner'];
//         }
//         if (!empty($comedata['commentTMC'])) {
//             $comment = 'Менеджер: ' . $comedata['commentManager'];
//         }



//         $partsCreated = explode("_", $comedata['ownerId']);
//         $partsResponsible = explode("_", $comedata['managerId']);
//         $createdId = $partsCreated[1];
//         $responsibleId = $partsResponsible[1];



//         $data['presentation'] = [
//             "count" => [
//                 "company" => 0,
//                 "smart" => 0,
//                 "deal" => 0
//             ],
//             "isPresentationDone" => false,
//             "isUnplannedPresentation" => false
//         ];

//         $data['report'] = [
//             "resultStatus" => "new",
//             "description" =>  $comment,
//             "failReason" => [
//                 "items" => [
//                     ["id" => 0, "code" => "fail_notime", "name" => "Не было времени", "isActive" => true]
//                 ],
//                 'current' =>   ["id" => 0, "code" => "fail_notime", "name" => "Не было времени", "isActive" => true]

//             ],
//             "failType" => [
//                 "items" => [
//                     ["id" => 0, "code" => "op_prospects_good", "name" => "Перспективная", "isActive" => true],
//                     ["id" => 2, "code" => "garant", "name" => "Гарант/Запрет", "isActive" => true]
//                 ],
//                 'current' =>   ["id" => 0, "code" => "op_prospects_good", "name" => "Перспективная", "isActive" => true]

//             ],
//             "noresultReason" => [
//                 "items" => [
//                     ["id" => 0, "code" => "secretar", "name" => "Секретарь", "isActive" => true]
//                 ],
//                 'current' =>  ["id" => 0, "code" => "secretar", "name" => "Секретарь", "isActive" => true]


//             ],
//             "workStatus" => [
//                 "items" => [
//                     ["id" => 0, "code" => "inJob", "name" => "В работе", "isActive" => true]
//                 ],
//                 "current" => [
//                     "id" => 0,
//                     "code" => "inJob",
//                     "name" => "В работе",
//                     "isActive" => true
//                 ],
//                 "default" => [
//                     "id" => 0,
//                     "code" => "inJob",
//                     "name" => "В работе",
//                     "isActive" => true
//                 ],
//                 "isChanged" => false
//             ]
//         ];
//         $data['currentTask'] = null;
//         // $data['report']['resultStatus'] = 'new';
//         // $data['report']['workStatus']['current']['code'] == 'inJob';
//         // $data['plan']['createdBy']

//         $data['plan'] = [
//             'type' => [
//                 'current' => [
//                     "id" => 2,
//                     "code" => "presentation",
//                     "name" => "Презентация",
//                     "isActive" => true
//                 ]
//             ],
//             "createdBy" => [
//                 "ID" => $createdId,
//                 // "XML_ID" => "",
//                 // "ACTIVE" => true,
//                 // "NAME" => "",
//                 // "LAST_NAME" => "",
//                 // "SECOND_NAME" => ""
//             ],
//             "responsibility" => [
//                 "ID" => $responsibleId,
//                 // "XML_ID" => "",
//                 // "ACTIVE" => true,
//                 // "NAME" => "",
//                 // "LAST_NAME" => "",
//                 // "SECOND_NAME" => ""
//             ],
//             "deadline" => $comedata['name'],
//             "isPlanned" => true,
//             "name" => $comedata['name']
//         ];





//         $data['placement'] = [
//             'options' => [
//                 'ID' =>  $companyId
//             ],
//             'placement' => 'COMPANY'
//         ];

//         $data['departament'] = [
//             "mode" => [
//                 "id" => 1,
//                 "code" => "sales",
//                 "name" => "Менеджер по продажам"
//             ]
//         ];

//         $hook = PortalController::getHook($domain);

//         //tmc init pres session 
//         $tmcDealSession = ReportController::getPresTMCInitDeal( //создается сессия со сделкой tmc
//             $domain,
//             $hook,
//             $tmcdealId,
//             $responsibleId,
//             $companyId
//         );
//         $baseDealSession =   ReportController::getDealsFromNewTaskInner( //создается сессия с base сделкой
//             $domain,
//             $hook,
//             $companyId,
//             $responsibleId,
//             'company' // $from да вроде оно и не нужно
//         );
//         $currentBaseDeals = null;
//         if (!empty($baseDealSession)) {
//             if (!empty($baseDealSession['deals'])) {
//                 if (!empty($baseDealSession['deals']['currentBaseDeals'])) {
//                     $currentBaseDeals = $baseDealSession['deals']['currentBaseDeals'];
//                 }
//             }
//         }


//         Log::info('HOOK TST', [
//             'currentBaseDeals' => $currentBaseDeals,
//             'baseDealSession' => $baseDealSession,

//             'tmcDealSession' => $tmcDealSession,
//             '$data' => $data

//         ]);


//         dispatch(
//             new EventJob($data)
//         )->onQueue('high-priority');
//     } catch (\Throwable $th) {
//         $errorData = [
//             'message'   => $th->getMessage(),
//             'file'      => $th->getFile(),
//             'line'      => $th->getLine(),
//             'trace'     => $th->getTraceAsString(),
//         ];
//         Log::error('API HOOK: Exception caught', $errorData);
//     }
// });




// Route::post('full/department', function (Request $request) {
//     return ReportController::getFullDepartment($request);
// });


// Route::post('/full/tasks', function (Request $request) {
//     return FullEventInitController::getEventTasks($request);
// });

// Route::post('/full/init', function (Request $request) {
//     return FullEventInitController::fullEventSessionInit($request);
// });

// Route::post('/full/session', function (Request $request) {
//     return FullEventInitController::sessionGet($request);
// });





// Route::post('/activity/test', function (Request $request) {
//     Log::info('', [
//         'request' => $request->all()
//     ]);
//     Log::channel('telegram')->info('', [
//         'request' => $request->all()
//     ]);
// });


// Route::post('/pres/count', function (Request $request) {
//     return ReportController::getPresCounts($request);
// });



// //////////////////////INIT EVENT FROM TASK ||  EVENT FROM NEW TASK || EVENT FROM FROM ONE MORE TASK || DOCUMENT
// //TODO full department



// Route::post('full/deals', function (Request $request) {
//     return ReportController::getFullDeals($request);
// });
// Route::post('full/newTask/init', function (Request $request) {
//     return ReportController::getDealsFromNewTaskInit($request);
// });


// Route::post('full/supply', function (Request $request) {
//     return ReportSupplyController::getSupplyForm($request);
// });




// // ............................... FULL EVENT Document PRES FRONT
// Route::post('/full/document/init', function (Request $request) {

//     //засовывает в сессию текущую base сделку
//     //находит сделки презентации fromBase и fromCompany
//     //текущую компанию 

//     return ReportController::getDocumentDealsInit($request);
//     // return FullEventInitController::sessionGet($request);
// });


// Route::post('/full/document/flow', function (Request $request) {

//     //    получает информацию о текущем документе
//     //    записывает в entity и списки и обновляет стадию сделки
//     // document service flow
//     $data = $request->all();
//     $service = new EventDocumentService($data);
//     return $service->getDocumentFlow();
// });


// Route::post('/full/contract/flow', function (Request $request) {

//     $data = $request->all();
//     return APIOnlineController::getSuccess(
//         ['contractData' => $data, 'link' => $data]
//     );
// });

// Route::post('/full', function (Request $request) {
//     return ReportController::eventReport($request);
// });

// // новй холодный звонка из Откуда Угодно
// Route::post('cold', function (Request $request) {

//     //from anywhere
//     // https: //april-hook.ru/api/cold?
//     // created={=Template:Parameter2}&
//     // responsible={=Template:Parameter3}&
//     // deadline={=Template:Parameter1}&
//     // name={=Template:Parameter4}&
//     // entity_id={{ID}}
//     // &entity_type=smart | company | lead
//     // isOlyDeal ??
//     // Log::info('APRIL_HOOK ', ['cold' => 'yo']);
//     // Log::info('APRIL_HOOK cold', ['data' => $request->all()]);
//     $controller = new BitrixHookController();
//     return $controller->getColdCall(
//         $request
//     );
// });


// require __DIR__.'/full/full_report.php';




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

        Log::channel('telegram')->info('APRIL_HOOK domain', ['domain' => $domain]);

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
    // Log::channel('telegram')->error('APRIL_HOOK', [
    //     'cold task create api' => [
    //         'domain' => $domain,
    //         'deadline' => $deadline,
    //         'name' => $name,
    //         'companyId' => $companyId,
    //         'crm' => $crm,
    //     ]
    // ]);

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






//for testing front

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

        $response = Http::get($url, $data);
        $responseData = APIBitrixController::getBitrixRespone($response, 'general service: update deal');


        return APIOnlineController::getSuccess([
            'result' =>  $responseData
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





Route::post('/test/', function (Request $request) {
    // $data = $request->all();
    // Log::channel('telegram')->error('APRIL_HOOK CALL TEST', [
    //     'call' => $request
    // ]);


    return APIOnlineController::getSuccess(['result' => true]);
});







Route::get('/alfa/activity', function (Request $request) {
    // Лид	1	LEAD	L	CRM_LEAD
    // Сделка	2	DEAL	D	CRM_DEAL
    // Контакт	3	CONTACT	C	CRM_CONTACT
    // Компания	4	COMPANY	CO	CRM_COMPANY
    // Счет (старый)	5	INVOICE	I	CRM_INVOICE
    // Счет (новый)	31	SMART_INVOICE	SI	CRM_SMART_INVOICE
    // Предложение	7	QUOTE	Q	CRM_QUOTE
    // Реквизит	8	REQUISITE	RQ	CRM_REQUISITE
    try {
        //code...

        $domain = 'alfacentr.bitrix24.ru';
        $fullDomain = 'https://' . $domain  . '/';
        $method = '/crm.activity.list.json';
        $yearAgo = date('Y-m-d', strtotime('-1 year'));
        $portal = PortalController::getPortal($domain);
        $portal = $portal['data'];

        $webhookRestKey = $portal['C_REST_WEB_HOOK_URL'];
        $hook = $fullDomain . $webhookRestKey;

        // $data = [
        //     'filter' => [
        //         'RESPONSIBLE_ID' => 502,
        //         '<CREATED' => $yearAgo,
        //         '!=PROVIDER_TYPE_ID' => 'TASK',
        //         'OWNER_TYPE_ID' => 4,
        //         '%SUBJECT' => 'юр. форум' // Поиск дел, где в названии есть "юр. форум"

        //     ]
        // ];
        // $response = Http::post($hook . $method, $data);

        $lastActivityID = 0; // Используйте последний ID для пагинации
        $allActivities = []; // Массив для сохранения всех активностей
        $finish = false;
        $responses = [];
        $pagesCount = 0;
        while (!$finish) {
            sleep(1);
            $data = [
                'order' => ['ID' => 'ASC'],
                'filter' => [
                    '>ID' => $lastActivityID,
                    // 'RESPONSIBLE_ID' => 502,
                    // '<CREATED' => $yearAgo,
                    'OWNER_TYPE_ID' => 4,
                    // '%QUERY' => 'юр форум | юрфорум | юр.форум | ВЮФ | юридический форум'
                    '%SUBJECT' => 'юр. форум'

                ],

            ];

            $responseJson = Http::post($hook . $method, $data);
            $response =  $responseJson->json();

            // $response =   APIBitrixController::getBitrixRespone($responseJson, 'getDepartments');


            if (!empty($response['result'])) {
                foreach ($response['result'] as $activity) {
                    $allActivities[] = $activity;
                    $lastActivityID = $activity['ID']; // Обновление последнего ID для следующего запроса

                    if (isset($activity['OWNER_ID'])) {

                        $companyId = $activity['OWNER_ID'];
                        sleep(1);
                        BitrixGeneralService::updateEntity(
                            $hook,
                            'company',
                            $companyId,
                            [
                                // 'ASSIGNED_BY_ID' => 502,
                                // 'UF_CRM_1720600919' => 'юрфорум',
                                'UF_CRM_1720600919' => 'юрфорум',
                                'UF_CRM_1721825948' => [15638]
                            ]
                        );
                        $responseJson = Http::post($hook . $method, $data);
                        array_push($responses, $responseJson);
                    }
                }
                $pagesCount++;
            } else {
                $finish = true; // Завершаем цикл, если результаты закончились
            }
        }

        $count = count($allActivities);
        return  APIOnlineController::getSuccess([
            'result' => $allActivities,
            'count' => $count,
            'responses' => $responses


        ]);
    } catch (\Throwable $th) {
        return APIOnlineController::getSuccess(['result' => $th->getMessage()]);
    }
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
