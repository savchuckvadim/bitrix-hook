<?php

namespace App\Services\General;

use App\Http\Controllers\APIBitrixController;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BitrixBatchService
{
    protected $hook;

    public function __construct($hook)
    {
        $this->hook = $hook;
    }

    public function sendBatchRequest($commands)
    {
        $url = $this->hook . '/batch';
        $maxCommandsPerBatch = 50; // Максимальное количество команд на один batch запрос
        $batchRequests = array_chunk($commands, $maxCommandsPerBatch, true);
        $result = [
            // 'errors' => []
        ];

        foreach ($batchRequests as $batchCommands) {
            $response = Http::post($url, [
                'halt' => 0,
                'cmd' => $batchCommands
            ]);
            $responseData = $response->json();

            if (isset($responseData['result']['result_total']) && count($responseData['result']['result_total']) > 0) {
                foreach ($responseData['result']['result_total'] as $key => $kpiCount) {

                    $result[$key] = $kpiCount;
                }
            } else {
                // array_push($result['errors'], $responseData);
                // return APIController::getError('batch result not found', $responseData);
            }
            sleep(0.1);
        };

        return $result;
    }
    public function processBatchResults($department, $currentActionsData, $batchResponseData)
    {
        $usersKPI = [];

        foreach ($department as $user) {
            $userName = $user['LAST_NAME'] . ' ' . $user['NAME'];
            $userKPI = [
                'user' => $user,
                'userName' => $userName,
                'kpi' => [],
                'callings' => []
            ];
            foreach ($currentActionsData as $currentAction) {
                $code = $currentAction['code'];

                $code = $currentAction['code'];
                $innerCode = $currentAction['innerCode'];
                if (strpos($innerCode, 'call') === false) {  //только не звонки
                    $kpiKey = "user_{$user['ID']}_action_{$code}";
                    $count = 0;
                    foreach ($batchResponseData as $cmdKey => $cmdResult) {
                        if ($cmdKey == $kpiKey) {
                            $count = $cmdResult;
                        }
                    }

                    array_push($userKPI['kpi'], [
                        'id' => $code,
                        'action' =>  $currentAction,
                        'count' =>  $count,
                        'items' => []
                    ]);
                } else {
                    if ((strpos($code, 'xo') === false) &&
                        (strpos($code, 'call_in_progress') === false) &&
                        (strpos($code, 'call_in_money') === false)
                    ) {
                        //взять только звонок без прогресс и моней но использовать массив типов - всех звонков
                        $kpiKey = "user_{$user['ID']}_action_{$code}";
                        $count = 0;
                        foreach ($batchResponseData as $cmdKey => $cmdResult) {
                            if ($cmdKey == $kpiKey) {
                                $count = $cmdResult;
                            }
                        }

                        array_push($userKPI['kpi'], [
                            'id' => $code,
                            'action' =>  $currentAction,
                            'count' =>  $count,
                            'items' => []
                        ]);
                    }
                }
            }

            array_push($usersKPI, $userKPI);
        }

        // Перебор всех результатов в ответе от batch запроса
        // foreach ($batchResponseData as $cmdKey => $cmdResult) {
        //     // Разбиваем ключ команды, чтобы получить ID пользователя и ID действия
        //     list($userPrefix, $userId, $actionPrefix, $actionId) = explode('_', $cmdKey);

        //     // Находим информацию о пользователе и название действия
        //     $user = $department[array_search($userId, array_column($department, 'ID'))];
        //     $userName = $user['LAST_NAME'] . ' ' . $user['NAME'];
        //     $actionTitle = $currentActionsData[$actionId];

        //     // Подсчет результатов
        //     $count = isset($cmdResult) ? $cmdResult : 0;

        //     // Формирование структуры данных для пользователя и его KPI
        //     if (!isset($usersKPI[$userId])) {
        //         $usersKPI[$userId] = [
        //             'user' => $user,
        //             'userName' => $userName,
        //             'kpi' => []
        //         ];
        //         foreach ($currentActionsData as $actId => $actionTitle) {

        //             array_push($usersKPI[$userId]['kpi'], [
        //                 'id' => $actId,
        //                 'action' =>  $actionTitle,
        //                 'count' =>  0
        //             ]);
        //         }
        //         foreach ($usersKPI[$userId]['kpi'] as $initialKpiItem) {
        //             if ($initialKpiItem['id'] === $actionId) {
        //                 $initialKpiItem['count'] = $count;
        //             }
        //         }
        //         // $usersKPI[$userId]['kpi'][] = [
        //         //     'action' => $actionTitle,
        //         //     'count' => $count
        //         // ];
        //     }
        // }
        // array_push($usersKPI, $batchResponseData['errors']);
        return $usersKPI; // Возвращаем переиндексированный массив пользователей и их KPI
    }
}
