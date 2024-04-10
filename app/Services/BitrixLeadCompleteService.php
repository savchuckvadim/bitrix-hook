<?php

namespace App\Services;

use App\Http\Controllers\APIOnlineController;
use App\Http\Controllers\PortalController;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BitrixCallingTaskService
{
    protected $portal;
    protected $aprilSmartData;
    protected $hook;
    protected $callingGroupId;
    protected $smartCrmId;
    protected $smartEntityTypeId;
    protected $isNeedCreateSmart;

    protected $type;
    protected $domain;
    protected $companyId;
    protected $leadId;

    protected $createdId;
    protected $responsibleId;
    protected $deadline;
    protected $name;
    protected $comment;
    // protected $crm;
    protected $currentBitrixSmart;
    protected $sale;
    protected $isOneMoreService;

    protected $taskTitle;

    protected $categoryId = 28;
    protected $stageId = 'DT162_28:NEW';
    protected $lastCallDateFieldCold = 'ufCrm10_1701270138';
    protected $commentField = 'ufCrm10_1709883918';
    protected $callThemeFieldCold = 'ufCrm10_1703491835';


    // принимает лид и компани айди
    // компани айди может быть null - тогда ничего не делаем
    // лид может быть завершен отрицательно - смарт тоже надо завершить отказом

    public function __construct(
        $type,
        $domain,
        $companyId,
        $leadId,
        $createdId,
        $responsibleId,
        $deadline,
        $name,
        $comment,
        // $crm,
        $currentBitrixSmart,
        $sale,
        $isOnemoreJob
    ) {

        $portal = PortalController::getPortal($domain);
        $portal = $portal['data'];
        $this->portal = $portal;
        $this->aprilSmartData = $portal['bitrixSmart'];


        $this->type = $type;
        $this->domain = $domain;
        $this->companyId = $companyId;
        $this->leadId = $leadId;

        $this->createdId = $createdId;
        $this->responsibleId = $responsibleId;

        $this->name = $name;
        $this->comment = $comment;
        // $this->crm = $crm;
        $this->currentBitrixSmart = $currentBitrixSmart;
        $this->sale = $sale;
        $this->isOneMoreService = $isOnemoreJob;

        $stringType = 'Звонок запланирован  ';

        if ($type) {
            if ($type === 'warm') {
                $stringType = 'Звонок запланирован  ';
            } else   if ($type === 'presentation') {
                $stringType = 'Презентация запланирована  ';
            }
        }





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

        $isNeedCreateSmart = false;
        if (!$currentBitrixSmart) { //если не пришло смарта - он вообще не существует и надо создавать
            $isNeedCreateSmart = true;
        } else { //смарт с фронта пришел
            if (isset($currentBitrixSmart['stageId'])) {
                if ($currentBitrixSmart['stageId'] == 'DT162_26:SUCCESS' || $currentBitrixSmart['stageId'] === 'DT156_14:SUCCESS') {
                    //пришел завершенный смарт
                    if ($sale) {
                        if (isset($sale['isCreateNewSale'])) {
                            if ($sale['isCreateNewSale']) {
                                $isNeedCreateSmart = true;
                            }
                        }
                    }
                }
            }
        }

        $this->isNeedCreateSmart =  $isNeedCreateSmart;


        $targetDeadLine = $deadline;
        $nowDate = now();
        if ($domain == 'alfacentr.bitrix24.ru') {

            $novosibirskTime = Carbon::createFromFormat('d.m.Y H:i:s', $deadline, 'Asia/Novosibirsk');
            $targetDeadLine = $novosibirskTime->setTimezone('Europe/Moscow');
            $targetDeadLine = $targetDeadLine->format('Y-m-d H:i:s');
        }
        $this->deadline = $targetDeadLine;
        $this->taskTitle = $stringType . $name . '  ' . $deadline;

        if ($domain == 'alfacentr.bitrix24.ru') {
            // DT156_12:UC_LEWVV8	Звонок согласован
            // DT156_12:UC_29HBRD	Презентация согласована
            $this->categoryId = 12;
            if ($type === 'warm') {

                $this->stageId = 'DT156_12:UC_LEWVV8';
            } else   if ($type === 'presentation') {


                $this->stageId = 'DT156_12:UC_29HBRD';
            }
        } else if ($domain == 'april-garant.bitrix24.ru') {
            $this->categoryId = 26;
            // DT162_26:UC_Q5V5H0	Теплый прозвон
            // DT162_26:UC_NFZKDU	Презентация запланирована

            if ($type === 'warm') {

                $this->stageId = 'DT162_26:UC_Q5V5H0';
            } else   if ($type === 'presentation') {


                $this->stageId = 'DT162_26:UC_NFZKDU';
            }
        }
        
        Log::channel('telegram')->error('APRIL_HOOK', [
            'APRIL_HOOK' => [
                '$type' => $type,
                '$domain' => $domain,
                'stageId' => $this->stageId,
                'categoryId' => $this->categoryId,
            ]
        ]);
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