<?php

namespace App\Logging;

use Monolog\Logger;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\LogRecord;
use Telegram\Bot\Api;

class TelegramLogger extends AbstractProcessingHandler
{
    protected function write(LogRecord $record): void
    {
        try {
            $telegram = new Api(env('TELEGRAM_ERROR_BOT_TOKEN'));
            $telegram->sendMessage([
                'chat_id' => env('TELEGRAM_CHAT_ID'),
                'text' => $record->formatted // Обращение к свойству как к объекту
            ]);
        } catch (\Exception $e) {
            // Обработка исключения при ошибке отправки сообщения в Telegram
        }
    }

    public function __construct($level = Logger::DEBUG, bool $bubble = true)
    {
        parent::__construct($level, $bubble);
    }
}
