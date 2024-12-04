<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustHosts as Middleware;

class TrustHosts extends Middleware
{
    /**
     * Get the host patterns that should be trusted.
     *
     * @return array<int, string|null>
     */
    public function hosts(): array
    {
        return [
            $this->allSubdomainsOfApplicationUrl(),
            'april-app.ru',
            'front.april-app.ru',
            'event.april-app.ru',
            'april-hook.ru',
            'garant-app.ru',
        ];
    }
}
