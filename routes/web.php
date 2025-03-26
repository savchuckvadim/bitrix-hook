<?php

use Illuminate\Support\Facades\Http;
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

// Route::post('/{any?}', [App\Http\Controllers\HomeBitrixController::class, 'index'])
//     ->where('any', '^(?!api\/)[\/\w\.-]*');
// Route::get('/{any?}', [App\Http\Controllers\HomeBitrixController::class, 'index'])
//     ->where('any', '^(?!api\/)[\/\w\.-]*');

// Route::get('/{any?}', [App\Http\Controllers\HomeController::class, 'index'])
//     ->where('any', '^(?!api\/)[\/\w\.-]*');

// Route::post('/{any?}', [App\Http\Controllers\HomeController::class, 'post'])
//     ->where('any', '^(?!api\/)[\/\w\.-]*');

// Auth::routes();

// Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');


// Route::get('/test/3479', function () {

 
//     dd([
//         'yo' => 'camon',
        
//     ]);

//     return 'yo';
// });


// HTML по POST

// Route::match(['post'], '/placement', function () {
//     $response = Http::get('http://localhost:3002/placement');
//     return response($response->body(), 200)
//         ->header('Content-Type', 'text/html');
// });

// // Группа прокси для ассетов
// Route::prefix('/placement')->group(function () {
//     Route::get('{path}', function ($path) {
//         $url = 'http://localhost:3002/placement/' . $path;
//         $response = Http::get($url);
//         return response($response->body(), $response->status())
//             ->withHeaders($response->headers());
//     })->where('path', '.*');
// });
