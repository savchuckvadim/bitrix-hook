<?php

namespace App\Http\Controllers\Front\Calling;

use App\Http\Controllers\APIOnlineController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\PortalController;
use App\Services\HookFlow\BitrixEntityFlowService;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Session;

class FullEventInitController extends Controller
{
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


                Log::channel('telegram')
                    ->info(
                        'Session saved',
                        [
                            'id' => $keys,
                            'hashedKey' => $hashedKey
                        ]
                    );

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
