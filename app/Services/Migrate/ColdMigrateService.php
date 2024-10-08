<?php

namespace App\Services\Migrate;

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
use DateTime;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use IntlDateFormatter;

class ColdMigrateService


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

    protected $nowDate = [];
    protected $isTmc = false;

    public function __construct(

        $data,

    ) {
        date_default_timezone_set('Europe/Moscow');
        $nowDate = new DateTime();
        setlocale(LC_TIME, 'ru_RU.utf8');
        // Форматируем дату и время в нужный формат
        $this->nowDate = $nowDate->format('d.m.Y H:i:s');
        $locale = 'ru_RU';
        $pattern = 'd MMMM yyyy';

        // Создаем форматтер
        $formatter = new IntlDateFormatter(
            $locale,
            IntlDateFormatter::NONE,
            IntlDateFormatter::NONE,
            date_default_timezone_get(),
            IntlDateFormatter::GREGORIAN,
            $pattern
        );

        // Форматируем дату
        $formattedStringNowDate = $formatter->format($nowDate);
        $domain = $data['domain'];
        $this->entityType = $data['entityType'];
        $this->entityId = $data['entityId'];
        $this->responsibleId = $data['responsible'];
        $this->createdId = $data['created'];
        $this->deadline = $data['deadline'];
        $this->name = 'от ' . $formattedStringNowDate;

        if (isset($data['name'])) {
            if (!empty($data['name'])) {
                $this->name = $data['name'];
            }
        }

        $this->currentDepartamentType = 'sales';

        if (isset($data['isTmc'])) {

            if (!empty($data['isTmc'])) {
                if ($data['isTmc'] == 'Y') {
                    $this->isTmc = true;
                    $this->currentDepartamentType = 'tmc';
                }
            }
        }

        $this->stringType = 'Холодный обзвон  ';
        // $this->entityType = $entityType;

        $portal = PortalController::getPortal($domain);
        $portal = $portal['data'];
        $this->portal = $portal;

        if ($domain === 'april-dev.bitrix24.ru' || $domain === 'gsr.bitrix24.ru') {
            $this->isDealFlow = true;
            $this->withLists = true;
            $this->isSmartFlow = false;
            if (!empty($portal['deals'])) {
                $this->portalDealData = $portal['bitrixDeal'];
            }
            if (!empty($portal['bitrixLists'])) {

                $this->bitrixLists = $portal['bitrixLists'];
            }
        }

        // if ($domain === 'gsr.bitrix24.ru') {
        //     $this->isSmartFlow = false;
        // }


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
            $rand = mt_rand(500000, 2000000); // случайное число от 300000 до 900000 микросекунд (0.3 - 0.9 секунды)
            usleep($rand);
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

        $workStatus = [
            'id' => 0,
            'code' => "inJob",
            'name' => "В работе"
        ];
        $workStatusController = new BitrixEntityFlowService();
        // $now =  new DateTime();
        // $now = $nowDate->format('d.m.Y H:i');
        $nowOnlyDate = $nowDate->format('d.m.Y');



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
                                $resultEntityFields['UF_CRM_' . $pField['bitrixId']] = $data['deadline'];

                                break;
                            case 'call_last_date':
                                // $currentEntityField = [
                                //     'UF_CRM_' . $companyField['bitrixId'] => $data['deadline']
                                // ];
                                $resultEntityFields['UF_CRM_' . $pField['bitrixId']] = $this->nowDate;

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

                                $stringComment = $nowOnlyDate . ' ХО запланирован ' . $data['name'] . ' на ' . $data['deadline'];

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
                            case 'op_current_status':
                                $resultEntityFields['UF_CRM_' . $pField['bitrixId']] =  'Холодный в работе от' . $nowOnlyDate;

                                break;
                            case 'op_work_status':


                                $resultEntityFields['UF_CRM_' . $pField['bitrixId']] = $workStatusController->getWorkstatusFieldItemValue(
                                    $pField, //with items
                                    $workStatus,
                                    'xo' // only PLAN ! event type
                                );
                                break;
                            case 'op_prospects_type':  //Перспективность
                                $resultEntityFields['UF_CRM_' . $pField['bitrixId']] = $workStatusController->getProspectsFieldItemValue(
                                    $pField, //with items
                                    $workStatus,
                                    false //$failType
                                );
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
        $resultEntityFields['ASSIGNED_BY_ID'] = $data['responsible'];

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
        // $targetStageId = 'DT158_13:NEW';
        $this->categoryId = $targetCategoryId;
        $this->stageId = $targetStageId;

        $this->lastCallDateField = $lastCallDateField;
        $this->callThemeField = $callThemeField;
        $this->lastCallDateFieldCold = $lastCallDateFieldCold;
        $this->callThemeFieldCold = $callThemeFieldCold;
    }


    public function getCold()
    {

        try {
            // Log::channel('telegram')->error('APRIL_HOOK data', ['entityType' => $this->entityType]);
            $currentDealsIds = [];
            $updatedCompany = null;
            $updatedLead = null;
            $currentSmart = null;
            $currentSmartId = null;
            $currentDeal = null;
            $currentDealId = null;
            // if(!$this->smartId){

            // }

            // if ($this->isSmartFlow) {
            //     $rand = rand(1, 2);
            //     sleep($rand);
            //     $urand = mt_rand(300000, 2000000); // случайное число от 300000 до 900000 микросекунд (0.3 - 0.9 секунды)
            //     usleep($urand);
            //     $this->getSmartFlow();
            // }
            // $rand = rand(1, 2);
            // sleep($rand);
            $urand = mt_rand(300000, 2000000); // случайное число от 300000 до 900000 микросекунд (0.3 - 0.9 секунды)
            usleep($urand);
            if ($this->isDealFlow && $this->portalDealData) {
                $currentDealsIds = $this->getDealFlow();
                if (!empty($currentDealsIds)) {
                    if (!empty($currentDealsIds['planDeals'])) {
                        $currentDealsIds = $currentDealsIds['planDeals'];
                    }
                }
            }
            // Log::channel('telegram')->info('plan deals ids', [
            //     'currentDealsIds' => $currentDealsIds

            // ]);
            // Log::info('currentDealsIds befor task ids', [
            //     'currentDealsIds' => $currentDealsIds

            // ]);
            if (!empty($currentDealsIds)) {

                $this->createColdTask($currentSmartId, $currentDealsIds);
            }

            $rand = mt_rand(1000000, 2500000); // случайное число от 300000 до 900000 микросекунд (0.3 - 0.9 секунды)
            usleep($rand);

            BitrixEntityFlowService::coldflow(
                $this->portal,
                $this->hook,
                $this->entityType,
                $this->entityId,
                'xo', // xo warm presentation,
                'plan',  // plan done expired 
                $this->entityFieldsUpdatingContent, //updting fields 
            );
            // // if ($this->withLists) {
            // $workStatus = [
            //     'id' => 0,
            //     'code' => "inJob",
            //     'name' => "В работе"
            // ];
            // BtxCreateListItemJob::dispatch(
            //     $this->hook,
            //     $this->bitrixLists,
            //     'xo',
            //     'Холодный обзвон',
            //     'plan',
            //     // $this->stringType,
            //     $this->deadline,
            //     $this->createdId,
            //     $this->responsibleId,
            //     $this->responsibleId,
            //     $this->entityId,
            //     'Холодный обзвон' . $this->name,
            //     $workStatus,
            //     'result',  // result noresult expired,
            //     null, //$noresultReason = null,
            //     null, //$failReason = null,
            //     null, //$failType = null,
            //     $currentDealsIds,
            //     null //current base deal id for uniq pres count

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


        return BitrixSmartFlowService::flow(
            $this->aprilSmartData,
            $this->hook,
            $this->entityType,
            $entityId,
            'xo', // xo warm presentation,
            'plan',  // plan done expired 
            $this->responsibleId,
            $fieldsData
        );
    }



    // deal flow

    protected function getDealFlow()
    {

        // $rand = rand(1, 2);
        // sleep($rand);
        $urand = mt_rand(300000, 2000000); // случайное число от 300000 до 900000 микросекунд (0.3 - 0.9 секунды)
        usleep($urand);

        if (!empty($this->portalDealData['categories'])) {
            foreach ($this->portalDealData['categories'] as $category) {
                $isBaseCategory = $category['code'] ===  'sales_base';

                $includedStages = [];


                $categoryId = $category['bitrixId'];

                if (!empty($category['stages'])) {
                    foreach ($category['stages'] as $stage) {
                        if ($stage['code']) {
                            if (
                                (strpos($stage['code'], 'fail') === false) &&
                                (strpos($stage['code'], 'noresult') === false) &&
                                ((strpos($stage['code'], 'double') === false)) &&
                                (strpos($stage['code'], 'success') === false)
                            ) {
                                array_push($includedStages, "C" . $categoryId . ":" . $stage['bitrixId']);
                            }
                        }
                    }
                }
                $randomNumber = rand(1, 3);



                sleep($randomNumber);
                $currentDeals = BitrixDealService::getDealList(
                    $this->hook,
                    [
                        'filter' => [
                            // "!=stage_id" => ["DT162_26:SUCCESS", "DT156_12:SUCCESS"],
                            // "=assignedById" => $userId,
                            // "=CATEGORY_ID" => $currentCategoryBtxId,
                            'COMPANY_ID' => $this->entityId,
                            // "ASSIGNED_BY_ID" => $this->responsibleId,
                            "=STAGE_ID" =>  $includedStages

                        ],
                        'select' => ["ID", "CATEGORY_ID", "STAGE_ID"],

                    ]


                );
                // Log::info('HOOK TEST CURRENTENTITY', [
                //     'currentDeals' => $currentDeals,


                // ]);

                if (!empty($currentDeals)) {
                    foreach ($currentDeals as $bxDeal) {
                        if (!empty($bxDeal)) {
                            if (!empty($bxDeal['ID'])) {
                                $rand = rand(1, 2);
                                sleep($rand);
                                $urand = mt_rand(300000, 2000000); // случайное число от 300000 до 900000 микросекунд (0.3 - 0.9 секунды)
                                usleep($urand);
                                BitrixDealService::updateDeal(
                                    $this->hook,
                                    $bxDeal['ID'],
                                    [
                                        'STAGE_ID' => 'C' . $categoryId . ':APOLOGY'

                                    ]
                                );
                            }
                        }
                    }
                }
            }
        }
        // Log::channel('telegram')->info('HOOK TEST CURRENTENTITY', [
        //     'includedStages' => $includedStages,


        // ]);

        $rand = mt_rand(300000, 2000000); // случайное число от 300000 до 900000 микросекунд (0.3 - 0.9 секунды)
        usleep($rand);
        $rand = rand(1, 2); // случайное число от 300000 до 900000 микросекунд (0.3 - 0.9 секунды)
        usleep($rand);

        $flowResult =  BitrixDealFlowService::flow(
            $this->hook,
            null, // current btx deals for report
            $this->portalDealData,
            $this->currentDepartamentType,
            $this->entityType,
            $this->entityId,
            'xo', // xo warm presentation,
            'Холодный звонок',
            $this->name,
            'plan',  // plan done expired 
            $this->responsibleId,
            true, //is result for report
            '$fields'
        );
        $planDeals = $flowResult['dealIds'];
        Log::channel('telegram')->error('APRIL_HOOK', [
            'planDeals' => $planDeals
        ]);
        // if (!empty($planDeals)) {
        //     foreach ($planDeals as $dealId) {
        //         $rand = mt_rand(300000, 2000000); // случайное число от 300000 до 900000 микросекунд (0.3 - 0.9 секунды)
        //         usleep($rand);
        //         $rand = rand(1, 2); // случайное число от 300000 до 900000 микросекунд (0.3 - 0.9 секунды)
        //         usleep($rand);

        //         BitrixEntityFlowService::coldflow(
        //             $this->portal,
        //             $this->hook,
        //             'deal',
        //             $dealId,
        //             'xo', // xo warm presentation,
        //             'plan',  // plan done expired 
        //             $this->entityFieldsUpdatingContent, //updting fields 
        //         );
        //     }
        // }

        return [
            'planDeals' => $planDeals,
        ];
    }




    //tasks for complete


    public function createColdTask(
        $currentSmartItemId,
        $currentDealsItemIds = null

    ) {

        $rand = mt_rand(300000, 2000000); // случайное число от 300000 до 900000 микросекунд (0.3 - 0.9 секунды)
        usleep($rand);
        $rand = rand(1); // случайное число от 300000 до 900000 микросекунд (0.3 - 0.9 секунды)
        usleep($rand);
        $createdTask = null;
        try {
            // Log::channel('telegram')->error('APRIL_HOOK', $this->portal);
            $companyId  = null;
            $leadId  = null;
            if ($this->entityType == 'company') {

                $companyId  = $this->entityId;
            } else if ($this->entityType == 'lead') {
                $leadId  = $this->entityId;
            }
            $taskService = new BitrixTaskMigrateService();


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
                null,
                $currentDealsItemIds,


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
            Log::error('error COLD', ['error' => $th->getMessage()]);
            Log::channel('telegram')->error('APRIL_HOOK', $errorMessages);
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