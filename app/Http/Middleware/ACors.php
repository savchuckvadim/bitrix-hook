<?php

namespace App\Http\Middleware;

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
        // Обработка предварительных запросов (OPTIONS)
        if ($request->isMethod('OPTIONS')) {
            return response()->json([], 200, [
                'CORS-Middleware-Called' => 'true',
                'Access-Control-Allow-Origin' => 'https://front.april-app.ru',
                'Access-Control-Allow-Methods' => 'GET, POST, PUT, DELETE, OPTIONS',
                'Access-Control-Allow-Headers' => 'Content-Type, Authorization',
                'Access-Control-Allow-Credentials' => 'true',
            ]);
        }
    
        // Обработка обычных запросов
        $response = $next($request);
    
        $response->headers->set('CORS-Middleware-Called', 'true');
        $response->headers->set('Access-Control-Allow-Origin', 'https://front.april-app.ru');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization');
        $response->headers->set('Access-Control-Allow-Credentials', 'true');
    
        return $response;
    }
    
}
