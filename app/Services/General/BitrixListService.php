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
            sleep(1);

            $nowDate = now();
            $uniqueHash = md5(uniqid(rand(), true));


            $code = $uniqueHash;
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
            // Log::channel('telegram')->error('APRIL_HOOK pres list service: setItem', [
            //     'IBLOCK_ID' => $listBitrixId,
            //     'ELEMENT_CODE' => $code,
            //     'responseData' => $responseData,



            // ]);
            // Log::channel('telegram')->error('APRIL_HOOK pres list service: setItem', [

            //     'FIELDS' => $fields


            // ]);
            return $result;
        } catch (\Throwable $th) {
            return $result;
        }
    }
    public static function getBatchCommandSetItem($hook, $listBitrixId, $fields, $elementCode = null)
    {
        $uniqueHash = md5(uniqid(rand(), true));
        $secondUniqueHash = md5(uniqid(rand(1, 5), true));

        $fullCode = $listBitrixId . '_' . $uniqueHash;
        $code = $elementCode ?:  $fullCode;

        $data = [
            'IBLOCK_TYPE_ID' => 'lists',
            'IBLOCK_ID' => $listBitrixId,
            // 'IBLOCK_CODE' => $listBitrixId,
            'ELEMENT_CODE' => $code,
            'FIELDS' => $fields
        ];




        return 'lists.element.add?' . http_build_query($data);
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
            $responseData = APIBitrixController::getBitrixRespone($response, 'list service: updateItem');
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
            $responseData = APIBitrixController::getBitrixRespone($response, 'list service: getItem');
            if (isset($responseData)) {
                $result = $responseData;
            }

            return $result;
        } catch (\Throwable $th) {
            return $result;
        }
    }

    static function getList(
        $hook,
        $listBitrixId, // from portal db
        $filter

    ) {

        $result = false;



        try {
            $method = '/lists.element.get.json';
            $url = $hook . $method;

            $data =  [
                'IBLOCK_TYPE_ID' => 'lists',
                'IBLOCK_ID' => $listBitrixId,
                'filter' => $filter,

            ];


            $response = Http::get($url, $data);
            // $responseData = $response->json();
            $responseData = APIBitrixController::getBitrixRespone($response, 'list service: get list');
            if (isset($responseData)) {
                $result = $responseData;
            }

            return $result;
        } catch (\Throwable $th) {
            return $result;
        }
    }

    static function getListFieldsGet(
        $hook,
        $listBitrixId, // from portal db
        // $filter

    ) {

        $result = false;



        try {
            $method = '/lists.field.get.json';
            $url = $hook . $method;

            $data =  [
                'IBLOCK_TYPE_ID' => 'lists',
                'IBLOCK_ID' => $listBitrixId,

            ];


            $response = Http::get($url, $data);
            // $responseData = $response->json();
            $responseData = APIBitrixController::getBitrixRespone($response, 'list service: get list');
            if (isset($responseData)) {
                $result = $responseData;
            }

            return $result;
        } catch (\Throwable $th) {
            return $result;
        }
    }
}
