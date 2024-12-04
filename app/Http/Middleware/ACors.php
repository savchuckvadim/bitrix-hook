<?php

namespace App\Http\Middleware;

use App\Http\Controllers\APIOnlineController;
use Closure;


class ACors
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle($request, Closure $next)
    {
        $data = $request->all();

        APIOnlineController::sendLog('CORS data', $data);
        APIOnlineController::sendLog('CORS headers', $request);


        // Обработка предварительных запросов (OPTIONS)
        if ($request->isMethod('OPTIONS')) {
            APIOnlineController::sendLog('OPTIONS 1', $data);
            return response()->json([], 200, [
                'CORS-Middleware-Called' => 'true',
                'Access-Control-Allow-Origin' => 'http://localhost:5000',
                'Access-Control-Allow-Methods' => 'GET, POST, PUT, DELETE, OPTIONS',
                'Access-Control-Allow-Headers' => 'Content-Type, Authorization, X-Requested-With',
                'Access-Control-Allow-Credentials' => 'true',
            ]);

        }
        APIOnlineController::sendLog('OPTIONS 2', $data);
        // Обработка обычных запросов
        $response = $next($request);
    
        $response->headers->set('CORS-Middleware-Called', 'true');
        $response->headers->set('Access-Control-Allow-Origin', 'http://localhost:5000');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
        $response->headers->set('Access-Control-Allow-Credentials', 'true');
    
        return $response;
    }
    
}
