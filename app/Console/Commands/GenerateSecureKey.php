<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GenerateSecureKey extends Command
{
    protected $signature = 'generate:secure-key';
    protected $description = 'Generate a secure random key';

    public function handle()
    {
        $key = base64_encode(random_bytes(32)); // Генерация ключа
        $this->info("Generated Secure Key: {$key}");
    }
}

