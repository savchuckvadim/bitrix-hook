<?php

namespace App\Services\HookFlow;

use App\Services\General\BitrixBatchService;
use App\Services\General\BitrixDealService;
use Illuminate\Support\Facades\Log;

class BitrixDealBatchFlowService



{

    public function __construct() {}



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
        $tmcPresRelationDealId = null, //id сделки TMC из BASE FLOW для связи с основной и со вделкой презентации

        $resultBatchCommands, // = []
        $tag //plan unpres report newpresdeal


    ) {
        // $rand = rand(1, 3); // случайное число от 300000 до 900000 микросекунд (0.3 - 0.9 секунды)
        // sleep($rand);
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

                // Log::channel('telegram')->info('HOOK TEST', [
                //     'fieldsData' => $fieldsData,
                //     'currentDealId' => $currentDealId,
                // ]);
                if (!empty($tmcPresRelationDealId)) {
                    if ($eventType === 'presentation' && $eventAction === 'plan') {
                        $fieldsData['UF_CRM_TO_BASE_TMC'] = $tmcPresRelationDealId;
                    }
                }



                if (!$currentDealId) {  //нет сделки для категории но она перебираемая/ что-то запланировано или сделана през
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
                    $batchCommand = BitrixDealBatchFlowService::getBatchCommand($fieldsData, 'add', null);
                    $key = 'set_' . $tag . '_' . $currentCategoryData['code'];
                    $resultBatchCommands[$key] = $batchCommand; // в результате будет id
                    // $currentDealId = BitrixDealService::setDeal(
                    //     $hook,
                    //     $fieldsData,
                    //     $currentCategoryData

                    // );

                    if ($currentCategoryData['code'] === 'sales_presentation') {
                        if (!empty($currentDealId)) {
                            // $rand = mt_rand(1000000, 2000000); // случайное число от 300000 до 900000 микросекунд (0.3 - 0.9 секунды)
                            // usleep($rand);
                            // $batchCommand = BitrixDealBatchFlowService::getBatchCommand($fieldsData, 'get', $currentDealId);
                            // $batchCommands['newPresDeal'] = $batchCommand;
                            $batchCommand = BitrixDealBatchFlowService::getBatchCommand($fieldsData, 'add', null, $tag);
                            $key = 'newpresdealget_' . $tag . '_' . $currentCategoryData['code'] . '_' . $currentDealId . '_' . $targetStageBtxId;
                            $resultBatchCommands[$key] = $batchCommand;


                            // $newPresDeal = BitrixDealService::getDeal(
                            //     $hook,
                            //     ['id' => $currentDealId]


                            // );
                        }
                    } else {
                        // $batchCommand = BitrixDealBatchFlowService::getBatchCommand($fieldsData, 'add', null);
                        // $key = 'set_' . $tag . '_' . $currentCategoryData['code'];
                        // $resultBatchCommands[$key] = $batchCommand; // в результате будет id
                    }

                    // Log::info('DEAL TEST', [
                    //     'setDeal currentDealId' => $currentDealId,

                    // ]);
                } else { // пришла уже созданная сделка
                    // Log::channel('telegram')->info('HOOK TEST CURRENTENTITY', [
                    //     'currentDealId' => $currentDealId,

                    // ]);
                    // Закидываю batch вск команды для update а определять какие обновлять
                    $batchCommand = BitrixDealBatchFlowService::getBatchCommand($fieldsData, 'update', $currentDealId);
                    $key = 'update_' . $tag . '_' . $currentCategoryData['code'] . '_'  . $currentDealId . '_' . $targetStageBtxId;
                    $resultBatchCommands[$key] = $batchCommand;

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
                        // $rand = mt_rand(1000000, 2000000); // случайное число от 300000 до 900000 микросекунд (0.3 - 0.9 секунды)
                        // usleep($rand);
                        // $batchCommand = BitrixDealBatchFlowService::getBatchCommand($fieldsData, 'update', $currentDealId);
                        // $key = 'update_' . $tag . '_' . $currentCategoryData['code'] . '_'  . $currentDealId . '_' . $targetStageBtxId;
                        // $resultBatchCommands[$key] = $batchCommand;

                        // Log::info('HOOK BATCH batchFlow report DEAL', ['report batchCommands' => $resultBatchCommands]);
                        // Log::channel('telegram')->info('HOOK BATCH batchFlow', ['batchCommands' => $resultBatchCommands]);
                        // BitrixDealService::updateDeal(  //обновляю сделку - а теперь даже нет, будет создана batch command
                        //     $hook,
                        //     $currentDealId,
                        //     $fieldsData,

                        // );

                        //но текущую сделку обновляю руками: mutation
                        $curbtxDeal['CATEGORY_ID'] = $currentCategoryData['bitrixId'];
                        $curbtxDeal['STAGE_ID'] = "C" . $currentCategoryData['bitrixId'] . ':' . $targetStageBtxId;
                    }
                }

                // array_push($currentDealIds, $currentDealId); //пушаться для каждой категории если был id - то обновляется а в результат закидывается пришедший id 
                // batch комманда для update  должна содержать в ключе id deal
                // так как в результе будет просто тру
                // new pres deal буду создавать не batchem а как и было
            }
        }
        // $batchService =  new BitrixBatchService($hook);
        // $result = $batchService->sendBatch($batchCommands);
        // Log::info('HOOK BATCH batchFlow', ['result' => $result]);
        // Log::channel('telegram')->info('HOOK BATCH batchFlow', ['result' => $result]);


        return ['dealIds' => ['$result'], 'newPresDeal' => $newPresDeal, 'commands' => $resultBatchCommands];
    }

    static function getBatchCommand(
        $fieldsData,
        $method, //update | add
        $dealId
    ) {

        $currentMethod = 'crm.deal.' . $method;
        $data = ['FIELDS' => $fieldsData];
        if (!empty($dealId)) {
            $data['ID'] = $dealId;
        }

        return $currentMethod . '?' . http_build_query($data);
    }

    static function cleanBatchCommands($batchCommands, $portalDealData)
    {
        $reportDeals = [];
        $planDeals = [];
        $unplannedPresDeals = [];
        $newPresDeal = null;
        $groupped = [];
        // Логирование результатов обработки
        // Log::info('HOOK BATCH handleBatchResults', ['batchResult' => $batchResult]);
        // Log::channel('telegram')->info('HOOK BATCH batchFlow', ['batchResult' => $batchResult]);
        Log::channel('telegram')->info('HOOK cleanBatchCommands', ['batchCommands' => $batchCommands]);
        // [
        // {"update_unpres_sales_base_7267_PRESENTATION":true,
        // "update_unpres_sales_presentation_7271_WON":true,
        // "update_report_sales_xo_7269_WON":true,
        // "update_plan_sales_base_7267_WARM":true}
        // ]}

        //перебираем комманды находим те что ч одинаковым dealId

        // Извлечение результатов
        $results = $batchCommands;  // Предполагаем, что структура такая, как в примере
        foreach ($results as $key => $value) { // value в данном случае сделка, точнее ее поля для обновления
            $parts = explode('_', $key);
            $operation = $parts[0];  // 'update' или 'set'
            $tag = $parts[1];        // 'report' или 'plan'
            $category = $parts[2] . '_' . $parts[3];  // Категория всегда состоит из двух слов

            if ($operation === 'set') {
                // Для 'set', значение представляет собой ID новой сделки
                $dealId = $value;
                if ($tag === 'report') {
                    $reportDeals[] = $dealId;  // Добавляем ID в массив reportDeals
                } elseif ($tag === 'plan') {
                    if ($category  == 'sales_presentation') {
                        $newPresDeal = $dealId;  // Добавляем ID в массив planDeals

                    }
                    $planDeals[] = $dealId;  // Добавляем ID в массив planDeals


                }
            } else if ($operation === 'update') {
                // Для 'update', ID сделки присутствует в последнем элементе ключа
                $dealId = $parts[4];
                $targetStageBtxId = $parts[5];
                // Log::channel('telegram')->info('HOOK cleanBatchCommands', ['result' => $targetStageBtxId]);
                $groupped[$dealId][] = [
                    'category' => $category,
                    'stage' => $targetStageBtxId,
                ];

                Log::channel('telegram')->info('HOOK cleanBatchCommands', ['groupped' => $groupped]);


                if ($tag === 'report') {
                    $reportDeals[] = $dealId;  // Добавляем ID в массив reportDeals
                } elseif ($tag === 'plan') {
                    $planDeals[] = $dealId;  // Добавляем ID в массив planDeals
                }
            }
        }



        // groupped":{"7297":
        //     [
        // {"category":"sales_base","stage":"PRESENTATION"},
        //     {"category":"sales_base","stage":"PRESENTATION"}
        // ],
        // "7303":[
        //     {"category":"sales_presentation","stage":"WON"}
        //     ]}}
        if (!empty($portalDealData['categories'])) {

            foreach ($portalDealData['categories'] as $category) {
                foreach ($groupped as $dealId => $processes) {

                    $isCurrentSearched = false;

                    foreach ($processes as $process) {
                        // Log::channel('telegram')->info('HOOK processesss', ['process' => $process]);
                        $isProcessNeedUpdate = false;

                        if ($category['code'] === $process['category']) {

                            foreach ($category['stages'] as $stage) {
                                if ($stage['bitrixId'] === $process['stage']) {
                                    // Log::channel('telegram')->info('HOOK process stage', ['process stage' => $stage]);
                                    if ($isCurrentSearched == true) {
                                        $isProcessNeedUpdate = true;
                                    }
                                    $isCurrentSearched = true;
                                }
                            }
                        }
                        $process['category']['isNeedUpdate'] = $isProcessNeedUpdate;
                    }
                }
            }
        }
        Log::channel('telegram')->info('HOOK RESULT process', ['process' => $process]);


        return $batchCommands;
        // return [
        //     'reportDeals' => $reportDeals,
        //     'planDeals' => $planDeals,
        //     // 'unplannedPresDeals' => $unplannedPresDeals,  // Раскомментируйте, если нужно использовать,
        //     'newPresDeal' =>  $newPresDeal
        // ];
    }

    static function handleBatchResults($batchResult)
    {
        $reportDeals = [];
        $planDeals = [];
        $unplannedPresDeals = [];
        $newPresDeal = null;
        // Логирование результатов обработки
        // Log::info('HOOK BATCH handleBatchResults', ['batchResult' => $batchResult]);
        // Log::channel('telegram')->info('HOOK BATCH batchFlow', ['batchResult' => $batchResult]);

        // Извлечение результатов
        $results = $batchResult[0];  // Предполагаем, что структура такая, как в примере
        foreach ($results as $key => $value) {
            $parts = explode('_', $key);
            $operation = $parts[0];  // 'update' или 'set'
            $tag = $parts[1];        // 'report' или 'plan'
            $category = $parts[2] . '_' . $parts[3];  // Категория всегда состоит из двух слов

            if ($operation === 'set') {
                // Для 'set', значение представляет собой ID новой сделки
                $dealId = $value;
                if ($tag === 'report') {
                    $reportDeals[] = $dealId;  // Добавляем ID в массив reportDeals
                } elseif ($tag === 'plan') {
                    if ($category  == 'sales_presentation') {
                        $newPresDeal = $dealId;  // Добавляем ID в массив planDeals

                    }
                    $planDeals[] = $dealId;  // Добавляем ID в массив planDeals


                }
            } else if ($operation === 'update') {
                // Для 'update', ID сделки присутствует в последнем элементе ключа
                $dealId = $parts[4];
                if ($tag === 'report') {
                    $reportDeals[] = $dealId;  // Добавляем ID в массив reportDeals
                } elseif ($tag === 'plan') {
                    $planDeals[] = $dealId;  // Добавляем ID в массив planDeals
                }
            }
        }

        return [
            'reportDeals' => $reportDeals,
            'planDeals' => $planDeals,
            // 'unplannedPresDeals' => $unplannedPresDeals,  // Раскомментируйте, если нужно использовать,
            'newPresDeal' =>  $newPresDeal
        ];
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
