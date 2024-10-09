<?php

namespace App\Jobs;

use App\Services\BitrixCallingColdService;
use App\Services\FullEventReport\EventReportService;
use App\Services\FullEventReport\EventReportTMCBatchService;
use App\Services\FullEventReport\EventReportTMCService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class EventJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */

    protected $data;

    public function __construct(
        $data
    ) {
        $this->data = $data;
        // $portal = PortalControlle::getPortal($domain);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info("Event Job from Redis queue.");
        $isTmc = false;
        if (!empty($this->data)) {
            if (!empty($this->data['departament'])) {

                if (!empty($this->data['departament']['mode'])) {
                    if (!empty($this->data['departament']['mode']['code'])) {
                        if (!empty($this->data['departament']['mode']['code'])) {
                            if ($this->data['departament']['mode']['code'] == 'tmc') {
                                $isTmc = true;
                            }
                        }
                    }
                }
            }
        }
        if ($isTmc) {
            // Log::channel('telegram')->info("Redis tmc queue.");
            // $service = new EventReportTMCService($this->data);
            $service = new EventReportTMCBatchService($this->data);

        } else {
            Log::channel('telegram')->info("Redis sale queue.");
            $service = new EventReportService($this->data);
        }

        $service->getEventFlow();
    }
}
