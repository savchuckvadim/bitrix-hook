
<?php

use App\Http\Controllers\APIOnlineController;
use App\Http\Controllers\PortalController;
use App\Services\General\BitrixTimeLineService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;




Route::post('timeline/set',  function (Request $request) {

    try {
        //code...

        $data = $request->validate([
            'domain' => 'required|string',
            'links' => 'sometimes',
            'message' => 'required|string',
            'companyId' => 'required|integer',

        ]);
        $hook = PortalController::getHook($data['domain']);
        $timeLineService = new BitrixTimeLineService($hook);

        $timeLineString = $data['message'];

        if (!empty($data['links'])) {

            foreach ($data['links'] as $linkData) {
                $message = '<a href="' . $linkData['value'] . '" target="_blank">' . $linkData['name'] . '</a>';
                $timeLineString .= "\n" . $message;
            }
        }

        $timeLineResult =  $timeLineService->setTimeLine($timeLineString, 'company', $data['companyId']);


        return APIOnlineController::getSuccess(
            [
                'message' => 'push',
                'bx_response' => $timeLineResult
            ]
        );
    } catch (\Throwable $th) {
        return APIOnlineController::sendFullError(
            $th,
            'helper.audio',
            'something wrong',
            $request->all(),

        );
    }
});
