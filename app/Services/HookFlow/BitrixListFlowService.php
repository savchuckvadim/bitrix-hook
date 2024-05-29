<?php

namespace App\Services\HookFlow;

use App\Services\General\BitrixListService;
use DateTime;

class BitrixListFlowService



{
    
    protected $currentDepartamentType = null;
    protected $withLists = false;
    protected $bitrixLists = [];



    public function __construct(

     

    ) {

    }


    //lists flow

    static function getListsFlow(
        $hook,
        $bitrixLists,
        $eventType, // xo warm presentation,
        $eventAction,  // plan done expired 
        $nowDate,
        $eventName,
        $deadline,
        $created,
        $responsible,
        $suresponsible,
        $companyId,
        $comment,

    ) {
        $nowDate = new DateTime();
        $xoFields = [
            [
                'code' => 'event_date',
                'name' => 'Дата',
                'value' => $nowDate->format('d.m.Y H:i:s'),
            ],
            [
                'code' => 'event_title',
                'name' => 'Название',
                'value' => $eventName
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
                    'code' => 'xo',
                    'name' => 'Холодный звонок',

                ],
            ],
            [
                'code' => 'event_action',
                'name' => 'Событие Действие',
                'list' =>  [
                    'code' => 'plan',
                    'name' => 'Запланирован'
                ],
            ],

            [
                'code' => 'op_work_status',
                'name' => 'Статус Работы',
                'list' =>  [
                    'code' => 'op_status_in_work',
                    'name' => 'В работе'
                ],
            ]

        ];


        foreach ($bitrixLists as $bitrixList) {
            $fieldsData = [
                'NAME' => $eventName
            ];
            foreach ($xoFields as $xoValue) {
                $currentDataField = [];
                $fieldCode = $bitrixList['group'].'_'.$bitrixList['type'].'_'. $xoValue['code'];
                $btxId = BitrixListFlowService::getBtxListCurrentData($bitrixList, $fieldCode, null);
                if (!empty($xoValue)) {

                    

                    if (!empty($xoValue['value'])) {
                        $fieldsData[$btxId] = $xoValue['value'];
                        $currentDataField[$btxId] = $xoValue['value'];
                    }

                    if (!empty($xoValue['list'])) {
                        $btxItemId = BitrixListFlowService::getBtxListCurrentData($bitrixList, $fieldCode, $xoValue['list']['code']);
                        $currentDataField[$btxId] = [
                            
                            $btxItemId =>  $xoValue['list']['name']
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
                    if (!empty($btxField['bitrixfielditems'])) {




                        $btxFieldItems = $btxField['bitrixfielditems'];



                        foreach ($btxFieldItems as $btxFieldItem) {

                            if ($listCode) {
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
}



