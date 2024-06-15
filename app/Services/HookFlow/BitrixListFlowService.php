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



    public function __construct()
    {
    }


    //lists flow

    static function getListsFlow(
        $hook,
        $bitrixLists,
        $eventType, // xo warm presentation, offer invoice
        $eventTypeName, //звонок по решению по оплате
        $eventAction,  // plan done expired fail success
        // $eventName,
        $deadline,
        $created,
        $responsible,
        $suresponsible,
        $companyId,
        $comment,
        $workStatus

    ) {
        $nowDate = new DateTime();

        $eventActionName = 'Запланирован';

        if ($eventType == 'presentation') {
            $eventActionName = 'Запланирована';
        }


        if ($eventAction == 'expired') {
            $eventAction = 'pound';
            $eventActionName = 'Перенос';
        } else    if ($eventAction == 'done') {

            $eventActionName = 'Состоялся';
            if ($eventType == 'presentation') {
                $eventActionName = 'Состоялась';
            }
        } else    if ($eventAction == 'fail') {

            $eventActionName = 'Отказ';
        } else    if ($eventAction == 'success') {

            $eventActionName = 'Продажа';
        }

        $xoFields = [
            [
                'code' => 'event_date',
                'name' => 'Дата',
                'value' => $nowDate->format('d.m.Y H:i:s'),
            ],
            [
                'code' => 'name',
                'name' => 'Название',
                'value' => $eventTypeName . ' ' . $eventAction
            ],
            [
                'code' => 'event_title',
                'name' => 'Название',
                'value' => $eventTypeName . ' ' . $eventAction
            ],
            [
                'code' => 'plan_date',
                'name' => 'Дата Следующей коммуникации',
                'value' => $deadline
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
                    'code' => $eventType,
                    'name' => $eventTypeName,

                ],
            ],
            [
                'code' => 'event_action',
                'name' => 'Событие Действие',
                'list' =>  [
                    'code' => $eventAction,
                    'name' => $eventActionName //Запланирован/на
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
                    'name' =>  'В работе' //'В работе'
                ],
            ]

        ];


        foreach ($bitrixLists as $bitrixList) {
            $fieldsData = [
                'NAME' => $eventTypeName . ' ' . $eventAction
            ];
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
            // Log::channel('telegram')->info('HOOK LISTS TEST', [
            //     'data' => $fieldsData
            // ]);
            BitrixListService::setItem(
                $hook,
                $bitrixList['bitrixId'],
                $fieldsData
            );
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
                        $resultCode = 'in_work';

                        if($currentEventType == 'hot'){
                            $resultCode = 'in_progress';
                        }else  if($currentEventType == 'moneyAwait'){
                            $resultCode = 'money_await';
                        }


                        break;
                    case 'setAside': //in_long
                        $resultCode = 'in_long';
                        break;
                    case 'fail':
                        $resultCode = 'fail';
                        break;
                    case 'success':
                        $resultCode = 'success';
                        break;
                    default:
                        break;
                }
            }
        }
        return $resultCode;
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
