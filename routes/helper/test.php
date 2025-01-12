
<?php

use App\Http\Controllers\APIOnlineController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('text',  function (Request $request) {

    try {
  
        $data = $request->all();
        return response([
            'result' => 'OK',
            'message' => 'success',
            'data' => $data
        ]);
    } catch (\Throwable $th) {
        return APIOnlineController::sendFullError(
            $th,
            'helper.text',
            'something wrong',

        );
    }
});
