<?php

namespace App\Services\FullEventReport;

use App\Http\Controllers\APIOnlineController;
use App\Http\Controllers\Front\Calling\FullEventInitController;
use App\Http\Controllers\Front\Calling\ReportController;
use App\Http\Controllers\PortalController;
use App\Jobs\BtxCreateListItemJob;
use App\Services\BitrixTaskService;
use App\Services\General\BitrixDealService;
use App\Services\General\BitrixDepartamentService;
use App\Services\HookFlow\BitrixDealFlowService;
use App\Services\HookFlow\BitrixEntityFlowService;
use App\Services\HookFlow\BitrixListPresentationFlowService;
use DateTime;
use Illuminate\Console\View\Components\Task;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Log;

class EventDocumentService

{
    protected $portal;
    protected $hook;
    protected $domain;
    protected $entityType;
    protected $entityId;


    protected $resultStatus;  // result noresult expired

    protected $currentReportEventType; // currentTask-> eventType xo  
    // todo если нет текущей задачи значит нужно брать report event type из списка типа событий отчета
    // в котором могут быть входящий звонок и тд
    // или пока просто можно воспринимать как NEW 
    protected $currentReportEventName = '';

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


    //sale

    protected $relationSalePresDeal = null;


    // deals
    protected $currentBaseDeal;
    protected $currentPresDeal;
    protected $currentColdDeal;
    protected $currentTMCDeal;

    protected $relationBaseDeals;
    protected $relationCompanyUserPresDeals; //allPresDeals
    protected $relationFromBasePresDeals;
    protected $relationColdDeals;
    protected $relationTMCDeals;

    public function __construct(

        $data,

    ) {


        if (
            // !empty($data['userId'])  &&
            // !empty($data['companyId']) &&
            !empty($data['domain']) &&
            !empty($data['dealId']) &&
            !empty($data['companyId']) &&
            !empty($data['userId'])


        ) {
            date_default_timezone_set('Europe/Moscow');

            $domain = $data['domain'];
            $baseDealId = $data['dealId'];
            $companyId = $data['companyId'];
            $userId = $data['userId'];
            // domain,
            // companyId: companyId,
            // placement: placement,
            // dealId: newDealId,
            // userId,
            // price,
            // supply,

            // manager: state.document.manager,
            // invoice: invoiceData,
            // isPublic: IS_PUBLIC || IS_MAX_DEV,
            // salePhrase,
            // invoiceDate,
            // withStamps: withStamps,
            // isWord,
            // currentComplect,
            Log::info('HOOK TEST EventDocumentService', [
                'rekvest' => $data,



            ]);

            //flow события документ
            //какие документы были сделаны
            //включать в отчет по презентации ?
            // с какой презентацией связывать







            $nowDate = new DateTime();
            // Форматируем дату и время в нужный формат
            $this->nowDate = $nowDate->format('d.m.Y H:i:s');


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



            $this->isUnplannedPresentation = false;

          if(isset($data['presentation'])){
            $this->currentPresDeal = $data['presentation'];

          }

            // Log::info('HOOK TEST sessionData', [
            //     'sessionData' => $sessionData

            // ]);
            // Log::channel('telegram')->info('HOOK TEST sessionData', [
            //     'task from session' => $sessionData['currentTask']

            // ]);
            $portal = PortalController::getPortal($domain);
            $portal = $portal['data'];
            $this->portal = $portal;

          


            // $this->aprilSmartData = $portal['bitrixSmart'];
            $this->portalCompanyData = $portal['company'];





            $webhookRestKey = $portal['C_REST_WEB_HOOK_URL'];
            $this->hook = 'https://' . $domain  . '/' . $webhookRestKey;


            $sessionKey = 'document_' . $domain . $userId . '_' . $baseDealId;

            $sessionData = FullEventInitController::getSessionItem($sessionKey);

            Log::info('HOOK TEST EventDocumentService sessionData try first', [
                'sessionData' => $sessionData,



            ]);
            if (isset($sessionData['currentCompany']) && isset($sessionData['deals'])) {
                $this->currentBtxEntity  = $sessionData['currentCompany'];


                $sessionDeals = $sessionData['deals'];
            }
            if (
                isset($sessionDeals['currentBaseDeal']) &&
                isset($sessionDeals['allBaseDeals'])
                // isset($sessionDeals['currentPresentationDeal']) &&
                // isset($sessionDeals['basePresentationDeals']) &&
                // isset($sessionDeals['allPresentationDeals']) &&
                // // isset($sessionDeals['presList']) &&
                // isset($sessionDeals['currentXODeal']) &&
                // isset($sessionDeals['allXODeals']) &&
                // isset($sessionDeals['currentTaskDeals'])


            ) {


                // $this->currentBtxDeals  = $sessionDeals['currentTaskDeals'];

                $this->currentBaseDeal = $sessionDeals['currentBaseDeal'];
                $this->currentPresDeal = $sessionDeals['currentPresentationDeal'];
                // $this->currentColdDeal = $sessionDeals['currentXODeal'];


                // $this->relationBaseDeals = $sessionDeals['allBaseDeals'];
                $this->relationCompanyUserPresDeals = $sessionDeals['allPresentationDeals']; //allPresDeal 
                $this->relationFromBasePresDeals = $sessionDeals['basePresentationDeals'];
                // $this->relationColdDeals = $sessionDeals['allXODeals'];
            }


            Log::info('HOOK TEST EventDocumentService', [
                'sessionData' => $sessionData,
                'currentBtxDeals' => $this->currentBtxDeals


            ]);



            // if (!isset($sessionData['currentCompany'])) {
            //     $currentBtxEntities =  BitrixEntityFlowService::getEntities(
            //         $this->hook,
            //         $this->currentTask,
            //     );

            //     if (!empty($currentBtxEntities)) {
            //         if (!empty($currentBtxEntities['companies'])) {
            //             $currentBtxEntity = $currentBtxEntities['companies'][0];
            //         }
            //         if (!empty($currentBtxEntities['deals'])) {
            //             $currentBtxDeals = $currentBtxEntities['deals'];
            //         }
            //     }
            //     $this->currentBtxEntity  = $currentBtxEntity;
            //     $this->currentBtxDeals  = $currentBtxDeals;
            // }

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


            // if (!empty($data['sale'])) {

            //     if (!empty($data['sale']['relationSalePresDeal'])) {

            //         $this->relationSalePresDeal = $data['sale']['relationSalePresDeal'];
            //     }
            // }
        }
    }

    public function getDocumentFlow()
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
            Log::channel('telegram')->error('APRIL_HOOK getDocumentFlow', [
                'data' => [
                    'currentPresDeal' =>  $this->currentPresDeal,

                ]
            ]);
            // if(!$this->smartId){

            // }
            // $randomNumber = rand(1, 2);

            // if ($this->isSmartFlow) {
            //     $this->getSmartFlow();
            // }

            // if ($this->isDealFlow && $this->portalDealData) {
            //     $currentDealsIds = $this->getDealFlow();
            // }

            // $this->createTask($currentSmartId);
            // if ($this->isExpired || $this->isPlanned) {
            //     $result = $this->taskFlow(null, $currentDealsIds['planDeals']);
            // } else {
            //     $result = $this->workStatus;
            // }
            // $this->getEntityFlow();
            // sleep(1);


            // $this->getListFlow();
            sleep(1);
            // $this->getListPresentationFlow(
            //     $currentDealsIds
            // );

            return APIOnlineController::getSuccess(['result' => $this->workStatus]);
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
    protected function getEntityFlow($isDeal = false, $deal = null)
    {
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
        $reportFields['op_prospects_type'] = '';
        $reportFields['op_work_status'] = '';
        $reportFields['op_work_status'] = '';

        $currentPresCount = 0;
        $companyPresCount = 0;
        $dealPresCount = 0;
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
        }


        $currentPresComments = [];
        $currentFailComments = [];
        if (isset($currentBtxEntity)) {
            if (!empty($currentBtxEntity['UF_CRM_PRES_COMMENTS'])) {
                $currentPresComments = $currentBtxEntity['UF_CRM_PRES_COMMENTS'];
            }

            if (!empty($currentBtxEntity['UF_CRM_OP_FAIL_COMMENTS'])) {
                $currentFailComments = $currentBtxEntity['UF_CRM_OP_FAIL_COMMENTS'];
            }
        }
        // Log::channel('telegram')->info('TST', [
        //     'currentPresComments' => $currentPresComments,
        //     'currentFailComments' => $currentFailComments,
        // ]);

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

        //presentation done with unplanned
        if ($this->isPresentationDone) {
            array_push($currentPresComments, $this->comment);


            $reportFields['last_pres_done_date'] = $this->nowDate;
            $reportFields['last_pres_done_responsible'] =  $this->planResponsibleId;
            $reportFields['pres_count'] = $currentPresCount + 1;
            $reportFields['pres_comments'] = $currentPresComments;
            if ($currentReportEventType !== 'presentation') {
                $reportFields['last_pres_plan_date'] = $this->nowDate; //когда запланировали последнюю през
                $reportFields['last_pres_plan_responsible'] = $this->planResponsibleId;
                $reportFields['next_pres_plan_date'] = $this->nowDate;  //дата на которую запланировали през

            }
            $reportFields['op_current_status'] = 'Презентация проведена';
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




            switch ($currentPlanEventType) {
                case 'xo':
                    $reportFields['xo_date'] = $this->planDeadline;
                    $reportFields['xo_name'] = $this->currentPlanEventName;
                    break;

                case 'presentation':

                    $reportFields['last_pres_plan_date'] = $this->nowDate; //когда запланировали последнюю през
                    $reportFields['last_pres_plan_responsible'] = $this->planResponsibleId;
                    $reportFields['next_pres_plan_date'] = $this->planDeadline;  //дата на которую запланировали през
                    $reportFields['op_current_status'] = 'Презентация запланирована в работе';
                    break;
                default:
                    # code...
                    break;
            }
        } else {
        }


        // Log::channel('telegram')->info('TST', [
        //     'currentPresComments' => $currentPresComments,
        //     'currentFailComments' => $currentFailComments,
        // ]);


        // Log::channel('telegram')->info('TST', [
        //     'reportFields' => $reportFields,

        // ]);
        $presentationFields = [];





        $currentFieldsForUpdate = [];
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
            'op_prospects_type', //тип отказа  ОП Неперспективная
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
            // 'xo_created',
            // 'manager_op',
            // 'call_next_date',
            // 'call_next_name',
            // 'call_last_date',

        ];


        // $statusesCodesAssoc = array_fill_keys($statusesCodes, true);
        // $generalSalesCodesAssoc = array_fill_keys($generalSalesCode, true);
        // $fieldsCallCodesAssoc = array_fill_keys($fieldsCallCodes, true);

        // $presentationCodesAssoc = array_fill_keys($fieldsPresentationCodes, true);


        // Объединение массивов
        // $currentFieldsForUpdate = [];
        // $mergedFields = array_merge(
        //     $presentationCodesAssoc,
        //     $statusesCodesAssoc,
        //     $generalSalesCodesAssoc,
        //     $fieldsCallCodesAssoc
        // );
        // foreach ($mergedFields as $targedCode => $bool) {
        //     array_push($currentFieldsForUpdate, $targedCode);
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
            $this->resultStatus, //result | noresult ...
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



    //smart
    protected function getSmartFlow()
    {
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
        $unplannedPresDeals = null;
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
                'Запланирована',
                'plan',  // plan done expired fail
                $this->planResponsibleId,
                true,
                '$fields',
                null // $relationSalePresDeal
            );

            Log::info('HOOK TEST unplannedPresDeal', [
                'unplannedPresDeal' => $unplannedPresDeal,


            ]);
            Log::info('HOOK TEST currentBtxDeals', [
                'currentBtxDeals' => $this->currentBtxDeals,


            ]);
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
                    $unplannedPresDeals = BitrixDealFlowService::flow(  // закрывает сделку  - презентация обновляет базовую в соответствии с проведенной през
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
                    // Log::channel('telegram')->info('HOOK TEST CURRENTENTITY', [
                    //     'unplannedPresDeals' => $unplannedPresDeals,


                    // ]);
                    // Log::info('HOOK TEST CURRENTENTITY', [
                    //     'unplannedPresDeals' => $unplannedPresDeals,


                    // ]);
                    foreach ($this->currentBtxDeals as $cbtxdeal) {
                        if ($cbtxdeal['ID'] !== $unplannedPresDealId) {
                            sleep(1);
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
        sleep(1);


        //если был unplanned а потом plan ->
        //если warm plan а report был xo 
        // - то нужна обновленная стадия в базовой битрикс сделке что не пыталось повысить
        // с xo в warm так как уже на самом деле pres 
        // если plan pres -> планируется новая презентация и поэтому в  
        // $this->currentBtxDeals должна отсутствовать сделка презентации созданная при unplanned, 
        // которая пушится туда  при unplanned - чтобы были обработаны базовая сделка 
        // в соответствии с проведенной през
        // при этом у основной сделки должна быть обновлена стадия - например на през если была unplanned
        Log::info('HOOK TEST currentBtxDeals', [
            'currentBtxDeals' => $currentBtxDeals,
            'this currentBtxDeals' => $this->currentBtxDeals,


        ]);
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
            $currentReportStatus,  // plan done expired fail success
            $this->planResponsibleId,
            $this->isResult,
            '$fields',
            $this->relationSalePresDeal
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


        foreach ($currentBtxDeals as $currentBtxDeal) {
            sleep(1);
            Log::info('HOOK TEST currentBtxDeals', [
                'currentBtxDeal' => $currentBtxDeal,

            ]);
            $this->getEntityFlow(true, $currentBtxDeal);
        }


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
                '$fields',
                null // $relationSalePresDeal
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




    //tasks for complete



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
        }

        $planComment = $planComment . ' ' . $planEventTypeName . ' ' . $this->comment;

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
        // $currentTask = $this->currentTask;
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