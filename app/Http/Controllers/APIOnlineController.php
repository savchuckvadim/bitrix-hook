<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

const BASE_URL = 'https://april-online.ru/api';
class APIOnlineController extends Controller
{
    public static function online($method, $endpoint, $requestData, $dataname)
    {
        try {
            $portalResponse = Http::withHeaders([
                'X-Requested-With' => 'XMLHttpRequest',
                'X-API-KEY' => env('API_KEY')
            ])->$method(
                BASE_URL . '/' . $endpoint,
                $requestData
            );


            if ($portalResponse->successful()) {
                $data = $portalResponse->json();

                if ($data['resultCode'] == 0 && $data[$dataname]) {
                    return [
                        'resultCode' => 0,
                        'message' => 'success',
                        'data' => $data[$dataname],
                    ];
                } else {
                    Log::error('APRIL_HOOK: portal $dataname was not found !', [
                        $portalResponse
                    ]);
                    Log::channel('telegram')->error(
                        'APRIL_HOOK online ERROR',[
                            'online Response' => $portalResponse,
                            'method' => $method
                            
                        ]);

                    return [
                        'resultCode' => 1,
                        'message' => 'HOOK: invalid data',
                        'data' => $data
                    ];
                }
            } else {
                Log::error('API ONLINE: ONLINE: Ошибка при запросе к API. portalResponse', [
                    $portalResponse
                ]);
                return [
                    'resultCode' => 1,
                    'message' => 'ONLINE: Ошибка при запросе к API.',

                ];
            }
        } catch (\Throwable $th) {
            Log::error('APRIL_HOOK: API ONLINE: Exception caught', [
                'message'   => $th->getMessage(),
                'file'      => $th->getFile(),
                'line'      => $th->getLine(),
                'trace'     => $th->getTraceAsString(),
            ]);
            return [
                'resultCode' => 1,
                'message' => $th->getMessage()
            ];
        }
    }

    public static function getResponse($resultCode, $message, $data)
    {

        return response([
            'resultCode' => $resultCode,
            'message' => $message,
            'data' => $data
        ]);
    }

    public static function getSuccess($data)
    {

        return response([
            'resultCode' => 0,
            'message' => 'success',
            'data' => $data
        ]);
    }
    public static function getError($message, $data)
    {
        Log::channel('telegram')->error('APRIL_HOOK', [
            'APRIL_HOOK' => [
                '$message' => $message,
                $data
            ]
        ]);
        Log::error('APRIL_HOOK', [
            'APRIL_HOOK' => [
                '$message' => $message,
                $data
            ]
        ]);
        return response([
            'resultCode' => 1,
            'message' => $message,
            'data' => $data
        ]);
    }
}
