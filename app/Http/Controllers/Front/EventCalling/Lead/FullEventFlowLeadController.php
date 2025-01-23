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
        $lead = BitrixGeneralService::getEntity($hook, 'lead', $leadId);

        APIOnlineController::sendLog('FullEventFlowLeadController', [

            'leadId' => $leadId,
            'domain' => $domain,
            'lead' => $lead,
        ]);
        $fields = [];
        foreach ($lead as $key => $value) {
            if ($key !== 'ID') {
                $fields[$key] = $value;
            }
        }
        $company = BitrixGeneralService::setEntity($hook, 'company', ['fields' => $fields]);
        APIOnlineController::sendLog('FullEventFlowLeadController', [

            'company' => $company,
    
        ]);
    }
}
