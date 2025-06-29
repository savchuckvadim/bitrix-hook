<?php

namespace App\Services;

use App\Http\Controllers\APIBitrixController;
use App\Services\General\BitrixBatchService;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BitrixGeneralService
{

    //smart
    static function getSmartItem(
        $hook,
        $leadId, //lidId ? from lead
        $companyId, //companyId ? from company
        $userId,
        $smart, //april smart data
        $excepStages = null

    ) {
        // lidIds UF_CRM_7_1697129081
        $currentSmart = null;
        if (!$excepStages) {
            $excepStages  = ["DT162_26:SUCCESS", "DT156_12:SUCCESS"];
        }

        try {
            $method = '/crm.item.list.json';
            $url = $hook . $method;
            if ($companyId) {
                $data =  [
                    'entityTypeId' => $smart['bitrixId'],
                    'filter' => [
                        "!=stage_id" => $excepStages,
                        "=assignedById" => $userId,
                        'COMPANY_ID' => $companyId,

                    ],
                    // 'select' => ["ID"],
                ];
            }
            if ($leadId) {
                $data =  [
                    'entityTypeId' => $smart['bitrixId'],
                    'filter' => [
                        "!=stage_id" => $excepStages,
                        "=assignedById" => $userId,

                        "=%ufCrm7_1697129081" => '%' . $leadId . '%',

                    ],
                    // 'select' => ["ID"],
                ];
            }


            $response = Http::get($url, $data);
            // $responseData = $response->json();
            $responseData = APIBitrixController::getBitrixRespone($response, 'general service: getSmartItem');
            if (isset($responseData)) {
                if (!empty($responseData['items'])) {
                    $currentSmart =  $responseData['items'][0];
                }
            }

            return $currentSmart;
        } catch (\Throwable $th) {
            return $currentSmart;
        }
    }



    static function createSmartItem(
        $hook,
        $entityId,
        $fieldsData
    ) {
        $resultFields = null;
        try {
            $methodSmart = '/crm.item.add.json';
            $url = $hook . $methodSmart;



            $data = [
                'entityTypeId' => $entityId,
                'fields' =>  $fieldsData

            ];
            // Log::channel('telegram')->error('APRIL_HOOK createSmartItem', [

            //     'createSmartItem data' => $data
            // ]);


            $smartFieldsResponse = Http::get($url, $data);

            $responseData = APIBitrixController::getBitrixRespone($smartFieldsResponse, 'general service: createSmartItem');
            $resultFields = $responseData;
            // Log::channel('telegram')->error('APRIL_HOOK createSmartItem', [

            //     'resultFields' => $resultFields
            // ]);


            if (isset($responseData['item'])) {
                $resultFields = $responseData['item'];
            }
            return $resultFields;
        } catch (\Throwable $th) {
            return $resultFields;
        }
    }

    static function updateSmartItem($hook, $entityId, $smartId, $fieldsData)
    {

        $methodSmart = '/crm.item.update.json';
        $url = $hook . $methodSmart;

        $data = [
            'id' => $smartId,
            'entityTypeId' => $entityId,

            'fields' =>  $fieldsData

        ];



        $smartFieldsResponse = Http::get($url, $data);

        $responseData = APIBitrixController::getBitrixRespone($smartFieldsResponse, 'general service: updateSmartItemCold');
        $resultFields = $responseData;

        if (isset($responseData['item'])) {
            $resultFields = $responseData['item'];
        }

        return $resultFields;
    }


    static function deleteSmartItem($hook, $entityId, $smartId)
    {

        $methodSmart = '/crm.item.delete.json';
        $url = $hook . $methodSmart;

        $data = [
            'id' => $smartId,
            'entityTypeId' => $entityId,


        ];



        $smartFieldsResponse = Http::get($url, $data);

        $responseData = APIBitrixController::getBitrixRespone($smartFieldsResponse, 'general service: deleteSmartItem');
        $resultFields = $responseData;
        // Log::channel('telegram')->info(
        //     'lead/complete deleteSmartItem',
        //     [
        //         'responseData' => $responseData,

        //     ]
        // );
        return $resultFields;
    }


    //company
    static function updateCompany($hook, $companyId, $fieldsData)
    {
        $resultFields = null;
        $data = [
            'id' => $companyId,

            'fields' =>  $fieldsData,
            'params' =>  ["REGISTER_SONET_EVENT" => "Y"]

        ];
        try {
            $methodSmart = '/crm.company.update.json';
            $url = $hook . $methodSmart;



            $smartFieldsResponse = Http::get($url, $data);
            $responseData = APIBitrixController::getBitrixRespone($smartFieldsResponse, 'general service: updateCompany');
            $resultFields = $responseData;
            // Log::info('HOOK UPDT COMPANY ', [
            //     'resultFields' => $resultFields,
            //     'data' => $data
            // ]);

            return $resultFields;
        } catch (\Throwable $th) {
            Log::info('HOOK UPDT COMPANY ERROR', [
                'data' => $data
            ]);
            return $resultFields;
        }
    }

    static function updateContactsToCompanyRespnsible($hook, $companyId,  $fields)
    {
        $resultFields = null;

        try {
            $methodContactsIdsGet = '/crm.company.contact.items.get';
            $url = $hook . $methodContactsIdsGet;
            $contactsIdsResponse = Http::get($url, [
                'id' => $companyId
            ]);
            $contactsIdsData = APIBitrixController::getBitrixRespone($contactsIdsResponse, 'general service: updateCompany');
            $resulContactsIds = $contactsIdsData;

          

            if (!empty($resulContactsIds)) {
                if (is_array($resulContactsIds)) {
                    foreach ($resulContactsIds as $resulContact) {
                        if (!empty($resulContact['CONTACT_ID'])) {
                            $resulContactId = $resulContact['CONTACT_ID'];
                            $method = '/crm.contact.update';

                            $url = $hook . $method;

                            $data = [
                                'ID' => $resulContactId,
                                'fields' => $fields
                            ];
                            sleep(1);
                            $response = Http::get($url, $data);
                            $responseData = APIBitrixController::getBitrixRespone($response, 'general service: crm.contact.update');
                            $resultFields = $responseData;
                        }
                    }
                }
            }



            return $resultFields;
        } catch (\Throwable $th) {
            Log::info('HOOK UPDT COMPANY ERROR', [
                'data' => $data
            ]);
            return $resultFields;
        }
    }




    //lead

    static function updateLead($hook, $leadId, $fieldsData)
    {
        $resultLead = null;
        try {
            $methodSmart = '/crm.lead.update.json';
            $url = $hook . $methodSmart;

            $data = [
                'id' => $leadId,

                'fields' =>  $fieldsData

            ];



            $resultLeadResponse = Http::get($url, $data);

            $resultLeadData = APIBitrixController::getBitrixRespone($resultLeadResponse, 'general service: updateLead');
            $resultLead = $resultLeadData;


            return $resultLead;
        } catch (\Throwable $th) {
            return $resultLead;
        }
    }


    // general simple entity
    static function setEntity($hook, $entityType, $fieldsData)
    {
        $resultLead = null;
        try {
            $methodSmart = '/crm.' . $entityType . '.add';
            $url = $hook . $methodSmart;

            $data = [

                'fields' =>  $fieldsData

            ];



            $resultLeadResponse = Http::get($url, $data);

            $resultData = APIBitrixController::getBitrixRespone($resultLeadResponse, 'general service: updateEntity');
            $result = $resultData;


            return $result;
        } catch (\Throwable $th) {
            return $resultLead;
        }
    }

    static function getEntity($hook, $entityType, $entityId, $filter = null, $select = null)
    {
        $resultFields = null;
        try {
            $methodSmart = '/crm.' . $entityType . '.get';
            $url = $hook . $methodSmart;

            $data = [
                'id' => $entityId,



            ];

            if (!empty($select)) {
                $data['select'] = $select;
            }


            $smartFieldsResponse = Http::get($url, $data);
            $responseData = APIBitrixController::getBitrixRespone($smartFieldsResponse, 'general service: getEntity' . $entityType . ' hook: ' . $hook);
            $resultFields = $responseData;

            return $resultFields;
        } catch (\Throwable $th) {
            return $resultFields;
        }
    }

    static function getEntityByID($hook, $entityType, $entityId, $filter = null, $select = null)
    {
        $resultFields = null;
        try {
            $methodSmart = '/crm.' . $entityType . '.get';
            $url = $hook . $methodSmart;

            $data = [
                'ID' => $entityId,



            ];

            if (!empty($select)) {
                $data['select'] = $select;
            }


            $smartFieldsResponse = Http::get($url, $data);
            $responseData = APIBitrixController::getBitrixRespone($smartFieldsResponse, 'general service: getEntity' . $entityType . ' hook: ' . $hook);
            $resultFields = $responseData;

            return $resultFields;
        } catch (\Throwable $th) {
            return $resultFields;
        }
    }

    static function getEntityList($hook, $entityType, $filter = null, $select = null)
    {
        $resultFields = null;
        try {
            $methodSmart = '/crm.' . $entityType . '.list';
            $url = $hook . $methodSmart;

            $data = [
                'filter' => $filter,
                'select' => $select,
                'start' => -1,

            ];


            $smartFieldsResponse = Http::get($url, $data);
            $responseData = APIBitrixController::getBitrixRespone($smartFieldsResponse, 'general service: getEntity' . $entityType . ' hook: ' . $hook);
            $resultFields = $responseData;

            return $resultFields;
        } catch (\Throwable $th) {
            return $resultFields;
        }
    }

    static function getEntityListWithFullData($hook, $entityType, $data)
    {
        $resultFields = null;
        try {
            $method = '/crm.' . $entityType . '.list';
            $url = $hook . $method;

            $data['start'] = -1;


            $smartFieldsResponse = Http::get($url, $data);
            $responseData = APIBitrixController::getBitrixRespone($smartFieldsResponse, 'general service: getEntity' . $entityType . ' hook: ' . $hook);
            $resultFields = $responseData;

            return $resultFields;
        } catch (\Throwable $th) {
            return $resultFields;
        }
    }

    static function updateEntity(
        $hook,
        $entityType,
        $entityId,
        $fieldsData
    ) {
        $resultLead = null;
        try {
            $methodSmart = '/crm.' . $entityType . '.update';
            $url = $hook . $methodSmart;

            $data = [
                'id' => $entityId,
                'fields' =>  $fieldsData

            ];



            $resultLeadResponse = Http::get($url, $data);

            $resultData = APIBitrixController::getBitrixRespone($resultLeadResponse, 'general service: updateEntity');
            $result = $resultData;


            return $result;
        } catch (\Throwable $th) {
            return $resultLead;
        }
    }




    //task
    static function createTask(
        $parentMethod,
        $hook,
        $companyId,
        $leadId,
        // $deadline,
        // $createdId,
        // $currentSmartItemId,
        // $smartCrmId,
        // $taskTitle,
        // $responsibleId,
        // $callingGroupId,
        $taskData

    ) {
        //company and contacts
        $methodCompany = '/crm.company.get';
        $methodContacts = '/crm.contact.list';
        $methodTask = '/tasks.task.add';


        $nowDate = now();
        // $crm = $currentSmartItemId;

        $createdTask = null;
        $description = '';
        try {



            $url = $hook . $methodContacts;
            $contactsData =  [
                'FILTER' => [
                    'COMPANY_ID' => $companyId,

                ],
                'select' => ["ID", "NAME", "LAST_NAME", "SECOND_NAME", "TYPE_ID", "SOURCE_ID", "PHONE", "EMAIL", "COMMENTS"],
            ];
            $getCompanyData = [
                'ID'  => $companyId,
                'select' => ["TITLE", "PHONE", "EMAIL"],
            ];

            $contacts = Http::get($url,  $contactsData);


            $url = $hook . $methodCompany;
            $company = Http::get($url,  $getCompanyData);


            //contacts description
            $contactsString = '';
            $contactsTable = '[TABLE]';
            $contactRows = '';
            if (isset($contacts['result'])) {
                foreach ($contacts['result'] as  $contact) {
                    $contactRow = '[TR]';
                    $contactPhones = '';
                    if (isset($contact["PHONE"])) {
                        foreach ($contact["PHONE"] as $phone) {
                            $contactPhones = $contactPhones .  $phone["VALUE"] . "   ";
                        }
                    }

                    $emails = '';
                    if (isset($contact["EMAIL"])) {
                        foreach ($contact["EMAIL"] as $email) {
                            if (isset($email["VALUE"])) {
                                $emails = $emails .  $email["VALUE"] . "   ";
                            }
                        }
                    }



                    $contactsNameString =  $contact["NAME"] . " " . $contact["SECOND_NAME"] . " " . $contact["SECOND_NAME"];
                    $contactsFirstCell = ' [TD]' . $contactsNameString . '[/TD]';
                    $contactsPhonesCell = ' [TD]' . $contactPhones . '[/TD]';
                    $contactsEmailsCell = ' [TD]' . $emails . '[/TD]';



                    $contactRow = '[TR]' . $contactsFirstCell . ''  . $contactsPhonesCell . $contactsEmailsCell . '[/TR]';
                    $contactRows = $contactRows . $contactRow;
                }




                $contactsTable = '[TABLE]' . $contactRows . '[/TABLE]';
            }


            //company phones description
            $cmpnPhonesEmailsList = '';

            if (isset($company['result'])) {

                $cmpnPhonesEmailsList = '';
                if (isset($company['result']['PHONE'])) {
                    $companyPhones = $company['result']['PHONE'];
                    $cmpnyListContent = '';

                    foreach ($companyPhones as $phone) {
                        $cmpnyListContent = $cmpnyListContent . '[*]' .  $phone["VALUE"] . "   ";
                    }

                    if (isset($company['result']['EMAIL'])) {

                        $companyEmails = $company['result']['EMAIL'];

                        foreach ($companyEmails as $email) {
                            if (isset($email["VALUE"])) {
                                $cmpnyListContent = $cmpnyListContent . '[*]' .  $email["VALUE"] . "   ";
                            }
                        }
                    }

                    $cmpnPhonesEmailsList = '[LIST]' . $cmpnyListContent . '[/LIST]';
                }







                $companyPhones = '';

                $companyTitleString = '[B][COLOR=#0070c0]' . $company['result']['TITLE'] . '[/COLOR][/B]';
                $description =  $companyTitleString . '
            ' . '[LEFT][B]Контакты компании: [/B][/LEFT]' . $contactsTable;
                $description = $description . '' . $cmpnPhonesEmailsList;
            }
            $taskData['fields']['DESCRIPTION'] = $description;
            //task

            $url = $hook . $methodTask;

            $responseData = Http::get($url, $taskData);
            // Log::channel('telegram')->error('APRIL_HOOK', [
            //     'createColdTask' => [
            //         'url' => $url,
            //         'responseData' => $responseData,

            //     ]
            // ]);

            $createdTask =  APIBitrixController::getBitrixRespone($responseData, $parentMethod);

            return $createdTask;
        } catch (\Throwable $th) {
            $errorMessages =  [
                'message'   => $th->getMessage(),
                'file'      => $th->getFile(),
                'line'      => $th->getLine(),
                'trace'     => $th->getTraceAsString(),
            ];
            Log::error('ERROR: Exception caught',  $errorMessages);
            Log::info('error', ['error' => $th->getMessage()]);
            Log::channel('telegram')->info(
                'HOOK: general createTask from' . $parentMethod,
                ['error' => $th->getMessage()]
            );
            return  $createdTask;
        }
    }
    static function createTaskBatch(
        $parentMethod,
        $hook,
        $companyId,
        $leadId,
        // $deadline,
        // $createdId,
        // $currentSmartItemId,
        // $smartCrmId,
        // $taskTitle,
        // $responsibleId,
        // $callingGroupId,
        $taskData,
        $batchCommands

    ) {
        //company and contacts
        // $methodCompany = '/crm.company.get.json';
        // $methodContacts = '/crm.contact.list.json';
        // $methodTask = 'tasks.task.add';
        $batchService = new BitrixBatchService($hook);
        $methodCompany = 'crm.company.get';
        $methodContacts = 'crm.contact.list';
        $methodTask = 'tasks.task.add';
        // Команда на получение данных компании

        $nowDate = now();
        // $crm = $currentSmartItemId;

        $createdTask = null;
        $description = '';
        try {


            // $getCompanyData = [
            //     'ID' => $companyId,
            //     'select' => ["TITLE", "PHONE", "EMAIL"],
            // ];
            // $batchCommands['get_company'] = $batchService->getGeneralBatchCommand($getCompanyData, $methodCompany, null);

            // // Команда на получение списка контактов
            // $contactsData = [
            //     'FILTER' => [
            //         'COMPANY_ID' => $companyId,
            //     ],
            //     'select' => ["ID", "NAME", "LAST_NAME", "PHONE", "EMAIL"],
            // ];
            // $batchCommands['get_contacts'] = $batchService->getGeneralBatchCommand($contactsData, $methodContacts, null);

            // $contactDescription = '';
            // // for ($i = 0; $i < 10; $i++) {
            // //     $contactDescription .= 'Имя: $result[get_contacts][' . $i . '][NAME] $result[get_contacts][' . $i . '][LAST_NAME], ';
            // $contactDescription .= 'Телефон: $result[get_company][PHONE][0][VALUE], ';
            // $contactDescription .= 'Телефон: $result[get_company][PHONE][1][VALUE], ';
            //     $contactDescription .= 'Email: $result[get_contacts][' . $i . '][EMAIL][0][VALUE]' . "\n";
            // }

            // $companyTitleString = '[URL=https://april-dev.bitrix24.ru/crm/company/details/' . $companyId . '/][B][COLOR=#0070c0] Компания: $result[get_company][TITLE] [/COLOR][/B][/URl]';



            // $description = $companyTitleString . "\n" .

            //     $contactDescription;

            // $taskData['fields']['DESCRIPTION'] = $description;
            // $url = $hook . $methodContacts;
            // $contactsData =  [
            //     'FILTER' => [
            //         'COMPANY_ID' => $companyId,

            //     ],
            //     'select' => ["ID", "NAME", "LAST_NAME", "SECOND_NAME", "TYPE_ID", "SOURCE_ID", "PHONE", "EMAIL", "COMMENTS"],
            // ];
            // $getCompanyData = [
            //     'ID'  => $companyId,
            //     'select' => ["TITLE", "PHONE", "EMAIL"],
            // ];

            // $contacts = Http::get($url,  $contactsData);


            // $url = $hook . $methodCompany;
            // $company = Http::get($url,  $getCompanyData);


            //contacts description
            $contactsString = '';
            // $contactsTable = '[TABLE]';
            // $contactRows = '';
            // if (isset($contacts['result'])) {
            //     foreach ($contacts['result'] as  $contact) {
            //         $contactRow = '[TR]';
            //         $contactPhones = '';
            //         if (isset($contact["PHONE"])) {
            //             foreach ($contact["PHONE"] as $phone) {
            //                 $contactPhones = $contactPhones .  $phone["VALUE"] . "   ";
            //             }
            //         }

            //         $emails = '';
            //         if (isset($contact["EMAIL"])) {
            //             foreach ($contact["EMAIL"] as $email) {
            //                 if (isset($email["VALUE"])) {
            //                     $emails = $emails .  $email["VALUE"] . "   ";
            //                 }
            //             }
            //         }



            //         $contactsNameString =  $contact["NAME"] . " " . $contact["SECOND_NAME"] . " " . $contact["SECOND_NAME"];
            //         $contactsFirstCell = ' [TD]' . $contactsNameString . '[/TD]';
            //         $contactsPhonesCell = ' [TD]' . $contactPhones . '[/TD]';
            //         $contactsEmailsCell = ' [TD]' . $emails . '[/TD]';



            //         $contactRow = '[TR]' . $contactsFirstCell . ''  . $contactsPhonesCell . $contactsEmailsCell . '[/TR]';
            //         $contactRows = $contactRows . $contactRow;
            //     }




            //     $contactsTable = '[TABLE]' . $contactRows . '[/TABLE]';
            // }


            // //company phones description
            // $cmpnPhonesEmailsList = '';

            // if (isset($company['result'])) {

            //     $cmpnPhonesEmailsList = '';
            //     if (isset($company['result']['PHONE'])) {
            //         $companyPhones = $company['result']['PHONE'];
            //         $cmpnyListContent = '';

            //         foreach ($companyPhones as $phone) {
            //             $cmpnyListContent = $cmpnyListContent . '[*]' .  $phone["VALUE"] . "   ";
            //         }

            //         if (isset($company['result']['EMAIL'])) {

            //             $companyEmails = $company['result']['EMAIL'];

            //             foreach ($companyEmails as $email) {
            //                 if (isset($email["VALUE"])) {
            //                     $cmpnyListContent = $cmpnyListContent . '[*]' .  $email["VALUE"] . "   ";
            //                 }
            //             }
            //         }

            //         $cmpnPhonesEmailsList = '[LIST]' . $cmpnyListContent . '[/LIST]';
            //     }







            //     $companyPhones = '';

            //     $companyTitleString = '[B][COLOR=#0070c0]' . $company['result']['TITLE'] . '[/COLOR][/B]';
            //     $description =  $companyTitleString . '
            // ' . '[LEFT][B]Контакты компании: [/B][/LEFT]' . $contactsTable;
            //     $description = $description . '' . $cmpnPhonesEmailsList;
            // }
            // $taskData['fields']['DESCRIPTION'] = $description;
            // //task

            // $url = $hook . $methodTask;

            // $responseData = Http::get($url, $taskData);

            $batchKey = 'task_create';
            $batchCommand = $batchService->getGeneralBatchCommand($taskData, $methodTask, null);
            $batchCommands[$batchKey] = $batchCommand;
            // Log::channel('telegram')->error('APRIL_HOOK', [
            //     'createColdTask' => [
            //         'url' => $url,
            //         'responseData' => $responseData,

            //     ]
            // ]);

            // $createdTask =  APIBitrixController::getBitrixRespone($responseData, $parentMethod);

            return $batchCommands;
        } catch (\Throwable $th) {
            $errorMessages =  [
                'message'   => $th->getMessage(),
                'file'      => $th->getFile(),
                'line'      => $th->getLine(),
                'trace'     => $th->getTraceAsString(),
            ];
            Log::error('ERROR: Exception caught',  $errorMessages);
            Log::info('error', ['error' => $th->getMessage()]);
            Log::channel('telegram')->info(
                'HOOK: general createTask from' . $parentMethod,
                ['error' => $th->getMessage()]
            );
            return  $createdTask;
        }
    }

    static function updateTask(
        $parentMethod,
        $hook,
        $taskData

    ) {

        $methodTask = '/tasks.task.update.json';



        $createdTask = null;

        try {

            $url = $hook . $methodTask;

            $responseData = Http::get($url, $taskData);
            $createdTask =  APIBitrixController::getBitrixRespone($responseData, $parentMethod);

            return $createdTask;
        } catch (\Throwable $th) {
            $errorMessages =  [
                'message'   => $th->getMessage(),
                'file'      => $th->getFile(),
                'line'      => $th->getLine(),
                'trace'     => $th->getTraceAsString(),
            ];
            Log::error('ERROR: Exception caught',  $errorMessages);
            Log::info('error', ['error' => $th->getMessage()]);
            Log::channel('telegram')->info(
                'HOOK: general createTask from' . $parentMethod,
                ['error' => $th->getMessage()]
            );
            return  $createdTask;
        }
    }

    // protected function getCurrentTasksIdsWarm($crmForCurrent)
    // {
    //     $hook = $this->hook;
    //     $responsibleId = $this->responsibleId;
    //     $callingTaskGroupId = $this->callingGroupId;


    //     $resultIds = [];

    //     $methodGet = '/tasks.task.list';
    //     $url = $hook . $methodGet;

    //     // for get
    //     $filter = [
    //         'GROUP_ID' => $callingTaskGroupId,
    //         'UF_CRM_TASK' => $crmForCurrent,
    //         'RESPONSIBLE_ID' => $responsibleId,
    //         '!=STATUS' => 5, // Исключаем задачи со статусом "завершена"

    //     ];

    //     $select = [
    //         'ID',
    //         'TITLE',
    //         'MARK',
    //         'STATUS',
    //         'GROUP_ID',
    //         'STAGE_ID',
    //         'RESPONSIBLE_ID'

    //     ];
    //     $getTaskData = [
    //         'filter' => $filter,
    //         'select' => $select,

    //     ];
    //     $responseData = Http::get($url, $getTaskData);

    //     if (isset($responseData['result'])) {
    //         if (isset($responseData['result']['tasks'])) {
    //             // Log::info('tasks', [$responseData['result']]);
    //             $resultTasks = $responseData['result']['tasks'];
    //             foreach ($resultTasks  as $key =>  $task) {
    //                 if (isset($task['id'])) {

    //                     array_push($resultIds, $task['id']);
    //                 }

    //                 // array_push($resultTasks, $task);
    //             }
    //         }
    //     }

    //     return $resultIds;
    // }

    // protected function completeTaskWarm($hook, $taskIds)
    // {

    //     $methodUpdate = 'tasks.task.update';
    //     $methodComplete = 'tasks.task.complete';

    //     $batchCommands = [];

    //     foreach ($taskIds as $taskId) {
    //         $batchCommands['cmd']['updateTask_' . $taskId] = $methodUpdate . '?taskId=' . $taskId . '&fields[MARK]=P';
    //         $batchCommands['cmd']['completeTask_' . $taskId] = $methodComplete . '?taskId=' . $taskId;
    //     }

    //     $response = Http::post($hook . '/batch', $batchCommands);

    //     // Обработка ответа от API
    //     if ($response->successful()) {
    //         $responseData = $response->json();
    //         // Логика обработки успешного ответа
    //     } else {
    //         // Обработка ошибок
    //         $errorData = $response->body();
    //         // Логика обработки ошибки
    //     }
    //     $res = $responseData ?? $errorData;

    //     return $res;
    // }
}
