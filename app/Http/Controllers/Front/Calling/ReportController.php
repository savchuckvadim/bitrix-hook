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
}
