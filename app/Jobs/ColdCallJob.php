<?php

namespace App\Jobs;

use App\Services\BitrixCallingColdService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ColdCallJob implements ShouldQueue
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
        Log::info("Processing job from Redis queue.");
        $rand = rand(1, 5);
        sleep($rand);
        $service = new BitrixCallingColdService($this->data);
        $reult =  $service->getCold();
    }
}
