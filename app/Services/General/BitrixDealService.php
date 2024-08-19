<?php

namespace App\Services\General;

use App\Http\Controllers\APIBitrixController;
use App\Http\Controllers\APIOnlineController;
use App\Http\Controllers\PortalController;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BitrixDealService
{

    //smart
    static function getDealId(
        $hook,
        $leadId, //lidId ? from lead
        $companyId, //companyId ? from company
        $userId,
        $portalDeal, //april deal data
        $currentCategoryData //april deal category data

    ) {
        // lidIds UF_CRM_7_1697129081
        $currentDeal = null;

        try {
            $method = '/crm.deal.list.json';
            $url = $hook . $method;
            // $portalDealCategories =  $portalDeal['categories'];
            $currentCategoryBtxId = $currentCategoryData['bitrixId'];
            if ($companyId) {
                $data =  [

                    'filter' => [
                        // "!=stage_id" => ["DT162_26:SUCCESS", "DT156_12:SUCCESS"],
                        // "=assignedById" => $userId,
                        "=CATEGORY_ID" => $currentCategoryBtxId,
                        'COMPANY_ID' => $companyId,
                        "ASSIGNED_BY_ID" => $userId,
                        "!=STAGE_ID" =>  ["C" . $currentCategoryBtxId . ":WON"]

                    ],
                    'select' => ["ID", "CATEGORY_ID", "STAGE_ID"],
                ];
            }
            if ($leadId) {
                $data =  [
                    // 'entityTypeId' => $smart['crmId'],
                    'filter' => [
                        // "!=stage_id" => ["DT162_26:SUCCESS", "DT156_12:SUCCESS"],
                        // "=assignedById" => $userId,

                        // "=%ufCrm7_1697129081" => '%' . $leadId . '%',
                        "ASSIGNED_BY_ID" => $userId,
                        'LEAD_ID' => $leadId,
                        "CATEGORY_ID" => $currentCategoryBtxId,
                        "!=STAGE_ID" =>  ["C" . $currentCategoryBtxId . ":WON"]

                    ],
                    'select' => ["ID", "CATEGORY_ID", "STAGE_ID"],
                ];
            }

            // Log::info('DEAL TEST', [
            //     'BitrixDealService::getDealId data' => $data,


            // ]);
            $response = Http::get($url, $data);
            // $responseData = $response->json();
            $currentDeal = APIBitrixController::getBitrixRespone($response, 'general service: get deal');
            if (isset($currentDeal)) {
                if (!empty($currentDeal['items'])) {
                    $currentDeal =  $currentDeal['items'][0];
                }
            }
            if (is_array($currentDeal)) {
                $currentDeal =  $currentDeal[0];
            }
            // if (!empty($currentDeal['ID'])) {
            //     $currentDeal =  $currentDeal['ID'];
            // }
            // Log::channel('telegram')->info('COLD DEAL get currentDeal', [
            //     'currentDeal' => $currentDeal
            // ]);
            // Log::info('DEAL TEST', [

            //     'BitrixDealService::getDealId' => $currentDeal,

            // ]);
            return $currentDeal;
        } catch (\Throwable $th) {
            return $currentDeal;
        }
    }



    static function setDeal(
        $hook,
        $fieldsData,

    ) {
        $responseData = null;
        try {
            $methodSmart = '/crm.deal.add.json';
            $url = $hook . $methodSmart;



            $data = [

                'fields' =>  $fieldsData

            ];
            // Log::channel('telegram')->error('APRIL_HOOK createSmartItem', [

            //     'createSmartItem data' => $data
            // ]);


            $smartFieldsResponse = Http::get($url, $data);

            $responseData = APIBitrixController::getBitrixRespone($smartFieldsResponse, 'general service: create Deal Item');




            if (isset($responseData['item'])) {
                $responseData = $responseData['item'];
            }
            return $responseData;
        } catch (\Throwable $th) {
            return $responseData;
        }
    }

    static function updateDeal(
        $hook,
        $dealId,
        $fieldsData
    ) {

        $methodSmart = '/crm.deal.update';
        $url = $hook . $methodSmart;
        $resultFields = null;
        $data = [
            'id' => $dealId,
            'fields' =>  $fieldsData

        ];



        $smartFieldsResponse = Http::get($url, $data);

        $responseData = APIBitrixController::getBitrixRespone($smartFieldsResponse, 'general service: update deal');
        $resultFields = $responseData;

        if (isset($responseData['item'])) {
            $resultFields = $responseData['item'];
        }

        return $resultFields;
    }


    protected function productsSet(
        $hook,
        $dealId,
        $fieldsData
    ) {


        try {
            $method = '/crm.item.productrow.set.json';
            $url = $hook . $method;
            $fieldsData['ownerId'] = $dealId;
            foreach ($fieldsData['productRows'] as $product) {
                $product['ownerId'] = $dealId;
            }
            $response = Http::get($url, $fieldsData);
            $responseData = APIBitrixController::getBitrixRespone($response, 'general service: update deal');


            return $responseData;
        } catch (\Throwable $th) {
            $errorMessages =  [
                'message'   => $th->getMessage(),
                'file'      => $th->getFile(),
                'line'      => $th->getLine(),
                'trace'     => $th->getTraceAsString(),
            ];
            // Log::channel('telegram')->error('APRIL_ONLINE', [
            //     'productsSet' => $errorMessages
            // ]);

            return null;
        }
    }



    static function getDeal(
        $hook,
        $data,

    ) {
        $responseData = null;
        try {
            $methodSmart = '/crm.deal.get.json';
            $url = $hook . $methodSmart;



            $smartFieldsResponse = Http::get($url, $data);

            $responseData = APIBitrixController::getBitrixRespone($smartFieldsResponse, 'general service: create Deal Item');

            if (isset($responseData['item'])) {
                $responseData = $responseData['item'];
            }
            if (isset($responseData['deal'])) {
                $responseData = $responseData['deal'];
            }
            return $responseData;
        } catch (\Throwable $th) {
            return $responseData;
        }
    }
    static function getDealList(
        $hook,
        $data,

    ) {
        $responseData = null;
        try {
            $methodSmart = '/crm.deal.list';
            $url = $hook . $methodSmart;



            $smartFieldsResponse = Http::get($url, $data);

            $responseData = APIBitrixController::getBitrixRespone($smartFieldsResponse, 'general service: create Deal Item');

            if (isset($responseData['items'])) {
                $responseData = $responseData['items'];
            }
            if (isset($responseData['deals'])) {
                $responseData = $responseData['deals'];
            }
            return $responseData;
        } catch (\Throwable $th) {
            return $responseData;
        }
    }

    static function deleteSmartItem($hook, $entityId, $smartId)
    {

        $methodSmart = '/crm.item.delete.json';
        $url = $hook . $methodSmart;

        $data = [
            'id' => $smartId,
            'entityTypeId' => $entityId,


        ];



        $smartFieldsResponse = Http::get($url, $data);

        $responseData = APIBitrixController::getBitrixRespone($smartFieldsResponse, 'general service: deleteSmartItem');
        $resultFields = $responseData;
        // Log::channel('telegram')->info(
        //     'lead/complete deleteSmartItem',
        //     [
        //         'responseData' => $responseData,

        //     ]
        // );
        return $resultFields;
    }



    // utils

    static function getTargetCategoryData(
        $portalDealData,
        $currentDepartamentType,
        $eventType, // xo warm presentation,
        $eventAction,  // plan done expired fail


    ) {
        // sales_base
        // sales_xo
        // sales_presentation
        // tmc_base
        // Log::channel('telegram')->info("DEAL FLOW", ['currentDepartamentType' => $currentDepartamentType]);

        $resultCategoryDatas = [];
        $categoryPrephicks = [];

        if ($currentDepartamentType === 'sales') {
            if (
                ($eventType == 'document') ||
                $eventAction == 'plan' ||
                ($eventAction == 'done' && $eventType == 'presentation') ||

                $eventAction == 'fail' ||
                $eventAction == 'success'
            ) {

                array_push($categoryPrephicks, $currentDepartamentType . '_base');
            }


            if ($eventType == 'xo') {
                // $categoryPrephicks = 'xo';
                array_push($categoryPrephicks, $currentDepartamentType . '_xo');
            } else if ($eventType == 'presentation') {
                array_push($categoryPrephicks, $currentDepartamentType . '_presentation');
            }
        } else   if ($currentDepartamentType === 'tmc') {
            // if (
            //     $eventAction == 'plan' ||
            //     // ($eventAction == 'done' && $eventType == 'presentation') ||
            //     $eventAction == 'fail' ||
            //     $eventAction == 'success'
            // ) {

            array_push($categoryPrephicks, $currentDepartamentType . '_base');
            // }


            // if ($eventType == 'xo') {
            //     // $categoryPrephicks = 'xo';
            //     array_push($categoryPrephicks, 'sales_xo');
            // }
        }
        if ($eventType == 'document') {
            array_push($categoryPrephicks, 'sales' . '_base');
        }
        $currentCategory = null;
        if (!empty($portalDealData['categories'])) {

            foreach ($portalDealData['categories'] as $category) {
                // Log::channel('telegram')->info('DEAL TEST', [
                //     'category code' => $category['code']
                // ]);
                if (in_array($category['code'], $categoryPrephicks)) {
                    $currentCategory = $category;
                    array_push($resultCategoryDatas, $category);
                }
            }
        }
        // Log::info('DEAL TEST', [
        //     // 'resultCategoryDatas' => $resultCategoryDatas,
        //     'eventType' => $eventType,
        //     'eventAction' => $eventAction,
        //     'categoryPrephicks' => $categoryPrephicks,
        //     // 'currentCategoryData' => "C" . $currentCategoryData['bitrixId'] . ':' . $stage['bitrixId'],
        //     // 'isCurrentSearched' => $isCurrentSearched,
        // ]);

        return $resultCategoryDatas;
    }

    static function getTargetStage(
        $currentCategoryData,
        $group,
        $eventType, // xo warm presentation,
        $eventAction,  // plan done expired fail
        $isResult,
    ) {
        // sales_new
        // sales_cold
        // sales_warm
        // sales_pres
        // sales_offer_create
        // sales_document_send
        // sales_in_progress
        // sales_money_await
        // sales_supply
        // sales_success
        // sales_fail
        // sales_double
        // cold_new
        // cold_plan
        // cold_pending
        // cold_success
        // cold_fail
        // cold_noresult
        // spres_new
        // spres_plan
        // spres_pending
        // spres_success
        // spres_fail
        // spres_noresult
        // sales_tmc_new
        // sales_tmc_plan
        // sales_tmc_pending
        // sales_tmc_pres_in_progress
        // sales_tmc_pres_plan
        // sales_tmc_success
        // sales_tmc_fail
        $targetStageBtxId = null;
        $stageSuphicks = 'plan';
        $stagePrephicks = 'warm';


        if ($currentCategoryData['code'] === 'sales_base') {
            $stagePrephicks = 'sales';

            if ($eventAction == 'fail' || $eventAction == 'success') {
                $stageSuphicks = $eventAction;
            } else {
                if ($eventType == 'xo') {
                    $stageSuphicks = 'cold';
                } else if ($eventType == 'warm') {
                    $stageSuphicks = 'warm';
                } else if ($eventType == 'presentation') {
                    $stageSuphicks = 'pres';
                } else if ($eventType == 'hot') {
                    $stageSuphicks = 'in_progress';
                } else if ($eventType == 'moneyAwait') {
                    $stageSuphicks = 'money_await';
                }
            }
        } else {
            if ($eventAction == 'done' || $eventAction == 'success') {
                $stageSuphicks = 'success';
            } else if ($eventAction == 'expired') {
                $stageSuphicks = 'pending';
            } else if ($eventAction == 'fail') {
                $stageSuphicks = 'fail';

                if (!$isResult) {
                    $stageSuphicks = 'noresult';
                }
            }

            if ($eventType == 'xo') {
                $stagePrephicks = 'cold';
            }
            //  else if ($eventType == 'warm') {
            //     $stagePrephicks = 'sales';
            // }
            else if ($eventType == 'presentation') {
                $stagePrephicks = 'spres';
            }

            if ($group == 'tmc') {
                $stagePrephicks = 'sales_tmc';
                if ($eventAction == 'plan' && $eventType == 'presentation') {
                    $stageSuphicks = 'pres_in_progress';
                }
                if ($eventAction == 'done' && $eventType == 'presentation') {
                    $stageSuphicks = 'success';
                }
            }
        }
        if ($eventType === 'document') {
            $stagePrephicks = 'sales';
            $stageSuphicks = 'offer_create';
        }
        if (!empty($currentCategoryData['stages'])) {

            foreach ($currentCategoryData['stages'] as $stage) {

                // if ($eventType === 'xo' || $eventType === 'cold') {

                if ($stage['code'] == $stagePrephicks . '_' . $stageSuphicks) {
                    $targetStageBtxId = $stage['bitrixId'];
                    Log::channel('telegram')->info('DEAL TEST', [
                        'stageCode' => $stage['code'],
                        'eventType' => $eventType,

                        'stage' => $stage,

                    ]);
                }
                // }
            }
        }

        return $targetStageBtxId;
    }



    static function getIsCanDealStageUpdate(
        $currentDeal, //with ID CATEGORY_ID STAGE_ID
        $targetStageBtxId,
        $currentCategoryData,
        // $eventType, // xo warm presentation,
        // $eventAction,  // plan done expired fail
    ) {
        $result = false;
        // Log::channel('telegram')->info('HOOK TEST CURRENTENTITY', [
        //     'currentDeal' => $currentDeal,
        //     'targetStageBtxId' => $targetStageBtxId,
        //     'currentCategoryData' => $currentCategoryData,

        // ]);
        Log::info('HOOK TEST CURRENTENTITY', [
            'currentDeal' => $currentDeal,
            'targetStageBtxId' => $targetStageBtxId,
            'currentCategoryData' => $currentCategoryData,

        ]);
        if (!empty($currentDeal) && !empty($targetStageBtxId)) {

            if ($currentCategoryData['code'] === 'sales_base') {
                // $stagePrephicks = 'sales';

                // if ($eventType == 'xo') {
                //     $stageSuphicks = 'cold';
                // } else if ($eventType == 'warm') {
                //     $stageSuphicks = 'warm';
                // } else if ($eventType == 'presentation') {
                //     $stageSuphicks = 'pres';
                // }

                $isCurrentSearched = false;
                if (!empty($currentCategoryData['stages'])) {

                    foreach ($currentCategoryData['stages'] as $stage) {

                        if ($stage['bitrixId'] ==  $targetStageBtxId) {
                            $result = $isCurrentSearched && true;
                        }

                        // if ($eventType === 'xo' || $eventType === 'cold') {
                        if ("C" . $currentCategoryData['bitrixId'] . ':' . $stage['bitrixId'] ==  $currentDeal['STAGE_ID']) {
                            $isCurrentSearched = true;
                            // Log::channel('telegram')->info('HOOK TEST CURRENTENTITY', [
                            //     'isCurrentSearched' => $isCurrentSearched,
                            //     'stage' => $stage,
                            //     'currentCategoryData' => $currentCategoryData['code'],

                            // ]);
                        }
                        Log::channel('telegram')->info('DEAL TEST', [
                            'bitrixId' => $stage['bitrixId'],
                            'isCurrentSearched' => $isCurrentSearched,
                            'result' => $result,
                        ]);
                        // }
                    }
                }
            } else {
                $result = true;
            }
        }


        // Log::channel('telegram')->info('HOOK TEST CURRENTENTITY', [
        //     'currentDeal' => $currentDeal,


        // ]);

        // Log::channel('telegram')->info('HOOK TEST CURRENTENTITY', [
        //     'getIsCanDealStageUpdate' => $result,


        // ]);
        return $result;
    }
}
