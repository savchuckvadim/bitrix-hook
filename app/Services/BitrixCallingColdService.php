<?php

namespace App\Services;

use App\Http\Controllers\APIBitrixController;
use App\Http\Controllers\APIOnlineController;
use App\Http\Controllers\PortalController;
use App\Services\General\BitrixDealService;
use App\Services\General\BitrixDepartamentService;
use App\Services\General\BitrixListService;
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

    protected $entityFieldsUpdatingContent;

    protected $isDealFlow = false;
    protected $isSmartFlow = true;

    protected $portalDealData = null;
    protected $portalCompanyData = null;


    protected $currentDepartamentType = null;
    protected $withLists = false;
    protected $bitrixLists = [];



    public function __construct(

        $data,

    ) {
        $domain = $data['domain'];
        $this->entityType = $data['entityType'];
        $this->entityId = $data['entityId'];
        $this->responsibleId = $data['responsible'];
        $this->createdId = $data['created'];
        $this->deadline = $data['deadline'];
        $this->name = $data['name'];
        $this->stringType = 'Холодный обзвон  ';
        // $this->entityType = $entityType;

        $portal = PortalController::getPortal($domain);
        $portal = $portal['data'];
        $this->portal = $portal;

        if ($domain === 'april-dev.bitrix24.ru' || $domain === 'gsr.bitrix24.ru') {
            $this->isDealFlow = true;
            $this->withLists = true;
            if (!empty($portal['deals'])) {
                $this->portalDealData = $portal['bitrixDeal'];
            }
            if (!empty($portal['bitrixLists'])) {
                Log::error('APRIL_HOOK constr', ['$portal.bitrixLists' => $portal['bitrixLists']]); // массив fields

                $this->bitrixLists = $portal['bitrixLists'];
            }
        }

        if ($domain === 'gsr.bitrix24.ru') {
            $this->isSmartFlow = false;
        }


        $this->aprilSmartData = $portal['bitrixSmart'];
        $this->portalCompanyData = $portal['company'];

        



        $webhookRestKey = $portal['C_REST_WEB_HOOK_URL'];
        $this->hook = 'https://' . $domain  . '/' . $webhookRestKey;

        $smartId = ''; //T9c_
        if (isset($portal['bitrixSmart']) && isset($portal['bitrixSmart']['crm'])) {
            $smartId =  $portal['bitrixSmart']['crm'] . '_';
        }

        $this->smartCrmId =  $smartId;





        $currentBtxCompany = null;
        $currentBtxEntity = null;
        if (!empty($data['entityType'])) {

            $currentBtxEntity = BitrixGeneralService::getEntity(
                $this->hook,
                $data['entityType'],
                $data['entityId']

            );
        }
        // Log::error('APRIL_HOOK portal', ['$portal.lead' => $portal['company']['bitrixfields']]); // массив fields
        // Log::error('APRIL_HOOK portal', ['$portal.company' => $portal['company']['bitrixfields']]); // массив fields



        $fieldsCodes = [
            'xo_name',
            'xo_date',
            'xo_responsible',
            'xo_created',
            'manager_op',
            'call_next_date',
            'call_next_name',
            'call_last_date',
            'op_history',
            'op_history_multiple',
        ];
        $resultEntityFields = [];




        if (!empty($portal[$data['entityType']])) {
            if (!empty($portal[$data['entityType']]['bitrixfields'])) {
                $currentEntityField = [];
                $entityBtxFields = $portal[$data['entityType']]['bitrixfields'];

                foreach ($entityBtxFields as $pField) {


                    if (!empty($pField['code'])) {
                        switch ($pField['code']) {
                            case 'xo_name':
                            case 'call_next_name':
                                // $currentEntityField = [
                                //     'UF_CRM_' . $companyField['bitrixId'] => $data['name']
                                // ];
                                $resultEntityFields['UF_CRM_' . $pField['bitrixId']] = $data['name'];
                                break;
                            case 'xo_date':
                            case 'call_next_date':
                            case 'call_last_date':
                                // $currentEntityField = [
                                //     'UF_CRM_' . $companyField['bitrixId'] => $data['deadline']
                                // ];
                                $resultEntityFields['UF_CRM_' . $pField['bitrixId']] = $data['deadline'];

                                break;

                            case 'xo_responsible':
                            case 'manager_op':

                                // $currentEntityField = [
                                //     'UF_CRM_' . $companyField['bitrixId'] => $data['responsible']
                                // ];
                                $resultEntityFields['UF_CRM_' . $pField['bitrixId']] = $data['responsible'];

                                break;

                            case 'xo_created':

                                // $currentEntityField = [
                                //     'UF_CRM_' . $companyField['bitrixId'] => $data['created']
                                // ];
                                $resultEntityFields['UF_CRM_' . $pField['bitrixId']] = $data['created'];

                                break;

                            case 'op_history':
                                // case 'op_mhistory':

                                $fullFieldId = 'UF_CRM_' . $pField['bitrixId'];  //UF_CRM_OP_MHISTORY
                                $now = now();
                                $stringComment = $now . ' ХО запланирован ' . $data['name'] . ' на ' . $data['deadline'];

                                $currentComments = '';


                                if (!empty($currentBtxEntity)) {
                                    // if (isset($currentBtxCompany[$fullFieldId])) {

                                    $currentComments = $currentBtxEntity[$fullFieldId];

                                    if ($pField['code'] == 'op_mhistory') {
                                        if (!empty($currentComments)) {
                                            array_push($currentComments, $stringComment);
                                        } else {
                                            $currentComments = $stringComment;
                                        }
                                    } else {
                                        $currentComments = $currentComments  . ' | ' . $stringComment;
                                    }
                                    // }
                                }


                                $resultEntityFields[$fullFieldId] =  $currentComments;

                                break;

                            default:
                                // Log::channel('telegram')->error('APRIL_HOOK', ['default' => $companyField['code']]);

                                break;
                        }

                        // if (!empty($currentEntityField)) {

                        //     array_push($resultEntityFields, $currentEntityField);
                        // }
                    }
                }
            }
        }

        if (!empty($resultEntityFields)) {
            $this->entityFieldsUpdatingContent = $resultEntityFields;
        }





        $smartEntityId = null;
        $targetCategoryId = null;
        $targetStageId = null;

        $lastCallDateField = '';
        $callThemeField = '';
        $lastCallDateFieldCold = '';
        $callThemeFieldCold = '';


        if (!empty($portal['smarts'])) {
            // foreach ($portal['smarts'] as $smart) {
            $smart = null;
            if (!empty($portal['smarts'])) {

                foreach ($portal['smarts'] as $pSmart) {
                    if ($pSmart['group'] == 'sales') {
                        $smart = $pSmart['group'];
                    }
                }
            }
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

        $this->currentDepartamentType = BitrixDepartamentService::getDepartamentTypeByUserId();
    }


    public function getCold()
    {

        try {
            // Log::channel('telegram')->error('APRIL_HOOK data', ['entityType' => $this->entityType]);
            $updatedCompany = null;
            $updatedLead = null;
            $currentSmart = null;
            $currentSmartId = null;
            $currentDeal = null;
            $currentDealId = null;
            // if(!$this->smartId){

            // }
            // $randomNumber = rand(1, 2);
            sleep(1);
            if ($this->isSmartFlow) {
                $this->getSmartFlow();
            }

            if ($this->isDealFlow && $this->portalDealData) {
                $this->getDealFlow();
            }



            // Log::channel('telegram')->error('APRIL_HOOK', ['currentSmart' => $currentSmart]);


            $this->createColdTask($currentSmartId);
            sleep(1);
            // Log::info('COLD companyId', ['log' => $this->companyId]);
            if ($this->entityType == 'company') {

                $updatedCompany = $this->updateCompanyCold($this->entityId);
            } else if ($this->entityType == 'lead') {
                $updatedLead = $this->updateLeadCold($this->entityId);
            }
            Log::info('COLD bitrixLists', ['bitrixLists' => $this->bitrixLists]);
            // if ($this->withLists) {
            $this->getListsFlow(
                $this->bitrixLists,
                $this->deadline,
                $this->stringType,
                $this->deadline,
                $this->createdId,
                $this->responsibleId,
                $this->responsibleId,
                $this->entityId,
                '$comment'
            );
            // }

            return APIOnlineController::getSuccess(['result' => 'success']);
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



    //smart
    protected function getSmartFlow()
    {
        if ($this->entityType !== 'smart') {
            $currentSmart = $this->getSmartItem();
            if ($currentSmart) {
                if (isset($currentSmart['id'])) {
                    $currentSmart = $this->updateSmartItemCold($currentSmart['id']);
                }
            } else {
                $currentSmart = $this->createSmartItemCold();
                // $currentSmart = $this->updateSmartItemCold($currentSmart['id']);
                // Log::channel('telegram')->error('APRIL_HOOK createSmartItemCold', ['currentSmart' => $currentSmart]);
            }
        } else {
            $currentSmart = $this->updateSmartItemCold($this->entityId);
        }
        sleep(1);
        if ($currentSmart && isset($currentSmart['id'])) {
            $currentSmartId = $currentSmart['id'];
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

        $companyId  = null;
        $leadId  = null;
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
        // Log::channel('telegram')->error('APRIL_HOOK updateSmartItem', [

        //     $this->hook,
        //     $entityId,
        //     $smartId,
        //     $fieldsData
        // ]);
        $resultFields = BitrixGeneralService::updateSmartItem(
            $this->hook,
            $entityId,
            $smartId,
            $fieldsData
        );
        return $resultFields;
    }


    // deal flow

    protected function getDealFlow()
    {
        $currentDeal = null;
        $currentDealId = null;
        $currentCategoryData =  BitrixDealService::getTargetCategoryData(
            $this->portalDealData,
            $this->currentDepartamentType,
            'cold'
        );
        $targetStageBtxId =  BitrixDealService::getTargetStage(
            $currentCategoryData,
            'sales',
            'cold'
        );

        $currentDealId = BitrixDealService::getDealId(
            $this->hook,
            null,
            $this->entityId,
            $this->responsibleId,
            $this->portalDealData,
            $currentCategoryData

        );


        $fieldsData = [
            'CATEGORY_ID' => $currentCategoryData['bitrixId'],
            'STAGE_ID' => "C" . $currentCategoryData['bitrixId'] . ':' . $targetStageBtxId,
            "COMPANY_ID" => $this->entityId
        ];


        if (!$currentDealId) {



            $currentDeal = BitrixDealService::setDeal(
                $this->hook,
                $fieldsData,
                $currentCategoryData

            );
        } else {
            $currentDeal = BitrixDealService::updateDeal(
                $this->hook,
                $currentDealId,
                $fieldsData,


            );
        }
    }


    // company
    protected function updateCompanyCold($companyId)
    {

        $hook = $this->hook;


        // UF_CRM_10_1709907744 - дата следующего звонка

        $result = null;
        $fields = [
            // 'UF_CRM_1709798145' => $responsibleId,
            // 'UF_CRM_10_170990774' => $this->deadline,   //  - дата следующего звонка
            ...$this->entityFieldsUpdatingContent
        ];

        

        $result =  BitrixGeneralService::updateCompany($hook, $companyId, $fields);
        // Log::channel('telegram')->error('APRIL_HOOK updateCompany', ['$result' => $result]);

        return $result;
    }


    //lead

    protected function updateLeadCold($leadId)
    {

        $hook = $this->hook;
        $responsibleId = $this->responsibleId;

        $result = null;


        $fields = [
            'ASSIGNED_BY_ID' => $responsibleId,
            ...$this->entityFieldsUpdatingContent
        ];


        $result =  BitrixGeneralService::updateLead($hook, $leadId, $fields);

        return $result;
    }




    //tasks for complete


    public function createColdTask(
        $currentSmartItemId

    ) {




        try {
            // Log::channel('telegram')->error('APRIL_HOOK', $this->portal);
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
            Log::channel('telegram')->error('APRIL_HOOK', $errorMessages);
            return $createdTask;
        }
    }


    //lists flow

    protected function getListsFlow(
        $bitrixLists,
        $nowDate,
        $eventName,
        $deadline,
        $created,
        $responsible,
        $suresponsible,
        $companyId,
        $comment,

    ) {

        $xoFields = [
            [
                'code' => 'event_date',
                'name' => 'Дата',
                'value' => $nowDate
            ],
            [
                'code' => 'event_title',
                'name' => 'Название',
                'value' => $eventName
            ],
            [
                'code' => 'plan_date',
                'name' => 'Дата Следующей коммуникации',
                'value' => $deadline
            ],
            [
                'code' => 'author',
                'name' => 'Автор',
                'value' => $created,
            ],
            [
                'code' => 'responsible',
                'name' => 'Ответственный',
                'value' => $responsible,
            ],
            [
                'code' => 'su',
                'name' => 'Соисполнитель',
                'value' => $suresponsible,
            ],
            [
                'code' => 'crm',
                'name' => 'crm',
                'value' => ['CO_' . $companyId],
            ],

            [
                'code' => 'manager_comment',
                'name' => 'Комментарий',
                'value' => $comment,
            ],
            [
                'code' => 'event_type',
                'name' => 'Тип События',
                'list' =>  [
                    'code' => 'xo',
                    'name' => 'Холодный звонок',

                ],
            ],
            [
                'code' => 'event_action',
                'name' => 'Событие Действие',
                'list' =>  [
                    'code' => 'plan',
                    'name' => 'Запланирован'
                ],
            ],

            [
                'code' => 'op_status_in_work',
                'name' => 'Статус Работы',
                'list' =>  [
                    'code' => 'op_status_in_work',
                    'name' => 'В работе'
                ],
            ]

        ];


        foreach ($bitrixLists as $bitrixList) {
            $fieldsData = [
                'NAME' => $eventName
            ];
            foreach ($xoFields as $xoValue) {
                $currentDataField = [];
                $fieldCode = $bitrixList['group'].'_'.$bitrixList['type'].'_'. $xoValue['code'];
                $btxId = $this->getBtxListCurrentData($bitrixList, $fieldCode, null);
                if (!empty($xoValue)) {

                    

                    if (!empty($xoValue['value'])) {
                        $fieldsData[$btxId] = $xoValue['value'];
                        $currentDataField[$btxId] = $xoValue['value'];
                    }

                    if (!empty($xoValue['list'])) {
                        $btxItemId = $this->getBtxListCurrentData($bitrixList, $fieldCode, $xoValue['list']['code']);
                        $currentDataField[$btxId] = [
                            
                            $btxItemId =>  $xoValue['list']['name']
                        ];

                        $fieldsData[$btxId] = [
                            
                            $btxItemId =>  $xoValue['list']['name']
                        ];
                      
                    }
                }
                // array_push($fieldsData, $currentDataField);
            }
          
            BitrixListService::setItem(
                $this->hook,
                $bitrixList['bitrixId'],
                $fieldsData
            );
        }
    }
    protected function getBtxListCurrentData(
        $bitrixList,
        $code,
        $listCode
    ) {
        $result = [
            'fieldBtxId' => false,
            'fieldItemBtxId' => false,
        ];
        if (!empty($bitrixList)) { //every from portal


            if (!empty($bitrixList['bitrixfields'])) {

                $btxFields = $bitrixList['bitrixfields'];
                foreach ($btxFields as $btxField) {

                    

                    if ($btxField['code'] === $code) {
                        $result['fieldBtxId'] = $btxField['bitrixCamelId'];
                        Log::channel('telegram')->error('APRIL_HOOK list setItem', [
                            'btxField' =>  $btxField,
                            'code' => $code
                        ]);
                    }
                    if (!empty($btxField['bitrixfielditems'])) {




                        $btxFieldItems = $btxField['bitrixfielditems'];



                        foreach ($btxFieldItems as $btxFieldItem) {

                            if ($listCode) {
                                if ($btxFieldItem['code'] === $listCode) {
                                    $result['fieldItemBtxId'] = $btxFieldItem['bitrixId'];
                                }
                            }
                        }
                    }
                }
            }
        }

        if (!$listCode) {
            return $result['fieldBtxId'];
        } else {
            return $result['fieldItemBtxId'];
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