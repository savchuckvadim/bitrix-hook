<?php

use App\Http\Controllers\APIOnlineController;
use App\Http\Controllers\Front\EventCalling\FullEventInitController;
use App\Http\Controllers\Front\EventCalling\ReportController;
use App\Http\Controllers\Front\EventCalling\ReportSupplyController;
use App\Http\Controllers\Front\ReportKPI\ReportKPIController;
use App\Http\Controllers\PortalController;
use App\Jobs\EventJob;
use App\Services\BitrixGeneralService;
use App\Services\FullEventReport\EventDocumentService;
use App\Services\General\BitrixTimeLineService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
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
        // Log::info('HOOK TST', [
        //     'comedata' => $comedata,


        // ]);
        // Log::channel('telegram')->info('HOOK TST', [

        //     '$comedata' => $comedata

        // ]);
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
                $comment = 'ТМЦ: ' . $comedata['commentTMC'] . "   ";
            }
            if (!empty($comedata['commentOwner'])) {
                $comment = $comment . "\n" . 'Руководитель: ' . $comedata['commentOwner'] . "   ";
            }
            if (!empty($comedata['commentTMC'])) {
                $comment = $comment . "\n" . 'Менеджер: ' . $comedata['commentManager'] . "   ";
            }



            $partsCreated = explode("_", $comedata['ownerId']);
            $partsResponsible = explode("_", $comedata['managerId']);
            $partsTmc = explode("_", $comedata['tmcId']);
            $createdId = $partsCreated[1];
            $responsibleId = $partsResponsible[1];
            $tmcId = $partsTmc[1];


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
            $planContact = null;

            if (!empty($comedata['contactId'])) {
                $planContact = [
                    "ID" => $comedata['contactId'],

                    "NAME" => '',
                    "POST" => '',
                    'PHONE' => '',
                    'EMAIL' => '',

                ];
            }

            $data['plan'] = [
                'contact' => $planContact,

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
                "tmc" => [
                    "ID" => $tmcId,
                    // "XML_ID" => "",
                    // "ACTIVE" => true,
                    // "NAME" => "",
                    // "LAST_NAME" => "",
                    // "SECOND_NAME" => ""
                ],
                "deadline" => $comedata['deadline'],
                "isPlanned" => true,
                "name" => $comedata['name'],
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
            // Log::info('HOOK TST', [
            //     'currentBaseDeals' => $currentBaseDeals,
            //     'baseDealSession' => $baseDealSession,

            //     'tmcDealSession' => $tmcDealSession,
            //     'plan' => $data['plan']

            // ]);


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
    Route::post('/comment/save', function (Request $request) {

        $comedata = $request->all();
        try {
            $domain = $comedata['domain'];

            $companyId = $comedata['companyId'];
            $userId = $comedata['userId'];
            $key = $domain . '_' . $companyId . '_' . $userId . '_comment';
            $comment = $comedata['comment'];
            Redis::set($key, $comment);
            $savedData =  Redis::get($key);
            $result = [
                $key => $savedData
            ];

            return APIOnlineController::getSuccess(['result' => $result]);
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
    Route::post('/comment/get', function (Request $request) {

        $comedata = $request->all();
        try {
            $domain = $comedata['domain'];

            $companyId = $comedata['companyId'];
            $userId = $comedata['userId'];
            $key = $domain . '_' . $companyId . '_' . $userId . '_comment';
            $comment =  Redis::get($key);

            Redis::del($key, $comment);

            return APIOnlineController::getSuccess(['comment' => $comment]);
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
        $service = new EventDocumentService($data);
        $result = $service->getDocumentFlow();
        return APIOnlineController::getSuccess(
            ['contractData' => $data, 'link' => $data, 'service' => $result]
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



    //UTILS
    // Route::post('/contract/flow', [ReportController::class, 'eventReport']);

    // https://april-hook.ru/api/full/company/update?responsible={{ОП Кто назначен ответственным}}&companyId={{Компания}}
    Route::post('/company/update', function (Request $request) {
        $data = $request->all();
        $domain = '';
        $responsibleId = '';
        $companyId = '';

        if (!empty($data['auth'])) {

            if (!empty($data['auth']['domain'])) {
                $domain = $data['auth']['domain'];
            }

            if (!empty($data['companyId'])) {
                $companyId = $data['companyId'];
            }

            if (!empty($data['responsible'])) {

                $partsResponsible = explode("_", $data['responsible']);
                $responsibleId = $partsResponsible[1];
            }
        }

        if (!empty($domain) && $responsibleId && $companyId) {
            $hook = PortalController::getHook($domain);
            BitrixGeneralService::updateCompany(
                $hook,
                $companyId,
                ["ASSIGNED_BY_ID" => $responsibleId]
            );
            sleep(1);
            // переводим все контакты на ответственного
            BitrixGeneralService::updateContactsToCompanyRespnsible(
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
        $companies = [];
        // $companyId = '';
        // Log::channel('telegram')->error('APRIL_HOOK', [
        //     'data'  =>  $data,
        // ]);
        if (!empty($data['auth'])) {

            if (!empty($data['auth']['domain'])) {
                $domain = $data['auth']['domain'];
            }
            // Log::channel('telegram')->error('APRIL_HOOK', [
            //     'auth companies/search'  =>  $data['auth'],
            // ]);
            if (!empty($data['leadId'])) {
                $leadId = $data['leadId'];
            }

            // if (!empty($data['responsible'])) {

            //     $partsResponsible = explode("_", $data['responsible']);
            //     $responsibleId = $partsResponsible[1];
            // }
        }
        // Log::channel('telegram')->error('APRIL_HOOK', [
        //     'domain'  =>  $domain,
        //     // 'responsibleId'  =>  $responsibleId,
        //     'leadId'  =>  $leadId,

        // ]);
        $select = ['TITLE',  'ID', 'EMAIL', 'PHONE', 'UF_CRM_OP_MHISTORY', 'UF_CRM_OP_CURRENT_STATUS'];

        if (!empty($domain) &&  $leadId) {
            $hook = PortalController::getHook($domain);
            $lead = BitrixGeneralService::getEntity(
                $hook,
                'lead',
                $leadId,
                null,
                $select

            );
            // Log::channel('telegram')->error('APRIL_HOOK', [
            //     'domain'  =>  $domain,


            // ]);
            // PHONE":[
            // {"ID":"407425","VALUE_TYPE":"WORK","VALUE":"+79620027991","TYPE_ID":"PHONE"},
            // {"ID":"407429","VALUE_TYPE":"WORK","VALUE":"+79678787898","TYPE_ID":"PHONE"}]},"leadId":"42669"}
            if (!empty($lead)) {
                // Log::channel('telegram')->error('APRIL_HOOK', [
                //     'domain'  =>  $domain,
                //     'lead'  =>  $lead,


                // ]);
                if (!empty($lead['PHONE'])) {

                    $phones = [];
                    $emails = [];


                    foreach ($lead['PHONE'] as $phone) {
                        array_push($phones, $phone['VALUE']);
                        $filter = [
                            'PHONE' => $phone['VALUE']
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
                    // Log::channel('telegram')->info('APRIL_HOOK', [
                    //     'phones'  =>  $phones,


                    // ]);
                    // companies":[
                    // {"TITLE":"TEST 134",
                    //     "ID":"171",
                    // "UF_CRM_OP_MHISTORY":["14.08.2024 ХО запланирован от 14 августа 2024 на 27.08.2024 15:18:00","03.09.2024 12:14:37 Презентация запланирована weg"],
                    // "UF_CRM_OP_CURRENT_STATUS":"В работе: Презентация запланирована Заявка weg от 03.09.2024 12:14:37",
                    // "PHONE":
                    // [{"ID":"104273","VALUE_TYPE":"WORK","VALUE":"+79620027991","TYPE_ID":"PHONE"}]},
                    // {"TITLE":"Тест","ID":"95763","UF_CRM_OP_MHISTORY":["11.09.2024 ХО запланирован от 11 сентября 2024 на 11.09.2024 12:59:00","11.09.2024 12:01:18 Результативный Холодный обзвон   от 11 сентября 2024  11.09.2024 12:59:00"],"UF_CRM_OP_CURRENT_STATUS":"Звонок запланирован в работе","PHONE":[{"ID":"406655","VALUE_TYPE":"WORK","VALUE":"+79620027991","TYPE_ID":"PHONE"}]}]}

                    $timeLineString = '';

                    if (!empty($companies)) {
                        $timeLineString = 'Возможно это:';
                        foreach ($companies as $company) {
                            $companyId = $company['ID'];
                            $companyTitle = $company['TITLE'];
                            $companyLink = 'https://' . $domain . '/crm/company/details/' . $companyId . '/';
                            $message = 'Компания: <a href="' . $companyLink . '" target="_blank">' . $companyTitle . '</a>';


                            $timeLineString .= "\n" . $message;
                            $timeLineString .= "\n"  . 'Статус: ' . $company['UF_CRM_OP_CURRENT_STATUS'];



                            // $timeLineString .= "\n" . $message;
                            // if (!empty($company['UF_CRM_OP_MHISTORY'])) {
                            //     $timeLineString .= "\n" . 'История: ';
                            //     $mHistory = $company['UF_CRM_OP_MHISTORY'];
                            //     foreach ($mHistory as $historyString) {
                            //         $timeLineString .= "\n" . $historyString;
                            //     }
                            // }
                            $timeLineString .= "\n";
                        }
                    }
                    if (empty($timeLineString)) {
                        $timeLineString = 'Совпадений по компаниям не найдено';
                    }

                    $bxTimeLineService = new BitrixTimeLineService($hook);
                    $bxTimeLineService->setTimeline($timeLineString, 'lead', $leadId);
                    // Log::channel('telegram')->error('APRIL_HOOK', [
                    //     'filter'  =>  $filter,


                    // ]);
                    // Log::channel('telegram')->error('APRIL_HOOK', [
                    //     'companies'  =>  $companies,


                    // ]);
                    // foreach ($phones as $phone) {
                    //     if(!empty($phone)){
                    //         $
                    //     }
                    // }
                }
            }
            // Log::channel('telegram')->info('APRIL_HOOK', [
            //     'domain'  =>  $domain,
            //     // 'lead'  =>  $lead,
            //     'companies'  =>  $companies,

            // ]);
            // Log::info('APRIL_HOOK', [
            //     'domain'  =>  $domain,
            //     // 'lead'  =>  $lead,
            //     'companies'  =>  $companies,

            // ]);
        }
    });

    Route::post('/company/contacts', function (Request $request) {
        $data = $request->all();
        $domain = '';
        $responsibleId = '';
        $companyId = '';

        if (!empty($data['auth'])) {

            if (!empty($data['auth']['domain'])) {
                $domain = $data['auth']['domain'];
            }

            if (!empty($data['companyId'])) {
                $companyId = $data['companyId'];
            }

            if (!empty($data['responsible'])) {

                $partsResponsible = explode("_", $data['responsible']);
                $responsibleId = $partsResponsible[1];
            }
        }

        
        if (!empty($domain) && $responsibleId && $companyId) {
            $hook = PortalController::getHook($domain);
            BitrixGeneralService::updateContactsToCompanyRespnsible(
                $hook,
                $companyId,
                ["ASSIGNED_BY_ID" => $responsibleId]
            );
        }
    });

    // Route::post('test', function (Request $request) {
    //     $data = $request->all();
    //     Log::channel('telegram')->info('data', ['data' => $data]);
    
    //     Log::channel('telegram')->info('APRIL_HOOK', [
    
    //         'date_from' => $request['date_from'],
    //         'date_to' => $request['date_from'],
    //         'user_inner_code' => $request['user_inner_code'],
    //         'client_phone_number' => $request['client_phone_number'],

    //         // 'название обзвона' => $name,
    //         // 'companyId' => $companyId,
    //         // 'domain' => $domain,
    //         // 'responsibleId' => $responsibleId,
    //         // 'btrx response' => $response['error_description']
    
    //     ]);
    //     return APIOnlineController::getSuccess($data);
    // });
});
