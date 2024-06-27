<?php

namespace App\Http\Controllers\Front\Calling;

use App\Http\Controllers\APIOnlineController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\PortalController;
use App\Services\HookFlow\BitrixEntityFlowService;

use Illuminate\Http\Request;
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
                $sessionKey = $domain . '' . $currentTask['id'];
                $sessionValue = [
                    'currentCompany' => $currentCompany,
                    'btxDeals' => $btxDeals,
                    'portal' => $portal,
                    'currentTask' => $currentTask,
                ];


                session([$sessionKey => $sessionValue]);

                $session = session()->all();


                return APIOnlineController::getSuccess(
                    [
                        'result' => 'success',
                        'message' => 'sission init !',
                        'sessionKey' => $sessionKey,
                        'all' => $session

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

                $sessionKey = $domain . '' . $currentTaskId;

                $value = Session::get($sessionKey);
                $session = session()->all();
                return APIOnlineController::getSuccess(
                    [
                        'result' => $value,
                        'message' => 'from session !',
                        'sessionKey' => $sessionKey,
                        'all' => $session

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
}
