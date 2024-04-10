<?php

namespace App\Services;

use App\Http\Controllers\PortalController;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class BitrixLeadCompleteService
{
    protected $portal;
    protected $aprilSmartData;
    protected $hook;
    protected $callingGroupId;
    protected $smartCrmId;
    protected $smartEntityTypeId;


    protected $domain;
    protected $companyId;
    protected $leadId;
    protected $responsibleId;


    protected $taskTitle;

    protected $targetCategoryId = 26;
    protected $targetStageId = 'DT162_26:PREPARATION';
    protected $lastCallDateFieldCold = 'ufCrm10_1701270138';
    protected $commentField = 'ufCrm10_1709883918';
    protected $callThemeFieldCold = 'ufCrm10_1703491835';


    // принимает лид и компани айди
    // компани айди может быть null - тогда ничего не делаем
    // лид может быть завершен отрицательно - смарт тоже надо завершить отказом

    public function __construct(
        // $type,
        $domain,
        $companyId,
        $leadId,
        $responsibleId,

    ) {

        $portal = PortalController::getPortal($domain);
        if (isset($portal)) {

            if (isset($portal['data'])) {

                $portal = $portal['data'];

                $this->portal = $portal;
                if (isset($portal['bitrixSmart'])) {
                    $this->aprilSmartData = $portal['bitrixSmart'];
                }

                if (isset($portal['bitrixSmart']['crmId'])) {
                    $this->smartEntityTypeId = $portal['bitrixSmart']['crmId'];
                }




                $this->domain = $domain;
                $this->companyId = $companyId;
                $this->leadId = $leadId;


                $this->responsibleId = $responsibleId;





                $webhookRestKey = $portal['C_REST_WEB_HOOK_URL'];
                $this->hook = 'https://' . $domain  . '/' . $webhookRestKey;


                $callingTaskGroupId = env('BITRIX_CALLING_GROUP_ID');
                if (isset($portal['bitrixCallingTasksGroup']) && isset($portal['bitrixCallingTasksGroup']['bitrixId'])) {
                    $callingTaskGroupId =  $portal['bitrixCallingTasksGroup']['bitrixId'];
                }

                $this->callingGroupId =  $callingTaskGroupId;

                $smartId = 'T9c_';
                if (isset($portal['bitrixSmart']) && isset($portal['bitrixSmart']['crm'])) {
                    $smartId =  $portal['bitrixSmart']['crm'] . '_';
                }

                $this->smartCrmId =  $smartId;

                Log::channel('telegram')->error('APRIL_HOOK', [
                    'APRIL_HOOK' => [
                        // '$type' => $type,
                        '$domain' => $domain,
                        // 'stageId' => $this->stageId,
                        // 'categoryId' => $this->categoryId,
                    ]
                ]);
            }
        }


        if ($domain == 'alfacentr.bitrix24.ru') {

            $this->targetCategoryId = 12;
            $this->targetStageId = 'DT156_12:CLIENT';
        } else if ($domain == 'april-garant.bitrix24.ru') {
            $this->targetCategoryId = 26;
            $this->targetStageId = 'DT162_26:PREPARATION';
        }

  
    }

    public function leadComplete()
    {
        $resultSmart = null;
        try {
            //Создание новой компании из лида или присоеднинение к существующей
            // взять два смарт процесса по лиду и по компании
            // если нет ни одного
            // если есть только по лиду -> засунуть в него компанию - передвинуть в работа с компанией если был на предыдущей стадии или в отказе
            // если есть только компании -> засунуть в него лид

            //если есть оба
            //их id равны - значит это один и тотже - передвинуть в работа с компанией если был на предыдущей стадии или в отказе
            // если их id разные перенести из лидовского смарта количество презентаций комментарии в смарт по компании
            //если у смарта по лиду более высокая стадия перенести в нее

            $smartFromLead = BitrixGeneralService::getSmartItem(
                $this->hook,
                $this->leadId, //lidId ? from lead
                null, //companyId ? from lead
                $this->responsibleId,
                $this->aprilSmartData, //april smart data
            );

            $smartFromCompany = BitrixGeneralService::getSmartItem(
                $this->hook,
                null, //lidId ? from lead
                $this->leadId, //companyId ? from lead
                $this->responsibleId,
                $this->aprilSmartData, //april smart data
            );






            if ($smartFromLead) { //если смарт по лиду есть
                if ($smartFromCompany) { //если смарт по лиду есть + по Компании есть 

                    //сращиваем из двух смартов один
                    $resultMergedSmart = $this->mergeSmarts($smartFromLead, $smartFromCompany, $this->leadId);
                 
                    //обновляем обновленный смарт по компании
                    if (isset($resultMergedSmart['id'])) {
                        $resultSmart = BitrixGeneralService::updateSmartItem(
                            $this->hook,
                            $this->smartEntityTypeId,
                            $resultMergedSmart['id'],
                            $resultMergedSmart

                        );
                    }

                    //удаляем смарт который был по лиду
                    if (isset($smartFromLead['id'])) {
                        BitrixGeneralService::deleteSmartItem(
                            $this->hook,
                            $this->smartEntityTypeId,
                            $smartFromLead['id'],

                        );
                    }
                } else if (!$smartFromCompany) { //если смарт по лиду есть + по Компании НЕТ 

                    //добавить в смарт по лиду компанию //переместить в target stage
                    $updatingLeadSmart = $smartFromLead;
                    if ($this->companyId) {
                        $updatingLeadSmart['ufCrm7_1698134405'] = $this->companyId;
                        $updatingLeadSmart['company_id'] = $this->companyId;
                    }
                    $updatingLeadSmart['stageId'] = $this->targetStageId;
                    $updatingLeadSmart['categoryId'] = $this->targetCategoryId;
                    if (isset($updatingLeadSmart['id'])) {
                        $resultSmart =   BitrixGeneralService::updateSmartItem(
                            $this->hook,
                            $this->smartEntityTypeId,
                            $updatingLeadSmart['id'],
                            $updatingLeadSmart

                        );
                    }
                }
            } else if (!$smartFromLead) { //если смарта по лиду нет
                if ($smartFromCompany) { //если смарта по лиду нет + по Компании есть 

                    //записываем в смарт данные о лиде
                    $updatingCompanySmart = $smartFromCompany;
                    if ($this->leadId) {
                        $updatingCompanySmart['parentId1'] = $this->leadId;
                        $updatingCompanySmart['ufCrm7_1697129037'] = $this->leadId;
                    }
                    $updatingCompanySmart['stageId'] = $this->targetStageId;
                    $updatingCompanySmart['categoryId'] = $this->targetCategoryId;

                    if (isset($updatingCompanySmart['id'])) {
                        $resultSmart =    BitrixGeneralService::updateSmartItem(
                            $this->hook,
                            $this->smartEntityTypeId,
                            $updatingCompanySmart['id'],
                            $updatingCompanySmart

                        );
                    }
                } else if (!$smartFromCompany) { //если смарт по лиду нет + по Компании НЕТ 

                    //создать смарт
                    // добавить в смарт лид и компанию .. переместить в стадию компания
                    $fieldsData = [];  //general data for create
                    if ($this->companyId) {
                        $fieldsData['ufCrm7_1698134405'] = $this->companyId;
                        $fieldsData['company_id'] = $this->companyId;
                    }
                    if ($this->leadId) {
                        $fieldsData['parentId1'] = $this->leadId;
                        $fieldsData['ufCrm7_1697129037'] = $this->leadId;
                    }


                    $resultSmart =   BitrixGeneralService::createSmartItem(
                        $this->hook,
                        $this->smartEntityTypeId,
                        $fieldsData

                    );
                }
            }


            return $resultSmart;
            //обновить компанию -> прибавить количество проведенных презентаций


        } catch (\Throwable $th) {
            $errorMessages =  [
                'message'   => $th->getMessage(),
                'file'      => $th->getFile(),
                'line'      => $th->getLine(),
                'trace'     => $th->getTraceAsString(),
            ];
            Log::error('ERROR COLD APIBitrixController: Exception caught',  $errorMessages);
            Log::info('error COLD APIBitrixController', ['error' => $th->getMessage()]);
            Log::channel('telegram', ['BitrixLeadCompleteService' => $errorMessages]);

            return $resultSmart;
        }
    }


    private function mergeSmarts($smartFromLead, $smartFromCompany, $leadId)
    {
        $fieldsData = [];
        $updatedPresentationCount = 0;
        $categoryId = $this->targetCategoryId;
        $stageId =  null;
        $resultComments = [];


        //sum presentation count ................................
        if (isset($smartFromCompany['ufCrm10_1709111529'])) {
            $updatedPresentationCount = $smartFromCompany['ufCrm10_1709111529'];
        }
        if (isset($smartFromLead['ufCrm10_1709111529'])) {
            $updatedPresentationCount =  $updatedPresentationCount + $smartFromLead['ufCrm10_1709111529'];
        }
        // ................................


        //updating stage id if need change ................................
        if (isset($smartFromCompany['stageId'])) {
            $stageId = $smartFromCompany['stageId'];
        }
        if (isset($smartFromCompany['stageId']) && isset($smartFromLead['stageId'])) {
            $canChangeStage = $this->isCanChangeStageFromLeadToCompanySmart($smartFromLead['stageId'], $smartFromCompany['stageId']);
        }

        if ($canChangeStage) {
            $stageId = $canChangeStage;
        }
        // ................................


        //comments from lead smart to company smart ................................
        // UF_CRM_10_1709883918 - комментарии smart

        if (!empty($smartFromCompany['ufCrm10_1709883918'])) {
            $resultComments = $smartFromCompany['ufCrm10_1709883918'];
        }
        if (!empty($smartFromLead['ufCrm10_1709883918'])) {
            foreach ($smartFromLead['ufCrm10_1709883918'] as $commentFromLead) {
                array_push($resultComments,  $commentFromLead);
            }
        }

        // ................................


        if ($leadId) {
            $fieldsData['parentId1'] = $leadId;
            $fieldsData['ufCrm7_1697129037'] = $leadId;
        }



        //result updating data
        $fieldsData['categoryId'] = $categoryId;
        $fieldsData['stageId'] = $stageId;
        $fieldsData['ufCrm10_1709111529'] = $updatedPresentationCount; //presentation count
        $fieldsData['ufCrm10_1709883918'] = $resultComments;           //comments
        return $fieldsData;
    }

    private function isCanChangeStageFromLeadToCompanySmart(
        $currentSmartStageIdLead,
        $currentSmartStageIdCompany
    ) {

        // $fieldsData['categoryId'] = $this->categoryId;
        // $fieldsData['stageId'] = $this->stageId;
        // $fieldsData['ufCrm7_1698134405'] = $companyId;
        // $fieldsData['assigned_by_id'] = $responsibleId;
        // $fieldsData['company_id'] = $companyId;
        $allStages = [
            // april
            'DT162_28:NEW', //          Создан
            'DT162_28:UC_J1ADFR', //              Запланирован звонок
            'DT162_28:PREPARATION', //	              Просрочен
            'DT162_28:UC_BDM2F0', //              Совершен без результата
            'DT162_28:SUCCESS', //              Успех
            'DT162_28:FAIL', //          Отказ


            'DT162_26:NEW', //          Лид
            'DT162_26:PREPARATION', //	              Компания
            'DT162_26:UC_Q5V5H0', //              Теплый прозвон
            'DT162_26:UC_NFZKDU', //              Презентация запланирована
            'DT162_26:CLIENT', //         	Презентация проведена
            'DT162_26:UC_R7UBSZ', //            	КП
            'DT162_26:UC_4REB8W', //            	Счет
            'DT162_26:UC_DYBZO8', //            	Договор
            'DT162_26:UC_JICYTK', //            	Поставка
            'DT162_26:SUCCESS', //          	Продажа
            'DT162_26:FAIL', //          Провал



            // alfa
            'DT156_14:NEW',  //   Создан
            'DT156_14:UC_TS7I14',  //   Запланирован
            'DT156_14:UC_8Q85WS',  //    Без оценки
            'DT156_14:PREPARATION',  //	Просрочен
            'DT156_14:CLIENT',  //   Недозвон
            'DT156_14:SUCCESS',  //   Успех
            'DT156_14:FAIL',  //Провал

            'DT156_12:NEW',  //   Создан Лид
            'DT156_12:CLIENT',  //   Создана Компания
            'DT156_12:UC_E4BPCB',  //   Работа с компанией
            'DT156_12:UC_LEWVV8',  //   Звонок согласован
            'DT156_12:UC_29HBRD',  //   Презентация согласована
            'DT156_12:UC_Y52JIL',  //   Звонок просрочен
            'DT156_12:UC_02ZP1T',  //   Презентация просрочена
            'DT156_12:UC_QZ3SL2',  //   Звонок состоялся
            'DT156_12:UC_DP0NEJ',  //   Презентация состоялась
            'DT156_12:UC_FA778R',  //   КП сформировано
            'DT156_12:UC_I0J7WW',  //   Счет сформирован
            'DT156_12:UC_HBDIWU',  //   Договор сформирован
            'DT156_12:UC_K2ORG9',  //   Оплачен
            'DT156_12:SUCCESS',  //   Успех
            'DT156_12:FAIL',  //   Отказ

        ];

        $indexLead = array_search($currentSmartStageIdLead, $allStages);
        $indexCompany = array_search($currentSmartStageIdCompany, $allStages);

        // Возвращаем стадию LEAD, если она продвинутее, чем стадия COMPANY. 
        // Проверяем также, что обе стадии найдены в списке.
        if ($indexLead !== false && $indexCompany !== false && $indexLead > $indexCompany) {
            return $currentSmartStageIdLead;
        }

        return false;
    }
}



      