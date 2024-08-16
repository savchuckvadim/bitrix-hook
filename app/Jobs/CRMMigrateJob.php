<?php

namespace App\Jobs;

use App\Http\Controllers\MigrateCRM\MigrateCRMController;
use App\Services\BitrixCallingColdService;
use App\Services\FullEventReport\EventReportService;
use App\Services\FullEventReport\EventReportTMCService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CRMMigrateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */

    protected $token;
    protected $domain;
    public function __construct(
        $token,
        $domain
    ) {
        $this->token = $token;
        $this->domain = $domain;
        // $portal = PortalControlle::getPortal($domain);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $controller = new  MigrateCRMController($this->token, $this->domain);
        $controller->crm();
    }
}
