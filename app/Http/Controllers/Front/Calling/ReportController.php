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

    public static function getDealFullDeals(Request $request)
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

                $currentBaseDeal = null;               //базовая сделка в задаче всегда должна быть одна
                $currentPresentationDeal = null;               // сделка презентации из задачи
                $basePresentationDeals = [];                 // сделки презентаций связанные с основной через списки
                $allPresentationDeals = [];                  // все сделки презентации связанные с компанией и пользователем
                $currentCompany = null;

                // $companyId = $data['userId'];
                // $userId = $data['companyId'];
                $domain = $data['domain'];
                $companyId  = $data['domain'];
                $btxDeals = []; //from task
                $currentTask =  $data['currentTask'];


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


                                    $getAllPresDealsData =  [
                                        'filter' => [
                                            'COMPANY_ID' => $currentCompany['ID'],
                                            'CATEGORY_ID' => $currentPresentCategoryBtxId,
                                            'RESPONSIBLE_ID' => 1,
                                            '!=STAGE_ID' => ['C' . $currentPresentCategoryBtxId . ':LOSE', 'C' . $currentPresentCategoryBtxId . ':APOLOGY']
                                        ],
                                        'select' => [
                                            'ID',
                                            'TITLE',
                                            'UF_CRM_PRES_COUNT',
                                            'STAGE_ID',

                                        ]
                                    ];

                                    sleep(1);
                                    $allPresentationDeals =   BitrixDealService::getDealList(
                                        $hook,
                                        $getAllPresDealsData,
                                    );
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


                Log::channel('telegram')->info('session presList', [
                    'presList' => $presList

                ]);



                FullEventInitController::setSessionItem(
                    $sessionKey,
                    [
                        'hook' => $hook,
                        'portal' => $portal,
                        'currentTask' => $currentTask,
                        'currentCompany' => $currentCompany,
                        'deals' => [
                            'currentBaseDeal' => $currentBaseDeal,
                            'currentPresentationDeal' => $currentPresentationDeal,
                            'basePresentationDeals' => $basePresentationDeals,
                            'allPresentationDeals' => $allPresentationDeals,

                        ],
                        'presList' => $presList


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
                            '$getAllPresDealsData' => $getAllPresDealsData,
                            'fromSession' => $fromSession

                        ],
                        'presList' => $presList

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
