<?php

namespace App\Http\Controllers\Front\Calling;

use App\Http\Controllers\APIController;
use App\Http\Controllers\APIOnlineController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\PortalController;
use App\Jobs\EventJob;
use App\Services\BitrixGeneralService;
use App\Services\FullEventReport\EventReportService;
use App\Services\General\BitrixDealService;
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

            ];
            $isFullData = true;

            if (isset($request->currentTask)) {
                $data['currentTask'] = $request->currentTask;
            } else {
                $isFullData = false;
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
            if ($isFullData) {
                // $service = new EventReportService($data);
                // $result = $service->getEventFlow();
                // return $result;
                dispatch(
                    new EventJob($data)
                )->onQueue('high-priority');

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

    public static function getFullDeals(Request $request)
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
                $responsibleId = 1;
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
                    if (!empty($btxDealPortalCategory['code'])) {
                        foreach ($btxDealPortalCategories as $btxDealPortalCategory) {
                            $currenBaseCategoryBtxId = $btxDealPortalCategory['bitrixId'];

                            array_push($allExecludeStages, 'C' . $currenBaseCategoryBtxId . ':LOSE');
                            array_push($allExecludeStages, 'C' . $currenBaseCategoryBtxId . ':APOLOGY');
                            if ($btxDealPortalCategory['code'] == "sales_base") {
                                array_push($allExecludeStages, 'C' . $currenBaseCategoryBtxId . ':WON');
                            }
                            array_push($allIncludeCategories, $currenBaseCategoryBtxId);
                        }
                    }

                    $getAllDealsData =  [
                        'filter' => [
                            'COMPANY_ID' => $currentCompany['ID'],
                            'CATEGORY_ID' => $allIncludeCategories,
                            'RESPONSIBLE_ID' => $responsibleId,
                            '!=STAGE_ID' => $allExecludeStages
                        ],
                        'select' => [
                            'ID',
                            'TITLE',
                            'UF_CRM_PRES_COUNT',
                            'STAGE_ID',
                            'UF_CRM_TO_BASE_SALES'

                        ]
                    ];


                    $allDeals =   BitrixDealService::getDealList(
                        $hook,
                        $getAllDealsData,
                    );

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
                            }
                        }
                    }

                    //deal from company user by category
                    foreach ($allDeals as $btxDealFromAll) {
                        foreach ($btxDealPortalCategories as $btxDealPortalCategory) {
                            if (!empty($btxDealPortalCategory['code'])) {
                                if ($btxDealPortalCategory['code'] == "sales_base") {

                                    if ($btxDealFromAll['CATEGORY_ID'] == $btxDealPortalCategory['bitrixId']) {
                                        array_push($allBaseDeals, $btxDealFromAll);
                                    }
                                } else  if ($btxDealPortalCategory['code'] == "sales_presentation") {
                                    $currentPresentCategoryBtxId = $btxDealPortalCategory['bitrixId'];
                                    if ($btxDealFromAll['CATEGORY_ID'] == $btxDealPortalCategory['bitrixId']) {
                                        array_push($allPresentationDeals, $btxDealFromAll);


                                        if (!empty($currentBaseDeal)) {
                                            if (!empty($currentBaseDeal['ID'])) {

                                                if (!empty($btxDealFromAll['UF_CRM_TO_BASE_SALES'])) {
                                                    if ($btxDealFromAll['UF_CRM_TO_BASE_SALES'] == $currentBaseDeal['ID']) {
                                                        array_push($basePresentationDeals, $btxDealFromAll);
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            } else  if ($btxDealPortalCategory['code'] == "sales_xo") {
                                $currentXOCategoryBtxId = $btxDealPortalCategory['bitrixId'];
                                if ($btxDealFromAll['CATEGORY_ID'] == $currentXOCategoryBtxId) {
                                    array_push($allXODeals, $btxDealFromAll);
                                }
                            }
                        }
                    }
                }



                // foreach ($btxDealPortalCategories as $btxDealPortalCategory) {
                //     if (!empty($btxDealPortalCategory['code'])) {
                //         if ($btxDealPortalCategory['code'] == "sales_base") {



                //             foreach ($btxDeals as $btxDeal) {
                //                 $currenBaseCategoryBtxId = $btxDealPortalCategory['bitrixId'];

                //                 if (!empty($btxDeal['CATEGORY_ID'])) {
                //                     if ($btxDeal['CATEGORY_ID'] == $btxDealPortalCategory['bitrixId']) {
                //                         $currentBaseDeal = $btxDeal;   //базовая сделка в задаче всегда должна быть одна
                //                     }
                //                 }


                //                 $getAllBaseDealsData =  [
                //                     'filter' => [
                //                         'COMPANY_ID' => $currentCompany['ID'],
                //                         'CATEGORY_ID' => $currenBaseCategoryBtxId,
                //                         'RESPONSIBLE_ID' => $responsibleId,
                //                         '!=STAGE_ID' => ['C' . $currenBaseCategoryBtxId . ':WON', 'C' . $currenBaseCategoryBtxId . ':LOSE', 'C' . $currenBaseCategoryBtxId . ':APOLOGY']
                //                     ],
                //                     'select' => [
                //                         'ID',
                //                         'TITLE',
                //                         'UF_CRM_PRES_COUNT',
                //                         'STAGE_ID',

                //                     ]

                //                 ];


                //                 $allBaseDeals =   BitrixDealService::getDealList(
                //                     $hook,
                //                     $getAllBaseDealsData,
                //                 );
                //             }
                //         } else  if ($btxDealPortalCategory['code'] == "sales_presentation") {
                //             $currentPresentCategoryBtxId = $btxDealPortalCategory['bitrixId'];

                //             foreach ($btxDeals as $btxDeal) {
                //                 if (!empty($btxDeal['CATEGORY_ID'])) {
                //                     if ($btxDeal['CATEGORY_ID'] == $currentPresentCategoryBtxId) {
                //                         $currentPresentationDeal = $btxDeal;      // сделка презентации из задачи

                //                     }
                //                 }


                //                 $getAllPresDealsData =  [
                //                     'filter' => [
                //                         'COMPANY_ID' => $currentCompany['ID'],
                //                         'CATEGORY_ID' => $currentPresentCategoryBtxId,
                //                         'RESPONSIBLE_ID' => $responsibleId,
                //                         '!=STAGE_ID' => ['C' . $currentPresentCategoryBtxId . ':LOSE', 'C' . $currentPresentCategoryBtxId . ':APOLOGY']
                //                     ],
                //                     'select' => [
                //                         'ID',
                //                         'TITLE',
                //                         'UF_CRM_PRES_COUNT',
                //                         'STAGE_ID',

                //                     ]
                //                 ];

                //                 sleep(1);
                //                 $allPresentationDeals =   BitrixDealService::getDealList(
                //                     $hook,
                //                     $getAllPresDealsData,
                //                 );

                //                 if (!empty($currentBaseDeal)) {
                //                     if (!empty($currentBaseDeal['ID'])) {
                //                         $getAllPresDealsData =  [
                //                             'filter' => [
                //                                 'COMPANY_ID' => $currentCompany['ID'],
                //                                 'CATEGORY_ID' => $currentPresentCategoryBtxId,
                //                                 'RESPONSIBLE_ID' => $responsibleId,
                //                                 '!=STAGE_ID' => ['C' . $currentPresentCategoryBtxId . ':LOSE', 'C' . $currentPresentCategoryBtxId . ':APOLOGY'],
                //                                 'UF_CRM_TO_BASE_SALES' => $currentBaseDeal['ID']
                //                             ],
                //                             'select' => [
                //                                 'ID',
                //                                 'TITLE',
                //                                 'UF_CRM_PRES_COUNT',
                //                                 'STAGE_ID',

                //                             ]
                //                         ];


                //                         $basePresentationDeals =   BitrixDealService::getDealList(
                //                             $hook,
                //                             $getAllPresDealsData,
                //                         );
                //                     }
                //                 }
                //             }
                //         } else  if ($btxDealPortalCategory['code'] == "sales_xo") {
                //             $currentXOCategoryBtxId = $btxDealPortalCategory['bitrixId'];

                //             foreach ($btxDeals as $btxDeal) {
                //                 if (!empty($btxDeal['CATEGORY_ID'])) {
                //                     if ($btxDeal['CATEGORY_ID'] == $currentXOCategoryBtxId) {
                //                         $currentXODeal = $btxDeal;      // сделка презентации из задачи

                //                     }
                //                 }


                //                 $getAllXODealsData =  [
                //                     'filter' => [
                //                         'COMPANY_ID' => $currentCompany['ID'],
                //                         'CATEGORY_ID' => $currentXOCategoryBtxId,
                //                         'RESPONSIBLE_ID' => $responsibleId,
                //                         '!=STAGE_ID' => ['C' . $currentXOCategoryBtxId . ':LOSE', 'C' . $currentXOCategoryBtxId . ':APOLOGY']
                //                     ],
                //                     'select' => [
                //                         'ID',
                //                         'TITLE',
                //                         'UF_CRM_PRES_COUNT',
                //                         'STAGE_ID',

                //                     ]
                //                 ];

                //                 sleep(1);
                //                 $allXODeals =   BitrixDealService::getDealList(
                //                     $hook,
                //                     $getAllXODealsData,
                //                 );
                //             }
                //         }
                //     }
                // }



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
                                $filter[$field['bitrixCamelId']] = 'user_1';
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
                        'hook' => $hook,
                        'portal' => $portal,
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
                            'allDeals' => $allDeals

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
                            'allDeals' => $allDeals

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
}
