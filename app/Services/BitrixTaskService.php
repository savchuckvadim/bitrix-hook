<?php

namespace App\Services;

use App\Http\Controllers\APIBitrixController;
use App\Http\Controllers\APIOnlineController;
use App\Http\Controllers\PortalController;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

use function Laravel\Prompts\error;

class BitrixTaskService



{

    public function __construct()
    {
    }

    public function createTask(

        //from bitrix hook
        $type,   //cold warm presentation hot  $stringType = 'Холодный обзвон ';
        $stringType,
        $portal,
        $domain,
        $hook,
        $companyId,  //may be null
        $leadId,     //may be null
        $createdId,
        $responsibleId,
        $deadline,
        $name,
        $currentSmartItemId,
        $isNeedCompleteOtherTasks,
        $currentTaskId = null,
        $currentDealsItemIds = null


    ) {
        Log::info(
            'APRIL_HOOK completeTask data',
            [

                'type' => $type,
                'stringType' => $stringType,
                'companyId' => $companyId,
                'createdId' => $createdId,
                'responsibleId' => $responsibleId,
                'deadline' => $deadline,
                'currentSmartItemId' => $currentSmartItemId,
                'isNeedCompleteOtherTasks' => $isNeedCompleteOtherTasks,
                'currentTaskId' => $currentTaskId,
                'currentDealsItemIds' => $currentDealsItemIds,
            ]
        );

        //TODO
        //type - cold warm presentation hot
        if ($type == 'cold' || $type == 'xo') {
            $stringType = 'Холодный обзвон ';
        }


        $gettedSmart = null;


        try {

            $smart = $portal['bitrixSmart'];

            $currentSmartItem = null;
            $description = '';
            $tasksCrmRelations = [];
            if ($companyId) {
                array_push($tasksCrmRelations, 'CO_' . $companyId);
            }
            if ($leadId) {
                array_push($tasksCrmRelations, 'L_' . $leadId);
            }


            //TODO get smart data for tasks

            $callingTaskGroupId = null;
            if (isset($portal['bitrixCallingTasksGroup']) && isset($portal['bitrixCallingTasksGroup']['bitrixId'])) {
                $callingTaskGroupId =  $portal['bitrixCallingTasksGroup']['bitrixId'];
            }

            $smartId = ''; //'T9c_'
            if (isset($portal['bitrixSmart']) && isset($portal['bitrixSmart']['crm'])) {
                $smartId =  $portal['bitrixSmart']['crm'] . '_';
            }

            // if (!$currentSmartItemId) { //если
            //     $getSmartItem = BitrixGeneralService::getSmartItem($hook, $leadId, $companyId, $responsibleId, $smart);
            //     $gettedSmart =  $getSmartItem;
            //     if ($getSmartItem && !empty($getSmartItem['id'])) {
            //         $currentSmartItemId = $getSmartItem['id'];
            //         $currentSmartItem =  $getSmartItem;
            //     }

            //     // return APIOnlineController::getResponse(0, 'success', ['crm' => $crm]);
            // }

            $crmItems = [...$tasksCrmRelations];

            if ($currentSmartItemId) {
                $crmItems = [$smartId  . $currentSmartItemId, ...$tasksCrmRelations];
            }
            $moscowTime = $deadline;
            $nowDate = now();
            if ($domain === 'alfacentr.bitrix24.ru') {
                $crmItems = [$smartId . ''  . '' . $currentSmartItemId];

                $novosibirskTime = Carbon::createFromFormat('d.m.Y H:i:s', $deadline, 'Asia/Novosibirsk');
                $moscowTime = $novosibirskTime->setTimezone('Europe/Moscow');
                $moscowTime = $moscowTime->format('Y-m-d H:i:s');
            }


            $taskTitle = $stringType . $name . '  ' . $deadline;


            if (!empty($currentDealsItemIds)) {
                foreach ($currentDealsItemIds as $dealId) {

                    array_push($crmItems, 'D_' . $dealId);
                }
            }

            $taskData =  [
                'fields' => [
                    'TITLE' => $taskTitle,
                    'RESPONSIBLE_ID' => $responsibleId,
                    'GROUP_ID' => $callingTaskGroupId,
                    'CHANGED_BY' => $createdId, //- постановщик;
                    'CREATED_BY' => $createdId, //- постановщик;
                    'CREATED_DATE' => $nowDate, // - дата создания;
                    'DEADLINE' => $moscowTime, //- крайний срок;
                    'UF_CRM_TASK' => $crmItems,
                    'ALLOW_CHANGE_DEADLINE' => 'N',
                    // 'DESCRIPTION' => $description
                ]
            ];



            if ($isNeedCompleteOtherTasks) {
                if (empty($currentTaskId)) {
                    $idsForComplete = $this->getCurrentTasksIds(
                        $hook,
                        $callingTaskGroupId,
                        $crmItems,
                        $responsibleId,
                        true, //$isNeedCompleteOnlyTypeTasks
                        $stringType
                    );
                } else {
                    $idsForComplete = [
                        $currentTaskId
                    ];
                }

                $this->completeTask($hook, $idsForComplete);
            }


            $createdTask = BitrixGeneralService::createTask(
                'Bitrix Task Service create task',
                $hook,
                $companyId,
                $leadId,
                // $crmItems,
                $taskData
            );

            return APIOnlineController::getResponse(
                0,
                'success',
                [
                    'createdTask' => $createdTask,

                    'currentSmartItem' => $currentSmartItem,
                    '$gettedSmart' => $gettedSmart,

                ]
            );
        } catch (\Throwable $th) {
            $errorMessages =  [
                'message'   => $th->getMessage(),
                'file'      => $th->getFile(),
                'line'      => $th->getLine(),
                'trace'     => $th->getTraceAsString(),
            ];
            Log::error('ERROR COLD: Exception caught',  $errorMessages);
            Log::info('error COLD', ['error' => $th->getMessage()]);
            return APIOnlineController::getResponse(1, $th->getMessage(),  $errorMessages);
        }
    }


    //tasks for complete
    protected function getCurrentTasksIds(
        $hook,
        $callingTaskGroupId,
        $crmForCurrent,
        $responsibleId,
        $isNeedCompleteOnlyTypeTasks,
        $typeNameString
    ) {
        $resultIds = [];
        try {
            $methodGet = '/tasks.task.list';
            $url = $hook . $methodGet;

            // for get
            $filter = [
                'GROUP_ID' => $callingTaskGroupId,
                'UF_CRM_TASK' => $crmForCurrent,
                'RESPONSIBLE_ID' => $responsibleId,
                '!=STATUS' => 5, // Исключаем задачи со статусом "завершена"

            ];
            if ($isNeedCompleteOnlyTypeTasks) {
                $filter['%TITLE']  =   $typeNameString;
            }


            $select = [
                'ID',
                'TITLE',
                'MARK',
                'STATUS',
                'GROUP_ID',
                'STAGE_ID',
                'RESPONSIBLE_ID'

            ];
            $getTaskData = [
                'filter' => $filter,
                'select' => $select,

            ];
            $response = Http::get($url, $getTaskData);

            $responseData = APIBitrixController::getBitrixRespone($response, 'cold: getCurrentTasksIds');

            // if (isset($responseData['result'])) {
            if (isset($responseData['tasks'])) {
                // Log::info('tasks', [$responseData['result']]);
                $resultTasks = $responseData['tasks'];
                foreach ($resultTasks  as $key =>  $task) {
                    if (isset($task['id'])) {
                        // Log::info('task', ['taskId' => $task['id']]);
                        array_push($resultIds, $task['id']);
                    }

                    // array_push($resultTasks, $task);
                }
            }
            // }
            return $resultIds;
        } catch (\Throwable $th) {
            return $resultIds;
        }
    }

    protected function completeTask($hook, $taskIds)
    {
        $responseData = null;
        Log::info(
            'APRIL_HOOK completeTask data',
            [

                'hook' => $hook,
                'taskIds' => $taskIds
            ]
        );
        try {
            $methodUpdate = 'tasks.task.update';
            $methodComplete = 'tasks.task.complete';

            $batchCommands = [];

            foreach ($taskIds as $taskId) {
                $batchCommands['cmd']['updateTask_' . $taskId] = $methodUpdate . '?taskId=' . $taskId . '&fields[MARK]=P';
                $batchCommands['cmd']['completeTask_' . $taskId] = $methodComplete . '?taskId=' . $taskId;
            }

            $response = Http::post($hook . '/batch', $batchCommands);
            $responseData = APIBitrixController::getBitrixRespone($response, 'cold: completeTask');
            Log::info(
                'APRIL_HOOK completeTask ',
                [

                    'responseData' => $responseData,

                ]
            );
            return $responseData;
        } catch (\Throwable $th) {
            Log::channel('telegram')->error('HOOK TASK SERVICE', ['message' => 'tasks was not completed', 'hook' => $hook]);
            return $responseData;
        }
    }
}
