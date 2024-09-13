<?php

namespace App\Services\HookFlow;

use App\Services\General\BitrixListService;
use DateTime;
use Illuminate\Support\Facades\Log;

class BitrixListFlowService



{

    protected $currentDepartamentType = null;
    protected $withLists = false;
    protected $bitrixLists = [];



    public function __construct() {}


    //lists flow

    static function getListsFlow(
        $hook,
        $bitrixLists,
        $eventType, // xo warm presentation, offer invoice
        $eventTypeName, //звонок по решению по оплате
        $eventAction,  // plan done //если будет репорт и при этом не было переноса придет done или nodone - типа состоялся или нет
        // $eventName,
        $deadline,
        $created,
        $responsible,
        $suresponsible,
        $companyId,
        $comment,
        $workStatus, //inJob
        $resultStatus,  // result noresult   .. without expired new !
        $noresultReason,
        $failReason,
        $failType,
        $dealIds,
        $currentBaseDealId,
        $nowDate = null,
        $hotName = null

    ) {
        try {



            if (empty($nowDate)) {
                date_default_timezone_set('Europe/Moscow');
                $currentNowDate = new DateTime();
                $nowDate = $currentNowDate->format('d.m.Y H:i:s');
            }

            $eventActionName = 'Запланирован';
            $evTypeName = 'Звонок';
            $nextCommunication = $deadline;
            $isUniqPresPlan = false;
            $isUniqPresReport = false;


            $crmValue = ['n0' => 'CO_' . $companyId];

            if (!empty($dealIds)) {

                foreach ($dealIds as $key => $dealId) {
                    $crmValue['n' . $key + 1] = 'D_' . $dealId;
                }
            }


            if ($eventType == 'xo' || $eventType == 'cold') {
                $evTypeName = 'Холодный звонок';
            } else if ($eventType == 'warm' || $eventType == 'call') {
                $evTypeName = 'Звонок';
            } else if ($eventType == 'presentation') {
                $evTypeName = 'Презентация';
                $eventActionName = 'Запланирована';
            } else if ($eventType == 'hot' || $eventType == 'inProgress' || $eventType == 'in_progress') {
                $evTypeName = 'Звонок по решению';
            } else if ($eventType == 'money' || $eventType == 'moneyAwait' || $eventType == 'money_await') {
                $evTypeName = 'Звонок по оплате';
            }






            if ($eventAction == 'expired') {
                $eventAction = 'pound';
                $eventActionName = 'Перенос';
            } else    if ($eventAction == 'done') {

                $eventActionName = 'Состоялся';
                if ($eventType == 'presentation') {
                    $eventActionName = 'Состоялась';

                    $isUniqPresReport = true;
                }
            } else    if ($eventAction == 'plan') {


                if ($eventType == 'presentation') {

                    $isUniqPresPlan = true;
                }
            } else    if ($eventAction == 'nodone') {
                $nextCommunication = null;
                $eventActionName = 'Не Состоялся: отказ';
                $eventAction = 'act_noresult_fail';


                if ($eventType == 'presentation') {
                    $eventActionName = 'Не Состоялась: отказ';
                }
            }

            if ($eventAction  !== 'plan') {
                if ($workStatus['code'] !== 'inJob' && $workStatus['code'] !== 'setAside') {
                    $nextCommunication = null;
                }
            }
            // Log::channel('telegram')->info('HOOK TST', ['eventAction' => $eventAction]);
            if (empty($hotName)) {

                $hotName = $evTypeName . ' ' . $eventActionName;
            }
            $xoFields = [
                [
                    'code' => 'event_date',
                    'name' => 'Дата',
                    'value' => $nowDate,
                ],
                // [
                //     'code' => 'name',
                //     'name' => 'Название',
                //     'value' => $evTypeName . ' ' . $eventActionName
                // ],
                [
                    'code' => 'event_title',
                    'name' => 'Название',
                    'value' => $hotName
                ],
                [
                    'code' => 'plan_date',
                    'name' => 'Дата Следующей коммуникации',
                    'value' => $nextCommunication
                ],
                [
                    'code' => 'author',
                    'name' => 'Автор',
                    'value' => $created,
                ],
                [
                    'code' => 'responsible',
                    'name' => 'Ответственный',
                    'value' => $responsible,
                ],
                [
                    'code' => 'su',
                    'name' => 'Соисполнитель',
                    'value' => $suresponsible,
                ],
                [
                    'code' => 'crm',
                    'name' => 'crm',
                    'value' => $crmValue
                ],
                [
                    'code' => 'crm_company',
                    'name' => 'crm_company',
                    'value' => ['n0' => 'CO_' . $companyId],
                ],

                [
                    'code' => 'manager_comment',
                    'name' => 'Комментарий',
                    'value' => $comment,
                ],
                [
                    'code' => 'event_type',
                    'name' => 'Тип События',
                    'list' =>  [
                        'code'  => BitrixListFlowService::getEventType(
                            $eventType
                        ),
                        'name' => $eventTypeName,

                    ],
                ],
                [
                    'code' => 'event_action',
                    'name' => 'Событие Действие',
                    'list' =>  [
                        'code' => $eventAction,
                        // 'name' => $eventActionName //Запланирован/на
                    ],
                ],

                [
                    'code' => 'op_work_status',
                    'name' => 'Статус Работы',
                    'list' =>  [
                        'code' => BitrixListFlowService::getCurrentWorkStatusCode(
                            $workStatus,
                            $eventType
                        ),  //'in_work',
                        // 'name' =>  'В работе' //'В работе'
                    ],
                ],
                [
                    'code' => 'op_result_status',
                    'name' => 'Результативность',
                    'list' =>  [
                        'code' => BitrixListFlowService::getResultStatus(
                            $resultStatus,
                        ),  //'in_work',
                        // 'name' =>  'В работе' //'В работе'
                    ],
                ],


            ];


            if ($resultStatus !== 'result' && $resultStatus !== 'new') {
                if (!empty($noresultReason)) {
                    if (!empty($noresultReason['code'])) {
                        $noresultReasoneItem = [
                            'code' => 'op_noresult_reason',
                            'name' => 'Тип Нерезультативности',
                            'list' =>  [
                                'code' => $noresultReason['code'],
                                // 'name' =>  'В работе' //'В работе'
                            ],
                        ];
                        array_push($xoFields, $noresultReasoneItem);
                    }
                }
            } else {
                $noresultReasoneItem = [
                    'code' => 'op_noresult_reason',
                    'name' => 'Тип Нерезультативности',
                    'list' =>  [
                        'code' => null,
                        // 'name' =>  'В работе' //'В работе'
                    ],
                ];
                array_push($xoFields, $noresultReasoneItem);
            }
            if (!empty($workStatus)) {
                if (!empty($workStatus['code'])) {
                    $workStatusCode = $workStatus['code'];


                    if ($workStatusCode === 'fail') {  //если провал
                        if (!empty($failType)) {
                            if (!empty($failType['code'])) {
                                $failTypeItemItem = [
                                    'code' => 'op_prospects_type',
                                    'name' => 'Перспективность',
                                    'list' =>  [
                                        'code' => BitrixListFlowService::getPerspectStatus(
                                            $failType['code']
                                        ),
                                    ],
                                ];
                                array_push($xoFields, $failTypeItemItem);



                                if ($failType['code'] == 'failure') { //если тип провала - отказ
                                    if (!empty($failReason)) {
                                        if (!empty($failReason['code'])) {
                                            $failReasonItem = [
                                                'code' => 'op_fail_reason',
                                                'name' => 'ОП Причина Отказа',
                                                'list' =>  [
                                                    'code' => BitrixListFlowService::getFailType(
                                                        $failReason['code']
                                                    ),
                                                ],
                                            ];
                                            array_push($xoFields, $failReasonItem);
                                        }
                                    }
                                }
                            }
                        }
                    } else {

                        // если не отказ - перспективная
                        $failTypeItemItem = [
                            'code' => 'op_prospects_type',
                            'name' => 'Перспективность',
                            'list' =>  [
                                'code' => 'op_prospects_good',
                                // 'name' =>  'В работе' //'В работе'
                            ],
                        ];
                        array_push($xoFields, $failTypeItemItem);
                    }
                }
            }
            $fieldsData = [
                'NAME' => $hotName
            ];

            foreach ($bitrixLists as $bitrixList) {


                if ($bitrixList['type'] === 'history') {

                    foreach ($xoFields as $xoValue) {
                        $currentDataField = [];
                        $fieldCode = $bitrixList['group'] . '_' . $bitrixList['type'] . '_' . $xoValue['code'];
                        $btxId = BitrixListFlowService::getBtxListCurrentData($bitrixList, $fieldCode, null);
                        if (!empty($xoValue)) {



                            if (!empty($xoValue['value'])) {
                                $fieldsData[$btxId] = $xoValue['value'];
                                $currentDataField[$btxId] = $xoValue['value'];
                            }

                            if (!empty($xoValue['list'])) {
                                $btxItemId = BitrixListFlowService::getBtxListCurrentData($bitrixList, $fieldCode, $xoValue['list']['code']);
                                $currentDataField[$btxId] = [

                                    $btxItemId =>  $xoValue['list']['code']
                                ];

                                $fieldsData[$btxId] =  $btxItemId;
                            }
                        }
                        // array_push($fieldsData, $currentDataField);
                    }
                    sleep(1);
                    Log::info('HOOK LIST', ['data' => $fieldsData]);
                    BitrixListService::setItem(
                        $hook,
                        $bitrixList['bitrixId'],
                        $fieldsData
                    );
                }
            }

            /**
             * KPI DOUBLE
             */
            foreach ($bitrixLists as $bitrixList) {
                if ($bitrixList['type'] === 'kpi') {

                    foreach ($xoFields as $xoValue) {
                        $currentDataField = [];
                        $fieldCode = $bitrixList['group'] . '_' . $bitrixList['type'] . '_' . $xoValue['code'];
                        $btxId = BitrixListFlowService::getBtxListCurrentData($bitrixList, $fieldCode, null);
                        if (!empty($xoValue)) {



                            if (!empty($xoValue['value'])) {
                                $fieldsData[$btxId] = $xoValue['value'];
                                $currentDataField[$btxId] = $xoValue['value'];
                            }

                            if (!empty($xoValue['list'])) {
                                $btxItemId = BitrixListFlowService::getBtxListCurrentData($bitrixList, $fieldCode, $xoValue['list']['code']);
                                $currentDataField[$btxId] = [

                                    $btxItemId =>  $xoValue['list']['code']
                                ];

                                $fieldsData[$btxId] =  $btxItemId;
                            }
                        }
                        // array_push($fieldsData, $currentDataField);
                    }
                    sleep(5);
                    Log::info('HOOK LIST', ['data' => $fieldsData]);
                    BitrixListService::setItem(
                        $hook,
                        $bitrixList['bitrixId'],
                        $fieldsData
                    );
                }
            }

            //for uniq pres
            if ($resultStatus === 'result' || $resultStatus === 'new') {

                if ($isUniqPresPlan || $isUniqPresReport) {
                    $xoFields[9]['list']['code'] = 'presentation_uniq';

                    if ($isUniqPresPlan) {

                        $code = $companyId . '_' . $currentBaseDealId . '_plan';
                    }

                    if ($isUniqPresReport) {
                        $code = $companyId . '_' . $currentBaseDealId . '_done';
                    }

                    foreach ($bitrixLists as $bitrixList) {
                        if ($bitrixList['type'] === 'kpi') {

                            foreach ($xoFields as $xoValue) {
                                $currentDataField = [];
                                $fieldCode = $bitrixList['group'] . '_' . $bitrixList['type'] . '_' . $xoValue['code'];
                                $btxId = BitrixListFlowService::getBtxListCurrentData($bitrixList, $fieldCode, null);
                                if (!empty($xoValue)) {



                                    if (!empty($xoValue['value'])) {
                                        $fieldsData[$btxId] = $xoValue['value'];
                                        $currentDataField[$btxId] = $xoValue['value'];
                                    }

                                    if (!empty($xoValue['list'])) {
                                        $btxItemId = BitrixListFlowService::getBtxListCurrentData($bitrixList, $fieldCode, $xoValue['list']['code']);
                                        $currentDataField[$btxId] = [

                                            $btxItemId =>  $xoValue['list']['code']
                                        ];

                                        $fieldsData[$btxId] =  $btxItemId;
                                    }
                                }
                                // array_push($fieldsData, $currentDataField);
                            }
                            sleep(1);
                            BitrixListService::setItem(
                                $hook,
                                $bitrixList['bitrixId'],
                                $fieldsData,
                                $code
                            );
                        }
                    }
                }
            }
        } catch (\Throwable $th) {
            $errorMessages =  [
                'message'   => $th->getMessage(),
                'file'      => $th->getFile(),
                'line'      => $th->getLine(),
                'trace'     => $th->getTraceAsString(),
            ];
            Log::error('ERROR COLD: getListsFlow',  $errorMessages);

            Log::channel('telegram')->error('APRIL_HOOK getListsFlow', $errorMessages);
        }
    }

    static function getBatchListFlow(
        $hook,
        $bitrixLists,
        $eventType, // xo warm presentation, offer invoice
        $eventTypeName, //звонок по решению по оплате
        $eventAction,  // plan done //если будет репорт и при этом не было переноса придет done или nodone - типа состоялся или нет
        // $eventName,
        $deadline,
        $created,
        $responsible,
        $suresponsible,
        $companyId,
        $comment,
        $workStatus, //inJob
        $resultStatus,  // result noresult   .. without expired new !
        $noresultReason,
        $failReason,
        $failType,
        $dealIds,
        $currentBaseDealId,
        $nowDate = null,
        $hotName = null,
        $resultBatchCommands // = []

    ) {
        try {



            if (empty($nowDate)) {
                date_default_timezone_set('Europe/Moscow');
                $currentNowDate = new DateTime();
                $nowDate = $currentNowDate->format('d.m.Y H:i:s');
            }

            $eventActionName = 'Запланирован';
            $evTypeName = 'Звонок';
            $nextCommunication = $deadline;
            $isUniqPresPlan = false;
            $isUniqPresReport = false;


            $crmValue = ['n0' => 'CO_' . $companyId];

            if (!empty($dealIds)) {

                foreach ($dealIds as $key => $dealId) {
                    $crmValue['n' . $key + 1] = 'D_' . $dealId;
                }
            }


            if ($eventType == 'xo' || $eventType == 'cold') {
                $evTypeName = 'Холодный звонок';
            } else if ($eventType == 'warm' || $eventType == 'call') {
                $evTypeName = 'Звонок';
            } else if ($eventType == 'presentation') {
                $evTypeName = 'Презентация';
                $eventActionName = 'Запланирована';
            } else if ($eventType == 'hot' || $eventType == 'inProgress' || $eventType == 'in_progress') {
                $evTypeName = 'Звонок по решению';
            } else if ($eventType == 'money' || $eventType == 'moneyAwait' || $eventType == 'money_await') {
                $evTypeName = 'Звонок по оплате';
            }






            if ($eventAction == 'expired') {
                $eventAction = 'pound';
                $eventActionName = 'Перенос';
            } else    if ($eventAction == 'done') {

                $eventActionName = 'Состоялся';
                if ($eventType == 'presentation') {
                    $eventActionName = 'Состоялась';

                    $isUniqPresReport = true;
                }
            } else    if ($eventAction == 'plan') {


                if ($eventType == 'presentation') {

                    $isUniqPresPlan = true;
                }
            } else    if ($eventAction == 'nodone') {
                $nextCommunication = null;
                $eventActionName = 'Не Состоялся: отказ';
                $eventAction = 'act_noresult_fail';


                if ($eventType == 'presentation') {
                    $eventActionName = 'Не Состоялась: отказ';
                }
            }

            if ($eventAction  !== 'plan') {
                if ($workStatus['code'] !== 'inJob' && $workStatus['code'] !== 'setAside') {
                    $nextCommunication = null;
                }
            }
            // Log::channel('telegram')->info('HOOK TST', ['eventAction' => $eventAction]);
            if (empty($hotName)) {

                $hotName = $evTypeName . ' ' . $eventActionName;
            }
            $xoFields = [
                [
                    'code' => 'event_date',
                    'name' => 'Дата',
                    'value' => $nowDate,
                ],
                // [
                //     'code' => 'name',
                //     'name' => 'Название',
                //     'value' => $evTypeName . ' ' . $eventActionName
                // ],
                [
                    'code' => 'event_title',
                    'name' => 'Название',
                    'value' => $hotName
                ],
                [
                    'code' => 'plan_date',
                    'name' => 'Дата Следующей коммуникации',
                    'value' => $nextCommunication
                ],
                [
                    'code' => 'author',
                    'name' => 'Автор',
                    'value' => $created,
                ],
                [
                    'code' => 'responsible',
                    'name' => 'Ответственный',
                    'value' => $responsible,
                ],
                [
                    'code' => 'su',
                    'name' => 'Соисполнитель',
                    'value' => $suresponsible,
                ],
                [
                    'code' => 'crm',
                    'name' => 'crm',
                    'value' => $crmValue
                ],
                [
                    'code' => 'crm_company',
                    'name' => 'crm_company',
                    'value' => ['n0' => 'CO_' . $companyId],
                ],

                [
                    'code' => 'manager_comment',
                    'name' => 'Комментарий',
                    'value' => $comment,
                ],
                [
                    'code' => 'event_type',
                    'name' => 'Тип События',
                    'list' =>  [
                        'code'  => BitrixListFlowService::getEventType(
                            $eventType
                        ),
                        'name' => $eventTypeName,

                    ],
                ],
                [
                    'code' => 'event_action',
                    'name' => 'Событие Действие',
                    'list' =>  [
                        'code' => $eventAction,
                        // 'name' => $eventActionName //Запланирован/на
                    ],
                ],

                [
                    'code' => 'op_work_status',
                    'name' => 'Статус Работы',
                    'list' =>  [
                        'code' => BitrixListFlowService::getCurrentWorkStatusCode(
                            $workStatus,
                            $eventType
                        ),  //'in_work',
                        // 'name' =>  'В работе' //'В работе'
                    ],
                ],
                [
                    'code' => 'op_result_status',
                    'name' => 'Результативность',
                    'list' =>  [
                        'code' => BitrixListFlowService::getResultStatus(
                            $resultStatus,
                        ),  //'in_work',
                        // 'name' =>  'В работе' //'В работе'
                    ],
                ],


            ];


            if ($resultStatus !== 'result' && $resultStatus !== 'new') {
                if (!empty($noresultReason)) {
                    if (!empty($noresultReason['code'])) {
                        $noresultReasoneItem = [
                            'code' => 'op_noresult_reason',
                            'name' => 'Тип Нерезультативности',
                            'list' =>  [
                                'code' => $noresultReason['code'],
                                // 'name' =>  'В работе' //'В работе'
                            ],
                        ];
                        array_push($xoFields, $noresultReasoneItem);
                    }
                }
            } else {
                $noresultReasoneItem = [
                    'code' => 'op_noresult_reason',
                    'name' => 'Тип Нерезультативности',
                    'list' =>  [
                        'code' => null,
                        // 'name' =>  'В работе' //'В работе'
                    ],
                ];
                array_push($xoFields, $noresultReasoneItem);
            }
            if (!empty($workStatus)) {
                if (!empty($workStatus['code'])) {
                    $workStatusCode = $workStatus['code'];


                    if ($workStatusCode === 'fail') {  //если провал
                        if (!empty($failType)) {
                            if (!empty($failType['code'])) {
                                $failTypeItemItem = [
                                    'code' => 'op_prospects_type',
                                    'name' => 'Перспективность',
                                    'list' =>  [
                                        'code' => BitrixListFlowService::getPerspectStatus(
                                            $failType['code']
                                        ),
                                    ],
                                ];
                                array_push($xoFields, $failTypeItemItem);



                                if ($failType['code'] == 'failure') { //если тип провала - отказ
                                    if (!empty($failReason)) {
                                        if (!empty($failReason['code'])) {
                                            $failReasonItem = [
                                                'code' => 'op_fail_reason',
                                                'name' => 'ОП Причина Отказа',
                                                'list' =>  [
                                                    'code' => BitrixListFlowService::getFailType(
                                                        $failReason['code']
                                                    ),
                                                ],
                                            ];
                                            array_push($xoFields, $failReasonItem);
                                        }
                                    }
                                }
                            }
                        }
                    } else {

                        // если не отказ - перспективная
                        $failTypeItemItem = [
                            'code' => 'op_prospects_type',
                            'name' => 'Перспективность',
                            'list' =>  [
                                'code' => 'op_prospects_good',
                                // 'name' =>  'В работе' //'В работе'
                            ],
                        ];
                        array_push($xoFields, $failTypeItemItem);
                    }
                }
            }
            $fieldsData = [
                'NAME' => $hotName
            ];

            foreach ($bitrixLists as $bitrixList) {
                if ($bitrixList['type'] !== 'presentation') {

                    foreach ($xoFields as $xoValue) {
                        $currentDataField = [];
                        $fieldCode = $bitrixList['group'] . '_' . $bitrixList['type'] . '_' . $xoValue['code'];
                        $btxId = BitrixListFlowService::getBtxListCurrentData($bitrixList, $fieldCode, null);
                        if (!empty($xoValue)) {



                            if (!empty($xoValue['value'])) {
                                $fieldsData[$btxId] = $xoValue['value'];
                                $currentDataField[$btxId] = $xoValue['value'];
                            }

                            if (!empty($xoValue['list'])) {
                                $btxItemId = BitrixListFlowService::getBtxListCurrentData($bitrixList, $fieldCode, $xoValue['list']['code']);
                                $currentDataField[$btxId] = [

                                    $btxItemId =>  $xoValue['list']['code']
                                ];

                                $fieldsData[$btxId] =  $btxItemId;
                            }
                        }
                        // array_push($fieldsData, $currentDataField);
                    }
                    $uniqueHash = md5(uniqid(rand(), true));
                    $code = $uniqueHash;
                    $uniqueSecondHash = md5(uniqid(rand(), true));
                    $fullCode = $bitrixList['type'] . '_' . $companyId . '_' . $code;
                    $command =  BitrixListService::getBatchCommandSetItem(
                        $hook,
                        $bitrixList['bitrixId'],
                        $fieldsData,
                        $fullCode
                    );
                    $resultBatchCommands['set_list_item_' . $fullCode] = $command;
                }
            }



            //for uniq pres
            if ($resultStatus === 'result' || $resultStatus === 'new') {

                if ($isUniqPresPlan || $isUniqPresReport) {
                    $xoFields[9]['list']['code'] = 'presentation_uniq';

                    if ($isUniqPresPlan) {

                        $code = $companyId . '_' . $currentBaseDealId . '_plan';
                    }

                    if ($isUniqPresReport) {
                        $code = $companyId . '_' . $currentBaseDealId . '_done';
                    }

                    foreach ($bitrixLists as $bitrixList) {
                        if ($bitrixList['type'] === 'kpi') {

                            foreach ($xoFields as $xoValue) {
                                $currentDataField = [];
                                $fieldCode = $bitrixList['group'] . '_' . $bitrixList['type'] . '_' . $xoValue['code'];
                                $btxId = BitrixListFlowService::getBtxListCurrentData($bitrixList, $fieldCode, null);
                                if (!empty($xoValue)) {



                                    if (!empty($xoValue['value'])) {
                                        $fieldsData[$btxId] = $xoValue['value'];
                                        $currentDataField[$btxId] = $xoValue['value'];
                                    }

                                    if (!empty($xoValue['list'])) {
                                        $btxItemId = BitrixListFlowService::getBtxListCurrentData($bitrixList, $fieldCode, $xoValue['list']['code']);
                                        $currentDataField[$btxId] = [

                                            $btxItemId =>  $xoValue['list']['code']
                                        ];

                                        $fieldsData[$btxId] =  $btxItemId;
                                    }
                                }
                                // array_push($fieldsData, $currentDataField);
                            }


                            $command =  BitrixListService::getBatchCommandSetItem(
                                $hook,
                                $bitrixList['bitrixId'],
                                $fieldsData,
                                $code
                            );
                            $resultBatchCommands['set_list_item_' . $code] = $command;
                            // print_r("<br>");
                            // print_r("<resultBatchCommands bxlflowservice>");
                            // print_r("<br>");
                            // print_r($resultBatchCommands);
                        }
                    }
                }
            }

            return $resultBatchCommands;
        } catch (\Throwable $th) {
            $errorMessages =  [
                'message'   => $th->getMessage(),
                'file'      => $th->getFile(),
                'line'      => $th->getLine(),
                'trace'     => $th->getTraceAsString(),
            ];
            Log::error('ERROR COLD: getListsFlow',  $errorMessages);

            Log::channel('telegram')->error('APRIL_HOOK getListsFlow', $errorMessages);
        }
    }

    static function getBtxListCurrentData(
        $bitrixList,
        $code,
        $listCode
    ) {
        $result = [
            'fieldBtxId' => false,
            'fieldItemBtxId' => false,
        ];
        if (!empty($bitrixList)) { //every from portal


            if (!empty($bitrixList['bitrixfields'])) {

                $btxFields = $bitrixList['bitrixfields'];
                foreach ($btxFields as $btxField) {



                    if ($btxField['code'] === $code) {
                        $result['fieldBtxId'] = $btxField['bitrixCamelId'];
                    }
                    if (!empty($btxField['items'])) {




                        $btxFieldItems = $btxField['items'];



                        foreach ($btxFieldItems as $btxFieldItem) {

                            if ($listCode) {
                                if ($listCode == 'op_status_in_work' || $listCode == 'in_work') {
                                }

                                if ($btxFieldItem['code'] === $listCode) {
                                    $result['fieldItemBtxId'] = $btxFieldItem['bitrixId'];
                                }
                            }
                        }
                    }
                }
            }
        }

        if (!$listCode) {
            return $result['fieldBtxId'];
        } else {
            return $result['fieldItemBtxId'];
        }
    }

    static function  getEventType(
        $eventType, // xo warm presentation, offer invoice
    ) {
        // Холодный звонок	event_type	xo
        // Звонок	event_type	call
        // Презентация	event_type	presentation
        // Информация	event_type	info
        // Приглашение на семинар	event_type	seminar
        // Звонок по решению	event_type	call_in_progress
        // Звонок по оплате	event_type	call_in_money
        // Входящий звонок	event_type	come_call
        // Заявка с сайта	event_type	site

        $result = 'xo';
        if ($eventType === 'call' || $eventType === 'warm') {
            $result = 'call';
        } else if ($eventType === 'presentation') {
            $result = 'presentation';
        } else if ($eventType === 'hot' || $eventType === 'inProgress' || $eventType === 'in_progress') {
            $result = 'call_in_progress';
        } else if ($eventType === 'moneyAwait' || $eventType === 'money_await' || $eventType === 'money') {
            $result = 'call_in_money';
        }


        return $result;
    }


    static function getCurrentWorkStatusCode(
        $workStatus,
        $currentEventType,

        // 0: {id: 1, code: "warm", name: "Звонок"}
        // // 1: {id: 2, code: "presentation", name: "Презентация"}
        // // 2: {id: 3, code: "hot", name: "Решение"}
        // // 3: {id: 4, code: "moneyAwait", name: "Оплата"}

    ) {
        $resultCode = 'in_work';
        // В работе	op_work_status	op_status_in_work
        // На долгий период	op_work_status	op_status_in_long
        // Продажа	op_work_status	op_status_success
        // В решении	op_work_status	op_status_in_progress
        // В оплате	op_work_status	op_status_money_await
        // Отказ	op_work_status	op_status_fail


        // 0: {id: 0, code: "inJob", name: "В работе"} in_long
        // 1: {id: 1, code: "setAside", name: "Отложено"}
        // 2: {id: 2, code: "success", name: "Продажа"}
        // 3: {id: 3, code: "fail", name: "Отказ"}
        if (!empty($workStatus)) {
            if (!empty($workStatus['code'])) {
                $code = $workStatus['code'];
                switch ($code) {
                    case 'inJob':
                        $resultCode = 'op_status_in_work';

                        if ($currentEventType == 'hot') {
                            $resultCode = 'op_status_in_progress';
                        } else  if ($currentEventType == 'moneyAwait') {
                            $resultCode = 'op_status_money_await';
                        }


                        break;
                    case 'setAside': //in_long
                        $resultCode = 'op_status_in_long';
                        break;
                    case 'fail':
                        $resultCode = 'op_status_fail';
                        break;
                    case 'success':
                        $resultCode = 'op_status_success';
                        break;
                    default:
                        break;
                }
            }
        }

        return $resultCode;
    }

    static function  getResultStatus($resultStatus)
    {

        $result = 'op_call_result_yes';
        if ($resultStatus !== 'result') {
            $result = 'op_call_result_no';
        }


        return $result;
    }


    static function  getPerspectStatus(
        $failTypeCode
    ) {

        $result = 'op_prospects_good';

        switch ($failTypeCode) {
            case 'op_prospects_good':
            case 'op_prospects_nopersp':
            case 'op_prospects_nophone':
            case 'op_prospects_company':
                $result = $failTypeCode;
                break;
            case 'garant':
            case 'go':
            case 'territory':
            case 'autsorc':
            case 'depend':

                $result = 'op_prospects_' . $failTypeCode;
                break;
            case 'accountant':
                $result = 'op_prospects_acountant';
                break;

            case 'failure':
                $result = 'op_prospects_fail';
                break;

            default:
                # code...
                break;
        }


        return $result;
    }


    static function  getNoResultReasone($resultStatus, $noresultReason)
    {
        // Секретарь 	op_noresult_reason	secretar
        // Недозвон - трубку не берут	op_noresult_reason	nopickup
        // Недозвон - номер не существует	op_noresult_reason	nonumber
        // Занято 	op_noresult_reason	busy
        // Перенос - не было времени	op_noresult_reason	noresult_notime
        // Контактера нет на месте	op_noresult_reason	nocontact
        // Просят оставить свой номер	op_noresult_reason	giveup
        // Не интересует, до свидания	op_noresult_reason	bay
        // По телефону отвечает не та организация	op_noresult_reason	wrong
        // Автоответчик	op_noresult_reason	auto
        $result = 'yes';
        if ($resultStatus !== 'result') {
            $result = 'no';
        }
        // Log::channel('telegram')->info('op_noresult_reason', [
        //     'resultStatus' => $resultStatus,


        // ]);

        if (!empty($noresultReason)) {
            if (!empty($noresultReason['code'])) {
                $code = $noresultReason['code'];
            }
        }
        return $result;
    }



    static function  getFailType(
        $failReason
    ) {
        // не было времени	op_fail_reason	fail_notime
        // конкуренты - привыкли	op_fail_reason	c_habit
        // конкуренты - оплачено	op_fail_reason	c_prepay
        // конкуренты - цена	op_fail_reason	c_price
        // слишком дорого	op_fail_reason	to_expensive
        // слишком дешево	op_fail_reason	to_cheap
        // нет денег	op_fail_reason	nomoney
        // не видят надобности	op_fail_reason	noneed
        // лпр против	op_fail_reason	lpr
        // ключевой сотрудник против	op_fail_reason	employee


        return $failReason;
    }

    static function  getFailReasone($resultStatus)
    {
        // не было времени	op_fail_reason	fail_notime
        // конкуренты - привыкли	op_fail_reason	c_habit
        // конкуренты - оплачено	op_fail_reason	c_prepay
        // конкуренты - цена	op_fail_reason	c_price
        // слишком дорого	op_fail_reason	to_expensive
        // слишком дешево	op_fail_reason	to_cheap
        // нет денег	op_fail_reason	nomoney
        // не видят надобности	op_fail_reason	noneed
        // лпр против	op_fail_reason	lpr
        // ключевой сотрудник против	op_fail_reason	employee


        $result = 'yes';
        if ($resultStatus !== 'result') {
            $result = 'no';
        }


        return $result;
    }
    // protected function getCurrentWorkStatusCode($isFail, $isSuccess)
    // {
    //     // В работе	op_work_status	op_status_in_work || in_work
    //     // На долгий период	op_work_status	op_status_in_long
    //     // Продажа	op_work_status	op_status_success
    //     // В решении	op_work_status	op_status_in_progress
    //     // В оплате	op_work_status	op_status_money_await
    //     // Отказ	op_work_status	op_status_fail
    // }
}
