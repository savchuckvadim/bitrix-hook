<?php

namespace App\Services\HookFlow;


use App\Http\Controllers\APIOnlineController;
use App\Services\BitrixGeneralService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BitrixEntityFlowService


{

    public function __construct()
    {
    }


    static function flow(
        $portal,
        $hook,
        $entityType,
        $entityId,
        $eventType, // xo warm presentation,
        $eventAction,  // plan done expired 
        $entityFieldsUpdatingContent, //updting fields
    ) {
        sleep(1);
        try {
            if ($entityType == 'company') {
                $updatedCompany = BitrixEntityFlowService::updateCompanyCold($hook, $entityId, $entityFieldsUpdatingContent);
            } else if ($entityType == 'lead') {
                $updatedLead = BitrixEntityFlowService::updateLeadCold($hook, $entityId, $entityFieldsUpdatingContent);
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




    // company
    static function updateCompanyCold($hook, $companyId, $fields)
    {




        // UF_CRM_10_1709907744 - дата следующего звонка

        $result = null;
        // $fields = [
        //     // 'UF_CRM_1709798145' => $responsibleId,
        //     // 'UF_CRM_10_170990774' => $this->deadline,   //  - дата следующего звонка
        //     ...$this->entityFieldsUpdatingContent
        // ];



        $result =  BitrixGeneralService::updateCompany($hook, $companyId, $fields);
        // Log::channel('telegram')->error('APRIL_HOOK updateCompany', ['$result' => $result]);

        return $result;
    }


    //lead

    static function updateLeadCold($hook, $leadId, $fields)
    {


        // $responsibleId = $this->responsibleId;

        $result = null;


        // $fields = [
        //     'ASSIGNED_BY_ID' => $responsibleId,
        //     ...$this->entityFieldsUpdatingContent
        // ];


        $result =  BitrixGeneralService::updateLead($hook, $leadId, $fields);

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