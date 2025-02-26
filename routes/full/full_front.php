<?php

use App\Http\Controllers\Front\EventCalling\List\HistoryController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('flow-front')->group(function () {

    Route::post('/history', function (Request $request) {
        $domain = $request->domain;
        $companyId = $request->companyId;
        $controller = new HistoryController($domain );
        return $controller->getHistory($companyId);

    });

    Route::post('/result/count', function (Request $request) {
        $domain = $request->domain;
        $companyId = $request->companyId;
        $userId = $request->userId;
        $controller = new HistoryController($domain );
        return $controller->getNoresultCount($companyId, $userId);

    });
    
});
