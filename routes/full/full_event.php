<?php

use App\Http\Controllers\APIOnlineController;
use App\Http\Controllers\Front\EventCalling\FullEventInitController;
use App\Http\Controllers\Front\EventCalling\ReportController;
use App\Http\Controllers\Front\EventCalling\ReportSupplyController;
use App\Http\Controllers\Front\Konstructor\ContractController;
use App\Http\Controllers\Front\ReportKPI\ReportKPIController;
use App\Http\Controllers\PortalController;
use App\Jobs\EventJob;
use App\Services\FullEventReport\EventDocumentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;


Route::post('/pres/count', function (Request $request) {
    return ReportController::getPresCounts($request);
});

Route::prefix('full')->group(function () {
    Route::post('', [ReportController::class, 'eventReport']);

    // ............................... FULL EVENT CALING PRES FRONT
    Route::post('/initpres/success', function (Request $request) {
        //     https://april-hook/api/full/initpres/success
        //     ?commentOwner={{Комментарий к заявке Руководитель}}&commentTMC={{Комментарий к заявке ТМЦ}
        // }&commentManager={{Комментарий к заявке Менеджер}}&deadline={{ОП Дата назначенной презентации}}
        //     &name={{Название}}&ownerId=user_1&managerId={{ОП Кто назначен ответственным}}
        //     &tmcId={{ТМЦ Кто назначил последнюю заявку на презентацию}}&tmcDealId={{ТМЦ Сделка}}&companyId
        $comedata = $request->all();
        Log::info('HOOK TST', [
            'comedata' => $comedata,


        ]);
        Log::channel('telegram')->info('HOOK TST', [

            '$comedata' => $comedata

        ]);
        //     должен сделать полный цикл flow 
        // как будто назначили презентацию
        // найти существующую сделку по компании и сотруднику base или создать
        // для этого контроллер подготовит data на основе request чтобы далее засунуть просто в event service
        $data = [];
        try {
            //code...
            $tmcdealId = $comedata['tmcDealId'];
            $companyId = $comedata['companyId'];

            if (!empty($comedata['auth'])) {
                if (!empty($comedata['auth']['domain'])) {
                    $domain =  $comedata['auth']['domain'];
                }
            }
            $data['domain'] =  $domain;




            $comment = '';
            if (!empty($comedata['commentTMC'])) {
                $comment = 'ТМЦ:' . $comedata['commentTMC'];
            }
            if (!empty($comedata['commentOwner'])) {
                $comment = 'Руководитель: ' . $comedata['commentOwner'];
            }
            if (!empty($comedata['commentTMC'])) {
                $comment = 'Менеджер: ' . $comedata['commentManager'];
            }



            $partsCreated = explode("_", $comedata['ownerId']);
            $partsResponsible = explode("_", $comedata['managerId']);
            $createdId = $partsCreated[1];
            $responsibleId = $partsResponsible[1];



            $data['presentation'] = [
                "count" => [
                    "company" => 0,
                    "smart" => 0,
                    "deal" => 0
                ],
                "isPresentationDone" => false,
                "isUnplannedPresentation" => false
            ];

            $data['report'] = [
                "resultStatus" => "new",
                "description" =>  $comment,
                "failReason" => [
                    "items" => [
                        ["id" => 0, "code" => "fail_notime", "name" => "Не было времени", "isActive" => true]
                    ],
                    'current' =>   ["id" => 0, "code" => "fail_notime", "name" => "Не было времени", "isActive" => true]

                ],
                "failType" => [
                    "items" => [
                        ["id" => 0, "code" => "op_prospects_good", "name" => "Перспективная", "isActive" => true],
                        ["id" => 2, "code" => "garant", "name" => "Гарант/Запрет", "isActive" => true]
                    ],
                    'current' =>   ["id" => 0, "code" => "op_prospects_good", "name" => "Перспективная", "isActive" => true]

                ],
                "noresultReason" => [
                    "items" => [
                        ["id" => 0, "code" => "secretar", "name" => "Секретарь", "isActive" => true]
                    ],
                    'current' =>  ["id" => 0, "code" => "secretar", "name" => "Секретарь", "isActive" => true]


                ],
                "workStatus" => [
                    "items" => [
                        ["id" => 0, "code" => "inJob", "name" => "В работе", "isActive" => true]
                    ],
                    "current" => [
                        "id" => 0,
                        "code" => "inJob",
                        "name" => "В работе",
                        "isActive" => true
                    ],
                    "default" => [
                        "id" => 0,
                        "code" => "inJob",
                        "name" => "В работе",
                        "isActive" => true
                    ],
                    "isChanged" => false
                ]
            ];
            $data['currentTask'] = null;
            // $data['report']['resultStatus'] = 'new';
            // $data['report']['workStatus']['current']['code'] == 'inJob';
            // $data['plan']['createdBy']

            $data['plan'] = [
                'type' => [
                    'current' => [
                        "id" => 2,
                        "code" => "presentation",
                        "name" => "Презентация",
                        "isActive" => true
                    ]
                ],
                "createdBy" => [
                    "ID" => $createdId,
                    // "XML_ID" => "",
                    // "ACTIVE" => true,
                    // "NAME" => "",
                    // "LAST_NAME" => "",
                    // "SECOND_NAME" => ""
                ],
                "responsibility" => [
                    "ID" => $responsibleId,
                    // "XML_ID" => "",
                    // "ACTIVE" => true,
                    // "NAME" => "",
                    // "LAST_NAME" => "",
                    // "SECOND_NAME" => ""
                ],
                "deadline" => $comedata['name'],
                "isPlanned" => true,
                "name" => $comedata['name']
            ];





            $data['placement'] = [
                'options' => [
                    'ID' =>  $companyId
                ],
                'placement' => 'COMPANY'
            ];

            $data['departament'] = [
                "mode" => [
                    "id" => 1,
                    "code" => "sales",
                    "name" => "Менеджер по продажам"
                ]
            ];

            $hook = PortalController::getHook($domain);

            //tmc init pres session 
            $tmcDealSession = ReportController::getPresTMCInitDeal( //создается сессия со сделкой tmc
                $domain,
                $hook,
                $tmcdealId,
                $responsibleId,
                $companyId
            );
            $baseDealSession =   ReportController::getDealsFromNewTaskInner( //создается сессия с base сделкой
                $domain,
                $hook,
                $companyId,
                $responsibleId,
                'company' // $from да вроде оно и не нужно
            );
            $currentBaseDeals = null;
            if (!empty($baseDealSession)) {
                if (!empty($baseDealSession['deals'])) {
                    if (!empty($baseDealSession['deals']['currentBaseDeals'])) {
                        $currentBaseDeals = $baseDealSession['deals']['currentBaseDeals'];
                    }
                }
            }


            Log::info('HOOK TST', [
                'currentBaseDeals' => $currentBaseDeals,
                'baseDealSession' => $baseDealSession,

                'tmcDealSession' => $tmcDealSession,
                '$data' => $data

            ]);


            dispatch(
                new EventJob($data)
            )->onQueue('high-priority');
        } catch (\Throwable $th) {
            $errorData = [
                'message'   => $th->getMessage(),
                'file'      => $th->getFile(),
                'line'      => $th->getLine(),
                'trace'     => $th->getTraceAsString(),
            ];
            Log::error('API HOOK: Exception caught', $errorData);
        }
    });





    Route::post('/department', [ReportController::class, 'getFullDepartment']);
    Route::post('/tasks', [FullEventInitController::class, 'getEventTasks']);
    Route::post('/init', [FullEventInitController::class, 'fullEventSessionInit']);
    Route::post('/session', [FullEventInitController::class, 'sessionGet']);

    //////////////////////INIT EVENT FROM TASK ||  EVENT FROM NEW TASK || EVENT FROM FROM ONE MORE TASK || DOCUMENT
    //TODO full department

    Route::post('/deals', [ReportController::class, 'getFullDeals']);
    Route::post('/newTask/init', [ReportController::class, 'getDealsFromNewTaskInit']);
    Route::post('/supply', [ReportSupplyController::class, 'getSupplyForm']);

    // ............................... FULL EVENT Document PRES FRONT

    Route::post('/document/init', [ReportController::class, 'getDocumentDealsInit']);
    //засовывает в сессию текущую base сделку
    //находит сделки презентации fromBase и fromCompany
    //текущую компанию 



    Route::post('/document/flow', function (Request $request) {

        //    получает информацию о текущем документе
        //    записывает в entity и списки и обновляет стадию сделки
        // document service flow
        $data = $request->all();
        $service = new EventDocumentService($data);
        return $service->getDocumentFlow();
    });


    Route::post('/contract/flow', function (Request $request) {

        $data = $request->all();
        return APIOnlineController::getSuccess(
            ['contractData' => $data, 'link' => $data]
        );
    }); //TODO |


    //REPORT APP
    Route::prefix('report')->group(function () {

        Route::post('/init', function (Request $request) {
            $domain = $request->domain;
            $controller = new ReportKPIController($domain);
            return $controller->frontInit($request);
        });

        Route::post('/filter', function (Request $request) {
            $domain = $request->domain;
            $controller = new ReportKPIController($domain);
            return $controller->getListFilter($request);
        });
    });

    // Route::post('/contract/flow', [ReportController::class, 'eventReport']);
});
