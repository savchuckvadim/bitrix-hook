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





    public  function getRecords($companyId, $contactIds)
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
            $activities = [];

            $dealsIds = $this->getCurrentDealIds($companyId);
            // $contacts = $this->getContacts($companyId);
            $activities =  $this->getActivities($companyId, $dealsIds, $contactIds);
            $records = $this->getFilesFromActivities($activities);
            return APIOnlineController::getSuccess([
                'deals' => $dealsIds,
                'contactIds' => $contactIds,
                'activities' => $activities,
                'records' => $records
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
            'select' => ['ID', 'NAME', 'SECOND_NAME', 'LAST_NAME', 'POST', 'COMMENTS', 'PHONE', 'HAS_PHONE']
        ];
        $contacts = BitrixGeneralService::getEntityListWithFullData(
            $this->hook,
            'contact',
            $data,

        );
        return $contacts;
    }
    protected function getCurrentDealIds($companyId)
    {
        $resultIds = [];
        $categoryId = $this->getSaleDealCategoryId();
        $filter = [
            'COMPANY_ID' => $companyId,
            // 'CATEGORY_ID' => $categoryId,

        ];
        $order = ['ID' => 'DESC'];
        $data = [
            'filter' => $filter,
            'order' => $order,
            'select' => ['ID']
        ];
        $deals = BitrixGeneralService::getEntityListWithFullData(
            $this->hook,
            'deal',
            $data,

        );
        foreach ($deals as $deal) {
            $resultIds[] = $deal['ID'];
        }
        return $resultIds;
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


    protected function getActivities($companyId, $dealsIds, $contactIds)
    {
        $activities = [];

        if (!empty($companyId)) {
            $filter =
                [
                    'OWNER_TYPE_ID' => 3, // 2- deal 3 - contact 4 - company
                    'OWNER_ID' => $companyId, // 2976,
                    "TYPE_ID" => 2 // Тип активности - Звонок
                ];
            $data = [
                'filter' => $filter,
            ];
            $companyActivities = BitrixGeneralService::getEntityListWithFullData(
                $this->hook,
                'activity',
                $data,

            );
            if (!empty($companyActivities)) {
                foreach ($companyActivities as $cmpnActivity) {
                    array_push($activities, $cmpnActivity);
                }
            }
        }
        if (!empty($dealsIds)) {
            // foreach ($dealsIds as $dealId) {
            $filter =
                [
                    'OWNER_TYPE_ID' => 2, // 2- deal 3 - contact 4 - company
                    'OWNER_ID' => $dealsIds, // 2976,
                    "TYPE_ID" => 2 // Тип активности - Звонок
                ];

            $order = ['ID' => 'DESC'];
            $data = [
                'filter' => $filter,
                'order' => $order,
            ];
            $dealActivities = BitrixGeneralService::getEntityListWithFullData(
                $this->hook,
                'activity',
                $data,
            );
            if (!empty($dealActivities)) {
                foreach ($dealActivities as $dealActivitiy) {
                    array_push($activities, $dealActivitiy);
                }
            }

            // }
        }
        if (!empty($contactIds)) {
            $filter =
                [
                    'OWNER_TYPE_ID' => 3, // 2- deal 3 - contact 4 - company
                    'OWNER_ID' => $contactIds, // 2976,
                    "TYPE_ID" => 2 // Тип активности - Звонок
                ];
            $order = ['ID' => 'DESC'];

            $data = [
                'filter' => $filter,
                'order' => $order,
            ];
            $contactActivities = BitrixGeneralService::getEntityListWithFullData(
                $this->hook,
                'activity',
                $data,
            );
            if (!empty($contactActivities)) {

                foreach ($contactActivities as $contactActivity) {
                    array_push($activities, $contactActivity);
                }
            }
        }

        if (!empty($activities)) {



            usort($activities, function ($a, $b) {
                return $b['ID'] <=> $a['ID']; // Сортировка по возрастанию
            });
            $activities = array_filter($activities, function ($activity) {
                return !empty($activity['FILES']); // Убираем пустые значения
            });
        }
        // $key = 'entity' . '_' . 'company';
        // $resultBatchCommands[$key] = $companyCommand; // в результате будет id

        // $batchService =  new BitrixBatchService($this->hook);
        // $batchService->sendGeneralBatchRequest($resultBatchCommands);
        return $activities;
    }

    public function getFilesFromActivities(array $activities): array
    {
        $files = [];

        // 🔹 Собираем файлы из всех активностей
        foreach ($activities as $activity) {
            if (!empty($activity['FILES'])) {
                foreach ($activity['FILES'] as $file) {
                    $date = date("d.m.Y H:i:s", strtotime($activity['LAST_UPDATED']));
                    $name = "{$activity['SUBJECT']} {$date}";

                    $files[$file['id']] = [
                        'activityId' => $activity['ID'],
                        'id' => $file['id'],
                        'name' => $name,
                        'url' => $file['url'],
                        'duration' => null,
                        'isPlaying' => false,
                    ];
                }
            }
        }

        // Если нет файлов, возвращаем пустой массив
        if (empty($files)) {
            return [];
        }

        // 🔹 Формируем batch-запрос
        $batchCommands = [];
        foreach ($files as $fileId => $file) {
            $batchCommands["get_{$fileId}"] = [
                'method' => 'disk.file.get',
                'params' => ['id' => $fileId]
            ];
        }

        // 🔹 Отправляем batch-запрос
        // $response = Http::post("{$this->hook}/batch", [
        //     'cmd' => $batchCommands,
        // ]);

        // if ($response->failed()) {
        //     throw new \Exception("Ошибка batch-запроса: " . $response->body());
        // }
        $batchService = new BitrixBatchService($this->hook);
        $result = $batchService->sendGeneralBatchRequest([$batchCommands]);
        // 🔹 Обновляем файлы с правильными URL
        // $batchResults = $response->json()['result'] ?? [];
        // foreach ($batchResults as $key => $fileData) {
        //     $fileId = str_replace("get_", "", $key); // Извлекаем ID файла
        //     if (isset($files[$fileId]) && isset($fileData['DOWNLOAD_URL'])) {
        //         $files[$fileId]['url'] = $fileData['DOWNLOAD_URL'];
        //     }
        // }

        // return array_values($files); // Возвращаем список файлов
        return $result;
    }
}
