

<?php

use App\Http\Controllers\APIController;
use App\Http\Controllers\APIOnlineController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;



Route::prefix('helper')
    ->middleware('helper.come.api.key')
    ->group(function () {

        require __DIR__.'/audio.php';

       
    });
