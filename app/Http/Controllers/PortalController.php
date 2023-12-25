<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PortalController extends Controller
{
    public static function getPortal($domain)
    {
        try {
            $requestPortalData = [
                'domain' => $domain
            ];
            $portalsRespone = APIOnlineController::online('post', 'getportal', $requestPortalData, 'portal');

            // return APIOnlineController::getResponse($portalsRespone['resultCode'], $portalsRespone['message'], $portalsRespone['data']);
            return $portalsRespone;
        } catch (\Throwable $th) {
            Log::error('API ONLINE: Exception caught', [
                'message'   => $th->getMessage(),
                'file'      => $th->getFile(),
                'line'      => $th->getLine(),
                'trace'     => $th->getTraceAsString(),
            ]);
            return APIOnlineController::getResponse(1, $th->getMessage(), null);
        }
    }
}
