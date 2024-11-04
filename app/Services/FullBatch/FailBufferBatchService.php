<?php

namespace App\Services\FullBatch;

use App\Http\Controllers\APIOnlineController;
use App\Http\Controllers\PortalController;
use App\Services\BitrixGeneralService;

use App\Services\General\BitrixBatchService;
use App\Services\HookFlow\BitrixDealBatchFlowService;
use App\Services\HookFlow\BitrixEntityFlowService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class FailBufferBatchService


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
    protected $currentBtxEntity;
    protected $responsibleId;
    protected $deadline;
    protected $name;
    // // protected $comment;
    // protected $smartId;
    // protected $currentBitrixSmart;
    // // protected $sale;


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
        // 'domain' => $domain,
        // 'entityType' => $entityType,
        // 'entityId' => $entityId,
        // 'responsible' => $responsibleId,
        Carbon::setLocale('ru'); // Устанавливаем локализацию Carbon

        $nowDate = Carbon::now();

        // Форматируем дату в нужный формат
        $formattedStringNowDate = $nowDate->translatedFormat('d F Y');

        $domain = $data['domain'];
        $this->entityType = $data['entityType'];
        $this->entityId = $data['entityId'];
        $this->responsibleId = $data['responsible'];

        $this->name = 'Буфер отказников';




        $this->stringType = 'Буфер отказников';
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
            sleep(1);
            $currentBtxEntity = BitrixGeneralService::getEntity(
                $this->hook,
                $data['entityType'],
                $data['entityId']

            );
            $this->currentBtxEntity =  $currentBtxEntity;
        }

        $resultEntityFields = [];

        $workStatus = [
            'id' => 2,
            'code' => "fail",
            'name' => "Отказ"
        ];
        $workStatusController = new BitrixEntityFlowService();
        // $now =  new DateTime();
        // $now = $nowDate->format('d.m.Y H:i');
        $nowOnlyDate = $nowDate->format('d.m.Y');
        $currentMComments = [];
        if (!empty($this->currentBtxEntity)) {
            if (!empty($this->currentBtxEntity['UF_CRM_OP_MHISTORY'])) {

                $currentMComments = $currentBtxEntity['UF_CRM_OP_MHISTORY'];
            }
        }
        $stringComment = $formattedStringNowDate . "\n"  . 'Буфер отказников';

        array_unshift($currentMComments,  $stringComment);
        if (count($currentMComments) > 8) {
            $currentMComments = array_slice($currentMComments, 0, 8);
        }


        if (!empty($portal[$data['entityType']])) {
            if (!empty($portal[$data['entityType']]['bitrixfields'])) {
                $currentEntityField = [];
                $entityBtxFields = $portal[$data['entityType']]['bitrixfields'];

                foreach ($entityBtxFields as $pField) {


                    if (!empty($pField['code'])) {
                        switch ($pField['code']) {
                            case 'xo_name':
                            case 'xo_date':
                            case 'xo_responsible':
                            case 'xo_created':
                            case 'call_next_date':
                            case 'call_next_name':
                            case 'next_pres_plan_date':
                            case 'call_next_name':
                                // $currentEntityField = [
                                //     'UF_CRM_' . $companyField['bitrixId'] => $data['name']
                                // ];
                                $resultEntityFields['UF_CRM_' . $pField['bitrixId']] = '';
                                break;

                            case 'op_mhistory':

                                $fullFieldId = 'UF_CRM_' . $pField['bitrixId'];  //UF_CRM_OP_MHISTORY
                                $resultEntityFields[$fullFieldId] =  $currentMComments;




                                $resultEntityFields[$fullFieldId] =  $currentMComments;

                                break;
                            case 'op_current_status':
                                $resultEntityFields['UF_CRM_' . $pField['bitrixId']] =  'Холодный в работе ' . $this->name;

                                break;
                            case 'op_current_status':
                                $resultEntityFields['UF_CRM_' . $pField['bitrixId']] =  'Холодный в работе ' . $this->name;

                                break;

                            case 'op_work_status':


                                $resultEntityFields['UF_CRM_' . $pField['bitrixId']] = $workStatusController->getWorkstatusFieldItemValue(
                                    $pField, //with items
                                    $workStatus,
                                    '' // only PLAN ! event type
                                );
                                break;
                            case 'op_prospects_type':  //Перспективность
                                $resultEntityFields['UF_CRM_' . $pField['bitrixId']] = $workStatusController->getProspectsFieldItemValue(
                                    $pField, //with items
                                    $workStatus,
                                    ['code' => 'failure'] //$failType
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
    }


    public function getFail()
    {

        try {
            if ($this->isDealFlow && $this->portalDealData) {
                $this->getFailBufferFlow();
            }

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



    //  flow

    protected function getFailBufferFlow()
    {



        $batchService =  new BitrixBatchService($this->hook);
        $batchCommands = [];

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

                $currentDealsBatchCommand = BitrixDealBatchFlowService::getFullBatchCommand(

                    [
                        'FILTER' => [
                            // "!=stage_id" => ["DT162_26:SUCCESS", "DT156_12:SUCCESS"],
                            // "=assignedById" => $userId,
                            // "=CATEGORY_ID" => $currentCategoryBtxId,
                            'COMPANY_ID' => $this->entityId,
                            // "ASSIGNED_BY_ID" => $this->responsibleId,
                            "=STAGE_ID" =>  $includedStages

                        ],
                        // 'select' => ["ID", "CATEGORY_ID", "STAGE_ID", 'TITLE'],

                    ],
                    'list',
                    null


                );
                $key = '0_list_deals_current';

                $closeCommands = [
                    $key => $currentDealsBatchCommand,

                ];

                // Теперь создаем команды для обновления каждой сделки на основе полученных данных
                for ($i = 0; $i < 15; $i++) { // Здесь $i — это индекс, используемый для ссылки на каждую сделку

                    $closeCommand = BitrixDealBatchFlowService::getBatchCommand(
                        [

                            'STAGE_ID' => 'C' . $categoryId . ':APOLOGY',

                        ],


                        'update',
                        '$result[' . $key . '][' . $i . '][ID]'
                    );

                    $closeCommands["update_deal_{$i}"] =   $closeCommand;

                    // [
                    //     'method' => 'crm.deal.update',
                    //     'params' => [
                    //         'ID' => '$result[get_deals][result][' . $i . '][ID]', // Формат подстановки из документации
                    //         'fields' => [
                    //             'STAGE_ID' => 'C' . $categoryId . ':APOLOGY'
                    //         ]
                    //     ]
                    // ];
                }
                $batchService->sendGeneralBatchRequest($closeCommands);
            }
        }


        $command = BitrixBatchService::batchCommand(
            $this->entityFieldsUpdatingContent,
            $this->entityType,
            $this->entityId,
            'update'
        );
        $key = 'entity_update' . '_' .  $this->entityType . '_' . $this->entityId;
        $batchCommands[$key] = $command; // в результате будет id


        /** TASKS BATCH */

        $batchService->sendGeneralBatchRequest($batchCommands);


        BitrixGeneralService::updateContactsToCompanyRespnsible(
            $this->hook,
            $this->entityId,
            ["ASSIGNED_BY_ID" => $this->responsibleId]
        );

        return [
            'planDeals' => ' $planDeals',
        ];
    }




    //tasks for complete



}
