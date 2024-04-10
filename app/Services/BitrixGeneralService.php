<?php

namespace App\Services;

use App\Http\Controllers\APIBitrixController;
use App\Http\Controllers\APIOnlineController;
use App\Http\Controllers\PortalController;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BitrixGeneralService
{
 

    static function getSmartItem(
        $hook,
        $leadId, //lidId ? from lead
        $companyId, //companyId ? from lead
        $userId,
        $smart, //april smart data

    ) {
        // lidIds UF_CRM_7_1697129081

        $currentSmart = null;

        $method = '/crm.item.list.json';
        $url = $hook . $method;
        if ($companyId) {
            $data =  [
                'entityTypeId' => $smart['crmId'],
                'filter' => [
                    "!=stage_id" => ["DT162_26:SUCCESS", "DT156_12:SUCCESS"],
                    "=assignedById" => $userId,
                    'COMPANY_ID' => $companyId,

                ],
                // 'select' => ["ID"],
            ];
        }
        if ($leadId) {
            $data =  [
                'entityTypeId' => $smart['crmId'],
                'filter' => [
                    "!=stage_id" => ["DT162_26:SUCCESS", "DT156_12:SUCCESS"],
                    "=assignedById" => $userId,

                    "=%ufCrm7_1697129081" => '%' . $leadId . '%',

                ],
                // 'select' => ["ID"],
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
    }


    //smart
    static function createSmartItem(
        $hook,
        $entityId,
        $fieldsData
    ) {

        $methodSmart = '/crm.item.add.json';
        $url = $hook . $methodSmart;



        $data = [
            'entityTypeId' => $entityId,
            'fields' =>  $fieldsData

        ];

  

        $smartFieldsResponse = Http::get($url, $data);

        $responseData = APIBitrixController::getBitrixRespone($smartFieldsResponse, 'cold: updateSmartItemCold');
        $resultFields = $responseData;

        return $resultFields;
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

        $responseData = APIBitrixController::getBitrixRespone($smartFieldsResponse, 'cold: updateSmartItemCold');
        $resultFields = $responseData;

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

        $responseData = APIBitrixController::getBitrixRespone($smartFieldsResponse, 'cold: updateSmartItemCold');
        $resultFields = $responseData;

        return $resultFields;
    }


}



