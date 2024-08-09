<?php

namespace App\Http\Controllers\Front\ReportKPI;

use App\Http\Controllers\APIBitrixController;
use App\Http\Controllers\APIController;
use App\Http\Controllers\APIOnlineController;
use App\Http\Controllers\BitrixHookController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\PortalController;
use App\Services\General\BitrixBatchService;
use App\Services\General\BitrixListService;
use App\Services\HookFlow\BitrixEntityFlowService;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Session;

class ReportKPIController extends Controller


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
                    if ($bitrixList['type'] == 'kpi') {
                        $this->portalKPIList = $bitrixList;
                    }
                }
            }
        }
    }


    public function getInitReport()
    {
    }

    // public  function getReport(Request $request)
    // {
    //     $callingsTotalCount = [
    //         'all' => null,
    //         '30' => 30,
    //         '60' => 60,
    //         '180' => 180
    //     ];
    //     $errors = [];
    //     $responses = [];
    //     $result = [];

    //     try {
    //         $domain = $request['domain'];
    //         $userFieldId = $request['filters']['userFieldId'];
    //         $userIds = $request['filters']['userIds'];
    //         $departament = $request['filters']['departament'];


    //         $actionFieldId = $request['filters']['actionFieldId'];
    //         $currentActionsData = $request['filters']['currentActions'];
    //         $dateFieldId = $request['filters']['dateFieldId'];
    //         $dateFrom = $request['filters']['dateFrom'];
    //         $dateTo = $request['filters']['dateTo'];

    //         $dateFieldForHookFrom = ">DATE_CREATE";
    //         $dateFieldForHookTo = "<DATE_CREATE";
    //         // $currentActions = [];
    //         // $lists = [];

    //         // if ($currentActionsData) {
    //         //     foreach ($currentActionsData as $id => $title) {
    //         //         array_push($currentActions, $id);
    //         //     }
    //         // }



    //         $listId = $this->portalKPIList['bitrixId'];

    //         $listsResponses = [];

    //         // Подготовка команд для batch запроса

    //         $commands = [];
    //         foreach ($departament as $user) {
    //             $userId = $user['ID'];
    //             $userName = $user['LAST_NAME'] . ' ' . $user['NAME'];

    //             foreach ($currentActionsData as $actionId => $actionTitle) {
    //                 // Формируем ключ команды, используя ID пользователя и ID действия для уникальности
    //                 $cmdKey = "user_{$userId}_action_{$actionId}";

    //                 // Добавляем команду в массив команд
    //                 $commands[$cmdKey] =
    //                     "lists.element.get?IBLOCK_TYPE_ID=lists&IBLOCK_ID=" . $listId . "&filter[$userFieldId]=$userId&filter[$actionFieldId]=$actionId&filter[$dateFieldForHookFrom]=$dateFrom&filter[$dateFieldForHookTo]=$dateTo";
    //             }
    //         }
    //         $batchService = new BitrixBatchService($this->hook);
    //         //lists
    //         // Отправляем batch запрос
    //         $batchResults = $batchService->sendBatchRequest($commands);
    //         $report = $batchService->processBatchResults($departament, $currentActionsData, $batchResults);
    //         // $report = $this->addVoximplantInReport($dateFrom, $dateTo, $report);
    //         // $report = $this->cleanReport($report);
    //         $totalReport = $this->addTotalAndMediumKPI($report);

    //         //voximplant
    //         return APIOnlineController::getSuccess(
    //             [
    //                 'commands' => $commands,
    //                 'report' =>  $report,
    //                 'total' =>  $totalReport['total'],
    //                 // 'medium' =>  $totalReport['medium'],
    //                 // 'getPortalReportData' =>  $getPortalReportData,
    //                 'listId' =>  $listId,
    //                 // 'commands' =>  $commands

    //             ]
    //         );
    //     } catch (\Throwable $th) {
    //         return APIOnlineController::getError(
    //             $th->getMessage(),
    //             [
    //                 '$batchResults' => $batchResults
    //             ]
    //         );
    //     }
    // }
    public  function getReport(Request $request)
    {
        $callingsTotalCount = [
            'all' => null,
            '30' => 30,
            '60' => 60,
            '180' => 180
        ];
        $errors = [];
        $responses = [];
        $result = [];
        $batchResults = null;
        try {
            $domain = $request['domain'];
            $userFieldId = $request['filters']['userFieldId'];
            $userIds = $request['filters']['userIds'];
            $departament = $request['filters']['departament'];


            $actionFieldId = $request['filters']['actionFieldId'];
            $currentActionsData = $request['filters']['currentActions'];
            $dateFieldId = $request['filters']['dateFieldId'];
            $dateFrom = $request['filters']['dateFrom'];
            $dateTo = $request['filters']['dateTo'];

            $dateFieldForHookFrom = ">DATE_CREATE";
            $dateFieldForHookTo = "<=DATE_CREATE";
            // $currentActions = [];
            // $lists = [];

            // if ($currentActionsData) {
            //     foreach ($currentActionsData as $id => $title) {
            //         array_push($currentActions, $id);
            //     }
            // }



            $listId = $this->portalKPIList['bitrixId'];
            $listFields = $this->portalKPIList['bitrixfields'];
            $eventActionField = null;
            $eventActionTypeField = null;
            $eventResponsibleField = null;
            $eventDateField = null;




            $currentActionsData = [];
            if (!empty($listFields)) {

                foreach ($listFields as $plField) {
                    if ($plField['code'] === 'sales_kpi_event_action') {
                        $eventActionField = $plField;
                        $actionFieldId = $eventActionField['bitrixCamelId']; //like PROPERTY_2119 
                    }
                    if ($plField['code'] === 'sales_kpi_event_type') {
                        $eventActionTypeField = $plField;
                        $actionTypeFieldId = $eventActionTypeField['bitrixCamelId']; //like PROPERTY_2119 
                    }
                    if ($plField['code'] === 'sales_kpi_responsible') {
                        $eventResponsibleField = $plField;
                        $eventResponsibleFieldId = $eventResponsibleField['bitrixCamelId']; //like PROPERTY_2119 

                    }
                    if ($plField['code'] === 'sales_kpi_plan_date') {
                        $eventDateField = $plField;
                        $eventDateFieldId = $eventDateField['bitrixCamelId']; //like PROPERTY_2119 

                    }
                }
            }

            if (!empty($eventActionTypeField) && !empty($eventActionTypeField)) {
                if (!empty($eventActionTypeField['items']) && !empty($eventActionTypeField['items'])) {
                    foreach ($eventActionTypeField['items'] as $actionType) { //презентация звонок
                        foreach ($eventActionField['items'] as $action) { //plan, done
                            $actionData = $this->getActionWithTypeData($actionType, $action);
                            if (!empty($actionData)) {
                                if (!empty($actionData['actionTypeItem']) && !empty($actionData['actionItem'])) {
                                    array_push($currentActionsData, $actionData);
                                }
                            }
                        }
                    }
                }
            }
            $listsResponses = [];

            // Подготовка команд для batch запроса





            $commands = [];
            foreach ($departament as $user) {
                $userId = $user['ID'];
                $userName = $user['LAST_NAME'] . ' ' . $user['NAME'];

                if (!empty($currentActionsData)) {
                    foreach ($currentActionsData as $currentAction) {
                        $code = $currentAction['code'];
                        $actionValuebitrixId = $currentAction['actionItem']['bitrixId'];
                        $actionTypeValuebitrixId = $currentAction['actionTypeItem']['bitrixId'];

                        // Формируем ключ команды, используя ID пользователя и ID действия для уникальности
                        $cmdKey = "user_{$userId}_action_{$code}";

                        // Добавляем команду в массив команд
                        $commands[$cmdKey] =
                            // "lists.element.get?IBLOCK_TYPE_ID=lists&IBLOCK_ID=" . $listId . "&filter[$eventResponsibleFieldId]=$userId&filter[$actionFieldId]=$actionValuebitrixId&filter[$actionTypeFieldId]=$actionTypeValuebitrixId&filter[$dateFieldForHookFrom]=$dateFrom&filter[$dateFieldForHookTo]=$dateTo";

                            "lists.element.get?IBLOCK_TYPE_ID=lists&IBLOCK_ID=" . $listId . "";
                    }
                }
            }
            $batchService = new BitrixBatchService($this->hook);
            //lists
            // Отправляем batch запрос
            $batchResults = $batchService->sendBatchRequest($commands);
            $report = $batchService->processBatchResults($departament, $currentActionsData, $batchResults);
            // $report = $this->addVoximplantInReport($dateFrom, $dateTo, $report);
            // $report = $this->cleanReport($report);
            // $totalReport = $this->addTotalAndMediumKPI($report);

            //voximplant
            return APIOnlineController::getSuccess(
                [
                    'commands' => $commands,
                    'report' =>  $report,
                    // 'total' =>  $totalReport['total'],
                    // 'medium' =>  $totalReport['medium'],
                    // 'getPortalReportData' =>  $getPortalReportData,
                    'listId' =>  $listId,
                    'commands' =>  $commands,
                    'list' => $this->portalKPIList,

                ]
            );
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

    protected function getActionWithTypeData($actionType, $action)
    {
        $result = [
            'name' => '',
            'actionTypeItem' => null,
            'actionItem' => null,
            'innerCode' => '',
            'code' => ''
        ];
        switch ($action['code']) {
            case 'plan':
            case 'expired':
            case 'done':
            case 'pound':
            case 'act_noresult_fail':
                if (
                    $actionType['code'] == 'xo' ||
                    $actionType['code'] == 'call' ||
                    $actionType['code'] == 'call_in_progress' ||
                    $actionType['code'] == 'call_in_money'
                ) {
                    $innerCode = 'call_' . $action['code'];
                    $result['name'] = 'Звонок ' . $action['name'];
                    $result['actionTypeItem'] = $actionType;
                    $result['actionItem'] = $action;
                    $result['innerCode'] = $innerCode;

                    $code = $actionType['code'] . '_' . $action['code'];
                    $result['code'] = $code;
                } else       if (

                    $actionType['code'] == 'presentation' ||
                    $actionType['code'] == 'presentation_uniq'
                ) {
                    $innerCode = $actionType['code'] . '_' . $action['code'];
                    $result['name'] = $actionType['name'] . $action['name'];
                    $result['actionTypeItem'] = $actionType;
                    $result['actionItem'] = $action;
                    $result['innerCode'] = $innerCode;

                    $code = $actionType['code'] . '_' . $action['code'];
                    $result['code'] = $code;
                }

                break;
                // case 'act_init_send':
                //     # code...
                //     break;
                // case 'act_init_done':
                //     # code...
                //     break;
            case 'act_send':
                if (
                    $actionType['code'] == 'ev_offer' ||
                    $actionType['code'] == 'ev_offer_pres' ||
                    $actionType['code'] == 'ev_invoice' ||
                    $actionType['code'] == 'ev_invoice_pres' ||
                    $actionType['code'] == 'ev_contract'
                ) {
                    $code = $actionType['code'] . '_' . $action['code'];
                    $result['code'] = $code;
                    $innerCode = $actionType['code'] . '_' . $action['code'];
                    $result['name'] = $actionType['name'] . $action['name'];
                    $result['actionTypeItem'] = $actionType;
                    $result['actionItem'] = $action;
                    $result['innerCode'] = $innerCode;
                }
                break;
                // case 'act_sign':
                //     # code...
                //     break;
                // case 'act_pay':
                //     # code...
                //     break;

            default:
                # code...
                break;
        }

        return $result;
    }
    protected function getFeminineForm($actionName)
    {
        // Карта преобразования мужских форм в женские
        $conversionMap = [
            'Запланирован' => 'Запланирована',
            'Просрочен' => 'Просрочена',
            'Состоялся' => 'Состоялась',
            'Перенос' => 'Перенесена',
            'Не состоялся' => 'Не состоялась',

            // Добавьте другие преобразования по мере необходимости
        ];

        return $conversionMap[$actionName] ?? $actionName;
    }
    protected function addVoximplantInReport($dateFrom, $dateTo, $report)
    {
        $callingsTypes = [
            [
                'id' => 'all',
                'action' => 'Наборов номера',
                'count' => 0,
                'duration' => 0,
                'period' => []
            ],
            [
                'id' => 30,
                'action' => 'Звонки > 30 сек',
                'count' => 0,
                'duration' => 0,
                'period' => []
            ],
            [
                'id' => 60,
                'action' => 'Звонки > минуты',
                'count' => 0,
                'duration' => 0,
                'period' => []
            ],
            [
                'id' => 180,
                'action' => 'Звонки > 3 минут',
                'count' => 0,
                'duration' => 0,
                'period' => []
            ],
            [
                'id' => 300,
                'action' => 'Звонки > 5 минут',
                'count' => 0,
                'duration' => 0,
                'period' => []
            ],
            [
                'id' => 600,
                'action' => 'Звонки > 10 минут',
                'count' => 0,
                'duration' => 0,
                'period' => []
            ],

        ];
        $errors = [];
        $responses = [];

        $result = [];

        $actionUrl = '/voximplant.statistic.get.json';
        $url = $this->hook . $actionUrl;
        $next = 0; // Начальное значение параметра "next"


        foreach ($report as $k => $userReport) {

            $user = $userReport['user'];
            $userId = $user['ID'];
            $userIds = [$userId];
            $resultUserReport = $userReport;
            $resultUserReport['callings'] = $callingsTypes;



            foreach ($resultUserReport['callings'] as $key => $type) {

                if ($type['id'] === 'all') {
                    $data =   [
                        "FILTER" => [
                            "PORTAL_USER_ID" => $userIds,
                            // ">CALL_DURATION" => $type->duration,
                            ">CALL_START_DATE" => $dateFrom,
                            "<CALL_START_DATE" =>  $dateTo
                        ]
                    ];
                } else {
                    $data =   [
                        "FILTER" => [
                            "PORTAL_USER_ID" => $userIds,
                            ">CALL_DURATION" => $type['id'],
                            ">CALL_START_DATE" => $dateFrom,
                            "<CALL_START_DATE" =>  $dateTo
                        ]
                    ];
                }

                $response = Http::get($url, $data);

                array_push($responses, $response);

                // if (isset($response['total'])) {
                // Добавляем полученные звонки к общему списку
                // $resultCallings = array_merge($resultCallings, $response['result']);
                // if (isset($response['next'])) {
                //     // Получаем значение "next" из ответа
                //     $next = $response['next'];
                // }

                // $type['count'] = $response['total'];
                if (isset($response['total'])) {
                    $resultUserReport['callings'][$key]['count'] = $response['total'];
                }

                // } else { 
                //     return APIController::getError(
                //         'response total not found',
                //         [
                //             'response' => $response
                //         ]
                //     );
                //     array_push($errors, $response);
                //     $type['count'] = 0;
                // }
                // Ждем некоторое время перед следующим запросом
                // sleep(1); // Например, ждем 5 секунд
            }
            array_push($result, $resultUserReport);
            // } while ($next > 0); // Продолжаем цикл, пока значение "next" больше нуля
        }
        return  $result;
    }
    protected function cleanReport($report)
    {
        $kpiToRemove = []; // KPI для удаления

        // Собираем информацию о KPI для удаления
        foreach ($report as $user) {
            foreach ($user['kpi'] as $kpi) {
                $action = $kpi['action'];
                if (!isset($kpiToRemove[$action])) {
                    $kpiToRemove[$action] = ['zeroCount' => 0, 'totalCount' => 0];
                }
                $kpiToRemove[$action]['totalCount']++;
                if ($kpi['count'] == 0) {
                    $kpiToRemove[$action]['zeroCount']++;
                }
            }
        }

        // Определение KPI для удаления
        foreach ($kpiToRemove as $action => $data) {
            if ($data['zeroCount'] != $data['totalCount']) {
                unset($kpiToRemove[$action]);
            }
        }

        // Удаление ненужных KPI
        foreach ($report as &$user) {
            $user['kpi'] = array_values(array_filter($user['kpi'], function ($kpi) use ($kpiToRemove) {
                return !isset($kpiToRemove[$kpi['action']]);
            }));
        }
        unset($user); // Разорвать ссылку

        return $report;
    }
    protected function addTotalAndMediumKPI($report)
    {
        $totalKPI = []; // Суммарная информация по KPI
        $mediumKPI = []; // Средняя информация по KPI

        // Собираем суммарную информацию
        foreach ($report as $user) {
            foreach ($user['kpi'] as $kpi) {
                $action = $kpi['action'];
                if (!isset($totalKPI[$action])) {
                    $totalKPI[$action] = ['count' => 0, 'users' => 0];
                }
                $totalKPI[$action]['count'] += $kpi['count'];
                $totalKPI[$action]['users']++;
            }
        }

        // Вычисляем средние значения
        foreach ($totalKPI as $action => $data) {
            $mediumKPI[$action] = ['count' => 0];
            if ($data['users'] > 0) {
                $mediumKPI[$action]['count'] = $data['count'] / $data['users'];
            }
        }

        // Возвращаем дополненный отчет
        return [
            'total' => $totalKPI,
            'medium' => $mediumKPI,
        ];
    }
    //protected report inner methods
    protected function getReportCallings($userId)
    {
    }
    // protected function getReportLists(
    //     $domain,
    //     $userFieldId,
    //     $userIds,
    //     $actionFieldId,
    //     $currentActions,
    //     $dateFieldId,
    //     $dateFrom,
    //     $dateTo
    // ) {
    //     // $domain
    //     // $action  - id поля в котором содержатся items действий
    //     // currentActions = массив айдишников действий которые нужно получить
    //     // date from
    //     // date to

    //     $method = '/lists.element.get.json';

    //     $listId = 86;



    //     $url = $this->hook . $method;

    //     $result = [];

    //     // $fromProp = '>' . $dateFieldId;
    //     // $torop = '>' . $dateFieldId;

    //     foreach ($currentActions as $actionId => $actionTitle) {
    //         $data =   [
    //             'IBLOCK_TYPE_ID' => 'lists',
    //             // IBLOCK_CODE/IBLOCK_ID
    //             'IBLOCK_ID' => $listId,
    //             'FILTER' => [
    //                 $userFieldId => $userIds,
    //                 $actionFieldId => $actionId,
    //                 '>DATE_CREATE' => $dateFrom,
    //                 '<DATE_CREATE' => $dateTo,

    //             ]
    //         ];

    //         $response = Http::get($url, $data);
    //         if ($response) {
    //             if (isset($response['result'])) {

    //                 $otherData = [];
    //                 if (isset($response['next'])) {
    //                     $otherData['next'] = $response['next'];
    //                 }


    //                 $res = [
    //                     'action' => $actionTitle,
    //                     'count' =>  0
    //                 ];
    //                 if (isset($response['total'])) {
    //                     $res['count'] = $response['total'];
    //                 }
    //                 array_push($result, $res);
    //             }
    //         }
    //     }


    //     return $result;



    //     // $next = 0;
    //     // $allResults = [];
    //     // do {
    //     //     $response = Http::get($url, array_merge($data, ['next' => $next])); // Добавляем параметр start к запросу
    //     //     $responseBody = $response->json();

    //     //     if (isset($responseBody['result'])) {
    //     //         $allResults = array_merge($allResults, $responseBody['result']); // Собираем результаты
    //     //     }

    //     //     $next = $responseBody['next'] ?? null; // Обновляем start для следующего запроса

    //     // } while (!is_null($next));

    //     // return ['data' => $allResults];

    // }


    // public function getList(Request $request)
    // {


    //     $method = '/lists.element.get.json';
    //     $fieldsMethod = 'lists.field.type.get';
    //     $listId = 86;

    //     try {
    //         $domain = $request['domain'];

    //         $portalResponse = PortalController::innerGetPortal($domain);
    //         if ($portalResponse) {
    //             if (isset($portalResponse['resultCode'])) {
    //                 if ($portalResponse['resultCode'] == 0) {
    //                     if (isset($portalResponse['portal'])) {
    //                         if ($portalResponse['portal']) {

    //                             $portal = $portalResponse['portal'];

    //                             $webhookRestKey = $portal['C_REST_WEB_HOOK_URL'];
    //                             $hook = 'https://' . $domain  . '/' . $webhookRestKey;
    //                             $actionUrl =  $method;
    //                             $url = $hook . $actionUrl;



    //                             $data =   [
    //                                 'IBLOCK_TYPE_ID' => 'lists',
    //                                 // IBLOCK_CODE/IBLOCK_ID
    //                                 'IBLOCK_ID' => $listId
    //                             ];


    //                             $response = Http::get($url, $data);
    //                             $fieldsResponse = Http::get($hook . $fieldsMethod, $data);
    //                             if (isset($response['result'])) {
    //                                 return APIController::getSuccess(

    //                                     [

    //                                         'response' => $response,
    //                                         'list' => $response['result'],
    //                                         'fieldsMethod' => $fieldsResponse['result']
    //                                     ]
    //                                 );
    //                             } else {
    //                                 Log::info('Response error ', [

    //                                     'response' => $response,

    //                                 ]);
    //                                 if (isset($response['error'])) {
    //                                     return APIController::getError(
    //                                         'request error',
    //                                         [

    //                                             'response' => $response,
    //                                             'error' => $response['error'],
    //                                             'description' => $response['error_description']
    //                                         ]
    //                                     );
    //                                 }
    //                                 return APIController::getError(
    //                                     'request error',

    //                                     [

    //                                         'response' => $response,
    //                                         // 'request' => $response
    //                                     ]
    //                                 );
    //                             }
    //                         }


    //                         return APIController::getError(
    //                             'portal not found',
    //                             null
    //                         );
    //                     }
    //                 }
    //             }
    //         }
    //     } catch (\Throwable $th) {
    //         return APIController::getError(
    //             $th->getMessage(),
    //             [
    //                 'request' => $request
    //             ]
    //         );
    //     }
    // }
    public function getListFilter(Request $request)
    {

        $listId = $this->portalKPIList['bitrixId'];

        try {

            $response = BitrixListService::getListFieldsGet(
                $this->hook,
                $listId
            );

            return APIOnlineController::getSuccess(
                [
                    'data' =>                [
                        'filter' => $response,
                        'list' => $this->portalKPIList,
                        // 'portal' => $this->portal


                    ]
                ]
            );
        } catch (\Throwable $th) {
            return APIOnlineController::getError(
                $th->getMessage(),
                [
                    'request' => $request
                ]
            );
        }
    }


    // protected function getPortalReportData($domain)
    // {
    //     $portal = Portal::where('domain', $domain)->first();
    //     return [
    //         'bitrixlistId' =>  $portal->getSalesBitrixListId()->bitrixId,
    //         'callingGroupId' =>  $portal->getSalesCallingGroupId()->bitrixId,
    //         'departamentId' =>  $portal->getSalesDepartamentId()->bitrixId,
    //     ];
    // }
}
