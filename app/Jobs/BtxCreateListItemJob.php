<?php

namespace App\Jobs;

use App\Services\HookFlow\BitrixListFlowService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class BtxCreateListItemJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    protected $hook;
    protected $bitrixLists;
    protected $eventType;
    protected $eventAction;
    protected $stringType;
    protected $eventName;
    protected $deadline;
    protected $createdId;
    protected $suresponsibleId;
    protected $responsibleId;
    protected $entityId;
    protected $comment;
    public function __construct(
        $hook,
        $bitrixLists,
        $eventType, // xo warm presentation,
        $eventAction,  // plan done expired 
        $stringType,
        $deadline,
        $createdId,
        $responsibleId,
        $suresponsibleId,
        $entityId,
        $comment,
    ) {
        $this->hook =  $hook;
        $this->bitrixLists =  $bitrixLists;
        $this->$eventType = $eventType;
        $this->$eventAction = $eventAction;
        $this->deadline =  $deadline;
        $this->stringType =  $stringType;
        $this->deadline =  $deadline;
        $this->createdId =  $createdId;
        $this->responsibleId =  $responsibleId;
        $this->suresponsibleId =  $suresponsibleId;
        $this->entityId =   $entityId;
        $this->comment = $comment;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $randomNumber = 5;
        sleep($randomNumber);
        Log::channel('telegram')->error('APRIL_HOOK', [
           'BtxCreateListItemJob' => [
                'randomNumber' => $randomNumber,
         
            ]
        ]);
        BitrixListFlowService::getListsFlow(
            $this->hook,
            $this->bitrixLists,
            $this->eventType,
            $this->eventAction,
            $this->deadline,
            $this->stringType,
            $this->deadline,
            $this->createdId,
            $this->responsibleId,
            $this->suresponsibleId,
            $this->entityId,
            $this->comment
        );
        
    }
}
