<?php

namespace App\Http\Controllers\Front\Calling;

use App\Http\Controllers\APIController;
use App\Http\Controllers\APIOnlineController;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public static function eventReport(Request $request)
    {

        return APIOnlineController::getSuccess(
            [
                'task' => [
                    'message' => 'success'
                ],
                'rq' => $request

            ]

        );
    }
}
