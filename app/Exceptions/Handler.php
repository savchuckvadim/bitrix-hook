<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }


    /**
     * Report or log an exception.
     *
     * @param  \Throwable  $exception
     * @return void
     */
    public function report(Throwable $exception)
    {
        if ($this->shouldReport($exception)) {
            // if ( $exception instanceof HttpException &&  $exception->getStatusCode() == 405 ) {

            // Получаем объект запроса
            $request = Request::capture();

            Log::cannel('telegram')->info('Error 500: ', [
                'url' => $request->fullUrl(),
            ]);
            // Формируем лог с дополнительной информацией
            Log::error('Error 500: ', [
                'exception' => $exception,
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'data' => $request->all(), // Выводит все данные запроса, кроме файлов
                'headers' => $request->headers->all() // Опционально, если нужны заголовки
            ]);
            // }
        }

        parent::report($exception);
    }
}
