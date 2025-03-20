<?php



use App\Http\Controllers\Yandex\TranscribationController;
use Illuminate\Support\Facades\Route;

Route::prefix('transcription')->group(function () {
   
 
    Route::post( '',[TranscribationController::class, 'getTranscribation']);
    Route::get( '{taskId}',[TranscribationController::class, 'getTranscriptionResult']);

});


