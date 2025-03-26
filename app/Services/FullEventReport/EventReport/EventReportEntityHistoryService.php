<?php

namespace App\Services\FullEventReport\EventReport;

use App\Http\Controllers\APIOnlineController;
use App\Services\BitrixGeneralService;
use Carbon\Carbon;
use DateTime;
use DateTimeZone;
use Illuminate\Support\Facades\Log;

class EventReportEntityHistoryService

{
    protected $domain;

    protected $hook;

    protected $fieldId = 'UF_CRM_OP_HISTORY'; //postfail date field type datetime
    protected $entityId;
    protected $entityType = 'company'; //

    protected $entity;

    protected $currentUser;
    protected $currentUserName;
    protected $nowDate;
    protected $comment;
    protected $isFail;
    public function __construct(

        $domain,
        $hook,
        $entityType,
        $entity,
        $currentUser,
        $nowDate,
        $comment,
        $isFail,


    ) {
        $this->domain = $domain;
        $this->hook = $hook;
        $this->entity = $entity;
        $this->entityId = $entity['ID'];

        $this->nowDate = $nowDate;
        date_default_timezone_set('Asia/Irkutsk');

        $nowDate = new DateTime("now", new DateTimeZone(date_default_timezone_get()));

        $this->nowDate = $nowDate->format('d.m.Y');


        $this->comment = $comment;
        $this->entityType = $entityType;
        $this->currentUser = $currentUser;
        $this->isFail = $isFail;
        if (!empty($this->currentUser)) {
            if (!empty($this->currentUser['NAME'])) {
                $this->currentUserName = $this->currentUser['NAME'];
            }
            if (!empty($this->currentUser['LAST_NAME'])) {
                $this->currentUserName .= ' ' . $this->currentUser['LAST_NAME'];
            }
        }
        // APIOnlineController::sendLog('EventReportEntityHistoryService', [

        //     'entityId' => $this->entityId,

        //     'domain' => $this->domain,
        //     'entityType' => $this->entityType,
        //     'currentUserName' => $this->currentUserName,

        //     'nowDate' => $this->nowDate,
        //     'comment' => $this->comment,
        //     'isFail' => $this->isFail,
        // ]);
      
    }
    public function process()
    {

        $maxLength = 10000;

        
        $entity = BitrixGeneralService::getEntityByID(
            $this->hook,
            $this->entityType,
            $this->entityId,
            null,
            ['UF_CRM_OP_HISTORY']
        
        );
        $currentHistory = $entity['UF_CRM_OP_HISTORY'] ?? '';
        APIOnlineController::sendLog('EventReportEntityHistoryService', [

           
            'entity' => $entity['UF_CRM_OP_HISTORY'],

        ]);
        $isEmptyCurrentHistory = mb_strlen($currentHistory, 'UTF-8') < 1;
        $currentComment = $this->getHistoryString($isEmptyCurrentHistory);

        // Склеиваем новый текст
        $newText = $currentHistory . $currentComment;

        // Проверка длины
        if (mb_strlen($newText, 'UTF-8') > $maxLength) {
            // Обрезаем начало
            $cutLength = mb_strlen($newText, 'UTF-8') - $maxLength;
            $newText = mb_substr($newText, $cutLength, null, 'UTF-8');
        }

        $fieldsData = [
            'UF_CRM_OP_HISTORY' => $newText

        ];
        BitrixGeneralService::updateEntity(
            $this->hook,
            $this->entityType,
            $this->entityId,
            $fieldsData
        );
    }
    protected function getHistoryString($isEmptyCurrentHistory)
    {
        // | 04.07.2024 - Шаматов Алексей - Не берут трубку 
        // | 04.07.2024 Шаматов Алексей - Попал в отдел управления персоналом, до гб можно дозвониться по номеру 481058 Елена Анатольевна. 
        // | 04.07.2024 - Шаматов Алексей - Не берут трубку 
        // | 08.07.2024 __ РЕЗЮМЕ __ (Шаматов А.) - Соединили с гб Еленой Анатольевной. Есть система, отказ от демо_________

        $fullCommentString = '';
        if (!$isEmptyCurrentHistory) {
            $fullCommentString .= '  |  ';
        }
        $fullCommentString .=   $this->nowDate;
        if ($this->isFail) {
            $fullCommentString .= ' __ РЕЗЮМЕ __ ';
        }
        $fullCommentString .= ' (' . $this->currentUserName . ') - ' . $this->comment;

        if ($this->isFail) {
            $fullCommentString .= ' _________    ';
        }



        APIOnlineController::sendLog('getFullEventComment', [

            'fullCommentString' => $fullCommentString,

        ]);
        return $fullCommentString;
    }
}
