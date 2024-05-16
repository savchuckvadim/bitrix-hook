<?php

namespace App\Services\HookFlow;

use App\Services\BitrixGeneralService;

class BitrixSmartFlowService




{


    public function __construct()
    {
    }


    //smart
    static function flow(
        $aprilSmartData,
        $hook,
        $entityType,
        $entityId,
        $eventType, // xo warm presentation,
        $eventAction,  // plan done expired 
        $responsibleId,
        $fieldsData, //updting fields
        // $categoryId,
        // $stageId,
        // $createdId,
        
        // $companyId,
        // $leadId,
        // $lastCallDateField,
        // $deadline,
        // $lastCallDateFieldCold,
        // $callThemeField,
        // $name,
        // $callThemeFieldCold,
        // $createdFieldCold,

    ) {
        if ($entityType !== 'smart') {
            $currentSmart = BitrixSmartFlowService::getSmartItem(
                $hook,
                $responsibleId,
                $aprilSmartData,
                $entityType,
                $entityId
            );
            if ($currentSmart) {
                if (isset($currentSmart['id'])) {
                    $currentSmart = BitrixSmartFlowService::updateSmartItemCold(
                        $hook,
                        $currentSmart['id'],
                        $aprilSmartData,
                        $entityType,
                        $entityId,
                        $fieldsData
                    );
                }
            } else {
                $currentSmart = BitrixSmartFlowService::createSmartItemCold(
                    $hook,
                    $aprilSmartData,
                    $entityType,
                    $entityId,
                    $fieldsData
                );
                // $currentSmart = $this->updateSmartItemCold($currentSmart['id']);
                // Log::channel('telegram')->error('APRIL_HOOK createSmartItemCold', ['currentSmart' => $currentSmart]);
            }
        } else {
            $currentSmart = BitrixSmartFlowService::updateSmartItemCold(
                $hook,
                $entityId,
                $aprilSmartData,
                $entityType,
                $entityId,
                $fieldsData

            );
        }

        if ($currentSmart && isset($currentSmart['id'])) {
            $currentSmartId = $currentSmart['id'];
        }
    }
    protected function getSmartItem(
        $hook,
        $responsibleId,
        $aprilSmartData,
        $entityType,
        $entityId

    ) {
        // lidIds UF_CRM_7_1697129081
        $leadId  = null;

        $companyId = null;
        $userId = $responsibleId;
        $smart = $aprilSmartData;

        $currentSmart = null;


        if ($entityType == 'company') {

            $companyId  = $entityId;
        } else if ($entityType == 'lead') {
            $leadId  = $entityId;
        }



        $currentSmart = BitrixGeneralService::getSmartItem(
            $hook,
            $leadId, //lidId ? from lead
            $companyId, //companyId ? from company
            $userId,
            $smart, //april smart data
        );


        return $currentSmart;
    }

    protected function createSmartItemCold(
        $hook,
        $aprilSmartData,
        $entityType,
        $entityId,
        $fieldsData

    ) {

       

        $entityId = $aprilSmartData['crmId'];

        $resultFields = BitrixGeneralService::createSmartItem(
            $hook,
            $entityId,
            $fieldsData
        );



        return $resultFields;
    }

    protected function updateSmartItemCold(
        $hook,
        $smartId,
        $aprilSmartData,
        $entityType,
        $entityId,
        $fieldsData
    ) {


        $entityId = $aprilSmartData['crmId'];
 
        $resultFields = BitrixGeneralService::updateSmartItem(
            $hook,
            $entityId,
            $smartId,
            $fieldsData
        );
        return $resultFields;
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