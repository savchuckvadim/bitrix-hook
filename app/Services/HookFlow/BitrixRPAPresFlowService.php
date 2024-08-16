<?php

namespace App\Services\HookFlow;

use App\Services\General\BitrixRPAService;
use Illuminate\Support\Facades\Log;

class BitrixRPAPresFlowService



{

    protected $portalRPA;
    protected $rpaTypeId;
    protected $portalRPAFields;
    protected $portalRPAStages;
    protected $hook;




    public function __construct(
        $hook,
        $pRPA,


    ) {
        $this->hook = $hook;
        $this->portalRPA = $pRPA;

        $this->rpaTypeId = $pRPA['bitrixId'];
        if (!empty($pRPA['bitrixfields'])) {

            $this->portalRPAFields = $pRPA['bitrixfields'];
        }
        if (!empty($pRPA['categories']) && is_array($pRPA['categories'])) {

            if (!empty($pRPA['categories'][0])) {

                if (!empty($pRPA['categories'][0]['stages'])) {

                    $this->portalRPAStages = $pRPA['categories'][0]['stages'];
                }
            }
        }
    }


    //rpa flow

    public function getRPAPresInitFlow(
        $tmcDealId,
        $nowDate,
        $deadline,
        $created,
        $responsible,
        // $bossId = 1,
        $companyId,
        // $contactId,
        $comment,
        $name,



    ) {
        try {
            //создает заявку на презентацию
            // заполняет поля и в  первую стадию
            // ОП Дата назначенной презентации	rpa_pres	datetime		next_pres_plan_date
            // ОП Дата последней назначенной презентации	rpa_pres	datetime		last_pres_plan_date
            // ОП Дата последней проведенной презентации	rpa_pres	datetime		last_pres_done_date
            // ОП Кто назначил последнюю заявку на презентацию	rpa_pres	employee		last_pres_plan_responsible
            // ОП Кто назначен ответственным	rpa_pres	employee		last_pres_done_responsible
            // ОП Проведено презентаций	rpa_pres	integer		pres_count
            // ОП Комментарии после презентаций	rpa_pres	multiple		pres_comments
            // Менеджер по продажам Гарант	rpa_pres	employee		manager_op
            // Менеджер ТМЦ	rpa_pres	employee		manager_tmc
            // Менеджер Отдела Сопровождения	rpa_pres	employee		manager_os
            // Менеджер Отдела Обучения	rpa_pres	employee		manager_edu
            // ОП История (Комментарии)	rpa_pres	multiple		op_mhistory
            // Компания	rpa_pres	crm		rpa_crm_company
            // Контакт	rpa_pres	crm		rpa_crm_contact
            // Презентация Сделка	rpa_pres	crm		rpa_crm_deal
            // ТМЦ Сделка	rpa_pres	crm		rpa_crm_tmc_deal
            // Основная Сделка	rpa_pres	crm		rpa_crm_base_deal
            // Комментарий к заявке Руководитель	rpa_pres	multiple		rpa_owner_comment
            // Комментарий к заявке ТМЦ	rpa_pres	multiple		rpa_tmc_comment
            // Комментарий к заявке Менеджер	rpa_pres	multiple		rpa_manager_comment
            // Комментарий к заявке Обучение	rpa_pres	multiple		rpa_edu_comment
            // Требуется обучение	rpa_pres	boolean		rpa_is_need_edu
            // Описание требования по обучению	rpa_pres	multiple		rpa_need_edu_comment
            // Дата обучения	rpa_pres	datetime		rpa_need_edu_date
            // Требуется тех поддержка	rpa_pres	boolean		rpa_is_need_technic
            // Описание требования по тех. поддержке	rpa_pres	multiple		rpa_need_technic_comment
            // Дата по тех. поддержке	rpa_pres	datetime		rpa_need_technic_date



            $eventActionName = 'Запланирована';
            $evTypeName = 'Презентация';

            // Log::info('HOOK TEST currentBtxDeals', [
            //     '$rpa case' => true,
            //     'tmcDealId' => $tmcDealId,
            //     'nowDate' => $nowDate,
            //     'deadline' => $deadline,
            //     'created' => $created,
            //     'responsible' => $responsible,
            //     'companyId' => $companyId,
            //     'comment' => $comment,
            //     'name' => $name,


            // ]);

            $presentatationInitRPAFields = [
                [
                    'code' => 'name', //дата начала
                    'name' => 'Название',
                    'value' => 'Заявка ' . $name . ' от ' . $nowDate
                ],
                [
                    'code' => 'next_pres_plan_date', //дата начала
                    'name' => 'ОП Дата назначенной презентации',
                    'value' => $nowDate //$nowDate->format('d.m.Y H:i:s'),
                ],
                [
                    'code' => 'last_pres_plan_responsible',
                    'name' => 'ОП Кто назначил последнюю заявку на презентацию',
                    'value' => $responsible
                ],

                // [
                //     'code' => 'last_pres_done_responsible',
                //     'name' => 'ОП Кто назначен ответственным',
                //     'value' => $deadline
                // ],
                [
                    'code' => 'rpa_crm_company',
                    'name' => 'Компания',
                    'value' => $companyId,
                ],
                // [
                //     'code' => 'rpa_crm_contact',
                //     'name' => 'Контакт',
                //     'value' => $contactId,
                // ],
                [
                    'code' => 'rpa_tmc_comment',
                    'name' => 'Комментарий к заявке',
                    'value' => $comment,
                ],
                [
                    'code' => 'rpa_crm_tmc_deal',
                    'name' => 'ТМЦ Сделка',
                    'value' => $tmcDealId, // []
                ],


            ];
            $currentDataField = [];
            $currentDataField['stageId'] = 'NEW';
            // $fieldsData['title'] = 'Заявка на презентацию ' . $name;
            // $fieldsData['name'] = 'Заявка на презентацию ' . $name;


            // $fieldsData['UF_RPA_69_NAME'] = 'Заявка ' . $name . ' от ' . $nowDate;

            foreach ($presentatationInitRPAFields as  $presValue) {

                $fieldCode = $presValue['code'];
                foreach ($this->portalRPAFields as $pField) {
                    if ($fieldCode == $pField['code']) {

                        $fieldId = $pField['bitrixId'];
                        $fieldsData[$fieldId] = $presValue['value'];
                    }
                }
            }
            $fieldsData['createdBy'] =  67;
            $dataForCreate = [
                'typeId' => $this->rpaTypeId,
                'fields' => $fieldsData
            ];

            $rpaService = new BitrixRPAService(
                $this->hook
            );



            $resultItem = $rpaService->setRPAItem(
                $dataForCreate
            );

            $fieldsData = [
                'createdBy' => $responsible,
                'updatedByBy' => $responsible,
            ];
            $dataForUpdate = [
                'id' => $resultItem['id'],
                'typeId' => $this->rpaTypeId,
                'fields' => $fieldsData
            ];
            $resultItem = $rpaService->updateRPAItem(
                $dataForUpdate
            );
            Log::channel('telegram')->info('TEST RPA', [
                'resultItem' => $resultItem
            ]);
            return $resultItem;
        } catch (\Throwable $th) {
            $errorMessages =  [
                'message'   => $th->getMessage(),
                'file'      => $th->getFile(),
                'line'      => $th->getLine(),
                'trace'     => $th->getTraceAsString(),
            ];
            Log::error('ERROR COLD: getListsFlow',  $errorMessages);

            Log::channel('telegram')->error('APRIL_HOOK Pres Lists Flow', $errorMessages);
        }
    }

    protected function convertFieldFormat($string)
    {
        // Удалить 'UF_' и разделить строку по '_'
        $parts = explode('_', str_replace('UF_', '', $string));

        // Преобразовать части в нужный регистр
        $result = strtolower(array_shift($parts));  // первая часть в нижний регистр
        foreach ($parts as $part) {
            $result .= ucfirst(strtolower($part));  // остальные с заглавной буквы
        }

        return $result;
    }
}
