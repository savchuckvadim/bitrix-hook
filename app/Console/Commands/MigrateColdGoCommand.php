<?php

namespace App\Console\Commands;

use App\Http\Controllers\MigrateCRM\MColdController;
use App\Http\Controllers\MigrateCRM\MColdFlowController;
use Illuminate\Console\Command;


class MigrateColdGoCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cold:go';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $domain = 'gsr.bitrix24.ru';
        set_time_limit(0);
        $migrateContraller = new MColdFlowController('token', $domain);
        $migrateContraller->crm();
        return 0; // Успешное выполнение команды
    }
   
}
