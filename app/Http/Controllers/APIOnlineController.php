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
                'X-Requested-With' => 'XMLHttpRequest'
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

                    return [
                        'resultCode' => 1,
                        'message' => 'HOOK: invalid data',
                        'data' => $data
                    ];
                }
            } else {
                return [
                    'resultCode' => 1,
                    'message' => 'ONLINE: Ошибка при запросе к API.',
                    
                ];
            }
        } catch (\Throwable $th) {
            Log::error('API ONLINE: Exception caught', [
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

    public static function getResponse($resultCode, $message, $data){

        return response([
            'resultCode' => $resultCode,
            'message' => $message,
            'data' => $data
        ]);
    }
}
