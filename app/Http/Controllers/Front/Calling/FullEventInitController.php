<?php

namespace App\Http\Controllers\Front\Calling;

use App\Http\Controllers\APIBitrixController;
use App\Http\Controllers\APIOnlineController;
use App\Http\Controllers\BitrixHookController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\PortalController;
use App\Services\HookFlow\BitrixEntityFlowService;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Session;

class FullEventInitController extends Controller
{
    public static function getEventTasks(Request $request)
    {
        $resultTasks = [];
        $domain = $request->domain;
        $userId = $request->userId;
        $placement = $request->placement;


        $portal = PortalController::getPortal($domain);
        $portal = $portal['data'];
        $webhookRestKey = $portal['C_REST_WEB_HOOK_URL'];
        $hook = 'https://' . $domain  . '/' . $webhookRestKey;
        $method = '/tasks.task.list.json';
        $url = $hook . $method;

        try {




            $tasksGroupId = FullEventInitController::getCallingGroupId($portal);
            $crmItems = [];


            if ($placement) {

                $placamentData = FullEventInitController::getPlacement($placement);

                if (!empty($placamentData['type']) && !empty($placamentData['id'])) {

                    if ($placamentData['type'] === "LEAD") {
                        $crmItems = ['L_' . $placamentData['id']];
                    } else  if ($placamentData['type'] === "COMPANY") {
                        $crmItems = ['CO_' . $placamentData['id']];
                    }
                }
            }

            if ($hook) {

                if (isset($userId['ID'])) {

                    $userId = $userId['ID'];
                }



                $data = [
                    'filter' => [
                        // '>DEADLINE' => $start,
                        // '<DEADLINE' => $finish,
                        'RESPONSIBLE_ID' => $userId,
                        // 'GROUP_ID' => $tasksGroupId,
                        '!=STATUS' => 5, // Исключаем задачи со статусом "завершена"
                        'UF_CRM_TASK' => $crmItems,
                    ],
                    'select' => [
                        'ID',
                        'UF_CRM_TASK',
                        'TITLE',
                        'DATE_START',
                        'CREATED_DATE',
                        'CHANGED_DATE',
                        'CLOSED_DATE',

                        'DEADLINE',
                        'PRIORITY',
                        'MARK',
                        'GROUP_ID',

                        'CREATED_BY',
                        'STATUS_CHANGED_BY',
                        'REAL_STATUS',
                        'STATUS',
                        'STAGE_ID',
                        'RESPONSIBLE_ID',
                        'CREATED_BY',
                        'TITLE',

                    ],

                    // 'RESPONSIBLE_LAST_NAME' => $userId,
                    // 'GROUP_ID' => $date,
                ];

                $response = Http::get($url, $data);

                $bitrixResult = APIBitrixController::getBitrixRespone($response, 'getCallingTasksReport');

                if (!empty($bitrixResult)) {
                    if (isset($bitrixResult['tasks'])) {
                        $resultTasks = $response['result']['tasks'];
                        return APIOnlineController::getSuccess(

                            [
                                'tasks' => $resultTasks,
                                '$response' => $response,
                                '$tasksGroupId' => $tasksGroupId,
                                'data' => $data,
                                'RESPONSIBLE_ID' => $userId,
                                '$url' => $url



                            ]
                        );
                    }
                }

                return APIOnlineController::getError(
                    $response['error_description'],
                    [
                        'response' => $response,
                        // 'date' => $date,
                        // 'data' => $data,
                        'RESPONSIBLE_ID' => $userId,
                        '$hook' => $hook,
                        '$url' => $url

                    ]

                );
            } else {
                return APIOnlineController::getError(
                    'hook not found',
                    [
                        'hook' => $hook
                    ]
                );
            }
        } catch (\Throwable $th) {
            $errorData = [
                'message'   => $th->getMessage(),
                'file'      => $th->getFile(),
                'line'      => $th->getLine(),
                'trace'     => $th->getTraceAsString(),
            ];

            return APIOnlineController::getError(
                'getCallingTasks',
                $errorData
            );
        }
    }

    public static function getCallingGroupId($portal)
    {
        $callingGroupId = 28;
        try {

            $callingGroups = $portal['callingGroups'];
            foreach ($callingGroups as $group) {
                if (isset($group['name']) && isset($group['bitrixId'])) {

                    if ($group['name'] === 'calling') {
                        $callingGroupId = $group['bitrixId'];
                    }
                }
            }

            return  $callingGroupId;
        } catch (\Throwable $th) {
            return  $callingGroupId;
        }
    }

    public static function getPlacement($placement)
    {
        $result = [
            'type' => '',
            'id' => null
        ];
        if (!empty($placement)) {
            if (isset($placement['placement']) && isset($placement['options']['ID'])) {
                $placementType = $placement['placement'];
                $result['id'] = $placement['options']['ID'];

                if (strpos($placementType, "LEAD") !== false) {
                    $result['type'] = "LEAD";
                } else if (strpos($placementType, "COMPANY") !== false) {

                    $result['type'] = "COMPANY";
                }
            }
        }
        return $result;
    }


    public static function fullEventSessionInit(Request $request)
    {
        try {

            $isFullData = true;
            $currentTask = false;
            if (isset($request->currentTask)) {
                if (isset($request->currentTask['id'])) {
                    $currentTask = $request->currentTask;
                }
            } else {
                $isFullData = false;
            }
            if (isset($request->domain)) {
                $domain = $request->domain;
            } else {
                $isFullData = false;
            }

            if ($isFullData) {
                $portal = PortalController::getPortal($domain);
                $portal = $portal['data'];
                $webhookRestKey = $portal['C_REST_WEB_HOOK_URL'];
                $hook = 'https://' . $domain  . '/' . $webhookRestKey;
                // $currentCompany = BitrixGeneralService::getEntity($hook, 'company', $companyId);


                //from task - получаем из task компании и сделки разных направлений

                $currentBtxEntities =  BitrixEntityFlowService::getEntities(
                    $hook,
                    $currentTask,
                );
                if (!empty($currentBtxEntities)) {
                    if (!empty($currentBtxEntities['companies'])) {
                        $currentCompany = $currentBtxEntities['companies'][0];
                    }
                    if (!empty($currentBtxEntities['deals'])) {
                        $btxDeals = $currentBtxEntities['deals'];
                    }
                }
                $sessionKey = $domain . '_' . $currentTask['id'];
                
                $sessionValue = [
                    // 'currentCompany' => $currentCompany,
                    // 'btxDeals' => $btxDeals,
                    // 'portal' => $portal,
                    'currentTask' => $currentTask,
                ];


                $hashedKey = md5($sessionKey);
                // $hashedKey = str_replace('.', '_', $sessionKey);
                // $hashedKey = str_replace('-', '_', $hashedKey);

                // Сериализация данных в JSON и сохранение в Redis
                Redis::set($hashedKey, json_encode($sessionValue));

                // Установка времени жизни данных (например, 30 минут)
                Redis::expire($hashedKey, 1800);
                $keys = Redis::keys('*');


                // Log::channel('telegram')
                //     ->info(
                //         'Session saved',
                //         [
                //             'id' => $keys,
                //             'hashedKey' => $hashedKey
                //         ]
                //     );

                return APIOnlineController::getSuccess(
                    [
                        'result' => 'success',
                        'message' => 'sission init !',
                        'sessionKey' => $hashedKey,
                        // 'all' => $session,
                        'keys' => $keys,
                    ]

                );
            } else {

                return APIOnlineController::getError(
                    'session dont init! is not full data',
                    [
                        'rq' => $request->all()

                    ]

                );
            }
        } catch (\Throwable $th) {
            return APIOnlineController::getError(
                $th->getMessage(),
                [
                    'task' => [
                        'message' => 'success'
                    ],
                    'rq' => $request->all()

                ]

            );
        }
    }
    public static function sessionGet(Request $request)
    {
        try {

            $isFullData = true;
            $currentTaskId = false;
            $domain =  false;
            if (isset($request->currentTaskId)) {
                $currentTaskId = $request->currentTaskId;
            } else {
                $isFullData = false;
            }
            if (isset($request->domain)) {
                $domain = $request->domain;
            } else {
                $isFullData = false;
            }

            if ($isFullData) {
                // $session = session()->all();
                $sessionKey = $domain . '_' . $currentTaskId;
                $hashedKey = md5($sessionKey);

                $value = Redis::get($hashedKey);

                // Десериализация данных из JSON
                $data = json_decode($value, true);

                $keys = Redis::keys('*');

                Log::channel('telegram')
                    ->info(
                        'Session get',
                        [
                            'id' => $keys,
                            'data' => $data
                        ]
                    );

                return APIOnlineController::getSuccess(
                    [
                        'result' => $data,
                        'message' => 'from session !',
                        'sessionKey' => $hashedKey,
                        // 'all' => $session,
                        'currentTaskId' => $currentTaskId

                    ]

                );
            } else {

                return APIOnlineController::getError(
                    'session dont init! is not full data',
                    [
                        'rq' => $request->all()

                    ]

                );
            }
        } catch (\Throwable $th) {
            return APIOnlineController::getError(
                $th->getMessage(),
                [
                    'task' => [
                        'message' => 'success'
                    ],
                    'rq' => $request->all()

                ]

            );
        }
    }

    public static function setSessionItem($key, $data)
    {

        try {

            $hashedKey = md5($key);

            // Сериализация данных в JSON и сохранение в Redis
            Redis::set($hashedKey, json_encode($data));

            // Установка времени жизни данных (например, 30 минут)
            Redis::expire($hashedKey, 1800);
            // $keys = Redis::keys('*');



            return [
                'result' => 'success',
                'message' => 'sission init !',
                'sessionKey' => $hashedKey,
                // 'all' => $session,
                // 'keys' => $keys,
            ];
        } catch (\Throwable $th) {
            return null;
        }
    }
    public static function getSessionItem($key, $itemKey = null)
    {
        $result = null;
        try {


            $hashedKey = md5($key);

            $value = Redis::get($hashedKey);

            // Десериализация данных из JSON
            $data = json_decode($value, true);
            if (!empty($itemKey) && isset($data[$itemKey])) {
                $data = $data[$itemKey];
            }
            $result = $data;

            return $result;
        } catch (\Throwable $th) {
            return $result;
        }
    }
}
