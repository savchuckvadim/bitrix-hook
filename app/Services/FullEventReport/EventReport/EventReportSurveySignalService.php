<?php

namespace App\Services\FullEventReport\EventReport;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Сигнал в новый Nest-бэк: "создана незапланированная презентация N".
 *
 * НИКАКИХ данных опросника здесь нет и не должно быть: значения хвоста/5К
 * легаси-фронт шлёт в Nest напрямую, а этот сигнал лишь сообщает точный id
 * unplanned-сделки (его знает только hook — из ответа батча), чтобы Nest
 * дописал в неё сводные поля (rendezvous на стороне Nest).
 *
 * Безопасность потока:
 *  - выключатель: пустой env EVENT_SURVEY_SIGNAL_URL = сервис полностью нем;
 *  - весь process() в try/catch — ошибка сигнала НИКОГДА не роняет отчёт;
 *  - fire-and-forget с коротким таймаутом, ответ не разбирается.
 */
class EventReportSurveySignalService
{
    protected $domain;
    protected $unplannedDealIds;
    protected $baseDealId;
    protected $leadId;
    protected $companyId;

    public function __construct(
        $domain,
        $unplannedDealIds,
        $baseDealId = null,
        $leadId = null,
        $companyId = null
    ) {
        $this->domain = $domain;
        $this->unplannedDealIds = is_array($unplannedDealIds)
            ? $unplannedDealIds
            : [$unplannedDealIds];
        $this->baseDealId = $baseDealId;
        $this->leadId = $leadId;
        $this->companyId = $companyId;
    }

    /**
     * Достать реальные id unplanned-сделок из ответа батча
     * (sendGeneralBatchRequest): чанк => [commandKey => результат], ключи
     * unplanned-команд начинаются с "set_unplanned_" (crm.deal.add -> int id).
     */
    public static function extractUnplannedDealIds($response)
    {
        $ids = [];
        if (!is_array($response)) {
            return $ids;
        }
        foreach ($response as $chunk) {
            if (!is_array($chunk)) {
                continue;
            }
            foreach ($chunk as $commandKey => $value) {
                if (strpos((string) $commandKey, 'set_unplanned_') !== 0) {
                    continue;
                }
                if (is_numeric($value) && (int) $value > 0) {
                    $ids[] = (int) $value;
                }
            }
        }
        return $ids;
    }

    public function process()
    {
        try {
            $url = env('EVENT_SURVEY_SIGNAL_URL');
            if (empty($url)) {
                return;
            }
            if (empty($this->unplannedDealIds)) {
                return;
            }

            foreach ($this->unplannedDealIds as $dealId) {
                if (empty($dealId)) {
                    continue;
                }
                $payload = [
                    'domain' => $this->domain,
                    'unplannedDealId' => (int) $dealId,
                ];
                if (!empty($this->baseDealId) && is_numeric($this->baseDealId)) {
                    $payload['baseDealId'] = (int) $this->baseDealId;
                }
                if (!empty($this->leadId) && is_numeric($this->leadId)) {
                    $payload['leadId'] = (int) $this->leadId;
                }
                if (!empty($this->companyId) && is_numeric($this->companyId)) {
                    $payload['companyId'] = (int) $this->companyId;
                }

                Http::timeout(5)->post($url, $payload);
            }
        } catch (\Throwable $th) {
            // Сигнал — best effort: Nest недоступен или URL кривой — отчёт
            // не страдает, сводные на unplanned просто не допишутся.
            Log::warning('EventReportSurveySignalService failed', [
                'domain' => $this->domain,
                'message' => $th->getMessage(),
            ]);
        }
    }
}
