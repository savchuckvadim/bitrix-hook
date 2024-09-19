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

    public function sendFlowBatchRequest($commands)
    {
        $url = $this->hook . '/batch';
        $maxCommandsPerBatch = 50; // Максимальное количество команд на один batch запрос
        $batchRequests = array_chunk($commands, $maxCommandsPerBatch, true);
        $result = [
            // 'errors' => []
        ];
        $resultBatchCommands = [];
        Log::channel('telegram')->info('HOOK send', ['result return' => $commands]);

        foreach ($batchRequests as  $batchCommands) {

            foreach ($batchCommands as $key => $value) {
                if (!empty($key) && !empty($value)) {
                    $batchKey = $value['batchKey'];
                    Log::channel('telegram')->info('HOOK send', ['batchKey' => $batchKey]);
                    Log::channel('telegram')->info('HOOK send', ['isNeedUpdate' => $value['isNeedUpdate']]);

                    Log::channel('telegram')->info('HOOK send', ['command' => $value['command']]);

                    if (!empty($value['deal']) && !empty($value['dealId'])) {
                        if (!empty($value['isNeedUpdate'])) {
                            $resultBatchCommands[$batchKey] = $value['command'];
                        } else {
                            $result[$batchKey] = $value['dealId'];
                        }
                    } else {
                        $resultBatchCommands[$batchKey] = $value['command'];
                    }
                }
            }



            $response = Http::post($url, [
                'halt' => 0,
                'cmd' => $resultBatchCommands
            ]);
            $responseData = $response->json();
            // Log::channel('telegram')->info('HOOK send', ['result return' => $responseData['result']]);

            // print_r("eventsCommands");
            // print_r("<br>");
            // print_r($batchCommands);
            // print_r("<br>");
            // print_r($responseData);
            if (!empty($responseData['result'])) {
                // if (!empty($responseData['result']['result'])) {
                //     $result[$key] = $responseData['result']['result'];
                // } else {
                // $result[$key] = $responseData['result'];
                // }
                if (isset($responseData['result']['result'])) {
                    $searchedResult = $responseData['result']['result'];
                } else {
                    $searchedResult = $responseData['result'];
                }
                // Log::channel('telegram')->info('HOOK send', ['rsearchedResultn' => $searchedResult]);

                foreach ($searchedResult as $resKey => $resValue) {
                    // Log::channel('telegram')->info('HOOK send', ['result resKey' => $resKey]);
                    // Log::channel('telegram')->info('HOOK send', ['result resValue' => $resValue]);
                    $result[$resKey] = $resValue;
                    if (!empty($resValue['result'])) {
                        $result[$resKey] = $resValue['result'];
                    }
                }

                // Log::channel('telegram')->info('HOOK responseData', ['result' => $responseData['result']['result']]);
            }
            if (!empty($responseData['result_error'])) {
                // $result['errors'][$key] = $responseData['result_error'];
                print_r("<br>");
                print_r($key);
                print_r("<br>");
                print_r($responseData['result_error']);
                print_r("<br>");
            }
            // usleep(mt_rand(1000, 400000));
        };

        // if (isset($result[0])) {
        //     $result = $result[0];
        // }

        // if (isset($result['result'])) {
        //     $result = $result['result'];
        // }4 Log::info('HOOK sendFlowBatchRequest', ['result resultBatchCommands' => $resultBatchCommands]);
        // Log::channel('telegram')->info('HOOK sendFlowBatchRequest', ['resultBatchCommands' => $resultBatchCommands]);

        // Log::channel('telegram')->info('HOOK send', ['result return' => $result]);
        return $result;
    }


    public function sendGeneralBatchRequest($commands)
    {
        $url = $this->hook . '/batch';
        $maxCommandsPerBatch = 50; // Максимальное количество команд на один batch запрос
        $batchRequests = array_chunk($commands, $maxCommandsPerBatch, true);
        $result = [
            // 'errors' => []
        ];

        foreach ($batchRequests as $key => $batchCommands) {
            $response = Http::post($url, [
                'halt' => 0,
                'cmd' => $batchCommands
            ]);
            $responseData = $response->json();
            // print_r("<br>");
            // print_r($key);
            // print_r("<br>");
            // print_r($batchCommands);
            // print_r("<br>");
            // print_r($responseData);
            if (isset($responseData['result'])) {
                $result[$key] = $responseData['result'];
                Log::info('HOOK TEST Service BATCH key', [
                    'result' => $result[$key]
    
    
                ]);
                Log::channel('telegram')->info('HOOK TEST Service BATCH key', [
                   'result' => $result[$key]
    
    
                ]);
                Log::info('HOOK TEST Service BATCH', [
                    'result' => $result
    
    
                ]);
                Log::channel('telegram')->info('HOOK TEST Service BATCH', [
                    'result' => $result
    
    
                ]);

                if (isset($responseData['result']['result'])) {
                    $result[$key] = $responseData['result']['result'];

                    Log::channel('telegram')->info('HOOK TEST Service BATCH', [
                        'responseData result result' => $result[$key]
        
        
                    ]);
                }
                if (!empty($responseData['result']['result'][0])) {
                    $result[$key] = $responseData['result']['result'][0];
                }
                // print_r($result[$key]);
                // print_r("<br>");
            }
            if (!empty($responseData['result_error'])) {
                // $result['errors'][$key] = $responseData['result_error'];
                print_r("<br>");
                print_r($key);
                print_r("<br>");
                print_r($responseData['result_error']);
                print_r("<br>");
            }
            usleep(mt_rand(1000, 400000));
        };

        // if (isset($result[0])) {
        //     $result = $result[0];
        // }

        // if (isset($result['result'])) {
        //     $result = $result['result'];
        // }

        // print_r("<br>");
        // print_r($result);
        return $result;
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
            $rand = mt_rand(100000, 400000); // случайное число от 300000 до 900000 микросекунд (0.3 - 0.9 секунды)
            usleep($rand);
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


    public function sendBatch($commands)
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

            $result = $responseData;
        };
        Log::info('HOOK BATCH sendBatch', ['result' => $result, '$url' => $url]);
        Log::channel('telegram')->info('HOOK BATCH sendBatch', ['result' => $result]);
        return $result;
    }

    static function batchCommand(
        $fieldsData,
        $entity,
        $entityId = null,
        $method, //update | add

    ) {

        $currentMethod = 'crm.' . $entity . '.' . $method;
        $data = ['FIELDS' => $fieldsData];
        if (!empty($entityId)) {
            $data['ID'] = $entityId;
        }

        return $currentMethod . '?' . http_build_query($data);
    }
}
