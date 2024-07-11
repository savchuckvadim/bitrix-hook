<?php

namespace App\Http\Controllers\Front\Calling;

use App\Http\Controllers\APIController;
use App\Http\Controllers\APIOnlineController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\PortalController;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ReportSupplyController extends Controller
{
    public static function getSupplyForm(Request $request)
    {
        try {

            //реквизиты компании и текущую base deal
            
            $data = $request->all();
            $isFullData = false;

            if (
                !empty($data['domain']) &&
                isset($data['isFromTask']) &&
                isset($data['taskId']) &&
                isset($data['companyId']) 

            ) {
                $isFullData = true;
                $domain = $data['domain'];
                $isFromTask = $data['isFromTask'];
                $taskId = $data['taskId'];
                $companyId = $data['companyId'];
                // $userId = $data['userId'];
            }
            if ($isFullData) {
                if (!empty($isFromTask) && !empty($taskId)) {
                    $sessionKey = $domain . '_' . $taskId;
                } else {
                    $sessionKey = 'newtask_' . $domain  . '_' . $companyId;
                }
                $sessionData = FullEventInitController::getSessionItem($sessionKey);


                if (empty($sessionData)) {
                    if (!empty($sessionData['currentCompany'])) {
                        $currentCompany = $sessionData['currentCompany'];
                    }

                    if (!empty($sessionData['deals'])) {
                        $sessionDeals = $sessionData['deals'];
                        if (
                            is_array($sessionDeals['currentBaseDeals']) &&
                            !empty($sessionDeals['currentBaseDeals'])
                        ) {
                            $currentDeal = $sessionDeals['currentBaseDeals'][0];
                        }
                    }
                }


                $fullDomain = 'https://' . $domain  . '/';
                $method = '/crm.requisite.list.json';

                $portal = PortalController::getPortal($domain);
                $portal = $portal['data'];

                $webhookRestKey = $portal['C_REST_WEB_HOOK_URL'];
                $hook = $fullDomain . $webhookRestKey;


                $rqGetData = [
                    'ENTITY_TYPE_ID' =>  4,
                    'ENTITY_ID' =>  $companyId,
                ];
                $responseJson = Http::post($hook . $method, $rqGetData);
                $response =  $responseJson->json();

                return APIOnlineController::getSuccess(
                    [
                        'currentCompany' => $currentCompany,
                        'currentDeal' => $currentDeal,
                        'response' => $response,

                    ]

                );
            } else {

                return APIOnlineController::getError(
                    'is not full data',
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
