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
        $eventTypeName, //Презентация , Звонок
        $eventName, //имя планируемого события
        $eventAction,  // plan done expired fail
        $responsibleId,
        $fields

    ) {
        sleep(1);

        $currentDealIds = [];

        $currentCategoryDatas =  BitrixDealService::getTargetCategoryData(
            $portalDealData,
            $currentDepartamentType,
            $eventType,
            $eventAction
        );
        if (!empty($currentCategoryDatas)) {
            foreach ($currentCategoryDatas as $currentCategoryData) {
                $currentDeal = null;
                $currentDealId = null;
                $targetStageBtxId =  BitrixDealService::getTargetStage(
                    $currentCategoryData,
                    'sales',
                    $eventType,
                    $eventAction
                );
                Log::info('DEAL TEST', [
                    'targetStageBtxId' => $targetStageBtxId,

                ]);
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


                if (
                    $eventAction === 'plan'
                    || ($eventAction === 'done' &&
                        $currentCategoryData['code'] === 'sales_presentation' &&
                        !$currentDealId
                    )
                ) {
                    if ($currentCategoryData['code'] === 'sales_xo' ||  $currentCategoryData['code'] === 'sales_presentation') {
                        $fieldsData['TITLE'] = $eventTypeName . ' ' .  $eventName;
                    }
                }

                Log::info('DEAL TEST', [
                    'currentDealId' => $currentDealId,
                    // 'targetStageBtxId' => $targetStageBtxId,
                    // 'currentCategoryData' => "C" . $currentCategoryData['bitrixId'] . ':' . $stage['bitrixId'],
                    // 'isCurrentSearched' => $isCurrentSearched,
                ]);

                if (!$currentDealId) {
                    Log::info('DEAL TEST', [
                        'currentDealId' => $currentDealId,

                    ]);
                    $currentDealId = BitrixDealService::setDeal(
                        $hook,
                        $fieldsData,
                        $currentCategoryData

                    );
                    Log::info('DEAL TEST', [
                        'setDeal currentDealId' => $currentDealId,

                    ]);
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
