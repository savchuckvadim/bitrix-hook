<?php

namespace App\Services\HookFlow;


use App\Services\General\BitrixDealService;


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
        $eventAction,  // plan done expired fail
        $responsibleId,
        $fields

    ) {
        sleep(1);
        $currentDeal = null;
        $currentDeals = [];
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

                $currentDealId = BitrixDealService::getDealId(
                    $hook,
                    null,
                    $entityId,
                    $responsibleId,
                    $portalDealData,
                    $currentCategoryData

                );


                $fieldsData = [
                    'CATEGORY_ID' => $currentCategoryData['bitrixId'],
                    'STAGE_ID' => "C" . $currentCategoryData['bitrixId'] . ':' . $targetStageBtxId,
                    "COMPANY_ID" => $entityId
                ];


                if (!$currentDealId) {
                    $currentDeal = BitrixDealService::setDeal(
                        $hook,
                        $fieldsData,
                        $currentCategoryData

                    );
                } else {
                    $currentDeal = BitrixDealService::updateDeal(
                        $hook,
                        $currentDealId,
                        $fieldsData,


                    );
                }

                array_push($currentDeals, $currentDeal);
            }
        }


        return $currentDeals;
    }
}
