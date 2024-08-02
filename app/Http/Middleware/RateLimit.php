<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Symfony\Component\HttpFoundation\Response;

class RateLimit
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $ip = $request->ip(); // Идентификатор для ограничения, например IP пользователя
        $key = "rate_limit:{$ip}";
        $count = Redis::incr($key);

        if ($count == 1) {
            Redis::expire($key, 60); // Сбросить счётчик каждую минуту
        }

        if ($count > 19.5) {
            $delay = $count - 19.5; // Задержка начинается после 10-го запроса
            sleep($delay); // Задержка перед обработкой запроса
        }

        return $next($request);
    }
}
