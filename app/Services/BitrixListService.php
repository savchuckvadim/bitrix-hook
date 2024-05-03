<?php

namespace App\Services;

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

            $data =  [
                'IBLOCK_TYPE_ID' => 'lists',
                'IBLOCK_ID' => $listBitrixId,
                'ELEMENT_CODE' => 'element1',
                'FIELDS' => $fields
            ];


            $response = Http::get($url, $data);
            // $responseData = $response->json();
            $responseData = APIBitrixController::getBitrixRespone($response, 'list service: setItem');
            if (isset($responseData)) {
                $result = $responseData;
            }

            return $result;
        } catch (\Throwable $th) {
            return $result;
        }
    }
}
