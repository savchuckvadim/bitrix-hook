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
        $portalDeal, //april smart data

    ) {
        // lidIds UF_CRM_7_1697129081
        $currentSmart = null;
        try {
            $method = '/crm.deal.list.json';
            $url = $hook . $method;
            $portalDealCategories =  $portalDeal['categories'];
            Log::channel('telegram')->info('COLD DEAL DATA', [
                'portalDealCategories' => $portalDealCategories
            ]);
            if ($companyId) {
                $data =  [
             
                    'filter' => [
                        // "!=stage_id" => ["DT162_26:SUCCESS", "DT156_12:SUCCESS"],
                        // "=assignedById" => $userId,
                        'COMPANY_ID' => $companyId,
                        "ASSIGNED_BY_ID" => $userId,
                        // "!=STAGE_ID" => 

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
                        'LEAD_ID' => $leadId 

                    ],
                    'select' => ["ID"],
                ];
            }


            $response = Http::get($url, $data);
            // $responseData = $response->json();
            $responseData = APIBitrixController::getBitrixRespone($response, 'general service: getSmartItem');
            if (isset($responseData)) {
                if (!empty($responseData['items'])) {
                    $currentSmart =  $responseData['items'][0];
                }
            }
           
            return $currentSmart;
        } catch (\Throwable $th) {
            return $currentSmart;
        }
    }



    static function setDeal(
        $hook,
        $fieldsData
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
           
            Log::channel('telegram')->error('APRIL_HOOK create deal', [

                'result Deal' => $responseData
            ]);


            if (isset($responseData['item'])) {
                $responseData = $responseData['item'];
            }
            return $responseData;
        } catch (\Throwable $th) {
            return $responseData;
        }
    }

    static function updateSmartItem($hook, $entityId, $smartId, $fieldsData)
    {

        $methodSmart = '/crm.item.update.json';
        $url = $hook . $methodSmart;

        $data = [
            'id' => $smartId,
            'entityTypeId' => $entityId,

            'fields' =>  $fieldsData

        ];



        $smartFieldsResponse = Http::get($url, $data);

        $responseData = APIBitrixController::getBitrixRespone($smartFieldsResponse, 'general service: updateSmartItemCold');
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
        $action, //cold warm done
    ){

        $currentCategory = null;
        if (!empty($portalDealData['categories'])) {

            foreach ($portalDealData['categories'] as $category) {

                if($currentDepartamentType === 'sales'){
                    if ($category['code'] == 'sales_base') {
                    } else if ($category['code'] == 'sales_xo') {
                        $currentCategory = $category;

                    } else if ($category['code'] == 'sales_presentation') {
                    } else if ($category['code'] == 'tmc_base') {
                    }

                }
               
            }
        }

        return $currentCategory;


    }

    protected function getTargetStage($portalDealCategories){
        Log::channel('telegram')->error('APRIL_HOOK updateCompany',
         ['portalDealCategories' => $portalDealCategories]
        );

        
    }

    
}
