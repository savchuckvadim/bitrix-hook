<?php

namespace App\Logging;

use Illuminate\Support\Facades\Http;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;
use Monolog\LogRecord;
use Illuminate\Support\Facades\Cache;

class TelegramLogger extends AbstractProcessingHandler
{
    protected function write(LogRecord $record): void
    {
        try {
              // Разрешаем только 1 отправку на 5 секунд, остальные пропускаем.
              if (!Cache::add('telegram_logger:cooldown', 1, now()->addSeconds(5))) {
                return;
            }

            $url = env('TELEGRAM_PROXY_URL');
            if (!$url) {
                return;
            }

            $app = 'april-hook';
            $domain = 'april-hook-doamin';
            $userId = 'userId';

            $text = sprintf(
                "💥 App: %s\n🌍 Domain: %s\n🧭 Env: %s\n⚠️ Level: %s\n👤 UserId: %s\n\nText: %s",
                $app,
                $domain,
                env('APP_ENV', 'local'),
                $record->level->getName(),
                $userId ?? '-',
                (string) $record->message
            );

            if (!empty($record->context)) {
                $safeContext = $record->context;
                unset($safeContext['userId']);

                if (!empty($safeContext)) {
                    $text .= "\n\nContext: " . json_encode($safeContext, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                }
            }

            $payload = [
                'app' => $app,
                'domain' => $domain,
                'text' => $this->cleanText($text),
                'userId' => $userId,
            ];

            Http::timeout(5)->post($url, $payload);
        } catch (\Throwable $e) {
            // не валим приложение из-за ошибок лог-канала
        }
    }

    private function cleanText(string $text): string
    {
        return mb_substr(
            preg_replace('/([_*[\]()~`>#+=|{}.!\\\\-])/u', '\\\\$1', $text),
            0,
            4000
        );
    }

    public function __construct($level = Logger::DEBUG, bool $bubble = true)
    {
        parent::__construct($level, $bubble);
    }
}
