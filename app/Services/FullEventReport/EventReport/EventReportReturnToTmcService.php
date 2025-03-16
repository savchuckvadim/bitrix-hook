<?php

namespace App\Services\FullEventReport\EventReport;


use App\Http\Controllers\APIBitrixController;
use App\Http\Controllers\APIOnlineController;
use App\Services\BitrixGeneralService;
use App\Services\General\BitrixBatchService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EventReportReturnToTmcService

{
    protected $domain;

    protected $callingTaskGroupId;
    protected $hook;

    protected $returnToTmc = false;
    protected $isNeedReturnToTmc = false;
    // returnToTmc: {
    //     data: searchedTmcItem as TmcDealsForReturn | undefined,
    //     isActive: returnToTmc.menu.isActive
    // }

    public function __construct(

        $domain,
        $hook,
        $portal,
        $returnToTmc,
        $isNeedReturnToTmc,


    ) {
        $this->domain = $domain;
        $this->hook = $hook;
        $this->returnToTmc = $returnToTmc;
        $this->isNeedReturnToTmc = $isNeedReturnToTmc;

        if (isset($portal['bitrixCallingTasksGroup']) && isset($portal['bitrixCallingTasksGroup']['bitrixId'])) {
            $this->callingTaskGroupId =  $portal['bitrixCallingTasksGroup']['bitrixId'];
        }
    }

    public function process()
    {
        try {
            $batchcommands = [];
            $batchService = new BitrixBatchService($this->hook);
            if (!empty($this->isNeedReturnToTmc)) {
                if (!empty($this->returnToTmc)) {

                    if (isset($this->returnToTmc['isActive']) && isset($this->returnToTmc['data'])) {
                        if (!empty($this->returnToTmc['isActive']) && !empty($this->returnToTmc['data']) && !empty($this->returnToTmc['data']['tmcDeal'])) {
                            $tmcDeal = $this->returnToTmc['data']['tmcDeal'];
                            if (!empty($tmcDeal['ID']) && !empty($tmcDeal['ASSIGNED_BY_ID'])) {
                                $assignedId = $tmcDeal['ASSIGNED_BY_ID'];
                                $companyId = $tmcDeal['COMPANY_ID'];
                                $tmcDealId = $tmcDeal['ID'];
                                $methodGet = '/tasks.task.list.json';
                                $url = $this->hook . $methodGet;
                                $crmForCurrent = ['CO_' . $companyId, 'D_' . $tmcDealId];
                                // for get
                                $filter = [
                                    '%TITLE' => 'Презентация',
                                    'GROUP_ID' => $this->callingTaskGroupId,
                                    'UF_CRM_TASK' => $crmForCurrent,
                                    'RESPONSIBLE_ID' => $assignedId,
                                    '!=STATUS' => 5, // Исключаем задачи со статусом "завершена"

                                ];
                                // $select = ['ID'];
                                $getTaskData = [
                                    'filter' => $filter,
                                    // 'select' => $select,

                                ];
                                $response = Http::get($url, $getTaskData);
                   
                                $responseData = APIBitrixController::getBitrixRespone($response, 'cold: getCurrentTasksIds');
                             
                               
                                if (!empty($responseData)) {
                                    if (!empty($responseData['tasks'])) {
                                        if (is_array($responseData['tasks'])) {
                                            if (!empty($responseData['tasks'][0])) {
                                                if (!empty($responseData['tasks'][0]['id'])) {
                                                    $searchedTaskId = $responseData['tasks'][0]['id'];
                                                    $newDeadline = Carbon::now()->addHours(24)->toDateTimeString();
                                                    $taskData =  [
                                                        'taskId' => $searchedTaskId,
                                                        'fields' => [
                                                            'DEADLINE' => $newDeadline, //- крайний срок;
                                                            'ALLOW_CHANGE_DEADLINE' => 'Y',
                                                            'TITLE' => 'Звонок: вернули из ОП'

                                                        ]
                                                    ];
                                                    $batchKey = 'task_update';
                                                    $batchcommand =   $batchService->getGeneralBatchCommand(
                                                        $taskData,
                                                        'tasks.task.update'
                                                    );
                                                    $batchCommands[$batchKey] = $batchcommand;
                                                }
                                            }
                                        }
                                    }
                                }

                                $entityData = [
                                    'ID' => $companyId,
                                    'fields' => [
                                        'ASSIGNED_BY_ID' => $assignedId
                                    ]

                                ];
                                $entitybatchKey = 'company_update';
                                $entitybatchcommand =   BitrixBatchService::getGeneralBatchCommand(
                                    $entityData,
                                    'crm.company.update'
                                );
                                $batchCommands[$entitybatchKey] = $entitybatchcommand;
                                $result = $batchService->sendGeneralBatchRequest($batchCommands);
                                APIOnlineController::sendLog('return to tmc result', [
                                    'batchCommands' => $batchCommands,
                                    'result' => $result,


                                ]);
                            }
                        }
                    }
                }
            }
        } catch (\Throwable $th) {
            $errorMessages =  [
                'message'   => $th->getMessage(),
                'file'      => $th->getFile(),
                'line'      => $th->getLine(),
                'trace'     => $th->getTraceAsString(),
            ];
            APIOnlineController::sendLog('return to tmc service', [

                'domain' => $this->domain,
                // 'companyId' => $companyId,
                'error' =>   $errorMessages

            ]);
        }
    }
}
