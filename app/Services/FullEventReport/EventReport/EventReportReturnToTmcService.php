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
        $returnToTmc,
        $isNeedReturnToTmc,


    ) {
        $this->domain = $domain;
        $this->hook = $hook;
        $this->returnToTmc = $returnToTmc;
        $this->isNeedReturnToTmc = $isNeedReturnToTmc;
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
                                $methodGet = '/tasks.task.list';
                                $url = $this->hook . $methodGet;
                                $crmForCurrent = ['CO_' . $companyId, 'D_' . $tmcDealId];
                                // for get
                                $filter = [
                                    'TITLE' => '%Презентация%',
                                    // 'GROUP_ID' => $callingTaskGroupId,
                                    // 'UF_CRM_TASK' => $crmForCurrent,
                                    'RESPONSIBLE_ID' => $assignedId,
                                    '!=STATUS' => 5, // Исключаем задачи со статусом "завершена"

                                ];
                                $select = ['ID'];
                                $getTaskData = [
                                    'filter' => $filter,
                                    'select' => $select,

                                ];
                                $response = Http::get($url, $getTaskData);

                                $responseData = APIBitrixController::getBitrixRespone($response, 'cold: getCurrentTasksIds');
                                APIOnlineController::sendLog('return to tmc get task list', [

                                    'responseData' => $responseData,


                                ]);
                                if (!empty($responseData)) {
                                    if (is_array($responseData)) {
                                        if (!empty($responseData[0])) {
                                            if (!empty($responseData[0]['ID'])) {
                                               
                                                $newDeadline = Carbon::now()->addHours(24)->toDateTimeString();
                                                $taskData =  [
                                                    'taskId' => $responseData[0]['ID'],
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

                                $entityData = [
                                    'ASSIGNED_BY_ID' => $assignedId
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
