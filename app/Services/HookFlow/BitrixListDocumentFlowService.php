<?php

namespace App\Services\HookFlow;

use App\Services\General\BitrixListService;
use DateTime;
use Illuminate\Support\Facades\Log;

class BitrixListDocumentFlowService



{

    protected $currentDepartamentType = null;
    protected $withLists = false;
    protected $bitrixLists = [];



    public function __construct() {}


    //lists flow

    static function getListsFlow(
        $hook,
        $bitrixLists,
        $eventType, // ev_invoice,  ev_offer_pres ....

        // Коммерческое Предлжение	event_type	ev_offer	EV_OFFER
        // Счет	event_type	ev_invoice	EV_INVOICE
        // Коммерческое Предлжение после презентации	event_type	ev_offer_pres	EV_OFFER_PRES
        // Счет после презентации	event_type	ev_invoice_pres	EV_INVOICE_PRES
        // Договор	event_type	ev_contract	EV_CONTRACT
        // Поставка	event_type	ev_supply	EV_SUPPLY
        $eventTypeName, //Коммерческое Предлжение   Счет после презентации Поставка


        $eventAction,  //           done - отчет сделан
        // Отправлен	event_action	act_send	ACT_SEND
        // Подписан	event_action	act_sign	ACT_SIGN
        // Оплачен	event_action	act_pay	ACT_PAY
        // $nowDate,
        $created,
        $responsible,
        $suresponsible,
        $companyId,
        $comment,
        $dealIds,
        $currentBaseDealId = null,
        $nowDate = null,
        $hotName  = null,
        // $workStatus, //inJob
        // $resultStatus,  // result noresult   .. without expired new !
        // $noresultReason,
        // $failReason,
        // $failType,


    ) {
        try {

            if (empty($nowDate)) {
                date_default_timezone_set('Europe/Moscow');
                $currentNowDate = new DateTime();
                $nowDate = $currentNowDate->format('d.m.Y H:i:s');
            }

            if (empty($hotName)) {

                $hotName = $eventTypeName . ' Создан';
            }
            // $eventActionName = 'Запланирован';
            // $evTypeName = 'Звонок';
            // $nextCommunication = $deadline;

            $crmValue = ['n0' => 'CO_' . $companyId];

            if (!empty($dealIds)) {

                foreach ($dealIds as $key => $dealId) {
                    $index = $key + 1;
                    $crmValue['n' . $index] = 'D_' . $dealId;
                }
            }


            $uniqueHash = md5(uniqid(rand(), true));


            $code = $uniqueHash;

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
                // [
                //     'code' => 'plan_date',
                //     'name' => 'Дата Следующей коммуникации',
                //     'value' => $nextCommunication
                // ],
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
                    'value' => $crmValue,
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
                        'code'  => $eventType,
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
                        'code' => 'op_status_in_work',  //'in_work',
                        // 'name' =>  'В работе' //'В работе'
                    ],
                ],
                [
                    'code' => 'op_result_status',
                    'name' => 'Результативность',
                    'list' =>  [
                        'code' => 'op_call_result_yes',  //'in_work',
                        // 'name' =>  'В работе' //'В работе'
                    ],
                ],
                [
                    'code' => 'op_noresult_reason',
                    'name' => 'Тип Нерезультативности',
                    'list' =>  [
                        'code' => null,
                        // 'name' =>  'В работе' //'В работе'
                    ],
                ],
                [
                    'code' => 'op_prospects_type',
                    'name' => 'Перспективность',
                    'list' =>  [
                        'code' => 'op_prospects_good',
                    ],
                ]


            ];



            $fieldsData = [
                'NAME' => $hotName
            ];

            foreach ($bitrixLists as $bitrixList) {
                if ($bitrixList['type'] === 'history' && $bitrixList['group'] === 'sales') {
                    if ($eventType !== 'ev_invoice_pres' && $eventType !== 'ev_offer_pres') { //их записываем  только kpi к каждому такому выше еще идет дубль обычного ev_offer | ev_invoice
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
                        }
                        // array_push($fieldsData, $currentDataField);
                    }

                    BitrixListService::setItem(
                        $hook,
                        $bitrixList['bitrixId'],
                        $fieldsData
                    );
                }
            }

            if ($companyId) {
                /**
                 * refact to install
                 */
                $dealId = '_';
                if (!empty($currentBaseDealId)) {
                    $dealId = $currentBaseDealId;
                }
                foreach ($bitrixLists as $bitrixList) {
                    if ($bitrixList['type'] === 'kpi' && $bitrixList['group'] === 'sales') {

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
                        $code = $companyId . '_' . $dealId . '_' . $eventType;

                        BitrixListService::setItem(
                            $hook,
                            $bitrixList['bitrixId'],
                            $fieldsData,
                            $code
                        );
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
        $eventType, // ev_invoice,  ev_offer_pres ....

        // Коммерческое Предлжение	event_type	ev_offer	EV_OFFER
        // Счет	event_type	ev_invoice	EV_INVOICE
        // Коммерческое Предлжение после презентации	event_type	ev_offer_pres	EV_OFFER_PRES
        // Счет после презентации	event_type	ev_invoice_pres	EV_INVOICE_PRES
        // Договор	event_type	ev_contract	EV_CONTRACT
        // Поставка	event_type	ev_supply	EV_SUPPLY
        $eventTypeName, //Коммерческое Предлжение   Счет после презентации Поставка


        $eventAction,  // 
        // Отправлен	event_action	act_send	ACT_SEND
        // Подписан	event_action	act_sign	ACT_SIGN
        // Оплачен	event_action	act_pay	ACT_PAY
        // $nowDate,
        $created,
        $responsible,
        $suresponsible,
        $companyId,
        $comment,
        $dealIds,
        $currentBaseDealId = null,
        $nowDate = null,
        $hotName  = null,
        // $workStatus, //inJob
        // $resultStatus,  // result noresult   .. without expired new !
        // $noresultReason,
        // $failReason,
        // $failType,
        $resultBatchCommands // []


    ) {
        try {

            if (empty($nowDate)) {
                date_default_timezone_set('Europe/Moscow');
                $currentNowDate = new DateTime();
                $nowDate = $currentNowDate->format('d.m.Y H:i:s');
            }
            $actionStringName = 'Создан';
            if ($eventAction === 'act_send') {
                $actionStringName = 'Отправлен';
            } else if ($eventAction === 'act_pay') {
                $actionStringName = 'Оплачен';
            } else if ($eventAction === 'act_sign') {
                $actionStringName = 'Подписан';
            }


            if (empty($hotName)) {

                $hotName = $eventTypeName . ' ' . $actionStringName;
            }
            print_r('<br>');
            print_r($hotName);
            print_r('<br>');
            print_r($eventType);
            print_r('<br>');

            print_r('           <br> eventAction           ');
            print_r($eventAction);
            print_r('<br>');
            // $eventActionName = 'Запланирован';
            // $evTypeName = 'Звонок';
            // $nextCommunication = $deadline;

            $crmValue = ['n0' => 'CO_' . $companyId];

            if (!empty($dealIds)) {

                foreach ($dealIds as $key => $dealId) {
                    $index = $key + 1;
                    $crmValue['n' . $index] = 'D_' . $dealId;
                }
            }


            $uniqueHash = md5(uniqid(rand(), true));


            $code = $uniqueHash;

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
                // [
                //     'code' => 'plan_date',
                //     'name' => 'Дата Следующей коммуникации',
                //     'value' => $nextCommunication
                // ],
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
                    'value' => $crmValue,
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
                        'code'  => $eventType,
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
                        'code' => 'op_status_in_work',  //'in_work',
                        // 'name' =>  'В работе' //'В работе'
                    ],
                ],
                [
                    'code' => 'op_result_status',
                    'name' => 'Результативность',
                    'list' =>  [
                        'code' => 'op_call_result_yes',  //'in_work',
                        // 'name' =>  'В работе' //'В работе'
                    ],
                ],
                [
                    'code' => 'op_noresult_reason',
                    'name' => 'Тип Нерезультативности',
                    'list' =>  [
                        'code' => null,
                        // 'name' =>  'В работе' //'В работе'
                    ],
                ],
                [
                    'code' => 'op_prospects_type',
                    'name' => 'Перспективность',
                    'list' =>  [
                        'code' => 'op_prospects_good',
                    ],
                ]


            ];



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

                    // BitrixListService::setItem(
                    //     $hook,
                    //     $bitrixList['bitrixId'],
                    //     $fieldsData
                    // );

                    $uniqueHash = md5(uniqid(rand(), true));
                    $code = $uniqueHash;
                    $fullCode = $bitrixList['type'] . '_' . $companyId . '_' . $eventAction . '_' . $code;
                    $command =  BitrixListService::getBatchCommandSetItem(
                        $hook,
                        $bitrixList['bitrixId'],
                        $fieldsData,
                        $fullCode
                    );
                    $resultBatchCommands['set_list_item_' . $fullCode] = $command;
                }
            }

            if ($companyId) {
                /**
                 * refact to install
                 */
                $dealId = '_';
                if (!empty($currentBaseDealId)) {
                    $dealId = $currentBaseDealId;
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
                        $code = $companyId . '_' . $dealId . '_' . $eventType . '_' . $eventAction;

                        // BitrixListService::setItem(
                        //     $hook,
                        //     $bitrixList['bitrixId'],
                        //     $fieldsData,
                        //     $code
                        // );
                        $command =  BitrixListService::getBatchCommandSetItem(
                            $hook,
                            $bitrixList['bitrixId'],
                            $fieldsData,
                            $code
                        );
                        $resultBatchCommands['set_list_item_' . $code] = $command;
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
}
