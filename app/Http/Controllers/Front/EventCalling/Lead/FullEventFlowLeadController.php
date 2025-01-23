<?php

namespace App\Http\Controllers\Front\EventCalling\Lead;

use App\Http\Controllers\APIBitrixController;
use App\Http\Controllers\APIOnlineController;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class FullEventFlowLeadController extends Controller
{


    public function cold(Request $request)
    {

        $data = $request->all();
        APIOnlineController::sendLog('FullEventFlowLeadController', $data);

        $leadId = $data['leadId'];

    }
}
