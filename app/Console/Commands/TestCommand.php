<?php

namespace App\Console\Commands;

use App\Http\Controllers\APIBitrixController;
use App\Http\Controllers\PortalController;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TestCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test';

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
        

            //CATEGORIES
            $webhookRestKey = $portal['data']['C_REST_WEB_HOOK_URL'];
            $hook = 'https://' . $domain  . '/' . $webhookRestKey;

            $methodSmart = '/crm.category.list.json';
            $url = $hook . $methodSmart;
            // $entityId = env('APRIL_BITRIX_SMART_MAIN_ID');
            $entityId = 162;
            $hookCategoriesData = ['entityTypeId' => $entityId];

            // Возвращение ответа клиенту в формате JSON

            $smartCategoriesResponse = Http::get($url, $hookCategoriesData);
            $bitrixResponse = $smartCategoriesResponse->json();
            // Log::info('SUCCESS RESPONSE SMART CATEGORIES', ['categories' => $bitrixResponse]);
            if(isset($smartCategoriesResponse['result'])){
                $categories = $smartCategoriesResponse['result']['categories'];

                //STAGES
    
                foreach ($categories as $category) {
                    // Log::info('category', ['category' => $category]);
                    $hook = 'https://' . $domain  . '/' . $webhookRestKey;
                    $stageMethod = '/crm.status.list.json';
                    $url = $hook . $stageMethod;
                    $hookStagesData = [
                        'entityTypeId' => $entityId,
                        'entityId' => 'STATUS',
                        'categoryId' => $category['id'],
                        'filter' => ['ENTITY_ID' => 'DYNAMIC_' . $entityId . '_STAGE_' . $category['id']]
    
                    ];
    
    
                    // Log::info('hookStagesData', ['hookStagesData' => $hookStagesData]);
                    $stagesResponse = Http::get($url, $hookStagesData);
                    $stages = $stagesResponse['result'];
                    // Log::info('stages', ['stages' => $stages]);
                    foreach ($stages as $stage) {
                        $resultstageData = [

                            'id' => $stage['ID'],
                            'entityId' => $stage['ENTITY_ID'],
                            'statusId' => $stage['STATUS_ID'],
                            'title' => $stage['NAME'],
                            'nameInit' => $stage['NAME_INIT'],
    
                        ];
                        $this->info(print_r($resultstageData, true));
                    }
                }

            }else{
                $this->info(print_r($bitrixResponse, true));

            }
           
            
    }
}
