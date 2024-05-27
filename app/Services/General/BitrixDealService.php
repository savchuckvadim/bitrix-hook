<?php

namespace App\Services\General;

use App\Http\Controllers\APIBitrixController;
use App\Http\Controllers\APIOnlineController;
use App\Http\Controllers\PortalController;
use Carbon\Carbon;
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
            $portalDealCategories =  $portalDeal['categories'];
            $currentCategoryBtxId = $currentCategoryData['bitrixId'];
            if ($companyId) {
                $data =  [

                    'filter' => [
                        // "!=stage_id" => ["DT162_26:SUCCESS", "DT156_12:SUCCESS"],
                        // "=assignedById" => $userId,
                        "CATEGORY_ID" => $currentCategoryBtxId,
                        'COMPANY_ID' => $companyId,
                        "ASSIGNED_BY_ID" => $userId,
                        "!=STAGE_ID" =>  ["C" . $currentCategoryBtxId . ":SUCCESS"]

                    ],
                    'select' => ["ID"],
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
                        "!=STAGE_ID" =>  ["C" . $currentCategoryBtxId . ":SUCCESS"]

                    ],
                    'select' => ["ID"],
                ];
            }


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
            if (!empty($currentDeal['ID'])) {
                $currentDeal =  $currentDeal['ID'];
            }
            // Log::channel('telegram')->info('COLD DEAL get currentDeal', [
            //     'currentDeal' => $currentDeal
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

        $methodSmart = '/crm.deal.update.json';
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
        Log::channel('telegram')->info(
            'lead/complete deleteSmartItem',
            [
                'responseData' => $responseData,

            ]
        );
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
        $categoryPrephicks = 'xo';
        if ($eventType == 'xo') {
            $categoryPrephicks = 'xo';
        } else if ($eventType == 'warm') {
            $categoryPrephicks = 'base';
        } else if ($eventType == 'presentation') {
            $categoryPrephicks = 'presentation';
        }

        $currentCategory = null;
        if (!empty($portalDealData['categories'])) {

            foreach ($portalDealData['categories'] as $category) {

                if ($currentDepartamentType === 'sales') {
                    if ($category['code'] == 'sales_' . $categoryPrephicks) {
                        $currentCategory = $category;
                    }
                }
            }
        }

        return $currentCategory;
    }

    static function getTargetStage(
        $currentCategoryData,
        $group,
        $eventType, // xo warm presentation,
        $eventAction,  // plan done expired fail

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
        // spres_new
        // spres_plan
        // spres_pending
        // spres_success
        // spres_fail
        // sales_tmc_new
        // sales_tmc_plan
        // sales_tmc_pending
        // sales_tmc_pres_in_progress
        // sales_tmc_success
        // sales_tmc_fail
        $targetStageBtxId = null;
        $stageSuphicks = 'plan';
        $stagePrephicks = 'plan';
        if ($eventAction == 'done') {
            $stageSuphicks = 'success';
        } else if ($eventAction == 'expired') {
            $stageSuphicks = 'pending';
        } else if ($eventAction == 'fail') {
            $stageSuphicks = 'fail';
        }

        if ($eventType == 'xo') {
            $stagePrephicks = 'cold';
        } else if ($eventType == 'warm') {
            $stagePrephicks = 'sales';
        } else if ($eventType == 'presentation') {
            $stagePrephicks = 'spres';
        }

        Log::info('STAGES', [
            '$currentCategoryData' => $currentCategoryData
        ]);
        if (!empty($currentCategoryData['stages'])) {

            foreach ($currentCategoryData['stages'] as $stage) {

                // if ($eventType === 'xo' || $eventType === 'cold') {

                if ($stage['code'] == $stagePrephicks . '_' . $stageSuphicks) {
                    $targetStageBtxId = $stage['bitrixId'];
                }
                // }
            }
        }

        Log::info('STAGES', [
            'targetStageBtxId' => $targetStageBtxId,
            'stagePrephicks' => $stagePrephicks,
            'stageSuphicks' => $stageSuphicks
        ]);
        return $targetStageBtxId;
    }
}
