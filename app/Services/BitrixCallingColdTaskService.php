<?php

namespace App\Services;

use App\Http\Controllers\APIBitrixController;
use App\Http\Controllers\APIOnlineController;
use App\Http\Controllers\PortalController;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BitrixCallingColdTaskService


// на данный момент содержит методы для initial cold
// TOD оставить только метод initial cold
// остальный перенести в General 
// еще к cold относится непосредственно создание задачи
// для него возможно не потребуется отдельный сервис так как для конструктора одни и те же параметры
// но может лучше на будущее и разделить

{
    protected $portal;
    protected $aprilSmartData;
    protected $hook;
    protected $callingGroupId;
    protected $smartCrmId;
    protected $smartEntityTypeId;
    protected $isNeedCreateSmart;

    // protected $type;
    protected $domain;
    protected $companyId;
    protected $leadId;
    protected $createdId;
    protected $responsibleId;
    protected $deadline;
    protected $name;
    // protected $comment;
    protected $smartId;
    protected $currentBitrixSmart;
    // protected $sale;
    protected $taskTitle;

    protected $categoryId = 28;
    protected $stageId = 'DT162_28:NEW';


    // TODO to DB
    protected $lastCallDateField = 'ufCrm10_1709907744';
    protected $callThemeField = 'ufCrm10_1709907850';
    protected $lastCallDateFieldCold = 'ufCrm10_1701270138';
    protected $callThemeFieldCold = 'ufCrm10_1703491835';


    protected $createdFieldCold = 'ufCrm6_1702453779';
    public function __construct(
        // $type,
        $domain,
        $companyId,
        $leadId,
        $createdId,
        $responsibleId,
        $deadline,
        $name,
        $smartId
        // $comment,
        // $crm,
        // $currentBitrixSmart,
        // $sale,
    ) {


        $randomNumber = rand(1, 2);
        sleep($randomNumber);
        $portal = PortalController::getPortal($domain);
  


        $portal = $portal['data'];
        $this->portal = $portal;
        $this->aprilSmartData = $portal['bitrixSmart'];


        // $this->type = $type;
        $this->domain = $domain;
        $this->companyId = $companyId;
        $this->leadId = $leadId;

        $this->createdId = $createdId;
        $this->responsibleId = $responsibleId;
        $this->smartId = $smartId;  //может быть null

        $this->name = $name;

        $stringType = 'Холодный обзвон ';


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




        if ($domain == 'alfacentr.bitrix24.ru') {
            // $this->lastCallDateField = 'ufCrm6_1709907693';
            // $this->callThemeField = 'ufCrm6_1709907816';
            // $this->lastCallDateFieldCold = 'ufCrm6_1709907693';
            // $this->callThemeFieldCold = 'ufCrm6_1700645937';
            $this->categoryId = 14;
            $this->stageId = 'DT156_14:NEW';
        }
        if ($domain == 'april-dev.bitrix24.ru') {

            // $this->categoryId = 14;
            // $this->stageId = 'DT156_14:NEW';
            $smartEntityId = null;
            $targetCategoryId = null;
            $targetStageId = null;

            $lastCallDateField = '';
            $callThemeField = '';
            $lastCallDateFieldCold = '';
            $callThemeFieldCold = '';


            if (!empty($portal['smarts'])) {
                // foreach ($portal['smarts'] as $smart) {
                $smart = $portal['smarts'][0];
                $smartForStageId = $smart['forStage'];

                if (!empty($smart['categories'])) {
                    foreach ($smart['categories'] as $category) {

                        if ($category && !empty($category['code'])) {

                            if ($category['code'] == 'cold') {

                                $targetCategoryId = $category['bitrixId'];
                                if (!empty($category['stages'])) {
                                    foreach ($category['stages'] as $stage) {
                                        if ($stage['code'] == 'new') {
                                            $targetStageId = $smartForStageId . $category['bitrixId']. ':' . $stage['bitrixId'];
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                if (!empty($smart['bitrixfields'])) {

                    foreach ($smart['bitrixfields'] as $field) {
                        if ($field && !empty($field['code'])) {
                            if ($field['code'] == 'xo_call_name') {
                                $callThemeFieldCold = $field['bitrixCamelId'];
                            } else if ($field['code'] == 'xo_deadline') {
                                $lastCallDateFieldCold = $field['bitrixCamelId'];
                            } else if ($field['code'] == 'next_call_date') {
                                $lastCallDateField = $field['bitrixCamelId'];
                            } else if ($field['code'] == 'next_call_name') {
                                $callThemeField = $field['bitrixCamelId'];
                            }
                        }
                    }
                }
                // }
            }
            // $targetStageId = 'DT158_13:NEW';
            $this->categoryId = $targetCategoryId;
            $this->stageId = $targetStageId;

            $this->lastCallDateField = $lastCallDateField;
            $this->callThemeField = $callThemeField;
            $this->lastCallDateFieldCold = $lastCallDateFieldCold;
            // $this->lastCallDateFieldCold =  'ufCrm10_1701270138'; 
            $this->callThemeFieldCold = $callThemeFieldCold;
            // Log::channel('telegram')->info(
            //     'HOOK: portal data',
            //     [
            //         'stageId' => $targetStageId,
            //         'lastCallDateField' => $lastCallDateField,
            //         'callThemeField' => $callThemeField,
            //         'lastCallDateFieldCold' => $lastCallDateFieldCold,
            //         'callThemeFieldCold' => $callThemeFieldCold,

            //     ]
            // );
        }
        $targetDeadLine = $deadline;
        // $nowDate = now();
        // if ($domain === 'alfacentr.bitrix24.ru') {

        //     $novosibirskTime = Carbon::createFromFormat('d.m.Y H:i:s', $deadline, 'Asia/Novosibirsk');
        //     $targetDeadLine = $novosibirskTime->setTimezone('Europe/Moscow');
        //     $targetDeadLine = $targetDeadLine->format('Y-m-d H:i:s');
        // }
        $this->deadline = $targetDeadLine;
        $this->taskTitle = $stringType . $name . '  ' . $deadline;
    }


    public function initialCold()
    {

        try {

            $updatedCompany = null;
            $updatedLead = null;
            // if(!$this->smartId){

            // }
            $randomNumber = rand(1, 3);
            sleep($randomNumber);
            Log::info('COLD companyId', ['log' => $this->companyId]);
            if ($this->companyId) {

                $updatedCompany = $this->updateCompanyCold();
            }



            if ($this->leadId) {

                $updatedLead = $this->updateLeadCold();
                Log::info('COLD updatedLead', ['updatedLead' => $updatedLead]);
            }

           

            // Log::info('COLD first updatedCompany', [
            //     'updatedCompany' => $updatedCompany,

            // ]);


            $currentSmart = $this->getSmartItem();
            // Log::info('COLD first getSmartItem', [
            //     'currentSmart' => $currentSmart,

            // ]);

            sleep(1);
            if ($currentSmart) {
                if (isset($currentSmart['id'])) {
                    $currentSmart = $this->updateSmartItemCold($currentSmart['id']);
                }
            } else {
                $currentSmart = $this->createSmartItemCold();
                // $currentSmart = $this->updateSmartItemCold($currentSmart['id']);
            }

            // if (isset($currentSmart['id'])) {
            //     $crmForCurrent = [$this->smartId . ''  . '' . $currentSmart['id']];
            //     $currentTasksIds = $this->getCurrentTasksIds(
            //         $this->hook,
            //         $this->callingGroupId,
            //         $crmForCurrent,
            //         $this->responsibleId
            //     );
            //     // Log::info('currentTasksIds', [$currentTasksIds]);
            //     $this->completeTask($this->hook, $currentTasksIds);
            // }


            // Log::info('SUCCESS INITIAL COLD', [
            //     'updated smart' => $currentSmart,
            //     'updated company' => $updatedCompany
            // ]);
            return APIOnlineController::getSuccess($currentSmart);
        } catch (\Throwable $th) {
            $errorMessages =  [
                'message'   => $th->getMessage(),
                'file'      => $th->getFile(),
                'line'      => $th->getLine(),
                'trace'     => $th->getTraceAsString(),
            ];
            Log::error('ERROR COLD: Exception caught',  $errorMessages);
            Log::info('error COLD', ['error' => $th->getMessage()]);
            return APIOnlineController::getError($th->getMessage(),  $errorMessages);
        }
    }

    protected function getSmartItem(
        //data from april 

    )
    {
        // lidIds UF_CRM_7_1697129081
        $leadId  = $this->leadId;

        $companyId = $this->companyId;
        $userId = $this->responsibleId;
        $smart = $this->aprilSmartData;
        // result stageId: "DT162_26:UC_R7UBSZ"
        //         $getSmartItem = $this->getSmartItem($hook, $smart, $companyId, $responsibleId);
        $currentSmart = null;

        $method = '/crm.item.list.json';
        $url = $this->hook . $method;
        if ($companyId) {
            $data =  [
                'entityTypeId' => $smart['crmId'],
                'filter' => [
                    "!=stage_id" => ["DT162_26:SUCCESS", "DT156_12:SUCCESS"],
                    "=assignedById" => $userId,
                    'COMPANY_ID' => $companyId,

                ],
                // 'select' => ["ID"],
            ];
        }
        if ($leadId) {
            $data =  [
                'entityTypeId' => $smart['crmId'],
                'filter' => [
                    "!=stage_id" => ["DT162_26:SUCCESS", "DT156_12:SUCCESS"],
                    "=assignedById" => $userId,

                    "=%ufCrm7_1697129081" => '%' . $leadId . '%',

                ],
                // 'select' => ["ID"],
            ];
        }



        $response = Http::get($url, $data);
        // $responseData = $response->json();
        $responseData = APIBitrixController::getBitrixRespone($response, 'cold: getSmartItem');
        if (isset($responseData)) {
            if (!empty($responseData['items'])) {
                $currentSmart =  $responseData['items'][0];
            }
        }

        return $currentSmart;
    }






    //smart
    protected function createSmartItemCold()
    {

        $methodSmart = '/crm.item.add.json';
        $url = $this->hook . $methodSmart;

        //дата следующего звонка smart
        // UF_CRM_6_1709907693 - alfa
        // UF_CRM_10_1709907744 - april


        //название обзвона общее - тема
        // UF_CRM_6_1709907816 - alfa
        // UF_CRM_10_1709907850 - april


        // cold
        // ..Дата холодного обзвона  UF_CRM_10_1701270138
        // ..Название Холодного обзвона  UF_CRM_10_1703491835



        //todo 
        // Постановщик ХО UF_CRM_6_1702453779
        // Ответственный ХО UF_CRM_6_1702652862

        // $categoryId = 28;
        // $stageId = 'DT162_28:NEW';

        // $lastCallDateField = 'ufCrm10_1709907744';
        // $callThemeField = 'ufCrm10_1709907850';
        // $lastCallDateFieldCold = 'ufCrm10_1701270138';
        // $callThemeFieldCold = 'ufCrm10_1703491835';

        // if ($this->domain == 'alfacentr.bitrix24.ru') {
        //     $lastCallDateField = 'ufCrm6_1709907693';
        //     $callThemeField = 'ufCrm6_1709907816';
        //     $lastCallDateFieldCold = 'ufCrm6_1709907693';
        //     $callThemeFieldCold = 'ufCrm6_1700645937';

        //     $categoryId = 14;
        //     $stageId = 'DT156_14:NEW';
        // }


        // $hook = $this->hook;
        $companyId  = $this->companyId;
        $responsibleId  = $this->responsibleId;
        $smart  = $this->aprilSmartData;

        $leadId  = $this->leadId;


        $resulFields = [];
        $fieldsData = [];
        $fieldsData['categoryId'] = $this->categoryId;
        $fieldsData['stageId'] = $this->stageId;
        // $fieldsData['ufCrm7_1698134405'] = $companyId;
        $fieldsData['assigned_by_id'] = $responsibleId;
        // $fieldsData['companyId'] = $companyId;

        if ($companyId) {
            $fieldsData['ufCrm7_1698134405'] = $companyId;
            $fieldsData['company_id'] = $companyId;
        }
        if ($leadId) {
            $fieldsData['parentId1'] = $leadId;
            $fieldsData['ufCrm7_1697129037'] = $leadId;
        }

        $fieldsData[$this->lastCallDateField] = $this->deadline;  //дата звонка следующего
        $fieldsData[$this->lastCallDateFieldCold] = $this->deadline; //дата холодного следующего
        $fieldsData[$this->callThemeField] = $this->name;      //тема следующего звонка
        $fieldsData[$this->callThemeFieldCold] = $this->name;  //тема холодного звонка

        $fieldsData['ufCrm6_1702652862'] = $responsibleId; // alfacenter Ответственный ХО 

        if ($this->createdId) {
            $fieldsData[$this->createdFieldCold] = $this->createdId;  // Постановщик ХО - smart field

        }



        $entityId = $smart['crmId'];
        $data = [
            'entityTypeId' => $entityId,
            'fields' =>  $fieldsData

        ];
        // Log::info('create Smart Item Cold', [$data]);
        // Возвращение ответа клиенту в формате JSON

        $smartFieldsResponse = Http::get($url, $data);
        // $bitrixResponse = $smartFieldsResponse->json();
        $responseData = APIBitrixController::getBitrixRespone($smartFieldsResponse, 'cold: createSmartItemCold');
        // Log::info('COLD createSmartItemCold', ['createSmartItemCold' => $responseData]);
        // $resultFields = null;
        // if (isset($responseData)) {
        $resultFields = $responseData;
        // }
        // Log::channel('telegram')->error('APRIL_HOOK', [
        //     'btrx createSmartItemCold' => $resultFields,

        // ]);

        return $resultFields;
    }

    protected function updateSmartItemCold($smartId)
    {

        $methodSmart = '/crm.item.update.json';
        $url = $this->hook . $methodSmart;
        //дата следующего звонка smart
        // UF_CRM_6_1709907693 - alfa
        // UF_CRM_10_1709907744 - april


        //название обзвона общее - тема
        // UF_CRM_6_1709907816 - alfa
        // UF_CRM_10_1709907850 - april


        // cold
        // ..Дата холодного обзвона  UF_CRM_10_1701270138
        // ..Название Холодного обзвона  UF_CRM_10_1703491835



        //todo 
        // Постановщик ХО UF_CRM_6_1702453779
        // Ответственный ХО UF_CRM_6_1702652862


        //lead
        //leadId UF_CRM_7_1697129037


        $companyId  = $this->companyId;
        $leadId  = $this->leadId;
        $responsibleId  = $this->responsibleId;
        $smart  = $this->aprilSmartData;


        $resulFields = [];
        $fieldsData = [];

        $fieldsData['categoryId'] = $this->categoryId;
        $fieldsData['stageId'] = $this->stageId;

        $fieldsData['ufCrm6_1702652862'] = $responsibleId; // alfacenter Ответственный ХО 
        $fieldsData['assigned_by_id'] = $responsibleId;

        if ($companyId) {
            $fieldsData['ufCrm7_1698134405'] = $companyId;
            $fieldsData['company_id'] = $companyId;
        }
        if ($leadId) {
            $fieldsData['parentId1'] = $leadId;
            $fieldsData['ufCrm7_1697129037'] = $leadId;
        }


        $fieldsData[$this->lastCallDateField] = $this->deadline;  //дата звонка следующего
        $fieldsData[$this->lastCallDateFieldCold] = $this->deadline; //дата холодного следующего
        $fieldsData[$this->callThemeField] = $this->name;      //тема следующего звонка
        $fieldsData[$this->callThemeFieldCold] = $this->name;  //тема холодного звонка

        if ($this->createdId) {
            $fieldsData[$this->createdFieldCold] = $this->createdId;  //Постановщик ХО - smart field

        }






        $entityId = $smart['crmId'];
        $data = [
            'id' => $smartId,
            'entityTypeId' => $entityId,

            'fields' =>  $fieldsData

        ];

        // Log::info('INITIAL COLD', [
        //     'updateSmartItemCold' => $data

        // ]);

        $smartFieldsResponse = Http::get($url, $data);
        // $bitrixResponse = $smartFieldsResponse->json();
        // Log::info('COLD Updt SmartItem', ['bitrixResponse' => $bitrixResponse]);
        // $resultFields = null;

        $responseData = APIBitrixController::getBitrixRespone($smartFieldsResponse, 'cold: updateSmartItemCold');
        // if (isset($bitrixResponse['result'])) {
        $resultFields = $responseData;
        // } 
        // else if (isset($bitrixResponse['error'])  && isset($bitrixResponse['error_description'])) {
        //     Log::info('INITIAL COLD BTX ERROR', [
        //         // 'btx error' => $smartFieldsResponse['error'],
        //         'dscrp' => $bitrixResponse['error_description']

        //     ]);
        // }
        return $resultFields;
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

    // company
    protected function updateCompanyCold()
    {

        $hook = $this->hook;
        $responsibleId = $this->responsibleId;
        $companyId = $this->companyId;


        // UF_CRM_1696580389 - запланировать звонок
        // UF_CRM_1709798145 менеджер по продажам Гарант
        // UF_CRM_1697117364 запланировать презентацию
        //
        $method = '/crm.company.update.json';
        $result = null;


        $fields = [
            'UF_CRM_1709798145' => $responsibleId
        ];

        $getUrl = $hook . $method;
        $fieldsData = [
            'id' => $companyId,
            'fields' => $fields
        ];

        $response = Http::get($getUrl,  $fieldsData);
        $responseData = APIBitrixController::getBitrixRespone($response, 'cold: updateCompanyCold');

        $result =  $responseData;
        // if ($response) {
        //     $responseData = $response->json();
        //     if (isset($responseData['result'])) {
        //         $result =  $responseData['result'];
        //     } else if (isset($responseData['error_description'])) {

        //         $result =  null;
        //         Log::error('BTX ERROR updateCompanyCold', ['fieldsData' => $responseData['error_description']]);
        //     }
        // }

        return $result;
    }


    //lead

    protected function updateLeadCold()
    {

        $hook = $this->hook;
        $responsibleId = $this->responsibleId;




        //
        $method = '/crm.lead.update.json';
        $result = null;


        $fields = [
            'ASSIGNED_BY_ID' => $responsibleId
        ];

        $getUrl = $hook . $method;
        $fieldsData = [
            'id' => $this->leadId,
            'fields' => $fields
        ];

        $response = Http::get($getUrl,  $fieldsData);
        $responseData = APIBitrixController::getBitrixRespone($response, 'cold: updateLeadCold');

        $result =  $responseData;


        // if ($response) {
        //     $responseData = $response->json();
        //     if (isset($responseData['result'])) {
        //         $result =  $responseData['result'];
        //     } else if (isset($responseData['error_description'])) {

        //         $result =  null;
        //         Log::error(
        //             'BTX ERROR updateLeadCold',
        //             [
        //                 'fieldsData' => $responseData['error_description']
        //             ]
        //         );
        //     }
        // }

        return $result;
    }




    //tasks for complete
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
        $response = Http::get($url, $getTaskData);

        $responseData = APIBitrixController::getBitrixRespone($response, 'cold: getCurrentTasksIds');

        // if (isset($responseData['result'])) {
        if (isset($responseData['tasks'])) {
            // Log::info('tasks', [$responseData['result']]);
            $resultTasks = $responseData['tasks'];
            foreach ($resultTasks  as $key =>  $task) {
                if (isset($task['id'])) {
                    // Log::info('task', ['taskId' => $task['id']]);
                    array_push($resultIds, $task['id']);
                }

                // array_push($resultTasks, $task);
            }
        }
        // }

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
        $responseData = APIBitrixController::getBitrixRespone($response, 'cold: completeTask');
        // Обработка ответа от API
        // if ($response->successful()) {
        //     $responseData = $response->json();
        //     // Логика обработки успешного ответа
        // } else {
        //     // Обработка ошибок
        //     $errorData = $response->body();
        //     // Логика обработки ошибки
        // }
        // $res = $responseData ?? $errorData;
        // Log::info('res', ['res' => $res]);
        return $responseData;
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