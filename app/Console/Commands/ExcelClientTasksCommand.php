<?php

namespace App\Console\Commands;

use App\Http\Controllers\APIBitrixController;
use App\Http\Controllers\MigrateCRM\MigrateCRMController;
use App\Http\Controllers\PortalController;
use App\Imports\ClientsImport;
use App\Imports\ContactsImport;
use App\Services\BitrixGeneralService;
use Box\Spout\Reader\Common\Creator\ReaderEntityFactory;
use DateTime;
use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Illuminate\Support\Facades\Http;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;

class ExcelCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'xoexel:go';

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
        // $domain = 'april-dev.bitrix24.ru';
        // $portal = PortalController::getPortal($domain);
        // $this->info($portal['data']['id']);

        ini_set('memory_limit', '6096M');  // Установка лимита в 4 ГБ
        $companiesPath = storage_path('app/public/clients/client_event.xlsx');
        // $contactsPath = storage_path('app/public/clients/contacts.xlsx');
        // $eventsPath = storage_path('app/public/clients/events.xlsx');


        // $this->info('Command executed successfully. Data saved to ' . $jsonFilePath);
        if (!file_exists($companiesPath)) {
            $this->error('File not found.');
            return 1; // Возврат ошибки в консольной команде
        }

        // Загрузка данных
        $companiesData = Excel::toArray(new ClientsImport, $companiesPath)[0];
        // $contactsData = Excel::toArray(new ContactsImport, $contactsPath)[0];
        // $eventsData = Excel::toArray(new ClientsImport, $eventsPath)[0];
        $companies = $this->createClients($companiesData);
        // $contacts = $this->createContacts($contactsData);
        $events = [];


        // $companies = $this->readExcelFile($companiesPath, 'createClients');
        // $contacts = $this->readExcelFile($contactsPath, 'createContacts');
        // $eventsData = $this->readExcelFile($eventsPath, 'createEvents');
        // $events = [];
        // Индексация контактов и событий
        // $indexedContacts = $this->indexContacts($contacts);

        // foreach ($eventsData as $eventRow) {
        //     $event = $this->createEvent($eventRow);
        //     array_push($events, $event);
        // }
        $indexedEvents = $this->indexEvents($events);
        // Подготовка данных клиентов
        $clientsWithDetails = $this->prepareClientDetails($companies);
        $chunks = array_chunk($clientsWithDetails, ceil(count($clientsWithDetails) / 1));
        $fileNumber = 1;

        foreach ($chunks as $chunk) {
            $jsonFilePath = storage_path('app/public/clients/clients_events_data_' . $fileNumber . '.json');
            file_put_contents($jsonFilePath, json_encode(['clients' => $chunk], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $this->info('Data saved to ' . $jsonFilePath);
            $this->info('....... ');

            $jsonFilePath = storage_path('app/public/clients/clients_events_data_' . $fileNumber . '.json');

            // Чтение данных из файла
            $jsonData = file_get_contents($jsonFilePath);

            // Преобразование JSON в массив
            $data = json_decode($jsonData, true);  // true преобразует данные в ассоциативный массив


            if (!empty($data['clients'])) {
                $clientsCount = count($data['clients']);
                // $contactsCount = 0;
                // $eventsCount = 0;
                // foreach ($data['clients'] as $client) {
                //     $contactsCount += count($client['contacts']);
                //     $eventsCount += count($client['events']);
                // }

                $this->info('CHUNK: ' . $fileNumber . '  ClientsCount : ' . $clientsCount);
                // $this->info('CHUNK: ' . $fileNumber . '  ContactsCount : ' . $contactsCount);
                // $this->info('CHUNK: ' . $fileNumber . '  EventsCount : ' . $eventsCount);
            }

            $this->info('................................................................ ');
            $this->info('................................................................ ');
            $this->info(' ');
            $this->info(' ');
            $this->info(' ');
            $fileNumber++;
        }

        // Сохранение данных в JSON
        // $jsonFilePath = storage_path('app/public/clients/clients_data.json');
        // file_put_contents($jsonFilePath, json_encode(['clients' => array_values($clientsWithDetails)], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        // $this->info('Command executed successfully. Data saved to ' . $jsonFilePath);


        // $jsonFilePath = storage_path('app/public/clients/clients_data.json');

        // // Чтение данных из файла
        // $jsonData = file_get_contents($jsonFilePath);

        // // Преобразование JSON в массив
        // $data = json_decode($jsonData, true);  // true преобразует данные в ассоциативный массив


        // if (!empty($data['clients'])) {
        //     $clientsCount = count($data['clients']);
        //     $contactsCount = 0;
        //     $eventsCount = 0;
        //     foreach ($data['clients'] as $client) {
        //         $contactsCount += count($client['contacts']);
        //         $eventsCount += count($client['events']);
        //     }

        //     $this->info('ClientsCount : ' . $clientsCount);
        //     $this->info('ContactsCount : ' . $contactsCount);
        //     $this->info('EventsCount : ' . $eventsCount);
        // }


        // $migrateContraller = new MigrateCRMController('token', $domain);
        // $migrateContraller->crm();
        return 0; // Успешное выполнение команды
    }

    // public function handle()
    // {
    //     $domain = 'april-dev.bitrix24.ru';
    //     $portal = PortalController::getPortal($domain);
    //     $this->info($portal['data']['id']);

    //     ini_set('memory_limit', '2048M');  // Increase memory limit if needed
    //     $companiesPath = storage_path('app/public/clients/companies.xlsx');
    //     $contactsPath = storage_path('app/public/clients/contacts.xlsx');
    //     $eventsPath = storage_path('app/public/clients/events_exel.xlsx');


    //     // $this->info('Command executed successfully. Data saved to ' . $jsonFilePath);
    //     if (!file_exists($companiesPath) || !file_exists($contactsPath) || !file_exists($eventsPath)) {
    //         $this->error('File not found.');
    //         return 1; // Возврат ошибки в консольной команде
    //     }

    //     // Загрузка данных
    //     $companiesData = Excel::toArray(new ClientsImport, $companiesPath)[0];
    //     $contactsData = Excel::toArray(new ContactsImport, $contactsPath)[0];
    //     $eventsData = Excel::toArray(new ClientsImport, $eventsPath)[0];
    //     $companies = $this->createClients($companiesData);
    //     $contacts = $this->createContacts($contactsData);
    //     $events = [];
    //     // Индексация контактов и событий
    //     $indexedContacts = $this->indexContacts($contacts);

    //     foreach ($eventsData as $eventRow) {
    //         $event = $this->createEvent($eventRow);
    //         array_push($events, $event);
    //     }
    //     $indexedEvents = $this->indexEvents($events);
    //     // Подготовка данных клиентов
    //     $clientsWithDetails = $this->prepareClientDetails($companies, $indexedContacts, $indexedEvents);
    //     $chunks = array_chunk($clientsWithDetails, ceil(count($clientsWithDetails) / 10));
    //     $fileNumber = 1;

    //     foreach ($chunks as $chunk) {
    //         $jsonFilePath = storage_path('app/public/clients/clients_data_' . $fileNumber . '.json');
    //         file_put_contents($jsonFilePath, json_encode(['clients' => $chunk], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    //         $this->info('Data saved to ' . $jsonFilePath);
    //         $fileNumber++;
    //     }

    //     return 0; // Успешное выполнение команды
    // }



    private function indexContacts($contactsData)
    {
        $indexedContacts = [];
        foreach ($contactsData as $contact) {
            if (empty($contact)) {
                print_r($contact);
            }
            if (!empty($contact)) {
                $clientId = sprintf("%06d", trim($contact['clientId']));
                $indexedContacts[$clientId][] = $contact;
            }
        }
        return $indexedContacts;
    }

    private function indexEvents($eventsData)
    {
        $indexedEvents = [];
        foreach ($eventsData as $event) {
            $clientId = sprintf("%06d", trim($event['clientId']));
            $indexedEvents[$clientId][] = $event;
        }
        return $indexedEvents;
    }

    private function prepareClientDetails($companiesData)
    {
        $clientsWithDetails = [];
        foreach ($companiesData as $client) {
            $clientId = sprintf("%06d", trim($client['id']));
            $clientsWithDetails[$clientId] = [
                'client' => $client,
                // 'contacts' => $indexedContacts[$clientId] ?? [],
                // 'events' => $indexedEvents[$clientId] ?? []
            ];
        }
        return $clientsWithDetails;
    }

    private function createEvent($row)
    {
        // Анализируем данные клиента и возвращаем массив

        // return $data;
        $clientId = trim($row[0]);
        $clientId = sprintf("%06d",  $clientId);  // Форматирование ID
        // Убедимся, что $excelTimestamp действительно число
        $dateTime = $row[1];
        $formattedDate = '';
        if (is_numeric($row[1])) {
            try {
                $dateTime = Date::excelToDateTimeObject($row[1]);
                // print_r($dateTime);
                // print_r('<br>');
                $formattedDate = $dateTime->format('Y-m-d H:i:s');
                // print_r($formattedDate);
                // print_r('<br>');
            } catch (\Exception $e) {
                echo "Ошибка при преобразовании даты: " . $e->getMessage();
            }
        } else {
            print_r($row[1]);
            print_r('<br>');
            echo "Дата в Excel не является числом: {$row[1]}";
        }


        return [
            'clientId' =>  $clientId,
            'eventType' => $row[3],
            'dateTime' =>  $formattedDate,
            // 'time' => $row[10],
            'responsible' => $row[4],
            'contact' => $row[5],
            'comment' => $row[6],
            'planComment' => $row[7],


        ];
    }

    private function readExcelFile($filePath, $processMethod)
    {
        $reader = ReaderEntityFactory::createXLSXReader();
        $reader->open($filePath);
        $results = [];

        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $cells = $row->getCells();
                $rowData = array_map(function ($cell) {
                    return $cell->getValue();
                }, $cells);
                $results[] = $this->$processMethod($rowData);
            }
        }

        $reader->close();
        return $results;
    }


    private function createClients($rows)
    {
        // Анализируем данные клиента и возвращаем массив
        $clients = [];
        foreach ($rows as $key => $row) {
            $clientId = trim($row[0]);

            $clientId = ltrim($clientId, '0');

            // $clientId = sprintf("%06d",  $clientId);  // Форматирование ID

            // if(empty($row[41])){
            //     foreach ($row as $key => $value) {
            //         $this->info($key);
            //         $this->info($value);
            //     }


            // }
            if ($key > 0) {


                print_r($row[33]);
                if (is_numeric($row[33])) {
                    $dateTime = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row[33]);
                    $formattedDate = $dateTime->format('Y-m-d H:i:s');
                } else {
                    $formattedDate = 'Invalid date'; // Или другое дефолтное значение для некорректных дат
                }
                print_r($formattedDate); // Покажет результат конвертации или 'Invalid date'



                $taskName = $row[30] . ' ';
                $client = [
                    'id' => $clientId,
                    'name' => $row[3],
                    'source' => $row[1],
                    'concurent' => $row[7],
                    'gorod' => $row[13],
                    'adress' => [
                        'indeks' => $row[12],
                        'region' => $row[11],
                        'gorod' => $row[13],
                        'adress' => $row[15],
                        'house' => $row[16],
                        'corpus' => $row[17],
                        'flat' => $row[18],
                        'fullAddress' => ''
                    ],

                    'inn' => $row[24],
                    'metka' => $row[26],
                    'assigned' => $row[28],
                    'perspect' => $row[30],
                    'category' => $row[9],

                    'prognoz' => $row[34],
                    'auto' => $row[41],
                    'statusk' => $row[10],
                    'commaent' => $row[35],
                    'deadline' => $formattedDate,
                    'taskName' => $taskName,




                    'contacts' => [],
                    'events' => []
                ];
                array_push($clients, $client);
            }
        }



        return $clients;
    }

    private function createContacts($rows)
    {
        // Анализируем данные клиента и возвращаем массив
        $collection = collect($rows);  // Преобразование массива в коллекцию

        $contacts = $collection->map(function ($row) {
            $clientId = trim($row[0]);
            $clientId = sprintf("%06d",  $clientId);  // Форматирование ID

            return [

                'clientId' => $clientId,
                'name' => $row[2],
                'position' => $row[1],
                'telefon' => $row[3],
                'dobTel' => $row[4],
                'isLpr' => $row[5],
                'email' => $row[6],
                'comment' => $row[7],
                'auto' => $row[8],

            ]; // Создание клиента на основе каждой строки
        });

        return $contacts;
    }
}
