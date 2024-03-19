<?php

namespace App\Services;

use App\Http\Controllers\APIOnlineController;
use App\Http\Controllers\PortalController;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BitrixCallingTaskService
{
    protected $portal;
    protected $aprilSmartData;
    protected $hook;
    protected $callingGroupId;
    protected $smartCrmId;
    protected $smartEntityTypeId;
    protected $isNeedCreateSmart;

    protected $type;
    protected $domain;
    protected $companyId;
    protected $createdId;
    protected $responsibleId;
    protected $deadline;
    protected $name;
    protected $comment;
    // protected $crm;
    protected $currentBitrixSmart;
    protected $sale;
    protected $isOnemore;
    
    protected $taskTitle;

    public function __construct(
        $type,
        $domain,
        $companyId,
        $createdId,
        $responsibleId,
        $deadline,
        $name,
        $comment,
        // $crm,
        $currentBitrixSmart,
        $sale,
        $isOnemore
    ) {

        $portal = PortalController::getPortal($domain);
        $portal = $portal['data'];
        $this->portal = $portal;
        $this->aprilSmartData = $portal['bitrixSmart'];


        $this->type = $type;
        $this->domain = $domain;
        $this->companyId = $companyId;
        $this->createdId = $createdId;
        $this->responsibleId = $responsibleId;

        $this->name = $name;
        $this->comment = $comment;
        // $this->crm = $crm;
        $this->currentBitrixSmart = $currentBitrixSmart;
        $this->sale = $sale;
        $this->isOnemore = $isOnemore;
        
        $stringType = 'Холодный обзвон  ';

        if ($type) {
            if ($type === 'warm') {
                $stringType = 'Звонок запланирован  ';
            } else   if ($type === 'presentation') {
                $stringType = 'Презентация запланирована  ';
            }
        }





        $webhookRestKey = $portal['C_REST_WEB_HOOK_URL'];
        $this->hook = 'https://' . $domain  . '/' . $webhookRestKey;


        $callingTaskGroupId = env('BITRIX_CALLING_GROUP_ID');
        if (isset($portal['bitrixCallingTasksGroup']) && isset($portal['bitrixCallingTasksGroup']['bitrixId'])) {
            $callingTaskGroupId =  $portal['bitrixCallingTasksGroup']['bitrixId'];
        }

        $this->callingGroupId =  $callingTaskGroupId;

        $smartId = 'T9c_';
        if (isset($portal['bitrixSmart']) && isset($portal['bitrixSmart']['crm'])) {
            $smartId =  $portal['bitrixSmart']['crm'] . '_';
        }

        $this->smartCrmId =  $smartId;

        $isNeedCreateSmart = false;
        if (!$currentBitrixSmart) { //если не пришло смарта - он вообще не существует и надо создавать
            $isNeedCreateSmart = true;
        } else { //смарт с фронта пришел
            if (isset($currentBitrixSmart['stageId'])) {
                if ($currentBitrixSmart['stageId'] == 'DT162_26:SUCCESS' || $currentBitrixSmart['stageId'] === 'DT156_14:SUCCESS') {
                    //пришел завершенный смарт
                    if ($sale) {
                        if (isset($sale['isCreateNewSale'])) {
                            if ($sale['isCreateNewSale']) {
                                $isNeedCreateSmart = true;
                            }
                        }
                    }
                }
            }
        }

        $this->isNeedCreateSmart =  $isNeedCreateSmart;


        $targetDeadLine = $deadline;
        $nowDate = now();
        if ($domain === 'alfacentr.bitrix24.ru') {

            $novosibirskTime = Carbon::createFromFormat('d.m.Y H:i:s', $deadline, 'Asia/Novosibirsk');
            $targetDeadLine = $novosibirskTime->setTimezone('Europe/Moscow');
            $targetDeadLine = $targetDeadLine->format('Y-m-d H:i:s');
        }
        $this->deadline = $targetDeadLine;
        $this->taskTitle = $stringType . $name . '  ' . $deadline;
    }

    public function createCallingTaskItem()
    {

        $currentSmartItem  = $this->currentBitrixSmart;

        try {

            if ($this->isNeedCreateSmart) {
                $newSmart = $this->createSmartItemWarm(
                    $this->hook,
                    $this->aprilSmartData,
                    $this->companyId,
                    $this->responsibleId,
                    // $companyName
                );
                if ($newSmart && isset($newSmart['item'])) {
                    $currentSmartItem  = $newSmart['item'];
                }
            }
            $currentSmartItemId = $currentSmartItem['id'];

            //TODO
            $crmForCurrent = [$this->smartCrmId . ''  . '' . $currentSmartItemId];

            if(!$this->isOnemore){
                $currentTasksIds = $this->getCurrentTasksIdsWarm(
                    $crmForCurrent
                );
              
                $this->completeTaskWarm($this->hook, $currentTasksIds);
    
            }
 


            $createdTask = $this->createTaskWarm($currentSmartItemId);


            // updateSmart($hook, $smartTypeId, $smartId, $description)
            $updatedCompany = $this->updateCompanyWarm();

            // $updatedSmart = $this->preUpdateSmartItemStageWarm($currentSmartItem);
            $updatedSmart = $this->updateSmartItemWarm($currentSmartItem);

            Log::info('updatedCompany', ['updatedCompany' => $updatedCompany]);

            return APIOnlineController::getResponse(
                0,
                'success',
                [
                    'createdTask' => $createdTask,
                    // 'smartFields' => $smartFields,
                    'updatedCompany' => $updatedCompany,
                    'updatedSmart' => $updatedSmart,
                    'currentSmartItem' => $currentSmartItem,
                    // '$gettedSmart' => $gettedSmart,
                    'currentBitrixSmart' => $this->currentBitrixSmart,
                    'sale' => $this->sale,

                    'comment' => $this->comment,
                    'isNeedCreateSmart' => $this->isNeedCreateSmart,


                ]
            );
        } catch (\Throwable $th) {
            $errorMessages =  [
                'message'   => $th->getMessage(),
                'file'      => $th->getFile(),
                'line'      => $th->getLine(),
                'trace'     => $th->getTraceAsString(),
            ];
            Log::error('ERROR: Exception caught',  $errorMessages);
            Log::info('error', ['error' => $th->getMessage()]);
            return APIOnlineController::getResponse(1, $th->getMessage(),  $errorMessages);
        }
    }



    //task
    protected function createTaskWarm($currentSmartItemId)
    {
        $companyId = $this->companyId;
        $createdId = $this->createdId;
        $crm = $currentSmartItemId;

        $createdTask = null;
        try {

            $crmForCurrent = [$this->smartCrmId . ''  . '' . $crm];
            $crmItems = [$this->smartCrmId . $crm, 'CO_' . $companyId];





            $crmItems = [$this->smartCrmId . $crm, 'CO_' . $companyId];




            //company and contacts
            $methodContacts = '/crm.contact.list.json';
            $methodCompany = '/crm.company.get.json';
            $url = $this->hook . $methodContacts;
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
            $url = $this->hook . $methodCompany;
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
            }






            $companyPhones = '';

            $companyTitleString = '[B][COLOR=#0070c0]' . $company['result']['TITLE'] . '[/COLOR][/B]';
            $description =  $companyTitleString . '
            ' . '[LEFT][B]Контакты компании: [/B][/LEFT]' . $contactsTable;
            $description = $description . '' . $cmpnPhonesEmailsList;

            //task
            $methodTask = '/tasks.task.add.json';
            $url = $this->hook . $methodTask;

            // $moscowTime = $deadline;
            $nowDate = now();
            if ($this->domain === 'alfacentr.bitrix24.ru') {
                $crmItems = [$this->smartCrmId . ''  . '' . $crm];
            }


            // $taskTitle = $stringType . $name . '  ' . $deadline;


            $taskData =  [
                'fields' => [
                    'TITLE' => $this->taskTitle,
                    'RESPONSIBLE_ID' => $this->responsibleId,
                    'GROUP_ID' => $this->callingGroupId,
                    'CHANGED_BY' => $createdId, //- постановщик;
                    'CREATED_BY' => $createdId, //- постановщик;
                    'CREATED_DATE' => $nowDate, // - дата создания;
                    'DEADLINE' => $this->deadline, //- крайний срок;
                    'UF_CRM_TASK' => $crmItems,
                    'ALLOW_CHANGE_DEADLINE' => 'N',
                    'DESCRIPTION' => $description
                ]
            ];


            $responseData = Http::get($url, $taskData);

            if (isset($responseData['result']) && !empty($responseData['result'])) {
                $createdTask = $responseData['result'];
            }






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
            return  $createdTask;
        }
    }

    protected function getCurrentTasksIdsWarm($crmForCurrent)
    {
        $hook = $this->hook;
        $responsibleId = $this->responsibleId;
        $callingTaskGroupId = $this->callingGroupId;


        $resultIds = [];

        $methodGet = '/tasks.task.list';
        $url = $hook . $methodGet;

        // for get
        $filter = [
            'GROUP_ID' => $callingTaskGroupId,
            'UF_CRM_TASK' => $crmForCurrent,
            'RESPONSIBLE_ID' => $responsibleId,
            '!=STATUS' => 5, // Исключаем задачи со статусом "завершена"

        ];

        $select = [
            'ID',
            'TITLE',
            'MARK',
            'STATUS',
            'GROUP_ID',
            'STAGE_ID',
            'RESPONSIBLE_ID'

        ];
        $getTaskData = [
            'filter' => $filter,
            'select' => $select,

        ];
        $responseData = Http::get($url, $getTaskData);

        if (isset($responseData['result'])) {
            if (isset($responseData['result']['tasks'])) {
                // Log::info('tasks', [$responseData['result']]);
                $resultTasks = $responseData['result']['tasks'];
                foreach ($resultTasks  as $key =>  $task) {
                    if (isset($task['id'])) {

                        array_push($resultIds, $task['id']);
                    }

                    // array_push($resultTasks, $task);
                }
            }
        }

        return $resultIds;
    }

    protected function completeTaskWarm($hook, $taskIds)
    {

        $methodUpdate = 'tasks.task.update';
        $methodComplete = 'tasks.task.complete';

        $batchCommands = [];

        foreach ($taskIds as $taskId) {
            $batchCommands['cmd']['updateTask_' . $taskId] = $methodUpdate . '?taskId=' . $taskId . '&fields[MARK]=P';
            $batchCommands['cmd']['completeTask_' . $taskId] = $methodComplete . '?taskId=' . $taskId;
        }

        $response = Http::post($hook . '/batch', $batchCommands);

        // Обработка ответа от API
        if ($response->successful()) {
            $responseData = $response->json();
            // Логика обработки успешного ответа
        } else {
            // Обработка ошибок
            $errorData = $response->body();
            // Логика обработки ошибки
        }
        $res = $responseData ?? $errorData;

        return $res;
    }



    //smart
    protected function createSmartItemWarm()
    {
        $hook = $this->hook;
        $companyId  = $this->companyId;
        $responsibleId  = $this->responsibleId;
        $smart  = $this->aprilSmartData;


        $resulFields = [];
        $fieldsData = [];

        $fields = $this->getCurrentSmartFields();
        foreach ($fields as $key => $field) {
            $resultField = '';
            // if ($field['upperName'] === 'TITLE') {
            //     $fieldsData[$field['upperName']] = 'Company Name';
            // } else 

            if ($field['title'] === 'companyId') {
                $fieldId = $this->getFormatedSmartFieldId($field['upperName']);
                $fieldsData[$fieldId] = $companyId;
            }
        }
        $fieldsData['ufCrm7_1698134405'] = $companyId;
        $fieldsData['assigned_by_id'] = $responsibleId;
        $fieldsData['company_id'] = $companyId;


        $methodSmart = '/crm.item.add.json';
        $url = $hook . $methodSmart;

        $entityId = $smart['crmId'];
        $data = [
            'entityTypeId' => $entityId,
            'fields' =>  $fieldsData

        ];

        // Возвращение ответа клиенту в формате JSON

        $smartFieldsResponse = Http::get($url, $data);
        $bitrixResponse = $smartFieldsResponse->json();


        if (isset($smartFieldsResponse['result'])) {
            $resultFields = $smartFieldsResponse['result'];
        }
        return $resultFields;
    }

    protected function updateSmartItemWarm($smartItemFromBitrix)
    {
        $isCanChange = false;

        $domain = $this->domain;
        $hook = $this->hook;
        $smart = $this->aprilSmartData;
        $type = $this->type;
        $deadline = $this->deadline;
        $callName = $this->name;
        $comment = $this->comment;

        $result = null;

        $methodSmart = '/crm.item.update.json';
        $url = $hook . $methodSmart;
        $entityId = $smart['crmId'];
        //         stageId: 

        //дата следующего звонка smart
        // UF_CRM_6_1709907693 - alfa
        // UF_CRM_10_1709907744 - april


        //комментарии smart
        //UF_CRM_6_1709907513 - alfa
        // UF_CRM_10_1709883918 - april


        //название обзвона - тема
        // UF_CRM_6_1709907816 - alfa
        // UF_CRM_10_1709907850 - april
        $stagesForWarm = [
            // april
            'DT162_26:NEW',
            'DT162_26:PREPARATION',
            'DT162_26:FAIL',

            'DT162_28:NEW',
            'DT162_28:UC_J1ADFR',
            'DT162_28:PREPARATION',
            'DT162_28:UC_BDM2F0',
            'DT162_28:SUCCESS',
            'DT162_28:FAIL',

            //presentation
            'DT162_26:UC_Q5V5H0',

            //alfa
            'DT156_12:NEW',
            'DT156_12:CLIENT',
            'DT156_12:UC_E4BPCB',
            'DT156_12:UC_Y52JIL',
            'DT156_12:UC_02ZP1T',
            'DT156_12:FAIL',

            'DT156_14:NEW',
            'DT156_14:UC_TS7I14',
            'DT156_14:UC_8Q85WS',
            'DT156_14:PREPARATION',
            'DT156_14:CLIENT',
            'DT156_14:SUCCESS',
            'DT156_14:FAIL',


            //presentation
            'DT156_12:UC_LEWVV8',


        ];

        $stageId = null;
        $fields = null;
        $smartItemId = null;
        $targetStageId = 'DT162_26:NEW';
        $categoryId = 26;
        $lastCallDateField = 'ufCrm10_1709907744';
        $commentField = 'ufCrm10_1709883918';
        $callThemeField = 'ufCrm10_1709907850';


        // if ($domain == 'alfacentr.bitrix24.ru') {
        //     $lastCallDateField = 'ufCrm6_1709907693';
        //     $commentField = 'ufCrm6_1709907513';
        //     $callThemeField = 'ufCrm6_1709907816';
        // }



        if (isset($smartItemFromBitrix['stageId'])) {
            $stageId =  $smartItemFromBitrix['stageId'];
        }

        if (isset($smartItemFromBitrix['id'])) {
            $smartItemId =  $smartItemFromBitrix['id'];
        }


        if ($type == 'warm') {

            if ($domain == 'april-garant.bitrix24.ru') {
                $targetStageId = 'DT162_26:UC_Q5V5H0'; //теплый прозвон
            } else  if ($domain == 'alfacentr.bitrix24.ru') {
                $targetStageId = 'DT156_12:UC_LEWVV8'; //звонок согласован
            }
        } else if ($type == 'presentation') {

            if ($domain == 'april-garant.bitrix24.ru') {
                $targetStageId = 'DT162_26:UC_NFZKDU'; //презентация запланирована
            } else  if ($domain == 'alfacentr.bitrix24.ru') {
                $targetStageId = 'DT156_12:UC_29HBRD'; //презентация согласована
            }
        }


        if ($domain == 'april-garant.bitrix24.ru') {
            $categoryId = 26;
        } else  if ($domain == 'alfacentr.bitrix24.ru') {
            $categoryId = 12;
        }




        // Получение текущих комментариев из $smartItemFromBitrix
        $currentComments = $smartItemFromBitrix[$commentField] ?? [];

        // Добавление нового комментария к существующим
        $currentComments[] = $comment;

        $fields = [
            $lastCallDateField => $deadline,
            $commentField =>  $currentComments,
            $callThemeField => $callName,
        ];

        if (in_array($stageId, $stagesForWarm)) {
            $isCanChange = true;
            $fields = [
                'categoryId' =>   $categoryId,
                'stageId' =>   $targetStageId,
                $lastCallDateField => $deadline,
                $commentField => $currentComments,
                $callThemeField => $callName
            ];
        }
        $data = [
            'entityTypeId' => $entityId,
            'id' =>  $smartItemId,
            'fields' => $fields


        ];

        $smartFieldsResponse = Http::get($url, $data);
        $bitrixResponse = $smartFieldsResponse->json();


        if (isset($smartFieldsResponse['result'])) {
            $result = $smartFieldsResponse['result'];
        } else  if (isset($smartFieldsResponse['error_description'])) {
            $result = $smartFieldsResponse['error_description'];
        }


        // Возвращение ответа клиенту в формате JSON

        $testingResult = [
            $domain,
            $stageId,
            $smart, //data from back
            $smartItemFromBitrix,
            $type,
            $targetStageId,
            $fields,
            'isCanChange' => $isCanChange,
            'bitrixResult' => $result

        ];

        return $testingResult;
    }



    protected function getCurrentSmartFields()
    {

        $hook = $this->hook;
        $smart  = $this->aprilSmartData;

        $resulFields = [];

        // try {


        $methodSmart = '/crm.item.fields.json';
        $url = $hook . $methodSmart;

        $entityId = $smart['crmId'];
        $data = ['entityTypeId' => $entityId, 'select' => ['title']];

        // Возвращение ответа клиенту в формате JSON

        $smartFieldsResponse = Http::get($url, $data);
        $bitrixResponse = $smartFieldsResponse->json();


        if (isset($smartFieldsResponse['result'])) {
            $resultFields = $smartFieldsResponse['result']['fields'];
        }
        return $resultFields;
    }

    protected function getFormatedSmartFieldId($smartId)
    {

        $originalString = $smartId;

        // Преобразуем строку в нижний регистр
        $lowerCaseString = strtolower($originalString);

        // Удаляем 'uf_' и используем регулярное выражение для изменения порядка элементов
        $transformedString = preg_replace_callback('/^(uf_)(crm_)(\d+_)(\d+)$/', function ($matches) {
            // Собираем строку в новом формате: 'ufCrm' + номер + '_' + timestamp
            return $matches[1] . ucfirst($matches[2]) . $matches[3] . $matches[4];
        }, $lowerCaseString);

        return $transformedString;
    }

    //company
    protected function updateCompanyWarm()
    {

        $hook = $this->hook;
        $responsibleId = $this->responsibleId;
        $type = $this->type;
        $deadline = $this->deadline;
        $companyId = $this->companyId;


        // UF_CRM_1696580389 - запланировать звонок
        // UF_CRM_1709798145 менеджер по продажам Гарант
        // UF_CRM_1697117364 запланировать презентацию
        //
        $method = '/crm.company.update.json';
        $result = null;
        $callField = 'UF_CRM_1696580389';
        if ($type == 'presentation') {
            $callField = 'UF_CRM_1697117364';
        }

        $fields = [
            $callField => $deadline,
            'UF_CRM_1709798145' => $responsibleId
        ];

        $getUrl = $hook . $method;
        $fieldsData = [
            'id' => $companyId,
            'fields' => $fields
        ];

        $response = Http::get($getUrl,  $fieldsData);
        if ($response) {
            $responseData = $response->json();
            if (isset($responseData['result'])) {
                $result =  $responseData['result'];
            } else if (isset($responseData['error_description'])) {
                $result =  $responseData['error_description'];
            }
        }
        Log::info('fieldsData', ['fieldsData' => $fieldsData]);
        return $result;
    }
}



        //проведено презентаций smart
        // UF_CRM_10_1709111529 - april
        // 	UF_CRM_6_1709894507 - alfa
        // компании 
        // UF_CRM_1709807026


        //дата следующего звонка smart
        // UF_CRM_6_1709907693 - alfa
        // UF_CRM_10_1709907744 - april


        //комментарии smart
        //UF_CRM_6_1709907513 - alfa
        // UF_CRM_10_1709883918 - april


        //название обзвона - тема
        // UF_CRM_6_1709907816 - alfa
        // UF_CRM_10_1709907850 - april