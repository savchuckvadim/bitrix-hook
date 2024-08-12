<?php

use App\Http\Controllers\MigrateCRM\MigrateCRMController;
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

Route::get('/{any?}', [App\Http\Controllers\HomeController::class, 'index'])->where('any', '^(?!api\/)[\/\w\.-]*');

// Auth::routes();

// Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');


Route::get('/gsr/crm/{pass}/{domain}/{token}/', function ($pass, $domain, $token) {
    // $url = LinkController ::urlForRedirect($linkId);
    // dd([
    //     'pass' => $pass,
    //     'domain' => $domain,
    //     'token' => $token,
    // ]);

    if ($pass == 'nmbrsdntl' && $domain) {
        return MigrateCRMController::crm($token, $domain);
    } else {
    return 'yo';
    }
});