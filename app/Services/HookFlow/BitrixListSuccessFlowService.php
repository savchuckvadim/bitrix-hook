<?php

namespace App\Services\HookFlow;

use App\Services\General\BitrixListService;
use DateTime;
use Illuminate\Support\Facades\Log;

class BitrixListSuccessFlowService



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
        $workStatus, //success | fail
        $resultStatus,  // result noresult   .. without expired new !
        $noresultReason,
        $failReason,
        $failType,
        $dealIds,
        $currentBaseDealId

    ) {
        try {

            date_default_timezone_set('Europe/Moscow');
            $nowDate = new DateTime();
            Log::channel('telegram')->info('HOOK TST SUCCESS', [
                'nowDate' => $nowDate,
                'message' => 'success service',
    
    
            ]);

            $evTypeName = 'Продажа';
            $isSuccess = true;
            if ($workStatus['code'] == 'fail') {

                $evTypeName = 'Отказ';
                $isSuccess = false;
            }




            $crmValue = ['n0' => 'CO_' . $companyId];

            if (!empty($dealIds)) {

                foreach ($dealIds as $key => $dealId) {
                    $crmValue['n' . $key + 1] = 'D_' . $dealId;
                }
            }



            $xoFields = [
                [
                    'code' => 'event_date',
                    'name' => 'Дата',
                    'value' => $nowDate->format('d.m.Y H:i:s'),
                ],
                // [
                //     'code' => 'name',
                //     'name' => 'Название',
                //     'value' => $evTypeName
                // ],
                [
                    'code' => 'event_title',
                    'name' => 'Название',
                    'value' => $evTypeName
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
                        'code'  => BitrixListSuccessFlowService::getEventType(
                            $isSuccess
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
                'NAME' => $evTypeName
            ];
        
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
                   
                    BitrixListService::setItem(
                        $hook,
                        $bitrixList['bitrixId'],
                        $fieldsData
                    );
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
 
    static function  getEventType(
        $isSuccess, // xo warm presentation, offer invoice
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

        //         Продажа	event_type	ev_success	EV_SUCCESS
        // Отказ	event_type	ev_fail	EV_FAIL

        $result = 'ev_success';

        if (!$isSuccess) {
            $result = 'ev_fail';
        }


        return $result;
    }

}
