<?php

namespace App\Http\Controllers\MigrateCRM;

use App\Http\Controllers\APIOnlineController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Front\EventCalling\FullEventInitController;
use App\Http\Controllers\PortalController;

use App\Services\General\BitrixBatchService;
use App\Services\General\BitrixDepartamentService;
use App\Services\HookFlow\BitrixListDocumentFlowService;
use App\Services\HookFlow\BitrixListFlowService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Halaxa\JsonMachine\JsonMachine;
use JsonMachine\Items;
use JsonMachine\JsonDecoder\ExtJsonDecoder;

class MColdController extends Controller
{

    protected $token;
    protected $domain;
    protected $hook;
    protected $portal;
    protected $portalBxLists;
    protected $portalBxCompany;
    protected $department;
    public function __construct(
        $token,
        $domain

    ) {

        $this->token = $token;
        $this->domain = $domain;

        $portal = PortalController::getPortal($domain);
        $this->portal = $portal['data'];
        $this->hook = PortalController::getHook($domain);
        $this->portalBxLists = $this->portal['bitrixLists'];
        $this->portalBxCompany  = $this->portal['company'];
        sleep(1);
        $this->department  = $this->getFullDepartment();
    }

    public function crm()
    {
        $result = null;
        $clients = [];
        $results = [];
        $googleData = null;
        $newCompanyId = null;
        try {
            /**
             * 
             * 
             * FROM GOOGLE VERSION
             */
            // $googleData = GoogleInstallController::getData($this->token);

            // if (!empty($googleData)) {
            //     if (!empty($googleData['clients'])) {
            //         $clients = $googleData['clients'];
            //     }
            // }
            $partsNumber = 10;
            $time_start = microtime(true);
            ini_set('memory_limit', '6048M');  // Increase memory limit if needed

            set_time_limit(0);
            $jsonFilePath = storage_path('app/public/clients/clients_events_data_' . $partsNumber . '.json');

            // Чтение данных из файла
            $jsonData = file_get_contents($jsonFilePath);

            // Преобразование JSON в массив
            $data = json_decode($jsonData, true);  // true преобразует данные в ассоциативный массив


            $decoder = new ExtJsonDecoder(true);  // true преобразует объекты JSON в ассоциативные массивы

            $clients = Items::fromFile($jsonFilePath, ['pointer' => '/clients', 'decoder' => $decoder]);


            // if (!empty($data['clients'])) {
            //     $clients = $data['clients'];

            $batchService = new BitrixBatchService($this->hook);
            $commands = [];
            $eventsCommands = [];
            foreach ($clients as $index => $clientData) {
                $client = $clientData['client'];
                // sleep(1);
                if (!empty($client)) {

                    // $rand = mt_rand(100000, 300000); // случайное число от 300000 до 900000 микросекунд (0.3 - 0.9 секунды)
                    // usleep($rand);


                    // if ($index < 30) {




                    $newCompanyId = null;
                    // $fullDepartment = $this->getFullDepartment();
                    $fullDepartment = $this->department;

                    $userId = 201; //201 - man savchuk in rostov
                    $fullDepartment =  $fullDepartment['department'];
                    // print_r('<br>');
                    // print_r($fullDepartment);
                    // print_r('<br>');
                    if (!empty($fullDepartment)) {
                        // print_r('<br>');
                        // print_r($fullDepartment['allUsers']);
                        // print_r('<br>');
                        if (!empty($fullDepartment['allUsers'])) {
                            foreach ($fullDepartment['allUsers'] as $user) {
                                $responsible = $client['assigned'];

                                $parts = explode(' ', $responsible);
                                $lastName = mb_strtolower(trim($parts[0])); // Приводим к нижнему регистру
                                $userLastName = mb_strtolower(trim($user['LAST_NAME'])); // Приводим фамилию пользователя к нижнему регистру

                                // print_r('<br>');
                                // print_r($responsible);
                                // print_r('<br>');
                                // print_r($lastName);
                                // print_r('<br>');


                                if ($lastName === $userLastName) {
                                    $userId = $user['ID'];
                                    break; // Прекратить перебор после нахождения пользователя

                                } else {
                                    // print_r('<br>');
                                    // print_r($responsible);
                                    // print_r('<br>');
                                    // print_r($lastName);
                                    // print_r('<br>');
                                }
                            }
                        }
                    }
                    // $perspekt = $this->getCompanyPerspect($client['perspect']);
                    // $concurent = $this->getCompanyConcurent($client['concurent']);
                    // $statusk = $this->getCompanyStatus($client['statusk']);
                    // $category = $this->getCompanyCategory($client['category']);
                    // $prognoz = $this->getCompanyPrognoz($client['prognoz']);

                    // $contacts = $this->getContactsField($clientData['contacts']);
                    // // $history = $this->getHistoryField($client['events']);


                    // $workStatus = $this->getCompanyWorkStatust($client['perspect']);
                    // $workResult = $this->getCompanyItemFromName($client['perspect'], 'op_work_result');
                    // $source = $this->getCompanyItemFromName($client['source'], 'op_source_select');
                    // $phonesArray = $this->getPhonesField($clientData['contacts']);

                    // $isAutoCall = 'N';
                    // if (!empty($client['auto'])) {
                    //     if (!empty($client['auto'] !== 'NULL') && $client['auto'] !== 'null') {
                    //         $isAutoCall = 'Y';
                    //     }
                    // }
                    // print_r('<br>');
                    // print_r($client['auto']);
                    // print_r($isAutoCall);
                    // print_r('<br>');
                    $newClientData = [

                        'filter' => [
                            'UF_CRM_OP_SMART_LID' => $client['id'], // сюда записывать id из старой crm
                        ],
                        'select' => ['ID', 'TITLE', 'UF_CRM_OP_SMART_LID'] // Выборка полей, которые нужно получить


                    ];


                    /**
                     * SINGLE REQUEST
                     */
                    // sleep(1);
                    // $rand = mt_rand(300000, 700000); // случайное число от 300000 до 900000 микросекунд (0.3 - 0.9 секунды)
                    // usleep($rand);
                    // $newCompanyId = BitrixGeneralService::setEntity(
                    //     $this->hook,
                    //     'company',
                    //     $newClientData
                    // );


                    /**
                     * BATCH
                     */
                    $batchData = [
                        'FIELDS' => $newClientData
                    ];

                    $commands[$client['id']] = 'crm.company.list?' . http_build_query($newClientData);
                    // print_r('<br>');
                    // print_r('get_client_' . $index . ': ' . $commands['get_client_' . $client['id']]);

                    /**
                     * LIST FLOW
                     */

                    // $rand = mt_rand(300000, 1900000); // случайное число от 300000 до 900000 микросекунд (0.3 - 0.9 секунды)
                    // usleep($rand);
                    // if (!empty($newCompanyId) && !empty($clientData['events'])) {

                    //     foreach ($clientData['events'] as $garusEvent) {

                    //         $rand = mt_rand(100000, 300000); // случайное число от 300000 до 900000 микросекунд (0.3 - 0.9 секунды)
                    //         usleep($rand);
                    //         $this->getListFlow($garusEvent, $newCompanyId, $userId);
                    //     }
                    // }
                    // }


                    // }
                }
            }


            if (!empty($commands)) {
                $results = $batchService->sendGeneralBatchRequest($commands);
                // print_r("<br> newCompanyIds");
                // print_r($results);
                $newCompanyIds = [];
                $clients = Items::fromFile($jsonFilePath, ['pointer' => '/clients', 'decoder' => $decoder]);
                $resultClients = [];
                foreach ($results as $batchKey => $batchResults) {
                    if (isset($batchResults)) {
                        foreach ($batchResults as $key => $result) {
                            // if (preg_match('/add_client_(.+)$/', $key, $matches)) {
                            // $oldClientId = $matches[1];
                            $resultClient = null;
                            // print_r(' KEY      ');
                            // print_r($key);
                            // print_r("<br>");
                            if (!empty($result)) {
                                if (!empty($result[0])) {
                                    // if (!empty($result[0][0])) {
                                    $newCompanyIds[$key] = $result[0];
                                    foreach ($clients as $index => $clientData) {
                                        if ($clientData['client']['id'] ===  $result[0]['UF_CRM_OP_SMART_LID']) {
                                            // print_r(' KEY      ');
                                            // print_r($clientData['client']);
                                            // print_r("<br>");
                                            // print_r($result[0]);
                                            // print_r("<br>");
                                            $fullDepartment = $this->department;
                                            $userId = 201; //201 - man savchuk in rostov
                                            $fullDepartment =  $fullDepartment['department'];
                                            if (!empty($fullDepartment)) {
                                                if (!empty($fullDepartment['allUsers'])) {

                                                    foreach ($fullDepartment['allUsers'] as $user) {

                                                        $responsible = $clientData['client']['assigned'];

                                                        $parts = explode(' ', $responsible);
                                                        $lastName = mb_strtolower(trim($parts[0])); // Приводим к нижнему регистру
                                                        $userLastName = mb_strtolower(trim($user['LAST_NAME'])); // Приводим фамилию пользователя к нижнему регистру


                                                        if ($lastName === $userLastName) {
                                                            $userId = $user['ID'];
                                                            break; // Прекратить перебор после нахождения пользователя

                                                        } else {
                                                            // print_r('<br>');
                                                            // print_r($responsible);
                                                            // print_r('<br>');
                                                            // print_r($lastName);
                                                            // print_r('<br>');
                                                        }
                                                    }
                                                }
                                            }

                                            $resultClient = [
                                                'domain' => $this->domain,
                                                'entityType' => 'company',
                                                'entityId' => (int)$result[0]['ID'],
                                                'responsible' => (int)$userId,
                                                'created' => 201,
                                                'deadline' => $clientData['client']['deadline'],
                                                'name' => $clientData['client']['taskName'],
                                                'isTmc' => 'N',


                                                // 'companyId' => $result[0]['ID'],


                                                // 'bxClient' => $result[0],
                                                // 'garusClient' => [
                                                //     'deadline' => $clientData['deadline'],

                                                // ]
                                            ];
                                            array_push($resultClients, $resultClient);
                                        }

                                        $companyId = $newCompanyIds[$clientData['client']['id']] ?? null;
                                    }
                                    // }
                                }
                            }
                            // }
                        }
                    }
                }
                print_r("<br> resultClients");
                print_r($resultClients);



                // // $eventsCommands = [];
                // foreach ($clients as $index => $clientData) {
                //     // if ($index < 1000) {

                //     $companyId = $newCompanyIds[$clientData['client']['id']] ?? null;

                //     if (!empty($companyId) && !empty($clientData['events'])) {
                //         print_r("<br> companyId    _  ");
                //         print_r($companyId);


                //         // print_r("eventsCommands");
                //         // print_r("<br>");
                //         // print_r($eventsCommands);

                //         print_r($clientData['client']['id']);
                //     }
                //     // }
                // }

                // // if (!empty($eventsCommands)) {
                // //     // $eventResults = $batchService->sendGeneralBatchRequest($eventsCommands);
                // // }
                // print_r("<br>");
                // print_r("set list item");
                // print_r("<br>");

                // $resultjsonFilePath = storage_path('app/public/result/resultClients.json');
                // $chunks = array_chunk($resultClients, ceil(count($resultClients) / 10));

                // Папка для сохранения файлов
                $storagePath = storage_path('app/public/clients/result/'); // Убедитесь, что путь корректен

                // Сохранение каждого чанка в отдельный файл
                // foreach ($chunks as $index => $chunk) {
                $chunkJsonData = json_encode($resultClients, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                $chunkFilePath = $storagePath . 'resultClients_part' . $partsNumber . '.json';
                file_put_contents($chunkFilePath, $chunkJsonData);
                echo "Часть " . ($index + 1) . " сохранена в файл: " . $chunkFilePath . "<br>";
                // }

                // $jsonFilePath = storage_path('app/public/clients/clients_events_data_' . $partsNumber . '.json');

                // Чтение данных из файла
                $jsonData = file_get_contents($chunkFilePath);

                // Преобразование JSON в массив
                $data = json_decode($jsonData, true);  // true преобразует данные в ассоциативный массив


                if (!empty($data['clients'])) {
                    $clientsCount = count($data['clients']);
                    $this->info('CHUNK: ' . $partsNumber . '  ClientsCount : ' . $clientsCount);
                }
                // Тут ваш код...

                // Записываем время окончания выполнения скрипта
                $time_end = microtime(true);

                // Вычисляем продолжительность выполнения
                $execution_time = ($time_end - $time_start);

                // Продолжительность в минутах и часах
                $execution_time_minutes = $execution_time / 60;
                $execution_time_hours = $execution_time_minutes / 60;
                // Выводим время начала, окончания и продолжительность выполнения
                print_r("<br>");
                print_r("Время начала: " . date('H:i:s', $time_start) . "<br>");
                print_r("<br>");
                print_r("Время окончания: " . date('H:i:s', $time_end) . "<br>");
                print_r("<br>");
                print_r("Продолжительность выполнения скрипта: " . $execution_time . " секунды.");

                print_r("Продолжительность выполнения скрипта: " . $execution_time_minutes . " минут.<br>");
                print_r("Продолжительность выполнения скрипта: " . $execution_time_hours . " часов.<br>");
                // $eventResults = null;
                // if (!empty($eventsCommands)) {
                //     $eventResults = $batchService->sendGeneralBatchRequest($eventsCommands);
                // }
                // print_r("set list item");
                // print_r("<br>");
                //     // Обработка результатов пакетного запроса
            }

            // Отправка пакетного запроса
            // if (!empty($commands)) {
            //     $results = $batchService->sendGeneralBatchRequest($commands);
            //     // print_r('<br> results');
            //     // print_r($results);
            //     $newCompanyIds = [];
            //     print_r("<br>  results");
            //     print_r($results);
            //     print_r("<br> count results");
            //     print_r(count($results));
            //     foreach ($results as $key => $result) {
            //         // print_r('<br> key');
            //         // print_r($key);
            //         if (preg_match('/add_client_(.+)$/', $key, $matches)) {
            //             $oldClientId = $matches[1];
            //             print_r("<br> oldClientId");
            //             print_r($oldClientId);
            //             if (!empty($result)) {
            //                 $newCompanyIds[$oldClientId] = $result;
            //             }
            //         }
            //     }
            //     print_r("<br> newCompanyIds");
            //     print_r($newCompanyIds);
            //     $eventsCommands = [];
            //     $clients = Items::fromFile($jsonFilePath, ['pointer' => '/clients', 'decoder' => $decoder]);

            //     foreach ($clients as $index => $clientData) {
            //         if ($index > 1 && $index < 200) {

            //             $companyId = $newCompanyIds[$clientData['client']['id']] ?? null;

            //             if (!empty($companyId) && !empty($clientData['events'])) {
            //                 // print_r("<br> companyId    _  ");
            //                 // print_r($companyId);

            //                 foreach ($clientData['events'] as $garusEvent) {
            //                     $eventsCommands = $this->getListFlow($garusEvent, $companyId, $userId, $eventsCommands);
            //                 }
            //                 // print_r("eventsCommands");
            //                 // print_r("<br>");
            //                 // print_r($eventsCommands);

            //                 print_r($clientData['client']['id']);
            //             }
            //         }
            //     }
            //     $eventResults = null;
            //     if (!empty($eventsCommands)) {
            //         $eventResults = $batchService->sendGeneralBatchRequest($eventsCommands);
            //     }
            //     print_r("set list item");
            //     // print_r("<br>");
            //     // Обработка результатов пакетного запроса

            // }


            // }
            return APIOnlineController::getError(
                'infoblocks not found',
                ['clients' => $results, 'newCompanyId' => $newCompanyId]
            );
        } catch (\Throwable $th) {
            $errorMessages =  [
                'message'   => $th->getMessage(),
                'file'      => $th->getFile(),
                'line'      => $th->getLine(),
                'trace'     => $th->getTraceAsString(),
            ];
            print_r($errorMessages);

            Log::channel('telegram')->error('ERROR COLD APIBitrixController: Exception caught',  $errorMessages);
            Log::error('ERROR COLD APIBitrixController: Exception caught',  $errorMessages);
            Log::info('error COLD APIBitrixController', ['error' => $th->getMessage()]);

            return APIOnlineController::getError(
                $th->getMessage(),
                [
                    // 'portal' => $this->portal,
                    // 'hook' => $this->hook,
                    // 'portalBxLists' => $this->portalBxLists,

                    'clients' => $results,
                    'newCompanyId' => $newCompanyId
                    // 'portalBxCompany' => $this->portalBxCompany,
                    // 'googleData' => $googleData,
                ]
            );
        }
    }

    protected function  getContactsField($contacts) //contacts
    {
        // op_contacts

        $pFields =  $this->portalBxCompany['bitrixfields'];
        $result = null;
        $pFieldBxId = null;
        $resultValue = [];
        foreach ($pFields as $pField) {
            if ($pField['code'] === 'op_contacts') {

                $pFieldBxId = 'UF_CRM_' . $pField['bitrixId'];
                $result = [$pFieldBxId => $resultValue];
            }
        }

        if (!empty($contacts)) {
            foreach ($contacts as $contact) {
                $resultContactstring = $contact['name'] . ' ' . $contact['position'];
                if (!empty($contact['telefon']) && $contact['telefon']  !== '(   )   -  -  '  && $contact['telefon'] !== '8' && $contact['telefon'] !== '( ) - -' && $contact['telefon'] !== '8( ) - -' && $contact['telefon'] !== '-'  && $contact['telefon'] !== ' ' && $contact['telefon'] !== 'NULL'  && $contact['telefon'] !== "\"NULL\"" && $contact['telefon'] !== "\"TELEFON\"") {

                    if (preg_match('/^\d/', $contact['telefon'])) {
                        // Если номер начинается с 8, заменяем первую '8' на '+7'
                        if (strpos($contact['telefon'], '8') === 0) {
                            $phone = '+7' . substr($contact['telefon'], 1);
                        } else {
                            // Добавляем '+7', если номер начинается не с '8'
                            $phone = '+7' . $contact['telefon'];
                        }
                    } else {
                        // Если номер начинается не с цифры (например, скобка или другой символ)
                        $phone = '+7' . $contact['telefon'];
                    }

                    // $phone = '+7' . substr($contact['telefon'], 1);
                    $resultContactstring = $resultContactstring . "\n " . ' тел: ' . $phone;
                }
                if (!empty($contact['dobTel']) && $contact['dobTel'] !== '-' && $contact['dobTel'] !== 'NULL'  && $contact['dobTel'] !== "\"NULL\"") {
                    $resultContactstring = $resultContactstring . ' доб: ' . $contact['dobTel'];
                }
                if (!empty($contact['email']) && $contact['email'] !== '-' && $contact['email'] !== 'NULL'  && $contact['email'] !== "\"NULL\"") {
                    $resultContactstring = $resultContactstring . "\n " . 'email: ' . $contact['email'];
                }
                if (!empty($contact['comment'])  && $contact['comment'] !== '-' && $contact['comment'] !== 'NULL'  && $contact['comment'] !== "\"NULL\"") {
                    $resultContactstring = $resultContactstring . " \n" . '' . $contact['comment'];
                }
                if (!empty($contact['isLpr']) && $contact['isLpr'] !== '-' && $contact['isLpr'] !== 'NULL'  && $contact['isLpr'] !== "\"NULL\"") {
                    $resultContactstring = $resultContactstring . " \n" . 'ЛПР';
                }
                if (!empty($resultContactstring)) {
                    array_push($resultValue, $resultContactstring);
                }
            }
            $result = [$pFieldBxId => $resultValue];
        }


        return $result;
    }
    protected function  getPhonesField($contacts) //contacts
    {
        // phones


        $result = null;

        if (!empty($contacts)) {
            // $phones = [];
            $processedPhones = [];
            foreach ($contacts as $key => $contact) {
                # code...



                $phone =  $contact['telefon'];
                // Log::channel('telegram')->info('TEST PHONE', ['$phones' => $phones]);

                if (!empty($phone)) {
                    // $phonesArray = explode(", ", $phone);

                    // Новый массив для обработанных телефонов


                    // Перебор массива и замена первой '8' на '+7'
                    // foreach ($phonesArray as $phone) {
                    // Удаляем пробелы и проверяем формат номера
                    // print_r($phone);

                    // Пропуск неправильно форматированных номеров
                    if (!empty($phone) && $phone  !== '8' && $phone  !== '(   )   -  -  ' && $phone  !== '()' && $phone  !== '() '  && $phone  !== '( )'  && $phone  !== '() - ' && $phone  !== '() -' && $phone  !== '() --' && $phone  !== '( ) - -' && $phone  !== '8( ) - -' && $phone  !== '-'  && $contact['telefon'] !== ' ' && $contact['telefon'] !== 'NULL'  && $contact['telefon'] !== "\"NULL\"" && $contact['telefon'] !== "\"TELEFON\"") {
                        $phone = trim($phone);
                        // if ($phone === "+7() - -" ||  $phone === "(  )  -  - " || $phone === "( ) - -" || $phone === "8( ) - -" || $phone === "NULL" || $phone === "TELEFON" || $phone === "TELEPHON" || $phone == ""  || $phone == "-" || $phone == null) {
                        //     continue;
                        // }

                        // Проверяем, начинается ли номер с цифры, не учитывая код страны
                        if (preg_match('/^\d/', $phone)) {
                            // Если номер начинается с 8, заменяем первую '8' на '+7'
                            if (strpos($phone, '8') === 0) {
                                $processedPhone = '+7' . substr($phone, 1);
                            } else {
                                // Добавляем '+7', если номер начинается не с '8'
                                $processedPhone = '+7' . $phone;
                            }
                        } else {
                            // Если номер начинается не с цифры (например, скобка или другой символ)
                            $processedPhone = '+7' . $phone;
                        }
                        if (!in_array($processedPhone, $processedPhones)) {
                            $resultPhone = [
                                // { "VALUE": "555888", "VALUE_TYPE": "WORK" } 
                                'VALUE' => $processedPhone,
                                "VALUE_TYPE" => "WORK"
                            ];
                            $processedPhones[] = $resultPhone;
                        }
                        // // Добавляем обработанный номер в массив
                        // $processedPhones[] = $processedPhone;
                        // }
                    }
                }
            }
            // print_r($result);
            $result = $processedPhones;
        }

        // Log::channel('telegram')->info('TEST PHONE', ['$result' => $result]);

        return $result;
    }

    protected function  getHistoryField($event, $currentFieldValue) //events
    {
        //    ОП История (Комментарии)	general	multiple		op_mhistory

        $pFields =  $this->portalBxCompany['bitrixfields'];
        $result = null;
        $pFieldBxId = null;
        $resultValue = $currentFieldValue;
        foreach ($pFields as $pField) {
            if ($pField['code'] === 'op_mhistory') {
                $pFieldBxId = 'UF_CRM_' . $pField['bitrixId'];
                $result = [$pFieldBxId => null];
            }
        }

        // foreach ($events as $event) {

        $date = $this->getDateTimeValue($event['date'], $event['time']);
        $eventValue = $date . ' ' . $event['eventType'];
        if ($event['comment'] !== "" &&  $event['comment'] !== null && $event['comment'] !== "NULL"  && $event['comment'] !== "-") {

            $eventValue = $eventValue . "\n " . $event['comment'];
        }

        if ($event['planComment'] !== "" &&  $event['planComment'] !== null && $event['planComment'] !== "NULL"  && $event['planComment'] !== "-") {

            $eventValue = $eventValue . "\n " . "План: " . $event['planComment'];
        }
        if ($event['contact'] !== "" &&  $event['contact'] !== null && $event['contact'] !== "NULL"  && $event['contact'] !== "-") {

            $eventValue = $eventValue . "\n " . "Контакт: " . $event['contact'];
        }

        array_push($resultValue, $eventValue);
        // }

        $result = [$pFieldBxId => $resultValue];
        return  $result;
    }
    protected function getCompanyWorkStatust($garusResultat)
    {

        $pFields =  $this->portalBxCompany['bitrixfields'];
        $result = null;
        foreach ($pFields as $pField) {
            if ($pField['code'] === 'op_work_status') {
                $result = ['UF_CRM_' . $pField['bitrixId'] => null];

                if (!empty($pField['items'])) {
                    foreach ($pField['items'] as $pItem) {
                        if ($garusResultat == $pItem['name']) {
                        }
                        switch ($garusResultat) {
                            case 'Отказ':
                            case 'Гарант/ Запрет':
                            case 'Конкурент':
                            case 'Неверный контакт':
                            case 'Нет финансирования':
                            case 'Нет_ перспектив':
                            case 'Пересечение':
                            case 'Покупает ГО':
                            case 'Потерявшиеся/Закрывшиеся':
                            case 'ХВА':
                            case 'чужая территория':
                                if ($pItem['code'] === 'op_status_fail') {
                                    $result = ['UF_CRM_' . $pField['bitrixId'] => $pItem['bitrixId']];
                                }
                                break;
                            case 'Клиенты':
                            case 'Пользователи':
                            case 'Должники':

                                if ($pItem['code'] === 'op_status_success') {
                                    $result = ['UF_CRM_' . $pField['bitrixId'] => $pItem['bitrixId']];
                                }
                                break;
                            case 'Чердак':
                                if ($pItem['code'] === 'long') {
                                    $result = ['UF_CRM_' . $pField['bitrixId'] => $pItem['bitrixId']];
                                }
                                break;


                            default:
                                if ($pItem['code'] === 'work') {
                                    $result = ['UF_CRM_' . $pField['bitrixId'] => $pItem['bitrixId']];
                                }


                                break;
                        }
                    }
                }
            }
        }

        return  $result;
    }
    protected function getCompanyItemFromName($garusResultat, $fieldCode) //resulotat and source
    {

        $pFields =  $this->portalBxCompany['bitrixfields'];
        $result = null;
        foreach ($pFields as $pField) {

            if ($pField['code'] === $fieldCode) {
                $result = ['UF_CRM_' . $pField['bitrixId'] => null];

                if (!empty($pField['items'])) {
                    foreach ($pField['items'] as $pItem) {
                        $normalizedGarusResultat = $this->normalizeString($garusResultat);
                        $normalizedPItemName = $this->normalizeString($pItem['name']);

                        if ($normalizedGarusResultat == $normalizedPItemName) {

                            $result = ['UF_CRM_' . $pField['bitrixId'] => $pItem['bitrixId']];
                        }
                    }
                }
            }
        }

        return  $result;
    }

    protected function normalizeString($string)
    {
        // Приведение к нижнему регистру
        $string = mb_strtolower($string);


        // Удаление пробелов и спецсимволов, оставляя буквы и цифры (как кириллицу, так и латиницу)
        $string = preg_replace('/[^\p{L}\p{N}]/u', '', $string);

        return $string;
    }
    protected function getCompanyConcurent($garusConcurent)
    {

        $pFields =  $this->portalBxCompany['bitrixfields'];
        $result = null;
        foreach ($pFields as $pField) {
            if ($pField['code'] === 'op_concurents') {

                $pFieldBxId = 'UF_CRM_' . $pField['bitrixId'];
                $result = [$pFieldBxId => null];
                // k
                // action
                // kodex
                // bitrix
                // kontur
                // internet
                // magazine
                if (!empty($pField['items'])) {
                    foreach ($pField['items'] as $pItem) {
                        switch ($garusConcurent) {
                            case 'К+':
                                if ($pItem['code'] === 'k') {
                                    $result = [$pFieldBxId => $pItem['bitrixId']];
                                }
                                break;
                            case 'Актион':
                                if ($pItem['code'] === 'action') {
                                    $result = [$pFieldBxId => $pItem['bitrixId']];
                                }
                                break;
                            case 'Кодекс':
                                if ($pItem['code'] === 'kodex') {
                                    $result = [$pFieldBxId => $pItem['bitrixId']];
                                }
                                break;

                            case '1С':
                                if ($pItem['code'] === 'bitrix') {
                                    $result = [$pFieldBxId => $pItem['bitrixId']];
                                }
                                break;

                            case 'Контур':
                                if ($pItem['code'] === 'kontur') {
                                    $result = [$pFieldBxId => $pItem['bitrixId']];
                                }
                                break;
                            case 'Интернет':
                                if ($pItem['code'] === 'internet') {
                                    $result = [$pFieldBxId => $pItem['bitrixId']];
                                }
                                break;

                            case 'Журналы':
                                if ($pItem['code'] === 'magazine') {
                                    $result = [$pFieldBxId => $pItem['bitrixId']];
                                }
                                break;

                            default:
                                $result = [$pFieldBxId => null];

                                break;
                        }
                    }
                }
            }
        }

        return  $result;
    }
    protected function getCompanyCategory($garusCategory)
    {

        $pFields =  $this->portalBxCompany['bitrixfields'];
        $result =  null;
        foreach ($pFields as $pField) {
            if ($pField['code'] === 'op_category') {
                $pFieldBxId = 'UF_CRM_' . $pField['bitrixId'];
                $result = [$pFieldBxId => null];
                // kkk
                // kk
                // vip
                // k
                // c
                if (!empty($pField['items'])) {
                    foreach ($pField['items'] as $pItem) {
                        switch ($garusCategory) {
                            case 'ККК':
                                if ($pItem['code'] === 'kkk') {
                                    $result = [$pFieldBxId  => $pItem['bitrixId']];
                                }
                                break;
                            case 'КК':
                                if ($pItem['code'] === 'kk') {
                                    $result = [$pFieldBxId => $pItem['bitrixId']];
                                }
                                break;
                            case 'VIP':
                                if ($pItem['code'] === 'vip') {
                                    $result = [$pFieldBxId => $pItem['bitrixId']];
                                }
                                break;

                            case 'К':
                                if ($pItem['code'] === 'k') {
                                    $result = [$pFieldBxId  => $pItem['bitrixId']];
                                }
                                break;

                            case 'С':
                                if ($pItem['code'] === 'c') {
                                    $result = [$pFieldBxId  => $pItem['bitrixId']];
                                }
                                break;
                            case 'М':
                                if ($pItem['code'] === 'm') {
                                    $result = [$pFieldBxId  => $pItem['bitrixId']];
                                }
                                break;


                            default:
                                $result = [$pFieldBxId  => null];

                                break;
                        }
                    }
                }
            }
        }

        return $result;
    }
    protected function getCompanyPrognoz($garusPrognoz)
    {

        $pFields =  $this->portalBxCompany['bitrixfields'];
        $result =  null;
        foreach ($pFields as $pField) {
            if ($pField['code'] === 'op_prospects') {
                // op_prospects	Красный	red
                // op_prospects	Желтый	yellow
                // op_prospects	Зеленый	green


                if (!empty($pField['items'])) {
                    foreach ($pField['items'] as $pItem) {
                        switch ($garusPrognoz) {
                            case 'красный':
                                if ($pItem['code'] === 'red') {
                                    $result = ['UF_CRM_' . $pField['bitrixId'] => $pItem['bitrixId']];
                                }
                                break;
                            case 'желтый':
                                if ($pItem['code'] === 'yellow') {
                                    $result = ['UF_CRM_' . $pField['bitrixId'] => $pItem['bitrixId']];
                                }
                                break;
                            case 'зеленый':
                            case 'зёленый':
                                if ($pItem['code'] === 'green') {
                                    $result = ['UF_CRM_' . $pField['bitrixId'] => $pItem['bitrixId']];
                                }
                                break;

                            default:
                                $result = ['UF_CRM_' . $pField['bitrixId'] => null];

                                break;
                        }
                    }
                }
            }
        }

        return $result;
    }
    protected function getCompanyStatus($garusConcurent)
    {

        $pFields =  $this->portalBxCompany['bitrixfields'];
        $result = null;
        foreach ($pFields as $pField) {
            if ($pField['code'] === 'op_client_status') {
                // free
                // chok
                // nok
                // ok
                // stranger_kup
                // stranger_kupkk
                // stranger_kgurp
                // own_kup
                // own_kupkk
                // own_kgurp
                if (!empty($pField['items'])) {
                    foreach ($pField['items'] as $pItem) {
                        switch ($garusConcurent) {
                            case 'ЧОК':
                                if ($pItem['code'] === 'chok') {
                                    $result = ['UF_CRM_' . $pField['bitrixId'] => $pItem['bitrixId']];
                                }
                                break;
                            case 'НОК':
                                if ($pItem['code'] === 'nok') {
                                    $result = ['UF_CRM_' . $pField['bitrixId'] => $pItem['bitrixId']];
                                }
                                break;
                            case 'ОК':
                                if ($pItem['code'] === 'ok') {
                                    $result = ['UF_CRM_' . $pField['bitrixId'] => $pItem['bitrixId']];
                                }
                                break;

                            case 'Чужой КУП':
                                if ($pItem['code'] === 'stranger_kup') {
                                    $result = ['UF_CRM_' . $pField['bitrixId'] => $pItem['bitrixId']];
                                }
                                break;
                            case 'Чужой КУП КК':
                                if ($pItem['code'] === 'stranger_kupkk') {
                                    $result = ['UF_CRM_' . $pField['bitrixId'] => $pItem['bitrixId']];
                                }
                                break;
                            case 'Чужой КГУ РП':
                                if ($pItem['code'] === 'stranger_kgurp') {
                                    $result = ['UF_CRM_' . $pField['bitrixId'] => $pItem['bitrixId']];
                                }

                                break;
                            case 'Свой КУП':
                                if ($pItem['code'] === 'own_kup') {
                                    $result = ['UF_CRM_' . $pField['bitrixId'] => $pItem['bitrixId']];
                                }

                                break;
                            case 'Свой КУП КК':
                                if ($pItem['code'] === 'own_kupkk') {
                                    $result = ['UF_CRM_' . $pField['bitrixId'] => $pItem['bitrixId']];
                                }

                                break;
                            case 'Свой КГУ РП':
                                if ($pItem['code'] === 'own_kgurp') {
                                    $result = ['UF_CRM_' . $pField['bitrixId'] => $pItem['bitrixId']];
                                }

                                break;
                            default:
                                if ($pItem['code'] === 'free') {
                                    $result = ['UF_CRM_' . $pField['bitrixId'] => $pItem['bitrixId']];
                                }
                                break;
                        }
                    }
                }
            }
        }

        return  $result;
    }
    protected function getCompanyPerspect($garusFailReasone)
    {

        $pFields =  $this->portalBxCompany['bitrixfields'];
        $result = null;
        foreach ($pFields as $pField) {
            if ($pField['code'] === 'op_prospects_type') {
                // op_prospects_good
                // op_prospects_nopersp
                // op_prospects_garant
                // op_prospects_go
                // op_prospects_territory
                // op_prospects_acountant
                // op_prospects_autsorc
                // op_prospects_depend
                // op_prospects_nophone
                // op_prospects_company
                // op_prospects_fail
                if (!empty($pField['items'])) {
                    foreach ($pField['items'] as $pItem) {
                        switch ($garusFailReasone) {
                            case 'Гарант/Запрет':
                                if ($pItem['code'] === 'op_prospects_garant') {
                                    $result = ['UF_CRM_' . $pField['bitrixId'] => $pItem['bitrixId']];
                                }
                                break;
                            case 'Нет перспектив':
                                if ($pItem['code'] === 'op_prospects_nopersp') {
                                    $result = ['UF_CRM_' . $pField['bitrixId'] => $pItem['bitrixId']];
                                }
                                break;
                            case 'Покупает ГО':
                                if ($pItem['code'] === 'op_prospects_go') {
                                    $result = ['UF_CRM_' . $pField['bitrixId'] => $pItem['bitrixId']];
                                }
                                break;

                            case 'Чужая территория':
                                if ($pItem['code'] === 'op_prospects_territory') {
                                    $result = ['UF_CRM_' . $pField['bitrixId'] => $pItem['bitrixId']];
                                }
                                break;
                            case 'Отказ':
                                if ($pItem['code'] === 'op_prospects_fail') {
                                    $result = ['UF_CRM_' . $pField['bitrixId'] => $pItem['bitrixId']];
                                }
                                break;

                            default:
                                if ($pItem['code'] === 'op_prospects_good') {
                                    $result = ['UF_CRM_' . $pField['bitrixId'] => $pItem['bitrixId']];
                                }
                                break;
                        }
                    }
                }
            }
        }
        return   $result;
    }


    /**
     * LIST
     */
    public function getWorkstatusFieldItemValue(
        $portalField, //with items
        $workStatus,
        $planEventType // only PLAN ! event type
    ) {
        $resultItemBtxId = null;
        //         inJob
        // setAside
        // success
        // fail
        // op_work_status

        // В работе	work
        // Отложена	long

        // В решении	in_progress
        // В оплате	money_await
        // Продажа	    op_status_success
        // Отказ	    op_status_fail
        $resultCode = 'work';
        switch ($workStatus) {
            case 'inJob':
                $resultCode = 'work';

                if ($planEventType == 'hot') {
                    $resultCode = 'in_progress';
                } else  if ($planEventType == 'moneyAwait') {
                    $resultCode = 'money_await';
                }


                break;
            case 'setAside': //in_long
                $resultCode = 'long';
                break;
            case 'fail':
                $resultCode = 'op_status_fail';
                break;
            case 'success':
                $resultCode = 'op_status_success';
                break;
            default:
                break;
        }


        if (!empty($portalField)) {
            if (!empty($portalField['items'])) {
                $pitems = $portalField['items'];
                foreach ($pitems as $pitem) {
                    if (!empty($pitem['code'])) {
                        if ($pitem['code'] == $resultCode) {
                            $resultItemBtxId = $pitem['bitrixId'];
                        }
                    }
                }
            }
        }
        // Log::channel('telegram')->info('HOOK TEST getWorkstatusFieldItemValue', [
        //     'resultCode' => $resultCode,
        //     'planEventType' => $planEventType,
        //     'workStatus' => $workStatus,
        //     'resultItemBtxId' => $resultItemBtxId,
        // ]);
        return $resultItemBtxId;
    }


    public function getProspectsFieldItemValue(
        $portalField, //with items
        $workStatus,
        $failType
    ) {
        $resultItemBtxId = null;
        //         inJob
        // setAside
        // success
        // fail


        // {id: 0, code: "op_prospects_good", name: "Перспективная", isActive: false} 
        // {id: 1, code: "op_prospects_good", name: "Нет перспектив", isActive: false} 
        // {id: 2, code: "garant", name: "Гарант/Запрет", isActive: true} 
        // {id: 3, code: "go", name: "Покупает ГО", isActive: true} 
        // {id: 4, code: "territory", name: "Чужая территория", isActive: true} 
        // {id: 5, code: "accountant", name: "Бухприх", isActive: true} 
        // {id: 6, code: "autsorc", name: "Аутсорсинг", isActive: true} 
        // {id: 7, code: "depend", name: "Несамостоятельная организация", isActive: true} 
        // {id: 8, code: "op_prospects_nophone", name: "Недозвон", isActive: true}
        // {id: 9, code: "op_prospects_company", name: "Компания не существует", isActive: true}
        // {id: 10, code: "failure", name: "Отказ", isActive: true}


        // Перспективность	op_prospects_type	Перспективная	op_prospects_good	calling
        // Нет перспектив	op_prospects_nopersp	calling
        // Гарант/Запрет	op_prospects_garant	calling
        // Покупает ГО	op_prospects_go	calling
        // Чужая территория	op_prospects_territory	calling
        // Бухприх	op_prospects_acountant	calling
        // Аутсорсинг	op_prospects_autsorc	calling
        // Несамостоятельная организация	op_prospects_depend	calling
        // недозвон	op_prospects_nophone	calling
        // компания не существует	op_prospects_company	calling
        // не хотят общаться	op_prospects_off	calling
        // Отказ	op_prospects_fail	calling


        $resultCode = 'op_prospects_good';
        if ($workStatus !== 'inJob' && $workStatus !== 'success' && $workStatus !== 'setAside') {

            if (!empty($failType) && !empty($failType['code'])) {
                $failCode = $failType['code'];

                switch ($failCode) {
                    case 'op_prospects_nopersp': //Нет перспектив
                        $resultCode = 'op_prospects_nopersp';

                        break;
                    case 'garant': //Гарант/Запрет
                        $resultCode = 'op_prospects_garant';
                        break;
                    case 'go':
                        $resultCode = 'op_prospects_go';
                        break;
                    case 'territory':
                        $resultCode = 'op_prospects_territory';
                        break;
                    case 'accountant':
                        $resultCode = 'op_prospects_acountant';
                        break;
                    case 'autsorc':
                        $resultCode = 'op_prospects_autsorc';
                        break;
                    case 'depend':
                        $resultCode = 'op_prospects_depend';
                        break;

                        //todo
                    case 'op_prospects_nophone':  //недозвон
                    case 'op_prospects_company': //компания не существует
                    case 'op_prospects_off': //не хотят общаться
                        $resultCode = $failCode;
                        break;

                    case 'failure':
                        $resultCode = 'op_prospects_fail';

                        break;


                    default:

                        $resultCode = 'op_prospects_good';
                        break;
                }
            }
        }


        if (!empty($portalField)) {
            if (!empty($portalField['items'])) {
                $pitems = $portalField['items'];
                foreach ($pitems as $pitem) {
                    if (!empty($pitem['code'])) {
                        if ($pitem['code'] == $resultCode) {
                            $resultItemBtxId = $pitem['bitrixId'];
                            break;
                        }
                    }
                }
            }
        }

        return $resultItemBtxId;
    }

    protected function getFullDepartment()
    {
        date_default_timezone_set('Europe/Moscow'); // Установка временной зоны
        $currentMonthDay = date('md');
        $result = [];
        $departmentResult = null;
        $generalDepartment = null;

        $childrenDepartments = null;
        $resultGeneralDepartment = [];

        $resultChildrenDepartments = [];
        try {
            //code...

            // записывает в session подготовленную data department по domain




            $sessionKey = 'department_' . $this->domain . '_' . $currentMonthDay;
            $sessionData = FullEventInitController::getSessionItem($sessionKey);

            if (!empty($sessionData)) {
                print_r('fromSession');
                if (!empty($sessionData['department'])) {
                    $result =  $sessionData;
                    $departmentResult = $sessionData['department'];
                    $result['fromSession'] = true;
                }
            } else {
                print_r('WITHOUT ession');
            }

            if (empty($departmentResult)) {                               // если в сессии нет department
                $departamentService = new BitrixDepartamentService($this->hook);
                $department =  $departamentService->getDepartamentIdByPortal($this->portal);

                $allUsers = [];
                if (!empty($department)) {

                    if (!empty($department['bitrixId'])) {
                        $departmentId =  $department['bitrixId'];


                        if ($departmentId) {
                            $generalDepartment = $departamentService->getDepartments([
                                'ID' =>  $departmentId
                            ]);
                            $childrenDepartments = $departamentService->getDepartments([
                                'PARENT' =>  $departmentId
                            ]);


                            if (!empty($generalDepartment)) {
                                foreach ($generalDepartment as $gDep) {
                                    if (!empty($gDep)) {
                                        if (!empty($gDep['ID'])) {
                                            // array_push($departamentIds, $gDep['ID']);
                                            $departmentUsers = $departamentService->getUsersByDepartment($gDep['ID']);

                                            $resultDep = $gDep;
                                            $resultDep['USERS'] = $departmentUsers;
                                            $allUsers = array_merge($allUsers, $departmentUsers);
                                            array_push($resultGeneralDepartment, $resultDep);
                                        }
                                    }
                                }
                            }

                            if (!empty($childrenDepartments)) {
                                foreach ($childrenDepartments as $chDep) {
                                    if (!empty($chDep)) {
                                        if (!empty($chDep['ID'])) {
                                            // array_push($departamentIds, $chDep['ID']);
                                            $departmentUsers  = $departamentService->getUsersByDepartment($chDep['ID']);
                                            $resultDep = $gDep;
                                            $resultDep['USERS'] = $departmentUsers;

                                            $allUsers = array_merge($allUsers, $departmentUsers);
                                            array_push($resultChildrenDepartments, $resultDep);
                                        }
                                    }
                                }
                            }
                        }
                        $departmentResult = [
                            'generalDepartment' => $resultGeneralDepartment,
                            'childrenDepartments' => $resultChildrenDepartments,
                            'allUsers' => $allUsers,
                        ];
                        $result =  ['department' => $departmentResult];
                        FullEventInitController::setSessionItem(
                            $sessionKey,
                            $result
                        );
                    }
                }
            }
            // print_r('<br>');
            // print_r('allUsers');
            // print_r('<br>');
            // print_r($result['department']['generalDepartment']);
            return $result;
        } catch (\Throwable $th) {
            return null;
        }
    }



    protected function getDateTimeValue($dateTimeValue)
    {

        // $date = Carbon::parse($dateValue);
        // $time = Carbon::parse($timeValue);
        // // Объединяем дату и время
        // $datetime = Carbon::create(
        //     $date->year,
        //     $date->month,
        //     $date->day,
        //     $time->hour,
        //     $time->minute,
        //     $time->second
        // );
        // $formattedDatetime = $datetime->format('d.m.Y H:i:s');

        $datetime = Carbon::parse($dateTimeValue);

        // Форматируем дату и время
        $formattedDatetime = $datetime->format('d.m.Y H:i:s');
        return $formattedDatetime;
    }


    protected function  getListFlowData($event, $companyId) //events
    {
        $flowdata = null;
        $hook = $this->hook;
        $bitrixLists = $this->portalBxLists;
        // $eventType, // xo warm presentation, offer invoice
        // $eventTypeName, //звонок по решению по оплате
        // $eventAction,  // plan done //если будет репорт и при этом не было переноса придет done или nodone - типа состоялся или нет
        // // $eventName,
        // $deadline,
        // $created,
        // $responsible,
        // $suresponsible,
        // $companyId,
        // $comment,
        // $workStatus, //inJob
        // $resultStatus,  // result noresult   .. without expired new !
        // $noresultReason,
        // $failReason,
        // $failType,
        // $dealIds,
        // $currentBaseDealId

        /**
         * FOR DOCUMENT FLOW
         */

        //  $eventType, // ev_invoice,  ev_offer_pres ....

        //  // Коммерческое Предлжение	event_type	ev_offer	EV_OFFER
        //  // Счет	event_type	ev_invoice	EV_INVOICE
        //  // Коммерческое Предлжение после презентации	event_type	ev_offer_pres	EV_OFFER_PRES
        //  // Счет после презентации	event_type	ev_invoice_pres	EV_INVOICE_PRES
        //  // Договор	event_type	ev_contract	EV_CONTRACT
        //  // Поставка	event_type	ev_supply	EV_SUPPLY
        //  $eventTypeName, //Коммерческое Предлжение   Счет после презентации Поставка


        //  $eventAction,  // 
        //  // Отправлен	event_action	act_send	ACT_SEND
        //  // Подписан	event_action	act_sign	ACT_SIGN
        //  // Оплачен	event_action	act_pay	ACT_PAY
        //  // $nowDate,
        //  $created,
        //  $responsible,
        //  $suresponsible,
        //  $companyId,
        //  $comment,
        //  $dealIds,
        //  $currentBaseDealId = null

        return $flowdata;
    }

    protected function getListFlow($event, $companyId, $responsibleId, $commands)
    {

        $resultEventType = 'warm';
        $resultAction = 'done';
        $isDocumentFlow = false;
        $resultStatus = 'result';
        $workStatus = ['code' => 'inJob']; //setAside fail
        $noresultReason = '';
        $failReason = '';
        $nowDate = '';
        $resultBatchCommands = '';
        $date = $this->getDateTimeValue($event['dateTime']);
        $comment = $event['comment'];


        if ($event['planComment'] !== "" &&  $event['planComment'] !== null && $event['planComment'] !== "NULL"  && $event['planComment'] !== "-") {

            $comment = $comment . "\n " . "План: " . $event['planComment'];
        }
        if ($event['contact'] !== "" &&  $event['contact'] !== null && $event['contact'] !== "NULL"  && $event['contact'] !== "-") {

            $comment = $comment . "\n " . "Контакт: " . $event['contact'];
        }

        // $normalizedGarusResultat = $this->normalizeString($event['eventType']);
        $actions = [
            $this->normalizeString('Звонок') => 'call',
            $this->normalizeString('Пред.договоренность') => 'plan',
            $this->normalizeString('Заявка на презу') => 'presentation',
            $this->normalizeString('Дист. заявка') => 'presentation',
            $this->normalizeString('Тлф.Заявка') => 'presentation',
            $this->normalizeString('Выезд') => 'presentation',

        ];
        $normalizedGarusResultat = $this->normalizeString($event['eventType']);

        $switchCases = [
            'plan' => [
                'cases' => [
                    $this->normalizeString('Пред.договоренность'),
                    $this->normalizeString('Заявка на презу'),
                    $this->normalizeString('Дист. заявка'),
                    $this->normalizeString('Тлф.Заявка'),
                    $this->normalizeString('Заявка'),

                ],
                'action' => 'plan',
                'eventType' => 'presentation'
            ],
            'done_presentation' => [
                'cases' => [
                    $this->normalizeString('Презентация'),
                    $this->normalizeString('Выезд')
                ],
                'action' => 'done',
                'eventType' => 'presentation'
            ],
            'expired' => [
                'cases' => [
                    $this->normalizeString('Перенос'),
                    $this->normalizeString('Повтор')
                ],
                'status' => 'expired'
            ],
            'act_send_offer' => [
                'cases' => [
                    $this->normalizeString('Отправлено КП'),
                    $this->normalizeString('Дист. Компред'),
                    $this->normalizeString('Компред'),
                    $this->normalizeString('кп'),
                    $this->normalizeString('коммерческое'),
                    $this->normalizeString('Ком.пред'),
                ],
                'action' => 'act_send',
                'eventType' => 'ev_offer',
                'isDocumentFlow' => true
            ],
            'act_send_contract' => [
                'cases' => [
                    $this->normalizeString('Отправлен договор'),
                    $this->normalizeString('договор Отправлен'),
                    // $this->normalizeString('договор'),
                    // $this->normalizeString('Договор подписан')
                ],
                'action' => 'act_send',
                'eventType' => 'ev_contract',
                'isDocumentFlow' => true
            ],
            'act_send_contract' => [
                'cases' => [
                    // $this->normalizeString('Отправлен договор'),
                    $this->normalizeString('Подписан договор'),
                    // $this->normalizeString('договор'),
                    $this->normalizeString('Договор подписан')
                ],
                'action' => 'act_sign',
                'eventType' => 'ev_contract',
                'isDocumentFlow' => true
            ],
            'act_send_invoice' => [
                'cases' => [
                    $this->normalizeString('Выставлен счет'),
                    $this->normalizeString('Счет выписан'),
                    // $this->normalizeString('Счет'),
                    // $this->normalizeString('Счет оплачен'),
                    // $this->normalizeString('оплачен Счет'),

                ],
                'action' => 'act_send',
                'eventType' => 'ev_invoice',
                'isDocumentFlow' => true
            ],

            'act_send_invoice' => [
                'cases' => [
                    // $this->normalizeString('Выставлен счет'),
                    // $this->normalizeString('Счет выписан'),
                    // $this->normalizeString('Счет'),
                    $this->normalizeString('Счет оплачен'),
                    $this->normalizeString('оплачен Счет'),
                    $this->normalizeString('оплачен'),
                    $this->normalizeString('оплата'),

                ],
                'action' => 'act_pay',
                'eventType' => 'ev_invoice',
                'isDocumentFlow' => true
            ],
            // 'act_sign_contract' => [
            //     'cases' => [
            //         $this->normalizeString('Договор подписан')
            //     ],
            //     'action' => 'act_sign',
            //     'eventType' => 'ev_contract',
            //     'isDocumentFlow' => true
            // ],
            // 'act_pay_invoice' => [
            //     'cases' => [
            //         $this->normalizeString('Счет оплачен'),
            //         $this->normalizeString('оплачен Счет'),
            //     ],
            //     'action' => 'act_pay',
            //     'eventType' => 'ev_invoice_pres',
            //     'isDocumentFlow' => true
            // ],
            'done_supply' => [
                'cases' => [
                    $this->normalizeString('Поставка'),
                    $this->normalizeString('Установка'),

                ],
                'action' => 'done',
                'eventType' => 'ev_supply',
                'isDocumentFlow' => true
            ],
            'cancel' => [
                'cases' => [
                    $this->normalizeString('Отмена'),
                    $this->normalizeString('Отказ'),
                ],
                'status' => 'nodone',
                'resultStatus' => 'noresult'
            ]
        ];

        foreach ($switchCases as $case) {
            if (in_array($normalizedGarusResultat, $case['cases'])) {
                if (isset($case['action'])) {
                    $resultAction = $case['action'];
                }
                if (isset($case['eventType'])) {
                    $resultEventType = $case['eventType'];
                }
                if (isset($case['status'])) {
                    $resultStatus = $case['status'];
                }
                if (isset($case['isDocumentFlow'])) {
                    $isDocumentFlow = $case['isDocumentFlow'];
                }
                break;
            }
        }
        // $fullDepartment = $this->getFullDepartment();
        $fullDepartment = $this->department;
        $userId = 201; //201 - man savchuk in rostov
        $fullDepartment =  $fullDepartment['department'];
        if (!empty($fullDepartment)) {
            if (!empty($fullDepartment['allUsers'])) {

                foreach ($fullDepartment['allUsers'] as $user) {

                    $responsible = $event['responsible'];

                    $parts = explode(' ', $responsible);
                    $lastName = mb_strtolower(trim($parts[0])); // Приводим к нижнему регистру
                    $userLastName = mb_strtolower(trim($user['LAST_NAME'])); // Приводим фамилию пользователя к нижнему регистру


                    if ($lastName === $userLastName) {
                        $userId = $user['ID'];
                        break; // Прекратить перебор после нахождения пользователя

                    } else {
                        // print_r('<br>');
                        // print_r($responsible);
                        // print_r('<br>');
                        // print_r($lastName);
                        // print_r('<br>');
                    }
                }
            }
        }

        if ($isDocumentFlow) {
            $commands =  BitrixListDocumentFlowService::getBatchListFlow(  //report - отчет по текущему событию
                $this->hook,
                $this->portalBxLists,
                $resultEventType,
                $event['eventType'],
                $resultAction, // 'act_send',  // сделано, отправлено
                $userId,
                $userId,
                $userId,
                $companyId,
                $comment,
                null, // $currentBxDealIds,
                null, //  $this->currentBaseDeal['ID']
                $date,
                $event['eventType'],
                $commands


            );
        } else {
            // $commands = BitrixListFlowService::getBatchListFlow(  //report - отчет по текущему событию
            //     $this->hook,
            //     $this->portalBxLists,
            //     $resultEventType,
            //     $event['eventType'],
            //     $resultAction,
            //     // $this->stringType,
            //     '', //$this->planDeadline,
            //     $userId,
            //     $userId,
            //     $userId,
            //     $companyId,
            //     $comment,
            //     $workStatus,
            //     $resultStatus, // result noresult expired,
            //     $noresultReason,
            //     $failReason,
            //     '', // $failType,
            //     '', // $currentDealIds,
            //     '', // $currentBaseDealId
            //     $date,
            //     $event['eventType'], //$hotName
            //     $commands

            // );
        }

        return $commands;
    }
}
