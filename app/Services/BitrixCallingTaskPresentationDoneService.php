<?php

namespace App\Services;

use App\Http\Controllers\APIOnlineController;
use App\Http\Controllers\PortalController;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
//проведено презентаций smart
// UF_CRM_10_1709111529 - april
// 	UF_CRM_6_1709894507 - alfa
// компании 
// UF_CRM_1709807026

//презентация проведена - bool
// UF_CRM_1696211878
class BitrixCallingTaskPresentationDoneService
{
    protected $portal;
    protected $aprilSmartData;
    protected $hook;
    protected $callingGroupId;
    protected $smartCrmId;
    protected $smartEntityTypeId;
    protected $domain;
    protected $companyId;
    protected $placement;
    protected $company;
    protected $currentBitrixSmart;
    protected $responsibleId;


    protected $categoryId;
    protected $stageId;

    public function __construct(
        $domain,
        $companyId,
        $responsibleId,
        $placement,
        $company,
        $smart

    ) {

        $portal = PortalController::getPortal($domain);
        $portal = $portal['data'];
        $this->portal = $portal;
        $this->aprilSmartData = $portal['bitrixSmart'];

        $categoryId = 26;
        $stageId = 'DT162_26:CLIENT';

        if ($domain === 'alfacentr.bitrix24.ru') {
            $categoryId = 12;
            $stageId = 'DT156_12:UC_DP0NEJ';
        }

        $this->categoryId = $categoryId;
        $this->stageId = $stageId;
        //alfa pre done DT156_12:UC_DP0NEJ 12
        $this->domain = $domain;
        $this->companyId = $companyId;
        $this->placement = $placement;
        $this->company = $company;
        $this->currentBitrixSmart = $smart;
        $this->$responsibleId = $responsibleId;




        $webhookRestKey = $portal['C_REST_WEB_HOOK_URL'];
        $this->hook = 'https://' . $domain  . '/' . $webhookRestKey;


        $callingTaskGroupId = env('BITRIX_CALLING_GROUP_ID');
        if (isset($portal['bitrixCallingTasksGroup']) && isset($portal['bitrixCallingTasksGroup']['bitrixId'])) {
            $callingTaskGroupId =  $portal['bitrixCallingTasksGroup']['bitrixId'];
        }

        $this->callingGroupId =  $callingTaskGroupId;

        $smartId = 'T9c_';
        if (isset($portal['bitrixSmart']) && isset($portal['bitrixSmart']['crm'])) {
            $smartId =  $portal['bitrixSmart']['crm'] . '_';
        }

        $this->smartCrmId =  $smartId;
    }

    public function presentationDone()
    {


        $currentSmartItemId = '';
        $currentCompanyCount = 0;
        $currentSmartCount = 0;
        $currentSmartItem  = $this->currentBitrixSmart;
        $smartFields = [];
        try {

            if ($currentSmartItem && isset($currentSmartItem) && isset($currentSmartItem['id'])) {
                $currentSmartItemId = $currentSmartItem['id'];
            }

            if ($this->company) {
                if (array_key_exists('UF_CRM_1709807026', $this->company)) {
                    // if ($this->company['UF_CRM_1709807026'] == null || $this->company['UF_CRM_1709807026'] == 0 || $this->company['UF_CRM_1709807026'] == "0") {
                    //     $this->company['UF_CRM_1709807026'] = 1;
                    // } else {
                    $currentCompanyCount = (int)$this->company['UF_CRM_1709807026'] + 1;
                    // }

                    $this->company['UF_CRM_1709807026'] = $currentCompanyCount;
                    if (array_key_exists('UF_CRM_1696211878', $this->company)) {

                        $this->company['UF_CRM_1696211878'] = 'Y';
                    }
                }
            }
            if ($this->currentBitrixSmart) {
                if (array_key_exists('ufCrm10_1709111529', $this->currentBitrixSmart)) {

                    // /april count
                    $currentSmartCount = (int)$this->currentBitrixSmart['ufCrm10_1709111529'] + 1;
                    $this->currentBitrixSmart['ufCrm10_1709111529'] = $currentSmartCount;

                    $smartFields = [
                        'ufCrm10_1709111529' => $currentSmartCount
                    ];
                } else if (array_key_exists('UF_CRM_6_1709894507', $this->currentBitrixSmart)) {

                    //alfa count
                    $currentSmartCount =    (int)$this->currentBitrixSmart['UF_CRM_6_1709894507'] + 1;
                    $this->currentBitrixSmart['UF_CRM_6_1709894507'] = $currentSmartCount;


                    $smartFields = [
                        'UF_CRM_6_1709894507' => $currentSmartCount
                    ];
                }
            }


            // $currentSmartItem  = $this->currentBitrixSmart;
            if (!$this->currentBitrixSmart) {
                $this->currentBitrixSmart =  $this->createSmartItemDone();
            }

            $updatedCompany = $this->updateCompany($this->company);
            $updatedSmart = $this->updateSmartItem($this->currentBitrixSmart, $smartFields);

            return APIOnlineController::getResponse(
                0,
                'success',
                [
                    'companyCount' => 0,
                    'smartCout' => 0,
                    'updatedCompany' => $updatedCompany,
                    'updatedSmart' => $updatedSmart,
                    'currentSmartItem' => $currentSmartItem,
                    // '$gettedSmart' => $gettedSmart,
                    'currentBitrixSmart' => $this->currentBitrixSmart,
                    // 'sale' => $this->sale,

                    // 'comment' => $this->comment,
                    // 'isNeedCreateSmart' => $this->isNeedCreateSmart,


                ]
            );
        } catch (\Throwable $th) {
            $errorMessages =  [
                'message'   => $th->getMessage(),
                'file'      => $th->getFile(),
                'line'      => $th->getLine(),
                'trace'     => $th->getTraceAsString(),
            ];
            Log::error('ERROR: Exception caught',  $errorMessages);
            Log::info('error', ['error' => $th->getMessage()]);
            return APIOnlineController::getResponse(1, $th->getMessage(),  $errorMessages);
        }
    }



    //task




    //smart
    protected function createSmartItemDone()
    {
        $methodSmart = '/crm.item.add.json';
        $url = $this->hook . $methodSmart;

        $companyId  = $this->companyId;
        $responsibleId  = $this->responsibleId;
        $smart  = $this->aprilSmartData;


        $resulFields = [];
        $fieldsData = [];
        $fieldsData['categoryId'] = $this->categoryId;
        $fieldsData['stageId'] = $this->stageId;
        $fieldsData['ufCrm7_1698134405'] = $companyId;
        $fieldsData['assigned_by_id'] = $responsibleId;
        $fieldsData['company_id'] = $companyId;
        // $fieldsData[$this->lastCallDateField] = $this->deadline;  //дата звонка следующего
        // $fieldsData[$this->lastCallDateFieldCold] = $this->deadline; //дата холодного следующего
        // $fieldsData[$this->callThemeField] = $this->name;      //тема следующего звонка
        // $fieldsData[$this->callThemeFieldCold] = $this->name;  //тема холодного звонка






        $entityId = $smart['crmId'];
        $data = [
            'entityTypeId' => $entityId,
            'fields' =>  $fieldsData

        ];

        // Возвращение ответа клиенту в формате JSON

        $smartFieldsResponse = Http::get($url, $data);
        $bitrixResponse = $smartFieldsResponse->json();


        if (isset($smartFieldsResponse['result'])) {
            $resultFields = $smartFieldsResponse['result'];
        }
        return $resultFields;
    }

    protected function updateSmartItem($smartItemFromBitrix, $smartFields)
    {
        $isCanChange = false;

        $domain = $this->domain;
        $hook = $this->hook;
        $smart = $this->aprilSmartData;


        $result = null;

        $methodSmart = '/crm.item.update.json';
        $url = $hook . $methodSmart;
        $entityId = $smart['crmId'];
        //         stageId: 

        //дата следующего звонка smart
        // UF_CRM_6_1709907693 - alfa
        // UF_CRM_10_1709907744 - april


        //комментарии smart
        //UF_CRM_6_1709907513 - alfa
        // UF_CRM_10_1709883918 - april


        //название обзвона - тема
        // UF_CRM_6_1709907816 - alfa
        // UF_CRM_10_1709907850 - april
        $stagesForWarm = [
            // april
            'DT162_26:NEW',
            'DT162_26:PREPARATION',
            'DT162_26:FAIL',

            'DT162_28:NEW',
            'DT162_28:UC_J1ADFR',
            'DT162_28:PREPARATION',
            'DT162_28:UC_BDM2F0',
            'DT162_28:SUCCESS',
            'DT162_28:FAIL',

            //presentation
            'DT162_26:UC_Q5V5H0',

            //alfa
            'DT156_12:NEW',
            'DT156_12:CLIENT',
            'DT156_12:UC_E4BPCB',
            'DT156_12:UC_Y52JIL',
            'DT156_12:UC_02ZP1T',
            'DT156_12:FAIL',

            'DT156_14:NEW',
            'DT156_14:UC_TS7I14',
            'DT156_14:UC_8Q85WS',
            'DT156_14:PREPARATION',
            'DT156_14:CLIENT',
            'DT156_14:SUCCESS',
            'DT156_14:FAIL',


            //presentation
            'DT156_12:UC_LEWVV8',


        ];

        $stageId = null;
        $fields = null;
        $smartItemId = null;
        $targetStageId = 'DT162_26:FAIL';

        // $lastCallDateField = 'ufCrm10_1709907744';
        // $commentField = 'ufCrm10_1709883918';
        // $callThemeField = 'ufCrm10_1709907850';


        // if ($domain == 'alfacentr.bitrix24.ru') {
        //     $lastCallDateField = 'ufCrm6_1709907693';
        //     $commentField = 'ufCrm6_1709907513';
        //     $callThemeField = 'ufCrm6_1709907816';
        // }



        if (isset($smartItemFromBitrix['stageId'])) {
            $stageId =  $smartItemFromBitrix['stageId'];
        }

        if (isset($smartItemFromBitrix['id'])) {
            $smartItemId =  $smartItemFromBitrix['id'];
        }
        $parts = explode(':', $stageId);


        $data = [
            'entityTypeId' => $entityId,
            'id' =>  $smartItemId,
            'fields' => $smartFields


        ];

        $smartFieldsResponse = Http::get($url, $data);
        $bitrixResponse = $smartFieldsResponse->json();


        if (isset($smartFieldsResponse['result'])) {
            $result = $smartFieldsResponse['result'];
        } else  if (isset($smartFieldsResponse['error_description'])) {
            $result = $smartFieldsResponse['error_description'];
        }


        // Возвращение ответа клиенту в формате JSON

        $testingResult = [
            $domain,
            $stageId,
            $smart, //data from back
            $smartItemFromBitrix,
            // $type,
            $targetStageId,
            $data,
            // 'isCanChange' => $isCanChange,
            'bitrixResult' => $result

        ];

        return $testingResult;
    }




    //company

    protected function updateCompany($company)
    {

        // компании count
        // UF_CRM_1709807026

        //презентация проведена - bool
        // UF_CRM_1696211878

        $hook = $this->hook;
        // $responsibleId = $this->responsibleId;
        // $type = $this->type;
        // $deadline = $this->deadline;
        $companyId = $this->companyId;



        $method = '/crm.company.update.json';
        $result = null;

        $getUrl = $hook . $method;
        $fieldsData = [
            'id' => $companyId,
            'fields' => $company
        ];

        $response = Http::get($getUrl,  $fieldsData);
        if ($response) {
            if (isset($response['result'])) {
                $result =  $response['result'];
            } else if (isset($response['error_description'])) {
                $result =  $response['error_description'];
            }
        }

        return [
            'updcompanyBitrixResult' => $result,
            'company' => $company
        ];
    }
}
