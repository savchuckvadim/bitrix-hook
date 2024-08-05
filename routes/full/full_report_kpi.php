<?php

use App\Http\Controllers\Front\ReportKPI\ReportKPIController;
use Illuminate\Support\Facades\Route;


Route::prefix('report')->group(function () {

    Route::post('/init', [ReportKPIController::class, 'frontInit']);
    // Route::post('/get', [ReportKPIController::class, 'getContractDocument']);
    Route::post('/filter', [ReportKPIController::class, 'getListFilter']);


});
