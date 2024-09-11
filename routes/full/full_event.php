<?php

use App\Http\Controllers\APIOnlineController;
use App\Http\Controllers\Front\EventCalling\FullEventInitController;
use App\Http\Controllers\Front\EventCalling\ReportController;
use App\Http\Controllers\Front\EventCalling\ReportSupplyController;
use App\Http\Controllers\Front\Konstructor\ContractController;
use App\Http\Controllers\Front\ReportKPI\ReportKPIController;
use App\Http\Controllers\PortalController;
use App\Jobs\EventJob;
use App\Services\BitrixGeneralService;
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
                "deadline" => $comedata['deadline'],
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

            // sleep(0.3);
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
        Route::post('/get', function (Request $request) {
            $domain = $request->domain;
            $controller = new ReportKPIController($domain);
            return $controller->getReport($request);
        });
        Route::post('/filter', function (Request $request) {
            $domain = $request->domain;
            $controller = new ReportKPIController($domain);
            return $controller->getListFilter($request);
        });
    });

    // Route::post('/contract/flow', [ReportController::class, 'eventReport']);

    // https://april-hook.ru/api/full/company/update?responsible={{ОП Кто назначен ответственным}}&companyId={{Компания}}
    Route::post('/company/update', function (Request $request) {
        $data = $request->all();
        $domain = '';
        $responsibleId = '';
        $companyId = '';
        // Log::channel('telegram')->error('APRIL_HOOK', [
        //     'data'  =>  $data,
        // ]);
        if (!empty($data['auth'])) {

            if (!empty($data['auth']['domain'])) {
                $domain = $data['auth']['domain'];
            }
            Log::channel('telegram')->error('APRIL_HOOK', [
                'auth'  =>  $data['auth'],
            ]);
            if (!empty($data['companyId'])) {
                $companyId = $data['companyId'];
            }

            if (!empty($data['responsible'])) {

                $partsResponsible = explode("_", $data['responsible']);
                $responsibleId = $partsResponsible[1];
            }
        }
        Log::channel('telegram')->error('APRIL_HOOK', [
            'domain'  =>  $domain,
            'responsibleId'  =>  $responsibleId,
            'companyId'  =>  $companyId,

        ]);
        if (!empty($domain) && $responsibleId && $companyId) {
            $hook = PortalController::getHook($domain);
            BitrixGeneralService::updateCompany(
                $hook,
                $companyId,
                ["ASSIGNED_BY_ID" => $responsibleId]
            );
        }
    });

    Route::post('/companies/search', function (Request $request) {
        $data = $request->all();
        $domain = '';
        $lead = null;
        $leadId = null;
        $companies = null;
        // $companyId = '';
        // Log::channel('telegram')->error('APRIL_HOOK', [
        //     'data'  =>  $data,
        // ]);
        if (!empty($data['auth'])) {

            if (!empty($data['auth']['domain'])) {
                $domain = $data['auth']['domain'];
            }
            Log::channel('telegram')->error('APRIL_HOOK', [
                'auth companies/search'  =>  $data['auth'],
            ]);
            if (!empty($data['leadId'])) {
                $leadId = $data['leadId'];
            }

            // if (!empty($data['responsible'])) {

            //     $partsResponsible = explode("_", $data['responsible']);
            //     $responsibleId = $partsResponsible[1];
            // }
        }
        Log::channel('telegram')->error('APRIL_HOOK', [
            'domain'  =>  $domain,
            // 'responsibleId'  =>  $responsibleId,
            'leadId'  =>  $leadId,

        ]);
        $select = ['TITLE',  'ID', 'EMAIL', 'PHONE'];

        if (!empty($domain) &&  $leadId) {
            $hook = PortalController::getHook($domain);
            $lead = BitrixGeneralService::getEntity(
                $hook,
                'lead',
                $leadId,
                null,
                $select

            );
            Log::channel('telegram')->error('APRIL_HOOK', [
                'domain'  =>  $domain,


            ]);
            // PHONE":[
            // {"ID":"407425","VALUE_TYPE":"WORK","VALUE":"+79620027991","TYPE_ID":"PHONE"},
            // {"ID":"407429","VALUE_TYPE":"WORK","VALUE":"+79678787898","TYPE_ID":"PHONE"}]},"leadId":"42669"}
            if (!empty($lead)) {
                Log::channel('telegram')->error('APRIL_HOOK', [
                    'domain'  =>  $domain,
                    'lead'  =>  $lead,


                ]);
                if (!empty($lead['PHONE'])) {

                    $phones = [];
                    $emails = [];

                       
                    foreach ($lead['PHONE'] as $phone) {
                        array_push($phones, $phone['VALUE']);
                        $filter = [
                            'PHONE' => $phone
                        ];
                    
                        $result = BitrixGeneralService::getEntityList(
                            $hook,
                            'company',
                            $filter,
                            $select
                        );
                    
                        if (!empty($result)) {
                            $companies = array_merge($companies, $result);
                        }
                    }
                    Log::channel('telegram')->info('APRIL_HOOK', [
                        'phones'  =>  $phones,


                    ]);
                    // foreach ($lead['EMAIL'] as $email) {
                    //     array_push($emails, $email['VALUE']);
                    // }
                    // $filter = [
                    //     'LOGIC' => 'OR',
                    //     [
                    //         'PHONE' => $phones, // условие по телефонам
                    //     ],
                    //     [
                    //         'EMAIL' => $emails, // условие по e-mail
                    //     ]

                    // ];
                    // $filter = [
                    //     'LOGIC' => 'OR',
                    //     array_map(function ($phone) {
                    //         return ['PHONE' => $phone];
                    //     }, $phones)

                    // ];
                    // $companies = BitrixGeneralService::getEntityList(
                    //     $hook,
                    //     'company',
                    //     $filter,
                    //     $select
                    // );
                    Log::channel('telegram')->error('APRIL_HOOK', [
                        'filter'  =>  $filter,


                    ]);
                    Log::channel('telegram')->error('APRIL_HOOK', [
                        'companies'  =>  $companies,


                    ]);
                    // foreach ($phones as $phone) {
                    //     if(!empty($phone)){
                    //         $
                    //     }
                    // }
                }
            }
            Log::channel('telegram')->info('APRIL_HOOK', [
                'domain'  =>  $domain,
                // 'lead'  =>  $lead,
                'companies'  =>  $companies,

            ]);
            Log::info('APRIL_HOOK', [
                'domain'  =>  $domain,
                // 'lead'  =>  $lead,
                'companies'  =>  $companies,

            ]);
        }
    });
});
