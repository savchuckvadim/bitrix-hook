<?php

namespace App\Services;

use App\Http\Controllers\APIOnlineController;
use App\Http\Controllers\PortalController;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BitrixCallingColdTaskService
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
    // protected $createdId;
    protected $responsibleId;
    protected $deadline;
    protected $name;
    // protected $comment;
    // protected $crm;
    protected $currentBitrixSmart;
    // protected $sale;
    protected $taskTitle;

    protected $categoryId = 28;
    protected $stageId = 'DT162_28:NEW';
    protected $lastCallDateField = 'ufCrm10_1709907744';
    protected $callThemeField = 'ufCrm10_1709907850';
    protected $lastCallDateFieldCold = 'ufCrm10_1701270138';
    protected $callThemeFieldCold = 'ufCrm10_1703491835';

    public function __construct(
        // $type,
        $domain,
        $companyId,
        // $createdId,
        $responsibleId,
        $deadline,
        $name,
        // $comment,
        // $crm,
        // $currentBitrixSmart,
        // $sale,
    ) {



        $portal = PortalController::getPortal($domain);
        $portal = $portal['data'];
        $this->portal = $portal;
        $this->aprilSmartData = $portal['bitrixSmart'];


        // $this->type = $type;
        $this->domain = $domain;
        $this->companyId = $companyId;
        // $this->createdId = $createdId;
        $this->responsibleId = $responsibleId;

        $this->name = $name;

        $stringType = 'Холодный обзвон  ';







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
            $this->lastCallDateField = 'ufCrm6_1709907693';
            $this->callThemeField = 'ufCrm6_1709907816';
            $this->lastCallDateFieldCold = 'ufCrm6_1709907693';
            $this->callThemeFieldCold = 'ufCrm6_1700645937';
            $this->categoryId = 14;
            $this->stageId = 'DT156_14:NEW';
        }


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


    public function initialCold()
    {

        try {
            $updatedCompany = $this->updateCompanyCold();
            $currentSmart = $this->getSmartItem();
            if ($currentSmart) {
                if (isset($currentSmart['id'])) {
                    $currentSmart = $this->updateSmartItemCold($currentSmart['id']);
                }
            } else {
                $currentSmart = $this->createSmartItemCold();
                $currentSmart = $this->updateSmartItemCold($currentSmart['id']);
            }
            Log::info('SUCCESS INITIAL COLD', [
                'updated smart' => $currentSmart,
                'updated company' => $updatedCompany
            ]);
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
            return APIOnlineController::getResponse(1, $th->getMessage(),  $errorMessages);
        }
    }

    protected function getSmartItem(
        //data from april 

    )
    {
        $companyId = $this->companyId;
        $userId = $this->responsibleId;
        $smart = $this->aprilSmartData;
        // result stageId: "DT162_26:UC_R7UBSZ"
        //         $getSmartItem = $this->getSmartItem($hook, $smart, $companyId, $responsibleId);
        $currentSmart = null;

        $method = '/crm.item.list.json';
        $url = $this->hook . $method;
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
        if (isset($response['result']) && !empty($response['result'])) {
            if (isset($response['result']['items']) && !empty($response['result']['items'])) {
                $currentSmart =  $response['result']['items'][0];
            }
        } else {
            $err = null;
            if (isset($response['error_description'])) {
                $err = $response['error_description'];
            }
            return   $err;
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


        $resulFields = [];
        $fieldsData = [];
        $fieldsData['categoryId'] = $this->categoryId;
        $fieldsData['stageId'] = $this->stageId;
        $fieldsData['ufCrm7_1698134405'] = $companyId;
        $fieldsData['assigned_by_id'] = $responsibleId;
        $fieldsData['company_id'] = $companyId;
        $fieldsData[$this->lastCallDateField] = $this->deadline;  //дата звонка следующего
        $fieldsData[$this->lastCallDateFieldCold] = $this->deadline; //дата холодного следующего
        $fieldsData[$this->callThemeField] = $this->name;      //тема следующего звонка
        $fieldsData[$this->callThemeFieldCold] = $this->name;  //тема холодного звонка






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



        $companyId  = $this->companyId;
        $responsibleId  = $this->responsibleId;
        $smart  = $this->aprilSmartData;


        $resulFields = [];
        $fieldsData = [];

        $fieldsData['categoryId'] = $this->categoryId;
        $fieldsData['stageId'] = $this->stageId;
        $fieldsData['ufCrm7_1698134405'] = $companyId;
        $fieldsData['assigned_by_id'] = $responsibleId;
        $fieldsData['company_id'] = $companyId;
        $fieldsData[$this->lastCallDateField] = $this->deadline;  //дата звонка следующего
        $fieldsData[$this->lastCallDateFieldCold] = $this->deadline; //дата холодного следующего
        $fieldsData[$this->callThemeField] = $this->name;      //тема следующего звонка
        $fieldsData[$this->callThemeFieldCold] = $this->name;  //тема холодного звонка






        $entityId = $smart['crmId'];
        $data = [
            'id' => $smartId,
            'entityTypeId' => $entityId,
           
            'fields' =>  $fieldsData

        ];

        Log::info('INITIAL COLD', [
            'updateSmartItemCold' => $data

        ]);

        $smartFieldsResponse = Http::get($url, $data);
        $bitrixResponse = $smartFieldsResponse->json();


        if (isset($smartFieldsResponse['result'])) {
            $resultFields = $smartFieldsResponse['result'];
        }
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
        if ($response) {
            if (isset($response['result'])) {
                $result =  $response['result'];
            } else if (isset($response['error_description'])) {
                $result =  $response['error_description'];
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