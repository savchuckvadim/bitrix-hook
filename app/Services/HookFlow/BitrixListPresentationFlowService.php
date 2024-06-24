<?php

namespace App\Services\HookFlow;

use App\Services\General\BitrixListService;
use DateTime;
use Illuminate\Support\Facades\Log;

class BitrixListPresentationFlowService



{

    protected $currentDepartamentType = null;
    protected $withLists = false;
    protected $bitrixLists = [];



    public function __construct()
    {
    }


    //lists flow

    static function getListPresentationPlanFlow(
        $hook,
        $bitrixLists,
        $currentDealIds,
        $nowDate,

        // $eventType, // xo warm presentation, offer invoice
        // $eventTypeName, //звонок по решению по оплате
        $eventAction,  // plan done expired fail success

        // // $eventName,
        $deadline,
        $created,
        $responsible,
        // $suresponsible,
        $companyId,
        $comment,
        $name,
        $workStatus, //inJob
        $resultStatus,  // result noresult expired
        // $noresultReason,
        // $failReason,
        // $failType,


    ) {
        try {
            $eventType = 'plan';
            if (isset($workStatus['code'])) {
                $workStatus = $workStatus['code'];
            }


            $presPortalBtxList = null;
            $code = '';

            foreach ($currentDealIds as $key => $dealId) {
                if ($key == 0) {
                    $code = $dealId;
                } else {
                    $code = $code . '_' . $dealId;
                }
            }

            foreach ($bitrixLists as $bitrixList) {
                if (!empty($bitrixList['type'] == 'presentation')) {
                    $presPortalBtxList = $bitrixList;
                }
            }

            // $fieldsData = [
            //     'NAME' => 'Заявка на презентацию ' . $name,

            // ];



            // $nowDate = new DateTime();

            $eventActionName = 'Запланирована';
            $evTypeName = 'Презентация';


            // if ($eventType == 'xo' || $eventType == 'cold') {
            //     $evTypeName = 'Холодный звонок';
            // } else if ($eventType == 'warm' || $eventType == 'call') {
            //     $evTypeName = 'Звонок';
            // } else if ($eventType == 'presentation') {
            //     $evTypeName = 'Презентация';
            //     $eventActionName = 'Запланирована';
            // } else if ($eventType == 'hot' || $eventType == 'inProgress' || $eventType == 'in_progress') {
            //     $evTypeName = 'Звонок по решению';
            // } else if ($eventType == 'money' || $eventType == 'moneyAwait' || $eventType == 'money_await') {
            //     $evTypeName = 'Звонок по оплате';
            // }






            if ($eventAction == 'expired') {
                $eventAction = 'pound';
                $eventActionName = 'Перенесена';
            } else    if ($eventAction == 'done') {

                $eventActionName = 'Состоялась';
            } else    if ($eventAction == 'fail') {

                $eventActionName = 'Отказ';
            } else    if ($eventAction == 'success') {

                $eventActionName = 'Продажа';
            }

            $presentatationPlanFields = [
                [
                    'code' => 'pres_event_date', //дата начала
                    'name' => 'Дата создания заявки',
                    'value' => $nowDate //$nowDate->format('d.m.Y H:i:s'),
                ],
                [
                    'code' => 'pres_init_status_date',
                    'name' => 'Заявка Принята/Отклонена',
                    'value' => $nowDate
                ],
                // [
                //     'code' => 'event_title',
                //     'name' => 'Название',
                //     'value' => $evTypeName . ' ' . $eventActionName
                // ],
                [
                    'code' => 'pres_plan_date',
                    'name' => 'Планируемая Дата презентации',
                    'value' => $deadline
                ],
                [
                    'code' => 'pres_plan_author',
                    'name' => 'Автор Заявки',
                    'value' => $created,
                ],
                [
                    'code' => 'pres_responsible',
                    'name' => 'Ответственный',
                    'value' => $responsible,
                ],
                [
                    'code' => 'pres_plan_comment',
                    'name' => 'Комментарий к заявке',
                    'value' => $comment,
                ],
                [
                    'code' => 'pres_plan_contacts',
                    'name' => 'Контактные данные',
                    'value' => 'Контактные данные', // []
                ],
                [
                    'code' => 'pres_init_status',
                    'name' => 'Статус Заявки',
                    'list' =>  [
                        'code' => BitrixListPresentationFlowService::getPresInitStatus(
                            $resultStatus
                        )
                    ]
                ],
                [
                    'code' => 'pres_crm',
                    'name' => 'pres_crm',
                    'value' => ['n0' => 'CO_' . $companyId],
                ],
                [
                    'code' => 'pres_crm_base_deal',
                    'name' => 'crm',
                    'value' => ['n0' => 'D_' . $currentDealIds[0]], //base deal
                ],
                [
                    'code' => 'pres_crm_deal',
                    'name' => 'crm',
                    'value' => ['n0' => 'D_' . $currentDealIds[1]], //base deal
                ],
                // [
                //     'code' => 'pres_crm_tmc_deal',
                //     'name' => 'crm',
                //     // 'value' => ['n0' => 'CO_' . $companyId], //tmc deal
                // ],
                [
                    'code' => 'pres_result_status',
                    'name' => 'Результативность',
                    'list' =>  [
                        'code' => BitrixListPresentationFlowService::getPresResultStatus(
                            false,
                            false,
                            true,
                            $workStatus
                        ),  //'in_work',
                        // 'name' =>  'В работе' //'В работе'
                    ],
                ],

                [
                    'code' => 'pres_work_status',
                    'name' => 'Статус Работы',
                    'list' =>  [
                        'code' => BitrixListPresentationFlowService::getCurrentWorkStatusCode(
                            ['code' => 'inJob'],
                            $eventType
                        ),  //'in_work',
                        // 'name' =>  'В работе' //'В работе'
                    ],
                ],
                // [
                //     'code' => 'op_result_status',
                //     'name' => 'Результативность',
                //     'list' =>  [
                //         'code' => BitrixListFlowService::getResultStatus(
                //             $resultStatus,
                //         ),  //'in_work',
                //         // 'name' =>  'В работе' //'В работе'
                //     ],
                // ],


            ];


            $fieldsData = [
                'NAME' => $evTypeName . ' ' . $name
            ];
            foreach ($presentatationPlanFields as $presValue) {
                $currentDataField = [];
                $fieldCode = $presPortalBtxList['group'] . '_' . $presPortalBtxList['type'] . '_' . $presValue['code'];
                $btxId = BitrixListPresentationFlowService::getBtxListCurrentData($presPortalBtxList, $fieldCode, null);
                if (!empty($presValue)) {



                    if (!empty($presValue['value'])) {
                        $fieldsData[$btxId] = $presValue['value'];
                        $currentDataField[$btxId] = $presValue['value'];
                    }

                    if (!empty($presValue['list'])) {
                        $btxItemId = BitrixListPresentationFlowService::getBtxListCurrentData($presPortalBtxList, $fieldCode, $presValue['list']['code']);
                        $currentDataField[$btxId] = [

                            $btxItemId =>  $presValue['list']['code']
                        ];

                        $fieldsData[$btxId] =  $btxItemId;
                    }
                }
                // array_push($fieldsData, $currentDataField);
            }

            // Log::channel('telegram')->info('getListPresentationPlanFlow', [
            //     'fieldsData' => $fieldsData,
            //     'currentDealIds' => $currentDealIds,


            // ]);

            BitrixListService::setItem(
                $hook,
                $bitrixList['bitrixId'],
                $fieldsData,
                $code
            );
        } catch (\Throwable $th) {
            $errorMessages =  [
                'message'   => $th->getMessage(),
                'file'      => $th->getFile(),
                'line'      => $th->getLine(),
                'trace'     => $th->getTraceAsString(),
            ];
            Log::error('ERROR COLD: getListsFlow',  $errorMessages);

            Log::channel('telegram')->error('APRIL_HOOK Pres Lists Flow', $errorMessages);
        }
    }


    static function getListPresentationReportFlow(
        $hook,
        $bitrixLists,
        $currentDealIds,
        // $reportStatus, // as pound noresultFail
        $isPresentationDone,
        $nowDate,
        $eventType, //plan|report
        // $eventType, // xo warm presentation, offer invoice
        // $eventTypeName, //звонок по решению по оплате
        $isExpired,
        // // $eventName,
        $deadline,
        $created,
        $responsible,
        // $suresponsible,
        $companyId,
        $comment,
        $name,
        $workStatus, //inJob
        $resultStatus,  // result noresult expired
        $noresultReason,
        $failReason,
        $failType,


    ) {


        try {
            $eventType = 'report';
            $isDone = $isPresentationDone;

            if (isset($workStatus['code'])) {
                $workStatus = $workStatus['code'];
            }

            $eventActionName = 'Запланирована';
            $evTypeName = 'Презентация';

            if ($isDone) {
                $eventActionName = 'Проведена';
            } else if ($isExpired) {
                $eventActionName = 'Перенесена';
            } else if (!$isExpired && !$isDone && $workStatus == 'fail') {
                $eventActionName = 'Не состоялась';
            }


            $comment = $deadline . ' ' . $eventActionName . ' ' . $comment;
            $totalPresComment = $comment;


            $bitrixList = null;
            $code = '';
            $fieldsData = [];
            foreach ($currentDealIds as $key => $dealId) {
                if ($key == 0) {
                    $code = $dealId;
                } else {
                    $code = $code . '_' . $dealId;
                }
            }

            foreach ($bitrixLists as $btxList) {
                if (!empty($btxList['type'] == 'presentation')) {
                    $bitrixList = $btxList;
                }
            }

            $currentItemList = BitrixListService::getItem(
                $hook,
                $bitrixList['bitrixId'],
                $code
            );

            if (!empty($currentItemList) && is_array($currentItemList)) {
                $currentItemList = $currentItemList[0];
                if (!empty($currentItemList)) {
                    $fieldsData = $currentItemList;

                    $totalPresComment = BitrixListPresentationFlowService::getCurrentPresComment(
                        $currentItemList,
                        $bitrixList,
                        $comment
                    );
                }
            }






            $presentatationReportFields = [
                [
                    'code' => 'pres_done_comment',
                    'name' => 'Комментарий после презентации',
                    'value' => $totalPresComment,
                ],


                [
                    'code' => 'pres_result_status',
                    'name' => 'Результативность',
                    'list' =>  [
                        'code' => BitrixListPresentationFlowService::getPresResultStatus(
                            $isDone,
                            $isExpired,
                            false, // isPlan
                            $workStatus
                        ),  //'in_work',
                        // 'name' =>  'В работе' //'В работе'
                    ],
                ],

                [
                    'code' => 'pres_work_status',
                    'name' => 'Статус Работы',
                    'list' =>  [
                        'code' => BitrixListPresentationFlowService::getCurrentWorkStatusCode(
                            $workStatus,
                            $eventType
                        ),  //'in_work',
                        // 'name' =>  'В работе' //'В работе'
                    ],
                ],
                // [
                //     'code' => 'op_result_status',
                //     'name' => 'Результативность',
                //     'list' =>  [
                //         'code' => BitrixListFlowService::getResultStatus(
                //             $resultStatus,
                //         ),  //'in_work',
                //         // 'name' =>  'В работе' //'В работе'
                //     ],
                // ],


            ];

            if ($isExpired) {
                $isExpiredItem = [
                    'code' => 'pres_pound_date',
                    'name' => 'Дата переноса',
                    'value' =>  $deadline,
                ];
                array_push($presentatationReportFields, $isExpiredItem);
            }
            if ($isDone) {
                $isDoneItem = [
                    'code' => 'pres_done_date',
                    'name' => 'Дата проведения презентации',
                    'value' =>  $nowDate,
                ];
                array_push($presentatationReportFields, $isDoneItem);
            }
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
                        array_push($presentatationReportFields, $noresultReasoneItem);
                    }
                }
            }


            if ($workStatus === 'fail') {  //если провал
                if (!empty($failType)) {
                    if (!empty($failType['code'])) {
                        $failTypeItemItem = [
                            'code' => 'op_fail_type',
                            'name' => 'Тип провала',
                            'list' =>  [
                                'code' => $failType['code'],
                                // 'name' =>  'В работе' //'В работе'
                            ],
                        ];
                        array_push($presentatationReportFields, $failTypeItemItem);



                        if ($failType['code'] == 'failure') { //если тип провала - отказ
                            if (!empty($failReason)) {
                                if (!empty($failReason['code'])) {
                                    $failReasonItem = [
                                        'code' => 'op_fail_reason',
                                        'name' => 'ОП Причина Отказа',
                                        'list' =>  [
                                            'code' => $failReason['code'],
                                            // 'name' =>  'В работе' //'В работе'
                                        ],
                                    ];
                                    array_push($presentatationReportFields, $failReasonItem);
                                }
                            }
                        }
                    }
                }
            }



            // $fieldsData['NAME'] = $evTypeName . ' ' . $eventActionName;
            foreach ($presentatationReportFields as $prRepValue) {
                $currentDataField = [];
                $fieldCode = $bitrixList['group'] . '_' . $bitrixList['type'] . '_' . $prRepValue['code'];
                $btxId = BitrixListPresentationFlowService::getBtxListCurrentData($bitrixList, $fieldCode, null);
                if (!empty($prRepValue)) {



                    if (!empty($prRepValue['value'])) {
                        $fieldsData[$btxId] = $prRepValue['value'];
                        $currentDataField[$btxId] = $prRepValue['value'];
                    }

                    if (!empty($prRepValue['list'])) {
                        $btxItemId = BitrixListPresentationFlowService::getBtxListCurrentData($bitrixList, $fieldCode, $prRepValue['list']['code']);
                        $currentDataField[$btxId] = [

                            $btxItemId =>  $prRepValue['list']['code']
                        ];

                        $fieldsData[$btxId] =  $btxItemId;
                    }
                }
                // array_push($fieldsData, $currentDataField);
            }

            // Log::channel('telegram')->info('pres lidt test update or create', [
            //     'currentItemList' => $currentItemList,
            //     'fieldsData' => $fieldsData,
            //     // 'failReason' => $failReason,
            //     // 'failType' => $failType,

            // ]);

            if ($currentItemList) {
                BitrixListService::updateItem(
                    $hook,
                    $bitrixList['bitrixId'],
                    $fieldsData,
                    $code
                );
            } else { //это так на всякий случай по идее при репорте не должно нечего создаваться
                // даже если unplanned
                BitrixListService::setItem(
                    $hook,
                    $bitrixList['bitrixId'],
                    $fieldsData,
                    $code
                );
            }

            $currentItemList = BitrixListService::getItem(
                $hook,
                $bitrixList['bitrixId'],

                $code
            );
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

    // static function  getEventType(
    //     $eventType, // xo warm presentation, offer invoice
    // ) {
    //     // Холодный звонок	event_type	xo
    //     // Звонок	event_type	call
    //     // Презентация	event_type	presentation
    //     // Информация	event_type	info
    //     // Приглашение на семинар	event_type	seminar
    //     // Звонок по решению	event_type	call_in_progress
    //     // Звонок по оплате	event_type	call_in_money
    //     // Входящий звонок	event_type	come_call
    //     // Заявка с сайта	event_type	site

    //     $result = 'xo';
    //     if ($eventType === 'call' || $eventType === 'warm') {
    //         $result = 'call';
    //     } else if ($eventType === 'presentation') {
    //         $result = 'presentation';
    //     } else if ($eventType === 'hot' || $eventType === 'inProgress' || $eventType === 'in_progress') {
    //         $result = 'call_in_progress';
    //     } else if ($eventType === 'moneyAwait' || $eventType === 'money_await' || $eventType === 'money') {
    //         $result = 'call_in_money';
    //     }


    //     return $result;
    // }


    static function getCurrentWorkStatusCode(
        $workStatus,
        $currentEventType,

        // 0: {id: 1, code: "warm", name: "Звонок"}
        // // 1: {id: 2, code: "presentation", name: "Презентация"}
        // // 2: {id: 3, code: "hot", name: "Решение"}
        // // 3: {id: 4, code: "moneyAwait", name: "Оплата"}

    ) {
        $resultCode = 'pres_status_in_work';
        //         В работе	pres_work_status	pres_status_in_work
        // На долгий период	pres_work_status	pres_status_in_long
        // Продажа	pres_work_status	pres_status_success
        // В решении	pres_work_status	pres_status_in_progress
        // В оплате	pres_work_status	pres_status_money_await
        // Отказ	pres_work_status	pres_status_fail


        // 0: {id: 0, code: "inJob", name: "В работе"} in_long
        // 1: {id: 1, code: "setAside", name: "Отложено"}
        // 2: {id: 2, code: "success", name: "Продажа"}
        // 3: {id: 3, code: "fail", name: "Отказ"}
        if (!empty($workStatus)) {
            if (!empty($workStatus['code'])) {
                $code = $workStatus['code'];
                switch ($code) {
                    case 'inJob':
                        $resultCode = 'pres_status_in_work';

                        if ($currentEventType == 'hot') {
                            $resultCode = 'pres_status_in_progress';
                        } else  if ($currentEventType == 'moneyAwait') {
                            $resultCode = 'pres_status_money_await';
                        }


                        break;
                    case 'setAside': //in_long
                        $resultCode = 'pres_status_in_long';
                        break;
                    case 'fail':
                        $resultCode = 'pres_status_fail';
                        break;
                    case 'success':
                        $resultCode = 'pres_status_success';
                        break;
                    default:
                        break;
                }
            }
        }

        return $resultCode;
    }

    static function  getPresInitStatus($resultStatus)
    {

        $result = 'pres_init_status_yes';
        if ($resultStatus !== 'result') {
            $result = 'pres_init_status_no';
        }


        return $result;
    }
    static function  getPresResultStatus(
        $isDone,
        $isExpired,
        $isPlan,
        $workStatus // inJob expired fail success

    ) {
        // Заявка в рассмотрении	pres_result_status	pres_result_init_await
        // Заявка принята	pres_result_status	pres_result_init_done
        // Заявка не принята	pres_result_status	pres_result_init_fail
        //   Презентация перенесена	pres_result_status	pres_result_init_pound
        //Презентация проведена	pres_result_status	pres_result_done
        //   Презентация не состоялась	pres_result_status	pres_result_nopres
        //   Отказ после презентации	pres_result_status	pres_result_pres_fail
        //   В работе после презентации	pres_result_status	pres_result_pres_in_work
        // Продажа после презентации	pres_result_status	pres_result_pres_sale
        $result = 'pres_result_init_done';

        if ($isPlan) {
            $result = 'pres_result_init_done';
        } else {
            if ($isDone) { // состоялась
                $result = 'pres_result_init_done';
                if ($workStatus == 'inJob' || $workStatus == 'expired') { //В работе после презентации

                    $result = 'pres_result_pres_in_work';
                } else if ($workStatus == 'fail') {  //Отказ после презентации

                    $result = 'pres_result_pres_fail';
                } else if ($workStatus == 'success') { //Продажа после презентации

                    $result = 'pres_result_pres_sale';
                }
            } else { // не состоялась

                if ($isExpired) { // Презентация перенесена
                    $result = 'pres_result_init_pound';
                } else {  // не состоялась

                    $result = 'pres_result_nopres';
                }
            }
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
    static function  getCurrentPresComment($currentItemList, $bitrixList, $comment)
    {
        // {"ID":"6121","
        //     IBLOCK_ID":"111",
        //     "NAME":"Презентация Перенесена",
        //     "IBLOCK_SECTION_ID":null,
        //     "CREATED_BY":"1",
        //     "BP_PUBLISHED":"Y",
        //     "CODE":"1801_1805",
        //     "PROPERTY_1213":{"49973":"18.06.2024 11:55:36"},
        //     "PROPERTY_1215":{"49975":"1"},
        //     "PROPERTY_1225":{"49979":"Контактные данные"},
        //     "PROPERTY_1227":{"49981":"1"},
        //     "PROPERTY_1217":{"49977":"07.07.2024 18:55:13"},
        //     "PROPERTY_1235":{"49991":"не было на месте"},
        //     "PROPERTY_1237":{"49993":"3509"},
        //     "PROPERTY_1245":{"49985":"CO_13"},
        //     "PROPERTY_1247":{"49987":"D_1805"},
        //     "PROPERTY_1239":{"49995":"3521"},
        //     "PROPERTY_1251":{"49989":"D_1801"}
        // }

        $commentField = null;
        $currentCommentIndex = 0;
        $resultComments = [];
        if (!empty($bitrixList)) { //every from portal

            if (!empty($bitrixList['bitrixfields'])) {

                $btxFields = $bitrixList['bitrixfields'];
                foreach ($btxFields as $btxField) {
                    if ($btxField['code'] == 'sales_presentation_pres_done_comment') {
                        Log::channel('telegram')->info('pres sales_presentation_pres_done_comment', [
                            'btxField' => $btxField,

                            // 'failReason' => $failReason,
                            // 'failType' => $failType,

                        ]);


                        foreach ($currentItemList as $prop_key => $value) {
                            // Log::channel('telegram')->info('pres all prop_key', [
                            //     'prop_key' => $prop_key,


                            // ]);


                            if ($prop_key == $btxField['bitrixCamelId']) {
                                Log::channel('telegram')->info('pres searching prop_key', [
                                    'prop_key' => $prop_key,
                                    'value' => $value,
                                    // 'failReason' => $failReason,
                                    // 'failType' => $failType,

                                ]);


                                if (!empty($value)) {
                                    foreach ($value as $id => $commentItemvalue) {
                                        $resultComments['n' . $currentCommentIndex] = $commentItemvalue;

                                        $currentCommentIndex += 1;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        $resultComments['n' . $currentCommentIndex] = $comment;
        return $resultComments;
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
