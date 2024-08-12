<?php

namespace App\Jobs;

use App\Services\HookFlow\BitrixListFlowService;
use App\Services\HookFlow\BitrixListSuccessFlowService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class BtxSuccessListItemJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */

    protected $hook;
    protected $bitrixLists;
    protected $eventType;
    protected $eventTypeName;
    protected $eventAction;
    protected $stringType;
    protected $eventName;
    protected $deadline;


    protected $createdId;
    protected $suresponsibleId;
    protected $responsibleId;
    protected $entityId;
    protected $comment;
    protected $workStatus;
    protected $resultStatus;
    protected $noresultReason;
    protected $failReason;
    protected $failType;
    protected $dealIds;
    protected $currentBaseDealId;

    public function __construct(
        $hook,
        $bitrixLists,
        $eventType, // xo warm presentation,
        $eventTypeName,
        $eventAction,  // plan done expired 

        // $stringType,
        $deadline,
        $createdId,
        $responsibleId,
        $suresponsibleId,
        $entityId,
        $comment,
        $workStatus,
        $resultStatus,
        $noresultReason = null,
        $failReason = null,
        $failType = null,
        $dealIds,
        $currentBaseDealId = null
    ) {
        $this->hook =  $hook;
        $this->bitrixLists =  $bitrixLists;
        $this->eventType = $eventType;
        $this->eventTypeName = $eventTypeName;
        $this->eventAction = $eventAction;
        $this->deadline =  $deadline;
        // $this->stringType =  $stringType;

        $this->createdId =  $createdId;
        $this->responsibleId =  $responsibleId;
        $this->suresponsibleId =  $suresponsibleId;
        $this->entityId =   $entityId;
        $this->comment = $comment;

        $this->workStatus = $workStatus;
        $this->resultStatus = $resultStatus;

        $this->noresultReason = $noresultReason;
        $this->failReason = $failReason;
        $this->failType = $failType;
        $this->dealIds = $dealIds;
        $this->currentBaseDealId = $currentBaseDealId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $randomNumber = 5;
        sleep($randomNumber);

        BitrixListSuccessFlowService::getListsFlow(
            $this->hook,
            $this->bitrixLists,
            $this->eventType,
            $this->eventTypeName,
            $this->eventAction,
            // $this->stringType,
            $this->deadline,
            $this->createdId,
            $this->responsibleId,
            $this->suresponsibleId,
            $this->entityId,
            $this->comment,
            $this->workStatus,
            $this->resultStatus,
            $this->noresultReason,
            $this->failReason,
            $this->failType,
            $this->dealIds,
            $this->currentBaseDealId



        );
    }
}
