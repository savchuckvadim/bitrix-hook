<?php

namespace App\Http\Controllers\Front\EventCalling\Lead;

use App\Http\Controllers\APIBitrixController;
use App\Http\Controllers\APIOnlineController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\PortalController;
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
            'hook' => $hook,
        ]);
    }
}
