<?php

namespace App\Services\FullEventReport\EventReport\EventReportPostFailService;

use App\Http\Controllers\APIOnlineController;
use App\Services\BitrixGeneralService;
use Carbon\Carbon;

class EventReportPostFailService

{
    protected $domain;

    protected $hook;

    protected $postFailDate;
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

            }
        }
        // {
        //     "postFailDate": "2025-02-09T11:46"
        //   }
    }

    public function processPostFail()
    {
        try {
            if (!empty($this->hook)) {
                if (!empty($this->companyId)) {
                    if (!empty($this->postFailDate)) {
                        $postFailDateString = $this->postFailDate;
                        // $this->postFailDate = date('d.m.Y H:i', strtotime($postFailDateString));
                        $fields = [
                            $this->postFailDateFieldId => $this->postFailDate,

                        ];
                        if ($this->domain == 'gsirk.bitrix24.ru') {
                            //USER
                            $fields = [
                                'ASSIGNED_BY_ID' => $this->postFailUserId,
                                $this->postFailDateStringFieldId => $this->postFailDate,
                                $this->postFailDateFieldId => $this->postFailDate,

                            ];
                        }
                        $companyUpdate = BitrixGeneralService::updateEntity(
                            $this->hook,
                            'lead',
                            $this->companyId,
                            $fields
                        );
                        APIOnlineController::sendLog('EventReportPostFailService', [

                            'companyUpdate' => $companyUpdate,
                            'fields' => [
                                'domain' => $this->domain,
                                $this->postFailDateStringFieldId => $this->postFailDate,
                                $this->postFailDateFieldId => $this->postFailDate,

                            ],

                        ]);
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
