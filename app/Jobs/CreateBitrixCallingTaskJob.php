<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\BitrixCallingTaskService;


class CreateBitrixCallingTaskJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $type;
    protected $domain;
    protected $companyId;
    protected $createdId;
    protected $responsibleId;
    protected $deadline;
    protected $name;
    protected $comment;
    // protected $crm;
    protected $currentBitrixSmart;
    protected $sale;

    /**
     * Create a new job instance.
     */
    public function __construct(
        $type,
        $domain,
        $companyId,
        $createdId,
        $responsibleId,
        $deadline,
        $name,
        $comment,
        // $crm,
        $currentBitrixSmart,
        $sale,
    ) {
        $this->type = $type;
        $this->domain = $domain;
        $this->companyId = $companyId;
        $this->createdId = $createdId;
        $this->responsibleId = $responsibleId;
        $this->deadline = $deadline;
        $this->name = $name;
        $this->comment = $comment;
        // $this->crm = $crm;
        $this->currentBitrixSmart = $currentBitrixSmart;
        $this->sale = $sale;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $service = new BitrixCallingTaskService(
            $this->type,
            $this->domain,
            $this->companyId,
            $this->createdId,
            $this->responsibleId,
            $this->deadline,
            $this->name,
            $this->comment,
            $this->currentBitrixSmart,
            $this->sale
        );
        $service->createCallingTaskItem();
    }
}
