<?php

namespace App\Console\Commands;

use App\Http\Controllers\APIBitrixController;
use App\Http\Controllers\PortalController;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class ActivityGetCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'activity:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $domain = 'april-garant.bitrix24.ru';
        $portal = PortalController::getPortal($domain);
        // Log::info('portal', ['portal' => $portal]);



        $url = 'https://april-hook/api/alfa/activity';
        // $entityId = env('APRIL_BITRIX_SMART_MAIN_ID');

        $smartCategoriesResponse = Http::get($url);


        $this->info(print_r($smartCategoriesResponse, true));
    }
}
