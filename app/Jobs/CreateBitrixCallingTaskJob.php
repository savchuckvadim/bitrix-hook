<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\BitrixCallingTaskService;
use Illuminate\Support\Facades\Log;

class CreateBitrixCallingTaskJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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
    protected $isOneMoreJob;
    
    /**
     * Create a new job instance.
     */
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
        $isOneMore,
    ) {
        $this->type = $type;
        $this->domain = $domain;
        $this->companyId = $companyId;
        $this->leadId = $leadId;

        $this->createdId = $createdId;
        $this->responsibleId = $responsibleId;
        $this->deadline = $deadline;
        $this->name = $name;
        $this->comment = $comment;
        // $this->crm = $crm;
        $this->currentBitrixSmart = $currentBitrixSmart;
        $this->sale = $sale;
        $this->isOneMoreJob = $isOneMore;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info("Processing job from Redis queue.");
        $service = new BitrixCallingTaskService(
            $this->type,
            $this->domain,
            $this->companyId,
            $this->leadId,
            $this->createdId,
            $this->responsibleId,
            $this->deadline,
            $this->name,
            $this->comment,
            $this->currentBitrixSmart,
            $this->sale,
            $this->isOneMoreJob
        );
        $service->createCallingTaskItem();
    }
}
