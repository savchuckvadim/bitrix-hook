<?php

namespace App\Services\HookFlow;


use App\Services\General\BitrixDealService;
use Illuminate\Support\Facades\Log;

class BitrixDealFlowService



{

    public function __construct()
    {
    }




    static function flow(

        $hook,
        $portalDealData,
        $currentDepartamentType,
        $entityType,
        $entityId,
        $eventType, // xo warm presentation,
        $eventName, //Презентация , Звонок
        $eventAction,  // plan done expired fail
        $responsibleId,
        $fields

    ) {
        sleep(1);
        $currentDeal = null;
        $currentDealIds = [];
        $currentDealId = null;
        $currentCategoryDatas =  BitrixDealService::getTargetCategoryData(
            $portalDealData,
            $currentDepartamentType,
            $eventType,
            $eventAction
        );
        if (!empty($currentCategoryDatas)) {
            foreach ($currentCategoryDatas as $currentCategoryData) {
                $targetStageBtxId =  BitrixDealService::getTargetStage(
                    $currentCategoryData,
                    'sales',
                    $eventType,
                    $eventAction
                );

                $currentDeal = BitrixDealService::getDealId(
                    $hook,
                    null,
                    $entityId,
                    $responsibleId,
                    $portalDealData,
                    $currentCategoryData

                );
                if (!empty($currentDeal['ID'])) {
                    $currentDealId =  $currentDeal['ID'];
                }

                $fieldsData = [
                   
                    'CATEGORY_ID' => $currentCategoryData['bitrixId'],
                    'STAGE_ID' => "C" . $currentCategoryData['bitrixId'] . ':' . $targetStageBtxId,
                    "COMPANY_ID" => $entityId
                ];


                if($currentCategoryData['code'] === 'sales_xo' ||  $currentCategoryData['code'] === 'sales_presentation'){
                    $fieldsData['TITLE'] =  $eventName;
                }
               
                // Log::info('DEAL TEST', [
                //     'currentDealId' => $currentDealId,
                //     // 'targetStageBtxId' => $targetStageBtxId,
                //     // 'currentCategoryData' => "C" . $currentCategoryData['bitrixId'] . ':' . $stage['bitrixId'],
                //     // 'isCurrentSearched' => $isCurrentSearched,
                // ]);

                if (!$currentDealId) {
                    $currentDealId = BitrixDealService::setDeal(
                        $hook,
                        $fieldsData,
                        $currentCategoryData

                    );
                    // Log::info('DEAL TEST', [
                    //     'BitrixDealService::setDeal' => $currentDealId,

                    // ]);
                } else {

                    $isCanDealStageUpdate = BitrixDealService::getIsCanDealStageUpdate(
                        $currentDeal, //with ID CATEGORY_ID STAGE_ID
                        $targetStageBtxId,
                        $currentCategoryData,
                        // $eventType, // xo warm presentation,
                        // $eventAction,  // plan done expired fail
                    );

                    if ($isCanDealStageUpdate) {
                        BitrixDealService::updateDeal(
                            $hook,
                            $currentDealId,
                            $fieldsData,

                        );
                    }
                }

                array_push($currentDealIds, $currentDealId);
            }
        }


        return $currentDealIds;
    }
}
