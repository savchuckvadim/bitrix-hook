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

                    if ($plField['code'] === 'op_noresult_reason') {
                        $noresultReasonField = $plField;
                        $noresultReasonFieldId = $commentField['bitrixCamelId']; //like PROPERTY_2119 

                    }

                    if ($plField['code'] === 'op_result_status') {
                        $resultStatusField = $plField;
                        $resultStatusFieldId = $commentField['bitrixCamelId']; //like PROPERTY_2119 

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

                if (!empty($responseData['result']['result'][$key][0])) {
                    Log::channel('telegram')->info('📡 Bitrix API Response', [
                        'history' => $responseData['result']['result'][$key][0]['ID'],
                        'next' => $responseData['result']['result_next'],
                    ]);
                } else {
                    Log::channel('telegram')->info('📡 Bitrix API Response', [
                        'history' => $responseData['result'],
                        'next' => $responseData['result']['result_next'],
                    ]);
                }

                // return APIOnlineController::getSuccess([
                //     'commands' => $command,
                //     'history' => $responseData['result']['result'][$key],
                //     'next' => $responseData['result']['result_next'],
                // ]);
                // 🟢 Проверяем, есть ли данные
                if (isset($responseData['result']['result'][$key]) && !empty($responseData['result']['result'][$key])) {
                    $batchResults = $responseData['result']['result'][$key];
                    $allResults = array_merge($allResults, $batchResults);
                    $lastId = end($batchResults)['ID'] ?? $lastId; // 🟢 Запоминаем последний ID
                }
                Log::channel('telegram')->info('📡 Bitrix API Response', [
                    'lastId' => $lastId

                ]);
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
}
