<?php

namespace App\Services;

use App\Http\Controllers\APIBitrixController;
use App\Http\Controllers\APIOnlineController;
use App\Http\Controllers\PortalController;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BitrixCallingColdService


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
    // protected $callingGroupId;
    protected $smartCrmId;
    protected $smartEntityTypeId;
    protected $isNeedCreateSmart;

    // protected $type;
    protected $domain;
    protected $entityType;
    protected $entityId;
    protected $createdId;
    protected $responsibleId;
    protected $deadline;
    protected $name;
    // // protected $comment;
    // protected $smartId;
    // protected $currentBitrixSmart;
    // // protected $sale;
    protected $taskTitle;

    protected $categoryId = 28;
    protected $stageId = 'DT162_28:NEW';


    // // TODO to DB
    protected $lastCallDateField = 'ufCrm10_1709907744';
    protected $callThemeField = 'ufCrm10_1709907850';
    protected $lastCallDateFieldCold = 'ufCrm10_1701270138';
    protected $callThemeFieldCold = 'ufCrm10_1703491835';


    protected $createdFieldCold = 'ufCrm6_1702453779';

    protected $stringType = '';
    public function __construct(

        $data,

    ) {
        $domain = $data['domain'];
        $entityType = $data['entityType'];
        $entityId = $data['entityId'];
        $responsibleId = $data['responsible'];
        $createdId = $data['created'];
        $deadline = $data['deadline'];
        $name = $data['name'];


       
        sleep(1);
        $portal = PortalController::getPortal($domain);


        $portal = $portal['data'];
        $this->portal = $portal;
        $this->aprilSmartData = $portal['bitrixSmart'];


        // $this->type = $type;
        $this->domain = $domain;


        $this->createdId = $createdId;
        $this->responsibleId = $responsibleId;
        // $this->smartId = $smartId;  //может быть null

        $this->name = $name;

        $this->stringType = 'Холодный обзвон  ';


        $webhookRestKey = $portal['C_REST_WEB_HOOK_URL'];
        $this->hook = 'https://' . $domain  . '/' . $webhookRestKey;



        $smartId = ''; //T9c_
        if (isset($portal['bitrixSmart']) && isset($portal['bitrixSmart']['crm'])) {
            $smartId =  $portal['bitrixSmart']['crm'] . '_';
        }

        $this->smartCrmId =  $smartId;





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
                                        $targetStageId = $smartForStageId . $category['bitrixId'] . ':' . $stage['bitrixId'];
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
        $this->callThemeFieldCold = $callThemeFieldCold;


        $this->deadline =  $deadline;
    }


    public function getCold()
    {

        try {

            $updatedCompany = null;
            $updatedLead = null;
            // if(!$this->smartId){

            // }
            $randomNumber = rand(1, 2);
            sleep($randomNumber);
            // Log::info('COLD companyId', ['log' => $this->companyId]);
            if ($this->entityType == 'company') {

                $updatedCompany = $this->updateCompanyCold($this->entityId);
            } else if ($this->entityType == 'lead') {
                $updatedLead = $this->updateLeadCold($this->entityId);
            }


            if ($this->entityType !== 'smart') {
                $currentSmart = $this->getSmartItem();
               
                if ($currentSmart) {
                    if (isset($currentSmart['id'])) {
                        $currentSmart = $this->updateSmartItemCold($currentSmart['id']);
                    }
                } else {
                    $currentSmart = $this->createSmartItemCold();
                    // $currentSmart = $this->updateSmartItemCold($currentSmart['id']);
                }
            } else {
                $currentSmart = $this->updateSmartItemCold($this->entityId);
            }


            if($currentSmart && isset($currentSmart['id'])){
                $randomNumber = rand(1, 2);
                sleep($randomNumber);
                $this->createColdTask($currentSmart['id']);
            }

        



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
        $leadId  = null;

        $companyId = null;
        $userId = $this->responsibleId;
        $smart = $this->aprilSmartData;

        $currentSmart = null;


        if ($this->entityType == 'company') {

            $companyId  = $this->entityId;
        } else if ($this->entityType == 'lead') {
            $leadId  = $this->entityId;
        }


        $currentSmart = BitrixGeneralService::getSmartItem(
            $this->hook,
            $leadId, //lidId ? from lead
            $companyId, //companyId ? from company
            $userId,
            $smart, //april smart data
        );


        return $currentSmart;
    }


    //smart
    protected function createSmartItemCold()
    {

        // $methodSmart = '/crm.item.add.json';
        // $url = $this->hook . $methodSmart;

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

        $responsibleId  = $this->responsibleId;
        $smart  = $this->aprilSmartData;

        $companyId  = null;
        $leadId  = null;

        if ($this->entityType == 'company') {

            $companyId  = $this->entityId;
        } else if ($this->entityType == 'lead') {
            $leadId  = $this->entityId;
        }

        $resultFields = [];
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

        $resultFields = BitrixGeneralService::createSmartItem(
            $this->hook,
            $entityId,
            $fieldsData
        );



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


        if ($this->entityType == 'company') {

            $companyId  = $this->entityId;
        } else if ($this->entityType == 'lead') {
            $leadId  = $this->entityId;
        }
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

        $resultFields = BitrixGeneralService::updateSmartItem(
            $this->hook,
            $entityId,
            $smartId,
            $fieldsData
        );
        return $resultFields;
    }




    // company
    protected function updateCompanyCold($companyId)
    {

        $hook = $this->hook;
        $responsibleId = $this->responsibleId;


        // UF_CRM_10_1709907744 - дата следующего звонка

        $result = null;
        $fields = [
            'UF_CRM_1709798145' => $responsibleId,
            'UF_CRM_10_170990774' => $this->deadline   //  - дата следующего звонка
        ];


        $result =  BitrixGeneralService::updateCompany($hook, $companyId, $fields);

        return $result;
    }


    //lead

    protected function updateLeadCold($leadId)
    {

        $hook = $this->hook;
        $responsibleId = $this->responsibleId;

        $result = null;


        $fields = [
            'ASSIGNED_BY_ID' => $responsibleId
        ];


        $result =  BitrixGeneralService::updateLead($hook, $leadId, $fields);

        return $result;
    }




    //tasks for complete


    public function createColdTask(
        $currentSmartItemId

    ) {




        try {

            $companyId  = null;
            $leadId  = null;
            if ($this->entityType == 'company') {

                $companyId  = $this->entityId;
            } else if ($this->entityType == 'lead') {
                $leadId  = $this->entityId;
            }
            $taskService = new BitrixTaskService();
            $createdTask =  $taskService->createTask(
                'cold',       //$type,   //cold warm presentation hot 
                $this->stringType,
                $this->portal,
                $this->domain,
                $this->hook,
                $companyId,  //may be null
                $leadId,     //may be null
                $this->createdId,
                $this->responsibleId,
                $this->deadline,
                $this->name,
                $currentSmartItemId,
                true, //$isNeedCompleteOtherTasks
            );

            return $createdTask;
        } catch (\Throwable $th) {
            $errorMessages =  [
                'message'   => $th->getMessage(),
                'file'      => $th->getFile(),
                'line'      => $th->getLine(),
                'trace'     => $th->getTraceAsString(),
            ];
            Log::error('ERROR COLD: createColdTask',  $errorMessages);
            Log::info('error COLD', ['error' => $th->getMessage()]);
            return $createdTask;
        }
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