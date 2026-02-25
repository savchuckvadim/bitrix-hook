<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class PortalController extends Controller
{
    public static function getPortal($domain)
    {
        $result = null;
        try {
            $requestPortalData = [
                'domain' => $domain
            ];
            $cacheKey = 'portal_' . $domain;
            $cachedPortalData = Cache::get($cacheKey);
            if (!empty($cachedPortalData)) {

                $result = $cachedPortalData;
            } else {
                $result = APIOnlineController::online('post', 'getportal', $requestPortalData, 'portal');
                Cache::put($cacheKey, $result, now()->addMinutes(15)); // Кешируем данные портала
            }
            Log::info('API ONLINE: getPortal', ['domain' => $domain]);
            Log::info('API ONLINE: getPortal', ['portal' => $result]);
            // if (!empty($result['data'])) {
            //     if (!empty($result['data']['C_REST_WEB_HOOK_URL'])) {
            //         Log::channel('telegram')->info('TEST PORTAL GET HOOK', [
            //             ['$portal' => $result['data']['C_REST_WEB_HOOK_URL']]
            //         ]);
            //     }
            // }

            // return APIOnlineController::getResponse($portalsRespone['resultCode'], $portalsRespone['message'], $portalsRespone['data']);
            return $result;
        } catch (\Throwable $th) {
            $errorData = [
                'message'   => $th->getMessage(),
                'file'      => $th->getFile(),
                'line'      => $th->getLine(),
                'trace'     => $th->getTraceAsString(),
            ];
            Log::error('API ONLINE: Exception caught', $errorData);
            return $result;
        }
    }


    public static function getHook($domain)
    {
        $result = null;
        try {
            $portal = PortalController::getPortal($domain);
            $portal = $portal['data'];
            $webhookRestKey = $portal['C_REST_WEB_HOOK_URL'];
            $number = random_int(1, 3);
            if ($number == 1) {
                $webhookRestKey = $portal['C_REST_CLIENT_ID'];
            } elseif ($number == 2) {
                $webhookRestKey = $portal['C_REST_CLIENT_SECRET'];
            } else {
                $webhookRestKey = $portal['C_REST_WEB_HOOK_URL'];
            }


            $hook = 'https://' . $domain  . '/' . $webhookRestKey;

            // if ($domain == 'gsirk.bitrix24.ru') {
            //     Log::channel('telegram')->info('TEST PORTAL GET HOOK', [
            //         ['$random' => $webhookRestKey]
            //     ]);
            // }

            // return APIOnlineController::getResponse($portalsRespone['resultCode'], $portalsRespone['message'], $portalsRespone['data']);
            return $hook;
        } catch (\Throwable $th) {
            $errorData = [
                'message'   => $th->getMessage(),
                'file'      => $th->getFile(),
                'line'      => $th->getLine(),
                'trace'     => $th->getTraceAsString(),
            ];
            Log::error('API HOOK: Exception caught', $errorData);
            return $result;
        }
    }
}
