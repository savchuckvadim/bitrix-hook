<?php

namespace App\Http\Controllers\Front\EventCalling;

use App\Http\Controllers\APIController;
use App\Http\Controllers\APIOnlineController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\PortalController;
use App\Jobs\EventJob;
use App\Services\BitrixGeneralService;
use App\Services\FullEventReport\EventReportService;
use App\Services\FullEventReport\EventReportTMCService;
use App\Services\General\BitrixDealService;
use App\Services\General\BitrixDepartamentService;
use App\Services\General\BitrixListService;
use App\Services\HookFlow\BitrixEntityFlowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ReportController extends Controller
{
    public static function eventReport(Request $request)
    {
        try {
            $data = [
                'currentTask' => null,
                'report' => null,
                'plan' => null,
                'placement' => null,
                'presentation' => null,
                'domain' => null,
                'departament' => null,
                'sale' => null,
                'contact' => null,
                'lead' => null,
                'fail' => null,

            ];
            $isFullData = true;
            if (isset($request->lead)) {
                $data['lead'] = $request->lead;
                Log::channel('telegram')->info(
                    '$request->lead',
                    [
                        'lead' => $request->lead,

                    ]
                );
            }
            if (isset($request->fail)) {
                $data['fail'] = $request->fail;
                Log::channel('telegram')->info(
                    '$request->fail',
                    [
                        'fail' => $request->fail,

                    ]
                );
            }

            if (isset($request->currentTask)) {
                $data['currentTask'] = $request->currentTask;
            }

            if (isset($request->report)) {
                $data['report'] = $request->report;
            } else {
                $isFullData = false;
            }
            if (isset($request->plan)) {
                $data['plan'] = $request->plan;
            } else {
                $isFullData = false;
            }
            if (isset($request->placement)) {
                $data['placement'] = $request->placement;
            } else {
                $isFullData = false;
            }
            if (isset($request->domain)) {
                $data['domain'] = $request->domain;
            } else {
                $isFullData = false;
            }
            if (isset($request->presentation)) {
                $data['presentation'] = $request->presentation;
            } else {
                $isFullData = false;
            }
            if (isset($request->departament)) {
                $data['departament'] = $request->departament;
            } else {
                $isFullData = false;
            }
            if (isset($request->sale)) {
                $data['sale'] = $request->sale;
            } else {
                $isFullData = false;
            }
            if (isset($request->contact)) {
                $data['contact'] = $request->contact;
            }
            if ($isFullData) {
                // $service = new EventReportService($data);
                // $result = $service->getEventFlow();
                // return $result;
                $isTmc = false;
                if (!empty($data)) {
                    if (!empty($data['departament'])) {

                        if (!empty($data['departament']['mode'])) {
                            if (!empty($data['departament']['mode']['code'])) {
                                if (!empty($data['departament']['mode']['code'])) {
                                    if ($data['departament']['mode']['code'] == 'tmc') {
                                        $isTmc = true;
                                    }
                                }
                            }
                        }
                    }
                }
                // if ($isTmc) {
                //     // Log::channel('telegram')->info("Redis tmc queue.");
                //     $service = new EventReportTMCService($data);
                //     return $service->getEventFlow();
                // } else {


                dispatch(
                    new EventJob($data)
                )->onQueue('high-priority');
                // }
                return APIOnlineController::getSuccess(
                    [
                        'result' => 'success',
                        'message' => 'job !'

                    ]

                );
            } else {

                return APIOnlineController::getError(
                    'is not full data',
                    [
                        'rq' => $request->all()

                    ]

                );
            }
        } catch (\Throwable $th) {
            return APIOnlineController::getError(
                $th->getMessage(),
                [
                    'task' => [
                        'message' => 'success'
                    ],
                    'rq' => $request->all()

                ]

            );
        }
    }

    public static function getPresCounts(Request $request)
    {

        // entities [deal, smart ...]
        //companyId
        //userId
        //currentTask
        $result = null;
        try {
            $data = $request->all();
            if (
                // !empty($data['userId'])  &&
                // !empty($data['companyId']) &&
                !empty($data['domain']) &&
                !empty($data['currentTask'])
            ) {



                // $companyId = $data['userId'];
                // $userId = $data['companyId'];
                $domain = $data['domain'];
                $btxDeals = []; //from task
                $currentTask =  $data['currentTask'];


                $result = [
                    'counts' => [
                        'deal' => 0,
                        'company' => 0,
                    ],
                    'deal' => null
                ];
                $portal = PortalController::getPortal($domain);
                $portal = $portal['data'];
                $webhookRestKey = $portal['C_REST_WEB_HOOK_URL'];
                $hook = 'https://' . $domain  . '/' . $webhookRestKey;
                // $currentCompany = BitrixGeneralService::getEntity($hook, 'company', $companyId);


                //from task - получаем из task компании и сделки разных направлений

                $currentBtxEntities =  BitrixEntityFlowService::getEntities(
                    $hook,
                    $currentTask,
                );
                if (!empty($currentBtxEntities)) {
                    if (!empty($currentBtxEntities['companies'])) {
                        $currentCompany = $currentBtxEntities['companies'][0];
                    }
                    if (!empty($currentBtxEntities['deals'])) {
                        $btxDeals = $currentBtxEntities['deals'];
                    }
                }



                $btxDealPortalCategories = null;
                $currentCategoryData  = null;
                if (!empty($portal['bitrixDeal'])) {
                    if (!empty($portal['bitrixDeal']['categories'])) {
                        $btxDealPortalCategories = $portal['bitrixDeal']['categories'];
                    }
                }


                if (!empty($btxDealPortalCategories)) {
                    foreach ($btxDealPortalCategories as $btxDealPortalCategory) {
                        if (!empty($btxDealPortalCategory['code'])) {
                            if ($btxDealPortalCategory['code'] == "sales_base") {
                                foreach ($btxDeals as $btxDeal) {
                                    if (!empty($btxDeal['CATEGORY_ID'])) {
                                        if ($btxDeal['CATEGORY_ID'] == $btxDealPortalCategory['bitrixId']) {
                                            $currentDeal = $btxDeal;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }


                if (!empty($currentDeal) && !empty($currentCompany)) {

                    if (isset($currentDeal['UF_CRM_PRES_COUNT'])) {
                        $result['counts']['deal'] = (int)$currentDeal['UF_CRM_PRES_COUNT'];
                    }
                    if (isset($currentCompany['UF_CRM_1709807026'])) {
                        $result['counts']['company'] = (int)$currentCompany['UF_CRM_1709807026'];
                    }

                    if (isset($currentCompany['UF_CRM_PRES_COUNT'])) {
                        $result['counts']['company'] = (int)$currentCompany['UF_CRM_PRES_COUNT'];
                    }
                }

                return APIOnlineController::getSuccess(
                    [
                        'presentation' => $result

                    ]

                );
            } else {
                return APIOnlineController::getError(
                    'is not full data',
                    [
                        'rq' => $request->all()

                    ]

                );
            }


            return APIOnlineController::getSuccess(
                [
                    'result' => 'success',
                    'message' => 'job !'

                ]

            );
        } catch (\Throwable $th) {
            return APIOnlineController::getError(
                $th->getMessage(),
                [
                    'task' => [
                        'message' => 'success'
                    ],
                    'rq' => $request->all()

                ]

            );
        }
    }

    public static function getFullDeals(Request $request) // GET DEALS AND INIT REDIS INIT EVENT FROM TASK  
    {

        // entities [deal, smart ...]
        //companyId
        //userId
        //currentTask
        $result = null;
        try {
            $data = $request->all();
            if (
                // !empty($data['userId'])  &&
                // !empty($data['companyId']) &&
                !empty($data['domain']) &&
                !empty($data['currentTask'])
            ) {
                $select = [
                    'ID',
                    'TITLE',
                    'UF_CRM_PRES_COUNT',
                    // 'UF_CRM_1709807026',


                    'CATEGORY_ID',
                    'ASSIGNED_BY_ID',
                    // 'COMPANY_ID',
                    'STAGE_ID',
                    // 'XO_NAME',
                    // 'XO_DATE',
                    // 'XO_RESPONSIBLE',
                    // 'XO_CREATED',
                    // 'NEXT_PRES_PLAN_DATE',
                    // 'LAST_PRES_PLAN_DATE',
                    // 'LAST_PRES_DONE_DATE',
                    // 'LAST_PRES_PLAN_RESPONSIBLE',
                    // 'LAST_PRES_DONE_RESPONSIBLE',

                    'UF_CRM_PRES_COMMENTS',
                    'UF_CRM_MANAGER_OP',
                    'UF_CRM_MANAGER_TMC',
                    // 'MANAGER_OS',
                    // 'MANAGER_EDU',
                    // 'CALL_NEXT_DATE',
                    // 'CALL_NEXT_NAME',
                    // 'CALL_LAST_DATE',
                    // 'GO_PLAN',
                    'UF_CRM_OP_HISTORY',
                    'UF_CRM_OP_MHISTORY',
                    // 'OP_WORK_STATUS',
                    // 'OP_PROSPECTS_TYPE',
                    // 'OP_EFIELD_FAIL_REASON',
                    // 'OP_FAIL_COMMENTS',
                    // 'OP_NORESULT_REASON',
                    // 'OP_CLIENT_STATUS',
                    // 'OP_PROSPECTS',
                    // 'OP_CLIENT_TYPE',
                    // 'OP_CONCURENTS',
                    // 'OP_CATEGORY',
                    // 'OP_SMART_COMPANY_ID',
                    // 'OP_SMART_ID',
                    // 'OP_SMART_LID',
                    // 'OP_SMART_LIDS',
                    // 'OFFER_SUM',
                    'UF_CRM_TO_BASE_SALES',
                    'UF_CRM_TO_XO_SALES',
                    'UF_CRM_TO_PRESENTATION_SALES',
                    'UF_CRM_TO_BASE_TMC',
                    'UF_CRM_TO_PRESENTATION_TMC',
                    'UF_CRM_TO_BASE_SERVICE',
                    'UF_CRM_OP_CURRENT_STATUS',

                ];
                $responsibleId = 1;
                $currentBaseDeal = null;               //базовая сделка в задаче всегда должна быть одна
                $currentPresentationDeal = null;               // сделка презентации из задачи
                $currentXODeal = null;


                $allBaseDeals = [];
                $basePresentationDeals = [];                 // сделки презентаций связанные с основной через списки
                $allPresentationDeals = [];                  // все сделки презентации связанные с компанией и пользователем
                $currentCompany = null;
                $allXODeals = [];
                $allTMCDeals = [];
                $currentTMCDeal = null;
                // $companyId = $data['userId'];
                // $userId = $data['companyId'];
                $domain = $data['domain'];
                $companyId  = $data['domain'];
                $btxDeals = []; //from task
                $currentTask =  $data['currentTask'];


                $getAllPresDealsData = [];

                $portal = PortalController::getPortal($domain);
                $portal = $portal['data'];
                $webhookRestKey = $portal['C_REST_WEB_HOOK_URL'];
                $hook = 'https://' . $domain  . '/' . $webhookRestKey;
                // $currentCompany = BitrixGeneralService::getEntity($hook, 'company', $companyId);
                $sessionKey = $domain . '_' . $currentTask['id'];


                $presList = null;



                //from task - получаем из task компании и сделки разных направлений

                $currentBtxEntities =  BitrixEntityFlowService::getEntities(
                    $hook,
                    $currentTask,
                );
                if (!empty($currentBtxEntities)) {
                    if (!empty($currentBtxEntities['companies'])) {
                        $currentCompany = $currentBtxEntities['companies'][0];
                    }
                    if (!empty($currentBtxEntities['deals'])) {
                        $btxDeals = $currentBtxEntities['deals'];
                    }
                }



                $btxDealPortalCategories = null;
                $currentCategoryData  = null;
                $allExecludeStages =  [];
                $allIncludeCategories =  [];
                if (!empty($portal['bitrixDeal'])) {
                    if (!empty($portal['bitrixDeal']['categories'])) {
                        $btxDealPortalCategories = $portal['bitrixDeal']['categories'];
                    }
                }

                if (!empty($btxDealPortalCategories)) {
                    // if (!empty($btxDealPortalCategory['code'])) {
                    //     foreach ($btxDealPortalCategories as $btxDealPortalCategory) {
                    //         if ($btxDealPortalCategory['code'] === "sales_base" || $btxDealPortalCategory['code'] === "sales_presentation") {
                    //             $currenBaseCategoryBtxId = $btxDealPortalCategory['bitrixId'];

                    //             array_push($allExecludeStages, 'C' . $currenBaseCategoryBtxId . ':LOSE');
                    //             array_push($allExecludeStages, 'C' . $currenBaseCategoryBtxId . ':APOLOGY');
                    //             if ($btxDealPortalCategory['code'] == "sales_base") {
                    //                 array_push($allExecludeStages, 'C' . $currenBaseCategoryBtxId . ':WON');
                    //             }
                    //             array_push($allIncludeCategories, $currenBaseCategoryBtxId);
                    //         }
                    //     }
                    // }

                    // $getAllDealsData =  [
                    //     'filter' => [
                    //         'COMPANY_ID' => $currentCompany['ID'],
                    //         'CATEGORY_ID' => $allIncludeCategories,
                    //         'RESPONSIBLE_ID' => $responsibleId,
                    //         '!=STAGE_ID' => $allExecludeStages
                    //     ],
                    //     'select' => [
                    //         'ID',
                    //         'TITLE',
                    //         'UF_CRM_PRES_COUNT',
                    //         'STAGE_ID',
                    //         'UF_CRM_TO_BASE_SALES',
                    //         'CATEGORY_ID'

                    //     ]
                    // ];


                    // $allDeals =   BitrixDealService::getDealList(
                    //     $hook,
                    //     $getAllDealsData,
                    // );

                    //task current deals

                    foreach ($btxDealPortalCategories as $btxDealPortalCategory) {
                        if (!empty($btxDealPortalCategory['code'])) {
                            if ($btxDealPortalCategory['code'] == "sales_base") {



                                foreach ($btxDeals as $btxDeal) {
                                    $currenBaseCategoryBtxId = $btxDealPortalCategory['bitrixId'];

                                    if (!empty($btxDeal['CATEGORY_ID'])) {
                                        if ($btxDeal['CATEGORY_ID'] == $btxDealPortalCategory['bitrixId']) {
                                            $currentBaseDeal = $btxDeal;   //базовая сделка в задаче всегда должна быть одна
                                        }
                                    }
                                }
                            } else  if ($btxDealPortalCategory['code'] == "sales_presentation") {
                                $currentPresentCategoryBtxId = $btxDealPortalCategory['bitrixId'];

                                foreach ($btxDeals as $btxDeal) {
                                    if (!empty($btxDeal['CATEGORY_ID'])) {
                                        if ($btxDeal['CATEGORY_ID'] == $currentPresentCategoryBtxId) {
                                            $currentPresentationDeal = $btxDeal;      // сделка презентации из задачи

                                        }
                                    }
                                }
                            } else  if ($btxDealPortalCategory['code'] == "sales_xo") {
                                $currentXOCategoryBtxId = $btxDealPortalCategory['bitrixId'];

                                foreach ($btxDeals as $btxDeal) {
                                    if (!empty($btxDeal['CATEGORY_ID'])) {
                                        if ($btxDeal['CATEGORY_ID'] == $currentXOCategoryBtxId) {
                                            $currentXODeal = $btxDeal;      // сделка презентации из задачи

                                        }
                                    }
                                }
                            } else  if ($btxDealPortalCategory['code'] == "tmc_base") {
                                $currentTMCCategoryBtxId = $btxDealPortalCategory['bitrixId'];

                                foreach ($btxDeals as $btxDeal) {
                                    if (!empty($btxDeal['CATEGORY_ID'])) {
                                        if ($btxDeal['CATEGORY_ID'] == $currentTMCCategoryBtxId) {
                                            $currentTMCDeal = $btxDeal;      // сделка презентации из задачи

                                        }
                                    }
                                }
                            }
                        }
                    }

                    //deal from company user by category
                    // foreach ($allDeals as $btxDealFromAll) {
                    //     foreach ($btxDealPortalCategories as $btxDealPortalCategory) {
                    //         if (!empty($btxDealPortalCategory['code'])) {
                    //             if ($btxDealPortalCategory['code'] == "sales_base") {

                    //                 if ($btxDealFromAll['CATEGORY_ID'] == $btxDealPortalCategory['bitrixId']) {
                    //                     array_push($allBaseDeals, $btxDealFromAll);
                    //                 }
                    //             } else  if ($btxDealPortalCategory['code'] == "sales_presentation") {
                    //                 $currentPresentCategoryBtxId = $btxDealPortalCategory['bitrixId'];
                    //                 if ($btxDealFromAll['CATEGORY_ID'] == $btxDealPortalCategory['bitrixId']) {
                    //                     array_push($allPresentationDeals, $btxDealFromAll);


                    //                     if (!empty($currentBaseDeal)) {
                    //                         if (!empty($currentBaseDeal['ID'])) {

                    //                             if (!empty($btxDealFromAll['UF_CRM_TO_BASE_SALES'])) {
                    //                                 if ($btxDealFromAll['UF_CRM_TO_BASE_SALES'] == $currentBaseDeal['ID']) {
                    //                                     array_push($basePresentationDeals, $btxDealFromAll);
                    //                                 }
                    //                             }
                    //                         }
                    //                     }
                    //                 }
                    //             }
                    //         } else  if ($btxDealPortalCategory['code'] == "sales_xo") {
                    //             $currentXOCategoryBtxId = $btxDealPortalCategory['bitrixId'];
                    //             if ($btxDealFromAll['CATEGORY_ID'] == $currentXOCategoryBtxId) {
                    //                 array_push($allXODeals, $btxDealFromAll);
                    //             }
                    //         }
                    //     }
                    // }






                }



                foreach ($btxDealPortalCategories as $btxDealPortalCategory) {
                    if (!empty($btxDealPortalCategory['code'])) {
                        if ($btxDealPortalCategory['code'] == "sales_base") {



                            foreach ($btxDeals as $btxDeal) {
                                $currenBaseCategoryBtxId = $btxDealPortalCategory['bitrixId'];

                                if (!empty($btxDeal['CATEGORY_ID'])) {
                                    if ($btxDeal['CATEGORY_ID'] == $btxDealPortalCategory['bitrixId']) {
                                        $currentBaseDeal = $btxDeal;   //базовая сделка в задаче всегда должна быть одна
                                    }
                                }


                                $getAllBaseDealsData =  [
                                    'filter' => [
                                        'COMPANY_ID' => $currentCompany['ID'],
                                        'CATEGORY_ID' => $currenBaseCategoryBtxId,
                                        'RESPONSIBLE_ID' => $responsibleId,
                                        '!=STAGE_ID' => ['C' . $currenBaseCategoryBtxId . ':WON', 'C' . $currenBaseCategoryBtxId . ':LOSE', 'C' . $currenBaseCategoryBtxId . ':APOLOGY']
                                    ],
                                    'select' => $select

                                ];


                                $allBaseDeals =   BitrixDealService::getDealList(
                                    $hook,
                                    $getAllBaseDealsData,
                                );
                            }
                        } else  if ($btxDealPortalCategory['code'] == "sales_presentation") {
                            $currentPresentCategoryBtxId = $btxDealPortalCategory['bitrixId'];

                            foreach ($btxDeals as $btxDeal) {
                                if (!empty($btxDeal['CATEGORY_ID'])) {
                                    if ($btxDeal['CATEGORY_ID'] == $currentPresentCategoryBtxId) {
                                        $currentPresentationDeal = $btxDeal;      // сделка презентации из задачи

                                    }
                                }


                                $getAllPresDealsData =  [
                                    'filter' => [
                                        'COMPANY_ID' => $currentCompany['ID'],
                                        'CATEGORY_ID' => $currentPresentCategoryBtxId,
                                        'RESPONSIBLE_ID' => $responsibleId,
                                        '!=STAGE_ID' => ['C' . $currentPresentCategoryBtxId . ':LOSE', 'C' . $currentPresentCategoryBtxId . ':APOLOGY']
                                    ],
                                    'select' => $select
                                ];

                                // sleep(1);
                                $allPresentationDeals =   BitrixDealService::getDealList(
                                    $hook,
                                    $getAllPresDealsData,
                                );

                                if (!empty($currentBaseDeal)) {
                                    if (!empty($currentBaseDeal['ID'])) {
                                        $getAllPresDealsData =  [
                                            'filter' => [
                                                'COMPANY_ID' => $currentCompany['ID'],
                                                'CATEGORY_ID' => $currentPresentCategoryBtxId,
                                                'RESPONSIBLE_ID' => $responsibleId,
                                                '!=STAGE_ID' => ['C' . $currentPresentCategoryBtxId . ':LOSE', 'C' . $currentPresentCategoryBtxId . ':APOLOGY'],
                                                'UF_CRM_TO_BASE_SALES' => $currentBaseDeal['ID']
                                            ],
                                            'select' => $select
                                        ];


                                        $basePresentationDeals =   BitrixDealService::getDealList(
                                            $hook,
                                            $getAllPresDealsData,
                                        );
                                    }
                                }
                            }
                        } else  if ($btxDealPortalCategory['code'] == "sales_xo") {
                            $currentXOCategoryBtxId = $btxDealPortalCategory['bitrixId'];

                            foreach ($btxDeals as $btxDeal) {
                                if (!empty($btxDeal['CATEGORY_ID'])) {
                                    if ($btxDeal['CATEGORY_ID'] == $currentXOCategoryBtxId) {
                                        $currentXODeal = $btxDeal;      // сделка презентации из задачи

                                    }
                                }


                                $getAllXODealsData =  [
                                    'filter' => [
                                        'COMPANY_ID' => $currentCompany['ID'],
                                        'CATEGORY_ID' => $currentXOCategoryBtxId,
                                        'RESPONSIBLE_ID' => $responsibleId,
                                        '!=STAGE_ID' => ['C' . $currentXOCategoryBtxId . ':LOSE', 'C' . $currentXOCategoryBtxId . ':APOLOGY']
                                    ],
                                    'select' => $select
                                ];

                                $rand = mt_rand(300000, 1000000); // случайное число от 300000 до 900000 микросекунд (0.3 - 0.9 секунды)
                                usleep($rand);
                                $allXODeals =   BitrixDealService::getDealList(
                                    $hook,
                                    $getAllXODealsData,
                                );
                            }
                        } else  if ($btxDealPortalCategory['code'] == "tmc_base") {



                            foreach ($btxDeals as $btxDeal) {
                                $currenBaseCategoryBtxId = $btxDealPortalCategory['bitrixId'];

                                if (!empty($btxDeal['CATEGORY_ID'])) {
                                    if ($btxDeal['CATEGORY_ID'] == $btxDealPortalCategory['bitrixId']) {
                                        $currentTMCDeal = $btxDeal;   //базовая сделка в задаче всегда должна быть одна
                                    }
                                }


                                $getAllTMCDealsData =  [
                                    'filter' => [
                                        'COMPANY_ID' => $currentCompany['ID'],
                                        'CATEGORY_ID' => $currenBaseCategoryBtxId,
                                        'RESPONSIBLE_ID' => $responsibleId,
                                        '!=STAGE_ID' => ['C' . $currenBaseCategoryBtxId . ':WON', 'C' . $currenBaseCategoryBtxId . ':LOSE', 'C' . $currenBaseCategoryBtxId . ':APOLOGY']
                                    ],
                                    'select' => $select

                                ];


                                $allTMCDeals =   BitrixDealService::getDealList(
                                    $hook,
                                    $getAllTMCDealsData,
                                );
                            }
                        }
                    }
                }

                if (empty($currentTMCDeal) && !empty($currentPresentationDeal) && !empty($allTMCDeals)) {

                    if (!empty($currentPresentationDeal['ID'])) {
                        foreach ($allTMCDeals as $key => $tmcDeal) {
                            if (!empty($tmcDeal['UF_CRM_TO_PRESENTATION_SALES'])) {
                                if ($tmcDeal['UF_CRM_TO_PRESENTATION_SALES'] === $currentPresentationDeal['ID']) {
                                    $currentTMCDeal = $tmcDeal;
                                }
                            }
                        }
                    }
                }

                if (!empty($currentBaseDeal) && !empty($currentCompany)) {

                    if (isset($currentBaseDeal['UF_CRM_PRES_COUNT'])) {
                        $result['counts']['deal'] = (int)$currentBaseDeal['UF_CRM_PRES_COUNT'];
                    }
                    if (isset($currentCompany['UF_CRM_1709807026'])) {
                        $result['counts']['company'] = (int)$currentCompany['UF_CRM_1709807026'];
                    }

                    if (isset($currentCompany['UF_CRM_PRES_COUNT'])) {
                        $result['counts']['company'] = (int)$currentCompany['UF_CRM_PRES_COUNT'];
                    }
                }
                $filter = [];
                if (!empty($portal['bitrixLists'])) {
                    $listBitrixId = null;
                    foreach ($portal['bitrixLists'] as $list) {
                        if ($list['type'] == 'presentation') {
                            $listBitrixId = $list['bitrixId'];
                        }
                    }

                    if (!empty($portal['bitrixLists']['bitrixfields'])) {
                        $listBitrixId = null;
                        foreach ($portal['bitrixLists']['bitrixfields'] as $field) {
                            if ($field['code'] == 'sales_presentation_pres_crm') {
                                $filter[$field['bitrixCamelId']] = $currentCompany['ID'];
                            } else if ($field['code'] == 'sales_presentation_pres_responsible') {
                                $filter[$field['bitrixCamelId']] = 'user_' . $responsibleId;
                            }
                        }
                    }

                    if ($listBitrixId) {
                        $presList = BitrixListService::getList(
                            $hook,
                            $listBitrixId,
                            $filter
                        );
                    }
                }




                FullEventInitController::setSessionItem(
                    $sessionKey,
                    [
                        // 'hook' => $hook,
                        // 'portal' => $portal,
                        'currentTask' => $currentTask,
                        'currentCompany' => $currentCompany,
                        'deals' => [
                            'currentBaseDeal' => $currentBaseDeal,
                            'allBaseDeals' => $allBaseDeals,
                            'currentPresentationDeal' => $currentPresentationDeal,
                            'basePresentationDeals' => $basePresentationDeals,
                            'allPresentationDeals' => $allPresentationDeals,
                            'presList' => $presList,
                            'currentXODeal' => $currentXODeal,
                            'allXODeals' => $allXODeals,
                            'currentTaskDeals' => $btxDeals,
                            'allTMCDeals' => $allTMCDeals,
                            'currentTMCDeal' => $currentTMCDeal

                        ],



                    ]
                );
                $fromSession = FullEventInitController::getSessionItem(
                    $sessionKey
                );
                return APIOnlineController::getSuccess(
                    [
                        'deals' =>  [
                            'currentBaseDeal' => $currentBaseDeal,
                            'currentPresentationDeal' => $currentPresentationDeal,
                            'basePresentationDeals' => $basePresentationDeals,
                            'allPresentationDeals' => $allPresentationDeals,
                            'presList' => $presList,
                            'currentXODeal' => $currentXODeal,
                            'allXODeals' => $allXODeals,
                            'btxDeals' => $btxDeals,
                            'currentCompany' => $currentCompany,
                            'fromSession' => $fromSession,
                            'sessionKey' =>  $sessionKey,


                        ],


                    ]
                );
            } else {
                return APIOnlineController::getError(
                    'is not full data',
                    [
                        'rq' => $request->all()

                    ]

                );
            }


            return APIOnlineController::getSuccess(
                [
                    'result' => 'success',
                    'message' => 'job !'

                ]

            );
        } catch (\Throwable $th) {
            return APIOnlineController::getError(
                $th->getMessage(),
                [
                    'task' => [
                        'message' => 'success'
                    ],
                    'rq' => $request->all()

                ]

            );
        }
    }

    public static function getDealsFromNewTaskInit(Request $request)  // GET DEALS AND INIT REDIS INIT EVENT FROM NEW TASK  
    {

        // entities [deal, smart ...]
        //companyId
        //userId
        //currentTask
        $result = null;
        try {
            $data = $request->all();
            if (
                // !empty($data['userId'])  &&
                // !empty($data['companyId']) &&
                !empty($data['domain']) &&
                isset($data['company']) &&
                isset($data['userId']) &&
                !empty($data['from']) //task //company  //deal //lead


            ) {
                $responsibleId = $data['userId'];
                $currentBaseDeal = null;               //базовая сделка в задаче всегда должна быть одна
                $currentPresentationDeal = null;               // сделка презентации из задачи
                $currentXODeal = null;


                $allBaseDeals = [];
                $basePresentationDeals = [];                 // сделки презентаций связанные с основной через списки
                $allPresentationDeals = [];                  // все сделки презентации связанные с компанией и пользователем
                $currentCompany = null;
                $allXODeals = [];
                $from = $data['from'];
                // $companyId = $data['userId'];
                // $userId = $data['companyId'];
                $domain = $data['domain'];

                $companyId  = 'random';
                $baseDealId =  $data['baseDealId'];
                $company =  $data['company'];
                if (!empty($company)) {
                    if (!empty($company['ID'])) {
                        $companyId  = $company['ID'];
                    }
                }
                $userId =  $data['userId'];


                $getAllPresDealsData = [];

                $portal = PortalController::getPortal($domain);
                $portal = $portal['data'];
                $webhookRestKey = $portal['C_REST_WEB_HOOK_URL'];
                $hook = 'https://' . $domain  . '/' . $webhookRestKey;
                // $currentCompany = BitrixGeneralService::getEntity($hook, 'company', $companyId);
                $sessionKey = 'newtask_' . $domain  . '_' . $companyId;


                $presList = null;



                //from task - получаем из task компании и сделки разных направлений

                $currentCompany = $company;
                // $currentBaseDeal = BitrixGeneralService::getEntity(
                //     $hook,
                //     'deal',
                //     $baseDealId
                // );

                $select = [
                    'ID',
                    'TITLE',
                    'UF_CRM_PRES_COUNT',
                    // 'UF_CRM_1709807026',


                    'CATEGORY_ID',
                    'ASSIGNED_BY_ID',
                    // 'COMPANY_ID',
                    'STAGE_ID',
                    // 'XO_NAME',
                    // 'XO_DATE',
                    // 'XO_RESPONSIBLE',
                    // 'XO_CREATED',
                    // 'NEXT_PRES_PLAN_DATE',
                    // 'LAST_PRES_PLAN_DATE',
                    // 'LAST_PRES_DONE_DATE',
                    // 'LAST_PRES_PLAN_RESPONSIBLE',
                    // 'LAST_PRES_DONE_RESPONSIBLE',

                    'UF_CRM_PRES_COMMENTS',
                    // 'MANAGER_OP',
                    // 'MANAGER_TMC',
                    // 'MANAGER_OS',
                    // 'MANAGER_EDU',
                    // 'CALL_NEXT_DATE',
                    // 'CALL_NEXT_NAME',
                    // 'CALL_LAST_DATE',
                    // 'GO_PLAN',
                    'UF_CRM_OP_HISTORY',
                    'UF_CRM_OP_MHISTORY',
                    // 'OP_WORK_STATUS',
                    // 'OP_PROSPECTS_TYPE',
                    // 'OP_EFIELD_FAIL_REASON',
                    // 'OP_FAIL_COMMENTS',
                    // 'OP_NORESULT_REASON',
                    // 'OP_CLIENT_STATUS',
                    // 'OP_PROSPECTS',
                    // 'OP_CLIENT_TYPE',
                    // 'OP_CONCURENTS',
                    // 'OP_CATEGORY',
                    // 'OP_SMART_COMPANY_ID',
                    // 'OP_SMART_ID',
                    // 'OP_SMART_LID',
                    // 'OP_SMART_LIDS',
                    // 'OFFER_SUM',
                    'UF_CRM_TO_BASE_SALES',
                    'UF_CRM_TO_XO_SALES',
                    'UF_CRM_TO_PRESENTATION_SALES',
                    'UF_CRM_TO_BASE_TMC',
                    'UF_CRM_TO_PRESENTATION_TMC',
                    'UF_CRM_TO_BASE_SERVICE',
                    'UF_CRM_OP_CURRENT_STATUS',

                ];
                $btxDealPortalCategories = null;
                $currentCategoryData  = null;
                $allExecludeStages =  [];
                $allIncludeCategories =  [];
                if (!empty($portal['bitrixDeal'])) {
                    if (!empty($portal['bitrixDeal']['categories'])) {
                        $btxDealPortalCategories = $portal['bitrixDeal']['categories'];
                    }
                }

                if (!empty($btxDealPortalCategories)) {


                    foreach ($btxDealPortalCategories as $btxDealPortalCategory) {
                        if (!empty($btxDealPortalCategory['code'])) {
                            if ($btxDealPortalCategory['code'] == "sales_base") {
                                $currentBaseCategoryBtxId = $btxDealPortalCategory['bitrixId'];
                            } else  if ($btxDealPortalCategory['code'] == "sales_presentation") {
                                $currentPresentCategoryBtxId = $btxDealPortalCategory['bitrixId'];
                            }
                        }
                    }
                }



                foreach ($btxDealPortalCategories as $btxDealPortalCategory) {
                    if (!empty($btxDealPortalCategory['code'])) {
                        if ($btxDealPortalCategory['code'] == "sales_base") {
                            $currentBaseCategoryBtxId = $btxDealPortalCategory['bitrixId'];


                            $getAllBaseDealsData =  [
                                'filter' => [
                                    'COMPANY_ID' => $currentCompany['ID'],
                                    'CATEGORY_ID' => $currentBaseCategoryBtxId,
                                    'RESPONSIBLE_ID' => $responsibleId,
                                    '!=STAGE_ID' => [
                                        'C' . $currentBaseCategoryBtxId . ':WON',
                                        'C' . $currentBaseCategoryBtxId . ':LOSE',
                                        'C' . $currentBaseCategoryBtxId . ':APOLOGY'
                                    ]
                                ],
                                'select' => $select

                            ];

                            // sleep(1);
                            $currentBaseDeals =   BitrixDealService::getDealList(
                                $hook,
                                $getAllBaseDealsData,
                            );
                        }


                        if ($btxDealPortalCategory['code'] == "sales_presentation") {
                            $currentPresentCategoryBtxId = $btxDealPortalCategory['bitrixId'];


                            $getAllPresDealsData =  [
                                'filter' => [
                                    'COMPANY_ID' => $currentCompany['ID'],
                                    'CATEGORY_ID' => $currentPresentCategoryBtxId,
                                    'RESPONSIBLE_ID' => $responsibleId,
                                    '!=STAGE_ID' => ['C' . $currentPresentCategoryBtxId . ':LOSE', 'C' . $currentPresentCategoryBtxId . ':APOLOGY']
                                ],
                                'select' => $select

                            ];

                            sleep(1);
                            $allPresentationDeals =   BitrixDealService::getDealList(
                                $hook,
                                $getAllPresDealsData,
                            );

                            // if (!empty($currentBaseDeal)) {
                            //     if (!empty($currentBaseDeal['ID'])) {
                            //         $getBasePresDealsData =  [
                            //             'filter' => [
                            //                 'COMPANY_ID' => $currentCompany['ID'],
                            //                 'CATEGORY_ID' => $currentPresentCategoryBtxId,
                            //                 'RESPONSIBLE_ID' => $responsibleId,
                            //                 '!=STAGE_ID' => ['C' . $currentPresentCategoryBtxId . ':LOSE', 'C' . $currentPresentCategoryBtxId . ':APOLOGY'],
                            //                 'UF_CRM_TO_BASE_SALES' => $currentBaseDeal['ID']
                            //             ],
                            //             'select' => [
                            //                 'ID',
                            //                 'TITLE',
                            //                 'UF_CRM_PRES_COUNT',
                            //                 'STAGE_ID',

                            //             ]
                            //         ];


                            //         $basePresentationDeals =   BitrixDealService::getDealList(
                            //             $hook,
                            //             $getBasePresDealsData,
                            //         );
                            //     }
                            // }
                        }
                    }
                }



                // if (!empty($currentBaseDeal) && !empty($currentCompany)) {

                //     if (isset($currentBaseDeal['UF_CRM_PRES_COUNT'])) {
                //         $result['counts']['deal'] = (int)$currentBaseDeal['UF_CRM_PRES_COUNT'];
                //     }
                //     if (isset($currentCompany['UF_CRM_1709807026'])) {
                //         $result['counts']['company'] = (int)$currentCompany['UF_CRM_1709807026'];
                //     }

                //     if (isset($currentCompany['UF_CRM_PRES_COUNT'])) {
                //         $result['counts']['company'] = (int)$currentCompany['UF_CRM_PRES_COUNT'];
                //     }
                // }
                // $filter = [];
                // if (!empty($portal['bitrixLists'])) {
                //     $listBitrixId = null;
                //     foreach ($portal['bitrixLists'] as $list) {
                //         if ($list['type'] == 'presentation') {
                //             $listBitrixId = $list['bitrixId'];
                //         }
                //     }

                //     if (!empty($portal['bitrixLists']['bitrixfields'])) {
                //         $listBitrixId = null;
                //         foreach ($portal['bitrixLists']['bitrixfields'] as $field) {
                //             if ($field['code'] == 'sales_presentation_pres_crm') {
                //                 $filter[$field['bitrixCamelId']] = $currentCompany['ID'];
                //             } else if ($field['code'] == 'sales_presentation_pres_responsible') {
                //                 $filter[$field['bitrixCamelId']] = 'user_' . $responsibleId;
                //             }
                //         }
                //     }

                //     if ($listBitrixId) {
                //         $presList = BitrixListService::getList(
                //             $hook,
                //             $listBitrixId,
                //             $filter
                //         );
                //     }
                // }

                $sessionData = [
                    // 'hook' => $hook,
                    // 'portal' => $portal,

                    'currentCompany' => $currentCompany,
                    'deals' => [
                        'currentBaseDeals' => $currentBaseDeals,
                        // 'basePresentationDeals' => $basePresentationDeals,
                        'allPresentationDeals' => $allPresentationDeals,

                    ],



                ];


                FullEventInitController::setSessionItem(
                    $sessionKey,
                    $sessionData
                );
                // $fromSession = FullEventInitController::getSessionItem(
                //     $sessionKey
                // );
                return APIOnlineController::getSuccess(
                    [
                        'deals' =>  $sessionData,


                    ]
                );
            } else {
                return APIOnlineController::getError(
                    'is not full data',
                    [
                        'rq' => $request->all()

                    ]

                );
            }


            return APIOnlineController::getSuccess(
                [
                    'result' => 'success',
                    'message' => 'job !'

                ]

            );
        } catch (\Throwable $th) {
            return APIOnlineController::getError(
                $th->getMessage(),
                [
                    'task' => [
                        'message' => 'success'
                    ],
                    'rq' => $request->all()

                ]

            );
        }
    }

    public static function getDealsFromNewTaskInner(
        $domain,
        $hook,
        $companyId,
        $userId,
        $from
    )  // GET DEALS AND INIT REDIS INIT EVENT FROM NEW TASK  
    {

        // entities [deal, smart ...]
        //companyId
        //userId
        //currentTask
        $sessionData = null;
        try {

            if (
                // !empty($data['userId'])  &&
                // !empty($data['companyId']) &&
                !empty($domain) &&
                !empty($companyId) &&
                !empty($userId)
                //  &&
                // !empty($from) //task //company  //deal //lead


            ) {
                $responsibleId = $userId;
                $allPresentationDeals = [];                  // все сделки презентации связанные с компанией и пользователем
                $currentCompany = null;

                $portal = PortalController::getPortal($domain);
                $portal = $portal['data'];

                $getAllPresDealsData = [];


                $sessionKey = 'newtask_' . $domain . '_' . $userId . '_' . $companyId;


                $presList = null;


                $select = [
                    'ID',
                    'TITLE',
                    'UF_CRM_PRES_COUNT',
                    // 'UF_CRM_1709807026',


                    'CATEGORY_ID',
                    'ASSIGNED_BY_ID',
                    // 'COMPANY_ID',
                    'STAGE_ID',
                    // 'XO_NAME',
                    // 'XO_DATE',
                    // 'XO_RESPONSIBLE',
                    // 'XO_CREATED',
                    // 'NEXT_PRES_PLAN_DATE',
                    // 'LAST_PRES_PLAN_DATE',
                    // 'LAST_PRES_DONE_DATE',
                    // 'LAST_PRES_PLAN_RESPONSIBLE',
                    // 'LAST_PRES_DONE_RESPONSIBLE',

                    'UF_CRM_PRES_COMMENTS',
                    // 'MANAGER_OP',
                    // 'MANAGER_TMC',
                    // 'MANAGER_OS',
                    // 'MANAGER_EDU',
                    // 'CALL_NEXT_DATE',
                    // 'CALL_NEXT_NAME',
                    // 'CALL_LAST_DATE',
                    // 'GO_PLAN',
                    'UF_CRM_OP_HISTORY',
                    'UF_CRM_OP_MHISTORY',
                    // 'OP_WORK_STATUS',
                    // 'OP_PROSPECTS_TYPE',
                    // 'OP_EFIELD_FAIL_REASON',
                    // 'OP_FAIL_COMMENTS',
                    // 'OP_NORESULT_REASON',
                    // 'OP_CLIENT_STATUS',
                    // 'OP_PROSPECTS',
                    // 'OP_CLIENT_TYPE',
                    // 'OP_CONCURENTS',
                    // 'OP_CATEGORY',
                    // 'OP_SMART_COMPANY_ID',
                    // 'OP_SMART_ID',
                    // 'OP_SMART_LID',
                    // 'OP_SMART_LIDS',
                    // 'OFFER_SUM',
                    'UF_CRM_TO_BASE_SALES',
                    'UF_CRM_TO_XO_SALES',
                    'UF_CRM_TO_PRESENTATION_SALES',
                    'UF_CRM_TO_BASE_TMC',
                    'UF_CRM_TO_PRESENTATION_TMC',
                    'UF_CRM_TO_BASE_SERVICE',
                    'UF_CRM_OP_CURRENT_STATUS',

                ];

                //from task - получаем из task компании и сделки разных направлений

                $currentCompany = BitrixGeneralService::getEntity($hook, 'company', $companyId);
                // $currentBaseDeal = BitrixGeneralService::getEntity(
                //     $hook,
                //     'deal',
                //     $baseDealId
                // );


                $btxDealPortalCategories = null;
                $currentCategoryData  = null;
                $allExecludeStages =  [];
                $allIncludeCategories =  [];
                if (!empty($portal['bitrixDeal'])) {
                    if (!empty($portal['bitrixDeal']['categories'])) {
                        $btxDealPortalCategories = $portal['bitrixDeal']['categories'];
                    }
                }

                if (!empty($btxDealPortalCategories)) {


                    foreach ($btxDealPortalCategories as $btxDealPortalCategory) {
                        if (!empty($btxDealPortalCategory['code'])) {
                            if ($btxDealPortalCategory['code'] == "sales_base") {
                                $currentBaseCategoryBtxId = $btxDealPortalCategory['bitrixId'];
                            } else  if ($btxDealPortalCategory['code'] == "sales_presentation") {
                                $currentPresentCategoryBtxId = $btxDealPortalCategory['bitrixId'];
                            }
                        }
                    }
                }



                foreach ($btxDealPortalCategories as $btxDealPortalCategory) {
                    if (!empty($btxDealPortalCategory['code'])) {
                        if ($btxDealPortalCategory['code'] == "sales_base") {
                            $currentBaseCategoryBtxId = $btxDealPortalCategory['bitrixId'];


                            $getAllBaseDealsData =  [
                                'filter' => [
                                    'COMPANY_ID' => $currentCompany['ID'],
                                    'CATEGORY_ID' => $currentBaseCategoryBtxId,
                                    'RESPONSIBLE_ID' => $responsibleId,
                                    '!=STAGE_ID' => [
                                        'C' . $currentBaseCategoryBtxId . ':WON',
                                        'C' . $currentBaseCategoryBtxId . ':LOSE',
                                        'C' . $currentBaseCategoryBtxId . ':APOLOGY'
                                    ]
                                ],
                                'select' => $select
                            ];

                            // sleep(1);
                            $currentBaseDeals =   BitrixDealService::getDealList(
                                $hook,
                                $getAllBaseDealsData,
                            );
                        }


                        if ($btxDealPortalCategory['code'] == "sales_presentation") {
                            $currentPresentCategoryBtxId = $btxDealPortalCategory['bitrixId'];


                            $getAllPresDealsData =  [
                                'filter' => [
                                    'COMPANY_ID' => $currentCompany['ID'],
                                    'CATEGORY_ID' => $currentPresentCategoryBtxId,
                                    'RESPONSIBLE_ID' => $responsibleId,
                                    '!=STAGE_ID' => ['C' . $currentPresentCategoryBtxId . ':LOSE', 'C' . $currentPresentCategoryBtxId . ':APOLOGY']
                                ],
                                'select' => $select

                            ];

                            sleep(1);
                            $allPresentationDeals =   BitrixDealService::getDealList(
                                $hook,
                                $getAllPresDealsData,
                            );
                        }
                    }
                }



                $sessionData = [
                    // 'hook' => $hook,
                    // 'portal' => $portal,

                    'currentCompany' => $currentCompany,
                    'deals' => [
                        'currentBaseDeals' => $currentBaseDeals,
                        // 'basePresentationDeals' => $basePresentationDeals,
                        'allPresentationDeals' => $allPresentationDeals,

                    ],



                ];


                FullEventInitController::setSessionItem(
                    $sessionKey,
                    $sessionData
                );
                // $fromSession = FullEventInitController::getSessionItem(
                //     $sessionKey
                // );
                return $sessionData;
            }

            // Log::channel('telegram')->error('API HOOK: getDealsFromNewTaskInner', [
            //     'message' => 'not full data',
            //     'come' => [
            //         'domain' =>  $domain,
            //         'hook' =>  $hook,
            //         'companyId' =>  $companyId,
            //         'userId' =>  $userId,
            //     ]

            // ]);

            return $sessionData;
        } catch (\Throwable $th) {
            $errorData = [
                'message'   => $th->getMessage(),
                'file'      => $th->getFile(),
                'line'      => $th->getLine(),
                'trace'     => $th->getTraceAsString(),
                'come' => [
                    $domain,
                    $hook,
                    $companyId,
                    $userId,
                ]
            ];
            Log::error('API HOOK: getDealsFromNewTaskInner', $errorData);
            Log::channel('telegram')->error('API HOOK: getDealsFromNewTaskInner', $errorData);

            return $sessionData;
        }
    }

    public static function getDealsFromNewTaskInnerTMC(
        $domain,
        $hook,
        $companyId,
        $userId,
        $from
    )  // GET DEALS AND INIT REDIS INIT EVENT FROM NEW TASK  
    {

        // entities [deal, smart ...]
        //companyId
        //userId
        //currentTask
        $sessionData = null;
        try {

            if (
                // !empty($data['userId'])  &&
                // !empty($data['companyId']) &&
                !empty($domain) &&
                !empty($companyId) &&
                !empty($userId)
                //  &&
                // !empty($from) //task //company  //deal //lead


            ) {
                $responsibleId = $userId;
                $allPresentationDeals = [];                  // все сделки презентации связанные с компанией и пользователем
                $currentCompany = null;
                $currentBaseDeals = [];
                $portal = PortalController::getPortal($domain);
                $portal = $portal['data'];

                $getAllPresDealsData = [];


                $sessionKey = 'newtask_' . $domain . '_' . $userId . '_' . $companyId;


                $presList = null;



                //from task - получаем из task компании и сделки разных направлений

                $currentCompany = BitrixGeneralService::getEntity($hook, 'company', $companyId);
                // $currentBaseDeal = BitrixGeneralService::getEntity(
                //     $hook,
                //     'deal',
                //     $baseDealId
                // );


                $btxDealPortalCategories = null;
                $currentCategoryData  = null;
                $allExecludeStages =  [];
                $allIncludeCategories =  [];
                if (!empty($portal['bitrixDeal'])) {
                    if (!empty($portal['bitrixDeal']['categories'])) {
                        $btxDealPortalCategories = $portal['bitrixDeal']['categories'];
                    }
                }

                if (!empty($btxDealPortalCategories)) {


                    foreach ($btxDealPortalCategories as $btxDealPortalCategory) {
                        if (!empty($btxDealPortalCategory['code'])) {
                            if ($btxDealPortalCategory['code'] == "tmc_base") {
                                $currentBaseCategoryBtxId = $btxDealPortalCategory['bitrixId'];
                            }
                        }
                    }
                }



                foreach ($btxDealPortalCategories as $btxDealPortalCategory) {
                    if (!empty($btxDealPortalCategory['code'])) {
                        if ($btxDealPortalCategory['code'] == "tmc_base") {
                            $currentBaseCategoryBtxId = $btxDealPortalCategory['bitrixId'];


                            $getAllBaseDealsData =  [
                                'filter' => [
                                    'COMPANY_ID' => $currentCompany['ID'],
                                    'CATEGORY_ID' => $currentBaseCategoryBtxId,
                                    'RESPONSIBLE_ID' => $responsibleId,
                                    '!=STAGE_ID' => [
                                        'C' . $currentBaseCategoryBtxId . ':WON',
                                        'C' . $currentBaseCategoryBtxId . ':LOSE',
                                        'C' . $currentBaseCategoryBtxId . ':APOLOGY'
                                    ]
                                ],
                                // 'select' => [
                                //     'ID',
                                //     'TITLE',
                                //     'UF_CRM_PRES_COUNT',
                                //     'STAGE_ID',

                                // ]
                            ];

                            // sleep(1);
                            $currentBaseDeals =   BitrixDealService::getDealList(
                                $hook,
                                $getAllBaseDealsData,
                            );
                        }


                        // if ($btxDealPortalCategory['code'] == "sales_presentation") {
                        //     $currentPresentCategoryBtxId = $btxDealPortalCategory['bitrixId'];


                        //     $getAllPresDealsData =  [
                        //         'filter' => [
                        //             'COMPANY_ID' => $currentCompany['ID'],
                        //             'CATEGORY_ID' => $currentPresentCategoryBtxId,
                        //             'RESPONSIBLE_ID' => $responsibleId,
                        //             '!=STAGE_ID' => ['C' . $currentPresentCategoryBtxId . ':LOSE', 'C' . $currentPresentCategoryBtxId . ':APOLOGY']
                        //         ],
                        //         // 'select' => [
                        //         //     'ID',
                        //         //     'TITLE',
                        //         //     'UF_CRM_PRES_COUNT',
                        //         //     'STAGE_ID',

                        //         // ]
                        //     ];

                        //     sleep(1);
                        //     $allPresentationDeals =   BitrixDealService::getDealList(
                        //         $hook,
                        //         $getAllPresDealsData,
                        //     );
                        // }
                    }
                }



                $sessionData = [
                    // 'hook' => $hook,
                    // 'portal' => $portal,

                    'currentCompany' => $currentCompany,
                    'deals' => [
                        'currentBaseDeals' => $currentBaseDeals,
                        // 'basePresentationDeals' => $basePresentationDeals,
                        'allPresentationDeals' => $allPresentationDeals,


                    ],



                ];


                FullEventInitController::setSessionItem(
                    $sessionKey,
                    $sessionData
                );
                // $fromSession = FullEventInitController::getSessionItem(
                //     $sessionKey
                // );
                return $sessionData;
            }

            // Log::channel('telegram')->error('API HOOK: getDealsFromNewTaskInner', [
            //     'message' => 'not full data',
            //     'come' => [
            //         'domain' =>  $domain,
            //         'hook' =>  $hook,
            //         'companyId' =>  $companyId,
            //         'userId' =>  $userId,
            //     ]

            // ]);

            return $sessionData;
        } catch (\Throwable $th) {
            $errorData = [
                'message'   => $th->getMessage(),
                'file'      => $th->getFile(),
                'line'      => $th->getLine(),
                'trace'     => $th->getTraceAsString(),
                'come' => [
                    $domain,
                    $hook,
                    $companyId,
                    $userId,
                ]
            ];
            Log::error('API HOOK: getDealsFromNewTaskInner', $errorData);
            Log::channel('telegram')->error('API HOOK: getDealsFromNewTaskInner', $errorData);

            return $sessionData;
        }
    }

    public static function getDocumentDealsFromCompany(Request $request)
    { //
        $resultDeal = null;
        $data = $request->all();
        $domain = $data['domain'];
        $companyId = $data['companyId'];
        $userId = $data['userId'];

        $portal = PortalController::getPortal($domain);
        $portal = $portal['data'];
        $webhookRestKey = $portal['C_REST_WEB_HOOK_URL'];
        $hook = 'https://' . $domain  . '/' . $webhookRestKey;

        $select = [
            'ID',
            'TITLE',
            'UF_CRM_PRES_COUNT',
            'CATEGORY_ID',
            // 'COMPANY_ID',
            'STAGE_ID',
            // 'XO_NAME',
            // 'XO_DATE',
            // 'XO_RESPONSIBLE',
            // 'XO_CREATED',
            // 'NEXT_PRES_PLAN_DATE',
            // 'LAST_PRES_PLAN_DATE',
            // 'LAST_PRES_DONE_DATE',
            // 'LAST_PRES_PLAN_RESPONSIBLE',
            // 'LAST_PRES_DONE_RESPONSIBLE',

            'UF_CRM_PRES_COMMENTS',
            // 'MANAGER_OP',
            // 'MANAGER_TMC',
            // 'MANAGER_OS',
            // 'MANAGER_EDU',
            // 'CALL_NEXT_DATE',
            // 'CALL_NEXT_NAME',
            // 'CALL_LAST_DATE',
            // 'GO_PLAN',
            'UF_CRM_OP_HISTORY',
            'UF_CRM_OP_MHISTORY',
            // 'OP_WORK_STATUS',
            // 'OP_PROSPECTS_TYPE',
            // 'OP_EFIELD_FAIL_REASON',
            // 'OP_FAIL_COMMENTS',
            // 'OP_NORESULT_REASON',
            // 'OP_CLIENT_STATUS',
            // 'OP_PROSPECTS',
            // 'OP_CLIENT_TYPE',
            // 'OP_CONCURENTS',
            // 'OP_CATEGORY',
            // 'OP_SMART_COMPANY_ID',
            // 'OP_SMART_ID',
            // 'OP_SMART_LID',
            // 'OP_SMART_LIDS',
            // 'OFFER_SUM',
            'UF_CRM_TO_BASE_SALES',
            'UF_CRM_TO_XO_SALES',
            'UF_CRM_TO_PRESENTATION_SALES',
            'TO_BASE_TMC',
            'TO_PRESENTATION_TMC',
            'TO_BASE_SERVICE',
            'UF_CRM_OP_CURRENT_STATUS',

        ];

        if (!empty($portal['bitrixDeal'])) {
            if (!empty($portal['bitrixDeal']['categories'])) {
                $btxDealPortalCategories = $portal['bitrixDeal']['categories'];
            }
        }

        if (!empty($btxDealPortalCategories)) {


            foreach ($btxDealPortalCategories as $btxDealPortalCategory) {
                if (!empty($btxDealPortalCategory['code'])) {
                    if ($btxDealPortalCategory['code'] == "sales_base") {
                        $salesBaseCategory = $btxDealPortalCategory['bitrixId'];
                    }
                }
            }
            $filter = [

                'COMPANY_ID' => $companyId,
                'CATEGORY_ID' => $salesBaseCategory,
                'RESPONSIBLE_ID' => $userId,
                '!=STAGE_ID' => ['C' . $salesBaseCategory . ':LOSE', 'C' . $salesBaseCategory . ':APOLOGY']


            ];
            $deals = BitrixGeneralService::getEntityList(
                $hook,
                'deal',
                $filter,
                $select
            );
            if (!empty($deals)) {
                if (is_array($deals)) {
                    $resultDeal = $deals[0];
                }
            }
        }
        return APIOnlineController::getSuccess([
            'deal' => $resultDeal,
        ]);
    }
    public static function getDocumentDealsInit(Request $request)
    {

        // entities [deal, smart ...]
        //companyId
        //userId
        //currentTask
        $result = null;
        try {
            $data = $request->all();
            if (
                // !empty($data['userId'])  &&
                // !empty($data['companyId']) &&
                !empty($data['domain']) &&
                !empty($data['baseDealId']) &&
                !empty($data['companyId']) &&
                !empty($data['userId'])


            ) {
                $select = [
                    'ID',
                    'TITLE',
                    'UF_CRM_PRES_COUNT',
                    'CATEGORY_ID',
                    // 'COMPANY_ID',
                    'STAGE_ID',
                    // 'XO_NAME',
                    // 'XO_DATE',
                    // 'XO_RESPONSIBLE',
                    // 'XO_CREATED',
                    // 'NEXT_PRES_PLAN_DATE',
                    // 'LAST_PRES_PLAN_DATE',
                    // 'LAST_PRES_DONE_DATE',
                    // 'LAST_PRES_PLAN_RESPONSIBLE',
                    // 'LAST_PRES_DONE_RESPONSIBLE',

                    'UF_CRM_PRES_COMMENTS',
                    // 'MANAGER_OP',
                    // 'MANAGER_TMC',
                    // 'MANAGER_OS',
                    // 'MANAGER_EDU',
                    // 'CALL_NEXT_DATE',
                    // 'CALL_NEXT_NAME',
                    // 'CALL_LAST_DATE',
                    // 'GO_PLAN',
                    'UF_CRM_OP_HISTORY',
                    'UF_CRM_OP_MHISTORY',
                    // 'OP_WORK_STATUS',
                    // 'OP_PROSPECTS_TYPE',
                    // 'OP_EFIELD_FAIL_REASON',
                    // 'OP_FAIL_COMMENTS',
                    // 'OP_NORESULT_REASON',
                    // 'OP_CLIENT_STATUS',
                    // 'OP_PROSPECTS',
                    // 'OP_CLIENT_TYPE',
                    // 'OP_CONCURENTS',
                    // 'OP_CATEGORY',
                    // 'OP_SMART_COMPANY_ID',
                    // 'OP_SMART_ID',
                    // 'OP_SMART_LID',
                    // 'OP_SMART_LIDS',
                    // 'OFFER_SUM',
                    'UF_CRM_TO_BASE_SALES',
                    'UF_CRM_TO_XO_SALES',
                    'UF_CRM_TO_PRESENTATION_SALES',
                    'TO_BASE_TMC',
                    'TO_PRESENTATION_TMC',
                    'TO_BASE_SERVICE',
                    'UF_CRM_OP_CURRENT_STATUS',

                ];
                $responsibleId = $data['userId'];
                $currentBaseDeal = null;               //базовая сделка в задаче всегда должна быть одна
                $currentPresentationDeal = null;               // сделка презентации из задачи
                $currentXODeal = null;


                $allBaseDeals = [];
                $basePresentationDeals = [];                 // сделки презентаций связанные с основной через списки
                $allPresentationDeals = [];                  // все сделки презентации связанные с компанией и пользователем
                $currentCompany = null;
                $allXODeals = [];

                // $companyId = $data['userId'];
                // $userId = $data['companyId'];
                $domain = $data['domain'];
                $companyId  = $data['domain'];

                $baseDealId =  $data['baseDealId'];
                $companyId =  $data['companyId'];
                $userId =  $data['userId'];


                $getAllPresDealsData = [];

                $portal = PortalController::getPortal($domain);
                $portal = $portal['data'];
                $webhookRestKey = $portal['C_REST_WEB_HOOK_URL'];
                $hook = 'https://' . $domain  . '/' . $webhookRestKey;
                // $currentCompany = BitrixGeneralService::getEntity($hook, 'company', $companyId);
                $sessionKey = 'document_' . $domain . '_' . $baseDealId;


                $presList = null;



                //from task - получаем из task компании и сделки разных направлений

                $currentCompany = BitrixGeneralService::getEntity(
                    $hook,
                    'company',
                    $companyId
                );
                $currentBaseDeal = BitrixGeneralService::getEntity(
                    $hook,
                    'deal',
                    $baseDealId
                );


                $btxDealPortalCategories = null;
                $currentCategoryData  = null;
                $allExecludeStages =  [];
                $allIncludeCategories =  [];
                if (!empty($portal['bitrixDeal'])) {
                    if (!empty($portal['bitrixDeal']['categories'])) {
                        $btxDealPortalCategories = $portal['bitrixDeal']['categories'];
                    }
                }

                if (!empty($btxDealPortalCategories)) {


                    foreach ($btxDealPortalCategories as $btxDealPortalCategory) {
                        if (!empty($btxDealPortalCategory['code'])) {
                            if ($btxDealPortalCategory['code'] == "sales_base") {
                                $currentBaseCategoryBtxId = $btxDealPortalCategory['bitrixId'];
                            } else  if ($btxDealPortalCategory['code'] == "sales_presentation") {
                                $currentPresentCategoryBtxId = $btxDealPortalCategory['bitrixId'];
                            }
                        }
                    }
                }



                foreach ($btxDealPortalCategories as $btxDealPortalCategory) {
                    if (!empty($btxDealPortalCategory['code'])) {
                        if ($btxDealPortalCategory['code'] == "sales_presentation") {
                            $currentPresentCategoryBtxId = $btxDealPortalCategory['bitrixId'];


                            $getAllPresDealsData =  [
                                'filter' => [
                                    'COMPANY_ID' => $currentCompany['ID'],
                                    'CATEGORY_ID' => $currentPresentCategoryBtxId,
                                    'RESPONSIBLE_ID' => $responsibleId,
                                    '!=STAGE_ID' => ['C' . $currentPresentCategoryBtxId . ':LOSE', 'C' . $currentPresentCategoryBtxId . ':APOLOGY']
                                ],
                                'select' => $select
                            ];

                            // sleep(1);
                            $allPresentationDeals =   BitrixDealService::getDealList(
                                $hook,
                                $getAllPresDealsData,
                            );

                            if (!empty($currentBaseDeal)) {
                                if (!empty($currentBaseDeal['ID'])) {
                                    $getBasePresDealsData =  [
                                        'filter' => [
                                            'COMPANY_ID' => $currentCompany['ID'],
                                            'CATEGORY_ID' => $currentPresentCategoryBtxId,
                                            'RESPONSIBLE_ID' => $responsibleId,
                                            '!=STAGE_ID' => ['C' . $currentPresentCategoryBtxId . ':LOSE', 'C' . $currentPresentCategoryBtxId . ':APOLOGY'],
                                            'UF_CRM_TO_BASE_SALES' => $currentBaseDeal['ID']
                                        ],
                                        'select' => $select
                                    ];


                                    $basePresentationDeals =   BitrixDealService::getDealList(
                                        $hook,
                                        $getBasePresDealsData,
                                    );
                                }
                            }
                        }
                    }
                }



                // if (!empty($currentBaseDeal) && !empty($currentCompany)) {

                //     if (isset($currentBaseDeal['UF_CRM_PRES_COUNT'])) {
                //         $result['counts']['deal'] = (int)$currentBaseDeal['UF_CRM_PRES_COUNT'];
                //     }
                //     if (isset($currentCompany['UF_CRM_1709807026'])) {
                //         $result['counts']['company'] = (int)$currentCompany['UF_CRM_1709807026'];
                //     }

                //     if (isset($currentCompany['UF_CRM_PRES_COUNT'])) {
                //         $result['counts']['company'] = (int)$currentCompany['UF_CRM_PRES_COUNT'];
                //     }
                // }
                // $filter = [];
                // if (!empty($portal['bitrixLists'])) {
                //     $listBitrixId = null;
                //     foreach ($portal['bitrixLists'] as $list) {
                //         if ($list['type'] == 'presentation') {
                //             $listBitrixId = $list['bitrixId'];
                //         }
                //     }

                //     if (!empty($portal['bitrixLists']['bitrixfields'])) {
                //         $listBitrixId = null;
                //         foreach ($portal['bitrixLists']['bitrixfields'] as $field) {
                //             if ($field['code'] == 'sales_presentation_pres_crm') {
                //                 $filter[$field['bitrixCamelId']] = $currentCompany['ID'];
                //             } else if ($field['code'] == 'sales_presentation_pres_responsible') {
                //                 $filter[$field['bitrixCamelId']] = 'user_' . $responsibleId;
                //             }
                //         }
                //     }

                //     if ($listBitrixId) {
                //         $presList = BitrixListService::getList(
                //             $hook,
                //             $listBitrixId,
                //             $filter
                //         );
                //     }
                // }

                $sessionData = [
                    // 'hook' => $hook,
                    // 'portal' => $portal,

                    'currentCompany' => $currentCompany,
                    'deals' => [
                        // '$sessionKey' => $sessionKey ,
                        'currentBaseDeal' => $currentBaseDeal,
                        'basePresentationDeals' => $basePresentationDeals,
                        'allPresentationDeals' => $allPresentationDeals,

                    ],



                ];


                FullEventInitController::setSessionItem(
                    $sessionKey,
                    $sessionData
                );
                // $fromSession = FullEventInitController::getSessionItem(
                //     $sessionKey
                // );
                return APIOnlineController::getSuccess(
                    [
                        'deals' =>  $sessionData,


                    ]
                );
            } else {
                return APIOnlineController::getError(
                    'is not full data',
                    [
                        'rq' => $request->all()

                    ]

                );
            }


            return APIOnlineController::getSuccess(
                [
                    'result' => 'success',
                    'message' => 'job !'

                ]

            );
        } catch (\Throwable $th) {
            return APIOnlineController::getError(
                $th->getMessage(),
                [
                    'task' => [
                        'message' => 'success'
                    ],
                    'rq' => $request->all()

                ]

            );
        }
    }



    public static function getFullDealsInner(
        $hook,
        $portal,
        $domain,
        $currentTask
    ) {


        $result = null;
        try {

            if (

                !empty($domain) &&
                !empty($currentTask)
            ) {

                $select = [
                    'ID',
                    'TITLE',
                    'UF_CRM_PRES_COUNT',
                    // 'UF_CRM_1709807026',


                    'CATEGORY_ID',
                    'ASSIGNED_BY_ID',
                    // 'COMPANY_ID',
                    'STAGE_ID',
                    // 'XO_NAME',
                    // 'XO_DATE',
                    // 'XO_RESPONSIBLE',
                    // 'XO_CREATED',
                    // 'NEXT_PRES_PLAN_DATE',
                    // 'LAST_PRES_PLAN_DATE',
                    // 'LAST_PRES_DONE_DATE',
                    // 'LAST_PRES_PLAN_RESPONSIBLE',
                    // 'LAST_PRES_DONE_RESPONSIBLE',

                    'UF_CRM_PRES_COMMENTS',
                    // 'MANAGER_OP',
                    // 'MANAGER_TMC',
                    // 'MANAGER_OS',
                    // 'MANAGER_EDU',
                    // 'CALL_NEXT_DATE',
                    // 'CALL_NEXT_NAME',
                    // 'CALL_LAST_DATE',
                    // 'GO_PLAN',
                    'UF_CRM_OP_HISTORY',
                    'UF_CRM_OP_MHISTORY',
                    // 'OP_WORK_STATUS',
                    // 'OP_PROSPECTS_TYPE',
                    // 'OP_EFIELD_FAIL_REASON',
                    // 'OP_FAIL_COMMENTS',
                    // 'OP_NORESULT_REASON',
                    // 'OP_CLIENT_STATUS',
                    // 'OP_PROSPECTS',
                    // 'OP_CLIENT_TYPE',
                    // 'OP_CONCURENTS',
                    // 'OP_CATEGORY',
                    // 'OP_SMART_COMPANY_ID',
                    // 'OP_SMART_ID',
                    // 'OP_SMART_LID',
                    // 'OP_SMART_LIDS',
                    // 'OFFER_SUM',
                    'UF_CRM_TO_BASE_SALES',
                    'UF_CRM_TO_XO_SALES',
                    'UF_CRM_TO_PRESENTATION_SALES',
                    'UF_CRM_TO_BASE_TMC',
                    'UF_CRM_TO_PRESENTATION_TMC',
                    'UF_CRM_TO_BASE_SERVICE',
                    'UF_CRM_OP_CURRENT_STATUS',

                ];
                $responsibleId = 1;
                $currentBaseDeal = null;               //базовая сделка в задаче всегда должна быть одна
                $currentPresentationDeal = null;               // сделка презентации из задачи
                $currentXODeal = null;


                $allBaseDeals = [];
                $basePresentationDeals = [];                 // сделки презентаций связанные с основной через списки
                $allPresentationDeals = [];                  // все сделки презентации связанные с компанией и пользователем
                $currentCompany = null;
                $allXODeals = [];
                $allTMCDeals = [];
                $currentTMCDeal = null;
                // $companyId = $data['userId'];
                // $userId = $data['companyId'];
                // $domain = $data['domain'];
                // $companyId  = $data['domain'];
                $btxDeals = []; //from task
                // $currentTask =  $data['currentTask'];


                $getAllPresDealsData = [];

                $portal = PortalController::getPortal($domain);
                $portal = $portal['data'];
                $webhookRestKey = $portal['C_REST_WEB_HOOK_URL'];
                $hook = 'https://' . $domain  . '/' . $webhookRestKey;
                // $currentCompany = BitrixGeneralService::getEntity($hook, 'company', $companyId);
                $sessionKey = $domain . '_' . $currentTask['id'];


                $presList = null;



                //from task - получаем из task компании и сделки разных направлений

                $currentBtxEntities =  BitrixEntityFlowService::getEntities(
                    $hook,
                    $currentTask,
                );
                if (!empty($currentBtxEntities)) {
                    if (!empty($currentBtxEntities['companies'])) {
                        $currentCompany = $currentBtxEntities['companies'][0];
                    }
                    if (!empty($currentBtxEntities['deals'])) {
                        $btxDeals = $currentBtxEntities['deals'];
                    }
                }



                $btxDealPortalCategories = null;
                $currentCategoryData  = null;
                $allExecludeStages =  [];
                $allIncludeCategories =  [];
                if (!empty($portal['bitrixDeal'])) {
                    if (!empty($portal['bitrixDeal']['categories'])) {
                        $btxDealPortalCategories = $portal['bitrixDeal']['categories'];
                    }
                }

                if (!empty($btxDealPortalCategories)) {
                    // if (!empty($btxDealPortalCategory['code'])) {
                    //     foreach ($btxDealPortalCategories as $btxDealPortalCategory) {
                    //         if ($btxDealPortalCategory['code'] === "sales_base" || $btxDealPortalCategory['code'] === "sales_presentation") {
                    //             $currenBaseCategoryBtxId = $btxDealPortalCategory['bitrixId'];

                    //             array_push($allExecludeStages, 'C' . $currenBaseCategoryBtxId . ':LOSE');
                    //             array_push($allExecludeStages, 'C' . $currenBaseCategoryBtxId . ':APOLOGY');
                    //             if ($btxDealPortalCategory['code'] == "sales_base") {
                    //                 array_push($allExecludeStages, 'C' . $currenBaseCategoryBtxId . ':WON');
                    //             }
                    //             array_push($allIncludeCategories, $currenBaseCategoryBtxId);
                    //         }
                    //     }
                    // }

                    // $getAllDealsData =  [
                    //     'filter' => [
                    //         'COMPANY_ID' => $currentCompany['ID'],
                    //         'CATEGORY_ID' => $allIncludeCategories,
                    //         'RESPONSIBLE_ID' => $responsibleId,
                    //         '!=STAGE_ID' => $allExecludeStages
                    //     ],
                    //     'select' => [
                    //         'ID',
                    //         'TITLE',
                    //         'UF_CRM_PRES_COUNT',
                    //         'STAGE_ID',
                    //         'UF_CRM_TO_BASE_SALES',
                    //         'CATEGORY_ID'

                    //     ]
                    // ];


                    // $allDeals =   BitrixDealService::getDealList(
                    //     $hook,
                    //     $getAllDealsData,
                    // );

                    //task current deals

                    foreach ($btxDealPortalCategories as $btxDealPortalCategory) {
                        if (!empty($btxDealPortalCategory['code'])) {
                            if ($btxDealPortalCategory['code'] == "sales_base") {



                                foreach ($btxDeals as $btxDeal) {
                                    $currenBaseCategoryBtxId = $btxDealPortalCategory['bitrixId'];

                                    if (!empty($btxDeal['CATEGORY_ID'])) {
                                        if ($btxDeal['CATEGORY_ID'] == $btxDealPortalCategory['bitrixId']) {
                                            $currentBaseDeal = $btxDeal;   //базовая сделка в задаче всегда должна быть одна
                                        }
                                    }
                                }
                            } else  if ($btxDealPortalCategory['code'] == "sales_presentation") {
                                $currentPresentCategoryBtxId = $btxDealPortalCategory['bitrixId'];

                                foreach ($btxDeals as $btxDeal) {
                                    if (!empty($btxDeal['CATEGORY_ID'])) {
                                        if ($btxDeal['CATEGORY_ID'] == $currentPresentCategoryBtxId) {
                                            $currentPresentationDeal = $btxDeal;      // сделка презентации из задачи

                                        }
                                    }
                                }
                            } else  if ($btxDealPortalCategory['code'] == "sales_xo") {
                                $currentXOCategoryBtxId = $btxDealPortalCategory['bitrixId'];

                                foreach ($btxDeals as $btxDeal) {
                                    if (!empty($btxDeal['CATEGORY_ID'])) {
                                        if ($btxDeal['CATEGORY_ID'] == $currentXOCategoryBtxId) {
                                            $currentXODeal = $btxDeal;      // сделка презентации из задачи

                                        }
                                    }
                                }
                            } else  if ($btxDealPortalCategory['code'] == "tmc_base") {
                                $currentTMCCategoryBtxId = $btxDealPortalCategory['bitrixId'];

                                foreach ($btxDeals as $btxDeal) {
                                    if (!empty($btxDeal['CATEGORY_ID'])) {
                                        if ($btxDeal['CATEGORY_ID'] == $currentTMCCategoryBtxId) {
                                            $currentTMCDeal = $btxDeal;      // сделка презентации из задачи

                                        }
                                    }
                                }
                            }
                        }
                    }

                    //deal from company user by category
                    // foreach ($allDeals as $btxDealFromAll) {
                    //     foreach ($btxDealPortalCategories as $btxDealPortalCategory) {
                    //         if (!empty($btxDealPortalCategory['code'])) {
                    //             if ($btxDealPortalCategory['code'] == "sales_base") {

                    //                 if ($btxDealFromAll['CATEGORY_ID'] == $btxDealPortalCategory['bitrixId']) {
                    //                     array_push($allBaseDeals, $btxDealFromAll);
                    //                 }
                    //             } else  if ($btxDealPortalCategory['code'] == "sales_presentation") {
                    //                 $currentPresentCategoryBtxId = $btxDealPortalCategory['bitrixId'];
                    //                 if ($btxDealFromAll['CATEGORY_ID'] == $btxDealPortalCategory['bitrixId']) {
                    //                     array_push($allPresentationDeals, $btxDealFromAll);


                    //                     if (!empty($currentBaseDeal)) {
                    //                         if (!empty($currentBaseDeal['ID'])) {

                    //                             if (!empty($btxDealFromAll['UF_CRM_TO_BASE_SALES'])) {
                    //                                 if ($btxDealFromAll['UF_CRM_TO_BASE_SALES'] == $currentBaseDeal['ID']) {
                    //                                     array_push($basePresentationDeals, $btxDealFromAll);
                    //                                 }
                    //                             }
                    //                         }
                    //                     }
                    //                 }
                    //             }
                    //         } else  if ($btxDealPortalCategory['code'] == "sales_xo") {
                    //             $currentXOCategoryBtxId = $btxDealPortalCategory['bitrixId'];
                    //             if ($btxDealFromAll['CATEGORY_ID'] == $currentXOCategoryBtxId) {
                    //                 array_push($allXODeals, $btxDealFromAll);
                    //             }
                    //         }
                    //     }
                    // }






                }



                foreach ($btxDealPortalCategories as $btxDealPortalCategory) {
                    if (!empty($btxDealPortalCategory['code'])) {
                        if ($btxDealPortalCategory['code'] == "sales_base") {



                            foreach ($btxDeals as $btxDeal) {
                                $currenBaseCategoryBtxId = $btxDealPortalCategory['bitrixId'];

                                if (!empty($btxDeal['CATEGORY_ID'])) {
                                    if ($btxDeal['CATEGORY_ID'] == $btxDealPortalCategory['bitrixId']) {
                                        $currentBaseDeal = $btxDeal;   //базовая сделка в задаче всегда должна быть одна
                                    }
                                }


                                $getAllBaseDealsData =  [
                                    'filter' => [
                                        'COMPANY_ID' => $currentCompany['ID'],
                                        'CATEGORY_ID' => $currenBaseCategoryBtxId,
                                        'RESPONSIBLE_ID' => $responsibleId,
                                        '!=STAGE_ID' => ['C' . $currenBaseCategoryBtxId . ':WON', 'C' . $currenBaseCategoryBtxId . ':LOSE', 'C' . $currenBaseCategoryBtxId . ':APOLOGY']
                                    ],
                                    'select' => $select

                                ];


                                $allBaseDeals =   BitrixDealService::getDealList(
                                    $hook,
                                    $getAllBaseDealsData,
                                );
                            }
                        } else  if ($btxDealPortalCategory['code'] == "sales_presentation") {
                            $currentPresentCategoryBtxId = $btxDealPortalCategory['bitrixId'];

                            foreach ($btxDeals as $btxDeal) {
                                if (!empty($btxDeal['CATEGORY_ID'])) {
                                    if ($btxDeal['CATEGORY_ID'] == $currentPresentCategoryBtxId) {
                                        $currentPresentationDeal = $btxDeal;      // сделка презентации из задачи

                                    }
                                }


                                $getAllPresDealsData =  [
                                    'filter' => [
                                        'COMPANY_ID' => $currentCompany['ID'],
                                        'CATEGORY_ID' => $currentPresentCategoryBtxId,
                                        'RESPONSIBLE_ID' => $responsibleId,
                                        '!=STAGE_ID' => ['C' . $currentPresentCategoryBtxId . ':LOSE', 'C' . $currentPresentCategoryBtxId . ':APOLOGY']
                                    ],
                                    'select' => $select
                                ];

                                // sleep(1);
                                $allPresentationDeals =   BitrixDealService::getDealList(
                                    $hook,
                                    $getAllPresDealsData,
                                );

                                if (!empty($currentBaseDeal)) {
                                    if (!empty($currentBaseDeal['ID'])) {
                                        $getAllPresDealsData =  [
                                            'filter' => [
                                                'COMPANY_ID' => $currentCompany['ID'],
                                                'CATEGORY_ID' => $currentPresentCategoryBtxId,
                                                'RESPONSIBLE_ID' => $responsibleId,
                                                '!=STAGE_ID' => ['C' . $currentPresentCategoryBtxId . ':LOSE', 'C' . $currentPresentCategoryBtxId . ':APOLOGY'],
                                                'UF_CRM_TO_BASE_SALES' => $currentBaseDeal['ID']
                                            ],
                                            'select' => $select
                                        ];


                                        $basePresentationDeals =   BitrixDealService::getDealList(
                                            $hook,
                                            $getAllPresDealsData,
                                        );
                                    }
                                }
                            }
                        } else  if ($btxDealPortalCategory['code'] == "sales_xo") {
                            $currentXOCategoryBtxId = $btxDealPortalCategory['bitrixId'];

                            foreach ($btxDeals as $btxDeal) {
                                if (!empty($btxDeal['CATEGORY_ID'])) {
                                    if ($btxDeal['CATEGORY_ID'] == $currentXOCategoryBtxId) {
                                        $currentXODeal = $btxDeal;      // сделка презентации из задачи

                                    }
                                }


                                $getAllXODealsData =  [
                                    'filter' => [
                                        'COMPANY_ID' => $currentCompany['ID'],
                                        'CATEGORY_ID' => $currentXOCategoryBtxId,
                                        'RESPONSIBLE_ID' => $responsibleId,
                                        '!=STAGE_ID' => ['C' . $currentXOCategoryBtxId . ':LOSE', 'C' . $currentXOCategoryBtxId . ':APOLOGY']
                                    ],
                                    'select' => $select
                                ];

                                $rand = mt_rand(300000, 1000000); // случайное число от 300000 до 900000 микросекунд (0.3 - 0.9 секунды)
                                usleep($rand);
                                $allXODeals =   BitrixDealService::getDealList(
                                    $hook,
                                    $getAllXODealsData,
                                );
                            }
                        } else  if ($btxDealPortalCategory['code'] == "tmc_base") {



                            foreach ($btxDeals as $btxDeal) {
                                $currenBaseCategoryBtxId = $btxDealPortalCategory['bitrixId'];

                                if (!empty($btxDeal['CATEGORY_ID'])) {
                                    if ($btxDeal['CATEGORY_ID'] == $btxDealPortalCategory['bitrixId']) {
                                        $currentTMCDeal = $btxDeal;   //базовая сделка в задаче всегда должна быть одна
                                    }
                                }


                                $getAllTMCDealsData =  [
                                    'filter' => [
                                        'COMPANY_ID' => $currentCompany['ID'],
                                        'CATEGORY_ID' => $currenBaseCategoryBtxId,
                                        'RESPONSIBLE_ID' => $responsibleId,
                                        '!=STAGE_ID' => ['C' . $currenBaseCategoryBtxId . ':WON', 'C' . $currenBaseCategoryBtxId . ':LOSE', 'C' . $currenBaseCategoryBtxId . ':APOLOGY']
                                    ],
                                    'select' => $select

                                ];


                                $allTMCDeals =   BitrixDealService::getDealList(
                                    $hook,
                                    $getAllTMCDealsData,
                                );
                            }
                        }
                    }
                }

                if (empty($currentTMCDeal) && !empty($currentPresentationDeal) && !empty($allTMCDeals)) {

                    if (!empty($currentPresentationDeal['ID'])) {
                        foreach ($allTMCDeals as $key => $tmcDeal) {
                            if (!empty($tmcDeal['UF_CRM_TO_PRESENTATION_SALES'])) {
                                if ($tmcDeal['UF_CRM_TO_PRESENTATION_SALES'] === $currentPresentationDeal['ID']) {
                                    $currentTMCDeal = $tmcDeal;
                                }
                            }
                        }
                    }
                }

                if (!empty($currentBaseDeal) && !empty($currentCompany)) {

                    if (isset($currentBaseDeal['UF_CRM_PRES_COUNT'])) {
                        $result['counts']['deal'] = (int)$currentBaseDeal['UF_CRM_PRES_COUNT'];
                    }
                    if (isset($currentCompany['UF_CRM_1709807026'])) {
                        $result['counts']['company'] = (int)$currentCompany['UF_CRM_1709807026'];
                    }

                    if (isset($currentCompany['UF_CRM_PRES_COUNT'])) {
                        $result['counts']['company'] = (int)$currentCompany['UF_CRM_PRES_COUNT'];
                    }
                }
                $filter = [];
                if (!empty($portal['bitrixLists'])) {
                    $listBitrixId = null;
                    foreach ($portal['bitrixLists'] as $list) {
                        if ($list['type'] == 'presentation') {
                            $listBitrixId = $list['bitrixId'];
                        }
                    }

                    if (!empty($portal['bitrixLists']['bitrixfields'])) {
                        $listBitrixId = null;
                        foreach ($portal['bitrixLists']['bitrixfields'] as $field) {
                            if ($field['code'] == 'sales_presentation_pres_crm') {
                                $filter[$field['bitrixCamelId']] = $currentCompany['ID'];
                            } else if ($field['code'] == 'sales_presentation_pres_responsible') {
                                $filter[$field['bitrixCamelId']] = 'user_' . $responsibleId;
                            }
                        }
                    }

                    if ($listBitrixId) {
                        $presList = BitrixListService::getList(
                            $hook,
                            $listBitrixId,
                            $filter
                        );
                    }
                }


                $sessionData = [
                    // 'hook' => $hook,
                    // 'portal' => $portal,
                    'currentTask' => $currentTask,
                    'currentCompany' => $currentCompany,
                    'deals' => [
                        'currentBaseDeal' => $currentBaseDeal,
                        'allBaseDeals' => $allBaseDeals,
                        'currentPresentationDeal' => $currentPresentationDeal,
                        'basePresentationDeals' => $basePresentationDeals,
                        'allPresentationDeals' => $allPresentationDeals,
                        'presList' => $presList,
                        'currentXODeal' => $currentXODeal,
                        'allXODeals' => $allXODeals,
                        'currentTaskDeals' => $btxDeals,
                        'allTMCDeals' => $allTMCDeals,
                        'currentTMCDeal' => $currentTMCDeal

                    ]
                ];



                FullEventInitController::setSessionItem(
                    $sessionKey,
                    $sessionData
                );
                // $fromSession = FullEventInitController::getSessionItem(
                //     $sessionKey
                // );
                return  $sessionData;
            } else {
                return null;
            }


            return null;
        } catch (\Throwable $th) {
            return null;
        }
    }


    public static function getFullDepartment(Request $request)
    {
        date_default_timezone_set('Europe/Moscow'); // Установка временной зоны
        $currentMonthDay = date('md');
        $result = [];
        $departmentResult = null;
        $generalDepartment = null;

        $childrenDepartments = null;
        $resultGeneralDepartment = [];

        $resultChildrenDepartments = [];
        try {
            //code...

            // записывает в session подготовленную data department по domain
            $data = $request->all();
            $domain = $data['domain'];
            $portal = PortalController::getPortal($domain);
            $portal = $portal['data'];
            $webhookRestKey = $portal['C_REST_WEB_HOOK_URL'];
            $hook = 'https://' . $domain  . '/' . $webhookRestKey;


            $sessionKey = 'department_' . $domain . '_' . $currentMonthDay;
            $sessionData = FullEventInitController::getSessionItem($sessionKey);

            if (!empty($sessionData)) {

                if (!empty($sessionData['department'])) {
                    $result = $departmentResult = $sessionData;
                    $departmentResult = $sessionData['department'];
                    $result['fromSession'] = true;
                }
            }

            if (empty($departmentResult)) {                               // если в сессии нет department
                $departamentService = new BitrixDepartamentService($hook);
                $department =  $departamentService->getDepartamentIdByPortal($portal);

                $allUsers = [];
                if (!empty($department)) {

                    if (!empty($department['bitrixId'])) {
                        $departmentId =  $department['bitrixId'];


                        if ($departmentId) {
                            $generalDepartment = $departamentService->getDepartments([
                                'ID' =>  $departmentId
                            ]);
                            $childrenDepartments = $departamentService->getDepartments([
                                'PARENT' =>  $departmentId
                            ]);


                            if (!empty($generalDepartment)) {
                                foreach ($generalDepartment as $gDep) {
                                    if (!empty($gDep)) {
                                        if (!empty($gDep['ID'])) {
                                            // array_push($departamentIds, $gDep['ID']);
                                            $departmentUsers = $departamentService->getUsersByDepartment($gDep['ID']);

                                            $resultDep = $gDep;
                                            $resultDep['USERS'] = $departmentUsers;
                                            $allUsers = array_merge($allUsers, $departmentUsers);
                                            array_push($resultGeneralDepartment, $resultDep);
                                        }
                                    }
                                }
                            }

                            if (!empty($childrenDepartments)) {
                                foreach ($childrenDepartments as $chDep) {
                                    if (!empty($chDep)) {
                                        if (!empty($chDep['ID'])) {
                                            // array_push($departamentIds, $chDep['ID']);
                                            $departmentUsers  = $departamentService->getUsersByDepartment($chDep['ID']);
                                            $resultDep = $chDep;
                                            $resultDep['USERS'] = $departmentUsers;

                                            $allUsers = array_merge($allUsers, $departmentUsers);
                                            array_push($resultChildrenDepartments, $resultDep);
                                        }
                                    }
                                }
                            }
                        }
                        $departmentResult = [
                            'generalDepartment' => $resultGeneralDepartment,
                            'childrenDepartments' => $resultChildrenDepartments,
                            'allUsers' => $allUsers,
                        ];
                        $result =  ['department' => $departmentResult];
                        FullEventInitController::setSessionItem(
                            $sessionKey,
                            $result
                        );
                    }
                }
            }


            return APIOnlineController::getSuccess(
                $result
            );
        } catch (\Throwable $th) {
            return APIOnlineController::getError(
                $th->getMessage(),
                ['departament' => $departmentResult]
            );
        }
    }



    public static function getPresTMCInitDeal(
        $domain,
        $hook,
        $tmcdealId,
        $userId,
        $companyId
    )  // GET DEALS AND INIT REDIS INIT EVENT FROM NEW TASK  
    {

        // entities [deal, smart ...]
        //companyId
        //userId
        //currentTask
        $sessionData = null;
        try {

            if (
                // !empty($data['userId'])  &&
                // !empty($data['companyId']) &&
                !empty($domain) &&
                !empty($hook) &&
                !empty($tmcdealId) &&
                !empty($userId) &&
                !empty($companyId)


            ) {
                $responsibleId = $userId;
                $allPresentationDeals = [];                  // все сделки презентации связанные с компанией и пользователем
                $currentCompany = null;



                $getAllPresDealsData = [];


                $sessionKey = 'tmcInit_' . $domain . '_' . $userId . '_' . $companyId;
                $tmcDeal = BitrixGeneralService::getEntity($hook, 'deal', $tmcdealId);

                $sessionData = [

                    'tmcDeal' => $tmcDeal,
                ];


                FullEventInitController::setSessionItem(
                    $sessionKey,
                    $sessionData
                );
                // $fromSession = FullEventInitController::getSessionItem(
                //     $sessionKey
                // );
                return $sessionData;
            }


            return $sessionData;
        } catch (\Throwable $th) {
            return $sessionData;
        }
    }
}
