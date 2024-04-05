<?php

namespace App\Http\Controllers;

use App\Jobs\CreateBitrixCallingTaskJob;
use App\Services\BitrixCallingColdTaskService;
use App\Services\BitrixCallingTaskService;
use App\Services\BitrixCallingTaskFailService;
use App\Services\BitrixCallingTaskPresentationDoneService;
use Carbon\Carbon;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

//TODO создать конструктор класса который принимает domain и хранит в себе всяческие хук урлы
class APIBitrixController extends Controller
{


    public function initialCold(
        $domain,
        $companyId,
        $leadId,
        $createdId,
        $responsibleId,
        $deadline,
        $name,
        $smartId
        // $crm,
    ) {
        try {

            $service = new BitrixCallingColdTaskService(
                $domain,
                $companyId,
                $leadId,
                $createdId,
                $responsibleId,
                $deadline,
                $name,
                $smartId
                // $crm, 
            );
            return $service->initialCold();
        } catch (\Throwable $th) {
            $errorMessages =  [
                'message'   => $th->getMessage(),
                'file'      => $th->getFile(),
                'line'      => $th->getLine(),
                'trace'     => $th->getTraceAsString(),
            ];
            Log::error('ERROR COLD APIBitrixController: Exception caught',  $errorMessages);
            Log::info('error COLD APIBitrixController', ['error' => $th->getMessage()]);
        }
    }


    public function createColdTask(
        $type,
        $domain,
        $companyId,
        $createdId,
        $responsibleId,
        $deadline,
        $name,
        $crm,

    ) {
        $portal = PortalController::getPortal($domain);

        //TODO
        //type - cold warm presentation hot
        $stringType = 'Холодный обзвон  ';

        $gettedSmart = null;



        try {
            $portal = $portal['data'];
            $smart = $portal['bitrixSmart'];



            $webhookRestKey = $portal['C_REST_WEB_HOOK_URL'];
            $hook = 'https://' . $domain  . '/' . $webhookRestKey;

            $currentSmartItem = null;




            $callingTaskGroupId = env('BITRIX_CALLING_GROUP_ID');
            if (isset($portal['bitrixCallingTasksGroup']) && isset($portal['bitrixCallingTasksGroup']['bitrixId'])) {
                $callingTaskGroupId =  $portal['bitrixCallingTasksGroup']['bitrixId'];
            }

            $smartId = 'T9c_';
            if (isset($portal['bitrixSmart']) && isset($portal['bitrixSmart']['crm'])) {
                $smartId =  $portal['bitrixSmart']['crm'] . '_';
            }
            $randomNumber = rand(1, 3);
            sleep($randomNumber);
            if (!$crm) { //если
                $getSmartItemId = $this->getSmartItem($hook, $smart, $companyId, $responsibleId);
                $gettedSmart =  $getSmartItemId;
                if ($getSmartItemId) {
                    $crm = $getSmartItemId['id'];
                    $currentSmartItem =  $getSmartItemId;
                }

                // return APIOnlineController::getResponse(0, 'success', ['crm' => $crm]);
            }
            $crmForCurrent = [$smartId . ''  . '' . $crm];


            //TODO
            $crmForCurrent = [$smartId . ''  . '' . $crm];

            // $currentTasksIds = $this->getCurrentTasksIds($hook, $callingTaskGroupId, $crmForCurrent,  $responsibleId);

            // $this->completeTask($hook, $currentTasksIds);


            $crmItems = [$smartId  . $crm, 'CO_' . $companyId];




            //company and contacts
            $methodContacts = '/crm.contact.list.json';
            $methodCompany = '/crm.company.get.json';
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

            $randomNumber = rand(1, 3);
            sleep($randomNumber);
            $contactsResponse = Http::get($url,  $contactsData);
            $url = $hook . $methodCompany;
           
            sleep(2);
            $companyResponse = Http::get($url,  $getCompanyData);
            $company = $companyResponse->json();


            $contacts = $contactsResponse->json();



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






  

            //task
            $methodTask = '/tasks.task.add.json';
            $url = $hook . $methodTask;

            $moscowTime = $deadline;
            $nowDate = now();
            if ($domain === 'alfacentr.bitrix24.ru') {
                $crmItems = [$smartId . ''  . '' . $crm];

                $novosibirskTime = Carbon::createFromFormat('d.m.Y H:i:s', $deadline, 'Asia/Novosibirsk');
                $moscowTime = $novosibirskTime->setTimezone('Europe/Moscow');
                $moscowTime = $moscowTime->format('Y-m-d H:i:s');
            }


            $taskTitle = $stringType . $name . '  ' . $deadline;


            $taskData =  [
                'fields' => [
                    'TITLE' => $taskTitle,
                    'RESPONSIBLE_ID' => $responsibleId,
                    'GROUP_ID' => $callingTaskGroupId,
                    'CHANGED_BY' => $createdId, //- постановщик;
                    'CREATED_BY' => $createdId, //- постановщик;
                    'CREATED_DATE' => $nowDate, // - дата создания;
                    'DEADLINE' => $moscowTime, //- крайний срок;
                    'UF_CRM_TASK' => $crmItems,
                    'ALLOW_CHANGE_DEADLINE' => 'N',
                    'DESCRIPTION' => $description
                ]
            ];


            $response = Http::get($url, $taskData);
            $responseData = $response->json();
            $createdTask = null;
            if (isset($responseData['result']) && !empty($responseData['result'])) {
                $createdTask = $responseData['result'];
            }




            // updateSmart($hook, $smartTypeId, $smartId, $description)
            // $updatedCompany = $this->updateCompany($hook, $responsibleId,  $companyId, $moscowTime,  $type);
            // $updatedSmart = $this->updateSmartItem(
            //     $domain,
            //     $hook,
            //     $smart, //data from back
            //     $currentSmartItem,
            //     $type
            // );
            // Log::error('COLD RESPONSE: Exception caught',  [
            //     'createdTask' => $createdTask,
            //     'currentSmartItem' => $currentSmartItem,
            //     'gettedSmart' => $gettedSmart,

            // ]);
            return APIOnlineController::getResponse(
                0,
                'success',
                [
                    'createdTask' => $createdTask,
                    // 'smartFields' => $smartFields,
                    // 'updatedCompany' => $updatedCompany,
                    // 'updatedSmart' => $updatedSmart,
                    'currentSmartItem' => $currentSmartItem,
                    '$gettedSmart' => $gettedSmart,

                ]
            );
        } catch (\Throwable $th) {
            $errorMessages =  [
                'message'   => $th->getMessage(),
                'file'      => $th->getFile(),
                'line'      => $th->getLine(),
                'trace'     => $th->getTraceAsString(),
            ];
            Log::error('ERROR COLD: Exception caught',  $errorMessages);
            Log::info('error COLD', ['error' => $th->getMessage()]);
            return APIOnlineController::getResponse(1, $th->getMessage(),  $errorMessages);
        }
    }


    public function createTask(
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
        $isOneMore


    ) {
        dispatch(new CreateBitrixCallingTaskJob(
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
            $isOneMore
        ));
        // $service = new BitrixCallingTaskService(
        //     $type,
        //     $domain,
        //     $companyId,
        //     $createdId,
        //     $responsibleId,
        //     $deadline,
        //     $name,
        //     $comment,
        //     $currentBitrixSmart,
        //     $sale,
        //     $isOneMore
        // );
    //    return $service->createCallingTaskItem();
        return APIOnlineController::getSuccess(false);
    }

    public function failTask(

        $domain,
        $companyId,
        $responsibleId,
        $currentBitrixSmart



    ) {
        $service = new BitrixCallingTaskFailService(
            $domain,
            $companyId,
            $responsibleId,
            $currentBitrixSmart
        );

        $failTask = $service->failTask();
        return  $failTask;
    }


    public function presentationDone(

        $domain,
        $companyId,
        $responsibleId,
        $placement,
        $company,
        $smart


    ) {
        $service = new BitrixCallingTaskPresentationDoneService(
            $domain,
            $companyId,
            $responsibleId,
            $placement,
            $company,
            $smart
        );

        $failTask = $service->done();
        // return  $failTask;

        return APIOnlineController::getSuccess(
            [
                'comeData' => [
                    'domain' =>   $domain,
                    'companyId' =>   $companyId,
                    'responsibleId' =>   $responsibleId,
                    'placement' =>   $placement,
                    'company' =>   $company,
                    'smart' =>   $smart
                ],
                'resultData' => $failTask
            ]
        );
    }







    protected function getCurrentTasksIds(
        $hook,
        $callingTaskGroupId,
        $crmForCurrent,
        $responsibleId
    ) {
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
                        // Log::info('task', ['taskId' => $task['id']]);
                        array_push($resultIds, $task['id']);
                    }

                    // array_push($resultTasks, $task);
                }
            }
        }

        return $resultIds;
    }

    protected function completeTask($hook, $taskIds)
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
        // Log::info('res', ['res' => $res]);
        return $res;
    }







    protected function updateSmart($hook, $smartTypeId, $smartId, $comment)
    {
        $methodFields = 'crm.item.fields';
        $getFieldsUrl = $hook . $methodFields;
        $fieldsData = [
            'entityTypeId' => $smartTypeId
        ];
        $commentFieldCode = null;
        $fieldsResponse = Http::get($getFieldsUrl,  $fieldsData);

        if (!empty($fieldsResponse['result'])) {
            foreach ($fieldsResponse['result'] as $fieldCode => $fieldDetails) {
                if ($fieldDetails['title'] === 'Комментарий' || $fieldDetails['formLabel'] === 'Комментарий') {
                    $commentFieldCode = $fieldCode; // Код поля "Комментарий"
                    break;
                }
            }
        }

        $methodUpdate = 'crm.item.update';

        $updateData = [
            'entityTypeId' => $smartTypeId,
            'id' => $smartId,
            'fields' => [
                $commentFieldCode => $comment,
            ]
        ];

        $updtSmartUrl = $hook . $methodUpdate;
        $smartUpdateResponse = Http::get($updtSmartUrl,  $updateData);
        if ($smartUpdateResponse) {
            if (isset($smartUpdateResponse['result'])) {
                // Log::info('current_tasks', [$smartUpdateResponse['result']]);
            } else {
                if (isset($smartUpdateResponse['error'])) {

                    Log::error('getSmartItem', [
                        'message'   => $smartUpdateResponse['error_description'],
                        'file'      => $smartUpdateResponse['error'],

                    ]);
                }
            }
        }
    }

    protected function updateCompany($hook, $responsibleId, $companyId, $deadline, $type)
    {

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
                $result =  null;
                Log::error('BTX ERROR updateCompanyCold', ['fieldsData' => $responseData['error_description']]);
            }
        }

        return $result;
    }

    protected function getSmartItem($hook, $smart, $companyId, $userId)
    {

        // result stageId: "DT162_26:UC_R7UBSZ"
        //

        $method = '/crm.item.list.json';
        $url = $hook . $method;
        $data =  [
            'entityTypeId' => $smart['crmId'],
            'filter' => [
                "!=stage_id" => ["DT162_26:SUCCESS", "DT156_12:SUCCESS"],
                "=assignedById" => $userId,
                'COMPANY_ID' => $companyId,

            ],
            // 'select' => ["ID"],
        ];
        $response = Http::get($url, $data);
        $responseData = $response->json();
        if (isset($responseData['result']) && !empty($responseData['result'])) {
            if (isset($responseData['result']['items']) && !empty($responseData['result']['items'])) {
                return $responseData['result']['items'][0];
            }
        } else {
            $err = null;
            if (isset($responseData['error_description'])) {
                Log::error('getSmartItem', [
                    'message'   => $responseData['error_description'],
                    'file'      => $responseData['error'],

                ]);
            }
            return   $err;
        }
    }


    protected function getCurrentSmartStages(
        $hook,
        $smart
    ) {


        //CATEGORY
        // entityTypeId: 162
        // id: 28
        // isDefault: "N"
        // name: "Холодный обзвон"
        // sort: 200

        // CATEGORY_ID: "26"
        // COLOR: "#e7d35d"
        // ENTITY_ID: "DYNAMIC_162_STAGE_26"
        // ID: "740"
        // NAME: "Теплый прозвон"
        // NAME_INIT: ""
        // SEMANTICS: null
        // SORT: "300"
        // STATUS_ID: "DT162_26:UC_Q5V5H0"
        // SYSTEM: "N"

        $resultCategories = [];

        // try {


        $methodSmart = '/crm.category.list.json';
        $url = $hook . $methodSmart;
        // $entityId = env('APRIL_BITRIX_SMART_MAIN_ID');
        $entityId = $smart['crmId'];
        $hookCategoriesData = ['entityTypeId' => $entityId];

        // Возвращение ответа клиенту в формате JSON

        $smartCategoriesResponse = Http::get($url, $hookCategoriesData);
        $bitrixResponse = $smartCategoriesResponse->json();


        if (isset($smartCategoriesResponse['result'])) {
            $categories = $smartCategoriesResponse['result']['categories'];

            //STAGES

            foreach ($categories as $category) {


                $stageMethod = '/crm.status.list.json';
                $url = $hook . $stageMethod;
                $hookStagesData = [
                    'entityTypeId' => $entityId,
                    'entityId' => 'STATUS',
                    'categoryId' => $category['id'],
                    'filter' => ['ENTITY_ID' => 'DYNAMIC_' . $entityId . '_STAGE_' . $category['id']],


                ];





                $stagesResponse = Http::get($url, $hookStagesData);
                $stages = $stagesResponse['result'];

                $resultCategory =
                    [
                        'category' => $category,
                        'stages' => $stages
                    ];
                array_push($resultCategories, $resultCategory);

                // foreach ($stages as $stage) {
                //     $resultstageData = [
                //         // 'category' => [
                //         //     'id' => $category['id'],
                //         //     'name' => $category['name'],
                //         // ],
                //         'id' => $stage['ID'],
                //         'entityId' => $stage['ENTITY_ID'],
                //         'statusId' => $stage['STATUS_ID'],
                //         'title' => $stage['NAME'],
                //         'nameInit' => $stage['NAME_INIT'],

                //     ];
                // }
            }
        }
        return $resultCategories;


        //     return APIOnlineController::getResponse(0, 'success', ['Smart-Categories' => $bitrixResponse]);
        // } catch (\Throwable $th) {
        //     Log::error('ERROR: Exception caught', [
        //         'message'   => $th->getMessage(),
        //         'file'      => $th->getFile(),
        //         'line'      => $th->getLine(),
        //         'trace'     => $th->getTraceAsString(),
        //     ]);
        //     return APIOnlineController::getResponse(1, $th->getMessage(), null);
        // }
    }
    protected function getCurrentSmartFields(
        $hook,
        $smart
    ) {



        $resulFields = [];

        // try {


        $methodSmart = '/crm.item.fields.json';
        $url = $hook . $methodSmart;

        $entityId = $smart['crmId'];
        $data = ['entityTypeId' => $entityId, 'select' => ['title']];

        // Возвращение ответа клиенту в формате JSON

        $smartFieldsResponse = Http::get($url, $data);
        $bitrixResponse = $smartFieldsResponse->json();

        $resultFields = null;
        if (isset($bitrixResponse['result'])) {
            $resultFields = $bitrixResponse['result']['fields'];
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





    protected function createSmartItem(
        $hook,
        $smart,
        $companyId,
        $responsibleId,
    ) {
        $resulFields = [];
        $fieldsData = [];
        $fields = $this->getCurrentSmartFields(
            $hook,
            $smart
        );
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


        if (isset($bitrixResponse['result'])) {
            $resultFields = $bitrixResponse['result'];
        } else if (isset($bitrixResponse['error'])  && isset($bitrixResponse['error_description'])) {
            Log::info('INITIAL COLD BTX ERROR', [
                // 'btx error' => $smartFieldsResponse['error'],
                'dscrp' => $bitrixResponse['error_description']

            ]);
        }
        return $resultFields;
    }

    protected function updateSmartItem(
        $domain,
        $hook,
        $smart, //data from back
        $smartItemFromBitrix,
        $type,
        $deadline,
        $callName,
        $comment,

        // $fields,
        // $companyId,
        // $responsibleId,
    ) {
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

        $lastCallDateField = 'ufCrm10_1709907744';
        $commentField = 'ufCrm10_1709883918';
        $callThemeField = 'ufCrm10_1709907850';


        if ($domain == 'alfacentr.bitrix24.ru') {
            $lastCallDateField = 'ufCrm6_1709907693';
            $commentField = 'ufCrm6_1709907513';
            $callThemeField = 'ufCrm6_1709907816';
        }



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

            $fields = [
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
            'bitrixResult' => $result

        ];

        return $testingResult;
    }


    public static function getSmartStages(
        $domain
    ) {
        $portal = PortalController::getPortal($domain);
        // Log::info('portal', ['portal' => $portal]);
        try {

            //CATEGORIES
            $webhookRestKey = $portal['data']['C_REST_WEB_HOOK_URL'];
            $hook = 'https://' . $domain  . '/' . $webhookRestKey;

            $methodSmart = '/crm.category.list.json';
            $url = $hook . $methodSmart;
            // $entityId = env('APRIL_BITRIX_SMART_MAIN_ID');
            $entityId = 156;
            $hookCategoriesData = ['entityTypeId' => $entityId];

            // Возвращение ответа клиенту в формате JSON

            $smartCategoriesResponse = Http::get($url, $hookCategoriesData);
            $bitrixResponse = $smartCategoriesResponse->json();
            // Log::info('SUCCESS RESPONSE SMART CATEGORIES', ['categories' => $bitrixResponse]);
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
                        // 'category' => [
                        //     'id' => $category['id'],
                        //     'name' => $category['name'],
                        // ],
                        'id' => $stage['ID'],
                        'entityId' => $stage['ENTITY_ID'],
                        'statusId' => $stage['STATUS_ID'],
                        'title' => $stage['NAME'],
                        'nameInit' => $stage['NAME_INIT'],

                    ];
                    // Log::info('STAGE', [$stage['NAME'] => $stage]);
                }
            }


            return APIOnlineController::getResponse(0, 'success', ['Smart-Categories' => $bitrixResponse]);
        } catch (\Throwable $th) {
            Log::error('ERROR: Exception caught', [
                'message'   => $th->getMessage(),
                'file'      => $th->getFile(),
                'line'      => $th->getLine(),
                'trace'     => $th->getTraceAsString(),
            ]);
            return APIOnlineController::getResponse(1, $th->getMessage(), null);
        }
    }
    public static function installSmart(
        $domain
    ) {
        //1) создает смарт процесс и сам задает  "entityTypeId" => 134,

        //3) записывает стадии и направления ввиде одного объекта json связь portal-smart


        $portal = PortalController::getPortal($domain);
        // Log::info('portal', ['portal' => $portal]);
        try {

            //CATEGORIES
            $webhookRestKey = $portal['data']['C_REST_WEB_HOOK_URL'];
            $hook = 'https://' . $domain  . '/' . $webhookRestKey;

            $methodSmartInstall = '/crm.type.add.json';
            $url = $hook . $methodSmartInstall;
            // $entityId = env('APRIL_BITRIX_SMART_MAIN_ID');
            $hookSmartInstallData = [
                'fields' => [
                    'id' => 134,
                    "title" => "TEST Смарт-процесс",
                    "entityTypeId" => 134,
                    'code' => 'april-garant',
                    "isCategoriesEnabled" => "Y",
                    "isStagesEnabled" => "Y",
                    "isClientEnabled" => "Y",
                    "isUseInUserfieldEnabled" => "Y",
                    "isLinkWithProductsEnabled" => "Y",
                    "isAutomationEnabled" => "Y",
                    "isBizProcEnabled" => "Y",
                ]
            ];

            // Возвращение ответа клиенту в формате JSON

            $smartInstallResponse = Http::get($url, $hookSmartInstallData);
            //2) использует "entityTypeId" чтобы создать направления
            $methodCategoryInstall = '/crm.category.add.json';
            $url = $hook . $methodCategoryInstall;
            $hookCategoriesData1  =
                [
                    "entityTypeId" => 134,

                    'fields' => [
                        'name' => 'Холодный обзвон',
                        'title' => 'Холодный обзвон',
                        "isDefault" => "N"
                    ]
                ];
            $hookCategoriesData2  =
                [
                    "entityTypeId" => 134,

                    'fields' => [
                        'name' => 'Продажи',
                        'title' => 'Продажи',
                        "isDefault" => "Y"
                    ]
                ];
            $smartCategoriesResponse1 = Http::get($url, $hookCategoriesData1);
            $smartCategoriesResponse2 = Http::get($url, $hookCategoriesData2);


            $bitrixResponse = $smartInstallResponse->json();
            $bitrixResponseCategory1 = $smartCategoriesResponse1->json();
            $bitrixResponseCategory2 = $smartCategoriesResponse2->json();
            $category1Id = $bitrixResponseCategory1['result']['category']['id'];
            $category2Id = $bitrixResponseCategory2['result']['category']['id'];
            // Log::info('SUCCESS SMART INSTALL', ['smart' => $bitrixResponse]);
            // Log::info('SUCCESS CATEGORY INSTALL', ['category1Id' => $category1Id]);
            // Log::info('SUCCESS CATEGORY INSTALL', ['category2Id' => $category2Id]);
            //STAGES
            //2) использует "entityTypeId" и category1Id  чтобы создать стадии
            $currentstagesMethod = '/crm.status.list.json';
            $url = $hook . $currentstagesMethod;
            $hookCurrentStagesData = [
                'entityTypeId' => 134,
                'entityId' => 'STATUS',
                'categoryId' => $category1Id,
                'filter' => ['ENTITY_ID' => 'DYNAMIC_' . 134 . '_STAGE_' . $category1Id]

            ];


            // Log::info('CURRENT STAGES GET 134', ['currentStagesResponse' => $hookCurrentStagesData]);
            $currentStagesResponse = Http::get($url, $hookCurrentStagesData);
            $currentStages = $currentStagesResponse['result'];
            // Log::info('CURRENT STAGES GET 134', ['currentStages' => $currentStages]);



            $callStages = [
                [
                    'title' => 'Создан',
                    'name' => 'NEW',
                    'color' => '#832EF9',
                    'sort' => 10,
                ],
                [
                    'title' => 'Запланирован',
                    'name' => 'PLAN',
                    'color' => '#BA8BFC',
                    'sort' => 20,
                ],
                [
                    'title' => 'Просрочен',
                    'name' => 'PREPARATION',
                    'color' => '#A262FC',
                    'sort' => 30,
                ],
                [
                    'title' => 'Завершен без результата',
                    'name' => 'CLIENT',
                    'color' => '#7849BB',
                    'sort' => 40,
                ],

            ];


            foreach ($callStages as $index => $callStage) {

                //TODO: try get stage if true -> update stage else -> create
                $NEW_STAGE_STATUS_ID = 'DT134_' . $category1Id . ':' . $callStage['name'];
                $isExist = false;
                foreach ($currentStages as $index => $currentStage) {
                    // Log::info('currentStage ITERABLE', ['STAGE STATUS ID' => $currentStage['STATUS_ID']]);
                    if ($currentStage['STATUS_ID'] === $NEW_STAGE_STATUS_ID) {
                        // Log::info('EQUAL STAGE', ['EQUAL STAGE' => $currentStage['STATUS_ID']]);
                        $isExist = $currentStage['ID'];
                    }
                }

                if ($isExist) { //если стадия с таким STATUS_ID существует - надо сделать update
                    $methodStageInstall = '/crm.status.update.json';
                    $url = $hook . $methodStageInstall;
                    $hookStagesDataCalls  =
                        [

                            'ID' => $isExist,
                            'fields' => [
                                // 'STATUS_ID' => 'DT134_' . $category1Id . ':' . $callStage['name'],
                                // "ENTITY_ID" => 'DYNAMIC_134_STAGE_' . $category1Id,
                                'NAME' => $callStage['title'],
                                'TITLE' => $callStage['title'],
                                'SORT' => $callStage['sort'],
                                'COLOR' => $callStage['color']
                                // "isDefault" => $callStage['title'] === 'Создан' ? "Y" : "N"
                            ]
                        ];
                } else {
                    $methodStageInstall = '/crm.status.add.json';
                    $url = $hook . $methodStageInstall;
                    $hookStagesDataCalls  =
                        [

                            'statusId' => 'DT134_' . $category1Id,
                            'fields' => [
                                'STATUS_ID' => 'DT134_' . $category1Id . ':' . $callStage['name'],
                                "ENTITY_ID" => 'DYNAMIC_134_STAGE_' . $category1Id,
                                'NAME' => $callStage['title'],
                                'TITLE' => $callStage['title'],
                                'SORT' => $callStage['sort'],
                                'COLOR' => $callStage['color']
                                // "isDefault" => $callStage['title'] === 'Создан' ? "Y" : "N"
                            ]
                        ];
                }
                $smartStageResponse = Http::get($url, $hookStagesDataCalls);
                $bitrixResponseStage = $smartStageResponse->json();
                // Log::info('SUCCESS SMART INSTALL', ['stage_response' => $bitrixResponseStage]);
            }



            APIBitrixController::getSmartStages($domain);

            return APIOnlineController::getResponse(0, 'success', ['Smart-Categories' => $bitrixResponse]);
        } catch (\Throwable $th) {
            Log::error('ERROR: Exception caught', [
                'message'   => $th->getMessage(),
                'file'      => $th->getFile(),
                'line'      => $th->getLine(),
                'trace'     => $th->getTraceAsString(),
            ]);
            return APIOnlineController::getResponse(1, $th->getMessage(), null);
        }
    }

    public static function getCalling(


        $domain,
        $callStartDateFrom,
        $callStartDateTo,


    ) {


        $portal = PortalController::getPortal($domain);
        // Log::info('portal', ['portal' => $portal]);
        $resultCallings = [];
        try {
            //CATEGORIES
            $webhookRestKey = $portal['data']['C_REST_WEB_HOOK_URL'];
            $hook = 'https://' . $domain  . '/' . $webhookRestKey;
            $actionUrl = '/voximplant.statistic.get.json';
            $url = $hook . $actionUrl;
            $next = 0; // Начальное значение параметра "next"
            $userId = 107;
            do {
                // Отправляем запрос на другой сервер
                $response = Http::get($url, [
                    "FILTER" => [
                        "USER_ID" => $userId,
                        ">CALL_START_DATE" => $callStartDateFrom,
                        "<CALL_START_DATE" =>  $callStartDateTo
                    ],
                    "start" => $next // Передаем значение "next" в запросе
                ]);
                // Log::info('response', ['response' => $response]);
                $responseData = $response->json();
                if (isset($responseData['result']) && !empty($responseData['result'])) {
                    // Добавляем полученные звонки к общему списку
                    $resultCallings = array_merge($resultCallings, $responseData['result']);
                    if (isset($response['next'])) {
                        // Получаем значение "next" из ответа
                        $next = $response['next'];
                    } else {
                        // Если ключ "next" отсутствует, выходим из цикла
                        break;
                    }
                }
                // Ждем некоторое время перед следующим запросом
                sleep(1); // Например, ждем 5 секунд

            } while ($next > 0); // Продолжаем цикл, пока значение "next" больше нуля


            return APIOnlineController::getResponse(
                0,
                'error callings',
                [
                    'result' => $resultCallings, 'response' => $response,
                    'callStartDateFrom' => $callStartDateFrom, 'callStartDateTo' => $callStartDateTo
                ]
            );
        } catch (\Throwable $th) {
            return APIOnlineController::getResponse(
                1,
                'error callings ' . $th->getMessage(),
                [
                    'result' => $resultCallings,
                    'response' => $response,
                    'error callings ' . $th->getMessage(),
                ]
            );
        }
        return APIOnlineController::getResponse(
            0,
            'error callings',
            [
                'result' => $resultCallings, 'response' => $response,
                'callStartDateFrom' => $callStartDateFrom, 'callStartDateTo' => $callStartDateTo
            ]
        );

        // BX24.callMethod(
        //     'batch',
        //     {
        //         'halt': 0,
        //         'cmd': {
        //             'user': 'user.get?ID=1',
        //             'first_lead': 'crm.lead.add?fields[TITLE]=Test Title',
        //             'user_by_name': 'user.search?NAME=Test2',
        //             'user_lead': 'crm.lead.add?fields[TITLE]=Test Assigned&fields[ASSIGNED_BY_ID]=$result[user_by_name][0][ID]',
        //         }
        //     },
        //     function(result)
        //     {
        //         console.log(result.answer);
        //     }
        // );


    }



    //calling front
    public function getSmartItemCallingFront(
        $domain,
        $companyId,
        $userId,
    ) {

        $smartItem = null;


        try {
            $portal = PortalController::getPortal($domain);
            $gettedSmart = null;
            $portal = $portal['data'];
            $smart = $portal['bitrixSmart'];
            $webhookRestKey = $portal['C_REST_WEB_HOOK_URL'];
            $hook = 'https://' . $domain  . '/' . $webhookRestKey;
            $method = '/crm.item.list.json';
            $url = $hook . $method;
            $data =  [
                'entityTypeId' => $smart['crmId'],
                'filter' => [

                    "=assignedById" => $userId,
                    '=company_id' => $companyId,

                ],

            ];
            $response = Http::get($url, $data);
            $responseData = $response->json();
            if (isset($responseData['result']) && !empty($responseData['result'])) {
                if (isset($responseData['result']['items']) && !empty($responseData['result']['items'])) {
                    // Перебираем все элементы, чтобы найти самый свежий
                    $latestTime = new DateTime('@0'); // Дата очень давно, чтобы любое сравнение было больше
                    foreach ($responseData['result']['items'] as $item) {
                        $itemTime = new DateTime($item['updatedTime']);
                        if ($itemTime > $latestTime) {
                            $latestTime = $itemTime;
                            $smartItem = $item; // Обновляем, если нашли более свежий элемент
                        }
                    }
                }
            }

            return APIOnlineController::getSuccess($smartItem);
        } catch (\Throwable $th) {
            $errorMessages =  [
                'message'   => $th->getMessage(),
                'file'      => $th->getFile(),
                'line'      => $th->getLine(),
                'trace'     => $th->getTraceAsString(),
            ];
            Log::error('ERROR: Exception caught',  $errorMessages);
            Log::info('error', ['error' => $th->getMessage()]);
            return APIOnlineController::getError($th->getMessage(),  $errorMessages);
        }
    }

    static function getBitrixRespone($bitrixResponse, $method)
    {
        $response =  $bitrixResponse->json();
        if ($response) {
            Log::channel('telegram')->error('APRIL_HOOK', [
                $method => [
                    'bitrixResponse' => $response,
                    // 'btrx response' => $response['error_description']
                ]
            ]);
            if (isset($response['result'])) {

                // Log::info('success btrx response', [
                //     'BTRX_RESPONSE_SUCCESS' => [
                //         'result' => $response['result'],

                //     ]

                // ]);
                return $response['result'];
            } else {
                if (isset($response['error_description'])) {
                    Log::channel('telegram')->error('APRIL_HOOK', [
                        $method => [
                            'btrx error' => $response['error'],
                            'btrx response' => $response['error_description']
                        ]
                    ]);


                    Log::info('error', [
                        $method => [
                            'btrx error' => $response['error'],
                            'btrx response' => $response['error_description']
                        ]

                    ]);
                    return null;
                }
            }
        }
    }
}
