<?php

namespace App\Services\General;

use App\Http\Controllers\APIBitrixController;
use App\Http\Controllers\APIOnlineController;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BitrixTimeLineService
{
    protected $hook;

    public function __construct($hook)
    {
        $this->hook = $hook;
    }

    public function setTimeline($resultText, $entityType, $entityId)
    {


        try {
            $hook = $this->hook; // Предполагаем, что функция getHookUrl уже определена

            $method = '/crm.timeline.comment.add';

            $url = $hook . $method;
            $fields = [
                "ENTITY_ID" => $entityId,
                "ENTITY_TYPE" => $entityType,
                "COMMENT" => $resultText
            ];
            $data = [
                'fields' => $fields
            ];
            // Log::channel('telegram')->info('HOOK TIME LINE', ['data' => $data]);

            // Log::info('HOOK TIME LINE', ['data' => $data]);
            $responseBitrix = Http::get($url, $data);
            $responseData = APIBitrixController::getBitrixRespone($responseBitrix, 'TimeLine Service: setTimeline');
       
            // Log::channel('telegram')->info('HOOK TIME LINE', ['responseData' => $responseData]);

            // Log::info('HOOK TIME LINE', ['responseData' => $responseData]);

            return $responseData;


        } catch (\Throwable $th) {
            return null;
        }
    }


    public function setTimelineBatchCommand($resultText, $entityType, $entityId)
    {


        try {
            $hook = $this->hook; // Предполагаем, что функция getHookUrl уже определена

            $method = '/crm.timeline.comment.add';

            $url = $hook . $method;
            $fields = [
                "ENTITY_ID" => $entityId,
                "ENTITY_TYPE" => $entityType,
                "COMMENT" => $resultText
            ];
            $data = [
                'fields' => $fields
            ];
            // Log::channel('telegram')->info('HOOK TIME LINE', ['data' => $data]);

            // Log::info('HOOK TIME LINE', ['data' => $data]);
            $responseBitrix = Http::get($url, $data);
            $responseData = APIBitrixController::getBitrixRespone($responseBitrix, 'TimeLine Service: setTimeline batch');
       
            // Log::channel('telegram')->info('HOOK TIME LINE', ['responseData' => $responseData]);

            // Log::info('HOOK TIME LINE', ['responseData' => $responseData]);

            return $method . '?' . http_build_query($data);


        } catch (\Throwable $th) {
            return '';
        }
    }
}
