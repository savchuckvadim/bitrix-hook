<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

const BASE_URL = 'https://april-online.ru/api';
class APIOnlineController extends Controller
{
    public static function online($domain, $method, $endpoint, $requestData, $dataname)
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
                return response([
                    'resultCode' => 0,
                    'data' => $data,
                    'message' => 'success'
                ], 200);
            } else {
                return response([
                    'resultCode' => 1,
                    'message' => 'ONLINE: Ошибка при запросе к API.'
                ], 200);
            }
        } catch (\Throwable $th) {
            Log::error('API ONLINE: Exception caught', [
                'message'   => $th->getMessage(),
                'file'      => $th->getFile(),
                'line'      => $th->getLine(),
                'trace'     => $th->getTraceAsString(),
            ]);
            return response([
                'resultCode' => 1,
                'message' => $th->getMessage()
            ], 200);
        }
    }
}
