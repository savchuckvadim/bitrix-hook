<?php

namespace App\Services\FullEventReport;

use App\Http\Controllers\APIBitrixController;
use App\Http\Controllers\APIOnlineController;
use App\Http\Controllers\Front\EventCalling\FullEventInitController;
use App\Http\Controllers\Front\Calling\ReportController;
use App\Http\Controllers\PortalController;
use App\Jobs\BtxCreateListItemJob;
use App\Services\BitrixGeneralService;
use App\Services\BitrixTaskService;
use App\Services\General\BitrixDealService;
use App\Services\General\BitrixDepartamentService;
use App\Services\HookFlow\BitrixDealFlowService;
use App\Services\HookFlow\BitrixEntityFlowService;
use App\Services\HookFlow\BitrixListDocumentFlowService;
use App\Services\HookFlow\BitrixListPresentationFlowService;
use DateTime;
use Illuminate\Console\View\Components\Task;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Http;
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
    protected $responsibleId;
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


    protected $productRows = null;
    protected $sum = 0;

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
            $this->responsibleId = $userId;
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

            //todo
            // rows


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
            $this->bitrixLists = $portal['bitrixLists'];



            $webhookRestKey = $portal['C_REST_WEB_HOOK_URL'];
            $this->hook = 'https://' . $domain  . '/' . $webhookRestKey;


            $sessionKey = 'document_' . $domain .  '_' . $baseDealId;
            Log::channel('telegram')->info('sessionKey', ['test done' => $sessionKey]);

            $sessionData = FullEventInitController::getSessionItem($sessionKey);
            Log::info('sessionData', ['sessionKey' => $sessionKey]);

            Log::info('sessionData', ['sessionData' => $sessionData]);

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
                isset($sessionDeals['currentBaseDeal'])
            ) {


                $this->currentBaseDeal = $sessionDeals['currentBaseDeal'];

                $this->relationCompanyUserPresDeals = $sessionDeals['allPresentationDeals']; //allPresDeal 
                $this->relationFromBasePresDeals = $sessionDeals['basePresentationDeals'];
            }

            if (!empty($data['presentation'])   && !empty($data['isFromPresentation'])) {

                $this->currentPresDeal = $data['presentation'];
            }

            if (!empty($data['rows'])) {

                $this->productRows = $data['rows'];
            }

            // Log::info('HOOK TEST rows', [
            //     'comming rows' => $data['rows'],

            // ]);

            $this->isOfferDone = true;
            if (!empty($data['invoice'])) {


                if (!empty($data['invoice']['one'])) {
                    if (!empty($data['invoice']['one']['value'])) {
                        $this->isInvoiceDone = true;
                    }
                }
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


            $this->getListFlow();
            sleep(1);
            if ($this->isFromPresentation && $this->currentPresDeal) {

                $this->getListPresentationFlow();
            }

            return APIOnlineController::getSuccess(['data' => ['result' => $this->workStatus, 'presInitLink' => null]]);
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
        $isFromPresentation = $this->isFromPresentation;


        $currentBtxEntity = $this->currentBtxEntity;
        $entityType = $this->entityType;
        $entityId = $this->entityId;

        $portalEntityData = $this->portalCompanyData;


        $reportFields = [];
        $reportFields['manager_op'] = $this->responsibleId;
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
        $currentComments = '';
        if (isset($currentBtxEntity)) {
            if ($this->isPresentationDone) {
                if (!empty($currentBtxEntity['UF_CRM_PRES_COMMENTS'])) {
                    $currentPresComments = $currentBtxEntity['UF_CRM_PRES_COMMENTS'];
                }
            }
        }

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
                $reportFields['op_current_status'] = 'Счет после презентации';
            }

            $reportFields['op_invoice_date'] = $this->nowDate;
        }
        $reportFields['pres_comments'] = $currentComments . ' | ' . $this->nowDate . ' ' . $reportFields['op_current_status'];
        // if ($this->isContractDone) {
        // $reportFields['op_current_status'] = 'Договор';
        //     $reportFields['op_contract_q'] = $currentContractCount + 1; //количество 
        //     $reportFields['op_contract_date'] = $this->nowDate;
        //     $reportFields['pres_comments'] = $currentPresComments;

        // }


        $entityService = new BitrixEntityFlowService();


        // Log::channel('telegram')->error('APRIL_HOOK COLD cold sevice', [
        //     'data' => [

        //         'reportFields' => $reportFields,

        //     ]
        // ]);

        $entityService->documentFlowflow(
            $currentBtxEntity,
            $portalEntityData,
            $this->hook,
            $entityType,
            $entityId,

            $this->responsibleId,
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
        $this->getEntityFlow(
            true,
            $this->currentBaseDeal,
            'base'
        );
        // Log::channel('telegram')->error('APRIL_HOOK get deal flow', ['currentPresDeal' => $this->currentPresDeal]);

        if (!empty($this->currentPresDeal) && !empty($this->currentPresDeal['ID'])) {
            $this->getEntityFlow(
                true,
                $this->currentPresDeal,
                'presentation'
            );
            // Log::info('APRIL_HOOK get deal flow', ['$this->productRows' => $this->productRows]);

            if (!empty($this->productRows)) {
                $productSetData = $this->getDealProductRows($this->productRows, $this->currentPresDeal['ID']);

                $methodProductSet = '/crm.item.productrow.set.json';
                $url = $this->hook . $methodProductSet;

                $response = Http::get($url, $productSetData);
                return APIBitrixController::getBitrixRespone($response, 'EVENT DOCUMENT SERVICE gert deal flow products Set');
            }
        } else {

            // if(!empty($this->currentBaseDeal['ID'])){
            // if(!empty($this->currentBaseDeal['ID'])){
            // /
            $this->getDealProductRows($this->productRows, 0); //for update this-> comment with complect price
            // }





        }

        BitrixDealFlowService::flow( //создает сделку
            $this->hook,
            [$this->currentBaseDeal],
            $this->portalDealData,
            'sales',
            $this->entityType,
            $this->entityId,
            'document', // xo warm presentation, hot moneyAwait
            $this->currentPlanEventTypeName,
            $this->currentPlanEventName,
            'done',  // plan done expired 
            $this->responsibleId,
            true,
            '$fields',
            null, // $relationSalePresDeal
        );

        return true;
    }

    protected function getDealProductRows($rows, $dealId)
    {
        // Log::info('APRIL_HOOK getDealProductRows', ['rows' => $rows]);

        // "cells": {
        //     "general": [
        //         {
        //             "name": "Гарант-Юрист",
        //             "cells": [
        //                 {
        //                     "name": "Наименование",
        //                     "code": "name",
        //                     "isActive": true,
        //                     "type": "string",
        //                     "order": 0,
        //                     "defaultValue": "Гарант-Юрист",
        //                     "value": "Гарант-Юрист Интернет версия на 1 одновременный доступ к системе",
        //                     "target": "general"
        //                 },
        //                 {
        //                     "name": "Количество доступов",
        //                     "code": "supply",
        //                     "isActive": false,
        //                     "type": "string",
        //                     "order": 0,
        //                     "defaultValue": "Интернет 1 ОД",
        //                     "value": "Интернет 1 ОД",
        //                     "target": "general",
        //                     "supply": {
        //                         "contractPropSuppliesQuantity": 1,
        //                         "lcontractProp2": "",
        //                         "lcontractName": "Многопользовательская Интернет-версия 1",
        //                         "lcontractPropEmail": "Адрес электронной почты Лицензиата, на который Лицензиар присылает информацию об административной учетной записи, с помощью которой Лицензиатом заводятся логины и пароли Пользователей.  Указанный адрес электронной почты Лицензиата  используется в течение всего срока действия лицензии.  При смене адреса электронной почты Лицензиата Стороны подписывают  Дополнительное соглашение об изложении настоящего пункта Приложения 1 в новой редакции. ",
        //                         "type": "internet",
        //                         "contractPropLoginsQuantity": "Неограниченно с возможностью одновременной работы одного Пользователя",
        //                         "number": 1,
        //                         "acontractName": "Многопользовательская Интернет-версия 1",
        //                         "contractPropComment": "Для работы с комплектом Справочника в электронном виде по каналам связи посредством телекоммуникационной сети Исполнитель предоставляет Заказчику в электронном виде на адрес электронной почты, указанный Заказчиком в настоящем Приложении, информацию об административной учетной записи, с помощью которой Заказчиком заводятся логины и пароли Пользователей.  Администрирование логинов и паролей  осуществляется Заказчиком самостоятельно. Если в течение 5 (пяти) дней с даты подписания Сторонами настоящего Приложения Заказчик не получил информацию об административной учетной записи, то Заказчик не позднее шестого дня с даты подписания Сторонами настоящего Приложения  обязан в письменной форме сообщить Исполнителю об отсутствии информации об административной учетной записи.  Услуги считаются оказываемыми с даты направления Исполнителем Заказчику в электронном виде на адрес электронной почты, указанный Заказчиком в настоящем Приложении, информации об административной учетной записи.",
        //                         "contractPropEmail": "Адрес электронной почты Заказчика, на который Исполнитель присылает информацию об административной учетной записи, с помощью которой Заказчиком заводятся логины и пароли Пользователей. Указанный адрес электронной почты Заказчика используется в течение всего срока оказания услуг Заказчику. При смене адреса электронной почты Заказчика Стороны подписывают Дополнительное соглашение об изложении настоящего пункта Приложения 1 в новой редакции.",
        //                         "quantityForKp": "Интернет версия на 1 одновременный доступ к системе",
        //                         "name": "Интернет 1 ОД",
        //                         "coefficient": 1.25,
        //                         "acontractPropComment": "Для подключения к обновлению текущих версий комплекта ЭПС «Система ГАРАНТ» Продавец предоставляет Покупателю в электронном виде на адрес электронной почты, указанный Покупателем в настоящем Приложении, информацию об административной учетной записи, с помощью которой Покупателем заводятся логины и пароли Пользователей.  Администрирование логинов и паролей  осуществляется Покупателем самостоятельно. Если в течение 5 (пяти) дней с даты подписания Сторонами настоящего Приложения Покупатель не получил информацию об административной учетной записи, то Покупатель не позднее шестого дня с даты подписания Сторонами настоящего Приложения  обязан в письменной форме сообщить Продавцу об отсутствии информации об административной учетной записи. ",
        //                         "contractName": "В электронном виде по каналам связи посредством телекоммуникационной сети Интернет Многопользовательская Интернет-версия 1",
        //                         "lcontractPropComment": "Для получения удаленного доступа  к Базе данных через информационно-телекоммуникационную сеть Интернет  Лицензиар  предоставляет Лицензиату в электронном виде на адрес электронной почты, указанный Лицензиатом  в настоящем Приложении, информацию об административной учетной записи, с помощью которой Лицензиатом  заводятся логины и пароли Пользователей.  Администрирование логинов и паролей  осуществляется Лицензиатом  самостоятельно. Если в течение 5 (пяти) дней с даты подписания Сторонами настоящего Приложения Лицензиат  не получил информацию об административной учетной записи, то Лицензиат  не позднее шестого дня с даты подписания Сторонами настоящего Приложения  обязан в письменной форме сообщить Лицензиару об отсутствии информации об административной учетной записи.  Доступ считается предоставленным с даты направления Лицензиаром Лицензиату в электронном виде на адрес электронной почты, указанный Лицензиатом в настоящем Приложении, информации об административной учетной записи.",
        //                         "contractProp2": "",
        //                         "contractProp1": ""
        //                     }
        //                 },
        //                 {
        //                     "name": "Версия",
        //                     "code": "supplyForOffer",
        //                     "isActive": false,
        //                     "type": "string",
        //                     "order": 0,
        //                     "value": "Интернет версия на 1 одновременный доступ к системе",
        //                     "defaultValue": "Интернет версия на 1 одновременный доступ к системе",
        //                     "target": "general",
        //                     "supply": {
        //                         "contractPropSuppliesQuantity": 1,
        //                         "lcontractProp2": "",
        //                         "lcontractName": "Многопользовательская Интернет-версия 1",
        //                         "lcontractPropEmail": "Адрес электронной почты Лицензиата, на который Лицензиар присылает информацию об административной учетной записи, с помощью которой Лицензиатом заводятся логины и пароли Пользователей.  Указанный адрес электронной почты Лицензиата  используется в течение всего срока действия лицензии.  При смене адреса электронной почты Лицензиата Стороны подписывают  Дополнительное соглашение об изложении настоящего пункта Приложения 1 в новой редакции. ",
        //                         "type": "internet",
        //                         "contractPropLoginsQuantity": "Неограниченно с возможностью одновременной работы одного Пользователя",
        //                         "number": 1,
        //                         "acontractName": "Многопользовательская Интернет-версия 1",
        //                         "contractPropComment": "Для работы с комплектом Справочника в электронном виде по каналам связи посредством телекоммуникационной сети Исполнитель предоставляет Заказчику в электронном виде на адрес электронной почты, указанный Заказчиком в настоящем Приложении, информацию об административной учетной записи, с помощью которой Заказчиком заводятся логины и пароли Пользователей.  Администрирование логинов и паролей  осуществляется Заказчиком самостоятельно. Если в течение 5 (пяти) дней с даты подписания Сторонами настоящего Приложения Заказчик не получил информацию об административной учетной записи, то Заказчик не позднее шестого дня с даты подписания Сторонами настоящего Приложения  обязан в письменной форме сообщить Исполнителю об отсутствии информации об административной учетной записи.  Услуги считаются оказываемыми с даты направления Исполнителем Заказчику в электронном виде на адрес электронной почты, указанный Заказчиком в настоящем Приложении, информации об административной учетной записи.",
        //                         "contractPropEmail": "Адрес электронной почты Заказчика, на который Исполнитель присылает информацию об административной учетной записи, с помощью которой Заказчиком заводятся логины и пароли Пользователей. Указанный адрес электронной почты Заказчика используется в течение всего срока оказания услуг Заказчику. При смене адреса электронной почты Заказчика Стороны подписывают Дополнительное соглашение об изложении настоящего пункта Приложения 1 в новой редакции.",
        //                         "quantityForKp": "Интернет версия на 1 одновременный доступ к системе",
        //                         "name": "Интернет 1 ОД",
        //                         "coefficient": 1.25,
        //                         "acontractPropComment": "Для подключения к обновлению текущих версий комплекта ЭПС «Система ГАРАНТ» Продавец предоставляет Покупателю в электронном виде на адрес электронной почты, указанный Покупателем в настоящем Приложении, информацию об административной учетной записи, с помощью которой Покупателем заводятся логины и пароли Пользователей.  Администрирование логинов и паролей  осуществляется Покупателем самостоятельно. Если в течение 5 (пяти) дней с даты подписания Сторонами настоящего Приложения Покупатель не получил информацию об административной учетной записи, то Покупатель не позднее шестого дня с даты подписания Сторонами настоящего Приложения  обязан в письменной форме сообщить Продавцу об отсутствии информации об административной учетной записи. ",
        //                         "contractName": "В электронном виде по каналам связи посредством телекоммуникационной сети Интернет Многопользовательская Интернет-версия 1",
        //                         "lcontractPropComment": "Для получения удаленного доступа  к Базе данных через информационно-телекоммуникационную сеть Интернет  Лицензиар  предоставляет Лицензиату в электронном виде на адрес электронной почты, указанный Лицензиатом  в настоящем Приложении, информацию об административной учетной записи, с помощью которой Лицензиатом  заводятся логины и пароли Пользователей.  Администрирование логинов и паролей  осуществляется Лицензиатом  самостоятельно. Если в течение 5 (пяти) дней с даты подписания Сторонами настоящего Приложения Лицензиат  не получил информацию об административной учетной записи, то Лицензиат  не позднее шестого дня с даты подписания Сторонами настоящего Приложения  обязан в письменной форме сообщить Лицензиару об отсутствии информации об административной учетной записи.  Доступ считается предоставленным с даты направления Лицензиаром Лицензиату в электронном виде на адрес электронной почты, указанный Лицензиатом в настоящем Приложении, информации об административной учетной записи.",
        //                         "contractProp2": "",
        //                         "contractProp1": ""
        //                     }
        //                 },
        //                 {
        //                     "name": "Цена по прайсу",
        //                     "code": "default",
        //                     "isActive": false,
        //                     "target": "general",
        //                     "type": "string",
        //                     "order": 1,
        //                     "defaultValue": 8008,
        //                     "value": 8008
        //                 },
        //                 {
        //                     "name": "Цена по прайсу в месяц",
        //                     "code": "defaultmonth",
        //                     "isActive": false,
        //                     "target": "general",
        //                     "type": "string",
        //                     "order": 1,
        //                     "defaultValue": 8008,
        //                     "value": 8008
        //                 },
        //                 {
        //                     "name": "Скидка, %",
        //                     "code": "discountprecent",
        //                     "isActive": false,
        //                     "target": "general",
        //                     "type": "string",
        //                     "order": 2,
        //                     "defaultValue": 1,
        //                     "value": 1
        //                 },
        //                 {
        //                     "name": "Скидка в рублях",
        //                     "code": "discountamount",
        //                     "isActive": false,
        //                     "target": "general",
        //                     "type": "string",
        //                     "order": 2,
        //                     "defaultValue": 0,
        //                     "value": 0
        //                 },
        //                 {
        //                     "name": "Цена",
        //                     "code": "current",
        //                     "isActive": false,
        //                     "target": "general",
        //                     "type": "string",
        //                     "order": 3,
        //                     "defaultValue": 8008,
        //                     "value": 8008
        //                 },
        //                 {
        //                     "name": "Цена в месяц",
        //                     "code": "currentmonth",
        //                     "isActive": true,
        //                     "target": "general",
        //                     "type": "string",
        //                     "order": 3,
        //                     "defaultValue": 8008,
        //                     "value": 8008
        //                 },
        //                 {
        //                     "name": "При внесении предоплаты от",
        //                     "code": "quantity",
        //                     "isActive": true,
        //                     "target": "general",
        //                     "type": "string",
        //                     "order": 4,
        //                     "defaultValue": 1,
        //                     "value": "1 месяца"
        //                 },
        //                 {
        //                     "name": "Количество изначальное",
        //                     "code": "defaultquantity",
        //                     "isActive": false,
        //                     "target": "general",
        //                     "type": "string",
        //                     "order": 4,
        //                     "defaultValue": 1,
        //                     "value": 1
        //                 },
        //                 {
        //                     "name": "Единица",
        //                     "code": "measure",
        //                     "isActive": false,
        //                     "target": "general",
        //                     "type": "string",
        //                     "order": 5,
        //                     "defaultValue": "мес.",
        //                     "value": "мес."
        //                 },
        //                 {
        //                     "name": "Сумма предоплаты",
        //                     "code": "prepaymentsum",
        //                     "isActive": true,
        //                     "target": "general",
        //                     "type": "string",
        //                     "order": 8,
        //                     "defaultValue": 8008,
        //                     "value": 8008
        //                 },
        //                 {
        //                     "name": "contract",
        //                     "code": "contract",
        //                     "isActive": false,
        //                     "type": "contract",
        //                     "order": 10,
        //                     "defaultValue": {
        //                         "measureId": 1,
        //                         "measureNumber": 0,
        //                         "discount": 1,
        //                         "aprilName": "Internet",
        //                         "measureName": "мес.",
        //                         "prepayment": 1,
        //                         "number": 0,
        //                         "itemId": 6777,
        //                         "measureCode": 6,
        //                         "bitrixName": "Internet",
        //                         "shortName": "internet",
        //                         "measureFullName": "Месяц",
        //                         "order": 0
        //                     },
        //                     "value": {
        //                         "measureId": 1,
        //                         "measureNumber": 0,
        //                         "discount": 1,
        //                         "aprilName": "Internet",
        //                         "measureName": "мес.",
        //                         "prepayment": 1,
        //                         "number": 0,
        //                         "itemId": 6777,
        //                         "measureCode": 6,
        //                         "bitrixName": "Internet",
        //                         "shortName": "internet",
        //                         "measureFullName": "Месяц",
        //                         "order": 0
        //                     },
        //                     "target": "general"
        //                 }
        //             ],
        //             "target": "general"
        //         }
        //     ],
        //     "alternative": [],
        //     "total": [
        //         {
        //             "name": "Гарант-Юрист",
        //             "cells": [
        //                 {
        //                     "name": "Наименование",
        //                     "code": "name",
        //                     "isActive": true,
        //                     "type": "string",
        //                     "order": 0,
        //                     "defaultValue": "Гарант-Юрист",
        //                     "value": "Гарант-Юрист Интернет версия на 1 одновременный доступ к системе",
        //                     "target": "general"
        //                 },
        //                 {
        //                     "name": "Количество доступов",
        //                     "code": "supply",
        //                     "isActive": false,
        //                     "type": "string",
        //                     "order": 0,
        //                     "defaultValue": "Интернет 1 ОД",
        //                     "value": "Интернет 1 ОД",
        //                     "target": "general",
        //                     "supply": {
        //                         "contractPropSuppliesQuantity": 1,
        //                         "lcontractProp2": "",
        //                         "lcontractName": "Многопользовательская Интернет-версия 1",
        //                         "lcontractPropEmail": "Адрес электронной почты Лицензиата, на который Лицензиар присылает информацию об административной учетной записи, с помощью которой Лицензиатом заводятся логины и пароли Пользователей.  Указанный адрес электронной почты Лицензиата  используется в течение всего срока действия лицензии.  При смене адреса электронной почты Лицензиата Стороны подписывают  Дополнительное соглашение об изложении настоящего пункта Приложения 1 в новой редакции. ",
        //                         "type": "internet",
        //                         "contractPropLoginsQuantity": "Неограниченно с возможностью одновременной работы одного Пользователя",
        //                         "number": 1,
        //                         "acontractName": "Многопользовательская Интернет-версия 1",
        //                         "contractPropComment": "Для работы с комплектом Справочника в электронном виде по каналам связи посредством телекоммуникационной сети Исполнитель предоставляет Заказчику в электронном виде на адрес электронной почты, указанный Заказчиком в настоящем Приложении, информацию об административной учетной записи, с помощью которой Заказчиком заводятся логины и пароли Пользователей.  Администрирование логинов и паролей  осуществляется Заказчиком самостоятельно. Если в течение 5 (пяти) дней с даты подписания Сторонами настоящего Приложения Заказчик не получил информацию об административной учетной записи, то Заказчик не позднее шестого дня с даты подписания Сторонами настоящего Приложения  обязан в письменной форме сообщить Исполнителю об отсутствии информации об административной учетной записи.  Услуги считаются оказываемыми с даты направления Исполнителем Заказчику в электронном виде на адрес электронной почты, указанный Заказчиком в настоящем Приложении, информации об административной учетной записи.",
        //                         "contractPropEmail": "Адрес электронной почты Заказчика, на который Исполнитель присылает информацию об административной учетной записи, с помощью которой Заказчиком заводятся логины и пароли Пользователей. Указанный адрес электронной почты Заказчика используется в течение всего срока оказания услуг Заказчику. При смене адреса электронной почты Заказчика Стороны подписывают Дополнительное соглашение об изложении настоящего пункта Приложения 1 в новой редакции.",
        //                         "quantityForKp": "Интернет версия на 1 одновременный доступ к системе",
        //                         "name": "Интернет 1 ОД",
        //                         "coefficient": 1.25,
        //                         "acontractPropComment": "Для подключения к обновлению текущих версий комплекта ЭПС «Система ГАРАНТ» Продавец предоставляет Покупателю в электронном виде на адрес электронной почты, указанный Покупателем в настоящем Приложении, информацию об административной учетной записи, с помощью которой Покупателем заводятся логины и пароли Пользователей.  Администрирование логинов и паролей  осуществляется Покупателем самостоятельно. Если в течение 5 (пяти) дней с даты подписания Сторонами настоящего Приложения Покупатель не получил информацию об административной учетной записи, то Покупатель не позднее шестого дня с даты подписания Сторонами настоящего Приложения  обязан в письменной форме сообщить Продавцу об отсутствии информации об административной учетной записи. ",
        //                         "contractName": "В электронном виде по каналам связи посредством телекоммуникационной сети Интернет Многопользовательская Интернет-версия 1",
        //                         "lcontractPropComment": "Для получения удаленного доступа  к Базе данных через информационно-телекоммуникационную сеть Интернет  Лицензиар  предоставляет Лицензиату в электронном виде на адрес электронной почты, указанный Лицензиатом  в настоящем Приложении, информацию об административной учетной записи, с помощью которой Лицензиатом  заводятся логины и пароли Пользователей.  Администрирование логинов и паролей  осуществляется Лицензиатом  самостоятельно. Если в течение 5 (пяти) дней с даты подписания Сторонами настоящего Приложения Лицензиат  не получил информацию об административной учетной записи, то Лицензиат  не позднее шестого дня с даты подписания Сторонами настоящего Приложения  обязан в письменной форме сообщить Лицензиару об отсутствии информации об административной учетной записи.  Доступ считается предоставленным с даты направления Лицензиаром Лицензиату в электронном виде на адрес электронной почты, указанный Лицензиатом в настоящем Приложении, информации об административной учетной записи.",
        //                         "contractProp2": "",
        //                         "contractProp1": ""
        //                     }
        //                 },
        //                 {
        //                     "name": "Версия",
        //                     "code": "supplyForOffer",
        //                     "isActive": false,
        //                     "type": "string",
        //                     "order": 0,
        //                     "value": "Интернет версия на 1 одновременный доступ к системе",
        //                     "defaultValue": "Интернет версия на 1 одновременный доступ к системе",
        //                     "target": "general",
        //                     "supply": {
        //                         "contractPropSuppliesQuantity": 1,
        //                         "lcontractProp2": "",
        //                         "lcontractName": "Многопользовательская Интернет-версия 1",
        //                         "lcontractPropEmail": "Адрес электронной почты Лицензиата, на который Лицензиар присылает информацию об административной учетной записи, с помощью которой Лицензиатом заводятся логины и пароли Пользователей.  Указанный адрес электронной почты Лицензиата  используется в течение всего срока действия лицензии.  При смене адреса электронной почты Лицензиата Стороны подписывают  Дополнительное соглашение об изложении настоящего пункта Приложения 1 в новой редакции. ",
        //                         "type": "internet",
        //                         "contractPropLoginsQuantity": "Неограниченно с возможностью одновременной работы одного Пользователя",
        //                         "number": 1,
        //                         "acontractName": "Многопользовательская Интернет-версия 1",
        //                         "contractPropComment": "Для работы с комплектом Справочника в электронном виде по каналам связи посредством телекоммуникационной сети Исполнитель предоставляет Заказчику в электронном виде на адрес электронной почты, указанный Заказчиком в настоящем Приложении, информацию об административной учетной записи, с помощью которой Заказчиком заводятся логины и пароли Пользователей.  Администрирование логинов и паролей  осуществляется Заказчиком самостоятельно. Если в течение 5 (пяти) дней с даты подписания Сторонами настоящего Приложения Заказчик не получил информацию об административной учетной записи, то Заказчик не позднее шестого дня с даты подписания Сторонами настоящего Приложения  обязан в письменной форме сообщить Исполнителю об отсутствии информации об административной учетной записи.  Услуги считаются оказываемыми с даты направления Исполнителем Заказчику в электронном виде на адрес электронной почты, указанный Заказчиком в настоящем Приложении, информации об административной учетной записи.",
        //                         "contractPropEmail": "Адрес электронной почты Заказчика, на который Исполнитель присылает информацию об административной учетной записи, с помощью которой Заказчиком заводятся логины и пароли Пользователей. Указанный адрес электронной почты Заказчика используется в течение всего срока оказания услуг Заказчику. При смене адреса электронной почты Заказчика Стороны подписывают Дополнительное соглашение об изложении настоящего пункта Приложения 1 в новой редакции.",
        //                         "quantityForKp": "Интернет версия на 1 одновременный доступ к системе",
        //                         "name": "Интернет 1 ОД",
        //                         "coefficient": 1.25,
        //                         "acontractPropComment": "Для подключения к обновлению текущих версий комплекта ЭПС «Система ГАРАНТ» Продавец предоставляет Покупателю в электронном виде на адрес электронной почты, указанный Покупателем в настоящем Приложении, информацию об административной учетной записи, с помощью которой Покупателем заводятся логины и пароли Пользователей.  Администрирование логинов и паролей  осуществляется Покупателем самостоятельно. Если в течение 5 (пяти) дней с даты подписания Сторонами настоящего Приложения Покупатель не получил информацию об административной учетной записи, то Покупатель не позднее шестого дня с даты подписания Сторонами настоящего Приложения  обязан в письменной форме сообщить Продавцу об отсутствии информации об административной учетной записи. ",
        //                         "contractName": "В электронном виде по каналам связи посредством телекоммуникационной сети Интернет Многопользовательская Интернет-версия 1",
        //                         "lcontractPropComment": "Для получения удаленного доступа  к Базе данных через информационно-телекоммуникационную сеть Интернет  Лицензиар  предоставляет Лицензиату в электронном виде на адрес электронной почты, указанный Лицензиатом  в настоящем Приложении, информацию об административной учетной записи, с помощью которой Лицензиатом  заводятся логины и пароли Пользователей.  Администрирование логинов и паролей  осуществляется Лицензиатом  самостоятельно. Если в течение 5 (пяти) дней с даты подписания Сторонами настоящего Приложения Лицензиат  не получил информацию об административной учетной записи, то Лицензиат  не позднее шестого дня с даты подписания Сторонами настоящего Приложения  обязан в письменной форме сообщить Лицензиару об отсутствии информации об административной учетной записи.  Доступ считается предоставленным с даты направления Лицензиаром Лицензиату в электронном виде на адрес электронной почты, указанный Лицензиатом в настоящем Приложении, информации об административной учетной записи.",
        //                         "contractProp2": "",
        //                         "contractProp1": ""
        //                     }
        //                 },
        //                 {
        //                     "name": "Цена по прайсу",
        //                     "code": "default",
        //                     "isActive": false,
        //                     "target": "general",
        //                     "type": "string",
        //                     "order": 1,
        //                     "defaultValue": 8008,
        //                     "value": 8008
        //                 },
        //                 {
        //                     "name": "Цена",
        //                     "code": "current",
        //                     "isActive": false,
        //                     "target": "general",
        //                     "type": "string",
        //                     "order": 3,
        //                     "defaultValue": 8008,
        //                     "value": 8008
        //                 },
        //                 {
        //                     "name": "Единица",
        //                     "code": "measure",
        //                     "isActive": false,
        //                     "target": "general",
        //                     "type": "string",
        //                     "order": 5,
        //                     "defaultValue": "мес.",
        //                     "value": "мес."
        //                 },
        //                 {
        //                     "name": "contract",
        //                     "code": "contract",
        //                     "isActive": false,
        //                     "type": "contract",
        //                     "order": 10,
        //                     "defaultValue": {
        //                         "measureId": 1,
        //                         "measureNumber": 0,
        //                         "discount": 1,
        //                         "aprilName": "Internet",
        //                         "measureName": "мес.",
        //                         "prepayment": 1,
        //                         "number": 0,
        //                         "itemId": 6777,
        //                         "measureCode": 6,
        //                         "bitrixName": "Internet",
        //                         "shortName": "internet",
        //                         "measureFullName": "Месяц",
        //                         "order": 0
        //                     },
        //                     "value": {
        //                         "measureId": 1,
        //                         "measureNumber": 0,
        //                         "discount": 1,
        //                         "aprilName": "Internet",
        //                         "measureName": "мес.",
        //                         "prepayment": 1,
        //                         "number": 0,
        //                         "itemId": 6777,
        //                         "measureCode": 6,
        //                         "bitrixName": "Internet",
        //                         "shortName": "internet",
        //                         "measureFullName": "Месяц",
        //                         "order": 0
        //                     },
        //                     "target": "general"
        //                 },
        //                 {
        //                     "name": "Цена в месяц",
        //                     "code": "currentmonth",
        //                     "isActive": true,
        //                     "target": "general",
        //                     "type": "string",
        //                     "order": 3,
        //                     "defaultValue": 8008,
        //                     "value": 8008
        //                 },
        //                 {
        //                     "name": "Цена по прайсу в месяц",
        //                     "code": "defaultmonth",
        //                     "isActive": false,
        //                     "target": "general",
        //                     "type": "string",
        //                     "order": 1,
        //                     "defaultValue": 8008,
        //                     "value": 8008
        //                 },
        //                 {
        //                     "name": "При внесении предоплаты от",
        //                     "code": "quantity",
        //                     "isActive": true,
        //                     "target": "general",
        //                     "type": "string",
        //                     "order": 4,
        //                     "defaultValue": 1,
        //                     "value": "1 месяца"
        //                 },
        //                 {
        //                     "name": "Количество изначальное",
        //                     "code": "defaultquantity",
        //                     "isActive": false,
        //                     "target": "general",
        //                     "type": "string",
        //                     "order": 4,
        //                     "defaultValue": 1,
        //                     "value": 1
        //                 },
        //                 {
        //                     "name": "Скидка, %",
        //                     "code": "discountprecent",
        //                     "isActive": false,
        //                     "target": "general",
        //                     "type": "string",
        //                     "order": 2,
        //                     "defaultValue": 1,
        //                     "value": 1
        //                 },
        //                 {
        //                     "name": "Скидка в рублях",
        //                     "code": "discountamount",
        //                     "isActive": false,
        //                     "target": "general",
        //                     "type": "string",
        //                     "order": 2,
        //                     "defaultValue": 0,
        //                     "value": 0
        //                 },
        //                 {
        //                     "name": "Сумма предоплаты",
        //                     "code": "prepaymentsum",
        //                     "isActive": true,
        //                     "target": "general",
        //                     "type": "string",
        //                     "order": 8,
        //                     "defaultValue": 8008,
        //                     "value": 8008
        //                 }
        //             ],
        //             "target": "general"
        //         }
        //     ]
        // },
        // "options": {
        //     "year": {
        //         "name": "year",
        //         "show": "Показать сумму за весь период обслуживания",
        //         "unshow": "Показать сумму предоплаты",
        //         "invoice": "Показать количество и сумму",
        //         "status": "unshow",
        //         "isYear": true,
        //         "isActive": true
        //     },
        //     "price": {
        //         "name": "price",
        //         "show": "Показать цену по прайсу",
        //         "unshow": "Скрыть цену по прайсу",
        //         "status": "show",
        //         "isYear": false,
        //         "isActive": true
        //     },
        //     "discount": {
        //         "name": "discount",
        //         "show": "Показать скидку",
        //         "unshow": "Скрыть скидку",
        //         "status": "show",
        //         "isYear": false,
        //         "isActive": true
        //     },
        //     "measure": {
        //         "name": "measure",
        //         "show": "Сделать единицу измерения - месяцы",
        //         "unshow": "Вернуть единицы измерения по умолчанию",
        //         "status": "show",
        //         "isYear": false,
        //         "isActive": false
        //     },
        //     "supply": {
        //         "name": "supply",
        //         "show": "Показать ОД развёрнуто",
        //         "unshow": "Сократить информацию об ОД",
        //         "invoice": "Убрать ОД",
        //         "status": "unshow",
        //         "isYear": false,
        //         "isActive": true
        //     }
        // },
        // "isInvoice": false,
        // "isDefaultShow": false,
        // "isTable": true,
        // "isOneMeasure": true,
        // "isDiscountShow": false,
        // "isSupplyLong": true,
        // "prepaymentStyle": "invoice"
        // Log::info('APRIL_HOOK getDealProductRows', [
        //     'data' => [
        //         'comming' =>  $rows,
        //     ]
        // ]);
        $resultRows = [];
        foreach ($rows as $i => $product) {
            if (!empty($product)) {

                if (!empty($product['prepayment'])) {

                    $quantity = $product['prepayment'];
                }
                if (!empty($product['price'])) {
                    if (isset($product['price']['quantity'])) {
                        $quantity = $product['price']['quantity'];
                    }
                }



                if (!empty($product['price'])) {
                    if (!empty($product['price']['default'])) {
                        $price = $product['price']['default'];
                        $priceCurrent = $product['price']['current'];
                        $priceNetto = $product['price']['default'];
                        $discountSum =  $priceNetto - $priceCurrent;
                        $id = 0;
                        if (isset($product['id'])) {
                            $id = $product['id'];
                        }
                        if (isset($product['number'])) {
                            $id = $product['number'];
                        }

                        $productName = $product['name'];
                        $supplyName = '';
                        if (!empty($product['supply'])) {
                            if (isset($product['supply']['name'])) {
                                $supplyName = $product['supply']['name'];
                                $productName =  $productName . ' ' .  $supplyName;
                            }
                        }





                        $measureCode = 0;
                        $measureId = 0;

                        if (!empty($product['price'])) {
                            if (isset($product['price']['measure'])) {
                                if (isset($product['price']['measure']['code'])) {
                                    $measureCode = $product['price']['measure']['code'];
                                    $measureId = $product['price']['measure']['id'];
                                }
                            }
                        }

                        $row = [
                            "id" => $id,
                            "priceNetto" => $priceNetto,
                            "price" => $priceCurrent,
                            "discountSum" => $discountSum,
                            "discountTypeId" => 1,
                            "ownerId" => $dealId,
                            "ownerType" => "D",
                            "productName" => $productName,
                            "quantity" => $quantity,
                            "customized" => "Y",
                            "supply" => $supplyName,
                            "measureCode" => $measureCode,
                            "measureId" => $measureId,
                            "sort" => $i,
                        ];
                        $this->comment = $this->comment . ' ' . $productName . ' ' . $priceCurrent . ' р. Количество: ' . $quantity;
                        $currentsum = $priceCurrent * $quantity;
                        $this->sum  += $currentsum;
                        array_push($resultRows, $row);
                    }
                }
            }
        }

        $setProductRowsData = [
            'ownerType' => 'D',
            'ownerId' => $dealId,
            'productRows' => $resultRows,
        ];

        return $setProductRowsData;
    }


    //tasks for complete



    protected function getListFlow()
    {
        $reportEventType = $this->currentReportEventType;
        $reportEventTypeName = $this->currentReportEventName;
        $planEventTypeName = $this->currentPlanEventTypeName;
        $planEventType = $this->currentPlanEventType; //если перенос то тип будет автоматически взят из report - предыдущего события
        $eventAction = 'expired';  // не состоялся и двигается крайний срок 
        $planComment = 'Сделан';

        // Коммерческое Предлжение	event_type	ev_offer	EV_OFFER
        // Счет	event_type	ev_invoice	EV_INVOICE
        // Коммерческое Предлжение после презентации	event_type	ev_offer_pres	EV_OFFER_PRES
        // Счет после презентации	event_type	ev_invoice_pres	EV_INVOICE_PRES
        // Договор	event_type	ev_contract	EV_CONTRACT
        // Поставка	event_type	ev_supply	EV_SUPPLY


        // Отправлен	event_action	act_send	ACT_SEND
        // Подписан	event_action	act_sign	ACT_SIGN
        // Оплачен	event_action	act_pay	ACT_PAY


        // protected $currentBaseDeal;
        // protected $currentPresDeal;

        $currentBxDealIds = [];
        if (!empty($this->currentBaseDeal['ID'])) {

            if (!empty($this->currentBaseDeal)) {

                array_push($currentBxDealIds, $this->currentBaseDeal['ID']);
            }
        }
        if ($this->isFromPresentation) {  //если после презентации - вне зависимости от типа документа
            if (!empty($this->currentPresDeal['ID'])) {

                if (!empty($this->currentPresDeal)) {

                    array_push($currentBxDealIds, $this->currentPresDeal['ID']);
                }
            }
        }

        if ($this->isOfferDone) { //если сделано кп


            $eventTypeCode = 'ev_offer';
            $eventTypeName = 'КП';
            if ($this->isFromPresentation) { //кп после презентации

                $eventTypeCode = 'ev_offer_pres';
                $eventTypeName = 'КП после презентации';
            }


            BitrixListDocumentFlowService::getListsFlow(  //report - отчет по текущему событию
                $this->hook,
                $this->bitrixLists,
                $eventTypeCode,
                $eventTypeName,
                'act_send',  // сделано, отправлено
                // $this->stringType,
                // $this->nowDate,
                $this->responsibleId,
                $this->responsibleId,
                $this->responsibleId,
                $this->entityId,
                $this->comment,
                $currentBxDealIds,
                $this->currentBaseDeal['ID']
                // $this->workStatus['current'], 
                // $this->resultStatus, // result noresult expired,
                // $this->noresultReason,
                // $this->failReason,
                // $this->failType

            );
        }

        if ($this->isInvoiceDone) { //если сделан счет


            $eventTypeCode = 'ev_invoice';
            $eventTypeName = 'Счет';
            if ($this->isFromPresentation) { //счет после презентации
                $eventTypeCode = 'ev_invoice_pres';
                $eventTypeName = 'Счет после презентации';
            }


            BitrixListDocumentFlowService::getListsFlow(  //report - отчет по текущему событию
                $this->hook,
                $this->bitrixLists,
                $eventTypeCode,
                $eventTypeName,
                'act_send',  // сделано, отправлено
                // $this->stringType,
                // $this->nowDate,
                $this->responsibleId,
                $this->responsibleId,
                $this->responsibleId,
                $this->entityId,
                $this->comment,
                $currentBxDealIds,
                $this->currentBaseDeal['ID'],
                null,
                null,
         
                // $this->workStatus['current'], 
                // $this->resultStatus, // result noresult expired,
                // $this->noresultReason,
                // $this->failReason,
                // $this->failType

            );
        }
    }
    protected function getListPresentationFlow(
        // $planPresDealIds
    )
    {

        //если документ связан с презентацией
        // должен найти элемент списка с id у през deal такой
        // как у текущей pres deal и засунуть сумма счета или кп и счета в зависимости от типа документа
        // $currentTask = $this->currentTask;
        // $currentDealIds = $planPresDealIds['planDeals'];
        // $unplannedPresDealsIds = $planPresDealIds['unplannedPresDeals'];

        // presentation list flow запускается когда
        // планируется презентация или unplunned тогда для связи со сделками берется $planPresDealIds
        // отчитываются о презентации презентация или unplunned тогда для связи со сделками берется $currentTask



        // Комментарий после презентации	presentation	string	pres_done_comment
        // Результативность	presentation	enumeration	pres_result_status
        // Статус Работы	presentation	enumeration	pres_work_status
        // Перспективность	presentation	enumeration	pres_prospects_type

        // Сумма предложения	presentation	string	pres_sum_offer
        // Сумма счета	presentation	string	pres_sum_invoice
        // Дата первого платежа	presentation	datetime	pres_sale_date
        // Сумма первого платежа	presentation	string	pres_sum_prepayment
        // Количество месяцев первого аванса	presentation	string	pres_quantity_prepayment
        // Сумма в месяц	presentation	string	pres_sum_month
        // Связи	presentation	crm	pres_crm_other

        $searchedBaseDealId = null; //
        $searchedPresDealId = null; //
        $serchingListCode = '';

        $currentPresDeal =  $this->currentPresDeal;
        if (!empty($currentPresDeal)) {
            if (!empty($currentPresDeal['ID'])) {
                $searchedPresDealId = $currentPresDeal['ID'];
            }
            if (!empty($currentPresDeal['UF_CRM_TO_BASE_SALES'])) {
                $searchedBaseDealId = $currentPresDeal['UF_CRM_TO_BASE_SALES']; //

            }
        }

        if (!empty($searchedBaseDealId) && !empty($searchedBaseDealId)) {
            $serchingListCode = $searchedBaseDealId . '_' . $searchedPresDealId;
        }
        // if ($this->isOfferDone) { //если сделано кп


        //         $eventTypeCode = 'pres_sum_offer';
        //         $eventTypeName = 'КП после презентации';

        // }

        // if ($this->isInvoiceDone) { //если сделан счет

        //         $eventTypeCode = 'pres_sum_invoice';
        //         $eventTypeName = 'Счет после презентации';

        // }




        BitrixListPresentationFlowService::getListPresentationDocumentFlow(
            $this->hook,
            $this->bitrixLists,
            $serchingListCode,
            $this->nowDate,
            $this->responsibleId,
            $this->entityId,
            $this->isOfferDone,
            $this->isInvoiceDone,
            $this->comment, //sum
            $this->sum

        );
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