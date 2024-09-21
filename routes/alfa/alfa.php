<?php

use App\Http\Controllers\APIOnlineController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BitrixHookController;
use App\Http\Controllers\PortalController;
use App\Services\FullBatch\ColdBatchService;
use App\Services\General\BitrixListService;
use App\Services\General\BitrixTimeLineService;
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
Route::post('alfa/contract-specification', function (Request $request) {

    $data = $request->all();
    try {
        // $companyId = $data['companyId'];
        $smartId = $data['smartId'];
        $domain = $data['auth']['domain'];
        $listBitrixId = $data['listBitrixId'];
        $hook = PortalController::getHook($domain);
        $listFilter = [
            // 'PROPERTY_' => $companyId,
            'PROPERTY_192' => $smartId,

        ];
        $listItems  = BitrixListService::getList($hook, $listBitrixId, $listFilter);
        //get smart -> smart list UF_CRM


        $documentNumber = $data['documentNumber'];
        $companyName = $data['companyName'];
        $position = $data['position'];
        $director = $data['director'];


        $persons = [];

        foreach ($listItems as $key => $listItem) {
            $person = [
                'personNumber' =>  $key + 1 . '. ',
                'person' => $listItem['NAME'],

            ];
            foreach ($listItem['PROPERTY_204'] as $key => $value) {
                $person['product'] = $value;
            }
            array_push($persons, $person);
        }
        date_default_timezone_set('Asia/Novosibirsk');
        $nowDate = new DateTime();
        setlocale(LC_TIME, 'ru_RU.utf8');
        // Форматируем дату и время в нужный формат
        $documentCreateDate = $nowDate->format('d.m.Y H:i:s');
        // $locale = 'ru_RU';
        // $pattern = 'd MMMM yyyy';

        // // Создаем форматтер
        // $formatter = new IntlDateFormatter(
        //     $locale,
        //     IntlDateFormatter::NONE,
        //     IntlDateFormatter::NONE,
        //     date_default_timezone_get(),
        //     IntlDateFormatter::GREGORIAN,
        //     $pattern
        // );

        // // Форматируем дату
        // $documentCreateDate = $formatter->format($nowDate);
        $documentCreateDate =  $documentCreateDate . 'г.';


        $documentData = [
            'documentNumber' => $documentNumber,
            'documentCreateDate' => $documentCreateDate,
            'persons' => $persons,
            'companyName' => $companyName,
            'position' => $position,
            'director' => $director,

        ];
        Log::channel('telegram')->info('TST HOOK ALFA', [
            'listItems' => $listItems
        ]);

        Log::info('TST HOOK ALFA', [
            'listItems' => $listItems
        ]);
        $documentLinkData = APIOnlineController::online('post', 'alfa/specification', $documentData, 'link');
        $documentLink =  $documentLinkData;
        if (!empty($documentLinkData['data'])) {
            $documentLink = $documentLinkData['data'];
        }
        if (!empty($documentLink['link'])) {
            $documentLink = $documentLink['link'];
        }
        $resultText = 'Приложение к договору ППК';

        $message = "\n" . 'Приложение: <a href="' . $documentLink . '" target="_blank">' . $resultText . '</a>';

        $timeLine = new BitrixTimeLineService($hook);
        $timeLine->setTimeLine($message, 'DYNAMIC_159', $smartId);
        Log::channel('telegram')->info('TST HOOK ALFA', [
            'documentLink' => $documentLink
        ]);

        Log::info('TST HOOK ALFA', [
            'documentLink' => $documentLink
        ]);

        APIOnlineController::getSuccess([
            'link' => $documentLink
        ]);
    } catch (\Throwable $th) {
        //throw $th;
    }
});



Route::get('alfa/contract-specification/{domain}/{smartId}', function ($domain, $smartId) {
    Log::channel('telegram')->info('TST HOOK ALFA', [
        'yo' => 'yo'
    ]);

    $listBitrixId = 48;
    $hook = PortalController::getHook($domain);
    $listFilter = [
        // 'PROPERTY_' => $companyId,
        'PROPERTY_192' => $smartId,

    ];
    $listItems  = BitrixListService::getList($hook, $listBitrixId, $listFilter);
    $persons = [];

    foreach ($listItems as $key => $listItem) {
        $person = [
            'personNumber' =>  $key + 1 . '. ',
            'person' => $listItem['NAME'],

        ];
        foreach ($listItem['PROPERTY_204'] as $key => $value) {
            $person['product'] = $value;
        }
        array_push($persons, $person);
    }
    date_default_timezone_set('Asia/Novosibirsk');
    $nowDate = new DateTime();
    setlocale(LC_TIME, 'ru_RU.utf8');
    // Форматируем дату и время в нужный формат
    $documentCreateDate = $nowDate->format('d.m.Y H:i:s');
    $documentCreateDate =  $documentCreateDate . 'г.';
    // $locale = 'ru_RU';
    // $pattern = 'd MMMM yyyy';

    // // Создаем форматтер
    // $formatter = new IntlDateFormatter(
    //     $locale,
    //     IntlDateFormatter::NONE,
    //     IntlDateFormatter::NONE,
    //     date_default_timezone_get(),
    //     IntlDateFormatter::GREGORIAN,
    //     $pattern
    // );

    // // Форматируем дату
    // $documentCreateDate = $formatter->format($nowDate);
    $rand = rand(10, 790);
    $documentNumber = $rand;

    $companyName = 'ТЕСТ НАЗВАНИЕ КОМПАНИИ';
    $position = '';
    $director = 'ТЕСТ ИМЯ РУКОВОДИТЕЛЯ';
    $clientType = 'fiz';
    $documentData = [
        'documentNumber' => $documentNumber,
        'documentCreateDate' => $documentCreateDate,
        'persons' => $persons,
        'companyName' => $companyName,
        'position' => $position,
        'director' => $director,

    ];
    Log::channel('telegram')->info('TST HOOK ALFA', [
        'listItems' => $listItems
    ]);

    Log::info('TST HOOK ALFA', [
        'listItems' => $listItems
    ]);
    $documentLinkData = APIOnlineController::online('post', 'alfa/specification', $documentData, 'data');
    $documentLink =  $documentLinkData;
    if (!empty($documentLinkData['data'])) {
        $documentLink = $documentLinkData['data'];
    }
    if (!empty($documentLink['link'])) {
        $documentLink = $documentLink['link'];
    }
    $resultText = 'Приложение к договору ППК';

    $message = "\n" . 'Приложение: <a href="' . $documentLink . '" target="_blank">' . $resultText . '</a>';

    $timeLine = new BitrixTimeLineService($hook);
    $timeLine->setTimeLine($message, 'DYNAMIC_159', $smartId);
    Log::channel('telegram')->info('TST HOOK ALFA', [
        'documentLink' => $documentLink
    ]);

    Log::info('TST HOOK ALFA', [
        'documentLink' => $documentLink
    ]);

    APIOnlineController::getSuccess([
        'link' => $documentLink
    ]);
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
