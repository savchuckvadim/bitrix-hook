<?php

namespace App\Http\Controllers\Front\Calling;

use App\Http\Controllers\APIBitrixController;
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
            $currentCompany =  null;
            $currentDeal = null;
            $data = $request->all();
            $isFullData = false;
            $rqLinkesponse = null;

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


                if (!empty($sessionData)) {
                    if (!empty($sessionData['currentCompany'])) {
                        $currentCompany = $sessionData['currentCompany'];
                    }

                    if (!empty($sessionData['deals'])) {
                        $sessionDeals = $sessionData['deals'];

                        if (isset($sessionDeals['currentBaseDeals'])) {
                            if (
                                !empty($sessionDeals['currentBaseDeals']) &&
                                is_array($sessionDeals['currentBaseDeals'])

                            ) {
                                $currentDeal = $sessionDeals['currentBaseDeals'][0];
                            }
                        }

                        if (isset($sessionDeals['currentBaseDeal'])) {

                            if (!empty($sessionDeals['currentBaseDeal'])) {

                                $currentDeal =  $sessionDeals['currentBaseDeal'];
                            }
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
                    'filter' => [
                        'ENTITY_TYPE_ID' =>  4,
                        'ENTITY_ID' =>  $companyId,
                    ]
                ];
                $responseJson = Http::post($hook . $method, $rqGetData);
                $response =  $responseJson->json();

                if (!empty($currentDeal)) {
                    if (!empty($currentDeal['ID'])) {


                        $rqLinkGetData = [
                            'filter' => [
                                'ENTITY_TYPE_ID' =>  2,
                                'ENTITY_ID' =>  $currentDeal['ID'],
                            ]
                        ];
                        $rqLinkMethod = '/crm.requisite.link.get.json';
                        $rqLinkJson = Http::post($hook . $rqLinkMethod, $rqLinkGetData);
                        $rqLinkesponse = APIBitrixController::getBitrixRespone($rqLinkJson, 'rqLinkesponse');
                    }
                }



                return APIOnlineController::getSuccess(
                    [
                        'currentCompany' => $currentCompany,
                        'currentDeal' => $currentDeal,
                        'response' => $response,
                        'sessionKey' =>  $sessionKey,
                        'sessionData' =>  $sessionData,
                        'rqlink' =>  $rqLinkesponse,

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
