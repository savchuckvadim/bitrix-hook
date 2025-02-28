<?php

namespace App\Http\Controllers\Front\EventCalling\List;


use App\Http\Controllers\APIOnlineController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\PortalController;
use App\Services\General\BitrixBatchService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HistoryController extends Controller


{
    protected $domain;
    protected $portal;
    protected $portalKPIList;
    protected $hook;

    protected $filters;


    public function __construct(
        // $type,
        $domain,

    ) {
        $this->domain = $domain;

        $portal = PortalController::getPortal($domain);

        if (!empty($portal['data'])) {
            $portal = $portal['data'];
        }

        $this->portal = $portal;
        $this->hook = PortalController::getHook($domain);
        if (!empty($portal['bitrixLists'])) {

            $bitrixLists = $portal['bitrixLists'];

            if (!empty($bitrixLists)) {

                foreach ($bitrixLists as $bitrixList) {
                    if ($bitrixList['type'] == 'history') {
                        $this->portalKPIList = $bitrixList;
                    }
                }
            }
        }
    }





    public  function getHistory($companyId)
    {

        $batchResults = null;
        $currentActionsData = [];
        $actionFieldId = null;
        try {
            // $domain = $request['domain'];


            // $companyId = $request['companyId'];
            $listId = $this->portalKPIList['bitrixId'];
            $listFields = $this->portalKPIList['bitrixfields'];
            $eventActionField = null;
            $eventActionTypeField = null;


            $companyIdField = null;
            $companyIdFieldId = null;

            $commentField = null;
            $commentFieldId = null;




            $resultStatusField = null;
            $resultStatusFieldId = null;



            $noresultReasonField = null;
            $noresultReasonFieldId = null;

            if (!empty($listFields)) {

                foreach ($listFields as $plField) {
                    if ($plField['code'] === 'sales_history_event_action') {
                        $eventActionField = $plField;
                        $actionFieldId = $eventActionField['bitrixCamelId']; //like PROPERTY_2119 
                    }
                    if ($plField['code'] === 'sales_history_event_type') {
                        $eventActionTypeField = $plField;
                        $actionTypeFieldId = $eventActionTypeField['bitrixCamelId']; //like PROPERTY_2119 
                    }
                    if ($plField['code'] === 'sales_history_crm') {
                        $companyIdField = $plField;
                        $companyIdFieldId = $companyIdField['bitrixCamelId']; //like PROPERTY_2119 

                    }
                    if ($plField['code'] === 'sales_history_manager_comment') {
                        $commentField = $plField;
                        $commentFieldId = $commentField['bitrixCamelId']; //like PROPERTY_2119 

                    }

                    if ($plField['code'] === 'sales_history_op_noresult_reason') {
                        $noresultReasonField = $plField;
                        $noresultReasonFieldId = $noresultReasonField['bitrixCamelId']; //like PROPERTY_2119 

                    }

                    if ($plField['code'] === 'sales_history_op_result_status') {
                        $resultStatusField = $plField;
                        $resultStatusFieldId = $resultStatusField['bitrixCamelId']; //like PROPERTY_2119 

                    }
                }
            }

            $url = $this->hook . '/batch';
            $method = 'lists.element.get';
            $key = 'history_list';
            $allResults = [];
            $lastId = null;

            do {
                // 🟢 Формируем параметры запроса с фильтрацией по `ID > lastId`
                $data = [
                    'IBLOCK_TYPE_ID' => 'lists',
                    'IBLOCK_ID' => $listId,
                    'filter' => [
                        $companyIdFieldId => '%' . $companyId . '%',
                    ],
                    'select' => [
                        $commentFieldId,
                        $actionFieldId,
                        $actionTypeFieldId,
                        $noresultReasonFieldId,
                        $resultStatusFieldId
                    ],
                    'order' => ['ID' => 'ASC'], // 🟢 Сортировка по ID
                ];

                if ($lastId) {
                    $data['filter']['>ID'] = $lastId;
                }

                // 🟢 Генерируем команду
                $command = $method . '?' . http_build_query($data);

                // 🟢 Делаем запрос
                $response = Http::post($url, [
                    'halt' => 0,
                    'cmd' => [$key => $command] // 🟢 Оборачиваем в массив, чтобы ключи совпадали
                ]);

                $responseData = $response->json();

                // 🟢 Проверяем, есть ли данные
                if (isset($responseData['result']['result'][$key]) && !empty($responseData['result']['result'][$key])) {
                    $batchResults = $responseData['result']['result'][$key];
                    $allResults = array_merge($allResults, $batchResults);
                    $lastId = end($batchResults)['ID'] ?? $lastId; // 🟢 Запоминаем последний ID
                }

                // 🟢 Проверяем наличие `result_next` для следующего запроса
                $next = $responseData['result']['result_next'][$key] ?? null;
            } while ($next !== null); // 🔄 Пока есть `result_next`, продолжаем

            // 🟢 Логируем ошибки, если есть
            if (!empty($responseData['result_error'])) {
                Log::channel('telegram')->error('❌ Ошибка Bitrix BATCH', [
                    'errors' => $responseData['result_error']
                ]);
            }

            // 🟢 Возвращаем данные API
            return APIOnlineController::getSuccess([
                'commands' => $command,
                'history' => $allResults,
            ]);
        } catch (\Throwable $th) {
            $errorMessages =  [
                'message'   => $th->getMessage(),
                'file'      => $th->getFile(),
                'line'      => $th->getLine(),
                'trace'     => $th->getTraceAsString(),
            ];
            return APIOnlineController::getError(
                $th->getMessage(),
                [
                    'list' => $this->portalKPIList,
                    '$batchResults' => $batchResults,
                    'error' => $errorMessages,
                    'currentActionsData' => $currentActionsData
                ]
            );
        }
    }


    public  function getNoresultCount($companyId, $userId)
    {

        $batchResults = null;
        $currentActionsData = [];
        $actionFieldId = null;
        try {
            // $domain = $request['domain'];


            // $companyId = $request['companyId'];
            $listId = $this->portalKPIList['bitrixId'];
            $listFields = $this->portalKPIList['bitrixfields'];
            $eventActionField = null;
            $actionFieldId = null;
            $resultActionItem = null;  //сoстоялся
            $resultActionItemId = null;

            $eventActionTypeField = null; // тип события
            $actionTypeFieldId = null;


            $actionTypePresentationItem = null; // презентация
            $actionTypePresentationItemId = null; //


            $actionTypeInProgressItem = null; //  в решении
            $actionTypeInProgressItemId = null; //

            $actionTypeMoneyItem = null; //  в оплате
            $actionTypeMoneyItemId = null; //


            $companyIdField = null;
            $companyIdFieldId = null;



            $responsibleField = null;
            $responsibleFieldId = null;


            $resultStatusField = null;
            $resultStatusFieldId = null;

            $resultStatusItem = null;
            $resultStatusItemId = null;

            $noResultStatusItem = null;
            $noResultStatusItemId = null;

            if (!empty($listFields)) {

                foreach ($listFields as $plField) {
                    if ($plField['code'] === 'sales_history_event_action') {
                        $eventActionField = $plField;
                        $actionFieldId = $eventActionField['bitrixCamelId']; //like PROPERTY_2119 
                        if (!empty($eventActionField) && !empty($eventActionField['items'])) {
                            foreach ($eventActionField['items'] as $item) {

                                if ($item['code'] === 'done') {
                                    $resultActionItem = $item;
                                    $resultActionItemId = $item['bitrixId'];
                                }
                            }
                        }
                    }
                    if ($plField['code'] === 'sales_history_event_type') {
                        $eventActionTypeField = $plField;
                        $actionTypeFieldId = $eventActionTypeField['bitrixCamelId']; //like PROPERTY_2119 

                        if (!empty($eventActionTypeField) && !empty($eventActionTypeField['items'])) {
                            foreach ($eventActionTypeField['items'] as $item) {

                                if ($item['code'] === 'presentation') {
                                    $actionTypePresentationItem = $item;
                                    $actionTypePresentationItemId = $item['bitrixId'];;
                                }

                                if ($item['code'] === 'call_in_progress') {
                                    $actionTypeInProgressItem = $item;
                                    $actionTypeInProgressItemId = $item['bitrixId'];;
                                }

                                if ($item['code'] === 'call_in_money') {
                                    $actionTypeMoneyItem = $item;
                                    $actionTypeMoneyItemId = $item['bitrixId'];;
                                }
                            }
                        }
                    }


                    if ($plField['code'] === 'sales_history_crm') {
                        $companyIdField = $plField;
                        $companyIdFieldId = $companyIdField['bitrixCamelId']; //like PROPERTY_2119 

                    }
                    if ($plField['code'] === 'sales_history_responsible') {
                        $responsibleField = $plField;
                        $responsibleFieldId = $responsibleField['bitrixCamelId']; //like PROPERTY_2119 

                    }

                    if ($plField['code'] === 'sales_history_op_noresult_reason') {
                        $noresultReasonField = $plField;
                        $noresultReasonFieldId = $noresultReasonField['bitrixCamelId']; //like PROPERTY_2119 

                    }

                    if ($plField['code'] === 'sales_history_op_result_status') {
                        $resultStatusField = $plField;
                        $resultStatusFieldId = $resultStatusField['bitrixCamelId']; //like PROPERTY_2119 
                        if (!empty($resultStatusField) && !empty($resultStatusField['items'])) {
                            foreach ($resultStatusField['items'] as $item) {

                                if ($item['code'] === 'op_call_result_yes') {
                                    $resultStatusItem = $item;
                                    $resultStatusItemId = $item['bitrixId'];
                                }
                                if ($item['code'] === 'op_call_result_no') {
                                    $noResultStatusItem = $item;
                                    $noResultStatusItemId = $item['bitrixId'];
                                    break;
                                }
                            }
                        }
                    }
                }
            }

            $url = $this->hook . '/batch';
            $method = 'lists.element.get';
            $resultKey = 'result';
            $presKey = 'pres';
            $progKey = 'prog';
            $moneyKey = 'money';
            $noresultKey = 'noresult';

            $resultResult = null;
            $presResult = null;
            $progResult = null;
            $moneyResult = null;
            $noresultResult = null;



            // 🟢 Формируем параметры запроса с фильтрацией по `ID > lastId`
            $resultData = [
                'IBLOCK_TYPE_ID' => 'lists',
                'IBLOCK_ID' => $listId,
                'filter' => [
                    $companyIdFieldId => '%' . $companyId . '%',
                    $responsibleFieldId => $userId,
                    $resultStatusFieldId => $resultStatusItemId,
                    $actionFieldId => $resultActionItemId
                ],

            ];
            $presentationData = [
                'IBLOCK_TYPE_ID' => 'lists',
                'IBLOCK_ID' => $listId,
                'filter' => [
                    $companyIdFieldId => '%' . $companyId . '%',
                    $responsibleFieldId => $userId,
                    $resultStatusFieldId => $resultStatusItemId,
                    $actionFieldId => $resultActionItemId,
                    $actionTypeFieldId => $actionTypePresentationItemId, //presentation

                ],

            ];
            $inProgressData = [
                'IBLOCK_TYPE_ID' => 'lists',
                'IBLOCK_ID' => $listId,
                'filter' => [
                    $companyIdFieldId => '%' . $companyId . '%',
                    $responsibleFieldId => $userId,
                    $resultStatusFieldId => $resultStatusItemId,
                    $actionFieldId => $resultActionItemId,
                    $actionTypeFieldId => $actionTypeInProgressItemId, //InProgress

                ],

            ];

            $moneyData = [
                'IBLOCK_TYPE_ID' => 'lists',
                'IBLOCK_ID' => $listId,
                'filter' => [
                    $companyIdFieldId => '%' . $companyId . '%',
                    $responsibleFieldId => $userId,
                    $resultStatusFieldId => $resultStatusItemId,
                    $actionFieldId => $resultActionItemId,
                    $actionTypeFieldId => $actionTypeMoneyItemId, //InProgress

                ],

            ];

            $noresultData = [
                'IBLOCK_TYPE_ID' => 'lists',
                'IBLOCK_ID' => $listId,
                'filter' => [
                    $companyIdFieldId => '%' . $companyId . '%',
                    $responsibleFieldId => $userId,
                    $resultStatusFieldId => $noResultStatusItemId
                ],

            ];


            // 🟢 Генерируем команду
            $commandResult = $method . '?' . http_build_query($resultData);
            $commandPres = $method . '?' . http_build_query($presentationData);
            $commandProg = $method . '?' . http_build_query($inProgressData);
            $commandMoney = $method . '?' . http_build_query($moneyData);
            $commandNoResult = $method . '?' . http_build_query($noresultData);

            // 🟢 Делаем запрос
            $response = Http::post($url, [
                'halt' => 0,
                'cmd' => [
                    $resultKey => $commandResult,
                    $noresultKey => $commandNoResult,
                    $presKey => $commandPres,
                    $progKey => $commandProg,
                    $moneyKey => $commandMoney,

                ] // 🟢 Оборачиваем в массив, чтобы ключи совпадали
            ]);

            $responseData = $response->json();

            // 🟢 Проверяем, есть ли данные
            if (isset($responseData['result']['result'][$resultKey]) && !empty($responseData['result']['result'][$resultKey])) {

                $resultResult = $responseData['result']['result_total'][$resultKey];
            }

            //pres
            if (isset($responseData['result']['result'][$presKey]) && !empty($responseData['result']['result'][$presKey])) {

                $presResult = $responseData['result']['result_total'][$presKey];
            }


            if (isset($responseData['result']['result'][$progKey]) && !empty($responseData['result']['result'][$progKey])) {

                $progResult = $responseData['result']['result_total'][$progKey];
            }

            if (isset($responseData['result']['result'][$moneyKey]) && !empty($responseData['result']['result'][$moneyKey])) {

                $moneyResult = $responseData['result']['result_total'][$moneyKey];
            }




            if (isset($responseData['result']['result'][$noresultKey]) && !empty($responseData['result']['result'][$noresultKey])) {

                $noresultResult = $responseData['result']['result_total'][$noresultKey];
            }


            // 🟢 Логируем ошибки, если есть
            if (!empty($responseData['result_error'])) {
                Log::channel('telegram')->error('❌ Ошибка Bitrix BATCH', [
                    'errors' => $responseData['result_error']
                ]);
            }

            // 🟢 Возвращаем данные API
            return APIOnlineController::getSuccess([
                'result' => [
                    // 'commands' => $command,
                    'noresultCount' => $noresultResult,
                    'resultCount' => $resultResult,
                    'presentationCount' => $presResult,
                    'inProgressCount' => $progResult,
                    'inMoneyCount' => $moneyResult,
                    // 'resultStatusField' => $resultStatusField,
                    // 'resultStatusFieldId' => $resultStatusFieldId,
                    // 'resultStatusItem' => $resultStatusItem,
                    // 'resultStatusItemId' => $resultStatusItemId,
                    // 'noResultStatusItem' => $noResultStatusItem,
                    // 'noResultStatusItemId' => $noResultStatusItemId,
                    // 'actionFieldId' => $actionFieldId,
                    // 'resultActionItem' => $resultActionItem,
                ]
            ]);
        } catch (\Throwable $th) {
            $errorMessages =  [
                'message'   => $th->getMessage(),
                'file'      => $th->getFile(),
                'line'      => $th->getLine(),
                'trace'     => $th->getTraceAsString(),
            ];
            return APIOnlineController::getError(
                $th->getMessage(),
                [
                    'list' => $this->portalKPIList,
                    '$batchResults' => $batchResults,
                    'error' => $errorMessages,
                    'currentActionsData' => $currentActionsData
                ]
            );
        }
    }
}
