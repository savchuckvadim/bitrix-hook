<?php

namespace App\Http\Controllers\Front\EventCalling\Lead;

use App\Http\Controllers\APIBitrixController;
use App\Http\Controllers\APIOnlineController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\PortalController;
use App\Services\BitrixGeneralService;
use Illuminate\Http\Request;

class FullEventFlowLeadController extends Controller
{


    public function cold(Request $request)
    {

        $data = $request->all();

        $leadId = $data['leadId'];
        $domain  = $data['auth']['domain'];
        $hook = PortalController::getHook($domain);

        APIOnlineController::sendLog('FullEventFlowLeadController', [

            'leadId' => $leadId,
            'domain' => $domain,
        ]);
        $lead = BitrixGeneralService::getEntityByID($hook, 'lead', $leadId);

        $fields = [];
        //UF_CRM_LEAD_QUEST_URL ссылка на отчет

        foreach ($lead as $key => $value) {
            if (
                $key === 'TITLE' ||
                $key === 'ASSIGNED_BY_ID' ||
                $key === 'PHONE' ||
                $key === 'EMAIL' 
                
                ) {
                APIOnlineController::sendLog('FullEventFlowLeadController', [

                    $key => $value

                ]);
            }
        }
        $fields['LEAD_ID'] = $leadId;
        $fields['TITLE'] = $lead['TITLE'];
        // $fields['ASSIGNED_BY_ID'] = $lead['ASSIGNED_BY_ID'];
        // $fields['PHONE'] = $lead['PHONE'];
        // $fields['EMAIL'] = $lead['EMAIL'];
        APIOnlineController::sendLog('FullEventFlowLeadController', [

            'fields' => $fields,
        ]);
        $companyId = BitrixGeneralService::setEntity($hook, 'company', $fields);
        APIOnlineController::sendLog('FullEventFlowLeadController', [

            'companyId' => $companyId,

        ]);
        $leadUpdate = BitrixGeneralService::updateEntity($hook,'lead', $leadId,  ['fields' => [
            'COMPANY_ID' => $companyId,
            'STATUS_ID' => 'PROCESSED'
        ]]);

        APIOnlineController::sendLog('FullEventFlowLeadController', [

            'leadUpdate' => $leadUpdate,

        ]);
    }
}
