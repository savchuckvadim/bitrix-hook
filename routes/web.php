<?php

use App\Http\Controllers\MigrateCRM\MigrateCRMController;
use App\Http\Controllers\PortalController;
use App\Jobs\CRMMigrateJob;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Route::get('/{any?}', [App\Http\Controllers\HomeController::class, 'index'])->where('any', '^(?!api\/)[\/\w\.-]*');

// Auth::routes();

// Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');


Route::get('/test/3479', function () {

    $domain = 'april-dev.bitrix24.ru';
    $portal = PortalController::getPortal($domain);
    if(!empty($portal) && !empty($portal['data'])){
        $result = $portal['data']['id'];

    }else{
        $result = $portal;

    }
    dd([
        'result' => $result,
        
    ]);

    return 'yo';
});
