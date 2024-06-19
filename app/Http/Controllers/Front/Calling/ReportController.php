<?php

namespace App\Http\Controllers\Front\Calling;

use App\Http\Controllers\APIController;
use App\Http\Controllers\APIOnlineController;
use App\Http\Controllers\Controller;
use App\Jobs\EventJob;
use App\Services\FullEventReport\EventReportService;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public static function eventReport(Request $request)
    {
        try {
            $data = [
                'currentTask' => null,
                'report' => null,
                'plan' => null,
                'placement' => null,
                'presentation' => null,
                'domain' => null,

            ];
            $isFullData = true;

            if (isset($request->currentTask)) {
                $data['currentTask'] = $request->currentTask;
            } else {
                $isFullData = false;
            }
            if (isset($request->report)) {
                $data['report'] = $request->report;
            } else {
                $isFullData = false;
            }
            if (isset($request->plan)) {
                $data['plan'] = $request->plan;
            } else {
                $isFullData = false;
            }
            if (isset($request->placement)) {
                $data['placement'] = $request->placement;
            } else {
                $isFullData = false;
            }
            if (isset($request->domain)) {
                $data['domain'] = $request->domain;
            } else {
                $isFullData = false;
            }
            if (isset($request->presentation)) {
                $data['presentation'] = $request->presentation;
            } else {
                $isFullData = false;
            }
            if ($isFullData) {
                dispatch(
                    new EventJob($data)
                )->onQueue('high-priority');

                return APIOnlineController::getSuccess(
                    [
                        'result' => 'success',
                        'message' => 'job !'

                    ]

                );
            } else {

                return APIOnlineController::getError(
                    'is not full data',
                    [
                        'rq' => $request->all()

                    ]

                );
            }
        } catch (\Throwable $th) {
            return APIOnlineController::getError(
                $th->getMessage(),
                [
                    'task' => [
                        'message' => 'success'
                    ],
                    'rq' => $request->all()

                ]

            );
        }
    }
}
