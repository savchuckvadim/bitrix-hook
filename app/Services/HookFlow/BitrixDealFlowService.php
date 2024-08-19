<?php

namespace App\Services\HookFlow;

use App\Services\General\BitrixBatchService;
use App\Services\General\BitrixDealService;
use Illuminate\Support\Facades\Log;

class BitrixDealFlowService



{

    public function __construct() {}




    static function flow(

        $hook,
        $currentBtxDeals,
        $portalDealData,
        $currentDepartamentType  = 'sales',
        $entityType,
        $entityId,
        $eventType, // xo warm presentation, 
        $eventTypeName, //Презентация , Звонок
        $eventName, //имя планируемого события
        $eventAction,  // plan done expired fail success
        $responsibleId,
        $isResult,
        $fields,
        $tmcPresRelationDealId = null //id сделки TMC из BASE FLOW для связи с основной и со вделкой презентации




    ) {
        $rand = rand(1, 3); // случайное число от 300000 до 900000 микросекунд (0.3 - 0.9 секунды)
        sleep($rand);
        $newPresDeal = null; //for mutation
        //находит сначала целевые категиории сделок из portal   по eventType и eventAction - по тому что происходит
        //сюда могут при ходить массив текущих сделок и которых есть CATEGORY_ID такой как в portal->deal->category->bitrixId
        //
        $currentDealIds = [];
        if ($eventType == 'document') {

            // Log::channel('telegram')->info('HOOK TEST CURRENTENTITY', [
            //     'eventType' => $eventType,
            //     'currentDepartamentType' => $currentDepartamentType,

            // ]);

            // Log::channel('telegram')->info('HOOK TEST CURRENTENTITY', [
            //     'currentBtxDeals' => $currentBtxDeals,

            // ]);
        }
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
                    $currentDepartamentType,
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
                    "COMPANY_ID" => $entityId,
                    'ASSIGNED_BY_ID' => $responsibleId
                ];
                if ($currentCategoryData['code'] === 'tmc_base' && $eventType === 'presentation' && $eventAction === 'done') {
                    //если данная перебираемая сделка - тмц , при этом событие - сделана презентация
                    //значит у презернтации была привязана тмц сделка и это она - надо у нее не менять ответственного а толкько закрыть - 
                    // през по заявке состоялась
                    $fieldsData = [

                        'CATEGORY_ID' => $currentCategoryData['bitrixId'],
                        'STAGE_ID' => "C" . $currentCategoryData['bitrixId'] . ':' . $targetStageBtxId,
                        "COMPANY_ID" => $entityId,

                    ];
                }

                // Log::channel('telegram')->info('HOOK TEST CURRENTENTITY', [
                //     'fieldsData' => $fieldsData,

                // ]);
                if (!empty($tmcPresRelationDealId)) {
                    if ($eventType === 'presentation' && $eventAction === 'plan') {
                        $fieldsData['UF_CRM_TO_BASE_TMC'] = $tmcPresRelationDealId;
                    }
                }



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
                            $rand = mt_rand(1000000, 2000000); // случайное число от 300000 до 900000 микросекунд (0.3 - 0.9 секунды)
                            usleep($rand);
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
                    // Log::channel('telegram')->info('HOOK TEST CURRENTENTITY', [
                    //     'currentDealId' => $currentDealId,

                    // ]);
                    $isCanDealStageUpdate = BitrixDealService::getIsCanDealStageUpdate(
                        $currentDeal, //with ID CATEGORY_ID STAGE_ID
                        $targetStageBtxId,
                        $currentCategoryData,
                        // $eventType, // xo warm presentation,
                        // $eventAction,  // plan done expired fail
                    );
                    // Log::channel('telegram')->info('HOOK TEST CURRENTENTITY', [
                    //     'isCanDealStageUpdate' => $isCanDealStageUpdate,

                    // ]);
                    if ($isCanDealStageUpdate) {
                        $rand = mt_rand(1000000, 2000000); // случайное число от 300000 до 900000 микросекунд (0.3 - 0.9 секунды)
                        usleep($rand);
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


        return ['dealIds' => $currentDealIds, 'newPresDeal' => $newPresDeal];
    }
    static function batchFlow(

        $hook,
        $currentBtxDeals,
        $portalDealData,
        $currentDepartamentType  = 'sales',
        $entityType,
        $entityId,
        $eventType, // xo warm presentation, 
        $eventTypeName, //Презентация , Звонок
        $eventName, //имя планируемого события
        $eventAction,  // plan done expired fail success
        $responsibleId,
        $isResult,
        $fields,
        $tmcPresRelationDealId = null //id сделки TMC из BASE FLOW для связи с основной и со вделкой презентации




    ) {
        $rand = rand(1, 3); // случайное число от 300000 до 900000 микросекунд (0.3 - 0.9 секунды)
        sleep($rand);
        $newPresDeal = null; //for mutation
        //находит сначала целевые категиории сделок из portal   по eventType и eventAction - по тому что происходит
        //сюда могут при ходить массив текущих сделок и которых есть CATEGORY_ID такой как в portal->deal->category->bitrixId
        //

        $batchCommands = [];

        $currentDealIds = [];
        if ($eventType == 'document') {

            // Log::channel('telegram')->info('HOOK TEST CURRENTENTITY', [
            //     'eventType' => $eventType,
            //     'currentDepartamentType' => $currentDepartamentType,

            // ]);

            // Log::channel('telegram')->info('HOOK TEST CURRENTENTITY', [
            //     'currentBtxDeals' => $currentBtxDeals,

            // ]);
        }
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
                    $currentDepartamentType,
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
                    "COMPANY_ID" => $entityId,
                    'ASSIGNED_BY_ID' => $responsibleId
                ];
                if ($currentCategoryData['code'] === 'tmc_base' && $eventType === 'presentation' && $eventAction === 'done') {
                    //если данная перебираемая сделка - тмц , при этом событие - сделана презентация
                    //значит у презернтации была привязана тмц сделка и это она - надо у нее не менять ответственного а толкько закрыть - 
                    // през по заявке состоялась
                    $fieldsData = [

                        'CATEGORY_ID' => $currentCategoryData['bitrixId'],
                        'STAGE_ID' => "C" . $currentCategoryData['bitrixId'] . ':' . $targetStageBtxId,
                        "COMPANY_ID" => $entityId,

                    ];
                }

                // Log::channel('telegram')->info('HOOK TEST CURRENTENTITY', [
                //     'fieldsData' => $fieldsData,

                // ]);
                if (!empty($tmcPresRelationDealId)) {
                    if ($eventType === 'presentation' && $eventAction === 'plan') {
                        $fieldsData['UF_CRM_TO_BASE_TMC'] = $tmcPresRelationDealId;
                    }
                }



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
                    $batchCommand = BitrixDealFlowService::getBatchCommand($fieldsData, 'add', null);
                    $batchCommands['set_' . $currentCategoryData['code']] = $batchCommand;
                    // $currentDealId = BitrixDealService::setDeal(
                    //     $hook,
                    //     $fieldsData,
                    //     $currentCategoryData

                    // );

                    if ($currentCategoryData['code'] === 'sales_presentation') {
                        if (!empty($currentDealId)) {
                            $rand = mt_rand(1000000, 2000000); // случайное число от 300000 до 900000 микросекунд (0.3 - 0.9 секунды)
                            usleep($rand);
                            $batchCommand = BitrixDealFlowService::getBatchCommand($fieldsData, 'get', $currentDealId);
                            $batchCommands['newPresDeal'] = $batchCommand;


                            // $newPresDeal = BitrixDealService::getDeal(
                            //     $hook,
                            //     ['id' => $currentDealId]


                            // );
                        }
                    }

                    // Log::info('DEAL TEST', [
                    //     'setDeal currentDealId' => $currentDealId,

                    // ]);
                } else {
                    // Log::channel('telegram')->info('HOOK TEST CURRENTENTITY', [
                    //     'currentDealId' => $currentDealId,

                    // ]);
                    $isCanDealStageUpdate = BitrixDealService::getIsCanDealStageUpdate(
                        $currentDeal, //with ID CATEGORY_ID STAGE_ID
                        $targetStageBtxId,
                        $currentCategoryData,
                        // $eventType, // xo warm presentation,
                        // $eventAction,  // plan done expired fail
                    );
                    // Log::channel('telegram')->info('HOOK TEST CURRENTENTITY', [
                    //     'isCanDealStageUpdate' => $isCanDealStageUpdate,

                    // ]);
                    if ($isCanDealStageUpdate) {
                        $rand = mt_rand(1000000, 2000000); // случайное число от 300000 до 900000 микросекунд (0.3 - 0.9 секунды)
                        usleep($rand);
                        $batchCommand = BitrixDealFlowService::getBatchCommand($fieldsData, 'add', null);
                        $batchCommands['update_' . $currentCategoryData['code']] = $batchCommand;


                        // BitrixDealService::updateDeal(
                        //     $hook,
                        //     $currentDealId,
                        //     $fieldsData,

                        // );


                        $curbtxDeal['CATEGORY_ID'] = $currentCategoryData['bitrixId'];
                        $curbtxDeal['STAGE_ID'] = "C" . $currentCategoryData['bitrixId'] . ':' . $targetStageBtxId;
                    }
                }

                // array_push($currentDealIds, $currentDealId);
            }
        }
        $batchService =  new BitrixBatchService($hook);
        $result = $batchService->sendBatch($batchCommands);
        Log::info('HOOK BATCH getBatchCommand', ['result' => $result]);
        Log::channel('telegram')->info('HOOK BATCH getBatchCommand', ['result' => $result]);
        return ['dealIds' => $result, 'newPresDeal' => $newPresDeal];
    }

    static function getBatchCommand(
        $fieldsData,
        $method, //update | add
        $dealId
    ) {

        $currentMethod = 'crm.deal.' . $method;
        $result = $currentMethod;
        if ($method == 'update' || $method == 'get') {
            $result = $result . '?dealId=' . $dealId;
        }
        foreach ($fieldsData as $key => $value) {
            $result = $result .  '&fields[' . $key . ']=' . $value;
        }
        Log::info('HOOK BATCH getBatchCommand', ['result' => $result]);
        Log::channel('telegram')->info('HOOK BATCH getBatchCommand', ['result' => $result]);
        return $result;
    }
    static function tmcPresentationRelation(
        //изменяет сделку тмц из основного потока
        //из потока ТМЦ - ПЛАН,  ПЕРЕНОС, ОТКАЗ, Заявка в рассмотрении - если планирование презентации
        //+ будет создаваться задача для тмц Презентация: заявка в рассмотрении - чтобы он потом мог поставить заявку отменили
        //отсюда изменение сделки ТМЦ на SUCCESS если планируется презентация из основного flow (то есть
        // летит из rpa) и при этом есть связанная TMCDeal


        $hook,
        // $currentBtxDeals,
        $portalDealData,
        $currentBaseDeal,
        $currentPresDeal,
        $currentTmcDealId,




    ) {
        $rand = mt_rand(300000, 900000); // случайное число от 300000 до 900000 микросекунд (0.3 - 0.9 секунды)
        usleep($rand);
        $newPresDeal = null; //for mutation
        //находит сначала целевые категиории сделок из portal   по eventType и eventAction - по тому что происходит
        //сюда могут при ходить массив текущих сделок и которых есть CATEGORY_ID такой как в portal->deal->category->bitrixId
        //
        $currentDealIds = [];
        $categoryId = null;
        if (!empty($portalDealData['categories'])) {

            foreach ($portalDealData['categories'] as $category) {
                if ($category['code'] == 'tmc_base') {

                    $categoryId = $category['bitrixId'];
                }
            }
        }




        $fieldsData = [
            'CATEGORY_ID' => $categoryId,
            'STAGE_ID' => "C" . $categoryId . ':' . 'PRES_PLAN',
            // "COMPANY_ID" => $entityId,
            // 'ASSIGNED_BY_ID' => $responsibleId
            'UF_CRM_TO_BASE_SALES' => $currentBaseDeal['ID'],
            'UF_CRM_TO_PRESENTATION_SALES' => $currentPresDeal['ID'],
            'UF_CRM_PRES_COMMENTS' => $currentPresDeal['UF_CRM_PRES_COMMENTS'],
            'UF_CRM_LAST_PRES_DONE_RESPONSIBLE' => $currentPresDeal['ASSIGNED_BY_ID'],
            'UF_CRM_MANAGER_OP' => $currentPresDeal['ASSIGNED_BY_ID'],


        ];


        $result =  BitrixDealService::updateDeal(
            $hook,
            $currentTmcDealId,
            $fieldsData,

        );




        return $result;
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
                        "COMPANY_ID" => $entityId,
                        'ASSIGNED_BY_ID' => $responsibleId

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

                    sleep(1);

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
