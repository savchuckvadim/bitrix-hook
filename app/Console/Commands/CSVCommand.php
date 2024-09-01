<?php

namespace App\Console\Commands;

use App\Http\Controllers\APIBitrixController;
use App\Http\Controllers\PortalController;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class CSVCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'csv:go';

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
        $domain = '';
        $portal = PortalController::getPortal($domain);
        // Log::info('portal', ['portal' => $portal]);
        $filePath = storage_path('app/public/clients/events.csv');



        if (!file_exists($filePath)) {
            return response()->json(['error' => 'File not found.'], 404);
        }

        $file = fopen($filePath, 'r');

        $clients = [];
        $events = [];
        $contacts = [];

        // Пропускаем заголовки
        fgetcsv($file, 0, ";"); // Установка ';' как разделителя

        while (($row = fgetcsv($file, 0, ";")) !== FALSE) {
            // Анализ данных
            $client = $this->createClient($row);
            $clients[] = $client;
        }

        fclose($file);


        $this->info(print_r([

            'client' => count($clients)
        ], true));
        // Логирование или дополнительная обработка
        foreach ($clients as $client) {

            if (!empty($client[42])) {

                $this->info(print_r([

                    'client' => $client
                ], true));
            }
        }

        // Отправка ответа в формате JSON
        // return response()->json([
        //     'clients' => $clients
        // ]);


        // $this->info(print_r([
        //     'clients' => $clients
        // ], true));
    }

    private function createClient($data)
    {
        // Анализируем данные клиента и возвращаем массив

        return $data;
        // return [
        //     'id' => $data[0],
        //     'source' => $data[1],
        //     // добавьте другие поля по аналогии
        // ];
    }
}
