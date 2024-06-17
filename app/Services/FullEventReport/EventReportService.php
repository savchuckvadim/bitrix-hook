<?php

namespace App\Services\FullEventReport;

use App\Http\Controllers\APIOnlineController;
use App\Http\Controllers\PortalController;
use App\Jobs\BtxCreateListItemJob;
use App\Services\BitrixTaskService;
use App\Services\General\BitrixDepartamentService;
use App\Services\HookFlow\BitrixDealFlowService;
use App\Services\HookFlow\BitrixEntityFlowService;
use App\Services\HookFlow\BitrixListPresentationFlowService;
use DateTime;
use Illuminate\Console\View\Components\Task;
use Illuminate\Support\Facades\Date;
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
    protected $resultStatus;  // result noresult expired

    //если есть текущая задача, то в ее названии будет
    // // Звонок Презентация, Звонок По решению, В оплате 
    // // или currentTask->eventType // xo 'presentation' in Work money_await
    protected $currentReportEventType; // currentTask-> eventType xo
    protected $currentReportEventName;

    protected $comment = '';


    protected $isResult = false;     //boolean
    protected $isExpired = false;     //boolean перенос текущей задачи

    protected $workStatus;    //object with current {code:"setAside" id:1 name:"Отложено"}
    // 0: {id: 0, code: "inJob", name: "В работе"} in_long
    // 1: {id: 1, code: "setAside", name: "Отложено"}
    // 2: {id: 2, code: "success", name: "Продажа"}
    // 3: {id: 3, code: "fail", name: "Отказ"}


    protected $noresultReason = false; // as fals | currentObject
    protected $failReason = false; // as fals | currentObject
    protected $failType = false; // as fals | currentObject



    protected $isInWork = false;  //boolean
    protected $isFail = false;  //boolean
    protected $isSuccessSale = false;  //boolean

    protected $plan;
    protected $presentation;
    protected $isPlanned;
    protected $currentPlanEventType;

    // 0: {id: 1, code: "warm", name: "Звонок"}
    // // 1: {id: 2, code: "presentation", name: "Презентация"}
    // // 2: {id: 3, code: "hot", name: "Решение"}
    // // 3: {id: 4, code: "moneyAwait", name: "Оплата"}

    protected $currentPlanEventTypeName;
    protected $currentPlanEventName;

    protected $planCreatedId;
    protected $planResponsibleId;
    protected $planDeadline;
    protected $nowDate;


    protected $isPresentationDone;
    protected $isUnplannedPresentation;



    protected $currentBtxEntity;
    protected $currentBtxDeals;

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
        $placement = $data['placement'];

        $entityType = null;
        $entityId = null;

        if (isset($placement)) {
            if (!empty($placement['placement'])) {
                if ($placement['placement'] == 'CALL_CARD') {
                    if (!empty($placement['options'])) {
                        if (!empty($placement['options']['CRM_BINDINGS'])) {
                            foreach ($placement['options']['CRM_BINDINGS'] as $crmBind) {
                                if (!empty($crmBind['ENTITY_TYPE'])) {
                                    if ($crmBind['ENTITY_TYPE'] == 'LEAD') {
                                        $entityType = 'lead';
                                        $entityId = $crmBind['ENTITY_ID'];
                                    }
                                    if ($crmBind['ENTITY_TYPE'] == 'COMPANY') {
                                        $entityType = 'company';
                                        $entityId = $crmBind['ENTITY_ID'];
                                        break;
                                    }
                                }
                            }
                        }
                    }
                } else if (strpos($placement['placement'], 'COMPANY') !== false) {
                    $entityType = 'company';
                    $entityId = $placement['options']['ID'];
                } else if (strpos($placement['placement'], 'LEAD') !== false) {
                    $entityType = 'lead';
                    $entityId = $placement['options']['ID'];
                }
            }
        }
        $this->entityType = $entityType;

        $this->entityId = $entityId;

        $this->presentation = $data['presentation'];

        // {isPresentationDone: true
        // isUnplannedPresentation: false
        // presentation: {companyCount: 0, smartCout: 0}
        // companyCount: 0
        // smartCout: 0}

        $this->currentTask = $data['currentTask'];
        if (!empty($data['currentTask'])) {
            if (!empty($data['currentTask']['eventType'])) {
                $this->currentReportEventType = $data['currentTask']['eventType'];

                switch ($data['currentTask']['eventType']) {
                    case 'xo':
                    case 'cold':
                        $this->currentReportEventName = 'Холодный звонок';
                    case 'presentation':
                    case 'pres':
                        $this->currentReportEventName = 'Презентация';
                        break;
                    case 'hot':
                    case 'inProgress':
                    case 'in_progress':
                        $this->currentReportEventName = 'В решении';
                        break;
                    case 'money':
                    case 'moneyAwait':
                    case 'money_await':
                        $this->currentReportEventName = 'В оплате';
                        break;
                    default:
                        # code...
                        break;
                }
            }
        }




        $this->report = $data['report'];

        $this->resultStatus = $data['report']['resultStatus']; // result | noresult | expired - todo expired to pound
        $this->workStatus  = $data['report']['workStatus'];

        if ($data['report']['resultStatus'] === 'result') {
            $this->isResult  = true;
        }
        if ($data['report']['workStatus']['current']['code'] === 'inJob' || $data['report']['workStatus']['current']['code'] === 'setAside') {
            $this->isInWork = true;
        } else  if ($data['report']['workStatus']['current']['code'] === 'fail') {
            $this->isFail =  true;
        } else  if ($data['report']['workStatus']['current']['code'] === 'success') {
            $this->isSuccessSale =  true;
        }

        if ($data['report']['resultStatus'] !== 'result' && $data['plan']['isPlanned']) {
            $this->isExpired  = true;
        }

        if (!empty($data['report']['description'])) {
            $this->comment  = $data['report']['description'];
        }

        if (!empty($data['report']['noresultReason'])) {
            $this->noresultReason  = $data['report']['noresultReason']['current'];
        }

        if (!empty($data['report']['failReason'])) {
            $this->failReason  = $data['report']['failReason']['current'];
        }
        if (!empty($data['report']['failType'])) {
            $this->failType  = $data['report']['failType']['current'];
        }


        $this->plan = $data['plan'];
        $this->isPlanned = $data['plan']['isPlanned'];
        if (!empty($data['plan']['isPlanned']) && !empty($data['plan']['type']) && !empty($data['plan']['type']['current']) && !empty($data['plan']['type']['current']['code'])) {
            $this->currentPlanEventType = $data['plan']['type']['current']['code'];
            $this->currentPlanEventTypeName = $data['plan']['type']['current']['name'];
            $this->currentPlanEventName = $data['plan']['name'];
        };

        if (!empty($data['plan']['createdBy']) && !empty($data['plan']['createdBy']['ID'])) {
            $this->planCreatedId = $data['plan']['createdBy']['ID'];
        };

        if (!empty($data['plan']['responsibility']) && !empty($data['plan']['responsibility']['ID'])) {
            $this->planResponsibleId = $data['plan']['responsibility']['ID'];
        };
        if (!empty($data['plan']['deadline'])) {
            $this->planDeadline = $data['plan']['deadline'];
        };
        $nowDate = new DateTime();

        // Форматируем дату и время в нужный формат
        $this->nowDate = $nowDate->format('d.m.Y H:i:s');

        $this->isPresentationDone = $data['presentation']['isPresentationDone'];





        $this->isUnplannedPresentation = false;

        if (!empty($this->isPresentationDone)) {
            if (!empty($data['currentTask'])) {
                if (!empty($data['currentTask']['eventType'])) {

                    // $this->currentReportEventType = $data['currentTask']['eventType'];


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
        $currentBtxDeals = null;
        // if (!empty($entityType)) {

        //     $currentBtxEntity = BitrixGeneralService::getEntity(
        //         $this->hook,
        //         $entityType,
        //         $entityId

        //     );
        // }

        $currentBtxEntities =  BitrixEntityFlowService::getEntities(
            $this->hook,
            $this->currentTask,
        );
        if (!empty($currentBtxEntities)) {
            if (!empty($currentBtxEntities['companies'])) {
                $currentBtxEntity = $currentBtxEntities['companies'][0];
            }
            if (!empty($currentBtxEntities['deals'])) {
                $currentBtxDeals = $currentBtxEntities['deals'];
            }
        }
        $this->currentBtxEntity  = $currentBtxEntity;
        $this->currentBtxDeals  = $currentBtxDeals;
        // Log::error('APRIL_HOOK portal', ['$portal.lead' => $portal['company']['bitrixfields']]); // массив fields
        // Log::error('APRIL_HOOK portal', ['$portal.company' => $portal['company']['bitrixfields']]); // массив fields



        $fieldsCallCodes = [
            'call_next_date', //ОП Дата Следующего звонка
            'call_next_name',    //ОП Тема Следующего звонка
            'call_last_date',  //ОП Дата последнего звонка
            'xo_created',
            'manager_op',
            'call_next_date', //дата следующего план звонка
            'call_next_name',
            'call_last_date', //дата последнего результативного звонка

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
                        'message' => 'portal smart was not found 420',
                        'smart' => $smart,
                        'portal' => $portal
                    ]
                ]);
            }

            // }
        }


        $this->currentDepartamentType = BitrixDepartamentService::getDepartamentTypeByUserId();
    }


    public function getEventFlow()
    {

        try {
            // Log::channel('telegram')->error('APRIL_HOOK data', ['entityType' => $this->entityType]);
            $updatedCompany = null;
            $updatedLead = null;
            $currentSmart = null;
            $currentSmartId = null;
            $currentDeal = null;
            $currentDealId = null;
            $currentDealsIds = null;
            // if(!$this->smartId){

            // }
            // $randomNumber = rand(1, 2);

            // if ($this->isSmartFlow) {
            //     $this->getSmartFlow();
            // }

            if ($this->isDealFlow && $this->portalDealData) {
                $currentDealsIds = $this->getDealFlow();
            }
            sleep(1);

            // $this->createTask($currentSmartId);

            $this->getEntityFlow();
            // sleep(1);
            if ($this->isExpired || $this->isPlanned) {
                $result = $this->taskFlow(null, $currentDealsIds['planDeals']);
            } else {
                $result = $this->workStatus;
            }
            sleep(1);
            $this->getListFlow();
            sleep(1);
            $this->getListPresentationFlow(
                $currentDealsIds['planDeals']
            );

            return APIOnlineController::getSuccess(['result' => $result]);
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



    //entity
    protected function getEntityFlow()
    {
        $fieldsPresentationCodes = [
            'next_pres_plan_date', // ОП Дата назначенной презентации
            'last_pres_plan_date', //ОП Дата последней назначенной презентации
            'last_pres_done_date',  //ОП Дата последней проведенной презентации
            'last_pres_plan_responsible',  //ОП Кто назначил последнюю заявку на презентацию
            'last_pres_done_responsible',   //ОП Кто провел последнюю презентацию
            'pres_count', //ОП Проведено презентаций
            'pres_comments', //ОП Комментарии после презентаций
            'call_last_date',

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

        $data =   [
            // 'plan' => $this->plan,
            // 'report' => $this->report,
            // 'presentation' => $this->presentation,
            'isPlanned' => $this->isPlanned,
            'isPresentationDone' => $this->isPresentationDone,
            'isUnplannedPresentation' => $this->isUnplannedPresentation,
            'currentReportEventType' => $this->currentReportEventType,
            'currentPlanEventType' => $this->currentPlanEventType,
            '$this->portalCompanyData' => $this->portalCompanyData



        ];
        $fields = $this->portalCompanyData['bitrixfields'];
        $updatedFields = $this->getReportFields(
            [],
            $fields //portal fields
        );



        BitrixEntityFlowService::flow(
            $this->portal,
            $this->hook,
            $this->entityType,
            $this->entityId,
            'xo', // xo warm presentation,
            'plan',  // plan done expired 
            $updatedFields, //updting fields 
        );
    }



    //todo 
    //get clod report fields
    //get warm report fields
    //get presentation report fields
    //get other report fields
    //get new event report fields
    protected function getReportFields(
        $updatedFields,
        $portalFields,


    ) {
        $isPresentationDone = $this->isPresentationDone;
        $isUnplannedPresentation = $this->isUnplannedPresentation;
        $isResult  = $this->isResult;
        $isInWork  = $this->isInWork;
        $isSuccessSale  = $this->isSuccessSale;
        $reportEventType = $this->currentReportEventType;
        $currentReportEventName = $this->currentReportEventName;

        $resultStatus = 'Совершен';

        if ($isInWork) {
            $resultStatus = $resultStatus . ' в работе';
        }


        //general report fields 
        foreach ($portalFields as $pField) {
            switch ($pField['code']) {

                case 'manager_op':
                    $updatedFields['UF_CRM_' . $pField['bitrixId']] = 'user_1';
                    break;
                case 'op_history':
                case 'op_mhistory':
                    $now = now();
                    $stringComment = $now . ' ' . $currentReportEventName . ' ' . $resultStatus;
                    $updatedFields = $this->getCommentsWithEntity($pField, $stringComment, $updatedFields);
                    break;
                default:
                    # code...
                    break;
            }
        }

        if ($reportEventType == 'xo') {

            foreach ($portalFields as $pField) {
                switch ($pField['code']) {
                    case 'call_last_date':
                        $now = date('d.m.Y H:i:s');
                        $updatedFields['UF_CRM_' . $pField['bitrixId']] = $now;
                        break;

                    default:
                        # code...
                        break;
                }
            }
        } else  if ($reportEventType == 'warm') {
            foreach ($portalFields as $pField) {
                switch ($pField['code']) {
                    case 'call_last_date':
                        $now = date('d.m.Y H:i:s');
                        $updatedFields['UF_CRM_' . $pField['bitrixId']] = $now;
                        break;
                    case 'manager_op':
                        $updatedFields['UF_CRM_' . $pField['bitrixId']] = 'user_1';
                        break;
                }
            }
        } else if ($reportEventType == 'presentation') {
            foreach ($portalFields as $pField) {
                switch ($pField['code']) {

                    case 'manager_op':
                        $updatedFields['UF_CRM_' . $pField['bitrixId']] = 'user_1';
                        break;
                }
            }
        } else if ($reportEventType == 'in_progress') {
            foreach ($portalFields as $pField) {
                switch ($pField['code']) {

                    case 'manager_op':
                        $updatedFields['UF_CRM_' . $pField['bitrixId']] = 'user_1';
                        break;
                }
            }
        } else if ($reportEventType == 'money_await') {
            foreach ($portalFields as $pField) {
                switch ($pField['code']) {

                    case 'manager_op':
                        $updatedFields['UF_CRM_' . $pField['bitrixId']] = 'user_1';
                        break;
                }
            }
        } else if ($reportEventType == 'other') {
            foreach ($portalFields as $pField) {
                switch ($pField['code']) {

                    case 'manager_op':
                        $updatedFields['UF_CRM_' . $pField['bitrixId']] = 'user_1';
                        break;
                }
            }
        }

        return $updatedFields;
    }

    protected function getCommentsWithEntity($pField, $stringComment, $fields)
    {
        $fullFieldId = 'UF_CRM_' . $pField['bitrixId'];  //UF_CRM_OP_MHISTORY
        // $now = now();
        // $stringComment = $now . ' ХО запланирован ' . $data['name'] . ' на ' . $data['deadline'];

        $currentComments = '';
        $currentBtxEntity = $this->currentBtxEntity;

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


        $fields[$fullFieldId] =  $currentComments;
        return $fields;
    }



    //get clod plan fields
    //get warm plan fields
    //get presentation plan fields
    //get in_progress plan fields
    //get money_await plan fields
    protected function getPlanFields(
        $updatedFields,
        $portalFields,


    ) {
        $isPresentationDone = $this->isPresentationDone;
        $isUnplannedPresentation = $this->isUnplannedPresentation;
        $isResult  = $this->isResult;
        $isInWork  = $this->isInWork;
        $isSuccessSale  = $this->isSuccessSale;
        $reportEventType = $this->currentReportEventType;
        $currentReportEventName = $this->currentReportEventName;

        $resultStatus = 'Совершен';

        if ($isInWork) {
            $resultStatus = $resultStatus . ' в работе';
        }


        //general report fields 
        foreach ($portalFields as $pField) {
            switch ($pField['code']) {

                case 'manager_op':
                    $updatedFields['UF_CRM_' . $pField['bitrixId']] = 'user_1';
                    break;
                case 'op_history':
                case 'op_mhistory':
                    $now = now();
                    $stringComment = $now . ' ' . $currentReportEventName . ' ' . $resultStatus;
                    $updatedFields = $this->getCommentsWithEntity($pField, $stringComment, $updatedFields);
                    break;
                default:
                    # code...
                    break;
            }
        }

        if ($reportEventType == 'xo') {

            foreach ($portalFields as $pField) {
                switch ($pField['code']) {
                    case 'call_last_date':
                        $now = date('d.m.Y H:i:s');
                        $updatedFields['UF_CRM_' . $pField['bitrixId']] = $now;
                        break;

                    default:
                        # code...
                        break;
                }
            }
        } else  if ($reportEventType == 'warm') {
            foreach ($portalFields as $pField) {
                switch ($pField['code']) {
                    case 'call_last_date':
                        $now = date('d.m.Y H:i:s');
                        $updatedFields['UF_CRM_' . $pField['bitrixId']] = $now;
                        break;
                    case 'manager_op':
                        $updatedFields['UF_CRM_' . $pField['bitrixId']] = 'user_1';
                        break;
                }
            }
        } else if ($reportEventType == 'presentation') {
            foreach ($portalFields as $pField) {
                switch ($pField['code']) {

                    case 'manager_op':
                        $updatedFields['UF_CRM_' . $pField['bitrixId']] = 'user_1';
                        break;
                }
            }
        } else if ($reportEventType == 'in_progress') {
            foreach ($portalFields as $pField) {
                switch ($pField['code']) {

                    case 'manager_op':
                        $updatedFields['UF_CRM_' . $pField['bitrixId']] = 'user_1';
                        break;
                }
            }
        } else if ($reportEventType == 'money_await') {
            foreach ($portalFields as $pField) {
                switch ($pField['code']) {

                    case 'manager_op':
                        $updatedFields['UF_CRM_' . $pField['bitrixId']] = 'user_1';
                        break;
                }
            }
        } else if ($reportEventType == 'other') {
            foreach ($portalFields as $pField) {
                switch ($pField['code']) {

                    case 'manager_op':
                        $updatedFields['UF_CRM_' . $pField['bitrixId']] = 'user_1';
                        break;
                }
            }
        }

        return $updatedFields;
    }



    //get presentation done fields
    //get statuses fields

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
        $reportDeals = [];
        $planDeals = [];
        $currentBtxDeals = $this->currentBtxDeals;
        // report - закрывает сделки
        // plan - создаёт
        //todo report flow

        // if report type = xo | cold
        $currentReportStatus = 'done';

        // if ($this->currentReportEventType == 'xo') {

        if ($this->isFail) {
            //найти сделку хо -> закрыть в отказ , заполнить поля отказа по хо 
            $currentReportStatus = 'fail';
        } else {
            if ($this->isResult) {                   // результативный

                if ($this->isInWork) {                // в работе или успех
                    //найти сделку хо и закрыть в успех
                }
            } else { //нерезультативный 
                if ($this->isPlanned) {                // если запланирован нерезультативный - перенос 
                    //найти сделку хо и закрыть в успех
                    $currentReportStatus = 'expired';
                }
            }
        }
        // }

        // if ($this->currentReportEventType !== 'presentation') {            // если не презентация - отчитываемся просто по закрытию звонка
        //     $reportDeals = BitrixDealFlowService::flow(  //закрывает сделку
        //         $this->hook,
        //         $this->currentBtxDeals,
        //         $this->portalDealData,
        //         $this->currentDepartamentType,
        //         $this->entityType,
        //         $this->entityId,
        //         $this->currentReportEventType, // xo warm presentation,
        //         $this->currentReportEventName,
        //         $this->currentPlanEventName,
        //         $currentReportStatus,  // plan done expired fail
        //         $this->planResponsibleId,
        //         '$fields'
        //     );
        // }

        // if ($this->isPresentationDone) {                 // вне зависимости от текущего отчетного события,
        //     // если была нажата презентация проведена  

        //     $reportDeals = BitrixDealFlowService::flow(  // закрывает сделку или создает и закрывает сделку - презентация
        //         $this->hook,
        //         null,
        //         $this->portalDealData,
        //         $this->currentDepartamentType,
        //         $this->entityType,
        //         $this->entityId,
        //         'presentation', // xo warm presentation,
        //         'Презентация',
        //         'Проведена',
        //         'done',  // plan done expired fail
        //         $this->planResponsibleId,
        //         '$fields'
        //     );

        //     if ($this->isFail) {
        //         $reportDeals = BitrixDealFlowService::flow(  // закрывает сделку или создает и закрывает сделку - презентация
        //             $this->hook,
        //             null,
        //             $this->portalDealData,
        //             $this->currentDepartamentType,
        //             $this->entityType,
        //             $this->entityId,
        //             'presentation', // xo warm presentation,
        //             'Презентация',
        //             'Отказ',
        //             'fail',  // plan done expired fail
        //             $this->planResponsibleId,
        //             '$fields'
        //         );
        //     }
        // }

        if ($this->isPresentationDone && $this->currentReportEventType !== 'presentation') { // проведенная презентация будет isUnplanned
            //в current task не будет id сделки презентации
            // в таком случае предполагается, что сделки презентация еще не существует
            $currentBtxDeals = [];
            $unplannedPresDeal = BitrixDealFlowService::unplannedPresflow(  //  создает - презентация
                $this->hook,
                null,
                $this->portalDealData,
                $this->currentDepartamentType,
                $this->entityType,
                $this->entityId,
                'presentation', // xo warm presentation,
                'Презентация',
                'Запланирована',
                'plan',  // plan done expired fail
                $this->planResponsibleId,
                true,
                '$fields'
            );

            if (!empty($unplannedPresDeal)) {
                if (isset($unplannedPresDeal['ID'])) {

                    $unplannedPresDealId = $unplannedPresDeal['ID'];
                    array_push($this->currentBtxDeals, $unplannedPresDeal);
                    $unplannedPresResultStatus = 'done';
                    $unplannedPresResultName = 'Проведена';
                    if ($this->isFail) {
                        $unplannedPresResultStatus = 'fail';
                        $unplannedPresResultName = 'Отказ после презентации';
                    }
                    BitrixDealFlowService::flow(  // закрывает сделку  - презентация обновляет базовую в соответствии с проведенной през
                        $this->hook,
                        $this->currentBtxDeals,
                        $this->portalDealData,
                        $this->currentDepartamentType,
                        $this->entityType,
                        $this->entityId,
                        'presentation', // xo warm presentation,
                        'Презентация',
                        $unplannedPresResultName,
                        $unplannedPresResultStatus,  // plan done expired fail
                        $this->planResponsibleId,
                        true,
                        '$fields'
                    );

                    foreach ($this->currentBtxDeals as $cbtxdeal) {
                        if ($cbtxdeal['ID'] !== $unplannedPresDealId) {
                            array_push($currentBtxDeals, $cbtxdeal);
                        }
                    }
                }
            }
        }
        sleep(1);


        //если был unplanned а потом plan ->
        //если warm plan а report был xo 
        // - то нужна обновленная стадия в базовой битрикс сделке что не пыталось повысить
        // с xo в warm так как уже на самом деле pres 
        // если plan pres -> планируется новая презентация и поэтому в  
        // $this->currentBtxDeals должна отсутствовать сделка презентации созданная при unplanned, 
        // которая пушится туда  при unplanned - чтобы были обработаны базовая сделка 
        // в соответствии с проведенной през
        $reportDeals = BitrixDealFlowService::flow(  // редактирует сделки отчетности из currentTask основную и если есть xo
            $this->hook,
            $currentBtxDeals,
            $this->portalDealData,
            $this->currentDepartamentType,
            $this->entityType,
            $this->entityId,
            $this->currentReportEventType, // xo warm presentation,
            $this->currentReportEventName,
            $this->currentPlanEventName,
            $currentReportStatus,  // plan done expired fail
            $this->planResponsibleId,
            $this->isResult,
            '$fields'
        );
        //todo plan flow

        // if ($this->currentPlanEventType == 'warm') {
        //     // найти или создать сделку base не sucess стадия теплый прозвон


        // }
        // if plan type = xo | cold

        //если запланирован
        //xo - создать или обновить ХО & Основная
        //warm | money_await | in_progress - создать или обновить  Основная
        //presentation - создать или обновить presentation & Основная
        sleep(1);
        if ($this->isPlanned) {
            $currentBtxDeals = BitrixDealFlowService::getBaseDealFromCurrentBtxDeals(
                $this->portalDealData,
                $currentBtxDeals
            );

            $planDeals =  BitrixDealFlowService::flow( //создает сделку
                $this->hook,
                $currentBtxDeals,
                $this->portalDealData,
                $this->currentDepartamentType,
                $this->entityType,
                $this->entityId,
                $this->currentPlanEventType, // xo warm presentation, hot moneyAwait
                $this->currentPlanEventTypeName,
                $this->currentPlanEventName,
                'plan',  // plan done expired 
                $this->planResponsibleId,
                $this->isResult,
                '$fields'
            );
        }

        Log::channel('telegram')->info('presentationBtxList', [
            'reportDeals' => $reportDeals,
            'planDeals' => $planDeals,
            // 'failReason' => $failReason,
            // 'failType' => $failType,

        ]);

        return [
            'reportDeals' => $reportDeals,
            'planDeals' => $planDeals,
        ];
    }




    //tasks for complete


    protected function taskFlow(
        $currentSmartItemId,
        $currentDealsIds

    ) {




        try {
            // Log::channel('telegram')->error('APRIL_HOOK', $this->portal);
            $companyId  = null;
            $leadId  = null;
            $currentTaskId = null;
            if (!empty($this->currentTask)) {
                if (!empty($this->currentTask['id'])) {
                    $currentTaskId = $this->currentTask['id'];
                }
            }

            if ($this->entityType == 'company') {

                $companyId  = $this->entityId;
            } else if ($this->entityType == 'lead') {
                $leadId  = $this->entityId;
            }
            $taskService = new BitrixTaskService();

            if (!$this->isExpired) {
                $createdTask =  $taskService->createTask(
                    $this->currentPlanEventType,       //$type,   //cold warm presentation hot 
                    $this->currentPlanEventTypeName,
                    $this->portal,
                    $this->domain,
                    $this->hook,
                    $companyId,  //may be null
                    $leadId,     //may be null
                    // $this->planCreatedId,
                    $this->planResponsibleId,
                    $this->planResponsibleId,
                    $this->planDeadline,
                    $this->currentPlanEventName,
                    $currentSmartItemId,
                    true, //$isNeedCompleteOtherTasks
                    $currentTaskId,
                    $currentDealsIds,

                );
            } else {
                $createdTask =  $taskService->updateTask(

                    $this->domain,
                    $this->hook,
                    $currentTaskId,
                    $this->planDeadline,
                    $this->currentPlanEventName,
                );
            }


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


    protected function getListFlow()
    {
        $reportEventType = $this->currentReportEventType;
        $reportEventTypeName = $this->currentReportEventName;
        $planEventTypeName = $this->currentPlanEventTypeName;
        $planEventType = $this->currentPlanEventType; //если перенос то тип будет автоматически взят из report - предыдущего события
        $eventAction = 'expired';  // не состоялся и двигается крайний срок 
        $planComment = 'Перенесен';


        if (!$this->isExpired) {  // если не перенос, то отчитываемся по прошедшему событию
            //report
            $eventAction = 'plan';
            $planComment = 'Запланирован';

            if ($reportEventType !== 'presentation') {

                //если текущий не презентация
                BtxCreateListItemJob::dispatch(  //report - отчет по текущему событию
                    $this->hook,
                    $this->bitrixLists,
                    $reportEventType,
                    $reportEventTypeName,
                    'done',
                    // $this->stringType,
                    $this->planDeadline,
                    $this->planResponsibleId,
                    $this->planResponsibleId,
                    $this->planResponsibleId,
                    $this->entityId,
                    $this->comment,
                    $this->workStatus['current'],
                    $this->resultStatus, // result noresult expired,
                    $this->noresultReason,
                    $this->failReason,
                    $this->failType

                )->onQueue('low-priority');
            }

            //если была проведена презентация - не важно какое текущее report event

            if ($this->isPresentationDone == true) {
                //если была проведена през
                if ($reportEventType !== 'presentation') {
                    //если текущее событие не през - значит uplanned
                    //значит надо запланировать през в холостую
                    BtxCreateListItemJob::dispatch(  //запись о планировании и переносе
                        $this->hook,
                        $this->bitrixLists,
                        'presentation',
                        'Презентация',
                        'plan',
                        // $this->stringType,
                        $this->nowDate,
                        $this->planResponsibleId,
                        $this->planResponsibleId,
                        $this->planResponsibleId,
                        $this->entityId,
                        'не запланированая презентация',
                        ['code' => 'inJob'], //$this->workStatus['current'],
                        'result',  // result noresult expired
                        $this->noresultReason,
                        $this->failReason,
                        $this->failType

                    )->onQueue('low-priority');
                }
                BtxCreateListItemJob::dispatch(  //report - отчет по текущему событию
                    $this->hook,
                    $this->bitrixLists,
                    'presentation',
                    'Презентация',
                    'done',
                    // $this->stringType,
                    $this->planDeadline,
                    $this->planResponsibleId,
                    $this->planResponsibleId,
                    $this->planResponsibleId,
                    $this->entityId,
                    $this->comment,
                    $this->workStatus['current'],
                    $this->resultStatus, // result noresult expired,
                    $this->noresultReason,
                    $this->failReason,
                    $this->failType

                )->onQueue('low-priority');
            }
        }



        if ($this->isPlanned) {
            BtxCreateListItemJob::dispatch(  //запись о планировании и переносе
                $this->hook,
                $this->bitrixLists,
                $planEventType,
                $planEventTypeName,
                $eventAction,
                // $this->stringType,
                $this->planDeadline,
                $this->planResponsibleId,
                $this->planResponsibleId,
                $this->planResponsibleId,
                $this->entityId,
                $planComment,
                $this->workStatus['current'],
                $this->resultStatus,  // result noresult expired
                $this->noresultReason,
                $this->failReason,
                $this->failType

            )->onQueue('low-priority');
        }
    }
    protected function getListPresentationFlow(
        $planPresDealIds
    ) {
        $currentTask = $this->currentTask;
        $currentDealIds = $planPresDealIds;
        // presentation list flow запускается когда
        // планируется презентация или unplunned тогда для связи со сделками берется $planPresDealIds
        // отчитываются о презентации презентация или unplunned тогда для связи со сделками берется $currentTask

        if ($this->currentPlanEventType == 'presentation') {
            $eventType = 'plan';
            Log::channel('telegram')->info('pres lidt test plan', [
                'currentDealIds' => $currentDealIds,
                // 'noresultReason' => $noresultReason,
                // 'failReason' => $failReason,
                // 'failType' => $failType,

            ]);
            BitrixListPresentationFlowService::getListPresentationFlow(
                $this->hook,
                $this->bitrixLists,
                $currentDealIds,
                $this->nowDate,
                $eventType

            );
        }
        sleep(1);
        if ($this->currentReportEventType == 'presentation') {
            $currentDealIds = [];
            if (!empty($currentTask)) {
                if (!empty($currentTask['ufCrmTask'])) {
                    $array = $currentTask['ufCrmTask'];
                    foreach ($array as $item) {
                        // Проверяем, начинается ли элемент с "D_"
                        if (strpos($item, "D_") === 0) {
                            // Добавляем ID в массив, удаляя первые два символа "D_"
                            $currentDealIds[] = substr($item, 2);
                        }
                    }
                }
            }
            $eventType = 'report';
            Log::channel('telegram')->info('pres lidt test report', [
                'currentDealIds' => $currentDealIds,
                // 'noresultReason' => $noresultReason,
                // 'failReason' => $failReason,
                // 'failType' => $failType,

            ]);
            BitrixListPresentationFlowService::getListPresentationFlow(
                $this->hook,
                $this->bitrixLists,
                $currentDealIds,
                $this->nowDate,
                $eventType

            );
        }

        // $reportEventType = $this->currentReportEventType;
        // $reportEventTypeName = $this->currentReportEventName;
        // $planEventTypeName = $this->currentPlanEventTypeName;
        // $planEventType = $this->currentPlanEventType; //если перенос то тип будет автоматически взят из report - предыдущего события
        // $eventAction = 'expired';  // не состоялся и двигается крайний срок 
        // $planComment = 'Перенесен';


        // if (!$this->isExpired) {  // если не перенос, то отчитываемся по прошедшему событию
        //     //report
        //     $eventAction = 'plan';
        //     $planComment = 'Запланирован';

        //     if ($reportEventType !== 'presentation') {

        //         //если текущий не презентация
        //         BtxCreateListItemJob::dispatch(  //report - отчет по текущему событию
        //             $this->hook,
        //             $this->bitrixLists,
        //             $reportEventType,
        //             $reportEventTypeName,
        //             'done',
        //             // $this->stringType,
        //             $this->planDeadline,
        //             $this->planResponsibleId,
        //             $this->planResponsibleId,
        //             $this->planResponsibleId,
        //             $this->entityId,
        //             $this->comment,
        //             $this->workStatus['current'],
        //             $this->resultStatus, // result noresult expired,
        //             $this->noresultReason,
        //             $this->failReason,
        //             $this->failType

        //         )->onQueue('low-priority');
        //     }

        //     //если была проведена презентация - не важно какое текущее report event

        //     if ($this->isPresentationDone == true) {
        //         //если была проведена през
        //         if ($reportEventType !== 'presentation') {
        //             //если текущее событие не през - значит uplanned
        //             //значит надо запланировать през в холостую
        //             BtxCreateListItemJob::dispatch(  //запись о планировании и переносе
        //                 $this->hook,
        //                 $this->bitrixLists,
        //                 'presentation',
        //                 'Презентация',
        //                 'plan',
        //                 // $this->stringType,
        //                 $this->nowDate,
        //                 $this->planResponsibleId,
        //                 $this->planResponsibleId,
        //                 $this->planResponsibleId,
        //                 $this->entityId,
        //                 'не запланированая презентация',
        //                 ['code' => 'inJob'], //$this->workStatus['current'],
        //                 'result',  // result noresult expired
        //                 $this->noresultReason,
        //                 $this->failReason,
        //                 $this->failType

        //             )->onQueue('low-priority');
        //         }
        //         BtxCreateListItemJob::dispatch(  //report - отчет по текущему событию
        //             $this->hook,
        //             $this->bitrixLists,
        //             'presentation',
        //             'Презентация',
        //             'done',
        //             // $this->stringType,
        //             $this->planDeadline,
        //             $this->planResponsibleId,
        //             $this->planResponsibleId,
        //             $this->planResponsibleId,
        //             $this->entityId,
        //             $this->comment,
        //             $this->workStatus['current'],
        //             $this->resultStatus, // result noresult expired,
        //             $this->noresultReason,
        //             $this->failReason,
        //             $this->failType

        //         )->onQueue('low-priority');
        //     }
        // }



        // if ($this->isPlanned) {
        //     BtxCreateListItemJob::dispatch(  //запись о планировании и переносе
        //         $this->hook,
        //         $this->bitrixLists,
        //         $planEventType,
        //         $planEventTypeName,
        //         $eventAction,
        //         // $this->stringType,
        //         $this->planDeadline,
        //         $this->planResponsibleId,
        //         $this->planResponsibleId,
        //         $this->planResponsibleId,
        //         $this->entityId,
        //         $planComment,
        //         $this->workStatus['current'],
        //         $this->resultStatus,  // result noresult expired
        //         $this->noresultReason,
        //         $this->failReason,
        //         $this->failType

        //     )->onQueue('low-priority');
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