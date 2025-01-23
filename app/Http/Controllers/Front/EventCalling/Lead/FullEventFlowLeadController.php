<?php

namespace App\Http\Controllers\Front\EventCalling\Lead;

use App\Http\Controllers\APIBitrixController;
use App\Http\Controllers\APIOnlineController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\PortalController;
use App\Jobs\EventBatch\ColdBatchJob;
use App\Services\BitrixGeneralService;
use Illuminate\Http\Request;

class FullEventFlowLeadController extends Controller
{


    public function cold(Request $request)
    {

        $data = $request->all();

        $leadId = $data['leadId'];
        $deadline = $data['deadline'];
        $assigned = $data['assigned'];
        $name = $data['name'];
        $domain  = $data['auth']['domain'];
        $hook = PortalController::getHook($domain);
        $responsibleId = null;
        $createdId = 1;
        $companyId = null;

        if (isset($assigned)) {

            $partsResponsible = explode("_", $assigned);

            $responsibleId = $partsResponsible[1];
        }


        APIOnlineController::sendLog('FullEventFlowLeadController', [

            'leadId' => $leadId,
            'domain' => $domain,
            'deadline' => $deadline,
            'assigned' => $assigned,
        ]);
        $lead = BitrixGeneralService::getEntityByID($hook, 'lead', $leadId);

        $fields = [];
        $fields['LEAD_ID'] = $leadId;
   
        //UF_CRM_LEAD_QUEST_URL ссылка на отчет
        if (!empty($lead['COMPANY_ID'])) {
            $companyId = $lead['COMPANY_ID'];
            BitrixGeneralService::updateEntity($hook, 'company', $companyId,   $fields);
        }else{
            $fields['TITLE'] = $lead['TITLE'];
            $companyId = BitrixGeneralService::setEntity($hook, 'company', $fields);

        }

     
        // $fields['ASSIGNED_BY_ID'] = $lead['ASSIGNED_BY_ID'];
        // $fields['PHONE'] = $lead['PHONE'];
        // $fields['EMAIL'] = $lead['EMAIL'];


        $leadUpdate = BitrixGeneralService::updateEntity($hook, 'lead', $leadId,   [
            'COMPANY_ID' => $companyId,
            //  'STATUS_ID' => 'PROCESSED'
            // 'STATUS_ID' => 'CONVERTED'
        ]);

        APIOnlineController::sendLog('FullEventFlowLeadController', [

            'leadUpdate' => $leadUpdate,

        ]);


        $data = [
            'domain' => $domain,
            'entityType' => 'company',
            'entityId' => $companyId,
            'responsible' => $responsibleId,
            'created' => $createdId,
            'deadline' => $deadline,
            'name' => $name,
            'lead' => $lead,
            'isTmc' => false

        ];

        dispatch(
            new ColdBatchJob($data)
        );
    }
}
