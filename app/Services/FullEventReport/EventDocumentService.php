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


    protected $isOfferDone = false;
    protected $isInvoiceDone = false;
    protected $isContractDone = false;
    protected $isSupplyReportDone = false;


    protected $isFromPresentation = false;

    protected $portalDealData = null;
    protected $portalCompanyData = null;

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
            Log::channel('telegram')->info('HOOK TEST EventDocumentService', [
                'rekvest presentation' => $data['presentation'],

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

            // if (isset($placement)) {
            //     if (!empty($placement['placement'])) {
            //         if ($placement['placement'] == 'CALL_CARD') {
            //             if (!empty($placement['options'])) {
            //                 if (!empty($placement['options']['CRM_BINDINGS'])) {
            //                     foreach ($placement['options']['CRM_BINDINGS'] as $crmBind) {
            //                         if (!empty($crmBind['ENTITY_TYPE'])) {
            //                             if ($crmBind['ENTITY_TYPE'] == 'LEAD') {
            //                                 $entityType = 'lead';
            //                                 $entityId = $crmBind['ENTITY_ID'];
            //                             }
            //                             if ($crmBind['ENTITY_TYPE'] == 'COMPANY') {
            //                                 $entityType = 'company';
            //                                 $entityId = $crmBind['ENTITY_ID'];
            //                                 break;
            //                             }
            //                         }
            //                     }
            //                 }
            //             }
            //         } else if (strpos($placement['placement'], 'COMPANY') !== false) {
            //             $entityType = 'company';
            //             $entityId = $placement['options']['ID'];
            //         } else if (strpos($placement['placement'], 'LEAD') !== false) {
            //             $entityType = 'lead';
            //             $entityId = $placement['options']['ID'];
            //         }
            //     }
            // }







            $portal = PortalController::getPortal($domain);
            $portal = $portal['data'];
            $this->portal = $portal;

            $this->isFromPresentation = $data['isFromPresentation'];


            // $this->aprilSmartData = $portal['bitrixSmart'];
            $this->portalCompanyData = $portal['company'];
            $this->portalDealData = $portal['bitrixDeal'];




            $webhookRestKey = $portal['C_REST_WEB_HOOK_URL'];
            $this->hook = 'https://' . $domain  . '/' . $webhookRestKey;


            $sessionKey = 'document_' . $domain . $userId . '_' . $baseDealId;

            $sessionData = FullEventInitController::getSessionItem($sessionKey);


            if (isset($sessionData['currentCompany']) && isset($sessionData['deals'])) {
                $this->currentBtxEntity  = $sessionData['currentCompany'];


                $this->entityType = 'company';


                if (isset($this->currentBtxEntity['ID'])) {
                    $this->entityId = $this->currentBtxEntity['ID'];
                }


                if (isset($this->currentBtxEntity['id'])) {
                    $this->entityId = $this->currentBtxEntity['id'];
                }




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


            Log::channel('telegram')->info('HOOK TEST sessionData', [

                'invoiceData' => $data['invoice'],


            ]);
            $this->isOfferDone = true;
            if (!empty($data['invoice'])) {

                Log::channel('telegram')->info('HOOK TEST sessionData', [

                    'invoiceData' => $data['invoice'],


                ]);
                if (!empty($data['invoice']['one'])) {
                    if (!empty($data['invoice']['one']['value'])) {
                        $this->isInvoiceDone = true;
                    }
                }
            }

            sleep(1);

            Log::channel('telegram')->info('HOOK TEST sessionData', [
                'isOfferDone' => $this->isOfferDone,
                'isInvoiceDone' => $this->isInvoiceDone,
                'currentBaseDeal' => $this->currentBaseDeal,



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
                    'portalDealData' =>  $this->portalDealData,

                ]
            ]);
            // if(!$this->smartId){

            // }
            // $randomNumber = rand(1, 2);

            // if ($this->isSmartFlow) {
            //     $this->getSmartFlow();
            // }

            if ($this->portalDealData) {
                $currentDealsIds = $this->getDealFlow();
                // обновляет основную сделку стадию в документ
                // если менее чем документ
            }

            // $this->createTask($currentSmartId);
            // if ($this->isExpired || $this->isPlanned) {
            //     $result = $this->taskFlow(null, $currentDealsIds['planDeals']);
            // } else {
            //     $result = $this->workStatus;
            // }
            $this->getEntityFlow();
            // обновляет поля связанные с документом kpi
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



    // document entity flow
    protected function getEntityFlow($isDeal = false, $deal = null)
    {
        // Количество КП	statistics_op	integer		op_offer_q
        // Количество Счетов	statistics_op	integer		op_invoice_q
        // Количество Договоров	statistics_op	integer		op_contract_q
        // Количество Поставок	statistics_op	integer		op_supplies_q
        // Количество КП после презентации	statistics_op	integer		op_offer_pres_q
        // Количество Счетов после презентации	statistics_op	integer		op_invoice_pres_q
        // Дата отправки КП	document	datetime		op_offer_date
        // Дата отправки Счета	document	datetime		op_invoice_date
        // Дата отправки Договора	document	datetime		op_contract_date
        // Сумма предложения	only_deals	money		offer_sum
        // Корневая сделка Продажи	only_deals	crm		to_base_sales
        // Сделка ХО Продажи	only_deals	crm		to_xo_sales
        // Сделка Презентации Продажи	only_deals	crm		to_presentation_sales
        // Корневая сделка ТМЦ	only_deals	crm		to_base_tmc
        // Сделка Презентации ТМЦ	only_deals	crm		to_presentation_tmc
        // Корневая сделка Сервис	only_deals	crm		to_base_service
        // ОП Текущий статус	op_current_status	string		op_current_status

        $complectName = null;
        $supply = null;
        $isFromPresentation = false;


        $currentBtxEntity = $this->currentBtxEntity;
        $entityType = $this->entityType;
        $entityId = $this->entityId;

        $portalEntityData = $this->portalCompanyData;


        $reportFields = [];
        $reportFields['manager_op'] = $this->planResponsibleId;
        $reportFields['op_work_status'] = '';
        $reportFields['op_prospects_type'] = '';



        $entityOfferCount = 0;
        $entityInvoiceCount = 0;
        $entityContractCount = 0;
        $entityPresOfferCount = 0;
        $entityPresInvoiceCount = 0;


        if ($isDeal && !empty($deal) && !empty($deal['ID'])) {


            $currentBtxEntity = $deal;
            $entityType = 'deal';
            $entityId =  $deal['ID'];
            $portalEntityData = $this->portalDealData;
        }


        //get current document counts
        if (!empty($currentBtxEntity['UF_CRM_OP_OFFER_Q'])) {
            $entityOfferCount  = $currentBtxEntity['UF_CRM_OP_OFFER_Q'];
        }
        if (!empty($currentBtxEntity['UF_CRM_OP_INVOICE_Q'])) {
            $entityInvoiceCount  = $currentBtxEntity['UF_CRM_OP_INVOICE_Q'];
        }

        if (!empty($currentBtxEntity['UF_CRM_OP_OFFER_PRES_Q'])) {
            $entityPresOfferCount  = $currentBtxEntity['UF_CRM_OP_OFFER_PRES_Q'];
        }
        if (!empty($currentBtxEntity['UF_CRM_OP_INVOICE_PRES_Q'])) {
            $entityPresInvoiceCount  = $currentBtxEntity['UF_CRM_OP_INVOICE_PRES_Q'];
        }

        if (!empty($deal['UF_CRM_OP_CONTRACT_Q'])) {
            $entityContractCount  = $deal['UF_CRM_OP_CONTRACT_Q'];
        }



        $currentPresComments = [];
        $currentFailComments = [];
        $currentComments = [];
        if (isset($currentBtxEntity)) {
            if ($this->isPresentationDone) {
                if (!empty($currentBtxEntity['UF_CRM_PRES_COMMENTS'])) {
                    $currentPresComments = $currentBtxEntity['UF_CRM_PRES_COMMENTS'];
                }
            }


            if (!empty($currentBtxEntity['UF_CRM_OP_MHISTORY'])) {
                $currentComments = $currentBtxEntity['UF_CRM_OP_MHISTORY'];
            }


            if (!empty($currentBtxEntity['UF_CRM_OP_OFFER_Q'])) { //количество кп
                $currentComments = $currentBtxEntity['UF_CRM_OP_OFFER_Q'];
            }



            if ($isFromPresentation) {
                if (!empty($currentBtxEntity['UF_CRM_OP_OFFER_PRES_Q'])) { //количество кп
                    $currentComments = $currentBtxEntity['UF_CRM_OP_OFFER_PRES_Q'];
                }
                if (!empty($currentBtxEntity['UF_CRM_OP_INVOICE_PRES_Q'])) { //количество кп
                    $currentComments = $currentBtxEntity['UF_CRM_OP_INVOICE_PRES_Q'];
                }
            }
        }
        // Log::channel('telegram')->info('TST', [
        //     'currentPresComments' => $currentPresComments,
        //     'currentFailComments' => $currentFailComments,
        // ]);


        // isOfferDone
        // isInvoiceDone
        // isContractDone
        // isSupplyReportDone
        $reportFields['pres_comments'] = $currentComments;
        if ($this->isOfferDone) {

            $reportFields['op_offer_q'] = $entityOfferCount + 1; //количество КП
            $reportFields['op_current_status'] = 'Коммерческое предложение';
            if ($isFromPresentation) {
                $reportFields['op_offer_pres_q'] =   $entityPresOfferCount + 1; //количество КП
                $reportFields['pres_comments'] = $currentPresComments;
                $reportFields['op_current_status'] = 'Коммерческое предложение после презентации';
            }

            $reportFields['op_offer_date'] = $this->nowDate;
        }
        if ($this->isInvoiceDone) {

            $reportFields['op_invoice_q'] = $entityInvoiceCount + 1; //количество 
            $reportFields['op_current_status'] = 'Счет';

            if ($isFromPresentation) {
                $reportFields['op_invoice_pres_q'] =   $entityPresInvoiceCount + 1; //количество после през
                $reportFields['op_current_status'] = 'Счет предложение после презентации';
            }

            $reportFields['op_invoice_date'] = $this->nowDate;
        }
        $reportFields['pres_comments'] = $currentComments . ' |' . $this->nowDate . ' ' . $reportFields['op_current_status'];
        // if ($this->isContractDone) {
        // $reportFields['op_current_status'] = 'Договор';
        //     $reportFields['op_contract_q'] = $currentContractCount + 1; //количество 
        //     $reportFields['op_contract_date'] = $this->nowDate;
        //     $reportFields['pres_comments'] = $currentPresComments;

        // }


        $entityService = new BitrixEntityFlowService();


        Log::channel('telegram')->error('APRIL_HOOK COLD cold sevice', [
            'data' => [

                'reportFields' => $reportFields,

            ]
        ]);

        $entityService->documentFlowflow(
            $currentBtxEntity,
            $portalEntityData,
            $this->hook,
            $entityType,
            $entityId,

            $this->planResponsibleId,
            // $this->planDeadline,
            // $this->nowDate,

            'inJob', // $this->workStatus['current']['code'],  // inJob setAside ...
            'result', //result | noresult ...

            // $this->comment,
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

        // в зависимости от условий сделка в итоге попадает либо в plan либо в report deals
        $this->getEntityFlow(true, $this->currentBaseDeal);
        if ($this->currentPresDeal) {
            $this->getEntityFlow(true, $this->currentPresDeal);
        }


        return true;
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