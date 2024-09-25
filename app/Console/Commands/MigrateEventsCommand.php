<?php

namespace App\Console\Commands;

use App\Http\Controllers\MigrateCRM\MColdController;
use Illuminate\Console\Command;


class MigrateEventsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'events:crm';

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
        $domain = '';
        set_time_limit(0);
        $migrateContraller = new MColdController('token', $domain);
        $migrateContraller->crm();
        return 0; // Успешное выполнение команды
    }
   
}
