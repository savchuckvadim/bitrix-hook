<?php

namespace App\Console\Commands;

use App\Http\Controllers\APIBitrixController;
use App\Http\Controllers\MigrateCRM\MigrateCRMController;
use App\Http\Controllers\PortalController;
use App\Imports\ClientsImport;
use App\Imports\ContactsImport;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Maatwebsite\Excel\Facades\Excel;

class MigrateCRMCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'json:crm';

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
        $domain = 'april-dev.bitrix24.ru';
       
        $migrateContraller = new MigrateCRMController('token', $domain);
        $migrateContraller->crm();
        return 0; // Успешное выполнение команды
    }
   
}
