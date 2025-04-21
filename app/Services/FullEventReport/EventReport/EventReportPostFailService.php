<?php

namespace App\Services\FullEventReport\EventReport;

use App\Http\Controllers\APIOnlineController;
use App\Services\BitrixGeneralService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class EventReportPostFailService

{
    protected $domain;

    protected $hook;

    protected $postFailDate;
    protected $postFailDateString;
    protected $postFailUserId = 251; //gsirk postfail user
    protected $postFailDateStringFieldId = 'UF_CRM_1401340642';  //gsirk old postfail date field type string
    protected $postFailDateFieldId = 'UF_CRM_CALL_NEXT_DATE'; //postfail date field type datetime
    protected $companyId;

    public function __construct(

        $data,

    ) {
        $this->domain = $data['domain'];
        $this->hook = $data['hook'];
        $this->companyId = $data['companyId'];
        if (!empty($data['fail'])) {
            if (!empty($data['fail']['postFailDate'])) {
                $date = $data['fail']['postFailDate'];
                // $this->postFailDate = $data['fail']['postFailDate'];
                $carbonDate = Carbon::parse($date);
                $this->postFailDate =  $carbonDate->format('d.m.Y H:i:s');
                $this->postFailDateString =  $carbonDate->format('d.m.Y');

            }
        }
        // {
        //     "postFailDate": "2025-02-09T11:46"
        //   }
    }

    public function processPostFail()
    {
        try {
            sleep(2);
            if ($this->domain == 'gsirk.bitrix24.ru') {
                if (!empty($this->hook)) {
                    if (!empty($this->companyId)) {
                        if (!empty($this->postFailDate)) {
                            $postFailDateString = $this->postFailDate;
                            // $this->postFailDate = date('d.m.Y H:i', strtotime($postFailDateString));
                            $fields = [
                                $this->postFailDateFieldId => $this->postFailDate,

                            ];

                            //USER
                            $fields = [
                                'ASSIGNED_BY_ID' => $this->postFailUserId,
                                $this->postFailDateStringFieldId => $this->postFailDateString,
                                $this->postFailDateFieldId => $this->postFailDate,

                            ];

                            $companyUpdate = BitrixGeneralService::updateEntity(
                                $this->hook,
                                'company',
                                $this->companyId,
                                $fields
                            );
                         
                        }
                    }
                }
            }
        } catch (\Throwable $th) {
            $errorMessages =  [
                'message'   => $th->getMessage(),
                'file'      => $th->getFile(),
                'line'      => $th->getLine(),
                'trace'     => $th->getTraceAsString(),
            ];
            APIOnlineController::sendLog('EventReportPostFailService', [

                'domain' => $this->domain,
                // 'companyId' => $companyId,
                'fields' => [

                    $this->postFailDateStringFieldId => $this->postFailDate,
                    $this->postFailDateFieldId => $this->postFailDate,

                ],
                'error' =>   $errorMessages

            ]);
        }
    }
}
