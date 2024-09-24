<?php

namespace App\Jobs\EventBatch;

use App\Services\BitrixCallingColdService;
use App\Services\FullBatch\ColdBatchService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ColdBatchJob implements ShouldQueue
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
        // $rand = rand(1, 2);
        sleep(1);
        // $urand = mt_rand(1000, 9000); // случайное число от 300000 до 900000 микросекунд (0.3 - 0.9 секунды)
        // usleep($urand);
        // $service = new BitrixCallingColdService($this->data);
        // $reult =  $service->getCold();
        $service = new ColdBatchService(
            $this->data
        );
        $result =  $service->getCold();
    }
}