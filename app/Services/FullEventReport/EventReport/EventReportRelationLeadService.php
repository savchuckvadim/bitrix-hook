<?php

namespace App\Services\FullEventReport\EventReport\EventReportRelationLeadService;


use App\Http\Controllers\APIOnlineController;
use App\Services\BitrixGeneralService;
use Illuminate\Support\Facades\Log;

class EventReportRelationLeadService

{
    protected $domain;
    // protected $portal;

    protected $hook;

    protected $leadId = null;
    protected $status; // fail || success


    public function __construct(

        $domain,
        $hook,
        $leadId,
        $status,

    ) {
        $this->domain = $domain;
        $this->hook = $hook;
        $this->leadId = $leadId;
        $this->status = $status;  // 'success' | 'fail'
    }

    public function processLead()
    {
        try {
            Log::channel('telegram')->info(
                'processLead',
                [
                    '$domain' => $this->domain,
                    // '$hook' => $this->hook,
                    '$status' => $this->status,

                ]
            );
            if (!empty($this->leadId)) {
                // if (!empty($this->lead['ID'])) {

                Log::channel('telegram')->info(
                    'processLead',
                    [

                        '$leadId' => $this->leadId,
                        '$status' => $this->status,

                    ]
                );



                // $leadId = $this->lead['ID'];
                $bxStatusId = 'CONVERTED';
                if (!empty($this->status)) {
                    if ($this->status == 'fail') {
                        $bxStatusId = 'JUNK';
                    }
                }
                $leadUpdate = BitrixGeneralService::updateEntity(
                    $this->hook,
                    'lead',
                    $this->leadId,
                    [
                        // 'COMPANY_ID' => $companyId,
                        'STATUS_ID' => $bxStatusId
                        // 'STATUS_ID' => 'CONVERTED'
                    ]
                );

                APIOnlineController::sendLog('EventReportRelationLeadService', [

                    'leadUpdate' => $leadUpdate,

                ]);
                // }
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
