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
        $workStatus, //inJob
        $resultStatus,  // result noresult expired
        $noresultReason,
        $failReason,
        $failType,


    ) {
        try {
            //code...

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
                    'value' => $eventTypeName . ' ' . $eventActionName
                ],
                [
                    'code' => 'event_title',
                    'name' => 'Название',
                    'value' => $eventTypeName . ' ' . $eventActionName
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


            if ($resultStatus !== 'result') {
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
            }
            if (!empty($workStatus)) {
                if (!empty($workStatus['code'])) {
                    $workStatusCode = $workStatus['code'];


                    if ($workStatusCode === 'fail') {  //если провал
                        if (!empty($failType)) {
                            if (!empty($failType['code'])) {
                                $noresultReasoneItem = [
                                    'code' => 'op_fail_type',
                                    'name' => 'Тип провала',
                                    'list' =>  [
                                        'code' => $failType['code'],
                                        // 'name' =>  'В работе' //'В работе'
                                    ],
                                ];
                                array_push($xoFields, $noresultReasoneItem);



                                if ($failType['code'] == 'failure') { //если тип провала - отказ
                                    if (!empty($failReason)) {
                                        if (!empty($failReason['code'])) {
                                            $noresultReasoneItem = [
                                                'code' => 'op_fail_reason',
                                                'name' => 'ОП Причина Отказа',
                                                'list' =>  [
                                                    'code' => $failReason['code'],
                                                    // 'name' =>  'В работе' //'В работе'
                                                ],
                                            ];
                                            array_push($xoFields, $noresultReasoneItem);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }

            foreach ($bitrixLists as $bitrixList) {
                $fieldsData = [
                    'NAME' => $eventTypeName . ' ' . $eventActionName
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

                BitrixListService::setItem(
                    $hook,
                    $bitrixList['bitrixId'],
                    $fieldsData
                );
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
                        $resultCode = 'in_work';

                        if ($currentEventType == 'hot') {
                            $resultCode = 'in_progress';
                        } else  if ($currentEventType == 'moneyAwait') {
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

    static function  getResultStatus($resultStatus)
    {

        $result = 'yes';
        if ($resultStatus !== 'result') {
            $result = 'no';
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
        Log::channel('telegram')->info('op_noresult_reason', [
            'resultStatus' => $resultStatus,


        ]);

        if (!empty($noresultReason)) {
            if (!empty($noresultReason['code'])) {
                $code = $noresultReason['code'];
            }
        }
        return $result;
    }



    static function  getFailType($resultStatus)
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
        Log::channel('telegram')->info('resultStatus', [
            'resultStatus' => $resultStatus,


        ]);

        return $result;
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
        Log::channel('telegram')->info('resultStatus', [
            'resultStatus' => $resultStatus,


        ]);

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
