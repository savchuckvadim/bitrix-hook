<?php

namespace App\Services\HookFlow;

use App\Http\Controllers\APIBitrixController;
use App\Http\Controllers\APIOnlineController;
use App\Services\BitrixGeneralService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BitrixEntityFlowService


{

    public function __construct()
    {
    }
    static function coldflow(
        $portal,
        $hook,
        $entityType,
        $entityId,
        $eventType, // xo warm presentation,
        $eventAction,  // plan done expired 
        $entityFieldsUpdatingContent, //updting fields
    ) {
        sleep(1);
        // Log::channel('telegram')->info('APRIL_HOOK updateCompany', ['$entityFieldsUpdatingContent' => $entityFieldsUpdatingContent]);
        // Log::info('APRIL_HOOK updateCompany', ['$entityFieldsUpdatingContent' => $entityFieldsUpdatingContent]);

        try {
            if ($entityType == 'company') {
                $updatedCompany = BitrixEntityFlowService::updateCompanyCold($hook, $entityId, $entityFieldsUpdatingContent);
            } else if ($entityType == 'lead') {
                $updatedLead = BitrixEntityFlowService::updateLeadCold($hook, $entityId, $entityFieldsUpdatingContent);
            }


            return APIOnlineController::getSuccess(['result' => 'success']);
        } catch (\Throwable $th) {
            $errorMessages =  [
                'message'   => $th->getMessage(),
                'file'      => $th->getFile(),
                'line'      => $th->getLine(),
                'trace'     => $th->getTraceAsString(),
            ];
            Log::error('ERROR COLD: Exception caught',  $errorMessages);
            Log::info('error COLD', ['error' => $th->getMessage()]);
            return APIOnlineController::getError($th->getMessage(),  $errorMessages);
        }
    }

    public function flow(
        $portal,
        $currentBtxEntity,
        $portalCompanyData,
        $hook,
        $entityType,
        $entityId,
        $planEventType, // xo warm presentation,
        $eventAction,  // plan done expired 
        //updting fields

        $createdId,
        $responsibleId,
        $deadline,
        $nowdate,
        $isPresentationDone,
        $isUnplannedPresentation,
        $workStatus,  // inJob setAside ...
        $resultStatus, //result | noresult ...
        $failType,
        $failReason,
        $noResultReason,
        $currentReportEventType,
        $currentReportEventName,
        $currentFieldsForUpdate,

    ) {
        sleep(1);
        // Log::channel('telegram')->info('APRIL_HOOK updateCompany', ['$entityFieldsUpdatingContent' => $entityFieldsUpdatingContent]);
        // Log::info('APRIL_HOOK updateCompany', ['$entityFieldsUpdatingContent' => $entityFieldsUpdatingContent]);


        // $data =   [
        //     // 'plan' => $this->plan,
        //     // 'report' => $this->report,
        //     // 'presentation' => $this->presentation,
        //     'isPlanned' => $this->isPlanned,
        //     'isPresentationDone' => $this->isPresentationDone,
        //     'isUnplannedPresentation' => $this->isUnplannedPresentation,
        //     'currentReportEventType' => $this->currentReportEventType,
        //     'currentPlanEventType' => $this->currentPlanEventType,
        //     '$this->portalCompanyData' => $this->portalCompanyData



        // ];


        try {
            if (!empty($portalCompanyData) && !empty($portalCompanyData['bitrixfields'])) {
                $fields = $portalCompanyData['bitrixfields'];

                Log::channel('telegram')->info('APRIL_HOOK updateCompany', [
                    'portal fields' => $fields,
                    'currentFieldsForUpdate' => $currentFieldsForUpdate,
                    'currentBtxEntity' => $currentBtxEntity

                ]);
                Log::info('APRIL_HOOK updateCompany', [
                    'portal fields' => $fields,
                    'currentFieldsForUpdate' => $currentFieldsForUpdate,
                    'currentBtxEntity' => $currentBtxEntity

                ]);

                $updatedFields = $this->getReportFields(
                    [],
                    $currentBtxEntity,
                    $currentFieldsForUpdate,
                    $fields, //portal fields
                    $planEventType,
                    $createdId,
                    $responsibleId,
                    $deadline,
                    $nowdate,
                    $isPresentationDone,
                    $isUnplannedPresentation,
                    $workStatus,  // inJob setAside ...
                    $resultStatus, //result | noresult ...
                    $failType,
                    $failReason,
                    $noResultReason,
                    $currentReportEventType,
                    $currentReportEventName,

                );

                // $entityFieldsUpdatingContent



                if ($entityType == 'company') {
                    $updatedCompany = BitrixEntityFlowService::updateCompanyCold(
                        $hook,
                        $entityId,
                        $updatedFields
                    );
                } else if ($entityType == 'lead') {
                    $updatedLead = BitrixEntityFlowService::updateLeadCold(
                        $hook,
                        $entityId,
                        $updatedFields
                    );
                }
            }

            return APIOnlineController::getSuccess(['result' => 'success']);
        } catch (\Throwable $th) {
            $errorMessages =  [
                'message'   => $th->getMessage(),
                'file'      => $th->getFile(),
                'line'      => $th->getLine(),
                'trace'     => $th->getTraceAsString(),
            ];
            Log::error('ERROR COLD: Exception caught',  $errorMessages);
            Log::info('error COLD', ['error' => $th->getMessage()]);
            return APIOnlineController::getError($th->getMessage(),  $errorMessages);
        }
    }




    // company
    static function updateCompanyCold($hook, $companyId, $fields)
    {




        // UF_CRM_10_1709907744 - дата следующего звонка

        $result = null;
        // $fields = [
        //     // 'UF_CRM_1709798145' => $responsibleId,
        //     // 'UF_CRM_10_170990774' => $this->deadline,   //  - дата следующего звонка
        //     ...$this->entityFieldsUpdatingContent
        // ];



        $result =  BitrixGeneralService::updateCompany($hook, $companyId, $fields);
        // Log::channel('telegram')->error('APRIL_HOOK updateCompany', ['$result' => $result]);

        return $result;
    }


    //lead

    static function updateLeadCold($hook, $leadId, $fields)
    {


        // $responsibleId = $this->responsibleId;

        $result = null;


        // $fields = [
        //     'ASSIGNED_BY_ID' => $responsibleId,
        //     ...$this->entityFieldsUpdatingContent
        // ];


        $result =  BitrixGeneralService::updateLead($hook, $leadId, $fields);

        return $result;
    }

    //entities
    static function getEntities($hook, $currentTask)
    {
        $response = null;
        $resultFields = null;
        $batchCommands = [];
        try {
            if (!empty($currentTask['ufCrmTask'])) {
                foreach ($currentTask['ufCrmTask'] as $ufCrm) {
                    $parts = explode('_', $ufCrm); // Разделяем строку по символу '_'
                    $type = $parts[0]; // Тип это все, что перед '_'
                    $id = $parts[1]; // ID это все, что после '_'

                    $keyName = '';
                    $method = '';
                    switch ($type) {
                        case 'CO':
                            $method = 'crm.company.get';
                            $keyName = 'company_' . $id;

                            break;
                        case 'D':
                            $method = 'crm.deal.get';
                            $keyName = 'deal_' . $id;
                            break;
                        default:
                            # code...
                            break;
                    }

                    $batchCommands['cmd'][$keyName] = $method . '?id=' . $id;
                }
            }

            $response = Http::post($hook . '/batch', $batchCommands);
            $responseData = APIBitrixController::getBitrixRespone($response, 'event: getEntities');

            if (!empty($responseData['result'])) {
                foreach ($responseData['result'] as $key => $value) {
                    if (strpos($key, 'company_') === 0) {
                        $resultFields['companies'][] = $value;
                    } elseif (strpos($key, 'deal_') === 0) {
                        $resultFields['deals'][] = $value;
                    }
                }
            }

            return $resultFields;
        } catch (\Throwable $th) {
            Log::info(
                'APRIL_HOOK getEntities ',
                [
                    'error' => $th->getMessage(),
                    'response' => $response,
                    'resultFields' => $resultFields,
                    'batchCommands' => $batchCommands,


                ]
            );
            return $resultFields;
        }
    }


    //fields

    //report fields
    protected function getReportFields(
        $updatedFields,
        $currentBtxEntity,
        $currentFieldsForUpdate,
        $portalFields,
        $planEventType,
        // 0: {id: 1, code: "warm", name: "Звонок"}
        // // 1: {id: 2, code: "presentation", name: "Презентация"}
        // // 2: {id: 3, code: "hot", name: "Решение"}
        // // 3: {id: 4, code: "moneyAwait", name: "Оплата"}
        $createdId,
        $responsibleId,
        $deadline,
        $nowdate,
        $isPresentationDone,
        $isUnplannedPresentation,
        $workStatus,  // inJob setAside ...
        $resultStatus, //result | noresult ...
        $failType,
        $failReason,
        $noResultReason,
        $reportEventType, //xo warm presentaton
        $currentReportEventName,


    ) {

        
        Log::channel('telegram')->info('HOOK TEST CURRENTENTITY', [
            'portalFields' => $portalFields,
        ]);
        //general report fields 
        foreach ($portalFields as $pField) {
            if (!empty($pField) && !empty($pField['code'])) {
                $portalFieldCode = $pField['code'];


                foreach ($currentFieldsForUpdate as $targetFieldCode) {  //массив кодов - которые нужно обновить



                    if ($portalFieldCode === $targetFieldCode) {
                        if ($portalFieldCode == 'op_prospects_type') {
                            Log::channel('telegram')->info('HOOK TEST CURRENTENTITY', [
                                'op_prospects_type' => $pField['code'],

                            ]);
                        }


                        switch ($portalFieldCode) {

                            case 'manager_op':
                                $updatedFields['UF_CRM_' . $pField['bitrixId']] = 'user_1';
                                break;
                            case 'op_history':
                            case 'op_mhistory':
                                $now = now();
                                $stringComment = $now . ' ' . $currentReportEventName . ' ' . $resultStatus;
                                $updatedFields = $this->getCommentsWithEntity(
                                    $currentBtxEntity,
                                    $pField,
                                    $stringComment,
                                    $updatedFields,

                                );


break;
                                // /statusesCodes
                            case 'op_work_status':
                                $updatedFields['UF_CRM_' . $pField['bitrixId']] = $this->getWorstatusFieldItemValue(
                                    $pField, //with items
                                    $workStatus,
                                    $planEventType // only PLAN ! event type
                                );
                                break;
                            case 'op_prospects_type':  //Перспективность
                                $updatedFields['UF_CRM_' . $pField['bitrixId']] = $this->getProspectsFieldItemValue(
                                    $pField, //with items
                                    $workStatus,
                                    $failType
                                );
                                break;


                                //xo

                                //warm
                            case 'call_next_date':   //ОП Дата Следующего звонка
                            case 'call_next_name':   //ОП Тема Следующего звонка
                            case 'call_last_date':  //ОП Дата последнего звонка

                                //in_progress

                                //money_a



                                //presentation
                            case 'next_pres_plan_date':
                            case 'last_pres_plan_date':
                            case 'last_pres_done_date':
                            case 'last_pres_plan_responsible':
                            case 'last_pres_done_responsible':
                            case 'pres_count':
                            case 'pres_comments':
                            case 'call_last_date':


                                //fail
                            case 'op_fail_comments':
                                break;
                            default:
                                # code...
                                break;
                        }
                    }
                }
            }
        }

        // if ($reportEventType == 'xo') {

        //     foreach ($portalFields as $pField) {
        //         switch ($pField['code']) {
        //             case 'call_last_date':
        //                 $now = date('d.m.Y H:i:s');
        //                 $updatedFields['UF_CRM_' . $pField['bitrixId']] = $now;
        //                 break;

        //             default:
        //                 # code...
        //                 break;
        //         }
        //     }
        // } else  if ($reportEventType == 'warm') {
        //     foreach ($portalFields as $pField) {
        //         switch ($pField['code']) {
        //             case 'call_last_date':
        //                 $now = date('d.m.Y H:i:s');
        //                 $updatedFields['UF_CRM_' . $pField['bitrixId']] = $now;
        //                 break;
        //             case 'manager_op':
        //                 $updatedFields['UF_CRM_' . $pField['bitrixId']] = 'user_1';
        //                 break;
        //         }
        //     }
        // } else if ($reportEventType == 'presentation') {
        //     foreach ($portalFields as $pField) {
        //         switch ($pField['code']) {

        //             case 'manager_op':
        //                 $updatedFields['UF_CRM_' . $pField['bitrixId']] = 'user_1';
        //                 break;
        //         }
        //     }
        // } else if ($reportEventType == 'in_progress') {
        //     foreach ($portalFields as $pField) {
        //         switch ($pField['code']) {

        //             case 'manager_op':
        //                 $updatedFields['UF_CRM_' . $pField['bitrixId']] = 'user_1';
        //                 break;
        //         }
        //     }
        // } else if ($reportEventType == 'money_await') {
        //     foreach ($portalFields as $pField) {
        //         switch ($pField['code']) {

        //             case 'manager_op':
        //                 $updatedFields['UF_CRM_' . $pField['bitrixId']] = 'user_1';
        //                 break;
        //         }
        //     }
        // } else if ($reportEventType == 'other') {
        //     foreach ($portalFields as $pField) {
        //         switch ($pField['code']) {

        //             case 'manager_op':
        //                 $updatedFields['UF_CRM_' . $pField['bitrixId']] = 'user_1';
        //                 break;
        //         }
        //     }
        // }


        if ($workStatus == 'fail') {
        }
        Log::channel('telegram')->info('HOOK TEST CURRENTENTITY', [
            'updatedFields' => $updatedFields
        ]);
        return $updatedFields;
    }


    protected function getCommentsWithEntity(
        $currentBtxEntity,
        $pField,
        $stringComment,
        $fields
    ) {
        $fullFieldId = 'UF_CRM_' . $pField['bitrixId'];  //UF_CRM_OP_MHISTORY
        // $now = now();
        // $stringComment = $now . ' ХО запланирован ' . $data['name'] . ' на ' . $data['deadline'];

        $currentComments = '';


        if (!empty($currentBtxEntity)) {
            // if (isset($currentBtxCompany[$fullFieldId])) {

            $currentComments = $currentBtxEntity[$fullFieldId];

            if ($pField['code'] == 'op_mhistory') {
                $currentComments = [];
                array_push($currentComments, $stringComment);
                // if (!empty($currentComments)) {
                //     array_push($currentComments, $stringComment);
                // } else {
                //     $currentComments = $stringComment;
                // }
            } else {
                $currentComments = $currentComments  . ' | ' . $stringComment;
            }
            // }
        }


        $fields[$fullFieldId] =  $currentComments;
        return $fields;
    }

    protected function getWorstatusFieldItemValue(
        $portalField, //with items
        $workStatus,
        $planEventType // only PLAN ! event type
    ) {
        $resultItemBtxId = null;
        //         inJob
        // setAside
        // success
        // fail
        // op_work_status
        // В работе	work
        // Отложена	long
        // В решении	in_progress
        // В оплате	money_await
        // Продажа	    op_status_success
        // Отказ	    op_status_fail
        $resultCode = 'work';
        switch ($workStatus) {
            case 'inJob':
                $resultCode = 'work';

                if ($planEventType == 'hot') {
                    $resultCode = 'in_progress';
                } else  if ($planEventType == 'moneyAwait') {
                    $resultCode = 'money_await';
                }


                break;
            case 'setAside': //in_long
                $resultCode = 'long';
                break;
            case 'fail':
                $resultCode = 'op_status_fail';
                break;
            case 'success':
                $resultCode = 'op_status_success';
                break;
            default:
                break;
        }


        if (!empty($portalField)) {
            if (!empty($portalField['items'])) {
                $pitems = $portalField['items'];
                foreach ($pitems as $pitem) {
                    if (!empty($pitem['code'])) {
                        if ($pitem['code'] == $resultCode) {
                            $resultItemBtxId = $pitem['bitrixId'];
                        }
                    }
                }
            }
        }
        Log::channel('telegram')->info('HOOK TEST getWorstatusFieldItemValue', [
            'resultCode' => $resultCode,
            'planEventType' => $planEventType,
            'workStatus' => $workStatus,
            'resultItemBtxId' => $resultItemBtxId,
        ]);
        return $resultItemBtxId;
    }


    protected function getProspectsFieldItemValue(
        $portalField, //with items
        $workStatus,
        $failType
    ) {
        $resultItemBtxId = null;
        //         inJob
        // setAside
        // success
        // fail

        // 0: {id: 0, code: "garant", name: "Гарант/Запрет"}
        // // 1: {id: 1, code: "go", name: "Покупает ГО"}
        // // 2: {id: 2, code: "territory", name: "Чужая территория"}
        // // 3: {id: 3, code: "accountant", name: "Бухприх"}
        // // 4: {id: 4, code: "autsorc", name: "Аутсорсинг"}
        // // 5: {id: 5, code: "depend", name: "Несамостоятельная организация"}
        // // 6: {id: 6, code: "failure", name: "Отказ"}


        // Перспективность	op_prospects_type	Перспективная	op_prospects_good	calling
        // Нет перспектив	op_prospects_nopersp	calling
        // Гарант/Запрет	op_prospects_garant	calling
        // Покупает ГО	op_prospects_go	calling
        // Чужая территория	op_prospects_territory	calling
        // Бухприх	op_prospects_acountant	calling
        // Аутсорсинг	op_prospects_autsorc	calling
        // Несамостоятельная организация	op_prospects_depend	calling
        // недозвон	op_prospects_nophone	calling
        // компания не существует	op_prospects_company	calling
        // не хотят общаться	op_prospects_off	calling
        // Отказ	op_prospects_fail	calling


        $resultCode = 'op_prospects_good';
        if ($workStatus !== 'inJob' && $workStatus !== 'success') {

            if (!empty($failType) && !empty($failType['code'])) {
                $failCode = $failType['code'];

                switch ($failCode) {
                    case 'op_prospects_nopersp': //Нет перспектив
                        $resultCode = 'op_prospects_nopersp';

                        break;
                    case 'garant': //Гарант/Запрет
                        $resultCode = 'op_prospects_garant';
                        break;
                    case 'go':
                        $resultCode = 'op_prospects_go';
                        break;
                    case 'territory':
                        $resultCode = 'op_prospects_territory';
                        break;
                    case 'accountant':
                        $resultCode = 'op_prospects_acountant';
                        break;
                    case 'autsorc':
                        $resultCode = 'op_prospects_autsorc';
                        break;
                    case 'depend':
                        $resultCode = 'op_prospects_depend';
                        break;

                        //todo
                        // case 'op_prospects_nophone':  //недозвон
                        // case 'op_prospects_company': //компания не существует
                        // case 'op_prospects_off': //не хотят общаться


                    case 'failure':
                        $resultCode = 'op_prospects_fail';

                        break;


                    default:
                        break;
                        break;
                }
            }
        }
        if (!empty($portalField)) {
            if (!empty($portalField['items'])) {
                $pitems = $portalField['items'];
                foreach ($pitems as $pitem) {
                    if (!empty($pitem['code'])) {
                        if ($pitem['code'] == $resultCode) {
                            $resultItemBtxId = $pitem['bitrixId'];
                        }
                    }
                }
            }
        }
        Log::channel('telegram')->info('HOOK TEST getProspectsFieldItemValue', [
            'resultCode' => $resultCode,
            'failType' => $failType,
            'workStatus' => $workStatus,
             'resultItemBtxId' => $resultItemBtxId,


        ]);
        return $resultItemBtxId;
    }

    //plan fields
    protected function getPlanFields(
        $updatedFields,
        $portalFields,


    ) {
        // $isPresentationDone = $this->isPresentationDone;
        // $isUnplannedPresentation = $this->isUnplannedPresentation;
        // $isResult  = $this->isResult;
        // $isInWork  = $this->isInWork;
        // $isSuccessSale  = $this->isSuccessSale;
        // $reportEventType = $this->currentReportEventType;
        // $currentReportEventName = $this->currentReportEventName;

        // $resultStatus = 'Совершен';

        // if ($isInWork) {
        //     $resultStatus = $resultStatus . ' в работе';
        // }


        // //general report fields 
        // foreach ($portalFields as $pField) {
        //     switch ($pField['code']) {

        //         case 'manager_op':
        //             $updatedFields['UF_CRM_' . $pField['bitrixId']] = 'user_1';
        //             break;
        //         case 'op_history':
        //         case 'op_mhistory':
        //             $now = now();
        //             $stringComment = $now . ' ' . $currentReportEventName . ' ' . $resultStatus;
        //             $updatedFields = $this->getCommentsWithEntity($pField, $stringComment, $updatedFields);
        //             break;
        //         default:
        //             # code...
        //             break;
        //     }
        // }

        // if ($reportEventType == 'xo') {

        //     foreach ($portalFields as $pField) {
        //         switch ($pField['code']) {
        //             case 'call_last_date':
        //                 $now = date('d.m.Y H:i:s');
        //                 $updatedFields['UF_CRM_' . $pField['bitrixId']] = $now;
        //                 break;

        //             default:
        //                 # code...
        //                 break;
        //         }
        //     }
        // } else  if ($reportEventType == 'warm') {
        //     foreach ($portalFields as $pField) {
        //         switch ($pField['code']) {
        //             case 'call_last_date':
        //                 $now = date('d.m.Y H:i:s');
        //                 $updatedFields['UF_CRM_' . $pField['bitrixId']] = $now;
        //                 break;
        //             case 'manager_op':
        //                 $updatedFields['UF_CRM_' . $pField['bitrixId']] = 'user_1';
        //                 break;
        //         }
        //     }
        // } else if ($reportEventType == 'presentation') {
        //     foreach ($portalFields as $pField) {
        //         switch ($pField['code']) {

        //             case 'manager_op':
        //                 $updatedFields['UF_CRM_' . $pField['bitrixId']] = 'user_1';
        //                 break;
        //         }
        //     }
        // } else if ($reportEventType == 'in_progress') {
        //     foreach ($portalFields as $pField) {
        //         switch ($pField['code']) {

        //             case 'manager_op':
        //                 $updatedFields['UF_CRM_' . $pField['bitrixId']] = 'user_1';
        //                 break;
        //         }
        //     }
        // } else if ($reportEventType == 'money_await') {
        //     foreach ($portalFields as $pField) {
        //         switch ($pField['code']) {

        //             case 'manager_op':
        //                 $updatedFields['UF_CRM_' . $pField['bitrixId']] = 'user_1';
        //                 break;
        //         }
        //     }
        // } else if ($reportEventType == 'other') {
        //     foreach ($portalFields as $pField) {
        //         switch ($pField['code']) {

        //             case 'manager_op':
        //                 $updatedFields['UF_CRM_' . $pField['bitrixId']] = 'user_1';
        //                 break;
        //         }
        //     }
        // }

        // return $updatedFields;
    }
}



        //проведено презентаций smart
        // UF_CRM_10_1709111529 - april
        // 	UF_CRM_6_1709894507 - alfa
        // компании 
        // UF_CRM_1709807026


        //дата следующего звонка smart
        // UF_CRM_6_1709907693 - alfa
        // UF_CRM_10_1709907744 - april


        //комментарии smart
        //UF_CRM_6_1709907513 - alfa
        // UF_CRM_10_1709883918 - april


        //название обзвона - тема
        // UF_CRM_6_1709907816 - alfa
        // UF_CRM_10_1709907850 - april