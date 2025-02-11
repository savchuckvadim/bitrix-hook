<?php

namespace App\Services\FullEventReport;

use App\Http\Controllers\APIOnlineController;
use App\Http\Controllers\Front\EventCalling\FullEventInitController;
use App\Http\Controllers\Front\EventCalling\ReportController;
use App\Http\Controllers\PortalController;
use App\Jobs\BtxCreateListItemJob;
use App\Jobs\BtxSuccessListItemJob;
use App\Services\BitrixTaskService;
use App\Services\FullEventReport\EventReport\EventReportPostFailService;
use App\Services\FullEventReport\EventReport\EventReportRelationLeadService;
use App\Services\General\BitrixBatchService;
use App\Services\General\BitrixDealService;
use App\Services\General\BitrixDepartamentService;
use App\Services\General\BitrixTimeLineService;
use App\Services\HookFlow\BitrixDealBatchFlowService;
use App\Services\HookFlow\BitrixDealFlowService;
use App\Services\HookFlow\BitrixEntityBatchFlowService;
use App\Services\HookFlow\BitrixEntityFlowService;
use App\Services\HookFlow\BitrixListFlowService;
use App\Services\HookFlow\BitrixListPresentationFlowService;
use Carbon\Carbon;
use DateTime;
use DateTimeZone;
use Illuminate\Console\View\Components\Task;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Log;
use IntlChar;

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
    protected $isPlanActive = true;
    protected $currentPlanEventType;

    // 0: {id: 1, code: "warm", name: "Звонок"}
    // // 1: {id: 2, code: "presentation", name: "Презентация"}
    // // 2: {id: 3, code: "hot", name: "Решение"}
    // // 3: {id: 4, code: "moneyAwait", name: "Оплата"}

    protected $currentPlanEventTypeName;
    protected $currentPlanEventName; // name 

    protected $planCreatedId;
    protected $planResponsibleId;
    protected $planTmcId;
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
    protected $currentTMCDealFromCurrentPres;

    protected $relationBaseDeals;
    protected $relationCompanyUserPresDeals; //allPresDeals
    protected $relationFromBasePresDeals;
    protected $relationColdDeals;
    protected $relationTMCDeals;


    protected $btxDealBaseCategoryId;
    protected $btxDealPresCategoryId;


    protected $planContact;
    protected $planContactId;
    protected $reportContact;
    protected $reportContactId;

    protected $relationLead = null;
    protected $postFail;


    // {
    //     name: 
    //     pnone:
    //     email:
    //     current:
    //     isNeedUpdate:
    //     isNeedCreate:
    // }

    public function __construct(

        $data,

    ) {

        // Форматируем дату и время в нужный формат



        $domain = $data['domain'];
        $this->domain = $domain;

        if (isset($data['fail'])) {
            if (!empty($data['fail'])) {
                $this->postFail = $data['fail'];
            }
        }

        if (isset($data['lead'])) {
            if (!empty($data['lead'])) {
                $this->relationLead = $data['lead'];
            }
        }
        date_default_timezone_set('Europe/Moscow');

        if ($domain == 'gsirk.bitrix24.ru') {
            date_default_timezone_set('Asia/Irkutsk');
        } else if ($domain == 'alfacentr.bitrix24.ru') {
            date_default_timezone_set('Asia/Novosibirsk');
        }
        // $nowDate = new DateTime();
        $nowDate = new DateTime("now", new DateTimeZone(date_default_timezone_get()));

        $this->nowDate = $nowDate->format('d.m.Y H:i:s');


        // if ($domain == 'gsirk.bitrix24.ru' || $domain == 'april-dev.bitrix24.ru' || $domain == 'april-garant.bitrix24.ru') {
        if (isset($data['plan']['isActive'])) {
            $this->isPlanActive = $data['plan']['isActive'];
        }
        // }


        $portal = PortalController::getPortal($domain);
        $portal = $portal['data'];
        $this->portal = $portal;


        $placement = $data['placement'];

        $entityType = null;
        $entityId = null;

        // if (isset($data['contact'])) {
        //     if (!empty($data['contact'])) {
        //         $this->planContact = $data['contact'];
        //     }
        // }

        if (!empty($data['plan'])) {
            if (!empty($data['plan']['contact'])) {
                $this->planContact = $data['plan']['contact'];
                if (!empty($data['plan']['contact']['ID'])) {
                    $this->planContactId = $data['plan']['contact']['ID'];
                }
            }
        }
        if (!empty($data['report'])) {
            if (!empty($data['report']['contact'])) {
                $this->reportContact = $data['report']['contact'];
                if (!empty($data['report']['contact']['ID'])) {
                    $this->reportContactId = $data['report']['contact']['ID'];
                }
            }
        }


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

                if (!empty($data['currentTask']['eventType'])) {
                    $this->currentReportEventName = 'Звонок';
                }

                switch ($data['currentTask']['eventType']) {
                    case 'xo':
                    case 'cold':
                        $this->currentReportEventName = 'Холодный звонок';
                        break;
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

        if (
            $data['report']['resultStatus'] !== 'result' &&
            $data['report']['resultStatus'] !== 'new' &&
            $data['plan']['isPlanned'] &&
            $this->isPlanActive

        ) {
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
        $this->isPlanned = $data['plan']['isPlanned'] && !empty($this->isPlanActive);


        if (
            !empty($data['plan']['isPlanned']) &&
            !empty($this->isPlanActive) &&
            !empty($data['plan']['type']) &&
            !empty($data['plan']['type']['current']) &&
            !empty($data['plan']['type']['current']['code'])

        ) {
            $this->currentPlanEventType = $data['plan']['type']['current']['code'];
            $this->currentPlanEventTypeName = $data['plan']['type']['current']['name'];
            $this->currentPlanEventName = $data['plan']['name'];

            if ($this->currentPlanEventType) {
                if ($this->currentPlanEventType === 'presentation' || $this->currentPlanEventType === 'pres') {
                    $this->currentPlanEventTypeName = '⚡' . ' ' . $this->currentPlanEventTypeName;
                }
                if (
                    $this->currentPlanEventType === 'hot' || 
                    $this->currentPlanEventType === 'inProgress' || 
                    $this->currentPlanEventType === 'in_progress'
                    
                    ) {
                    $this->currentPlanEventTypeName =  '🔥' . ' ' .$this->currentPlanEventTypeName;
                }
                if (
                    $this->currentPlanEventType === 'money' || 
                    $this->currentPlanEventType === 'moneyAwait' || 
                    $this->currentPlanEventType === 'money_await'
                    
                    ) {
                    $this->currentPlanEventTypeName = '💎' . ' ' . $this->currentPlanEventTypeName;
                }
                


            }
        };

        if (!empty($data['plan']['createdBy']) && !empty($data['plan']['createdBy']['ID'])) {
            $this->planCreatedId = $data['plan']['createdBy']['ID'];
        };

        if (!empty($data['plan']['responsibility']) && !empty($data['plan']['responsibility']['ID'])) {
            $this->planResponsibleId = $data['plan']['responsibility']['ID'];
        };

        if (isset($data['plan']['tmc'])) {
            if (!empty($data['plan']['tmc']) && !empty($data['plan']['tmc']['ID'])) {
                $this->planTmcId = $data['plan']['tmc']['ID'];
            };
        };


        if (!empty($data['plan']['deadline'])) {
            $this->planDeadline = $data['plan']['deadline'];
        };


        $this->isPresentationDone = $data['presentation']['isPresentationDone'];


        // TODO FXD IS PRES
        // $this->isPresentationDone = false;
        // if ($data['report']['resultStatus'] !== 'result' && $this->currentReportEventType == 'presentation') {
        //     $this->isPresentationDone  = true;
        // }


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


        // if ($domain === 'april-dev.bitrix24.ru' || $domain === 'gsr.bitrix24.ru') {
        $this->isDealFlow = true;
        $this->withLists = true;
        if (!empty($portal['deals'])) {
            $this->portalDealData = $portal['bitrixDeal'];
        }
        if (!empty($portal['bitrixLists'])) {

            $this->bitrixLists = $portal['bitrixLists'];
        }
        // }


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



        if ($domain === 'gsr.bitrix24.ru' || $domain === 'gsirk.bitrix24.ru' || $domain === 'april-garant.bitrix24.ru') {
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
                $this->currentBtxEntity  = $sessionData['currentCompany'];

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
                    $this->currentTMCDealFromCurrentPres = $sessionDeals['currentTMCDeal'];
                    // Log::info('HOOK TMC SESSION', ['sessionDeals' => $sessionDeals]);
                    // Log::info('HOOK TMC SESSION currentTMCDeal', ['session currentTMCDeal' => $sessionDeals['currentTMCDeal']]);
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
        // Log::channel('telegram')->info('HOOK TMC SESSION GET', ['sessionData' => $sessionData]);

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
        // Log::info(
        //     'HOOK TMC SESSION currentTMCDeal',
        //     [
        //         'session currentTMCDealFromCurrentPres' => $this->currentTMCDealFromCurrentPres
        //     ]
        // );
        // Log::info(
        //     'HOOK TMC SESSION currentTMCDeal',
        //     [
        //         'session currentTMCDeal' => $this->currentTMCDeal
        //     ]
        // );


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
            $result = null;

            if ($this->isDealFlow && $this->portalDealData) {

                $currentDealsIds = $this->getNEWBatchDealFlow();
            }




            if (!empty($this->postFail)) {
                if (!empty($this->postFail['postFailDate'])) {
                    if (!empty($this->workStatus['current'])) {
                        if (!empty($this->workStatus['current']['code'])) {
                            $workStatusCode = $this->workStatus['current']['code'];


                            if ($workStatusCode === 'fail') {  //если провал 
                                $this->failFlow();
                            }
                        }
                    }
                }
            }


            $this->relationLeadFlow();

            // sleep(1);

            /** TESTING BATCH */

            // $this->getListFlow();

            $this->getListBatchFlow();
            //   $this->getListFlow();

            // if ($this->domain !== 'april-dev.bitrix24.ru') {

            //     // $rand = mt_rand(600000, 1000000); // случайное число от 300000 до 900000 микросекунд (0.3 - 0.9 секунды)
            //     $rand = mt_rand(600000, 1000000); // случайное число от 300000 до 900000 микросекунд (0.3 - 0.9 секунды)
            //     usleep($rand);

            //     $this->getListPresentationFlow(
            //         $currentDealsIds
            //     );
            // }
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

    protected function relationLeadFlow()
    {
        try {
            if (!empty($this->relationLead)) {
                if (!empty($this->relationLead['ID'])) {

                    $statusForRelationLead = '';

                    if (!empty($this->isResult)) {
                        if (!empty($this->isInWork) || !empty($this->isSuccessSale)) {
                            $statusForRelationLead = 'success';
                        }
                    }
                    if (!empty($this->isFail)) {
                        $statusForRelationLead = 'fail';
                    }

                    if ($statusForRelationLead == 'success' || $statusForRelationLead == 'fail') {
                        $relationLeadService = new EventReportRelationLeadService(
                            $this->domain,
                            $this->hook,
                            $this->relationLead['ID'],
                            $statusForRelationLead,
                        );
                        $relationLeadService->processLead();
                    }
                }
            } else {
                // Log::channel('telegram')->info(
                //     'relationLead NO LEAD',
                //     [
                //         '$this->' => $this->relationLead,

                //     ]
                // );
            }
        } catch (\Throwable $th) {
            $errorMessages =  [
                'message'   => $th->getMessage(),
                'file'      => $th->getFile(),
                'line'      => $th->getLine(),
                'trace'     => $th->getTraceAsString(),
            ];
            APIOnlineController::sendLog('EventReportRelationLeadService', [

                'domain' => $this->domain,
                // 'companyId' => $companyId,
                'error' =>   $errorMessages

            ]);
        }
    }
    protected function failFlow()
    {
        if (!empty($this->postFail)) {
            if (!empty($this->workStatus['current'])) {
                if (!empty($this->workStatus['current']['code'])) {
                    $workStatusCode = $this->workStatus['current']['code'];


                    if ($workStatusCode === 'fail') {  //если провал 
                        if (!empty($this->failType)) {
                            if (!empty($this->failType['code'])) {

                                // $reportFields['op_prospects_type'] = $this->failType['code'];


                                if ($this->failType['code'] == 'failure') {
                                    Log::channel('telegram')->info(
                                        'failFlow',
                                        [
                                            'domain' => $this->domain,
                                            // 'hook' => $this->hook,
                                            'fail' => $this->postFail,
                                            'companyId' => $this->entityId
                                        ]
                                    );
                                    $postFailService = new EventReportPostFailService([
                                        'domain' => $this->domain,
                                        'hook' => $this->hook,
                                        'fail' => $this->postFail,
                                        'companyId' => $this->entityId
                                    ]);
                                    $postFailService->processPostFail();
                                }
                            }
                        }
                    }
                }
            }
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
            $reportFields['call_next_date'] = $this->planDeadline;
            $reportFields['call_next_name'] = $this->currentPlanEventName;
            $reportFields['op_current_status'] = 'Звонок запланирован в работе';

            //general
            // $reportFields['call_next_date'] = $this->planDeadline;
            // $reportFields['call_next_name'] = $this->currentPlanEventName;
            // $reportFields['xo_responsible'] = $this->planResponsibleId;
            // $reportFields['xo_created'] = $this->planResponsibleId;
            // $reportFields['op_current_status'] = 'Звонок запланирован в работе';

            // Log::channel('telegram')->info('TST', [
            //     'currentPlanEventType' => $currentPlanEventType,

            // ]);
            if ($this->isExpired) {
                switch ($this->currentReportEventType) {
                        // 0: {id: 1, code: "warm", name: "Звонок"}
                        // // 1: {id: 2, code: "presentation", name: "Презентация"}
                        // // 2: {id: 3, code: "hot", name: "Решение"}
                        // // 3: {id: 4, code: "moneyAwait", name: "Оплата"}
                    case 'xo':
                        $reportFields['xo_date'] = $this->planDeadline;
                        $reportFields['op_current_status'] = 'Перенос: ' . $this->currentReportEventName;

                        // $reportFields['xo_name'] = 'Перенос: ' $this->currentReportEventName;

                        break;
                    case 'hot':
                        $reportFields['op_current_status'] = 'Перенос: ' . $this->currentReportEventName;
                        array_push($currentMComments, $this->nowDate . 'Перенос: ' . $this->currentReportEventName . $this->comment);

                        break;
                    case 'moneyAwait':
                        $reportFields['op_current_status'] = 'Перенос: ' . $this->currentReportEventName;
                        array_push($currentMComments, $this->nowDate . 'Перенос: ' . $this->currentReportEventName . $this->comment);
                        break;


                    case 'presentation':

                        // $reportFields['last_pres_plan_date'] = $this->nowDate; //когда запланировали последнюю през
                        // $reportFields['last_pres_plan_responsible'] = $this->planResponsibleId;
                        $reportFields['next_pres_plan_date'] = $this->planDeadline;  //дата на которую запланировали през
                        $reportFields['op_current_status'] = 'Перенос: ' . $this->currentReportEventName;
                        // array_push($currentPresComments, $this->nowDate . 'Перенос: ' . $this->currentReportEventName . $this->comment);
                        // array_push($currentMComments, $this->nowDate . 'Перенос: ' . $this->currentReportEventName . $this->comment);
                        break;
                    default:
                        # code...
                        break;
                }
            } else {


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


    //entity
    protected function getEntityBatchFlowCommand(
        $isDeal = false,
        $deal = null,
        $dealType = 'base',  //presentation, xo
        $baseDealId = null,
        $dealEventType = false //plan done unplanned fail
    ) {
        $entityCommand = '';
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

        // $reportFields['op_fail_comments'] = '';
        $reportFields['op_history'] = '';
        $reportFields['op_mhistory'] = [];


        $currentPresCount = 0;
        $companyPresCount = 0;
        $dealPresCount = 0;



        if (!empty($this->currentBtxEntity)) {
            if (isset($this->currentBtxEntity['UF_CRM_1709807026'])) {

                $currentPresCount = (int)$this->currentBtxEntity['UF_CRM_1709807026'];
                $companyPresCount = (int)$this->currentBtxEntity['UF_CRM_1709807026'];
            }
        }

        if (!empty($this->currentBaseDeal)) {
            if (isset($this->currentBaseDeal['UF_CRM_PRES_COUNT'])) {

                $dealPresCount = (int)$this->currentBaseDeal['UF_CRM_PRES_COUNT'];
            }
        }

        // if (!empty($this->currentTask)) {
        //     if (!empty($this->currentTask['presentation'])) {

        //         if (!empty($this->currentTask['presentation']['company'])) {
        //             $companyPresCount = (int)$this->currentTask['presentation']['company'];
        //         }
        //         if (!empty($this->currentTask['presentation']['deal'])) {
        //             $dealPresCount = (int)$this->currentTask['presentation']['deal'];
        //         }
        //     }
        // }



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
                $reportFields['op_mhistory'] =   $currentMComments;
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
            array_unshift($currentPresComments, $this->nowDate . ' Презентация проведена ' . $this->comment);
            // array_push($currentMComments, $this->nowDate . ' Презентация проведена ' . $this->comment);
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
                    // array_unshift($currentMComments, $this->nowDate . 'В решении: ' . $this->comment);

                    break;
                case 'moneyAwait':
                    $reportFields['op_current_status'] = 'Ждем оплаты: ' . $this->currentPlanEventName;
                    // array_unshift($currentMComments, $this->nowDate . 'В оплате: ' . $this->comment);
                    break;


                case 'presentation':

                    $reportFields['last_pres_plan_date'] = $this->nowDate; //когда запланировали последнюю през
                    $reportFields['last_pres_plan_responsible'] = $this->planResponsibleId;
                    $reportFields['next_pres_plan_date'] = $this->planDeadline;  //дата на которую запланировали през
                    $reportFields['op_current_status'] = 'В работе: Презентация запланирована ' . $this->currentPlanEventName;
                    array_push($currentPresComments, $this->nowDate . ' Презентация запланирована ' . $this->currentPlanEventName);
                    // array_unshift($currentMComments, $this->nowDate . ' Презентация запланирована ' . $this->currentPlanEventName);
                    break;
                default:
                    # code...
                    break;
            }
        } else {
            if ($this->workStatus['current']['code'] === 'fail') {
                $reportFields['op_current_status'] = 'Отказ';
                // array_unshift($currentMComments, $this->nowDate . ' Отказ ' . $this->comment);

                array_unshift($currentFailComments, $this->nowDate . "\n" . $this->comment);
                if (count($currentFailComments) > 18) {
                    $currentFailComments = array_slice($currentFailComments, 0, 18);
                }
                $reportFields['op_fail_comments'] = $currentFailComments;


                if ($this->isPresentationDone) {
                    array_unshift($currentPresComments, $this->nowDate . ' Отказ после презентации ' . $this->currentTaskTitle . ' ' . $this->comment);
                } else {
                    if ($currentReportEventType === 'presentation') {

                        array_unshift($currentPresComments, $this->nowDate . ' Отказ: Презентация не состоялась ' . $this->currentTaskTitle . ' ' . $this->comment);
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

                        array_unshift($currentPresComments, $this->nowDate . ' Перенос: ' . $this->currentTaskTitle . ' ' . $this->comment);
                    }
                    // array_unshift($currentMComments, $this->nowDate . ' Перенос: ' . $this->currentTaskTitle . ' ' . $this->comment);
                }

                // array_push($currentMComments, $this->nowDate . ' Нерезультативный. ' . $this->currentTaskTitle);
            } else {
                // array_unshift($currentMComments, $this->nowDate . ' Результативный ' . $this->currentTaskTitle. ' ' . $this->comment);
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


                            if ($this->failType['code'] == 'failure') { //если тип провала - отказ возражение


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
        $comment = $this->getFullEventComment(); 
      
        array_unshift($currentMComments, $this->nowDate . "\n" . $comment);
        $totalCommentsCount = 12;
        if ($this->domain === 'gsirk.bitrix24.ru') {
            $totalCommentsCount = 30;
        }
        if (count($currentMComments) > $totalCommentsCount) {
            $currentMComments = array_slice($currentMComments, 0, $totalCommentsCount);
        }
        if (count($currentPresComments) > 15) {
            $currentPresComments = array_slice($currentPresComments, 0, 15);
        }

        //закидываем сформированные комментарии
        $reportFields['op_mhistory'] = $currentMComments;
        // if ($this->isPresentationDone || ($this->isPlanned && $currentPlanEventType == 'presentation')) {
        $reportFields['pres_comments'] = $currentPresComments;
        // }


        $entityService = new BitrixEntityBatchFlowService();


        // Log::channel('telegram')->info('HOOK FROM ONLINE', ['reportFields' => $reportFields]);
        // Log::info('HOOK FROM ONLINE', ['reportFields' => $reportFields]);

        if (isset($reportFields['op_work_status'])) {

            // Log::channel('telegram')->info('HOOK FROM ONLINE', ['op_work_status' => $reportFields['op_work_status']]);
            // Log::info('HOOK FROM ONLINE', ['op_work_status' => $reportFields['op_work_status']]);
        }





        $entityCommand = $entityService->getBatchCommand(
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



        // return ['command' => $entityCommand];
        return $entityCommand;
    }

    protected function getEntityBatchFlowCommandFromIdForNewDeal(
        $isDeal = true,
        $dealId,
        $dealType = 'base',  //presentation, xo
        $baseDealId = null,
        $dealEventType = false //plan done unplanned fail
    ) {
        $entityCommand = '';
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
        $reportFields['op_mhistory'] = [];


        $currentPresCount = 0;
        $companyPresCount = 0;
        $dealPresCount = 0;


        //CЧЕТЧИК ОБНУЛЯЕТСЯ ЕСЛИ NEW TASK
        if (!empty($this->currentBtxEntity)) {
            if (isset($this->currentBtxEntity['UF_CRM_1709807026'])) {

                $currentPresCount = (int)$this->currentBtxEntity['UF_CRM_1709807026'];
                $companyPresCount = (int)$this->currentBtxEntity['UF_CRM_1709807026'];
            }
        }

        if (!empty($this->currentBaseDeal)) {
            if (isset($this->currentBaseDeal['UF_CRM_PRES_COUNT'])) {

                $dealPresCount = (int)$this->currentBaseDeal['UF_CRM_PRES_COUNT'];
            }
        }

        // if (!empty($this->currentTask)) {
        //     if (!empty($this->currentTask['presentation'])) {

        //         if (!empty($this->currentTask['presentation']['company'])) {
        //             $companyPresCount = (int)$this->currentTask['presentation']['company'];
        //         }
        //         if (!empty($this->currentTask['presentation']['deal'])) {
        //             $dealPresCount = (int)$this->currentTask['presentation']['deal'];
        //         }
        //     }
        // }


        $currentPresCount =  $companyPresCount;
        if ($isDeal && !empty($dealId)) {

            $currentPresCount =  $dealPresCount;
            // $currentBtxEntity = $deal;
            $entityType = 'deal';
            $entityId =  $dealId;
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
        // if (isset($currentBtxEntity)) {
        //     if (!empty($currentBtxEntity['UF_CRM_PRES_COMMENTS'])) {
        //         $currentPresComments = $currentBtxEntity['UF_CRM_PRES_COMMENTS'];
        //     }

        //     if (!empty($currentBtxEntity['UF_CRM_OP_FAIL_COMMENTS'])) {
        //         $currentFailComments = $currentBtxEntity['UF_CRM_OP_FAIL_COMMENTS'];
        //     }

        //     if (!empty($currentBtxEntity['UF_CRM_OP_MHISTORY'])) {
        //         $currentMComments = $currentBtxEntity['UF_CRM_OP_MHISTORY'];
        //     }
        // }


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
            // array_push($currentMComments, $this->nowDate . ' Презентация проведена ' . $this->comment);
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
                    // array_unshift($currentMComments, $this->nowDate . 'В решении: ' . $this->comment);

                    break;
                case 'moneyAwait':
                    $reportFields['op_current_status'] = 'Ждем оплаты: ' . $this->currentPlanEventName;
                    // array_unshift($currentMComments, $this->nowDate . 'В оплате: ' . $this->comment);
                    break;


                case 'presentation':

                    $reportFields['last_pres_plan_date'] = $this->nowDate; //когда запланировали последнюю през
                    $reportFields['last_pres_plan_responsible'] = $this->planResponsibleId;
                    $reportFields['next_pres_plan_date'] = $this->planDeadline;  //дата на которую запланировали през
                    $reportFields['op_current_status'] = 'В работе: Презентация запланирована ' . $this->currentPlanEventName;
                    array_push($currentPresComments, $this->nowDate . ' Презентация запланирована ' . $this->currentPlanEventName);
                    // array_unshift($currentMComments, $this->nowDate . ' Презентация запланирована ' . $this->currentPlanEventName);
                    break;
                default:
                    # code...
                    break;
            }
        } else {
            if ($this->workStatus['current']['code'] === 'fail') {
                $reportFields['op_current_status'] = 'Отказ';
                // array_unshift($currentMComments, $this->nowDate . ' Отказ ' . $this->comment);



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
                    // array_unshift($currentMComments, $this->nowDate . ' Перенос: ' . $this->currentTaskTitle . ' ' . $this->comment);
                }

                // array_push($currentMComments, $this->nowDate . ' Нерезультативный. ' . $this->currentTaskTitle);
            } else {
                // array_unshift($currentMComments, $this->nowDate . ' Результативный ' . $this->currentTaskTitle. ' ' . $this->comment);
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
        $comment = $this->getFullEventComment();
        array_unshift($currentMComments, $this->nowDate . "\n" . $comment);
        if (count($currentMComments) > 8) {
            $currentMComments = array_slice($currentMComments, 0, 8);
        }


        //закидываем сформированные комментарии
        $reportFields['op_mhistory'] = $currentMComments;
        // if ($this->isPresentationDone || ($this->isPlanned && $currentPlanEventType == 'presentation')) {
        $reportFields['pres_comments'] = $currentPresComments;
        // }


        $entityService = new BitrixEntityBatchFlowService();




        $entityCommand = $entityService->getBatchCommand(
            $this->portal,
            null, // $currentBtxEntity,
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



        // return ['command' => $entityCommand];
        return $entityCommand;
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

    // protected function getDealFlow()
    // {

    //     //сейчас есть
    //     // protected $currentBaseDeal;
    //     // protected $currentPresDeal;
    //     // protected $currentColdDeal;
    //     // protected $currentTMCDeal;

    //     // protected $relationBaseDeals;  //базовые сделки пользователь-компания
    //     // protected $relationCompanyUserPresDeals; //allPresDeals //през сделки пользователь-компания
    //     // protected $relationFromBasePresDeals;
    //     // protected $relationColdDeals;
    //     // protected $relationTMCDeals;



    //     // $currentBaseDeal - обновляется в любом случае если ее нет - создается
    //     // $currentPresDeal - обновляется если през - done или planEventType - pres
    //     // $currentColdDeal - обновляется если xo - done или planEventType - xo

    //     // в зависимости от условий сделка в итоге попадает либо в plan либо в report deals

    //     $reportDeals = [];
    //     $planDeals = [];
    //     $currentBtxDeals = $this->currentBtxDeals;


    //     if (empty($currentBtxDeals)) {   //если текущие сделки отсутствуют значит надо сначала создать базовую - чтобы нормально отработал поток
    //         $setNewDealData = [
    //             'COMPANY_ID' => $this->entityId,
    //             'CATEGORY_ID' => $this->btxDealBaseCategoryId,
    //             'ASSIGNED_BY_ID' => $this->planResponsibleId,
    //         ];
    //         $currentDealId = BitrixDealService::setDeal(
    //             $this->hook,
    //             $setNewDealData,

    //         );

    //         if (!empty($currentDealId)) {
    //             $rand = mt_rand(300000, 900000); // случайное число от 300000 до 900000 микросекунд (0.3 - 0.9 секунды)
    //             usleep($rand);
    //             $newBaseDeal = BitrixDealService::getDeal(
    //                 $this->hook,
    //                 ['id' => $currentDealId]


    //             );
    //             $this->currentBaseDeal = $newBaseDeal;
    //             $currentBtxDeals = [$newBaseDeal];
    //             $this->currentBtxDeals = [$newBaseDeal];
    //         }
    //     }


    //     $unplannedPresDeals = null;
    //     $newPresDeal = null;
    //     // report - закрывает сделки
    //     // plan - создаёт
    //     //todo report flow

    //     // if report type = xo | cold
    //     $currentReportStatus = 'done';

    //     // if ($this->currentReportEventType == 'xo') {

    //     if ($this->isFail) {
    //         //найти сделку хо -> закрыть в отказ , заполнить поля отказа по хо 
    //         $currentReportStatus = 'fail';
    //     } else if ($this->isSuccessSale) {
    //         //найти сделку хо -> закрыть в отказ , заполнить поля отказа по хо 
    //         $currentReportStatus = 'success';
    //     } else {
    //         if ($this->isResult) {                   // результативный

    //             if ($this->isInWork) {                // в работе или успех
    //                 //найти сделку хо и закрыть в успех
    //             }
    //         } else { //нерезультативный 
    //             if ($this->isPlanned) {                // если запланирован нерезультативный - перенос 
    //                 //найти сделку хо и закрыть в успех
    //                 $currentReportStatus = 'expired';
    //             }
    //         }
    //     }
    //     // }


    //     if ($this->isPresentationDone && $this->currentReportEventType !== 'presentation') { // проведенная презентация будет isUnplanned
    //         //в current task не будет id сделки презентации
    //         // в таком случае предполагается, что сделки презентация еще не существует
    //         $currentBtxDeals = [];
    //         $unplannedPresDeal = BitrixDealFlowService::unplannedPresflow(  //  создает - презентация
    //             $this->hook,
    //             null,
    //             $this->portalDealData,
    //             $this->currentDepartamentType,
    //             $this->entityType,
    //             $this->entityId,
    //             'presentation', // xo warm presentation,
    //             'Презентация',
    //             'Спонтанная от ' . $this->nowDate,
    //             'plan',  // plan done expired fail
    //             $this->planResponsibleId,
    //             true,
    //             '$fields',
    //             null // $relationSalePresDeal
    //         );

    //         // $isDeal = false, 
    //         // $deal = null, 
    //         // $dealType = 'base',  //presentation, xo
    //         // $baseDealId = null

    //         // Log::info('HOOK TEST unplannedPresDeal', [
    //         //     'currentBaseDeal' => $this->currentBaseDeal,


    //         // ]);
    //         if (!empty($this->currentBaseDeal)) {
    //             $this->getEntityFlow(
    //                 true,
    //                 $unplannedPresDeal,
    //                 'presentation',
    //                 $this->currentBaseDeal['ID'],
    //                 'unplanned'
    //             );
    //         }




    //         if (!empty($unplannedPresDeal)) {
    //             if (isset($unplannedPresDeal['ID'])) {

    //                 $unplannedPresDealId = $unplannedPresDeal['ID'];
    //                 array_push($this->currentBtxDeals, $unplannedPresDeal);
    //                 $unplannedPresResultStatus = 'done';
    //                 $unplannedPresResultName = 'Проведена';
    //                 if ($this->isFail) {
    //                     $unplannedPresResultStatus = 'fail';
    //                     $unplannedPresResultName = 'Отказ после презентации';
    //                 }
    //                 $flowResult = BitrixDealFlowService::flow(  // закрывает сделку  - презентация обновляет базовую в соответствии с проведенной през
    //                     $this->hook,
    //                     $this->currentBtxDeals,
    //                     $this->portalDealData,
    //                     $this->currentDepartamentType,
    //                     $this->entityType,
    //                     $this->entityId,
    //                     'presentation', // xo warm presentation,
    //                     'Презентация',
    //                     $unplannedPresResultName,
    //                     $unplannedPresResultStatus,  // plan done expired fail
    //                     $this->planResponsibleId,
    //                     true,
    //                     '$fields',
    //                     null // $relationSalePresDeal
    //                 );
    //                 $unplannedPresDeals = $flowResult['dealIds'];




    //                 // Log::channel('telegram')->info('HOOK TEST CURRENTENTITY', [
    //                 //     'unplannedPresDeals' => $unplannedPresDeals,


    //                 // ]);
    //                 // Log::info('HOOK TEST CURRENTENTITY', [
    //                 //     'unplannedPresDeals' => $unplannedPresDeals,


    //                 // ]);
    //                 foreach ($this->currentBtxDeals as $cbtxdeal) {
    //                     if ($cbtxdeal['ID'] !== $unplannedPresDealId) {
    //                         $rand = mt_rand(600000, 1000000); // случайное число от 300000 до 900000 микросекунд (0.3 - 0.9 секунды)
    //                         usleep($rand);
    //                         $updtdbtxdeal = BitrixDealService::getDeal(
    //                             $this->hook,
    //                             ['id' => $cbtxdeal['ID']]
    //                         );
    //                         if (!empty($updtdbtxdeal)) {

    //                             $cbtxdeal = $updtdbtxdeal;
    //                         }
    //                         array_push($currentBtxDeals, $cbtxdeal);
    //                     }
    //                 }
    //             }
    //         }
    //     }
    //     sleep(1);


    //     //если был unplanned а потом plan ->
    //     //если warm plan а report был xo 
    //     // - то нужна обновленная стадия в базовой битрикс сделке что не пыталось повысить
    //     // с xo в warm так как уже на самом деле pres 
    //     // если plan pres -> планируется новая презентация и поэтому в  
    //     // $this->currentBtxDeals должна отсутствовать сделка презентации созданная при unplanned, 
    //     // которая пушится туда  при unplanned - чтобы были обработаны базовая сделка 
    //     // в соответствии с проведенной през
    //     // при этом у основной сделки должна быть обновлена стадия - например на през если была unplanned



    //     $flowResult = BitrixDealFlowService::flow(  // редактирует сделки отчетности из currentTask основную и если есть xo
    //         $this->hook,
    //         $currentBtxDeals,
    //         $this->portalDealData,
    //         $this->currentDepartamentType,
    //         $this->entityType,
    //         $this->entityId,
    //         $this->currentReportEventType, // xo warm presentation, 
    //         $this->currentReportEventName,
    //         $this->currentPlanEventName,
    //         $currentReportStatus,  // plan done expired fail success
    //         $this->planResponsibleId,
    //         $this->isResult,
    //         '$fields',
    //         $this->relationSalePresDeal
    //     );
    //     $reportDeals = $flowResult['dealIds'];


    //     if (!empty($this->currentTMCDeal) && $this->currentReportEventType === 'presentation') {
    //         // Log::info('HOOK TEST currentBtxDeals', [
    //         //     'currentBtxDeals' => $currentBtxDeals,
    //         //     'this currentBtxDeals' => $this->currentBtxDeals,


    //         // ]);
    //         if ($this->resultStatus === 'result') {

    //             BitrixDealFlowService::flow(  // редактирует сделки отчетности из currentTask основную и если есть xo
    //                 $this->hook,
    //                 [$this->currentTMCDeal],
    //                 $this->portalDealData,
    //                 'tmc',
    //                 $this->entityType,
    //                 $this->entityId,
    //                 $this->currentReportEventType, // xo warm presentation, 
    //                 $this->currentReportEventName,
    //                 $this->currentPlanEventName,
    //                 'done', //$currentReportStatus,  // plan done expired fail success
    //                 $this->planResponsibleId,
    //                 $this->isResult,
    //                 '$fields',
    //                 $this->relationSalePresDeal
    //             );
    //             //обновляет сделку тмц в успех если есть tmc deal и если през состоялась
    //         } else    if ($this->isFail) {

    //             BitrixDealFlowService::flow(  // редактирует сделки отчетности из currentTask основную и если есть xo
    //                 $this->hook,
    //                 [$this->currentTMCDeal],
    //                 $this->portalDealData,
    //                 'tmc',
    //                 $this->entityType,
    //                 $this->entityId,
    //                 $this->currentReportEventType, // xo warm presentation, 
    //                 $this->currentReportEventName,
    //                 $this->currentPlanEventName,
    //                 'fail', //$currentReportStatus,  // plan done expired fail success
    //                 $this->planResponsibleId,
    //                 $this->isResult,
    //                 '$fields',
    //                 $this->relationSalePresDeal
    //             );
    //             //обновляет сделку тмц в успех если есть tmc deal и если през состоялась
    //         }
    //     }

    //     //todo plan flow

    //     // if ($this->currentPlanEventType == 'warm') {
    //     //     // найти или создать сделку base не sucess стадия теплый прозвон


    //     // }
    //     // if plan type = xo | cold

    //     //если запланирован
    //     //xo - создать или обновить ХО & Основная
    //     //warm | money_await | in_progress - создать или обновить  Основная
    //     //presentation - создать или обновить presentation & Основная

    //     if (!empty($this->currentBaseDeal)) {
    //         $rand = mt_rand(300000, 700000); // случайное число от 300000 до 900000 микросекунд (0.3 - 0.9 секунды)
    //         usleep($rand);
    //         $this->getEntityFlow(
    //             true,
    //             $this->currentBaseDeal,
    //             'base',
    //             $this->currentBaseDeal['ID'],
    //             'unplanned'
    //         );
    //     }
    //     // Log::info('HOOK TEST currentBtxDeals', [
    //     //     'currentBtxDeals' => $currentBtxDeals,
    //     //     '$this->currentPresDeal' => $this->currentPresDeal,


    //     // ]);
    //     if (!empty($this->currentPresDeal)) {  //report pres deal
    //         $rand = mt_rand(300000, 700000); // случайное число от 300000 до 900000 микросекунд (0.3 - 0.9 секунды)
    //         usleep($rand);
    //         $this->getEntityFlow(
    //             true,
    //             $this->currentPresDeal,
    //             'presentation',
    //             $this->currentBaseDeal['ID'],
    //             'done'
    //         );
    //     }


    //     if ($this->isPlanned) {
    //         $currentBtxDeals = BitrixDealFlowService::getBaseDealFromCurrentBtxDeals(
    //             $this->portalDealData,
    //             $currentBtxDeals
    //         );

    //         $flowResult =  BitrixDealFlowService::flow( //создает сделку
    //             $this->hook,
    //             $currentBtxDeals,
    //             $this->portalDealData,
    //             $this->currentDepartamentType,
    //             $this->entityType,
    //             $this->entityId,
    //             $this->currentPlanEventType, // xo warm presentation, hot moneyAwait
    //             $this->currentPlanEventTypeName,
    //             $this->currentPlanEventName,
    //             'plan',  // plan done expired 
    //             $this->planResponsibleId,
    //             $this->isResult,
    //             '$fields',
    //             null, // $relationSalePresDeal
    //         );
    //         $planDeals = $flowResult['dealIds'];
    //         $newPresDeal = $flowResult['newPresDeal'];

    //         // Log::channel('telegram')->info('HOOK', [
    //         //     '$this->currentTMCDeal' => $this->currentTMCDeal
    //         // ]);

    //         if (!empty($this->currentTMCDeal) && $this->currentPlanEventType == 'presentation') {
    //             BitrixDealFlowService::tmcPresentationRelation(
    //                 $this->hook,
    //                 $this->portalDealData,
    //                 $this->currentBaseDeal,
    //                 $newPresDeal,
    //                 $this->currentTMCDeal['ID']
    //             );
    //         }
    //     }

    //     // Log::info('HOOK TEST currentBtxDeals', [
    //     //     'newPresDeal' => $newPresDeal,



    //     // ]);
    //     if (!empty($newPresDeal)) {  //plan pres deal
    //         $rand = mt_rand(200000, 700000); // случайное число от 300000 до 900000 микросекунд (0.3 - 0.9 секунды)
    //         usleep($rand);
    //         $this->getEntityFlow(
    //             true,
    //             $newPresDeal,
    //             'presentation',
    //             $this->currentBaseDeal['ID'],
    //             'plan'
    //         );
    //     }
    //     // Log::channel('telegram')->info('presentationBtxList', [
    //     //     'reportDeals' => $reportDeals,
    //     //     'planDeals' => $planDeals,
    //     //     // 'failReason' => $failReason,
    //     //     // 'failType' => $failType,

    //     // ]);

    //     return [
    //         'reportDeals' => $reportDeals,
    //         'planDeals' => $planDeals,
    //         'unplannedPresDeals' => $unplannedPresDeals,
    //     ];
    // }


    // protected function getBatchDealFlow()
    // {

    //     // должен собрать batch commands
    //     // отправить send batch
    //     // из резултатов вернуть объект с массивами созданных и обновленных сделок
    //     // если при начале функции нет currentBtxDeals - сначала создается она

    //     //сейчас есть
    //     // protected $currentBaseDeal;
    //     // protected $currentPresDeal;
    //     // protected $currentColdDeal;
    //     // protected $currentTMCDeal;

    //     // protected $relationBaseDeals;  //базовые сделки пользователь-компания
    //     // protected $relationCompanyUserPresDeals; //allPresDeals //през сделки пользователь-компания
    //     // protected $relationFromBasePresDeals;
    //     // protected $relationColdDeals;
    //     // protected $relationTMCDeals;



    //     // $currentBaseDeal - обновляется в любом случае если ее нет - создается
    //     // $currentPresDeal - обновляется если през - done или planEventType - pres
    //     // $currentColdDeal - обновляется если xo - done или planEventType - xo

    //     // в зависимости от условий сделка в итоге попадает либо в plan либо в report deals

    //     $reportDeals = [];
    //     $planDeals = [];
    //     $currentBtxDeals = $this->currentBtxDeals;
    //     $batchCommands = [];
    //     $entityBatchCommands = [];

    //     $unplannedPresDeal =  null;
    //     if (empty($currentBtxDeals)) {   //если текущие сделки отсутствуют значит надо сначала создать базовую - чтобы нормально отработал поток
    //         $setNewDealData = [
    //             'COMPANY_ID' => $this->entityId,
    //             'CATEGORY_ID' => $this->btxDealBaseCategoryId,
    //             'ASSIGNED_BY_ID' => $this->planResponsibleId,
    //         ];
    //         $currentDealId = BitrixDealService::setDeal(
    //             $this->hook,
    //             $setNewDealData,

    //         );

    //         if (!empty($currentDealId) && empty($this->currentBaseDeal)) {
    //             // $rand = mt_rand(100000, 300000); // случайное число от 300000 до 900000 микросекунд (0.3 - 0.9 секунды)
    //             // usleep($rand);
    //             $newBaseDeal = BitrixDealService::getDeal(
    //                 $this->hook,
    //                 ['id' => $currentDealId]


    //             );
    //             $this->currentBaseDeal = $newBaseDeal;
    //             $currentBtxDeals = [$newBaseDeal];
    //             $this->currentBtxDeals = [$newBaseDeal];
    //         }
    //     }

    //     $this->setTimeLine();
    //     $unplannedPresDeals = null;
    //     $newPresDeal = null;
    //     // report - закрывает сделки
    //     // plan - создаёт
    //     //todo report flow

    //     // if report type = xo | cold
    //     $currentReportStatus = 'done';

    //     // if ($this->currentReportEventType == 'xo') {

    //     if ($this->isFail) {
    //         //найти сделку хо -> закрыть в отказ , заполнить поля отказа по хо 
    //         $currentReportStatus = 'fail';
    //     } else if ($this->isSuccessSale) {
    //         //найти сделку хо -> закрыть в отказ , заполнить поля отказа по хо 
    //         $currentReportStatus = 'success';
    //     } else {
    //         if ($this->isResult) {                   // результативный

    //             if ($this->isInWork) {                // в работе или успех
    //                 //найти сделку хо и закрыть в успех
    //             }
    //         } else { //нерезультативный 
    //             if ($this->isPlanned) {                // если запланирован нерезультативный - перенос 
    //                 //найти сделку хо и закрыть в успех
    //                 $currentReportStatus = 'expired';
    //             }
    //         }
    //     }
    //     // }


    //     if ($this->isPresentationDone && $this->currentReportEventType !== 'presentation') { // проведенная презентация будет isUnplanned
    //         //в current task не будет id сделки презентации
    //         // в таком случае предполагается, что сделки презентация еще не существует
    //         $currentBtxDeals = [];
    //         $unplannedPresDeal = BitrixDealFlowService::unplannedPresflow(  //  создает - презентация
    //             $this->hook,
    //             null,
    //             $this->portalDealData,
    //             $this->currentDepartamentType,
    //             $this->entityType,
    //             $this->entityId,
    //             'presentation', // xo warm presentation,
    //             'Презентация',
    //             'Спонтанная от ' . $this->nowDate,
    //             'plan',  // plan done expired fail
    //             $this->planResponsibleId,
    //             true,
    //             '$fields',
    //             null // $relationSalePresDeal
    //         );

    //         // $isDeal = false, 
    //         // $deal = null, 
    //         // $dealType = 'base',  //presentation, xo
    //         // $baseDealId = null

    //         // Log::info('HOOK TEST unplannedPresDeal', [
    //         //     'currentBaseDeal' => $this->currentBaseDeal,


    //         // ]);
    //         if (!empty($this->currentBaseDeal)) {
    //             $entityCommand =  $this->getEntityBatchFlowCommand(
    //                 true,
    //                 $unplannedPresDeal,
    //                 'presentation',
    //                 $this->currentBaseDeal['ID'],
    //                 'unplanned'
    //             );
    //             $key = 'entity_unplanned' . '_' . 'deal' . '_' . $unplannedPresDeal['ID'];
    //             $entityBatchCommands[$key] = $entityCommand; // в результате будет id
    //         }




    //         if (!empty($unplannedPresDeal)) {
    //             if (isset($unplannedPresDeal['ID'])) {

    //                 $unplannedPresDealId = $unplannedPresDeal['ID'];
    //                 array_push($this->currentBtxDeals, $unplannedPresDeal);
    //                 $unplannedPresResultStatus = 'done';
    //                 $unplannedPresResultName = 'Проведена';
    //                 if ($this->isFail) {
    //                     $unplannedPresResultStatus = 'fail';
    //                     $unplannedPresResultName = 'Отказ после презентации';
    //                 }
    //                 $flowResult = BitrixDealBatchFlowService::batchFlow(  // закрывает сделку  - презентация обновляет базовую в соответствии с проведенной през
    //                     $this->hook,
    //                     $this->currentBtxDeals,
    //                     $this->portalDealData,
    //                     $this->currentDepartamentType,
    //                     $this->entityType,
    //                     $this->entityId,
    //                     'presentation', // xo warm presentation,
    //                     'Презентация',
    //                     $unplannedPresResultName,
    //                     $unplannedPresResultStatus,  // plan done expired fail
    //                     $this->planResponsibleId,
    //                     true,
    //                     '$fields',
    //                     null, // $relationSalePresDeal
    //                     $batchCommands,
    //                     'unpres'
    //                 );
    //                 // $unplannedPresDeals = $flowResult['dealIds'];
    //                 $batchCommands = $flowResult['commands'];



    //                 // Log::channel('telegram')->info('HOOK TEST CURRENTENTITY', [
    //                 //     'unplannedPresDeals' => $unplannedPresDeals,


    //                 // ]);
    //                 // Log::info('HOOK TEST CURRENTENTITY', [
    //                 //     'unplannedPresDeals' => $unplannedPresDeals,


    //                 // ]);
    //                 foreach ($this->currentBtxDeals as $cbtxdeal) {
    //                     if ($cbtxdeal['ID'] !== $unplannedPresDealId) {
    //                         $rand = mt_rand(100000, 300000); // случайное число от 300000 до 900000 микросекунд (0.3 - 0.9 секунды)
    //                         usleep($rand);
    //                         $updtdbtxdeal = BitrixDealService::getDeal(
    //                             $this->hook,
    //                             ['id' => $cbtxdeal['ID']]
    //                         );
    //                         if (!empty($updtdbtxdeal)) {

    //                             $cbtxdeal = $updtdbtxdeal;
    //                         }
    //                         array_push($currentBtxDeals, $cbtxdeal);
    //                     }
    //                 }
    //             }
    //         }
    //     }
    //     // $rand = mt_rand(600000, 1000000); // случайное число от 300000 до 900000 микросекунд (0.3 - 0.9 секунды)
    //     // usleep($rand);


    //     //если был unplanned а потом plan ->
    //     //если warm plan а report был xo 
    //     // - то нужна обновленная стадия в базовой битрикс сделке что не пыталось повысить
    //     // с xo в warm так как уже на самом деле pres 
    //     // если plan pres -> планируется новая презентация и поэтому в  
    //     // $this->currentBtxDeals должна отсутствовать сделка презентации созданная при unplanned, 
    //     // которая пушится туда  при unplanned - чтобы были обработаны базовая сделка 
    //     // в соответствии с проведенной през
    //     // при этом у основной сделки должна быть обновлена стадия - например на през если была unplanned
    //     // Log::info('HOOK TEST currentBtxDeals', [
    //     //     'currentBtxDeals' => $currentBtxDeals,
    //     //     'this currentBtxDeals' => $this->currentBtxDeals,


    //     // ]);

    //     // Log::info('HOOK BATCH batchFlow report DEAL', ['report currentBtxDeals' => $currentBtxDeals]);
    //     // Log::channel('telegram')->info('HOOK BATCH batchFlow', ['currentBtxDeals' => $currentBtxDeals]);
    //     $flowResult = BitrixDealBatchFlowService::batchFlow(  // редактирует сделки отчетности из currentTask основную и если есть xo
    //         $this->hook,
    //         $currentBtxDeals,
    //         $this->portalDealData,
    //         $this->currentDepartamentType,
    //         $this->entityType,
    //         $this->entityId,
    //         $this->currentReportEventType, // xo warm presentation, 
    //         $this->currentReportEventName,
    //         $this->currentPlanEventName,
    //         $currentReportStatus,  // plan done expired fail success
    //         $this->planResponsibleId,
    //         $this->isResult,
    //         '$fields',
    //         $this->relationSalePresDeal,
    //         $batchCommands,
    //         'report'

    //     );
    //     // $reportDeals = $flowResult['dealIds'];
    //     $batchCommands = $flowResult['commands'];
    //     // Log::info('HOOK BATCH batchFlow report DEAL', ['report batchCommands' => $batchCommands]);
    //     // Log::channel('telegram')->info('HOOK BATCH batchFlow', ['batchCommands' => $batchCommands]);
    //     // Log::info('HOOK BATCH $this->currentTMCDeal', ['report $this->currentTMCDeal' => $this->currentTMCDeal]);
    //     // Log::channel('telegram')->info('HOOK BATCH $this->currentTMCDeal', ['report $this->currentTMCDeal' => $this->currentTMCDeal]);

    //     // Log::info('HOOK BATCH $this->currentTMCDealFromCurrentPres', ['report $this->currentTMCDealFromCurrentPres' => $this->currentTMCDealFromCurrentPres]);
    //     // Log::channel('telegram')->info('HOOK BATCH $this->currentTMCDealFromCurrentPres', ['report $this->currentTMCDealFromCurrentPres' => $this->currentTMCDealFromCurrentPres]);



    //     // обновляет стадию тмц сделку
    //     // если есть из tmc init pres или relation tmc from session 
    //     // пытается подставить если есть связанную если нет - из init
    //     // обновляет сделку 
    //     // из инит - заявка принята
    //     // из relation - состоялась или fail
    //     if ((!empty($this->currentTMCDealFromCurrentPres) || !empty($this->currentTMCDeal)) &&
    //         ($this->resultStatus === 'result' || $this->isFail || $this->isSuccessSale) &&
    //         $this->currentReportEventType === 'presentation'
    //     ) {
    //         $curTMCDeal = $this->currentTMCDeal;
    //         if (!empty($this->currentTMCDealFromCurrentPres)) {
    //             $curTMCDeal = $this->currentTMCDealFromCurrentPres;
    //         }
    //         $tmcAction = 'done';
    //         if ($this->resultStatus !== 'result' && $this->isFail) {
    //             $tmcAction = 'fail';
    //         }
    //         $tmcflowResult =  BitrixDealBatchFlowService::batchFlow(  // редактирует сделки отчетности из currentTask основную и если есть xo
    //             $this->hook,
    //             [$curTMCDeal],
    //             $this->portalDealData,
    //             'tmc',
    //             $this->entityType,
    //             $this->entityId,
    //             $this->currentReportEventType, // xo warm presentation, 
    //             $this->currentReportEventName,
    //             $this->currentPlanEventName,
    //             $tmcAction, //$currentReportStatus,  // plan done expired fail success
    //             $this->planResponsibleId,
    //             $this->isResult,
    //             '$fields',
    //             $this->relationSalePresDeal,
    //             $batchCommands,
    //             'tmc_report'
    //         );
    //         $batchCommands = $tmcflowResult['commands'];
    //         //обновляет сделку тмц в успех если есть tmc deal и если през состоялась
    //     }

    //     //todo plan flow

    //     // if ($this->currentPlanEventType == 'warm') {
    //     //     // найти или создать сделку base не sucess стадия теплый прозвон


    //     // }
    //     // if plan type = xo | cold

    //     //если запланирован
    //     //xo - создать или обновить ХО & Основная
    //     //warm | money_await | in_progress - создать или обновить  Основная
    //     //presentation - создать или обновить presentation & Основная

    //     if (!empty($this->currentBaseDeal)) {
    //         // $rand = mt_rand(100000, 300000); // случайное число от 300000 до 900000 микросекунд (0.3 - 0.9 секунды)
    //         // usleep($rand);
    //         // $this->getEntityFlow(
    //         //     true,
    //         //     $this->currentBaseDeal,
    //         //     'base',
    //         //     $this->currentBaseDeal['ID'],
    //         //     'unplanned'
    //         // );

    //         $entityCommand =  $this->getEntityBatchFlowCommand(
    //             true,
    //             $this->currentBaseDeal,
    //             'base',
    //             $this->currentBaseDeal['ID'],
    //             'unplanned'
    //         );
    //         $key = 'entity_unplannedbase' . '_' . 'deal' . '_' .  $this->currentBaseDeal['ID'];
    //         $entityBatchCommands[$key] = $entityCommand; // в результате будет id
    //     }
    //     // Log::info('HOOK TEST currentBtxDeals', [
    //     //     'currentBtxDeals' => $currentBtxDeals,
    //     //     '$this->currentPresDeal' => $this->currentPresDeal,


    //     // ]);
    //     if (!empty($this->currentPresDeal)) {  //report pres deal
    //         // $rand = mt_rand(100000, 300000); // случайное число от 300000 до 900000 микросекунд (0.3 - 0.9 секунды)
    //         // usleep($rand);
    //         // $this->getEntityFlow(
    //         //     true,
    //         //     $this->currentPresDeal,
    //         //     'presentation',
    //         //     $this->currentBaseDeal['ID'],
    //         //     'done'
    //         // );

    //         $entityCommand =  $this->getEntityBatchFlowCommand(
    //             true,
    //             $this->currentPresDeal,
    //             'presentation',
    //             $this->currentBaseDeal['ID'],
    //             'done'
    //         );
    //         $key = 'entity_pres' . '_' . 'deal' . '_' . $this->currentPresDeal['ID'];
    //         $entityBatchCommands[$key] = $entityCommand; // в результате будет id
    //     }



    //     if ($this->isPlanned) {
    //         $currentBtxDeals = BitrixDealFlowService::getBaseDealFromCurrentBtxDeals(
    //             $this->portalDealData,
    //             $currentBtxDeals
    //         );

    //         $flowResult =   BitrixDealBatchFlowService::batchFlow( //создает сделку
    //             $this->hook,
    //             $currentBtxDeals,
    //             $this->portalDealData,
    //             $this->currentDepartamentType,
    //             $this->entityType,
    //             $this->entityId,
    //             $this->currentPlanEventType, // xo warm presentation, hot moneyAwait
    //             $this->currentPlanEventTypeName,
    //             $this->currentPlanEventName,
    //             'plan',  // plan done expired 
    //             $this->planResponsibleId,
    //             $this->isResult,
    //             '$fields',
    //             null, // $relationSalePresDeal
    //             $batchCommands, //тут я не эжу batch command а только созданная newpresdeal интересует, 
    //             // чтобы связать ее с тмц сделкой если таковая имелась
    //             // обновить поля в карточке презентационной сделки   
    //             'plan'
    //         );
    //         // $planDeals = $flowResult['dealIds'];
    //         $batchCommands = $flowResult['commands'];
    //     }
    //     $cleanBatchCommands = BitrixDealBatchFlowService::cleanBatchCommands($batchCommands, $this->portalDealData);

    //     // Log::channel('telegram')->info('HOOK BATCH', ['cleanBatchCommands' => $cleanBatchCommands]);


    //     $batchService =  new BitrixBatchService($this->hook);
    //     $results = $batchService->sendFlowBatchRequest($cleanBatchCommands);
    //     // Log::info('HOOK BATCH', ['results' => $results]);
    //     // Log::channel('telegram')->info('HOOK BATCH', ['results' => $results]);

    //     $result = BitrixDealBatchFlowService::handleBatchResults($results);
    //     $newPresDealId = null;
    //     // Log::info('HOOK BATCH TARGET NEW PRES RESULT', ['results' => $results]);
    //     // Log::channel('telegram')->info('HOOK BATCH TARGET NEW PRES RESULT', ['results' => $results]);


    //     if (!empty($result)) {
    //         if (!empty($result['newPresDeal'])) {
    //             $newPresDealId = $result['newPresDeal'];
    //             $newPresDeal = BitrixDealService::getDeal(
    //                 $this->hook,
    //                 ['id' => $newPresDealId]


    //             );
    //         }
    //     }
    //     // Log::info('HOOK BATCH', ['newPresDealId' => $newPresDealId]);
    //     // Log::channel('telegram')->info('HOOK BATCH newPresDealId', ['newPresDealId' => $newPresDealId]);


    //     // Log::info('HOOK BATCH entityBatchCommands DEAL', ['entityBatchCommands' => $entityBatchCommands]);
    //     // Log::channel('telegram')->info('HOOK BATCH entityBatchCommands', ['entityBatchCommands' => $entityBatchCommands]);


    //     // Log::info('HOOK BATCH', ['result' => $result]);
    //     // Log::channel('telegram')->info('HOOK BATCH', ['result' => $result]);
    //     // WITHOUT NEW
    //     // $newPresDeal = $flowResult['newPresDeal'];

    //     // Новая сделка созданная для презентации если есть тмц сделка
    //     // новая сделка презентации нужна только здесь
    //     //поэтому в batch commands - results будет 'new_pres_deal_id'
    //     // и в этот момент я ее отдельным get возьму

    //     // Устанавливает связь с переданной тмц сделкой из init pres и новой созданной pres deal
    //     if (!empty($this->currentTMCDeal) && $this->currentPlanEventType == 'presentation' && $newPresDeal) {
    //         BitrixDealFlowService::tmcPresentationRelation(
    //             $this->hook,
    //             $this->portalDealData,
    //             $this->currentBaseDeal,
    //             $newPresDeal,
    //             $this->currentTMCDeal['ID']
    //         );
    //         $sessionTMCDealKey = 'tmcInit_' . $this->domain . '_' . $this->planResponsibleId . '_' . $this->entityId;
    //         FullEventInitController::clearSessionItem($sessionTMCDealKey);
    //     }
    //     // }

    //     // Log::info('HOOK TEST currentBtxDeals', [
    //     //     'newPresDeal' => $newPresDeal,



    //     // ]);
    //     if (!empty($newPresDeal)) {  //plan pres deal
    //         // $rand = mt_rand(200000, 400000); // случайное число от 300000 до 900000 микросекунд (0.3 - 0.9 секунды)
    //         // usleep($rand);
    //         // $this->getEntityFlow(
    //         //     true,
    //         //     $newPresDeal,
    //         //     'presentation',
    //         //     $this->currentBaseDeal['ID'],
    //         //     'plan'
    //         // );

    //         $entityCommand =  $this->getEntityBatchFlowCommand(
    //             true,
    //             $newPresDeal,
    //             'presentation',
    //             $this->currentBaseDeal['ID'],
    //             'plan'
    //         );
    //         $key = 'entity_newpres' . '_' . 'deal' . '_' . $newPresDeal['ID'];
    //         $entityBatchCommands[$key] = $entityCommand; // в результате будет id
    //     }
    //     $companyCommand =  $this->getEntityBatchFlowCommand();
    //     $key = 'entity_newpres' . '_' . 'company' . '_';
    //     $entityBatchCommands[$key] = $companyCommand; // в результате будет id


    //     // ENTITY
    //     $entityResult =  $batchService->sendGeneralBatchRequest($entityBatchCommands);



    //     // Log::info('HOOK BATCH entityBatchCommands DEAL', ['entityBatchCommands' => $entityBatchCommands]);
    //     // Log::channel('telegram')->info('HOOK BATCH entityBatchCommands', ['entityBatchCommands' => $entityBatchCommands]);

    //     // Log::info('HOOK BATCH entity', ['result' => $entityResult]);
    //     // Log::channel('telegram')->info('HOOK BATCH entity', ['result' => $entityResult]);
    //     $result['unplannedPresDeals'] = [$unplannedPresDeal];

    //     return  $result;
    // }


    protected function getNEWBatchDealFlow()
    {

        $result =  ['dealIds' => ['$result'], 'planDeals' => null, 'newPresDeal' => null, 'commands' => null, 'unplannedPresDeals' => null];
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
        $currentDealId = null;
        if (!empty($this->currentBaseDeal)) {
            $currentDealId = $this->currentBaseDeal['ID'];
        }

        $xoDealId = null;
        if (!empty($this->currentColdDeal)) {
            $xoDealId = $this->currentColdDeal['ID'];
        }

        $reportPresDealId = null;
        if (!empty($this->currentPresDeal)) {
            $reportPresDealId = $this->currentPresDeal['ID'];
        }

        $reportDeals = [];
        $planDeals = [];
        $currentBtxDeals = $this->currentBtxDeals;
        $batchCommands = [];
        $entityBatchCommands = [];
        $isUnplanned = $this->isPresentationDone && $this->currentReportEventType !== 'presentation';
        $unplannedPresDeal =  null;
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
                // $rand = mt_rand(100000, 300000); // случайное число от 300000 до 900000 микросекунд (0.3 - 0.9 секунды)
                // usleep($rand);
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

        $batchService =  new BitrixBatchService($this->hook);

        // $result = BitrixDealBatchFlowService::batchFlowNEW(  // редактирует сделки отчетности из currentTask основную и если есть xo
        //     $this->hook,
        //     $this->currentBaseDeal,
        //     $this->portalDealData,
        //     $this->currentDepartamentType,
        //     $this->entityType,
        //     $this->entityId,
        //     $this->currentPlanEventType,
        //     $this->currentReportEventType, // xo warm presentation, 
        //     $this->currentReportEventName,
        //     $this->currentPlanEventName,
        //     $currentReportStatus,  // plan done expired fail success
        //     'plan',
        //     $this->planResponsibleId,
        //     $isUnplanned,
        //     $this->isExpired,
        //     $this->isResult,
        //     $this->isSuccessSale,
        //     $this->isFail,
        //     '$fields',
        //     $this->relationSalePresDeal,
        //     $batchCommands,
        //     'report',
        //     $currentDealId,
        //     $xoDealId,
        //     $reportPresDealId
        // );



        $newPresDeal = null;
        $planDeals = [];
        $reportDeals = [];
        $unplannedPresDeals = [];


        // Log::channel('telegram')
        //     ->info(
        //         'vheck',
        //         [
        //             'currentTMCDealFromCurrentPres' => $this->currentTMCDealFromCurrentPres,

        //         ]
        //     );
        // Log::channel('telegram')
        //     ->info(
        //         'vheck',
        //         [
        //             'currentTMCDeal' => $this->currentTMCDeal,

        //         ]
        //     );


        //DEALS FLOW
        foreach ($this->portalDealData['categories'] as $category) {

            switch ($category['code']) {
                case 'sales_base':
                    // Log::info('HOOK BATCH batchFlow report DEAL', ['category' =>  $category]);
                    // Log::channel('telegram')->info('HOOK BATCH currentDealId', ['currentDealId' =>  $currentDealId]);
                    // Log::info('HOOK BATCH batchFlow report DEAL', ['currentDealId' =>  $currentDealId]);
                    // Log::channel('telegram')->info('HOOK BATCH currentDealId', ['currentDealId' =>  $currentDealId]);
                    $currentStageOrder = BitrixDealService::getEventOrderFromCurrentBaseDeal($this->currentBaseDeal, $category);
                    $pTargetStage = BitrixDealService::getSaleBaseTargetStage(
                        $category,
                        $currentStageOrder,
                        // $currentDepartamentType,
                        $this->currentPlanEventType, // xo warm presentation, || null
                        $this->currentReportEventType, // xo warm presentation,
                        $this->currentReportEventName,
                        $this->currentPlanEventName,
                        $this->isResult,
                        $isUnplanned,
                        $this->isSuccessSale,
                        $this->isFail,

                    );
                    $targetStageBtxId = $pTargetStage;
                    // Log::info('HOOK BATCH batchFlow report DEAL', ['pTargetStage' =>  $pTargetStage]);
                    // Log::channel('telegram')->info('HOOK BATCH category', ['pTargetStage' =>  $pTargetStage]);

                    $fieldsData = [

                        'CATEGORY_ID' => $category['bitrixId'],
                        'STAGE_ID' => "C" . $category['bitrixId'] . ':' . $targetStageBtxId,
                        "COMPANY_ID" => $this->entityId,
                        'ASSIGNED_BY_ID' =>  $this->planResponsibleId
                    ];
                    if ($currentDealId) {

                        $batchCommand = BitrixDealBatchFlowService::getBatchCommand($fieldsData, 'update', $currentDealId);
                        $key = 'update_' . '_' . $category['code'] . '_' . $currentDealId;
                        $resultBatchCommands[$key] = $batchCommand;
                    } else {



                        $batchCommand = BitrixDealBatchFlowService::getBatchCommand($fieldsData, 'add', null);
                        $key = 'set_' . '_' . $category['code'];
                        $resultBatchCommands[$key] = $batchCommand;
                        $currentDealId = '$result[' . $key . ']';
                    }


                    $entityCommand =  $this->getEntityBatchFlowCommand(
                        true,
                        $this->currentBaseDeal,
                        'base',
                        $this->currentBaseDeal['ID'],
                        ''
                    );
                    $key = 'entity_base' . '_' . 'deal' . '_' .  $currentDealId;
                    $resultBatchCommands[$key] = $entityCommand; // в результате будет id

                    // if ($isUnplanned) {

                    //     array_push($planDeals, $baseDealId);
                    // }

                    if (!empty($this->currentPlanEventType)) {
                        array_push($planDeals, $currentDealId);
                    }
                    array_push($reportDeals, $currentDealId);
                    array_push($unplannedPresDeals, $currentDealId);


                    break;
                case 'sales_xo':
                    $pTargetStage = BitrixDealService::getXOTargetStage(
                        $category,
                        $this->currentReportEventType, // xo warm presentation,
                        $this->isExpired,
                        $this->isResult,
                        $this->isSuccessSale,
                        $this->isFail,

                    );
                    $targetStageBtxId = $pTargetStage;
                    // Log::info('HOOK BATCH batchFlow report DEAL', ['pTargetStage' =>  $pTargetStage]);
                    // Log::channel('telegram')->info('HOOK BATCH category', ['pTargetStage' =>  $pTargetStage]);
                    $fieldsData = [

                        'CATEGORY_ID' => $category['bitrixId'],
                        'STAGE_ID' => "C" . $category['bitrixId'] . ':' . $targetStageBtxId,
                        "COMPANY_ID" => $this->entityId,
                        'ASSIGNED_BY_ID' => $this->planResponsibleId,
                    ];

                    if ($xoDealId) {

                        $batchCommand = BitrixDealBatchFlowService::getBatchCommand($fieldsData, 'update', $xoDealId);
                        $key = 'update_' . '_' . $category['code'] . '_' . $xoDealId;
                        $resultBatchCommands[$key] = $batchCommand;
                    }

                    break;

                case 'sales_presentation':
                    $currentPresReportStatus = $currentReportStatus;

                    // APIOnlineController::sendLog('test pres noresult', [
                    //     'currentPresReportStatus' => $currentPresReportStatus,
                    //     'currentReportEventType' => $this->currentReportEventType,
                    //     'this->isFail' => $this->isFail,
                    //     'this->isResult' => $this->isResult,
                    //     'this->isPlanned' => $this->isPlanned,
                    //     'this->isInWork' => $this->isInWork,
                    //     'this->isSuccessSale' => $this->isSuccessSale,
                    //     'this->isExpired' => $this->isExpired,

                    //     'this->currentPlanEventType' => $this->currentPlanEventType,
                    //     'this->currentReportEventType' => $this->currentReportEventType,
                    // ]);
                    // 1) если report - presentetion - обновить текущую pres deal from task
                    if ($this->currentReportEventType == 'presentation') {
                        if (!$this->isFail) {

                            if ($this->isResult) {                   // результативный

                                if ($this->isInWork) {                // в работе или успех
                                    //найти сделку хо и закрыть в успех
                                }
                            } else { //нерезультативный 
                                if ($this->isPlanned) {                // если запланирован нерезультативный - перенос 
                                    //найти сделку хо и закрыть в успех
                                    $currentPresReportStatus = 'expired';
                                } else {
                                    $currentPresReportStatus = 'fail';
                                }
                            }
                        }
                        if ($reportPresDealId) {
                            array_push($reportDeals, $reportPresDealId);

                            $pTargetStage = BitrixDealService::getTargetStagePresentation(
                                $category,
                                // $currentDepartamentType,
                                $this->currentReportEventType, // xo warm presentation,
                                $currentPresReportStatus,  // plan done expired fail
                                $this->isResult,
                                $isUnplanned,
                                $this->isSuccessSale,
                                $this->isFail,

                            );
                            $fieldsData = [

                                // 'CATEGORY_ID' => $category['bitrixId'],
                                'STAGE_ID' => "C" . $category['bitrixId'] . ':' . $pTargetStage,
                                // "COMPANY_ID" => $entityId,
                                // 'ASSIGNED_BY_ID' => $responsibleId
                            ];


                            $batchCommand = BitrixDealBatchFlowService::getBatchCommand($fieldsData, 'update', $reportPresDealId);
                            $key = 'update_' . '_' . $category['code'] . '_' . $reportPresDealId;
                            $resultBatchCommands[$key] = $batchCommand;


                            $entityCommand =  $this->getEntityBatchFlowCommandFromIdForNewDeal(
                                true,
                                $reportPresDealId,
                                'presentation',
                                $this->currentBaseDeal['ID'],
                                $currentReportStatus
                            );

                            $key = 'update_entity_deal_plan' . '_' . $category['code'];
                            $resultBatchCommands[$key] = $entityCommand;
                        }
                    } else {  // для отмененной презентации - когда был report type - pres, но сделали - noPres - надо закрыть сделку през
                        if (!empty($this->currentTask)) {
                            if (!empty($this->currentTask['isPresentationCanceled'])) {

                                if ($reportPresDealId) {
                                    array_push($reportDeals, $reportPresDealId);

                                    $pTargetStage = BitrixDealService::getTargetStagePresentation(
                                        $category,
                                        // $currentDepartamentType,
                                        'presentation', // xo warm presentation,
                                        'fail',  // plan done expired fail
                                        false, //$this->isResult,
                                        false, //$isUnplanned,
                                        false, //$this->isSuccessSale,
                                        false, //$this->isFail,

                                    );
                                    $fieldsData = [

                                        // 'CATEGORY_ID' => $category['bitrixId'],
                                        'STAGE_ID' => "C" . $category['bitrixId'] . ':' . $pTargetStage,
                                        // "COMPANY_ID" => $entityId,
                                        // 'ASSIGNED_BY_ID' => $responsibleId
                                    ];


                                    $batchCommand = BitrixDealBatchFlowService::getBatchCommand($fieldsData, 'update', $reportPresDealId);
                                    $key = 'update_' . '_' . $category['code'] . '_' . $reportPresDealId;
                                    $resultBatchCommands[$key] = $batchCommand;


                                    $entityCommand =  $this->getEntityBatchFlowCommandFromIdForNewDeal(
                                        true,
                                        $reportPresDealId,
                                        'presentation',
                                        $this->currentBaseDeal['ID'],
                                        'fail'
                                    );

                                    $key = 'update_entity_deal_plan' . '_' . $category['code'];
                                    $resultBatchCommands[$key] = $entityCommand;
                                }
                            }
                        }
                    }

                    // 2) если plan - presentetion создать plan pres deal  и засунуть в plan и в task
                    if ($this->currentPlanEventType == 'presentation') {


                        $pTargetStage = BitrixDealService::getTargetStagePresentation(
                            $category,
                            // $currentDepartamentType,
                            $this->currentPlanEventType, // xo warm presentation,
                            'plan',  // plan done expired fail
                            $this->isResult,
                            $isUnplanned,
                            $this->isSuccessSale,
                            $this->isFail,

                        );
                        $fieldsData = [
                            'TITLE' => 'Презентация ' . $this->currentPlanEventName,
                            'CATEGORY_ID' => $category['bitrixId'],
                            'STAGE_ID' => "C" . $category['bitrixId'] . ':' . $pTargetStage,
                            "COMPANY_ID" => $this->entityId,
                            'ASSIGNED_BY_ID' => $this->planResponsibleId
                        ];
                        if (!empty($this->currentTMCDeal)) {
                            $fieldsData['UF_CRM_TO_BASE_TMC'] = $this->currentTMCDeal['ID'];
                        }
                        $batchCommand = BitrixDealBatchFlowService::getBatchCommand($fieldsData, 'add', null);
                        $key = 'set_' . '_' . $category['code'];
                        $resultBatchCommands[$key] = $batchCommand;
                        $newPresDeal = '$result[' . $key . ']';
                        // $newPresDealId = '$result[' . $key . '][ID]';
                        $entityCommand =  $this->getEntityBatchFlowCommandFromIdForNewDeal(
                            true,
                            $newPresDeal,
                            'presentation',
                            $this->currentBaseDeal['ID'],
                            'plan'
                        );

                        $key = 'update_entity_deal_plan' . '_' . $category['code'];
                        $resultBatchCommands[$key] = $entityCommand;

                        array_push($planDeals, $newPresDeal);
                    }

                    if (!empty($isUnplanned)) {
                        // 3) если unplanned pres создает еще одну и в успех ее сразу
                        $pTargetStage = BitrixDealService::getTargetStagePresentation(
                            $category,
                            // $currentDepartamentType,
                            'presentation', // xo warm presentation,
                            'done',  // plan done expired fail
                            $this->isResult,
                            $isUnplanned,
                            $this->isSuccessSale,
                            $this->isFail,

                        );

                        $fieldsData = [
                            'TITLE' => 'Презентация от ' . $this->nowDate,
                            'CATEGORY_ID' => $category['bitrixId'],
                            'STAGE_ID' => "C" . $category['bitrixId'] . ':' . $pTargetStage,
                            "COMPANY_ID" => $this->entityId,
                            'ASSIGNED_BY_ID' => $this->planResponsibleId
                        ];
                        $batchCommand = BitrixDealBatchFlowService::getBatchCommand($fieldsData, 'add', null);
                        $key = 'set_' . 'unplanned_' . $category['code'];
                        $resultBatchCommands[$key] = $batchCommand;
                        $unplannedPresDeal = '$result[' . $key . ']';

                        $entityCommand =  $this->getEntityBatchFlowCommandFromIdForNewDeal(
                            true,
                            $unplannedPresDeal,
                            'presentation',
                            $this->currentBaseDeal['ID'],
                            'unplanned'
                        );
                        $key = 'entity_unplannedbase' . '_' . 'deal' . '_' .  $this->currentBaseDeal['ID'];
                        $resultBatchCommands[$key] = $entityCommand;

                        array_push($unplannedPresDeals, $unplannedPresDeal);


                        $entityCommand =  $this->getEntityBatchFlowCommandFromIdForNewDeal(
                            true,
                            $unplannedPresDeal,
                            'presentation',
                            $currentDealId,
                            'unplanned'
                        );
                        $key = 'entity_unplanned' . '_' . 'deal';
                        $resultBatchCommands[$key] = $entityCommand; // в результате будет id
                    }



                    break;
                case 'tmc_base':


                    if (!empty($this->currentTMCDeal) && $this->currentPlanEventType == 'presentation') {
                        $categoryId = $category['bitrixId'];

                        $fieldsData = [
                            'CATEGORY_ID' => $categoryId,
                            'STAGE_ID' => "C" . $categoryId . ':' . 'PRES_PLAN',
                            // "COMPANY_ID" => $entityId,
                            // 'ASSIGNED_BY_ID' => $responsibleId
                            'UF_CRM_TO_BASE_SALES' => $this->currentBaseDeal['ID'],
                            'UF_CRM_TO_PRESENTATION_SALES' => $newPresDeal,
                            // 'UF_CRM_PRES_COMMENTS' => $newPresDeal['UF_CRM_PRES_COMMENTS'],
                            'UF_CRM_LAST_PRES_DONE_RESPONSIBLE' => $this->planResponsibleId,
                            'UF_CRM_MANAGER_OP' => $this->planResponsibleId,
                        ];

                        $batchCommand = BitrixDealBatchFlowService::getBatchCommand($fieldsData, 'update', $this->currentTMCDeal['ID']);
                        $key = 'update_' . '_' . $category['code'] . '_' . $this->currentTMCDeal['ID'];
                        $resultBatchCommands[$key] = $batchCommand;
                    }


                    // Log::channel('telegram')->info('TMC DEAL', [
                    //     'currentTMCDealFromCurrentPres' => $this->currentTMCDealFromCurrentPres
                    // ]);
                    if ((!empty($this->currentTMCDealFromCurrentPres) || !empty($this->currentTMCDeal)) &&
                        ($this->resultStatus === 'result' || $this->isFail || $this->isSuccessSale) &&
                        $this->currentReportEventType === 'presentation'
                    ) {
                        // обновляет стадию тмц сделку
                        // если есть из tmc init pres или relation tmc from session 
                        // пытается подставить если есть связанную если нет - из init
                        // обновляет сделку 
                        // из инит - заявка принята
                        // из relation - состоялась или fail
                        $curTMCDeal = $this->currentTMCDeal;
                        if (!empty($this->currentTMCDealFromCurrentPres)) {
                            $curTMCDeal = $this->currentTMCDealFromCurrentPres;
                        }
                        $tmcAction = 'done';
                        if ($this->resultStatus !== 'result' && $this->isFail) {
                            $tmcAction = 'fail';
                        }


                        $pTargetStage = BitrixDealService::getTargetStage(
                            $category,
                            'tmc',
                            $this->currentReportEventType, // xo warm presentation,
                            $tmcAction,  // plan done expired fail
                            $this->isResult,
                            // $isUnplanned,
                            // $this->isSuccessSale,
                            // $this->isFail,

                        );
                        $fieldsData = [
                            // 'TITLE' => 'Презентация от ' . $this->nowDate . ' ' . $this->currentPlanEventName,
                            'CATEGORY_ID' => $category['bitrixId'],
                            'STAGE_ID' => "C" . $category['bitrixId'] . ':' . $pTargetStage,
                            "COMPANY_ID" => $this->entityId,
                            // 'ASSIGNED_BY_ID' => $this->planResponsibleId
                        ];
                        $batchCommand = BitrixDealBatchFlowService::getBatchCommand($fieldsData, 'update', $curTMCDeal['ID']);

                        $key = 'update_' . '_' . $category['code'];
                        $resultBatchCommands[$key] = $batchCommand;

                        $entityCommand =  $this->getEntityBatchFlowCommand(
                            true,
                            $curTMCDeal,
                            'base',
                            null, // $this->currentBaseDeal['ID'],
                            ''
                        );

                        $key = 'update_entity_deal' . '_' . $category['code'];
                        $resultBatchCommands[$key] = $batchCommand;
                    }

                    $sessionTMCDealKey = 'tmcInit_' . $this->domain . '_' . $this->planResponsibleId . '_' . $this->entityId;

                    FullEventInitController::clearSessionItem($sessionTMCDealKey);

                    break;

                default:
                    # code...
                    break;
            }
        }




        // if (!empty($this->currentTMCDeal) && $this->currentPlanEventType == 'presentation') {
        //     BitrixDealFlowService::tmcPresentationRelation(
        //         $this->hook,
        //         $this->portalDealData,
        //         $this->currentBaseDeal,
        //         $newPresDeal,
        //         $this->currentTMCDeal['ID']
        //     );
        // }





        if (!empty($this->currentPresDeal)) {  //report pres deal
            // $rand = mt_rand(100000, 300000); // случайное число от 300000 до 900000 микросекунд (0.3 - 0.9 секунды)
            // usleep($rand);
            // $this->getEntityFlow(
            //     true,
            //     $this->currentPresDeal,
            //     'presentation',
            //     $this->currentBaseDeal['ID'],
            //     'done'
            // );

            $entityCommand =  $this->getEntityBatchFlowCommand(
                true,
                $this->currentPresDeal,
                'presentation',
                $this->currentBaseDeal['ID'],
                'done'
            );
            $key = 'entity_pres' . '_' . 'deal' . '_' . $this->currentPresDeal['ID'];
            $resultBatchCommands[$key] = $entityCommand; // в результате будет id
        }


        $companyCommand =  $this->getEntityBatchFlowCommand();
        $key = 'entity' . '_' . 'company';
        $resultBatchCommands[$key] = $companyCommand; // в результате будет id


        $result =  [
            'dealIds' => ['$result'],
            'planDeals' => $planDeals,
            'reportDeals' => $reportDeals,
            'newPresDeal' => $newPresDeal,
            'unplannedPresDeals' => $unplannedPresDeals,
            'commands' => $resultBatchCommands
        ];


        // $taskService = new BitrixTaskService();

        // $taskId = null;
        // if (!empty($this->currentTask)) {
        //     if (!empty($this->currentTask['ID'])) {

        //         $taskId = $this->currentTask['ID'];
        //     }
        //     if (!empty($this->currentTask['id'])) {
        //         $taskId = $this->currentTask['id'];
        //     }
        // }
        // $batchCommands =  $result['commands'];
        // if ($this->isExpired || $this->isPlanned) {
        $resultBatchCommands = $this->getTaskFlowBatchCommand(
            null,
            $result['planDeals'],
            $resultBatchCommands
        );
        // }
        $resultBatchCommands =  $this->getListPresentationFlowBatch(
            $result,
            $resultBatchCommands
        );

        $batchService->sendGeneralBatchRequest($resultBatchCommands);
        $this->setTimeLine();
        // Log::info('HOOK BATCH batchFlow report DEAL', ['report result' => $result]);
        // Log::channel('telegram')->info('HOOK BATCH batchFlow', ['result' => $result]);
        // Log::info('HOOK BATCH batchFlow report DEAL', ['planDeals planDeals' =>  $result['planDeals']]);
        // Log::channel('telegram')->info('HOOK BATCH planDeals', ['planDeals' => $result['planDeals']]);

        // if (!empty($result)) {
        //     if (!empty($result['newPresDeal'])) {
        //         $newPresDealId = $result['newPresDeal'];
        //         $newPresDeal = BitrixDealService::getDeal(
        //             $this->hook,
        //             ['id' => $newPresDealId]


        //         );
        //     }
        // }

        // $result['unplannedPresDeals'] = [$unplannedPresDeal];
        // $contactId = null;
        // if (!empty($this->planContact)) {
        //     if (!empty($this->planContact['current'])) {
        //         if (!empty($this->planContact['current']['contact'])) {
        //             $contactId = $this->planContact['current']['contact']['ID'];
        //         }
        //     }
        // }
        return  $result;
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
                    false, //$isNeedCompleteOtherTasks
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

    protected function getTaskFlowBatchCommand(
        $currentSmartItemId,
        $currentDealsIds,
        $batchCommands

    ) {


        $companyId  = null;
        $leadId  = null;
        $currentTaskId = null;
        $createdTask = null;
        $contact = $this->planContact;

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

                if (!empty($this->isPlanned)) {
                    $batchCommands =  $taskService->getCreateTaskBatchCommands(
                        $this->currentPlanEventType,       //$type,   //cold warm presentation hot 
                        $this->currentPlanEventTypeName,
                        $this->portal,
                        $this->domain,
                        $this->hook,
                        $this->currentBtxEntity,
                        $companyId,  //may be null
                        $leadId, //$leadId,     //may be null
                        $this->planCreatedId,
                        $this->planResponsibleId,
                        $this->planDeadline,
                        $this->currentPlanEventName,
                        null, // $currentSmartItemId,
                        false, //$isNeedCompleteOtherTasks
                        $currentTaskId, // null,
                        $currentDealsIds,
                        $contact,
                        $batchCommands

                    );
                } else {
                    if (!empty($currentTaskId)) {
                        $taskServiceForComplete = new BitrixTaskService();
                        $taskServiceForComplete->completeTask(
                            $this->hook,
                            [$currentTaskId]
                        );
                    }
                }
            } else {
                // $createdTask =  $taskService->updateTask(

                //     $this->domain,
                //     $this->hook,
                //     $currentTaskId,
                //     $this->planDeadline,
                //     $this->currentPlanEventName,
                // );
                $batchCommand =  $taskService->getUpdateTaskBatchCommand(
                    $this->domain,
                    $currentTaskId,
                    $this->planDeadline,
                );
                if (!empty($batchCommand)) {

                    array_push($batchCommands, $batchCommand);
                }
            }


            return $batchCommands;
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
            return $batchCommands;
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

        $planComment = $planComment . ' ' . $planEventTypeName . ' ' . $this->currentPlanEventName;
        if ($this->isNew || $this->isExpired) {
            $planComment .=  ' ' . $this->comment;
        }
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

    protected function getListBatchFlow()
    {

        $currentDealIds = [];
        $currentBaseDealId = null;
        $commands = [];

        date_default_timezone_set('Europe/Moscow');
        $currentNowDate = new DateTime();
        $nowDate = $currentNowDate->format('d.m.Y H:i:s');


        Log::channel('telegram')
            ->info('APRIL_HOOK init deadline', [
                'list initdeadline' => $this->planDeadline
            ]);
        $planDeadline = $this->planDeadline;
        if ($this->domain === 'alfacentr.bitrix24.ru') {

            $tmpDeadline = Carbon::createFromFormat('d.m.Y H:i:s', $planDeadline, 'Asia/Novosibirsk');
            $tmpDeadline = $tmpDeadline->setTimezone('Europe/Moscow');
            $planDeadline = $tmpDeadline->format('Y-m-d H:i:s');
        } else   if ($this->domain === 'gsirk.bitrix24.ru') {

            $tmpDeadline = Carbon::createFromFormat('d.m.Y H:i:s', $planDeadline, 'Asia/Irkutsk');
            $tmpDeadline = $tmpDeadline->setTimezone('Europe/Moscow');
            $planDeadline = $tmpDeadline->format('Y-m-d H:i:s');
        }
        Log::channel('telegram')->info('APRIL_HOOK list deadline', [
            'list result $this->planDeadline' => $planDeadline
        ]);


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
        } else {
            $planEventTypeName = $this->currentReportEventName;
            $planEventType = $this->currentReportEventType;
        }

        $planComment = $planComment . ' ' . $planEventTypeName . ' ' . $this->currentPlanEventName;
        if ($this->isNew || $this->isExpired) {
            $planComment .=  ' ' . $this->comment;
        }
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

                    $deadline = $planDeadline;


                    if (!$this->isPlanned) {
                        $deadline = null;
                    }


                    $currentNowDate->modify('+1 second');
                    $nowDate = $currentNowDate->format('d.m.Y H:i:s');
                    $commands = BitrixListFlowService::getBatchListFlow(  //report - отчет по текущему событию
                        $this->hook,
                        $this->bitrixLists,
                        $reportEventType,
                        $reportEventTypeName,
                        $reportAction,
                        // $this->stringType,
                        $deadline, //'', //$this->planDeadline,
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
                        $currentBaseDealId,
                        $nowDate, // $date,
                        null, // $event['eventType'], //$hotName
                        $this->reportContactId,
                        $commands

                    );
                }

                //если была проведена презентация - не важно какое текущее report event
            }
        }
        // Log::channel('telegram')->info('HOOK TST', [
        //     'isPresentationDone' => $this->isPresentationDone
        // ]);

        if ($this->isPresentationDone == true && !$this->isExpired) {
            //если была проведена през
            if ($reportEventType !== 'presentation') {
                //если текущее событие не през - значит uplanned
                //значит надо запланировать през в холостую


                $currentNowDate->modify('+2 second');
                $nowDate = $currentNowDate->format('d.m.Y H:i:s');

                $commands = BitrixListFlowService::getBatchListFlow(  //report - отчет по текущему событию
                    $this->hook,
                    $this->bitrixLists,
                    'presentation',
                    'Презентация',
                    'plan',
                    // $this->stringType,
                    $nowDate, //'', //$this->planDeadline,
                    $this->planResponsibleId,
                    $this->planResponsibleId,
                    $this->planResponsibleId,
                    $this->entityId,
                    'незапланированая презентация',
                    ['code' => 'inJob'],
                    'result', // result noresult expired,
                    $this->noresultReason,
                    $this->failReason,
                    $this->failType,
                    $currentDealIds,
                    $currentBaseDealId,
                    $nowDate, // $date,
                    null, // $event['eventType'], //$hotName
                    $this->reportContactId,
                    $commands

                );
            }

            $deadline = $planDeadline;
            if (!$this->isPlanned) {
                $deadline = null;
            }

            $currentNowDate->modify('+3 second');
            $nowDate = $currentNowDate->format('d.m.Y H:i:s');
            $commands = BitrixListFlowService::getBatchListFlow(  //report - отчет по текущему событию
                $this->hook,
                $this->bitrixLists,
                'presentation',
                'Презентация',
                'done',
                // $this->stringType,
                $deadline, //'', //$this->planDeadline,
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
                $currentBaseDealId,
                $nowDate, // $date,
                null, // $event['eventType'], //$hotName
                $this->reportContactId,
                $commands

            );

            $curTMCDeal = null;
            //если есть тмц сделка создаем эдемент списка о проведенной презентации 
            if (!empty($this->currentTMCDealFromCurrentPres)) {
                $curTMCDeal = $this->currentTMCDealFromCurrentPres;
            }

            if (!empty($curTMCDeal)) {
                if (!empty($curTMCDeal['ASSIGNED_BY_ID'])) {
                    $tmcUserId = $curTMCDeal['ASSIGNED_BY_ID'];
                    $currentNowDate->modify('+4 second');
                    $nowDate = $currentNowDate->format('d.m.Y H:i:s');
                    $commands = BitrixListFlowService::getBatchListFlow(  //report - отчет по текущему событию
                        $this->hook,
                        $this->bitrixLists,
                        'presentation',
                        'Презентация',
                        'done',
                        // $this->stringType,
                        $planDeadline, //'', //$this->planDeadline,
                        $tmcUserId,
                        $tmcUserId,
                        $this->planResponsibleId,
                        $this->entityId,
                        'Презентация по заявке ТМЦ' . $this->comment,
                        $this->workStatus['current'],
                        $this->resultStatus, // result noresult expired,
                        $this->noresultReason,
                        $this->failReason,
                        $this->failType,
                        $currentDealIds,
                        $currentBaseDealId,
                        $nowDate, // $date,
                        null, // $event['eventType'], //$hotName
                        $this->reportContactId,
                        $commands

                    );
                }
            }
        }



        if (!$this->isSuccessSale && !$this->isFail) {

            if ($this->isPlanned) {


                $currentNowDate->modify('+5 second');
                $nowDate = $currentNowDate->format('d.m.Y H:i:s');
                $commands = BitrixListFlowService::getBatchListFlow(  //report - отчет по текущему событию
                    $this->hook,
                    $this->bitrixLists,
                    $planEventType,
                    $planEventTypeName,
                    $eventAction,
                    // $this->stringType,
                    $planDeadline, //'', //$this->planDeadline,
                    $this->planResponsibleId,
                    $this->planResponsibleId,
                    $this->planResponsibleId,
                    $this->entityId,
                    $planComment,
                    $this->workStatus['current'],
                    $this->resultStatus, // result noresult expired,
                    $this->noresultReason,
                    $this->failReason,
                    $this->failType,
                    $currentDealIds,
                    $currentBaseDealId,
                    $nowDate, // $date,
                    null, // $event['eventType'], //$hotName
                    $this->planContactId,

                    $commands

                );
            }
        }


        if ($this->isSuccessSale || $this->isFail) {

            $eventType = 'success';
            if (!empty($this->isSuccessSale)) {
                $eventType = 'success';
            } else  if (!empty($this->isFail)) {
                $eventType = 'fail';
            }
            $currentNowDate->modify('+7 second');
            $nowDate = $currentNowDate->format('d.m.Y H:i:s');
            $commands = BitrixListFlowService::getBatchListFlow(  //report - отчет по текущему событию
                $this->hook,
                $this->bitrixLists,
                $eventType,
                $planEventTypeName,
                'done',
                // $this->stringType,
                $planDeadline, //'', //$this->planDeadline,
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
                $currentBaseDealId,
                $nowDate,  // $date,
                null, // $event['eventType'], //$hotName
                $this->reportContactId,

                $commands

            );
        }

        $batchService = new BitrixBatchService($this->hook);
        $batchService->sendGeneralBatchRequest($commands);
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

    protected function getListPresentationFlowBatch(
        $planPresDealIds,
        $batchCommands
    ) {
        $currentTask = $this->currentTask;
        $currentDealIds = $planPresDealIds['planDeals'];
        $currentRepoertDealIds = $planPresDealIds['reportDeals'];
        $unplannedPresDealsIds = $planPresDealIds['unplannedPresDeals'];
        date_default_timezone_set('Europe/Moscow');
        $currentNowDate = new DateTime();
        $nowDate = $currentNowDate->format('d.m.Y H:i:s');
        Log::channel('telegram')
            ->info('APRIL_HOOK init deadline', [
                'pres initdeadline' => $this->planDeadline
            ]);
        $planDeadline = $this->planDeadline;
        if ($this->domain === 'alfacentr.bitrix24.ru') {

            $tmpDeadline = Carbon::createFromFormat('d.m.Y H:i:s', $planDeadline, 'Asia/Novosibirsk');
            $tmpDeadline = $tmpDeadline->setTimezone('Europe/Moscow');
            $planDeadline = $tmpDeadline->format('Y-m-d H:i:s');
        } else   if ($this->domain === 'gsirk.bitrix24.ru') {

            $tmpDeadline = Carbon::createFromFormat('d.m.Y H:i:s', $planDeadline, 'Asia/Irkutsk');
            $tmpDeadline = $tmpDeadline->setTimezone('Europe/Moscow');
            $planDeadline = $tmpDeadline->format('Y-m-d H:i:s');
        }
        Log::channel('telegram')->info('APRIL_HOOK list deadline', [
            'presresult $this->planDeadline' => $planDeadline
        ]);
        // Log::channel('telegram')->info('HOOK TEST COLD BATCH', [
        //     'planDeals' => $planPresDealIds['planDeals'],


        // ]);
        // Log::info('HOOK TEST COLD BATCH', [
        //     'reportDeals' => $planPresDealIds['reportDeals'],


        // ]);



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
        // Log::channel('telegram')->error('APRIL_HOOK getListPresentationFlowBatch', [

        //     'currentPlanEventType' => $this->currentPlanEventType,
        //     'planTmcId' => $this->planTmcId,

        // ]);

        if (  //планируется презентация без переносов
            $this->currentPlanEventType == 'presentation' &&
            $this->isPlanned && !$this->isExpired
        ) { //plan
            $eventType = 'plan';

            $batchCommands =  BitrixListPresentationFlowService::getListPresentationPlanFlowBatch(
                $this->domain,
                $this->hook,
                $this->bitrixLists,
                $currentDealIds,
                $nowDate,
                $eventType,
                $planDeadline,
                $this->planCreatedId,
                $this->planResponsibleId,
                $this->planTmcId,
                $this->entityId,
                $this->comment,
                $this->currentPlanEventName,
                $this->workStatus['current'],
                $this->resultStatus, // result noresult expired,
                $batchCommands,
                // $this->noresultReason,
                // $this->failReason,
                // $this->failType


            );
        }

        // sleep(1);
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
                $batchCommands =   BitrixListPresentationFlowService::getListPresentationReportFlowBatch(
                    $this->domain,
                    $this->hook,
                    $this->bitrixLists,
                    $currentRepoertDealIds,
                    // $reportStatus,
                    $this->isPresentationDone,

                    $nowDate,
                    $eventType,
                    $this->isExpired,
                    $planDeadline,
                    $this->planCreatedId,
                    $this->planResponsibleId,
                    $this->entityId,
                    $this->comment,
                    $this->currentPlanEventName,
                    $this->workStatus['current'],
                    $this->resultStatus, // result noresult expired,
                    $this->noresultReason,
                    $this->failReason,
                    $this->failType,
                    $batchCommands


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
                $batchCommands = BitrixListPresentationFlowService::getListPresentationUnplannedtFlowBatch(
                    $this->domain,
                    $this->hook,
                    $this->bitrixLists,
                    $currentDealIds, //planDeals || unplannedDeals если през была незапланированной
                    $nowDate,
                    $this->planResponsibleId,
                    $this->entityId,
                    $this->comment,
                    $this->workStatus['current'],
                    $this->failReason,
                    $this->failType,
                    $batchCommands
                );
            }
            // sleep(1);

            if ($this->currentReportEventType === 'presentation') {
                // если была проведена презентация вне зависимости от текущего события
                $batchCommands = BitrixListPresentationFlowService::getListPresentationReportFlowBatch(
                    $this->domain,
                    $this->hook,
                    $this->bitrixLists,
                    $currentDealIds, //planDeals || unplannedDeals если през была незапланированной
                    // $reportStatus,
                    $this->isPresentationDone,

                    $nowDate,
                    'report',
                    $this->isExpired,
                    $planDeadline,
                    $this->planCreatedId,
                    $this->planResponsibleId,
                    $this->entityId,
                    $this->comment,
                    $this->currentPlanEventName,
                    $this->workStatus['current'],
                    $this->resultStatus, // result noresult expired,
                    $this->noresultReason,
                    $this->failReason,
                    $this->failType,
                    $batchCommands
                );
            }
        }
        return $batchCommands;
    }

    protected function setTimeLine()
    {
        $timeLineService = new BitrixTimeLineService($this->hook);
        $timeLineString = '';
        $planEventType = $this->currentPlanEventType; //если перенос то тип будет автоматически взят из report - предыдущего события
        $eventAction = '';  // не состоялся и двигается крайний срок 
        $planComment = '';
        $planEventTypeName = $this->currentPlanEventTypeName;
        $date = $this->planDeadline; // Предположим, это ваша дата
        // Создаем объект Carbon из строки
        $carbonDate = Carbon::createFromFormat('d.m.Y H:i:s', $date);

        // Устанавливаем локализацию
        $carbonDate->locale('ru');

        // Преобразуем в нужный формат: "1 ноября 12:30"
        $formattedDate = $carbonDate->isoFormat('D MMMM HH:mm');


        if ($this->isPlanned) {
            if (!$this->isExpired) {  // если не перенос, то отчитываемся по прошедшему событию
                //report
                $eventAction = 'plan';
                $planComment = 'Запланирован';
                if ($this->currentPlanEventTypeName == 'Презентация') {
                    $planComment = 'Запланирована';
                }
            } else {
                $eventAction = 'expired';  // не состоялся и двигается крайний срок 
                $planComment = 'Перенесен';
                $planEventTypeName = $this->currentReportEventName;

                if ($this->currentReportEventName == 'Презентация') {
                    $planComment = 'Перенесена';
                }
            }
            $planComment = $planComment . ' ' . $planEventTypeName . ' на ' . $formattedDate;
        } else {
            $reportAction = 'done';
            $reportComment = 'Coстоялся';
            if ($this->resultStatus !== 'result') {
                $reportAction = 'nodone';
                $reportComment = 'Не состоялся';
            }

            if (!empty($this->currentReportEventName)) {

                if ($this->currentReportEventName == 'Презентация') {
                    if ($reportComment == 'Coстоялся') {
                        $reportComment = 'Coстоялась';
                    } else if ($reportComment == 'Не состоялся') {
                        $reportComment = 'Не состоялась';
                    }
                }
                $planComment = $reportComment . ' ' . $this->currentReportEventName;
            }
        }





        $planComment = $planComment .  "\n" . $this->comment;


        if (!empty($this->currentBaseDeal)) {
            if (!empty($this->currentBaseDeal['ID'] && !empty($this->currentBaseDeal['TITLE']))) {
                $dealId = $this->currentBaseDeal['ID'];
                $dealTitle = $this->currentBaseDeal['TITLE'];
                $dealLink = 'https://' . $this->domain . '/crm/deal/details/' . $dealId . '/';
                $message = "\n" . 'Сделка: <a href="' . $dealLink . '" target="_blank">' . $dealTitle . '</a>';
            }
        }
        $messagePlanContact = null;
        $messageReportContact = null;

        if (!empty($this->reportContact) && !empty($this->reportContactId)) {
            $reportContactId = $this->reportContactId;
            $reportContactName = $this->reportContact['NAME'];

            $reportContactLink = 'https://' . $this->domain . '/crm/contact/details/' . $reportContactId . '/';
            $messageReportContact = '   Контакты: <a href="' . $reportContactLink . '" target="_blank">' . $reportContactName . '</a>';
        }


        if (!empty($this->planContact) && !empty($this->planContactId)) {
            if ($this->reportContactId !== $this->planContactId) {


                $planContactId = $this->planContactId;
                $planContactName = $this->planContact['NAME'];

                $planContactLink = 'https://' . $this->domain . '/crm/contact/details/' . $planContactId . '/';
                if (!empty($this->reportContact) && !empty($this->reportContactId)) {
                    $messagePlanContact = ', <a href="' . $planContactLink . '" target="_blank">' . $planContactName . '</a>';
                } else {
                    $messagePlanContact = '   Контакты:  <a href="' . $planContactLink . '" target="_blank">' . $planContactName . '</a>';
                }
            }
        }



        $timeLineString =  $planComment;
        if (!empty($message)) {

            $timeLineString .= $message;
        }
        if (!empty($messageReportContact)) {

            $timeLineString .= $messageReportContact;
        }
        if (!empty($messagePlanContact)) {

            $timeLineString .= $messagePlanContact;
        }
        // Log::channel('telegram')->info('HOOK TIME LINE', ['set' => $timeLineString]);

        // Log::info('HOOK TIME LINE', ['set' => $timeLineString]);
        if (!empty($timeLineString)) {
            $timeLineService->setTimeLine($timeLineString, 'company', $this->entityId);
        }
    }

    protected function getFullEventComment()
    {

        $planComment = '';
        $planEventTypeName =  $this->removeEmojisIntl($this->currentPlanEventTypeName);
        $date = $this->planDeadline; // Предположим, это ваша дата
        // Создаем объект Carbon из строки
        $carbonDate = Carbon::createFromFormat('d.m.Y H:i:s', $date);

        // Устанавливаем локализацию
        $carbonDate->locale('ru');

        // Преобразуем в нужный формат: "1 ноября 12:30"
        $formattedDate = $carbonDate->isoFormat('D MMMM HH:mm');


        if ($this->isPlanned) {
            if (!$this->isExpired) {  // если не перенос, то отчитываемся по прошедшему событию
                //report
                $eventAction = 'plan';
                $planComment = 'Запланирован';
                if ($this->removeEmojisIntl($this->currentPlanEventTypeName) == 'Презентация') {
                    $planComment = 'Запланирована';
                }
            } else {
                $eventAction = 'expired';  // не состоялся и двигается крайний срок 
                $planComment = 'Перенесен';
                $planEventTypeName = $this->currentReportEventName;

                if ($this->currentReportEventName == 'Презентация') {
                    $planComment = 'Перенесена';
                }
            }
            $planComment = $planComment . ' ' . $planEventTypeName . ' на ' . $formattedDate;
        } else {
            $reportAction = 'done';
            $reportComment = 'Coстоялся';
            if ($this->resultStatus !== 'result') {
                $reportAction = 'nodone';
                $reportComment = 'Не состоялся';
            }

            if (!empty($this->currentReportEventName)) {
                if ($this->currentReportEventName == 'Презентация') {
                    if ($reportComment == 'Coстоялся') {
                        $reportComment = 'Coстоялась';
                    } else if ($reportComment == 'Не состоялся') {
                        $reportComment = 'Не состоялась';
                    }
                }
                $planComment = $reportComment . ' ' . $this->currentReportEventName;
            }
        }

        if (!empty($this->workStatus['current'])) {
            if (!empty($this->workStatus['current']['code'])) {
                $workStatusCode = $this->workStatus['current']['code'];


                if ($workStatusCode === 'fail') {  //если провал 
                    $planComment = 'ОП ОТКАЗ ' . $planComment .  "\n" . $this->comment;
                } else {

                    $planComment = 'ОП ' . $planComment .  "\n" . $this->comment;
                }
            }
        }

        APIOnlineController::sendLog('getFullEventComment', [

            'planComment' => $planComment,
            'work status' => $this->workStatus['current'],
            'domain' => $this->domain,


        ]);
        return $planComment;
    }

    protected function removeEmojisIntl($string) {
        $result = '';
        $len = mb_strlen($string, 'UTF-8');
        
        for ($i = 0; $i < $len; $i++) {
            $char = mb_substr($string, $i, 1, 'UTF-8');
            $code = IntlChar::ord($char);
            
            // Удаляем эмодзи по диапазонам Unicode
            if (
                ($code >= 0x1F600 && $code <= 0x1F64F) ||  // Эмодзи эмоций
                ($code >= 0x1F300 && $code <= 0x1F5FF) ||  // Символы и пиктограммы
                ($code >= 0x1F680 && $code <= 0x1F6FF) ||  // Транспорт
                ($code >= 0x2600 && $code <= 0x26FF)   ||  // Разные символы
                ($code >= 0x2700 && $code <= 0x27BF)   ||  // Дополнительные символы
                ($code >= 0x1F1E0 && $code <= 0x1F1FF)     // Флаги
            ) {
                continue; // Пропускаем эмодзи
            }
    
            $result .= $char;
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