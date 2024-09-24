<?php

use App\Http\Controllers\APIOnlineController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BitrixHookController;
use App\Services\FullBatch\ColdBatchService;
use Illuminate\Support\Facades\Log;

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




// Route::middleware(['rate.limit'])->group(function () {
// новй холодный звонка из Откуда Угодно
Route::post('cold', function (Request $request) {

  
    $controller = new BitrixHookController();
    return $controller->getColdCall(
        $request
    );
});

Route::post('coldtest', function (Request $request) {

    $createdId =  null;
    $responsibleId =  null;
    $entityId =  null;
    $entityType = null;
    //     Log::channel('telegram')->error('APRIL_HOOK', [

    //         'deadline' => $request['deadline'],
    //         // 'название обзвона' => $name,
    //         // 'companyId' => $companyId,
    //         // 'domain' => $domain,
    //         // 'responsibleId' => $responsibleId,
    //         // 'btrx response' => $response['error_description']

    // ]);
    try {
        date_default_timezone_set('Europe/Moscow');
        $nowDate = new DateTime();
        setlocale(LC_TIME, 'ru_RU.utf8');
        // Форматируем дату и время в нужный формат
        $locale = 'ru_RU';
        $pattern = 'd MMMM yyyy';

        // Создаем форматтер
        $formatter = new IntlDateFormatter(
            $locale,
            IntlDateFormatter::NONE,
            IntlDateFormatter::NONE,
            date_default_timezone_get(),
            IntlDateFormatter::GREGORIAN,
            $pattern
        );

        $formattedStringNowDate = $formatter->format($nowDate);
        $name = 'от ' . $formattedStringNowDate;
        if (isset($request['name'])) {
            if (!empty($request['name'])) {
                $name = $request['name'];
            }
        }



        if (isset($request['created'])) {
            $created = $request['created'];
            $partsCreated = explode("_", $created);
            $createdId = $partsCreated[1];
        }

        if (isset($request['responsible'])) {
            $responsible = $request['responsible'];
            $partsResponsible = explode("_", $responsible);

            $responsibleId = $partsResponsible[1];
        }



        $auth = $request['auth'];
        $domain = $auth['domain'];
        if (isset($request['entity_id'])) {
            $entityId = $request['entity_id'];
        }

        if (isset($request['entity_type'])) {
            $entityType  = $request['entity_type'];
        }

        $deadline = $request['deadline'];
        // $crm = $request['crm'];
        // if (isset($request['name'])) {
        //     $name = $request['name'];
        // }

        if (isset($request['isTmc'])) {
            $isTmc  = $request['isTmc'];
        }

        $data = [
            'domain' => $domain,
            'entityType' => $entityType,
            'entityId' => $entityId,
            'responsible' => $responsibleId,
            'created' => $createdId,
            'deadline' => $deadline,
            'name' => $name,
            'isTmc' => $isTmc

        ];
        // Log::info('APRIL_HOOK pre rerdis', ['$data' => $data]);

        $service = new ColdBatchService(
            $data
        );
        $reult =  $service->getCold();
        // $service = new BitrixCallingColdService($data);
        // $reult =  $service->getCold();

        return APIOnlineController::getSuccess(['result' => $reult]);

        // return APIOnlineController::getSuccess(['result' => 'job catch it!']);
    } catch (\Throwable $th) {
        $errorMessages =  [
            'message'   => $th->getMessage(),
            'file'      => $th->getFile(),
            'line'      => $th->getLine(),
            'trace'     => $th->getTraceAsString(),
        ];
        Log::error('ERROR COLD BTX HOOK CONTROLLER: Exception caught',  $errorMessages);
    }
});


    // Route::post('company/assigned', function (Request $request) {

    
    //     $controller = new BitrixHookController();
    //     return $controller->getColdCall(
    //         $request
    //     );
    // });
// });






















































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
