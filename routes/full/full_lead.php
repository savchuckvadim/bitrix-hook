<?php

use App\Http\Controllers\Front\EventCalling\Lead\FullEventFlowLeadController;
use Illuminate\Support\Facades\Route;

Route::prefix('/lead')->group(function () {

    Route::post('/cold', [FullEventFlowLeadController::class, 'cold']);

});
