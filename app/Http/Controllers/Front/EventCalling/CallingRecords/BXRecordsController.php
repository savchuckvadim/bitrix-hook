<?php

namespace App\Http\Controllers\Front\EventCalling\CallingRecords;


use App\Http\Controllers\APIOnlineController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\PortalController;
use App\Services\BitrixGeneralService;
use App\Services\General\BitrixBatchService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BXRecordsController extends Controller


{
    protected $domain;
    protected $portal;
    protected $portalKPIList;
    protected $hook;

    protected $filters;


    public function __construct(
        // $type,
        $domain,

    ) {
        $this->domain = $domain;

        $portal = PortalController::getPortal($domain);

        if (!empty($portal['data'])) {
            $portal = $portal['data'];
        }

        $this->portal = $portal;
        $this->hook = PortalController::getHook($domain);
        if (!empty($portal['bitrixLists'])) {

            $bitrixLists = $portal['bitrixLists'];

            if (!empty($bitrixLists)) {

                foreach ($bitrixLists as $bitrixList) {
                    if ($bitrixList['type'] == 'history') {
                        $this->portalKPIList = $bitrixList;
                    }
                }
            }
        }
    }





    public  function getRecords($companyId)
    {

        $batchResults = null;
        $currentActionsData = [];
        $actionFieldId = null;
        try {
            // $domain = $request['domain'];




            $url = $this->hook . '/batch';
            $method = 'lists.element.get';
            $key = 'history_list';
            $allResults = [];
            $lastId = null;

            // do {
            //     // 🟢 Формируем параметры запроса с фильтрацией по `ID > lastId`
            //     $data = [
            //         'IBLOCK_TYPE_ID' => 'lists',
            //         'IBLOCK_ID' => $listId,
            //         'filter' => [
            //             $companyIdFieldId => '%' . $companyId . '%',
            //         ],
            //         'select' => [
            //             $commentFieldId,
            //             $actionFieldId,
            //             $actionTypeFieldId,
            //             $noresultReasonFieldId,
            //             $resultStatusFieldId
            //         ],
            //         'order' => ['ID' => 'ASC'], // 🟢 Сортировка по ID
            //     ];

            //     if ($lastId) {
            //         $data['filter']['>ID'] = $lastId;
            //     }

            //     // 🟢 Генерируем команду
            //     $command = $method . '?' . http_build_query($data);

            //     // 🟢 Делаем запрос
            //     $response = Http::post($url, [
            //         'halt' => 0,
            //         'cmd' => [$key => $command] // 🟢 Оборачиваем в массив, чтобы ключи совпадали
            //     ]);

            //     $responseData = $response->json();

            //     // 🟢 Проверяем, есть ли данные
            //     if (isset($responseData['result']['result'][$key]) && !empty($responseData['result']['result'][$key])) {
            //         $batchResults = $responseData['result']['result'][$key];
            //         $allResults = array_merge($allResults, $batchResults);
            //         $lastId = end($batchResults)['ID'] ?? $lastId; // 🟢 Запоминаем последний ID
            //     }

            //     // 🟢 Проверяем наличие `result_next` для следующего запроса
            //     $next = $responseData['result']['result_next'][$key] ?? null;
            // } while ($next !== null); // 🔄 Пока есть `result_next`, продолжаем

            // 🟢 Логируем ошибки, если есть
            // if (!empty($responseData['result_error'])) {
            //     Log::channel('telegram')->error('❌ Ошибка Bitrix BATCH', [
            //         'errors' => $responseData['result_error']
            //     ]);
            // }

            $result = [];
            $commands = [];

            $deals = $this->getCurrentDeal($companyId, $commands);
            $contacts = $this->getContacts($companyId);
            //  $currentDealId = '$result[' . $key . ']';
            return APIOnlineController::getSuccess([
                'deals' => $deals,
                'contacts' => $contacts
            ]);
        } catch (\Throwable $th) {
            $errorMessages =  [
                'message'   => $th->getMessage(),
                'file'      => $th->getFile(),
                'line'      => $th->getLine(),
                'trace'     => $th->getTraceAsString(),
            ];
            return APIOnlineController::getError(
                $th->getMessage(),
                [
                    'list' => $this->portalKPIList,
                    '$batchResults' => $batchResults,
                    'error' => $errorMessages,
                    'currentActionsData' => $currentActionsData
                ]
            );
        }
    }

    protected function getContacts($companyId)
    {
        $filter = [
            'COMPANY_ID' => $companyId,

        ];
        $sort = ['ID' => 'DESC'];
        $data = [
            'filter' => $filter,
            'order' => $sort,
            'select' => ['ID', 'NAME', 'SECOND_NAME', 'POST', 'COMMENTS', 'PHONE', 'HAS_PHONE']
        ];
        $contacts = BitrixGeneralService::getEntityListWithFullData(
            $this->hook,
            'contact',
            $data,

        );
        return $contacts;
    }
    protected function getCurrentDeal($companyId, $commands)
    {
        $categoryId = $this->getSaleDealCategoryId();
        $filter = [
            'COMPANY_ID' => $companyId,
            'CATEGORY_ID' => $categoryId,

        ];
        $sort = ['ID' => 'DESC'];
        $data = [
            'filter' => $filter,
            'order' => $sort,
            'select' => ['ID']
        ];
        $deals = BitrixGeneralService::getEntityListWithFullData(
            $this->hook,
            'deal',
            $data,
    
        );
        $deals;
        return $commands;
    }

    protected function getSaleDealCategoryId()
    {
        $portal = $this->portal;
        $categoryBitrixId = null;
        if (!empty($portal['bitrixDeal'])) {
            if (!empty($portal['bitrixDeal']['categories'])) {
                $btxDealPortalCategories = $portal['bitrixDeal']['categories'];
            }
        }

        if (!empty($btxDealPortalCategories)) {
            foreach ($btxDealPortalCategories as $btxDealPortalCategory) {
                if (!empty($btxDealPortalCategory['code'])) {
                    if ($btxDealPortalCategory['code'] == "sales_base") {
                        $categoryBitrixId = $btxDealPortalCategory['bitrixId'];
                    }
                }
            }
        }

        return  $categoryBitrixId;
    }
}
