<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BitrixHookController;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/




Route::middleware(['rate.limit'])->group(function () {
    // новй холодный звонка из Откуда Угодно
    Route::post('cold', function (Request $request) {

    
        $controller = new BitrixHookController();
        return $controller->getColdCall(
            $request
        );
    });


    // Route::post('company/assigned', function (Request $request) {

    
    //     $controller = new BitrixHookController();
    //     return $controller->getColdCall(
    //         $request
    //     );
    // });
});






















































// Route::post('/taskevent', function (Request $request) {
//     //     http://portal.bitrix24.com/rest/placement.bind/?access_token=sode3flffcmv500fuagrprhllx3soi72
//     // 	&PLACEMENT=CRM_CONTACT_LIST_MENU
//     // 	&HANDLER=http%3A%2F%2Fwww.applicationhost.com%2Fplacement%2F
//     // 	&TITLE=Тестовое приложение
//     // HTTP/1.1 200 OK
//     // {
//     // 	"result": true
//     // }
//     $actionUrl = '/placement.bind.json';
//     $domain = $request['auth']['domain'];
//     $portal = PortalController::getPortal($domain);
//     Log::info('portal', ['portal' => $portal]);

//     Log::info('taskevent', ['request' => $request->all()]);
//     try {

//         $webhookRestKey = $portal['data']['C_REST_WEB_HOOK_URL'];
//         $hook = 'https://' . $domain  . '/' . $webhookRestKey;

//         $url = $hook . $actionUrl;
//         $data = [
//             'PLACEMENT'=>'TASK_VIEW_SIDEBAR',
//             'HANDLER'=>'https://april-server/test/placement.php',
//             'LANG_ALL' => [
//                 'en' => [
//                     'TITLE' => 'Get Offer app',
//                     'DESCRIPTION' => 'App Helps Garant employees prepare commercial documents and collect sales funnel statistics',
//                     'GROUP_NAME' => 'Garant',
//                 ],
//                 'ru' => [
//                     'TITLE' => 'КП Гарант',
//                     'DESCRIPTION' => 'Приложение помогает сотрудникам Гарант составлять коммерческие документы и собирать статистику воронки продаж',
//                     'GROUP_NAME' => 'группа',
//                 ],
//             ],
//         ];




//         Log::info('taskevent', ['request' => $request->all()]);
//     } catch (\Throwable $th) {
//         Log::info('taskevent', ['request' => $request->all()]);
//         return APIOnlineController::getError(

//             'error callings ' . $th->getMessage(),
//             [
//                 // 'result' => $resultCallings,

//                 'error callings ' . $th->getMessage(),
//             ]
//         );
//     }
// });
