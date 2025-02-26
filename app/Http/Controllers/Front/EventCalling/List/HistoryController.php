<?php

namespace App\Http\Controllers\Front\EventCalling\List;


use App\Http\Controllers\APIOnlineController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\PortalController;
use App\Services\General\BitrixBatchService;
use Illuminate\Http\Request;


class HistoryController extends Controller


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





    public  function getHistory($companyId)
    {

        $batchResults = null;
        $currentActionsData = [];
        $actionFieldId = null;
        try {
            // $domain = $request['domain'];


            // $companyId = $request['companyId'];
            $listId = $this->portalKPIList['bitrixId'];
            $listFields = $this->portalKPIList['bitrixfields'];
            $eventActionField = null;
            $eventActionTypeField = null;


            $companyIdField = null;
            $companyIdFieldId = null;

            $commentField = null;
            $commentFieldId = null;


            if (!empty($listFields)) {

                foreach ($listFields as $plField) {
                    if ($plField['code'] === 'sales_history_event_action') {
                        $eventActionField = $plField;
                        $actionFieldId = $eventActionField['bitrixCamelId']; //like PROPERTY_2119 
                    }
                    if ($plField['code'] === 'sales_history_event_type') {
                        $eventActionTypeField = $plField;
                        $actionTypeFieldId = $eventActionTypeField['bitrixCamelId']; //like PROPERTY_2119 
                    }
                    if ($plField['code'] === 'sales_history_crm') {
                        $companyIdField = $plField;
                        $companyIdFieldId = $companyIdField['bitrixCamelId']; //like PROPERTY_2119 

                    }
                    if ($plField['code'] === 'sales_history_manager_comment') {
                        $commentField = $plField;
                        $commentFieldId = $commentField['bitrixCamelId']; //like PROPERTY_2119 

                    }
                }
            }

            // Добавляем команду в массив команд

            $data = [
                'IBLOCK_TYPE_ID' => 'lists',
                'IBLOCK_ID' => $listId,
                'filter' => [
                    $companyIdFieldId => '%' . $companyId . '%',
                ],
                'select' => [

                    $commentFieldId,
                    $actionFieldId,
                    $actionTypeFieldId
                ]

            ];
            $batchService = new BitrixBatchService($this->hook);
            $command = $batchService->getGeneralBatchCommand($data, 'lists.element.get');
            //lists
            // Отправляем batch запрос
            $batchResults = $batchService->sendGeneralBatchRequest([$command]);


            //voximplant
            return APIOnlineController::getSuccess(
                [
                    'commands' => $command,
                    'history' =>  $batchResults,
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
}
