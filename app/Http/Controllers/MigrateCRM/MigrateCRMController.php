<?php

namespace App\Http\Controllers\MigrateCRM;

use App\Http\Controllers\APIOnlineController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\InstallHelpers\GoogleInstallController;
use App\Http\Controllers\PortalController;
use App\Services\BitrixGeneralService;
use Illuminate\Support\Facades\Log;

class MigrateCRMController extends Controller
{


    public static function crm($token, $domain)
    {
        $result = null;
        $clients = [];
        try {
            $hook = PortalController::getHook($domain);
            $googleData = GoogleInstallController::getData($token);

            if (!empty($googleData)) {
                if (!empty($googleData['infoblocks'])) {
                    $clients = $googleData['clients'];
                }
            }

            if (!empty($clients)) {


                foreach ($clients as $index => $client) {
                    if ($index <= 3) {
                        $newClientData = [
                            'TITLE' => $client['name'],
                            'UF_CRM_OP_WORK_STATUS' => $client['name'],
                            'UF_CRM_OP_PROSPECTS_TYPE' => $client['name'],
                            'UF_CRM_OP_CLIENT_STATUS' => $client['name'], //ЧОК ОК
                            'UF_CRM_OP_SMART_LID' => $client['name'], // сюда записывать id из старой crm
                            'UF_CRM_OP_CONCURENTS' => $client['name'],  // конкуренты
                            'UF_CRM_OP_CATEGORY' => $client['name'],  // ККК ..
                            'UF_CRM_OP_WORK_STATUS' => $client['name'],
                            'ASSIGNED_BY_ID' => $client['name'],
                            'ADDRESS' => $client['name'],
                        ];

                        $newCompany = BitrixGeneralService::setEntity(
                            $hook,
                            'company',
                            $newClientData
                        );

                        Log::channel()->info('TEST CRM MIGRATE', [
                            'newCompany' => $newCompany
                        ]);
                    }
                }
            }


            return APIOnlineController::getError(
                'infoblocks not found',
                ['clients' => $clients, 'googleData' => $googleData]
            );
        } catch (\Throwable $th) {
            return APIOnlineController::getError(
                $th->getMessage(),
                null
            );
        }
    }
}
