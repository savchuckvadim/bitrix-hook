<?php

namespace App\Services\FullEventReport\EventReport\EventReportRelationLeadService;


use App\Http\Controllers\APIOnlineController;
use App\Services\BitrixGeneralService;
use Illuminate\Support\Facades\Log;

class EventReportRelationLeadService

{
    protected $domain;
    protected $portal;

    protected $hook;

    protected $lead = null;
    protected $status; // fail || success


    public function __construct(

        $data,

    ) {
        $this->domain = $data['domain'];
        $this->hook = $data['hook'];
        $this->lead = $data['lead'];
        $this->status = $data['status'];  // 'success' | 'fail'
    }

    public function processLead()
    {
        try {
            Log::channel('telegram')->info(
                'processLead',
                [
                    '$domain' => $this->domain,
                    '$hook' => $this->hook,
                    '$status' => $this->status,
    
                ]
            );
            if (!empty($this->lead)) {
                if (!empty($this->lead['ID'])) {

                    Log::channel('telegram')->info(
                        'processLead',
                        [
                            '$domain' => $this->domain,
                            '$hook' => $this->hook,
                            '$leadId' => $this->lead['ID'],
                            '$status' => $this->status,
            
                        ]
                    );



                    $leadId = $this->lead['ID'];
                    $bxStatusId = 'CONVERTED';
                    if (!empty($this->status)) {
                        if ($this->status == 'fail') {
                            $bxStatusId = 'JUNK';
                        }
                    }
                    $leadUpdate = BitrixGeneralService::updateEntity($this->hook, 'lead', $leadId,   [
                        // 'COMPANY_ID' => $companyId,
                        'STATUS_ID' => $bxStatusId
                        // 'STATUS_ID' => 'CONVERTED'
                    ]);

                    APIOnlineController::sendLog('EventReportRelationLeadService', [

                        'leadUpdate' => $leadUpdate,

                    ]);
                }
            }
        } catch (\Throwable $th) {
            $errorMessages =  [
                'message'   => $th->getMessage(),
                'file'      => $th->getFile(),
                'line'      => $th->getLine(),
                'trace'     => $th->getTraceAsString(),
            ];
            APIOnlineController::sendLog('EventReportRelationLeadService', [

                'domain' => $this->domain,
                // 'companyId' => $companyId,
                'error' =>   $errorMessages

            ]);
        }
    }
}
