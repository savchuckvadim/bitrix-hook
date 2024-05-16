<?php

namespace App\Services\General;

use App\Http\Controllers\APIBitrixController;
use App\Http\Controllers\APIOnlineController;
use App\Http\Controllers\PortalController;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BitrixListService
{

    //smart
    static function setItem(
        $hook,
        $listBitrixId, // from portal db
        $fields, //fields values eith fields id's from portal db

    ) {

        $result = false;
        try {
            $method = '/lists.element.add.json';
            $url = $hook . $method;
            $nowDate = now();
            $data =  [
                'IBLOCK_TYPE_ID' => 'lists',
                'IBLOCK_ID' => $listBitrixId,
                'ELEMENT_CODE' => $listBitrixId. 'element1'.$nowDate,
                'FIELDS' => $fields
            ];

            Log::info('APRIL_HOOK list setItem', [

                'data' => $data
            ]);
            $response = Http::get($url, $data);
            // $responseData = $response->json();
            $responseData = APIBitrixController::getBitrixRespone($response, 'list service: setItem');
            if (isset($responseData)) {
                $result = $responseData;
            }
            Log::channel('telegram')->error('APRIL_HOOK list setItem', [

                'responseData' => $responseData
            ]);
            return $result;
        } catch (\Throwable $th) {
            return $result;
        }
    }
}
