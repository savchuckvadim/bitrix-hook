
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
            'links' => 'required',
            'message' => 'required|string',
            'companyId' => 'required|string',

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

        $timeLineService->setTimeLine($timeLineString, 'company', $data['domain']);


        return APIOnlineController::getSuccess(
            [
                'message' => 'push'
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
