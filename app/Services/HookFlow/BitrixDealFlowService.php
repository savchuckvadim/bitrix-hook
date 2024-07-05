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
        $currentBtxDeals,
        $portalDealData,
        $currentDepartamentType,
        $entityType,
        $entityId,
        $eventType, // xo warm presentation, 
        $eventTypeName, //Презентация , Звонок
        $eventName, //имя планируемого события
        $eventAction,  // plan done expired fail success
        $responsibleId,
        $isResult,
        $fields,
        $newPresDeal = null //for mutation


    ) {
        sleep(1);
        //находит сначала целевые категиории сделок из portal   по eventType и eventAction - по тому что происходит
        //сюда могут при ходить массив текущих сделок и которых есть CATEGORY_ID такой как в portal->deal->category->bitrixId
        //
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
                    $eventAction,
                    $isResult,
                );


                // $currentDeal = BitrixDealService::getDealId(
                //     $hook,
                //     null,
                //     $entityId,
                //     $responsibleId,
                //     $portalDealData,
                //     $currentCategoryData

                // );
                $currentDeal = null;

                if (!empty($currentBtxDeals)) {
                    foreach ($currentBtxDeals as $curbtxDeal) {
                        if (!empty($curbtxDeal['CATEGORY_ID'])) {
                            if ($curbtxDeal['CATEGORY_ID'] == $currentCategoryData['bitrixId']) {
                                $currentDeal = $curbtxDeal;
                            }
                        }
                    }
                }


                if (!empty($currentDeal['ID'])) {
                    $currentDealId =  $currentDeal['ID'];
                }

                $fieldsData = [

                    'CATEGORY_ID' => $currentCategoryData['bitrixId'],
                    'STAGE_ID' => "C" . $currentCategoryData['bitrixId'] . ':' . $targetStageBtxId,
                    "COMPANY_ID" => $entityId
                ];




                if (!$currentDealId) {
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
                    $currentDealId = BitrixDealService::setDeal(
                        $hook,
                        $fieldsData,
                        $currentCategoryData

                    );

                    if ($currentCategoryData['code'] === 'sales_presentation') {
                        if (!empty($currentDealId)) {
                            $rand = 1;
                            sleep($rand);
                            $newPresDeal = BitrixDealService::getDeal(
                                $hook,
                                ['id' => $currentDealId]


                            );
                        }
                    }

                    // Log::info('DEAL TEST', [
                    //     'setDeal currentDealId' => $currentDealId,

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
                        $rand = 1;
                        sleep($rand);
                        BitrixDealService::updateDeal(
                            $hook,
                            $currentDealId,
                            $fieldsData,

                        );


                        $curbtxDeal['CATEGORY_ID'] = $currentCategoryData['bitrixId'];
                        $curbtxDeal['STAGE_ID'] = "C" . $currentCategoryData['bitrixId'] . ':' . $targetStageBtxId;
                    }
                }

                array_push($currentDealIds, $currentDealId);
            }
        }


        return $currentDealIds;
    }


    static function unplannedPresflow(

        $hook,
        $currentDeal,
        $portalDealData,
        $currentDepartamentType,
        $entityType,
        $entityId,
        $eventType, // xo warm presentation,
        $eventTypeName, //Презентация , Звонок
        $eventName, //имя планируемого события
        $eventAction,  // plan done expired fail
        $responsibleId,
        $isResult,
        $fields

    ) {



        $currentDealId = null;
        $currentCategoryDatas =  BitrixDealService::getTargetCategoryData(
            $portalDealData,
            $currentDepartamentType,
            $eventType,
            $eventAction
        );
        if (!empty($currentCategoryDatas)) {
            foreach ($currentCategoryDatas as $currentCategoryData) {




                if ($currentCategoryData['code'] == 'sales_presentation') {
                    // $currentDeal = null;

                    $targetStageBtxId =  BitrixDealService::getTargetStage(
                        $currentCategoryData,
                        'sales',
                        $eventType,
                        $eventAction,
                        $isResult,
                    );

                    // $rand = rand(1, 2);
                    // sleep($rand);
                    // $currentDeal = BitrixDealService::getDealId(
                    //     $hook,
                    //     null,
                    //     $entityId,
                    //     $responsibleId,
                    //     $portalDealData,
                    //     $currentCategoryData

                    // );


                    // if (!empty($currentBtxDeals)) {
                    //     foreach ($currentBtxDeals as $curbtxDeal) {
                    //         if (!empty($curbtxDeal['CATEGORY_ID'])) {
                    //             if ($curbtxDeal['CATEGORY_ID'] == $currentCategoryData['bitrixId']) {
                    //                 $currentDeal = $curbtxDeal;
                    //             }
                    //         }
                    //     }
                    // }


                    if (!empty($currentDeal['ID'])) {
                        $currentDealId =  $currentDeal['ID'];
                    }

                    $fieldsData = [

                        'CATEGORY_ID' => $currentCategoryData['bitrixId'],
                        'STAGE_ID' => "C" . $currentCategoryData['bitrixId'] . ':' . $targetStageBtxId,
                        "COMPANY_ID" => $entityId
                    ];


                    // if (
                    //     $eventAction === 'plan'
                    //     || ($eventAction === 'done' &&
                    //         $currentCategoryData['code'] === 'sales_presentation' &&
                    //         !$currentDealId
                    //     )
                    // ) {
                    // if ($currentCategoryData['code'] === 'sales_xo' ||  $currentCategoryData['code'] === 'sales_presentation') {
                    //     $fieldsData['TITLE'] = $eventTypeName . ' ' .  $eventName;
                    // }
                    // }

                    $rand = 1;
                    sleep($rand);

                    if (!$currentDeal) {

                        $fieldsData['TITLE'] = $eventTypeName . ' ' .  $eventName;

                        $currentDealId = BitrixDealService::setDeal(
                            $hook,
                            $fieldsData,
                            $currentCategoryData

                        );
                    } else {
                        BitrixDealService::updateDeal(
                            $hook,
                            $currentDealId,
                            $fieldsData,

                        );
                    }
                    // if (!$currentDealId) {
                    // Log::info('DEAL TEST', [
                    //     'currentDealId' => $currentDealId,

                    // ]);

                    $rand = 1;
                    sleep($rand);
                    $currentDeal = BitrixDealService::getDeal(
                        $hook,
                        ['id' => $currentDealId]


                    );
                }
            }
        }


        return $currentDeal;
    }


    static function getBaseDealFromCurrentBtxDeals(
        $portalDealData,
        $currentBtxDeals,
        // $eventType, // xo warm presentation,
        // $eventAction,  // plan done expired fail

    ) {
        // sales_base
        // sales_xo
        // sales_presentation
        // tmc_base
        $result = [];
        $currentBtxDeal = null;
        foreach ($currentBtxDeals as $btxDeal) {

            if (!empty($portalDealData['categories'])) {

                foreach ($portalDealData['categories'] as $category) {

                    if ($category['code'] == 'sales_base' && $btxDeal['CATEGORY_ID'] === $category['bitrixId']) {
                        $currentBtxDeal = $btxDeal;
                        $result = [$currentBtxDeal];
                    }
                }
            }
        }


        // Log::info('DEAL TEST', [
        //     'resultCategoryDatas' => $resultCategoryDatas,
        //     // 'targetStageBtxId' => $targetStageBtxId,
        //     // 'currentCategoryData' => "C" . $currentCategoryData['bitrixId'] . ':' . $stage['bitrixId'],
        //     // 'isCurrentSearched' => $isCurrentSearched,
        // ]);
        return $result;
    }
}
