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

    
    static function setItem(
        $hook,
        $listBitrixId, // from portal db
        $fields, //fields values eith fields id's from portal db
        $elementCode = null

    ) {

        $result = false;



        try {
            $method = '/lists.element.add.json';
            $url = $hook . $method;
            $nowDate = now();

            $code = $listBitrixId . '_' . $nowDate;
            if (!empty($elementCode)) {
                $code =  $elementCode;
            }
            $data =  [
                'IBLOCK_TYPE_ID' => 'lists',
                'IBLOCK_ID' => $listBitrixId,
                'ELEMENT_CODE' => $code,
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

    static function updateItem(
        $hook,
        $listBitrixId, // from portal db
        $fields, //fields values eith fields id's from portal db
        $elementCode = null

    ) {

        $result = false;



        try {
            $method = '/lists.element.update.json';
            $url = $hook . $method;
            $nowDate = now();

            $code = $listBitrixId . '_' . $nowDate;
            if (!empty($elementCode)) {
                $code =  $elementCode;
            }
            $data =  [
                'IBLOCK_TYPE_ID' => 'lists',
                'IBLOCK_ID' => $listBitrixId,
                'ELEMENT_CODE' => $code,
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

    static function getItem(
        $hook,
        $listBitrixId, // from portal db
        $elementCode

    ) {

        $result = false;



        try {
            $method = '/lists.element.get.json';
            $url = $hook . $method;
            $nowDate = now();

            $code = $listBitrixId . '_' . $nowDate;
            if (!empty($elementCode)) {
                $code =  $elementCode;
            }
            $data =  [
                'IBLOCK_TYPE_ID' => 'lists',
                'IBLOCK_ID' => $listBitrixId,
                'ELEMENT_CODE' => $code,

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
