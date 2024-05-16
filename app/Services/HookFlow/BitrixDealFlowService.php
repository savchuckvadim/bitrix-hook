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
        $eventAction,  // plan done expired 
        $responsibleId,
        $fields

    ) {
        sleep(1);
        $currentDeal = null;
        $currentDealId = null;
        $currentCategoryData =  BitrixDealService::getTargetCategoryData(
            $portalDealData,
            $currentDepartamentType,
            'cold'
        );
        $targetStageBtxId =  BitrixDealService::getTargetStage(
            $currentCategoryData,
            'sales',
            'cold'
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
    }
}
