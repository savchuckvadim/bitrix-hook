<?php

namespace App\Services\FullEventReport;

use App\Http\Controllers\APIOnlineController;
use App\Http\Controllers\Front\EventCalling\FullEventInitController;
use App\Http\Controllers\Front\EventCalling\ReportController;
use App\Http\Controllers\PortalController;
use App\Jobs\BtxCreateListItemJob;
use App\Jobs\BtxSuccessListItemJob;
use App\Services\BitrixTaskService;
use App\Services\General\BitrixBatchService;
use App\Services\General\BitrixDealService;
use App\Services\General\BitrixDepartamentService;
use App\Services\HookFlow\BitrixDealBatchFlowService;
use App\Services\HookFlow\BitrixDealFlowService;
use App\Services\HookFlow\BitrixEntityFlowService;
use App\Services\HookFlow\BitrixListFlowService;
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
    // todo если нет текущей задачи значит нужно брать report event type из списка типа событий отчета
    // в котором могут быть входящий звонок и тд
    // или пока просто можно воспринимать как NEW 
    protected $currentReportEventName = '';

    protected $comment = '';
    protected $currentTaskTitle = '';

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

    // 0: {id: 0, code: "garant", name: "Гарант/Запрет"}
    // // 1: {id: 1, code: "go", name: "Покупает ГО"}
    // // 2: {id: 2, code: "territory", name: "Чужая территория"}
    // // 3: {id: 3, code: "accountant", name: "Бухприх"}
    // // 4: {id: 4, code: "autsorc", name: "Аутсорсинг"}
    // // 5: {id: 5, code: "depend", name: "Несамостоятельная организация"}
    // // 6: {id: 6, code: "failure", name: "Отказ"}


    protected $isInWork = false;  //boolean
    protected $isFail = false;  //boolean
    protected $isSuccessSale = false;  //boolean
    protected $isNew = false;  //boolean


    protected $plan;
    protected $presentation;
    protected $isPlanned;
    protected $currentPlanEventType;

    // 0: {id: 1, code: "warm", name: "Звонок"}
    // // 1: {id: 2, code: "presentation", name: "Презентация"}
    // // 2: {id: 3, code: "hot", name: "Решение"}
    // // 3: {id: 4, code: "moneyAwait", name: "Оплата"}

    protected $currentPlanEventTypeName;
    protected $currentPlanEventName; // name 

    protected $planCreatedId;
    protected $planResponsibleId;
    protected $planDeadline;
    protected $nowDate;


    protected $isPresentationDone;
    protected $isUnplannedPresentation;





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


    //sale

    protected $relationSalePresDeal = null;


    // deals
    protected $currentBtxEntity;
    protected $currentBtxDeals;


    protected $currentBaseDeal;
    protected $currentPresDeal;
    protected $currentColdDeal;
    protected $currentTMCDeal;

    protected $relationBaseDeals;
    protected $relationCompanyUserPresDeals; //allPresDeals
    protected $relationFromBasePresDeals;
    protected $relationColdDeals;
    protected $relationTMCDeals;


    protected $btxDealBaseCategoryId;
    protected $btxDealPresCategoryId;



    public function __construct(

        $data,

    ) {
        date_default_timezone_set('Europe/Moscow');
        $nowDate = new DateTime();
        // Форматируем дату и время в нужный формат
        $this->nowDate = $nowDate->format('d.m.Y H:i:s');


        $domain = $data['domain'];
        $portal = PortalController::getPortal($domain);
        $portal = $portal['data'];
        $this->portal = $portal;


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
        $this->currentReportEventType = 'new';
        if (!empty($data['currentTask'])) {
            if (!empty($data['currentTask']['eventType'])) {

                if (isset($data['currentTask']['TITLE'])) {
                    $this->currentTaskTitle = $data['currentTask']['TITLE'];
                }
                if (isset($data['currentTask']['title'])) {
                    $this->currentTaskTitle = $data['currentTask']['title'];
                }



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
        if ($data['report']['resultStatus'] === 'new') {
            $this->isNew  = true;
        }

        // Log::channel('telegram')->info('HOOK TEST sessionDeals', [
        //     'isNew' => $this->isNew,
        //     'data' => $data['report']['resultStatus'],
        //     'currentTask' => $data['currentTask']


        // ]);


        if ($data['report']['workStatus']['current']['code'] === 'inJob' || $data['report']['workStatus']['current']['code'] === 'setAside') {
            $this->isInWork = true;
        } else  if ($data['report']['workStatus']['current']['code'] === 'fail') {
            $this->isFail =  true;
        } else  if ($data['report']['workStatus']['current']['code'] === 'success') {
            $this->isSuccessSale =  true;
        }

        if ($data['report']['resultStatus'] !== 'result' && $data['report']['resultStatus'] !== 'new' && $data['plan']['isPlanned']) {
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


        // Log::info('HOOK TEST sessionData', [
        //     'sessionData' => $sessionData

        // ]);
        // Log::channel('telegram')->info('HOOK TEST sessionData', [
        //     'task from session' => $sessionData['currentTask']

        // ]);


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


        $btxDealBaseCategoryId = null;
        $btxDealPresCategoryId = null;

        if (!empty($portal['bitrixDeal'])) {

            if (!empty($portal['bitrixDeal']['categories'])) {

                foreach ($portal['bitrixDeal']['categories'] as $pCategory) {
                    if ($pCategory['code'] == 'sales_base') {
                        $this->btxDealBaseCategoryId = $pCategory['bitrixId'];
                        $btxDealBaseCategoryId = $pCategory['bitrixId'];
                    }

                    if ($pCategory['code'] == 'sales_presentation') {
                        $this->btxDealPresCategoryId = $pCategory['bitrixId'];
                        $btxDealPresCategoryId = $pCategory['bitrixId'];
                    }
                }
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
        $currentBtxDeals = [];
        // if (!empty($entityType)) {

        //     $currentBtxEntity = BitrixGeneralService::getEntity(
        //         $this->hook,
        //         $entityType,
        //         $entityId

        //     );
        // }


        if (!empty($data['currentTask'])) {
            if (!empty($data['currentTask']['id'])) {

                $sessionKey = $domain . '_' . $data['currentTask']['id'];
                $sessionData = FullEventInitController::getSessionItem($sessionKey);


                if (isset($sessionData['currentCompany']) && isset($sessionData['deals'])) {
                    $this->currentBtxEntity  = $sessionData['currentCompany'];


                    $sessionDeals = $sessionData['deals'];
                } else {
                    $sessionData = ReportController::getFullDealsInner(
                        $this->hook,
                        $portal,
                        $domain,
                        $this->currentTask
                    );
                    if (!empty($sessionData['deals'])) {
                        $sessionDeals = $sessionData['deals'];
                    }


                    // Log::info('HOOK TEST sessionDeals', [
                    //     'sessionDeals' => $sessionDeals,



                    // ]);
                }
                if (
                    isset($sessionDeals['currentBaseDeal'])
                    //  &&
                    // isset($sessionDeals['allBaseDeals'])
                    // isset($sessionDeals['currentPresentationDeal']) &&
                    // isset($sessionDeals['basePresentationDeals']) &&
                    // isset($sessionDeals['allPresentationDeals']) &&
                    // // isset($sessionDeals['presList']) &&
                    // isset($sessionDeals['currentXODeal']) &&
                    // isset($sessionDeals['allXODeals']) &&
                    // isset($sessionDeals['currentTaskDeals'])


                ) {


                    $this->currentBtxDeals  = $sessionDeals['currentTaskDeals'];

                    $this->currentBaseDeal = $sessionDeals['currentBaseDeal'];
                    $this->currentPresDeal = $sessionDeals['currentPresentationDeal'];
                    $this->currentColdDeal = $sessionDeals['currentXODeal'];


                    $this->relationBaseDeals = $sessionDeals['allBaseDeals'];
                    $this->relationCompanyUserPresDeals = $sessionDeals['allPresentationDeals']; //allPresDeal 
                    $this->relationFromBasePresDeals = $sessionDeals['basePresentationDeals'];
                    $this->relationColdDeals = $sessionDeals['allXODeals'];
                }
            }
        } else {
            $sessionKey = 'newtask_' . $domain  . '_' . $entityId;
            $sessionData = FullEventInitController::getSessionItem($sessionKey);

            if (empty($sessionData)) {
                $sessionData = ReportController::getDealsFromNewTaskInner(
                    $domain,
                    $this->hook,
                    $entityId,
                    $this->planResponsibleId,
                    'company'
                );
            }

            if (isset($sessionData['currentCompany']) && isset($sessionData['deals'])) {
                $this->currentBtxEntity  = $sessionData['currentCompany'];


                $sessionDeals = $sessionData['deals'];
            }
            if (isset($sessionDeals) && isset($sessionDeals['currentBaseDeals'])) {
                $this->currentBtxEntity  = $sessionData['currentCompany'];

                if (is_array($sessionDeals['currentBaseDeals']) && !empty($sessionDeals['currentBaseDeals'])) {
                    $this->currentBtxDeals  = [$sessionDeals['currentBaseDeals'][0]];
                    $this->currentBaseDeal = $sessionDeals['currentBaseDeals'][0];
                } else {

                    $this->currentBtxDeals  = [];
                }
            }
        }
        // Log::channel('telegram')->info('HOOK TMC SESSION GET', ['isSuccessSale' => $this->isSuccessSale]);

        if ($this->isSuccessSale) {
            if (!empty($data['sale'])) {
                if (!empty($data['sale']['relationSalePresDeal'])) {
                    $this->currentPresDeal = $data['sale']['relationSalePresDeal'];
                    array_push($this->currentBtxDeals,  $data['sale']['relationSalePresDeal']);
                }
            }
        }
        $sessionTMCDealKey = 'tmcInit_' . $domain . '_' . $this->planResponsibleId . '_' . $entityId;
        $sessionData = FullEventInitController::getSessionItem($sessionTMCDealKey);

        // Log::info('HOOK TMC SESSION GET', ['sessionData' => $sessionData]);

        if (isset($sessionData['tmcDeal'])) {
            $this->currentTMCDeal = $sessionData['tmcDeal'];
        }

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


        if (!empty($data['sale'])) {

            if (!empty($data['sale']['relationSalePresDeal'])) {

                $this->relationSalePresDeal = $data['sale']['relationSalePresDeal'];
            }
        }
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
            Log::info('HOOK TEST unplannedPresDeal', [
                'currentBaseDeal' => $this->currentBaseDeal,
                'currentPresDeal' => $this->currentPresDeal,
                'currentBtxDeals' => $this->currentBtxDeals,

            ]);
            if ($this->isDealFlow && $this->portalDealData) {
                // $currentDealsIds = $this->getDealFlow();
                $currentDealsIds = $this->getBatchDealFlow();
                
            }

            // $this->createTask($currentSmartId);
            if ($this->isExpired || $this->isPlanned) {
                $result = $this->taskFlow(null, $currentDealsIds['planDeals']);
            } else {
                $result = $this->workStatus;
            }
            $this->getEntityFlow();
            // sleep(1);


            $this->getListFlow();
            // $rand = mt_rand(600000, 1000000); // случайное число от 300000 до 900000 микросекунд (0.3 - 0.9 секунды)
            $rand = mt_rand(600000, 1000000); // случайное число от 300000 до 900000 микросекунд (0.3 - 0.9 секунды)
            usleep($rand);
            $this->getListPresentationFlow(
                $currentDealsIds
            );

            return APIOnlineController::getSuccess(['data' => ['result' => $result, 'presInitLink' => null]]);
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
    protected function getEntityFlow(
        $isDeal = false,
        $deal = null,
        $dealType = 'base',  //presentation, xo
        $baseDealId = null,
        $dealEventType = false //plan done unplanned fail
    ) {
        $currentReportEventType = $this->currentReportEventType;
        $currentPlanEventType = $this->currentPlanEventType;
        $isPresentationDone = $this->isPresentationDone;

        $currentBtxEntity = $this->currentBtxEntity;
        $entityType = $this->entityType;
        $entityId = $this->entityId;

        $portalEntityData = $this->portalCompanyData;


        $reportFields = [];
        $reportFields['manager_op'] = $this->planResponsibleId;
        $reportFields['op_work_status'] = '';
        $reportFields['op_prospects_type'] = 'op_prospects_good';
        $reportFields['op_result_status'] = '';
        $reportFields['op_noresult_reason'] = '';
        $reportFields['op_fail_reason'] = '';

        $reportFields['op_fail_comments'] = '';
        $reportFields['op_history'] = '';



        $currentPresCount = 0;
        $companyPresCount = 0;
        $dealPresCount = 0;
        if (!empty($this->currentTask)) {
            if (!empty($this->currentTask['presentation'])) {

                if (!empty($this->currentTask['presentation']['company'])) {
                    $companyPresCount = (int)$this->currentTask['presentation']['company'];
                }
                if (!empty($this->currentTask['presentation']['deal'])) {
                    $dealPresCount = (int)$this->currentTask['presentation']['deal'];
                }
            }
        }



        $currentPresCount =  $companyPresCount;
        if ($isDeal && !empty($deal) && !empty($deal['ID'])) {

            $currentPresCount =  $dealPresCount;
            $currentBtxEntity = $deal;
            $entityType = 'deal';
            $entityId =  $deal['ID'];
            $portalEntityData = $this->portalDealData;

            if ($dealType == 'presentation') {
                $reportFields['to_base_sales'] = $baseDealId;
                $currentPresCount = 0;
                if ($dealEventType == 'plan' || $dealEventType == 'fail') {
                    $currentPresCount = -1;
                }
            }
        }


        $currentPresComments = [];
        $currentFailComments = [];
        $currentMComments = [];
        if (isset($currentBtxEntity)) {
            if (!empty($currentBtxEntity['UF_CRM_PRES_COMMENTS'])) {
                $currentPresComments = $currentBtxEntity['UF_CRM_PRES_COMMENTS'];
            }

            if (!empty($currentBtxEntity['UF_CRM_OP_FAIL_COMMENTS'])) {
                $currentFailComments = $currentBtxEntity['UF_CRM_OP_FAIL_COMMENTS'];
            }

            if (!empty($currentBtxEntity['UF_CRM_OP_MHISTORY'])) {
                $currentMComments = $currentBtxEntity['UF_CRM_OP_MHISTORY'];
            }
        }


        //обнуляем дату следующей презентации и звонка - они будут аполнены только если реально что-то запланировано
        $reportFields['next_pres_plan_date'] = null;
        $reportFields['call_next_date'] = null;

        if ($currentReportEventType) {


            //general
            $reportFields['call_last_date'] = $this->nowDate;

            switch ($currentReportEventType) {
                case 'xo':
                    $reportFields['xo_date'] = null;

                    break;

                default:
                    # code...
                    break;
            }
        }
        // Log::channel('telegram')->info('HOOK entity flow', [
        //     'isPresentationDone' => $this->isPresentationDone
        // ]);
        //presentation done with unplanned
        if ($this->isPresentationDone) {



            $reportFields['last_pres_done_date'] = $this->nowDate;
            $reportFields['last_pres_done_responsible'] =  $this->planResponsibleId;
            $reportFields['pres_count'] = $currentPresCount + 1;

            if ($currentReportEventType !== 'presentation' || $this->isNew) {
                $reportFields['last_pres_plan_date'] = $this->nowDate; //когда запланировали последнюю през
                $reportFields['last_pres_plan_responsible'] = $this->planResponsibleId;
                $reportFields['next_pres_plan_date'] = $this->nowDate;  //дата на которую запланировали през

            }
            $reportFields['op_current_status'] = ' Презентация проведена';
            array_push($currentPresComments, $this->nowDate . ' Презентация проведена ' . $this->comment);
            array_push($currentMComments, $this->nowDate . ' Презентация проведена ' . $this->comment);
        }


        //plan
        $planFields = [];

        if ($this->isPlanned) {


            //general
            $reportFields['call_next_date'] = $this->planDeadline;
            $reportFields['call_next_name'] = $this->currentPlanEventName;
            $reportFields['xo_responsible'] = $this->planResponsibleId;
            $reportFields['xo_created'] = $this->planResponsibleId;
            $reportFields['op_current_status'] = 'Звонок запланирован в работе';

            // Log::channel('telegram')->info('TST', [
            //     'currentPlanEventType' => $currentPlanEventType,

            // ]);


            switch ($currentPlanEventType) {
                    // 0: {id: 1, code: "warm", name: "Звонок"}
                    // // 1: {id: 2, code: "presentation", name: "Презентация"}
                    // // 2: {id: 3, code: "hot", name: "Решение"}
                    // // 3: {id: 4, code: "moneyAwait", name: "Оплата"}
                case 'xo':
                    $reportFields['xo_date'] = $this->planDeadline;
                    $reportFields['xo_name'] = $this->currentPlanEventName;

                    break;
                case 'hot':
                    $reportFields['op_current_status'] = 'В решении: ' . $this->currentPlanEventName;
                    array_push($currentMComments, $this->nowDate . 'В решении: ' . $this->comment);

                    break;
                case 'moneyAwait':
                    $reportFields['op_current_status'] = 'Ждем оплаты: ' . $this->currentPlanEventName;
                    array_push($currentMComments, $this->nowDate . 'В оплате: ' . $this->comment);
                    break;


                case 'presentation':

                    $reportFields['last_pres_plan_date'] = $this->nowDate; //когда запланировали последнюю през
                    $reportFields['last_pres_plan_responsible'] = $this->planResponsibleId;
                    $reportFields['next_pres_plan_date'] = $this->planDeadline;  //дата на которую запланировали през
                    $reportFields['op_current_status'] = 'В работе: Презентация запланирована ' . $this->currentPlanEventName;
                    array_push($currentPresComments, $this->nowDate . ' Презентация запланирована ' . $this->currentPlanEventName);
                    array_push($currentMComments, $this->nowDate . ' Презентация запланирована ' . $this->currentPlanEventName);
                    break;
                default:
                    # code...
                    break;
            }
        } else {
            if ($this->workStatus['current']['code'] === 'fail') {
                $reportFields['op_current_status'] = 'Отказ';
                array_push($currentMComments, $this->nowDate . ' Отказ ' . $this->comment);



                $reportFields['op_fail_comments'] = $currentFailComments;
                if ($this->isPresentationDone) {
                    array_push($currentPresComments, $this->nowDate . ' Отказ после презентации ' . $this->currentTaskTitle . ' ' . $this->comment);
                } else {
                    if ($currentReportEventType === 'presentation') {

                        array_push($currentPresComments, $this->nowDate . ' Отказ: Презентация не состоялась ' . $this->currentTaskTitle . ' ' . $this->comment);
                    }
                }
            }

            if ($this->workStatus['current']['code'] === 'success') {
                $reportFields['op_current_status'] = 'Успех: продажа состоялась ' . $this->nowDate;
            }
        }
        if (!$this->isNew) {
            if ($this->resultStatus !== 'result') {
                if (!empty($this->noresultReason)) {
                    if (!empty($this->noresultReason['code'])) {

                        $reportFields['op_noresult_reason'] = $this->noresultReason['code'];
                    }
                }

                if ($this->workStatus['current']['code'] === 'inJob' || $this->workStatus['current']['code'] === 'setAside') {
                    if ($currentReportEventType === 'presentation') {

                        array_push($currentPresComments, $this->nowDate . ' Перенос: ' . $this->currentTaskTitle . ' ' . $this->comment);
                    }
                    array_push($currentMComments, $this->nowDate . ' Перенос: ' . $this->currentTaskTitle . ' ' . $this->comment);
                }

                // array_push($currentMComments, $this->nowDate . ' Нерезультативный. ' . $this->currentTaskTitle);
            } else {
                array_push($currentMComments, $this->nowDate . ' Результативный ' . $this->currentTaskTitle);
            }
        }



        // Log::channel('telegram')->info('TST', [
        //     'currentPresComments' => $currentPresComments,
        //     'currentFailComments' => $currentFailComments,
        // ]);
        // Log::channel('telegram')->info('HOOK TEST getWorkstatusFieldItemValue', [
        //     'failType' => $this->failType,
        //     'failReason' => $this->failReason,
        // ]);

        if (!empty($this->workStatus['current'])) {
            if (!empty($this->workStatus['current']['code'])) {
                $workStatusCode = $this->workStatus['current']['code'];


                if ($workStatusCode === 'fail') {  //если провал
                    if (!empty($this->failType)) {
                        if (!empty($this->failType['code'])) {

                            // $reportFields['op_prospects_type'] = $this->failType['code'];


                            if ($this->failType['code'] == 'failure') { //если тип провала - отказ
                                if (!empty($this->failReason)) {
                                    if (!empty($this->failReason['code'])) {

                                        $reportFields['op_fail_reason'] = $this->failReason['code'];
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }



        //закидываем сформированные комментарии
        // $reportFields['op_mhistory'] = $currentMComments;
        // if ($this->isPresentationDone || ($this->isPlanned && $currentPlanEventType == 'presentation')) {
        $reportFields['pres_comments'] = $currentPresComments;
        // }


        $entityService = new BitrixEntityFlowService();




        $entityService->flow(
            $this->portal,
            $currentBtxEntity,
            $portalEntityData,
            $this->hook,
            $entityType,
            $entityId,
            $this->currentPlanEventType, // xo warm presentation,
            'plan',  // plan done expired 
            $this->planCreatedId,
            $this->planResponsibleId,
            $this->planDeadline,
            $this->nowDate,
            $this->isPresentationDone,
            $this->isUnplannedPresentation,
            $this->workStatus['current']['code'],  // inJob setAside ...
            $this->resultStatus, //result | noresult ... new
            $this->failType,
            $this->failReason,
            $this->noresultReason,
            $this->currentReportEventType,
            $this->currentReportEventName,
            $this->currentPlanEventName,
            $this->comment,
            $reportFields
        );
    }


    // get deal relations flow


    //todo 
    //get clod report fields
    //get warm report fields
    //get presentation report fields
    //get other report fields
    //get new event report fields




    //get clod plan fields
    //get warm plan fields
    //get presentation plan fields
    //get in_progress plan fields
    //get money_await plan fields


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

        //сейчас есть
        // protected $currentBaseDeal;
        // protected $currentPresDeal;
        // protected $currentColdDeal;
        // protected $currentTMCDeal;

        // protected $relationBaseDeals;  //базовые сделки пользователь-компания
        // protected $relationCompanyUserPresDeals; //allPresDeals //през сделки пользователь-компания
        // protected $relationFromBasePresDeals;
        // protected $relationColdDeals;
        // protected $relationTMCDeals;



        // $currentBaseDeal - обновляется в любом случае если ее нет - создается
        // $currentPresDeal - обновляется если през - done или planEventType - pres
        // $currentColdDeal - обновляется если xo - done или planEventType - xo

        // в зависимости от условий сделка в итоге попадает либо в plan либо в report deals

        $reportDeals = [];
        $planDeals = [];
        $currentBtxDeals = $this->currentBtxDeals;


        if (empty($currentBtxDeals)) {   //если текущие сделки отсутствуют значит надо сначала создать базовую - чтобы нормально отработал поток
            $setNewDealData = [
                'COMPANY_ID' => $this->entityId,
                'CATEGORY_ID' => $this->btxDealBaseCategoryId,
                'ASSIGNED_BY_ID' => $this->planResponsibleId,
            ];
            $currentDealId = BitrixDealService::setDeal(
                $this->hook,
                $setNewDealData,

            );

            if (!empty($currentDealId)) {
                $rand = mt_rand(300000, 900000); // случайное число от 300000 до 900000 микросекунд (0.3 - 0.9 секунды)
                usleep($rand);
                $newBaseDeal = BitrixDealService::getDeal(
                    $this->hook,
                    ['id' => $currentDealId]


                );
                $this->currentBaseDeal = $newBaseDeal;
                $currentBtxDeals = [$newBaseDeal];
                $this->currentBtxDeals = [$newBaseDeal];
            }
        }


        $unplannedPresDeals = null;
        $newPresDeal = null;
        // report - закрывает сделки
        // plan - создаёт
        //todo report flow

        // if report type = xo | cold
        $currentReportStatus = 'done';

        // if ($this->currentReportEventType == 'xo') {

        if ($this->isFail) {
            //найти сделку хо -> закрыть в отказ , заполнить поля отказа по хо 
            $currentReportStatus = 'fail';
        } else if ($this->isSuccessSale) {
            //найти сделку хо -> закрыть в отказ , заполнить поля отказа по хо 
            $currentReportStatus = 'success';
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
                'Спонтанная от ' . $this->nowDate,
                'plan',  // plan done expired fail
                $this->planResponsibleId,
                true,
                '$fields',
                null // $relationSalePresDeal
            );

            // $isDeal = false, 
            // $deal = null, 
            // $dealType = 'base',  //presentation, xo
            // $baseDealId = null

            // Log::info('HOOK TEST unplannedPresDeal', [
            //     'currentBaseDeal' => $this->currentBaseDeal,


            // ]);
            if (!empty($this->currentBaseDeal)) {
                $this->getEntityFlow(
                    true,
                    $unplannedPresDeal,
                    'presentation',
                    $this->currentBaseDeal['ID'],
                    'unplanned'
                );
            }




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
                    $flowResult = BitrixDealFlowService::flow(  // закрывает сделку  - презентация обновляет базовую в соответствии с проведенной през
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
                        '$fields',
                        null // $relationSalePresDeal
                    );
                    $unplannedPresDeals = $flowResult['dealIds'];




                    // Log::channel('telegram')->info('HOOK TEST CURRENTENTITY', [
                    //     'unplannedPresDeals' => $unplannedPresDeals,


                    // ]);
                    // Log::info('HOOK TEST CURRENTENTITY', [
                    //     'unplannedPresDeals' => $unplannedPresDeals,


                    // ]);
                    foreach ($this->currentBtxDeals as $cbtxdeal) {
                        if ($cbtxdeal['ID'] !== $unplannedPresDealId) {
                            $rand = mt_rand(600000, 1000000); // случайное число от 300000 до 900000 микросекунд (0.3 - 0.9 секунды)
                            usleep($rand);
                            $updtdbtxdeal = BitrixDealService::getDeal(
                                $this->hook,
                                ['id' => $cbtxdeal['ID']]
                            );
                            if (!empty($updtdbtxdeal)) {

                                $cbtxdeal = $updtdbtxdeal;
                            }
                            array_push($currentBtxDeals, $cbtxdeal);
                        }
                    }
                }
            }
        }
        $rand = mt_rand(600000, 1000000); // случайное число от 300000 до 900000 микросекунд (0.3 - 0.9 секунды)
        usleep($rand);


        //если был unplanned а потом plan ->
        //если warm plan а report был xo 
        // - то нужна обновленная стадия в базовой битрикс сделке что не пыталось повысить
        // с xo в warm так как уже на самом деле pres 
        // если plan pres -> планируется новая презентация и поэтому в  
        // $this->currentBtxDeals должна отсутствовать сделка презентации созданная при unplanned, 
        // которая пушится туда  при unplanned - чтобы были обработаны базовая сделка 
        // в соответствии с проведенной през
        // при этом у основной сделки должна быть обновлена стадия - например на през если была unplanned
        // Log::info('HOOK TEST currentBtxDeals', [
        //     'currentBtxDeals' => $currentBtxDeals,
        //     'this currentBtxDeals' => $this->currentBtxDeals,


        // ]);


        $flowResult = BitrixDealFlowService::flow(  // редактирует сделки отчетности из currentTask основную и если есть xo
            $this->hook,
            $currentBtxDeals,
            $this->portalDealData,
            $this->currentDepartamentType,
            $this->entityType,
            $this->entityId,
            $this->currentReportEventType, // xo warm presentation, 
            $this->currentReportEventName,
            $this->currentPlanEventName,
            $currentReportStatus,  // plan done expired fail success
            $this->planResponsibleId,
            $this->isResult,
            '$fields',
            $this->relationSalePresDeal
        );
        $reportDeals = $flowResult['dealIds'];


        if (!empty($this->currentTMCDeal) && $this->resultStatus === 'result' && $this->currentReportEventType === 'presentation') {

            BitrixDealFlowService::flow(  // редактирует сделки отчетности из currentTask основную и если есть xo
                $this->hook,
                [$this->currentTMCDeal],
                $this->portalDealData,
                'tmc',
                $this->entityType,
                $this->entityId,
                $this->currentReportEventType, // xo warm presentation, 
                $this->currentReportEventName,
                $this->currentPlanEventName,
                'done', //$currentReportStatus,  // plan done expired fail success
                $this->planResponsibleId,
                $this->isResult,
                '$fields',
                $this->relationSalePresDeal
            );
            //обновляет сделку тмц в успех если есть tmc deal и если през состоялась
        }

        //todo plan flow

        // if ($this->currentPlanEventType == 'warm') {
        //     // найти или создать сделку base не sucess стадия теплый прозвон


        // }
        // if plan type = xo | cold

        //если запланирован
        //xo - создать или обновить ХО & Основная
        //warm | money_await | in_progress - создать или обновить  Основная
        //presentation - создать или обновить presentation & Основная

        if (!empty($this->currentBaseDeal)) {
            $rand = mt_rand(300000, 700000); // случайное число от 300000 до 900000 микросекунд (0.3 - 0.9 секунды)
            usleep($rand);
            $this->getEntityFlow(
                true,
                $this->currentBaseDeal,
                'base',
                $this->currentBaseDeal['ID'],
                'unplanned'
            );
        }
        // Log::info('HOOK TEST currentBtxDeals', [
        //     'currentBtxDeals' => $currentBtxDeals,
        //     '$this->currentPresDeal' => $this->currentPresDeal,


        // ]);
        if (!empty($this->currentPresDeal)) {  //report pres deal
            $rand = mt_rand(300000, 700000); // случайное число от 300000 до 900000 микросекунд (0.3 - 0.9 секунды)
            usleep($rand);
            $this->getEntityFlow(
                true,
                $this->currentPresDeal,
                'presentation',
                $this->currentBaseDeal['ID'],
                'done'
            );
        }


        if ($this->isPlanned) {
            $currentBtxDeals = BitrixDealFlowService::getBaseDealFromCurrentBtxDeals(
                $this->portalDealData,
                $currentBtxDeals
            );

            $flowResult =  BitrixDealFlowService::flow( //создает сделку
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
                '$fields',
                null, // $relationSalePresDeal
            );
            $planDeals = $flowResult['dealIds'];
            $newPresDeal = $flowResult['newPresDeal'];



            if (!empty($this->currentTMCDeal) && $this->currentPlanEventType == 'presentation') {
                BitrixDealFlowService::tmcPresentationRelation(
                    $this->hook,
                    $this->portalDealData,
                    $this->currentBaseDeal,
                    $newPresDeal,
                    $this->currentTMCDeal['ID']
                );
            }
        }

        // Log::info('HOOK TEST currentBtxDeals', [
        //     'newPresDeal' => $newPresDeal,



        // ]);
        if (!empty($newPresDeal)) {  //plan pres deal
            $rand = mt_rand(200000, 700000); // случайное число от 300000 до 900000 микросекунд (0.3 - 0.9 секунды)
            usleep($rand);
            $this->getEntityFlow(
                true,
                $newPresDeal,
                'presentation',
                $this->currentBaseDeal['ID'],
                'plan'
            );
        }
        // Log::channel('telegram')->info('presentationBtxList', [
        //     'reportDeals' => $reportDeals,
        //     'planDeals' => $planDeals,
        //     // 'failReason' => $failReason,
        //     // 'failType' => $failType,

        // ]);

        return [
            'reportDeals' => $reportDeals,
            'planDeals' => $planDeals,
            'unplannedPresDeals' => $unplannedPresDeals,
        ];
    }


    protected function getBatchDealFlow()
    {

        // должен собрать batch commands
        // отправить send batch
        // из резултатов вернуть объект с массивами созданных и обновленных сделок
        // если при начале функции нет currentBtxDeals - сначала создается она

        //сейчас есть
        // protected $currentBaseDeal;
        // protected $currentPresDeal;
        // protected $currentColdDeal;
        // protected $currentTMCDeal;

        // protected $relationBaseDeals;  //базовые сделки пользователь-компания
        // protected $relationCompanyUserPresDeals; //allPresDeals //през сделки пользователь-компания
        // protected $relationFromBasePresDeals;
        // protected $relationColdDeals;
        // protected $relationTMCDeals;



        // $currentBaseDeal - обновляется в любом случае если ее нет - создается
        // $currentPresDeal - обновляется если през - done или planEventType - pres
        // $currentColdDeal - обновляется если xo - done или planEventType - xo

        // в зависимости от условий сделка в итоге попадает либо в plan либо в report deals

        $reportDeals = [];
        $planDeals = [];
        $currentBtxDeals = $this->currentBtxDeals;
        $batchCommands = [];

        if (empty($currentBtxDeals)) {   //если текущие сделки отсутствуют значит надо сначала создать базовую - чтобы нормально отработал поток
            $setNewDealData = [
                'COMPANY_ID' => $this->entityId,
                'CATEGORY_ID' => $this->btxDealBaseCategoryId,
                'ASSIGNED_BY_ID' => $this->planResponsibleId,
            ];
            $currentDealId = BitrixDealService::setDeal(
                $this->hook,
                $setNewDealData,

            );

            if (!empty($currentDealId) && empty($this->currentBaseDeal)) {
                $rand = mt_rand(300000, 900000); // случайное число от 300000 до 900000 микросекунд (0.3 - 0.9 секунды)
                usleep($rand);
                $newBaseDeal = BitrixDealService::getDeal(
                    $this->hook,
                    ['id' => $currentDealId]


                );
                $this->currentBaseDeal = $newBaseDeal;
                $currentBtxDeals = [$newBaseDeal];
                $this->currentBtxDeals = [$newBaseDeal];
            }
        }


        $unplannedPresDeals = null;
        $newPresDeal = null;
        // report - закрывает сделки
        // plan - создаёт
        //todo report flow

        // if report type = xo | cold
        $currentReportStatus = 'done';

        // if ($this->currentReportEventType == 'xo') {

        if ($this->isFail) {
            //найти сделку хо -> закрыть в отказ , заполнить поля отказа по хо 
            $currentReportStatus = 'fail';
        } else if ($this->isSuccessSale) {
            //найти сделку хо -> закрыть в отказ , заполнить поля отказа по хо 
            $currentReportStatus = 'success';
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
                'Спонтанная от ' . $this->nowDate,
                'plan',  // plan done expired fail
                $this->planResponsibleId,
                true,
                '$fields',
                null // $relationSalePresDeal
            );

            // $isDeal = false, 
            // $deal = null, 
            // $dealType = 'base',  //presentation, xo
            // $baseDealId = null

            // Log::info('HOOK TEST unplannedPresDeal', [
            //     'currentBaseDeal' => $this->currentBaseDeal,


            // ]);
            if (!empty($this->currentBaseDeal)) {
                $this->getEntityFlow(
                    true,
                    $unplannedPresDeal,
                    'presentation',
                    $this->currentBaseDeal['ID'],
                    'unplanned'
                );
            }




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
                    $flowResult = BitrixDealBatchFlowService::batchFlow(  // закрывает сделку  - презентация обновляет базовую в соответствии с проведенной през
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
                        '$fields',
                        null, // $relationSalePresDeal
                        $batchCommands,
                        'unpres'
                    );
                    // $unplannedPresDeals = $flowResult['dealIds'];
                    $batchCommands = $flowResult['commands'];



                    // Log::channel('telegram')->info('HOOK TEST CURRENTENTITY', [
                    //     'unplannedPresDeals' => $unplannedPresDeals,


                    // ]);
                    // Log::info('HOOK TEST CURRENTENTITY', [
                    //     'unplannedPresDeals' => $unplannedPresDeals,


                    // ]);
                    foreach ($this->currentBtxDeals as $cbtxdeal) {
                        if ($cbtxdeal['ID'] !== $unplannedPresDealId) {
                            $rand = mt_rand(200000, 1000000); // случайное число от 300000 до 900000 микросекунд (0.3 - 0.9 секунды)
                            usleep($rand);
                            $updtdbtxdeal = BitrixDealService::getDeal(
                                $this->hook,
                                ['id' => $cbtxdeal['ID']]
                            );
                            if (!empty($updtdbtxdeal)) {

                                $cbtxdeal = $updtdbtxdeal;
                            }
                            array_push($currentBtxDeals, $cbtxdeal);
                        }
                    }
                }
            }
        }
        // $rand = mt_rand(600000, 1000000); // случайное число от 300000 до 900000 микросекунд (0.3 - 0.9 секунды)
        // usleep($rand);


        //если был unplanned а потом plan ->
        //если warm plan а report был xo 
        // - то нужна обновленная стадия в базовой битрикс сделке что не пыталось повысить
        // с xo в warm так как уже на самом деле pres 
        // если plan pres -> планируется новая презентация и поэтому в  
        // $this->currentBtxDeals должна отсутствовать сделка презентации созданная при unplanned, 
        // которая пушится туда  при unplanned - чтобы были обработаны базовая сделка 
        // в соответствии с проведенной през
        // при этом у основной сделки должна быть обновлена стадия - например на през если была unplanned
        // Log::info('HOOK TEST currentBtxDeals', [
        //     'currentBtxDeals' => $currentBtxDeals,
        //     'this currentBtxDeals' => $this->currentBtxDeals,


        // ]);

        Log::info('HOOK BATCH batchFlow report DEAL', ['report currentBtxDeals' => $currentBtxDeals]);
        Log::channel('telegram')->info('HOOK BATCH batchFlow', ['currentBtxDeals' => $currentBtxDeals]);
        $flowResult = BitrixDealBatchFlowService::batchFlow(  // редактирует сделки отчетности из currentTask основную и если есть xo
            $this->hook,
            $currentBtxDeals,
            $this->portalDealData,
            $this->currentDepartamentType,
            $this->entityType,
            $this->entityId,
            $this->currentReportEventType, // xo warm presentation, 
            $this->currentReportEventName,
            $this->currentPlanEventName,
            $currentReportStatus,  // plan done expired fail success
            $this->planResponsibleId,
            $this->isResult,
            '$fields',
            $this->relationSalePresDeal,
            $batchCommands,
            'report'

        );
        // $reportDeals = $flowResult['dealIds'];
        $batchCommands = $flowResult['commands'];
        Log::info('HOOK BATCH batchFlow report DEAL', ['report batchCommands' => $batchCommands]);
        Log::channel('telegram')->info('HOOK BATCH batchFlow', ['batchCommands' => $batchCommands]);


        if (!empty($this->currentTMCDeal) && $this->resultStatus === 'result' && $this->currentReportEventType === 'presentation') {

            $tmcflowResult =  BitrixDealBatchFlowService::batchFlow(  // редактирует сделки отчетности из currentTask основную и если есть xo
                $this->hook,
                [$this->currentTMCDeal],
                $this->portalDealData,
                'tmc',
                $this->entityType,
                $this->entityId,
                $this->currentReportEventType, // xo warm presentation, 
                $this->currentReportEventName,
                $this->currentPlanEventName,
                'done', //$currentReportStatus,  // plan done expired fail success
                $this->planResponsibleId,
                $this->isResult,
                '$fields',
                $this->relationSalePresDeal,
                $batchCommands,
                'tmc_report'
            );
            $batchCommands = $tmcflowResult['commands'];
            //обновляет сделку тмц в успех если есть tmc deal и если през состоялась
        }

        //todo plan flow

        // if ($this->currentPlanEventType == 'warm') {
        //     // найти или создать сделку base не sucess стадия теплый прозвон


        // }
        // if plan type = xo | cold

        //если запланирован
        //xo - создать или обновить ХО & Основная
        //warm | money_await | in_progress - создать или обновить  Основная
        //presentation - создать или обновить presentation & Основная

        if (!empty($this->currentBaseDeal)) {
            $rand = mt_rand(300000, 700000); // случайное число от 300000 до 900000 микросекунд (0.3 - 0.9 секунды)
            usleep($rand);
            $this->getEntityFlow(
                true,
                $this->currentBaseDeal,
                'base',
                $this->currentBaseDeal['ID'],
                'unplanned'
            );
        }
        // Log::info('HOOK TEST currentBtxDeals', [
        //     'currentBtxDeals' => $currentBtxDeals,
        //     '$this->currentPresDeal' => $this->currentPresDeal,


        // ]);
        if (!empty($this->currentPresDeal)) {  //report pres deal
            $rand = mt_rand(300000, 700000); // случайное число от 300000 до 900000 микросекунд (0.3 - 0.9 секунды)
            usleep($rand);
            $this->getEntityFlow(
                true,
                $this->currentPresDeal,
                'presentation',
                $this->currentBaseDeal['ID'],
                'done'
            );
        }


        if ($this->isPlanned) {
            $currentBtxDeals = BitrixDealFlowService::getBaseDealFromCurrentBtxDeals(
                $this->portalDealData,
                $currentBtxDeals
            );

            $flowResult =   BitrixDealBatchFlowService::batchFlow( //создает сделку
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
                '$fields',
                null, // $relationSalePresDeal
                $batchCommands, //тут я не эжу batch command а только созданная newpresdeal интересует, 
                // чтобы связать ее с тмц сделкой если таковая имелась
                // обновить поля в карточке презентационной сделки   
                'plan'
            );
            // $planDeals = $flowResult['dealIds'];
            $batchCommands = $flowResult['commands'];


            // WITHOUT NEW
            $newPresDeal = $flowResult['newPresDeal'];

            // Новая сделка созданная для презентации если есть тмц сделка
            // новая сделка презентации нужна только здесь
            //поэтому в batch commands - results будет 'new_pres_deal_id'
            // и в этот момент я ее отдельным get возьму

            if (!empty($this->currentTMCDeal) && $this->currentPlanEventType == 'presentation') {
                BitrixDealFlowService::tmcPresentationRelation(
                    $this->hook,
                    $this->portalDealData,
                    $this->currentBaseDeal,
                    $newPresDeal,
                    $this->currentTMCDeal['ID']
                );
            }
        }

        // Log::info('HOOK TEST currentBtxDeals', [
        //     'newPresDeal' => $newPresDeal,



        // ]);
        if (!empty($newPresDeal)) {  //plan pres deal
            $rand = mt_rand(200000, 700000); // случайное число от 300000 до 900000 микросекунд (0.3 - 0.9 секунды)
            usleep($rand);
            $this->getEntityFlow(
                true,
                $newPresDeal,
                'presentation',
                $this->currentBaseDeal['ID'],
                'plan'
            );
        }
        // Log::channel('telegram')->info('presentationBtxList', [
        //     'reportDeals' => $reportDeals,
        //     'planDeals' => $planDeals,
        //     // 'failReason' => $failReason,
        //     // 'failType' => $failType,

        // ]);
        Log::info('HOOK BATCH batchFlow DEAL', ['batchCommands' => $batchCommands]);
        Log::channel('telegram')->info('HOOK BATCH batchFlow', ['batchCommands' => $batchCommands]);


        $batchService =  new BitrixBatchService($this->hook);
        $result = $batchService->sendGeneralBatchRequest($batchCommands);
        Log::info('HOOK BATCH batchFlow', ['result' => $result]);
        Log::channel('telegram')->info('HOOK BATCH batchFlow', ['result' => $result]);
        return [
            'reportDeals' => $reportDeals,
            'planDeals' => $planDeals,
            'unplannedPresDeals' => $unplannedPresDeals,
        ];
    }




    //tasks for complete


    protected function taskFlow(
        $currentSmartItemId,
        $currentDealsIds

    ) {


        $companyId  = null;
        $leadId  = null;
        $currentTaskId = null;
        $createdTask = null;
        try {
            // Log::channel('telegram')->error('APRIL_HOOK', $this->portal);

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

        $currentDealIds = [];
        $currentBaseDealId = null;

        if (!empty($this->currentBtxDeals)) {

            foreach ($this->currentBtxDeals as $currentBtxDeals) {
                if (isset($currentBtxDeals['ID'])) {

                    array_push($currentDealIds, $currentBtxDeals['ID']);
                }
            }
        }

        if (!empty($this->currentBaseDeal)) {

            if (!empty($this->currentBaseDeal['ID'])) {
                $currentBaseDealId = $this->currentBaseDeal['ID'];
            }
        }

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
        }

        $planComment = $planComment . ' ' . $planEventTypeName . ' ' . $this->comment;
        if (!$this->isNew) { //если новая то не отчитываемся
            // покачто
            // todo сделать чтобы в новой задаче можно было отчитаться что было




            if (!$this->isExpired) {  // если не перенос, то отчитываемся по прошедшему событию

                $reportAction = 'done';
                if ($this->resultStatus !== 'result') {
                    $reportAction = 'nodone';
                }

                if ($reportEventType !== 'presentation') {

                    //если текущий не презентация
                    BtxCreateListItemJob::dispatch(  //report - отчет по текущему событию
                        $this->hook,
                        $this->bitrixLists,
                        $reportEventType,
                        $reportEventTypeName,
                        $reportAction,
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
                        $this->failType,
                        $currentDealIds,
                        $currentBaseDealId

                    )->onQueue('low-priority');
                }

                //если была проведена презентация - не важно какое текущее report event
            }
        }
        // Log::channel('telegram')->info('HOOK TST', [
        //     'isPresentationDone' => $this->isPresentationDone
        // ]);

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
                    $this->failType,
                    $currentDealIds,
                    $currentBaseDealId


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
                $this->failType,
                $currentDealIds,
                $currentBaseDealId

            )->onQueue('low-priority');
        }



        if (!$this->isSuccessSale && !$this->isFail) {
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
                    $this->failType,
                    $currentDealIds,
                    $currentBaseDealId

                )->onQueue('low-priority');
            }
        }


        if ($this->isSuccessSale || $this->isFail) {
            BtxSuccessListItemJob::dispatch(  //запись о планировании и переносе
                $this->hook,
                $this->bitrixLists,
                $planEventType,
                $planEventTypeName,
                'done',
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
                $this->failType,
                $currentDealIds,
                $currentBaseDealId

            )->onQueue('low-priority');
        }
    }
    protected function getListPresentationFlow(
        $planPresDealIds
    ) {
        $currentTask = $this->currentTask;
        $currentDealIds = $planPresDealIds['planDeals'];
        $unplannedPresDealsIds = $planPresDealIds['unplannedPresDeals'];

        // presentation list flow запускается когда
        // планируется презентация или unplunned тогда для связи со сделками берется $planPresDealIds
        // отчитываются о презентации презентация или unplunned тогда для связи со сделками берется $currentTask


        // Дата начала	presentation	datetime	pres_event_date
        // Автор Заявки	presentation	employee	pres_plan_author
        // Планируемая Дата презентации	presentation	datetime	pres_plan_date
        // Дата переноса	presentation	datetime	pres_pound_date
        // Дата проведения презентации	presentation	datetime	pres_done_date
        // Комментарий к заявке	presentation	string	pres_plan_comment
        // Контактные данные	presentation	multiple	pres_plan_contacts
        // Ответственный	presentation	employee	pres_responsible
        // Статус Заявки	presentation	enumeration	pres_init_status
        // Заявка Принята/Отклонена	presentation	datetime	pres_init_status_date
        // Комментарий к непринятой заявке	presentation	string	pres_init_fail_comment
        // Комментарий после презентации	presentation	string	pres_done_comment
        // Результативность	presentation	enumeration	pres_result_status
        // Статус Работы	presentation	enumeration	pres_work_status
        // Неперспективная 	presentation	enumeration	pres_fail_type
        // ОП Причина Отказа	presentation	enumeration	pres_fail_reason
        // CRM	presentation	crm	pres_crm
        // Презентация Сделка	presentation	crm	pres_crm_deal
        // ТМЦ Сделка	presentation	crm	pres_crm_tmc_deal
        // Основная Сделка	presentation	crm	pres_crm_base_deal
        // Связи	presentation	crm	pres_crm_other
        // Контакт	presentation	crm	pres_crm_contacts

        // для планирования plan
        // дата
        // автор заявки
        // ответственный
        // планируемая дата презентации
        // название 
        // комментарий к заявке
        // crm - компания и plan deals
        //  по идее связать с tmc deal



        // для отчетности report
        // результативность да или нет, тип нерезультативности
        // статус работы в работе, отказ, причина
        // если перенос - отображать в комментариях после през строками
        // текущая дата - дата последнего изменения 
        // если была проведена презентация обновляется поле дата проведения презентации
        // все изменения записываются в множественное поле коммент после презентации
        // Log::channel('telegram')->error('APRIL_HOOK', [

        //     'currentPlanEventType' => $this->currentPlanEventType,
        //     'isPlanned' => $this->isPlanned,
        //     'isExpired' => $this->isExpired,

        // ]);

        if (  //планируется презентация без переносов
            $this->currentPlanEventType == 'presentation' &&
            $this->isPlanned && !$this->isExpired
        ) { //plan
            $eventType = 'plan';

            BitrixListPresentationFlowService::getListPresentationPlanFlow(
                $this->hook,
                $this->bitrixLists,
                $currentDealIds,
                $this->nowDate,
                $eventType,
                $this->planDeadline,
                $this->planCreatedId,
                $this->planResponsibleId,
                $this->entityId,
                $this->comment,
                $this->currentPlanEventName,
                $this->workStatus['current'],
                $this->resultStatus, // result noresult expired,
                // $this->noresultReason,
                // $this->failReason,
                // $this->failType


            );
        }

        sleep(1);
        if ($this->currentReportEventType == 'presentation') {  //report

            //если текущее событие по которому отчитываются - презентация

            $currentDealIds = [];
            // if (!empty($currentTask)) {
            //     if (!empty($currentTask['ufCrmTask'])) {
            //         $array = $currentTask['ufCrmTask'];
            //         foreach ($array as $item) {
            //             // Проверяем, начинается ли элемент с "D_"
            //             if (strpos($item, "D_") === 0) {
            //                 // Добавляем ID в массив, удаляя первые два символа "D_"
            //                 $currentDealIds[] = substr($item, 2);
            //             }
            //         }
            //     }
            // }

            if (!empty($this->currentBtxDeals)) {

                $array = $currentTask['ufCrmTask'];
                foreach ($this->currentBtxDeals as $deal) {
                    // Проверяем, начинается ли элемент с "D_"
                    // Добавляем ID в массив, удаляя первые два символа "D_"
                    $currentDealIds[] = $deal['ID'];
                }
            }
            $eventType = 'report';

            if (
                $this->isExpired ////текущую назначенную презентацию переносят
                || ( // //текущая назначенная презентация не состоялась

                    $this->resultStatus !== 'result'
                    && $this->isFail && !$this->isPresentationDone
                )
            ) {

                // $reportStatus = 'pound';
                // $eventAction = 'expired';
                //report
                BitrixListPresentationFlowService::getListPresentationReportFlow(
                    $this->hook,
                    $this->bitrixLists,
                    $currentDealIds,
                    // $reportStatus,
                    $this->isPresentationDone,

                    $this->nowDate,
                    $eventType,
                    $this->isExpired,
                    $this->planDeadline,
                    $this->planCreatedId,
                    $this->planResponsibleId,
                    $this->entityId,
                    $this->comment,
                    $this->currentPlanEventName,
                    $this->workStatus['current'],
                    $this->resultStatus, // result noresult expired,
                    $this->noresultReason,
                    $this->failReason,
                    $this->failType


                );
            }
        }

        if ($this->isPresentationDone) { //unplanned | planned


            if ($this->currentReportEventType !== 'presentation') {
                $currentDealIds =  $unplannedPresDealsIds;
                // если unplanned то у следующих действий додлжны быть айди 
                // соответствующих сделок
                // если текущее событие не през - значит uplanned
                // занчит сначала планируем
                // Log::channel('telegram')->info('presentationBtxList', [
                //     'currentDealIds' => $currentDealIds,


                // ]);
                BitrixListPresentationFlowService::getListPresentationPlanFlow(
                    $this->hook,
                    $this->bitrixLists,
                    $currentDealIds, //передаем айди основной и уже закрытой през сделки
                    $this->nowDate,
                    'plan',
                    $this->planDeadline,
                    $this->planCreatedId,
                    $this->planResponsibleId,
                    $this->entityId,
                    $this->comment,
                    $this->currentPlanEventName,
                    $this->workStatus['current'],
                    'result', // result noresult expired,
                    // $this->noresultReason,
                    // $this->failReason,
                    // $this->failType


                );
            }
            sleep(1);

            // если была проведена презентация вне зависимости от текущего события
            BitrixListPresentationFlowService::getListPresentationReportFlow(
                $this->hook,
                $this->bitrixLists,
                $currentDealIds, //planDeals || unplannedDeals если през была незапланированной
                // $reportStatus,
                $this->isPresentationDone,

                $this->nowDate,
                'report',
                $this->isExpired,
                $this->planDeadline,
                $this->planCreatedId,
                $this->planResponsibleId,
                $this->entityId,
                $this->comment,
                $this->currentPlanEventName,
                $this->workStatus['current'],
                $this->resultStatus, // result noresult expired,
                $this->noresultReason,
                $this->failReason,
                $this->failType


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