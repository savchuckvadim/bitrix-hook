<?php

namespace App\Services;

use App\Http\Controllers\APIOnlineController;
use App\Http\Controllers\PortalController;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BitrixCallingTaskFailService
{
    protected $portal;
    protected $aprilSmartData;
    protected $hook;
    protected $callingGroupId;
    protected $smartCrmId;
    protected $smartEntityTypeId;
    protected $domain;
    protected $companyId;
    protected $responsibleId;
    protected $currentBitrixSmart;


    protected $isNeedCreateSmart;

    public function __construct(
        $domain,
        $companyId,
        $responsibleId,
        $currentBitrixSmart,

    ) {

        $portal = PortalController::getPortal($domain);
        $portal = $portal['data'];
        $this->portal = $portal;
        $this->aprilSmartData = $portal['bitrixSmart'];



        $this->domain = $domain;
        $this->companyId = $companyId;

        $this->responsibleId = $responsibleId;

        $this->currentBitrixSmart = $currentBitrixSmart;






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
                    $isNeedCreateSmart = true;
                    //пришел завершенный смарт
                    // if ($sale) {
                    //     if (isset($sale['isCreateNewSale'])) {
                    //         if ($sale['isCreateNewSale']) {
                    //             $isNeedCreateSmart = true;
                    //         }
                    //     }
                    // }
                }
            }
        }

        $this->isNeedCreateSmart =  $isNeedCreateSmart;
    }

    public function failTask()
    {

        $currentSmartItem  = $this->currentBitrixSmart;
        $currentSmartItemId = '';

        try {
            if ($this->isNeedCreateSmart) {
                $newSmart = $this->createSmartItem(
                    $this->hook,
                    $this->aprilSmartData,
                    $this->companyId,
                    $this->responsibleId,
                    // $companyName
                );
                if ($newSmart && isset($newSmart['item'])) {
                    $this->currentBitrixSmart = $newSmart;
                    $currentSmartItem  = $newSmart['item'];
                }
            }

            if ($currentSmartItem && isset($currentSmartItem) && isset($currentSmartItem['id'])) {
                $currentSmartItemId = $currentSmartItem['id'];
            }


            //TODO
            $crmForCurrent = [$this->smartCrmId . ''  . '' . $currentSmartItemId];

            $currentTasksIds = $this->getCurrentTasksIds(
                $crmForCurrent
            );
            // Log::info('currentTasksIds', [$currentTasksIds]);
            $this->completeTask($this->hook, $currentTasksIds);


            $updatedCompany = $this->updateCompany();
            $updatedSmart = $this->updateSmartItem($currentSmartItem);

            return APIOnlineController::getResponse(
                0,
                'success',
                [
                    // 'createdTask' => $createdTask,
                    // 'smartFields' => $smartFields,
                    'updatedCompany' => $updatedCompany,
                    'updatedSmart' => $updatedSmart,
                    'currentSmartItem' => $currentSmartItem,
                    // '$gettedSmart' => $gettedSmart,
                    'currentBitrixSmart' => $this->currentBitrixSmart,
                    // 'sale' => $this->sale,

                    // 'comment' => $this->comment,
                    // 'isNeedCreateSmart' => $this->isNeedCreateSmart,


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

    protected function getCurrentTasksIds($crmForCurrent)
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
            $batchCommands['cmd']['updateTask_' . $taskId] = $methodUpdate . '?taskId=' . $taskId . '&fields[MARK]=N';
            $batchCommands['cmd']['completeTask_' . $taskId] = $methodComplete . '?taskId=' . $taskId;
        }
        Log::info('batchCommands', [$batchCommands]);
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
        Log::info('res', ['res' => $res]);
        return $res;
    }



    //smart

    protected function createSmartItem()
    {
        $hook = $this->hook;
        $companyId  = $this->companyId;
        $responsibleId  = $this->responsibleId;
        $smart  = $this->aprilSmartData;


        $resulFields = [];
        $fieldsData = [];


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
        }else if(isset($smartFieldsResponse['error'])  && isset($smartFieldsResponse['error_description'])){
            Log::info('INITIAL COLD BTX ERROR', [
                // 'btx error' => $smartFieldsResponse['error'],
                'dscrp' => $smartFieldsResponse['error_description']
    
            ]);
        }
        return $resultFields;
    }


    protected function updateSmartItem($smartItemFromBitrix)
    {
        $isCanChange = false;

        $domain = $this->domain;
        $hook = $this->hook;
        $smart = $this->aprilSmartData;


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
        // $stagesForWarm = [
        //     // april
        //     'DT162_26:NEW',
        //     'DT162_26:PREPARATION',
        //     'DT162_26:FAIL',

        //     'DT162_28:NEW',
        //     'DT162_28:UC_J1ADFR',
        //     'DT162_28:PREPARATION',
        //     'DT162_28:UC_BDM2F0',
        //     'DT162_28:SUCCESS',
        //     'DT162_28:FAIL',

        //     //presentation
        //     'DT162_26:UC_Q5V5H0',

        //     //alfa
        //     'DT156_12:NEW',
        //     'DT156_12:CLIENT',
        //     'DT156_12:UC_E4BPCB',
        //     'DT156_12:UC_Y52JIL',
        //     'DT156_12:UC_02ZP1T',
        //     'DT156_12:FAIL',

        //     'DT156_14:NEW',
        //     'DT156_14:UC_TS7I14',
        //     'DT156_14:UC_8Q85WS',
        //     'DT156_14:PREPARATION',
        //     'DT156_14:CLIENT',
        //     'DT156_14:SUCCESS',
        //     'DT156_14:FAIL',


        //     //presentation
        //     'DT156_12:UC_LEWVV8',


        // ];

        $stageId = null;
        $fields = null;
        $smartItemId = null;
        $targetStageId = 'DT162_26:FAIL';

        // $lastCallDateField = 'ufCrm10_1709907744';
        // $commentField = 'ufCrm10_1709883918';
        // $callThemeField = 'ufCrm10_1709907850';


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
        $parts = explode(':', $stageId);

        // Заменяем вторую часть на 'FAIL'
        $parts[1] = 'FAIL';
        
        // Собираем строку обратно
        $targetStageId = implode(':', $parts);

        // if ($domain == 'april-garant.bitrix24.ru') {
        //     $targetStageId = 'DT162_26:FAIL'; //отказ
        // } else  if ($domain == 'alfacentr.bitrix24.ru') {
        //     $targetStageId = 'DT156_14:FAIL'; //отказ
        // }



        // Получение текущих комментариев из $smartItemFromBitrix
        // $currentComments = $smartItemFromBitrix[$commentField] ?? [];

        // Добавление нового комментария к существующим
        // $currentComments[] = $comment;

        $fields = [
            // $lastCallDateField => $deadline,
            // $commentField =>  $currentComments,
            // $callThemeField => $callName,
        ];

        // if (in_array($stageId, $stagesForWarm)) {
            $isCanChange = true;
            $fields = [
                'stageId' =>   $targetStageId,
                // $lastCallDateField => $deadline,
                // $commentField => $currentComments,
                // $callThemeField => $callName
            ];
        // }
        $data = [
            'entityTypeId' => $entityId,
            'id' =>  $smartItemId,
            'fields' => $fields


        ];

        $smartFieldsResponse = Http::get($url, $data);
        $bitrixResponse = $smartFieldsResponse->json();


        if (isset($bitrixResponse['result'])) {
            $result = $bitrixResponse['result'];
        } else  if (isset($bitrixResponse['error_description'])) {
            $result = $bitrixResponse['error_description'];
        }


        // Возвращение ответа клиенту в формате JSON

        $testingResult = [
            $domain,
            $stageId,
            $smart, //data from back
            $smartItemFromBitrix,
            // $type,
            $targetStageId,
            $fields,
            'isCanChange' => $isCanChange,
            'bitrixResult' => $result

        ];

        return $testingResult;
    }



    // protected function getCurrentSmartFields()
    // {

    //     $hook = $this->hook;
    //     $smart  = $this->aprilSmartData;

    //     $resulFields = [];

    //     // try {


    //     $methodSmart = '/crm.item.fields.json';
    //     $url = $hook . $methodSmart;

    //     $entityId = $smart['crmId'];
    //     $data = ['entityTypeId' => $entityId, 'select' => ['title']];

    //     // Возвращение ответа клиенту в формате JSON

    //     $smartFieldsResponse = Http::get($url, $data);
    //     $bitrixResponse = $smartFieldsResponse->json();


    //     if (isset($smartFieldsResponse['result'])) {
    //         $resultFields = $smartFieldsResponse['result']['fields'];
    //     }
    //     return $resultFields;
    // }

    // protected function getFormatedSmartFieldId($smartId)
    // {

    //     $originalString = $smartId;

    //     // Преобразуем строку в нижний регистр
    //     $lowerCaseString = strtolower($originalString);

    //     // Удаляем 'uf_' и используем регулярное выражение для изменения порядка элементов
    //     $transformedString = preg_replace_callback('/^(uf_)(crm_)(\d+_)(\d+)$/', function ($matches) {
    //         // Собираем строку в новом формате: 'ufCrm' + номер + '_' + timestamp
    //         return $matches[1] . ucfirst($matches[2]) . $matches[3] . $matches[4];
    //     }, $lowerCaseString);

    //     return $transformedString;
    // }

    //company
    protected function updateCompany()
    {

        $hook = $this->hook;
        $responsibleId = $this->responsibleId;
        // $type = $this->type;
        // $deadline = $this->deadline;
        $companyId = $this->companyId;


        // UF_CRM_1696580389 - запланировать звонок
        // UF_CRM_1709798145 менеджер по продажам Гарант
        // UF_CRM_1697117364 запланировать презентацию
        //
        $method = '/crm.company.update.json';
        $result = null;
        // $callField = 'UF_CRM_1696580389';
        // if ($type == 'presentation') {
        //     $callField = 'UF_CRM_1697117364';
        // }

        $fields = [
            'UF_CRM_1709117864' => true, //отказ -да
            'UF_CRM_1709798145' => $responsibleId   //ответственный гарант
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