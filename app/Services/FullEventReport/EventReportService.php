<?php

namespace App\Services\FullEventReport;

use App\Http\Controllers\APIBitrixController;
use App\Http\Controllers\APIOnlineController;
use App\Http\Controllers\PortalController;
use App\Jobs\BtxCreateListItemJob;
use App\Services\BitrixGeneralService;
use App\Services\General\BitrixDealService;
use App\Services\General\BitrixDepartamentService;
use App\Services\General\BitrixListService;
use App\Services\HookFlow\BitrixDealFlowService;
use App\Services\HookFlow\BitrixEntityFlowService;
use App\Services\HookFlow\BitrixListFlowService;
use App\Services\HookFlow\BitrixSmartFlowService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EventReportService




{
    protected $portal;
    protected $aprilSmartData;
    protected $smartCrmId;

    protected $hook;


    // protected $type;
    protected $domain;
    protected $entityType;
    protected $entityId;
    protected $currentTask;
    protected $report;
    protected $plan;
    protected $presentation;
    protected $isPlanned;
    protected $isPresentationDone;
    protected $isUnplannedPresentation;
    protected $currentReportEventType;




    protected $taskTitle;

    protected $categoryId = 28;
    protected $stageId = 'DT162_28:NEW';


    // // TODO to DB


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
        $report = $data['report'];
        $plan = $data['plan'];
        $placement = $data['placement'];
        $currentTask = $data['currentTask'];
        $this->entityType = $data['entityType'];
        $this->entityId = $data['entityId'];

        if (isset($placement)) {
            if (!empty($placement['placement'])) {
                if ($placement['placement'] == 'CALL_CARD') {
                    if (!empty($placement['options'])) {
                        if (!empty($placement['options']['CRM_BINDINGS'])) {
                            foreach ($placement['options']['CRM_BINDINGS'] as $crmBind) {
                                if (!empty($crmBind['ENTITY_TYPE'])) {
                                    if ($crmBind['ENTITY_TYPE'] == 'LEAD') {
                                        $this->entityType = 'lead';
                                        $this->entityId = $crmBind['ENTITY_ID'];
                                    }
                                    if ($crmBind['ENTITY_TYPE'] == 'COMPANY') {
                                        $this->entityType = 'company';
                                        $this->entityId = $crmBind['ENTITY_ID'];
                                        break;
                                    }
                                }
                            }
                        }
                    }
                } else if (strpos($placement['placement'], 'COMPANY') !== false) {
                    $this->entityType = 'company';
                    $this->entityId = $placement['options']['ID'];
                } else if (strpos($placement['placement'], 'LEAD') !== false) {
                    $this->entityType = 'lead';
                    $this->entityId = $placement['options']['ID'];
                }
            }
        }
        $this->presentation = $data['presentation'];
        $this->isPlanned = $data['plan']['isPlanned'];
        $this->isPresentationDone = $data['presentation']['isPresentationDone'];

        $this->isUnplannedPresentation = false;

        if (!empty($this->isPresentationDone)) {
            if (!empty($data['currentTask'])) {
                if (!empty($data['currentTask']['eventType'])) {

                    $this->currentReportEventType = $data['currentTask']['eventType'];


                    if ($data['currentTask']['eventType'] !== 'presentation') {
                        $this->isUnplannedPresentation = true;
                    }
                }
            } else {
                $this->isUnplannedPresentation = true;
            }
        }



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



        $fieldsCallCodes = [
            'call_next_date', //ОП Дата Следующего звонка
            'call_next_name',    //ОП Тема Следующего звонка
            'call_last_date',  //ОП Дата последнего звонка
            'xo_created',
            'manager_op',
            'call_next_date',
            'call_next_name',
            'call_last_date',

        ];

        $fieldsPresentationCodes = [
            'next_pres_plan_date', // ОП Дата назначенной презентации
            'last_pres_plan_date', //ОП Дата последней назначенной презентации
            'last_pres_done_date',  //ОП Дата последней проведенной презентации
            'last_pres_plan_responsible',  //ОП Кто назначил последнюю заявку на презентацию
            'last_pres_done_responsible',   //ОП Кто провел последнюю презентацию
            'pres_count', //ОП Проведено презентаций
            'pres_comments', //ОП Комментарии после презентаций
            'call_last_date',
            'op_history',
            'op_history_multiple',
        ];

        $statusesCodes = [
            'op_work_status', //Статус Работы
            'op_fail_type', //тип отказа  ОП Неперспективная
            'op_fail_reason', //причина отказа
            'op_noresult_reason', //ОП Причины нерезультативности
        ];
        $generalSalesCode = [
            'manager_op',  //Менеджер по продажам Гарант
            'op_history',
            'op_history_multiple',
        ];

        $failSalesCode = [
            'op_fail_comments',  //ОП Комментарии после отказов

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
                            case 'op_mhistory':

                                $fullFieldId = 'UF_CRM_' . $pField['bitrixId'];  //UF_CRM_OP_MHISTORY
                                $now = now();
                                $stringComment = $now . ' ХО запланирован ' . $data['name'] . ' на ' . $data['deadline'];

                                $currentComments = '';


                                if (!empty($currentBtxEntity)) {
                                    // if (isset($currentBtxCompany[$fullFieldId])) {

                                    $currentComments = $currentBtxEntity[$fullFieldId];

                                    if ($pField['code'] == 'op_mhistory') {
                                        $currentComments = [];
                                        array_push($currentComments, $stringComment);
                                        // if (!empty($currentComments)) {
                                        //     array_push($currentComments, $stringComment);
                                        // } else {
                                        //     $currentComments = $stringComment;
                                        // }
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
                        $smart = $pSmart;
                    }
                }
            }
            if (!empty($smart)) {
                $smartForStageId = $smart['forStage'];

                if (!empty($smart['categories'])) {
                    foreach ($smart['categories'] as $category) {

                        if ($category && !empty($category['code'])) {

                            if ($category['code'] == 'sales_cold') {

                                $targetCategoryId = $category['bitrixId'];
                                if (!empty($category['stages'])) {
                                    foreach ($category['stages'] as $stage) {
                                        if ($stage['code'] == 'cold_plan') {
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
            } else {
                Log::channel('telegram')->error('APRIL_HOOK COLD cold sevice', [
                    'data' => [
                        'message' => 'portal smart was not found 340',
                        'smart' => $smart,
                        'portal' => $portal
                    ]
                ]);
            }

            // }
        }


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

            // if ($this->isSmartFlow) {
            //     $this->getSmartFlow();
            // }

            // if ($this->isDealFlow && $this->portalDealData) {
            //     $this->getDealFlow();
            // }


            // $this->createTask($currentSmartId);

            $this->getEntityFlow();
            // if ($this->withLists) {
            $nowDate = now();
            // BtxCreateListItemJob::dispatch(
            //     $this->hook,
            //     $this->bitrixLists,
            //     'xo',
            //     'plan',
            //     $nowDate,
            //     $this->stringType,
            //     $this->deadline,
            //     $this->createdId,
            //     $this->responsibleId,
            //     $this->responsibleId,
            //     $this->entityId,
            //     '$comment'
            // )->onQueue('low-priority');
            // BitrixListFlowService::getListsFlow(
            //     $this->hook,
            //     $this->bitrixLists,
            //     'xo',
            //     'plan',
            //     $this->deadline,
            //     $this->stringType,
            //     $this->deadline,
            //     $this->createdId,
            //     $this->responsibleId,
            //     $this->responsibleId,
            //     $this->entityId,
            //     '$comment'
            // );
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
    protected function getEntityFlow()
    {

        Log::channel('telegram')->info(
            'HOOK event flow',
            [
                'plan' => $this->plan,
                'report' => $this->report,
                'presentation' => $this->presentation,
                'isPlanned' => $this->isPlanned,
                'isPresentationDone' => $this->isPresentationDone,
                'isUnplannedPresentation' => $this->isUnplannedPresentation,
                'currentReportEventType' => $this->currentReportEventType
                

            ]

        );
        // BitrixEntityFlowService::flow(
        //     $this->portal,
        //     $this->hook,
        //     $this->entityType,
        //     $this->entityId,
        //     'xo', // xo warm presentation,
        //     'plan',  // plan done expired 
        //     $this->entityFieldsUpdatingContent, //updting fields 
        // );
    }

    //smart
    protected function getSmartFlow()
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

        // $responsibleId  = $this->responsibleId;
        // $smart  = $this->aprilSmartData;

        // $companyId  = null;
        // $leadId  = null;

        // if ($this->entityType == 'company') {

        //     $companyId  = $this->entityId;
        // } else if ($this->entityType == 'lead') {
        //     $leadId  = $this->entityId;
        // }

        // $resultFields = [];
        // $fieldsData = [];
        // $fieldsData['categoryId'] = $this->categoryId;
        // $fieldsData['stageId'] = $this->stageId;


        // // $fieldsData['ufCrm7_1698134405'] = $companyId;
        // $fieldsData['assigned_by_id'] = $responsibleId;
        // // $fieldsData['companyId'] = $companyId;

        // if ($companyId) {
        //     $fieldsData['ufCrm7_1698134405'] = $companyId;
        //     $fieldsData['company_id'] = $companyId;
        // }
        // if ($leadId) {
        //     $fieldsData['parentId1'] = $leadId;
        //     $fieldsData['ufCrm7_1697129037'] = $leadId;
        // }

        // $fieldsData[$this->lastCallDateField] = $this->deadline;  //дата звонка следующего
        // $fieldsData[$this->lastCallDateFieldCold] = $this->deadline; //дата холодного следующего
        // $fieldsData[$this->callThemeField] = $this->name;      //тема следующего звонка
        // $fieldsData[$this->callThemeFieldCold] = $this->name;  //тема холодного звонка

        // $fieldsData['ufCrm6_1702652862'] = $responsibleId; // alfacenter Ответственный ХО 

        // if ($this->createdId) {
        //     $fieldsData[$this->createdFieldCold] = $this->createdId;  // Постановщик ХО - smart field

        // }



        // $entityId = $smart['crmId'];


        // return BitrixSmartFlowService::flow(
        //     $this->aprilSmartData,
        //     $this->hook,
        //     $this->entityType,
        //     $entityId,
        //     'xo', // xo warm presentation,
        //     'plan',  // plan done expired 
        //     $this->responsibleId,
        //     $fieldsData
        // );
    }



    // deal flow

    protected function getDealFlow()
    {
        // BitrixDealFlowService::flow(
        //     $this->hook,
        //     $this->portalDealData,
        //     $this->currentDepartamentType,
        //     $this->entityType,
        //     $this->entityId,
        //     'xo', // xo warm presentation,
        //     'plan',  // plan done expired 
        //     $this->responsibleId,
        //     '$fields'
        // );
    }




    //tasks for complete


    public function createTask(
        $currentSmartItemId

    ) {




        //     try {
        //         // Log::channel('telegram')->error('APRIL_HOOK', $this->portal);
        //         $companyId  = null;
        //         $leadId  = null;
        //         if ($this->entityType == 'company') {

        //             $companyId  = $this->entityId;
        //         } else if ($this->entityType == 'lead') {
        //             $leadId  = $this->entityId;
        //         }
        //         $taskService = new BitrixTaskService();


        //         $createdTask =  $taskService->createTask(
        //             'cold',       //$type,   //cold warm presentation hot 
        //             $this->stringType,
        //             $this->portal,
        //             $this->domain,
        //             $this->hook,
        //             $companyId,  //may be null
        //             $leadId,     //may be null
        //             $this->createdId,
        //             $this->responsibleId,
        //             $this->deadline,
        //             $this->name,
        //             $currentSmartItemId,
        //             true, //$isNeedCompleteOtherTasks
        //         );

        //         return $createdTask;
        //     } catch (\Throwable $th) {
        //         $errorMessages =  [
        //             'message'   => $th->getMessage(),
        //             'file'      => $th->getFile(),
        //             'line'      => $th->getLine(),
        //             'trace'     => $th->getTraceAsString(),
        //         ];
        //         Log::error('ERROR COLD: createColdTask',  $errorMessages);
        //         Log::info('error COLD', ['error' => $th->getMessage()]);
        //         Log::channel('telegram')->error('APRIL_HOOK', $errorMessages);
        //         return $createdTask;
        //     }
        // }
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